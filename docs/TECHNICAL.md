---
title: Техническая документация anime-db/app-bundle
tags: [project/anime-db, app-bundle, docs/technical]
updated: 2026-06-18
---

# Техническая документация — anime-db/app-bundle

> См. также: [PROJECT.md](PROJECT.md) · [AUDIT.md](AUDIT.md) · [BUGS.md](BUGS.md)

## 1. Планировщик задач (Task Scheduler)

### Модель `Task`

- `command` — строка консольной команды (без префикса `php app/console`);
- `next_run` — когда запустить (datetime, индексируется вместе со `status`);
- `last_run` — когда запускалась в последний раз;
- `modify` — PHP-строка модификации даты (`strtotime`-совместимая), задаёт периодичность;
- `status` — `STATUS_ENABLED (1)` / `STATUS_DISABLED (0)`.

Геттеры `getNextRun()/getLastRun()` возвращают **клон** `\DateTime`, сеттеры тоже клонируют — защита от мутации извне.

### Алгоритм `Task::executed()`

```
1. last_run = now
2. если modify пуст → status = DISABLED (одноразовая задача)
3. если status == ENABLED:
     повторять: next_run.modify(modify)
       пока next_run <= now            (догоняем будущее)
     если modify() вернул false → modify='', status=DISABLED (защита от битого формата)
```

`setInterval($seconds)` — сахар: при `$interval > 0` пишет `modify = "+N second"`.

Валидатор `isModifyValid()` (`@Assert\Callback`) проверяет `strtotime($modify) !== false`. **Важно:** `modify` — это НЕ cron-выражение.

### Демон `TaskSchedulerCommand`

`animedb:task-scheduler` — бесконечный `while (true)`:

1. `Task::getNextTask()` — ближайшая включённая задача с `next_run <= now`;
2. если есть — запуск её команды в фоне (`exec ... &` на *nix, `popen('start /b ...')` на Windows), затем `executed()` + `flush`;
3. `Task::getWaitingTime()` → `sleep()` (но не дольше `MAX_STANDBY_TIME = 3600` сек, чтобы подхватывать вновь добавленные задачи);
4. `gc_collect_cycles()` для борьбы с утечками в долгоживущем процессе.

Запуск самого демона **не управляется** этим бандлом (нет supervisor/systemd-обёртки) — предполагается внешний запуск.

## 2. Уведомления (Notice)

Жизненный цикл: `STATUS_CREATED (0)` → `STATUS_SHOWN (1)` → `STATUS_CLOSED (2)`.

- `Notice::shown()` — при первом показе вычисляет `date_closed = now + lifetime` (по умолчанию `lifetime = 300` сек) и ставит статус SHOWN;
- `date_start` — когда уведомление начинает показываться (для отложенного показа).

### Репозиторий `Notice`

- `getFirstShow()` — первое актуальное уведомление: `status != CLOSED AND date_start <= now AND (date_closed IS NULL OR date_closed >= now)`;
- `seeLater()` — «напомнить позже»: сдвигает `date_start` на `SEE_LATER_INTERVAL = 3600` сек вперёд, а уже показанным с фиксированным `date_closed` — продлевает срок через DQL-функцию `DATETIME()`;
- `getList()/count()/getFilteredQuery()` — выборки для админ-списка.

`NoticeController::showAction` использует кэш-заголовки (`cache_time_keeper`) + ETag по id уведомления — клиент опрашивает endpoint, а 304 экономит рендер.

## 3. Плагины (Plugin)

Плагины — это Composer-пакеты с `"type": "anime-db-plugin"`. ID сущности `Plugin` = имя пакета `vendor/name`.

Поток (`Event\Listener\Package`):

1. Composer-обёртка host-приложения бросает `anime_db.package.installed|updated|removed`;
2. listener фильтрует по типу пакета;
3. при install/update: ищет/создаёт `Plugin`, тянет метаданные (`title`, `description`, `logo`) через `@anime_db.api.client` (`getPlugin($vendor, $package)`), логотип скачивает `Downloader::entity()`;
4. при remove: удаляет `Plugin`.

Ошибки API **молча проглатываются** (`catch (\Exception)`), плагин сохраняется с пустыми полями.

### Особый случай — shmop

Установка пакета `anime-db/shmop` переключает параметр `cache_time_keeper.driver` на `multi` (быстрый слой = shmop), удаление — возвращает на `file`. Listener `Project::onInstalledOrUpdatedAddShmop` добавляет/убирает пакет shmop в `composer.json` в зависимости от наличия PHP-расширения `shmop`.

## 4. Downloader

`Downloader` (Guzzle 3) умеет:

| Метод          | Что делает                                                                               |
|----------------|------------------------------------------------------------------------------------------|
| `download()`   | Скачать URL в файл (с `mkdir` 0755, опцией `override`)                                   |
| `image()`      | `download()` + проверка `finfo` MIME `image/*`; иначе `unlink` («remove dangerous file») |
| `isExists()`   | HEAD-запрос (`CURLOPT_NOBODY`)                                                           |
| `favicon()`    | Качает favicon через прокси-URL Google в `favicon_root.$host.'.ico'`                     |
| `entity()`     | Скачать для `EntityInterface` (логотип плагина и т.п.), basename из URL-пути             |
| `imageField()` | Загрузка изображения формы: remote URL **или** локальный upload, с валидацией            |

Параметры (`parameters.yml`):

- `anime_db.downloader.root` = `web/`;
- `anime_db.downloader.favicon.root` = `web/media/favicon/`;
- `anime_db.downloader.favicon.proxy` = `http://www.google.com/s2/favicons?domain=%s`.

`Entity\Field\Image::setFilename()` префиксует имя как `tmp/YYYYMMDD/<file>` — временные загрузки складываются в `web/media/tmp/...`, откуда их потом чистит `animedb:clear-media-temp`.

`BaseEntity` ведёт список `old_filenames`: при смене имени файла старое запоминается, и listener `Entity` удаляет его с диска на `postUpdate`/`postRemove`.

## 5. CommandExecutor

Три способа запуска:

- `execute($cmd, $timeout>0, $cb)` → foreground через `Symfony\Process`, бросает `RuntimeException` при ненулевом коде;
- `execute($cmd, $timeout<=0)` → background (`exec($cmd.' &')` / `popen('start /b ...')`);
- `send($cmd)` → raw `fsockopen` POST на собственный маршрут `command_exec` (fire-and-forget, ответ не читается, таймаут 2 сек).

`prepare()` нормализует команду:

- префикс `php ` (ровно 4 символа) → подставляет путь к бинарнику PHP (`PhpFinder::getPath()`, уже `escapeshellarg`);
- ` app/console ` → экранированный путь к `console`;
- `/dev/null` → `nul` на Windows.

Связка: контроллер `Command:exec` (`/command/exec.html`) принимает команду по HTTP и запускает в фоне (`execute(..., 0)`); `send()` — клиентская часть, которая шлёт на этот endpoint. Так приложение порождает фоновые процессы из веб-контекста, не блокируя ответ.

## 6. Пагинация (`Util\Pagination`)

Чистая, не зависящая от фреймворка библиотека:

- `Builder` (сервис) — фабрика `Configuration` с дефолтным `max_navigate` из параметра;
- `Configuration` — total/current страницы, окно навигации, шаблон ссылки (`%s` или callback), отдельная ссылка на первую страницу;
- `View` — ленивая генерация узлов: `first/prev/current/next/last` + итерируемый список окна. Окно центрируется вокруг текущей страницы с корректировкой у краёв (`left_offset`/`right_offset`);
- `Node` — одна ссылка (page, link, is_current).

`buildLink()` поддерживает callback и спец-ссылку для первой страницы.

## 7. Локализация

- `Event\Listener\Request` определяет локаль: явный параметр `%locale%` → иначе первый валидный язык из `Accept-Language` (валидируется `@Assert\Locale`) → иначе дефолт запроса. Затем `setlocale`, `Request::setLocale`, translator и Gedmo translatable;
- `Event\Listener\Console` ставит локаль для CLI (дефолт `en`).

Переводы — XLIFF только на русском (`*.ru.xlf`).

## 8. Filesystem-утилита

Статические методы:

- `getUserHomeDir()` — кроссплатформенный home (env `HOME`, иначе эвристики Windows с `iconv cp1251→utf-8`);
- `scandir($path, $filter, $order)` — листинг с фильтром FILE/DIRECTORY, скрывает `.*`, `*~`, `pagefile.sys`, добавляет «..»; пути оборачиваются `Patchwork\Utf8::wrapPath`;
- `getRealPath()` — нормализация разделителей + хвостовой слэш.

Используется формой выбора локального пути (`Form/Type/Field/LocalPath`) — пользователь выбирает каталог коллекции на своей машине.

## 9. Сборка / окружение

- автозагрузка PSR-4: `AnimeDb\Bundle\AppBundle\` → `src/`, тесты → `tests/`;
- `minimum-stability: dev`, `branch-alias dev-master: 0.4.x-dev`;
- тестовый bootstrap требует `vendor/autoload.php` (composer install);
- PHPUnit-конфиг: bootstrap `tests/bootstrap.php`, whitelist `src/` кроме `src/Resources`, clover-покрытие в `build/`.

⚠️ Локальная среда разработки сейчас — **PHP 8.3**, а заявленный стек (PHP 5.4, Symfony 2, PHPUnit 4, Guzzle 3) на нём не установится и не запустится. Подробнее — [RECOMMENDATIONS.md](RECOMMENDATIONS.md).
