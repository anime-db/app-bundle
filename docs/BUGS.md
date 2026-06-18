---
title: Найденные баги и уязвимости anime-db/app-bundle
tags: [project/anime-db, app-bundle, docs/bugs, security]
updated: 2026-06-18
---

# Баги и уязвимости — anime-db/app-bundle

> Аудит статический (без запуска: `vendor/` отсутствует, локальный PHP 8.3 несовместим со стеком).
> См. также: [AUDIT.md](AUDIT.md) · [RECOMMENDATIONS.md](RECOMMENDATIONS.md)

## Сводка

| # | Серьёзность | Где                                                  | Кратко                                                    |
|---|-------------|------------------------------------------------------|-----------------------------------------------------------|
| 1 | 🔴 Critical | `Controller/CommandController::execAction`           | Удалённое выполнение произвольных команд (RCE)            |
| 2 | 🟠 High     | `Event/Listener/Project::onUpdatedProposeUpdateTask` | Разыменование null при отсутствии задачи                  |
| 3 | 🟡 Medium   | `Service/Downloader::favicon` + `media_favicon`      | Path traversal / запись файла вне каталога favicon        |
| 4 | 🟡 Medium   | `Service/CommandExecutor::send`                      | Параметр `$host` игнорируется, используется `$this->host` |
| 5 | 🟢 Low      | `Service/Downloader::imageField`                     | Имя файла из `getClientOriginalName()` без санитизации    |
| 6 | 🟢 Low      | `Command/ProposeUpdateCommand`                       | Состояние хранится в mtime `composer.json`                |
| 7 | 🟢 Low      | `Command/TaskSchedulerCommand`                       | Возможный busy-loop / нет graceful shutdown               |
| 8 | ℹ️ Info     | `Event/Listener/Project`                             | Подозрительная строка modify `'+%s seconds  01:00:00'`    |
| 9 | ℹ️ Info     | весь форм-слой                                       | API форм Symfony 2 — несовместим с Symfony 3+             |

---

## 1. 🔴 RCE через `/command/exec.html`

**Файл:** `src/Controller/CommandController.php:24-32`, маршрут `command_exec` (`routing.yml:52`).

```php
public function execAction(Request $request)
{
    ignore_user_abort(true);
    set_time_limit(0);
    $this->get('anime_db.command')->execute($request->get('command'), 0);
    return new Response();
}
```

Параметр `command` берётся из запроса **как есть** и уходит в `CommandExecutor::execute(..., 0)` → `executeCommandInBackground()` → `exec($command.' &')`. `prepare()` переписывает только префикс `php `, всё прочее проходит насквозь. Аутентификации, CSRF-токена, белого списка команд, проверки источника — нет.

**Последствие:** любой, кто может послать POST на этот endpoint, выполняет произвольные shell-команды с правами процесса приложения. Для приложения, которое слушает HTTP, это полная компрометация машины.

**Смягчающий фактор:** приложение «для домашнего использования», обычно на `localhost`. Но: (а) часто биндится на `0.0.0.0`; (б) уязвимо к CSRF/SSRF и атакам из локальной сети; (в) `CommandExecutor::send()` шлёт на этот endpoint plaintext-POST — то есть архитектурно endpoint открыт.

**Рекомендация:**
- Убрать приём произвольной команды по HTTP. Заменить на enum заранее зарегистрированных команд (id → команда), без передачи строки от клиента.
- Если механизм нужен — закрыть endpoint shared-secret токеном (сверять с серверным параметром), ограничить `127.0.0.1` на уровне firewall/маршрута, добавить CSRF-защиту.
- Минимум — `escapeshellcmd`/`escapeshellarg` и валидация по строгому шаблону. (Только как временная мера — это не делает дизайн безопасным.)

---

## 2. 🟠 Null-разыменование в `Project::onUpdatedProposeUpdateTask`

**Файл:** `src/Event/Listener/Project.php:52-64`.

```php
$task = $this->em->getRepository('AnimeDbAppBundle:Task')
    ->findOneBy(['command' => 'animedb:propose-update']);
$next_run = new \DateTime();
$next_run->modify(sprintf('+%s seconds  01:00:00', ProposeUpdateCommand::INERVAL_UPDATE));
$this->em->persist($task->setNextRun($next_run));   // ← $task может быть null
```

Если задачи с командой `animedb:propose-update` в БД нет (например, миграция её не создала или её удалили), `findOneBy` вернёт `null`, и вызов `$task->setNextRun()` упадёт с фатальной ошибкой (`Call to a member function on null`). Срабатывает по событию `anime_db.project.updated`, то есть при каждом обновлении проекта.

**Рекомендация:** проверять `if ($task instanceof Task)` (или создавать задачу, если её нет) перед обращением.

---

## 3. 🟡 Path traversal в favicon

**Файлы:** `src/Service/Downloader.php:157-166`, `src/Controller/MediaController.php:22-30`, маршрут `media_favicon` с `requirements: { host: .+ }`.

```php
$target = $this->favicon_root.$host.'.ico';   // host из URL, requirement .+ → допускает / и ..
```

`host` приходит из URL `/media/favicon/{host}.ico`, и requirement `.+` разрешает слэши и точки. Значение подставляется в путь записи файла без нормализации. Подобранный `host` (например `../../something`) уводит запись `.ico`-файла за пределы `web/media/favicon/`. Дополнительно `host` инжектится в прокси-URL Google через `sprintf(...domain=%s...)`.

**Смягчающие факторы:** к имени всегда дописывается `.ico`; `image()` проверяет MIME и удаляет не-картинки; реальную загрузку выполняет Google-прокси (не сервер напрямую — SSRF ограничен). Но запись файла в произвольный путь — реальный риск.

**Рекомендация:** валидировать `host` строгим шаблоном домена (`requirements: { host: '[a-zA-Z0-9.\-]+' }`), дополнительно `basename()`/whitelist перед формированием пути.

---

## 4. 🟡 `CommandExecutor::send()` игнорирует аргумент `$host`

**Файл:** `src/Service/CommandExecutor.php:157-174`.

```php
public function send($command, $host = '')
{
    $host = $host ?: $this->host;          // вычислили...
    if (!$host) { throw ... }
    ...
    $fp = fsockopen($this->host, 80, ...); // ...но используем $this->host
    $request .= 'Host: '.$host."\r\n";     // а здесь — $host
}
```

В `fsockopen` подставляется `$this->host` (свойство), а не локальная переменная `$host`. Передать кастомный хост через аргумент невозможно — соединение всегда идёт на хост из текущего запроса. Заголовок `Host:` при этом берётся из `$host` — рассинхрон. Также порт жёстко `80` (игнорирует порт из конструктора, где host = `getHost().':'.getPort()`).

**Рекомендация:** использовать локальную `$host` в `fsockopen`; разобрать host:port корректно; вынести порт в параметр.

---

## 5. 🟢 Небезопасное имя локально загружаемого файла

**Файл:** `src/Service/Downloader.php:231-238`.

```php
$entity->setFilename($entity->getLocal()->getClientOriginalName());
$info = pathinfo($this->getTargetDirForImageField($entity, $override));
$entity->getLocal()->move($info['dirname'], $info['basename']);
```

Имя берётся из `getClientOriginalName()` (контролируется клиентом). `pathinfo()['basename']` отсекает каталоги, что снимает прямой traversal, но не нормализует спецсимволы/двойные расширения/Юникод-омоглифы. Для remote-ветки имя строится из `parse_url(...PATH)` + `pathinfo basename` — аналогично.

**Рекомендация:** генерировать собственное безопасное имя (хэш/uuid + проверенное по MIME расширение), не доверять оригинальному имени.

---

## 6. 🟢 Состояние в mtime `composer.json`

**Файл:** `src/Command/ProposeUpdateCommand.php:53-73`.

Команда определяет «пора обновляться» по `filemtime(composer.json/.lock)` и затем делает `touch(composer.json, time() + INERVAL_NOTIFICATION - INERVAL_UPDATE)` — искусственно меняет mtime, чтобы следующее напоминание вышло через 5 дней. Хрупко: любой `composer install/update` сбросит mtime; mtime — не предназначен для хранения логического состояния.

**Рекомендация:** хранить дату последнего напоминания в БД/параметре (`last_update` уже есть в `parameters.yml`, но не используется), а не в mtime файла.

---

## 7. 🟢 `TaskSchedulerCommand` — долгоживущий цикл без управления

**Файл:** `src/Command/TaskSchedulerCommand.php:51-79`.

`while (true)` без условия выхода и обработки сигналов. Если включённая задача имеет `next_run <= now`, но её `executed()` оставляет следующий запуск тоже `<= now` (например, очень частый интервал или битый `modify`, не пойманный валидатором), `getWaitingTime()` вернёт `0`, и цикл будет крутиться без паузы, нагружая CPU и плодя фоновые процессы. Нет graceful shutdown (SIGTERM), нет лимита числа итераций/процессов.

**Рекомендация:** обработка `pcntl` сигналов; минимальный `sleep` (≥1 сек) даже при `getWaitingTime()==0`; защита от «убежавших» задач (минимальный интервал, счётчик подряд идущих немедленных запусков).

---

## 8. ℹ️ Подозрительная строка modify

**Файл:** `src/Event/Listener/Project.php:60`.

```php
$next_run->modify(sprintf('+%s seconds  01:00:00', ProposeUpdateCommand::INERVAL_UPDATE));
```

Двойной пробел и комбинация относительного (`+N seconds`) и абсолютного (`01:00:00`) формата в одной строке `modify`. PHP это «проглатывает» (выставит время 01:00:00 после сдвига на N секунд), но намерение неочевидно и легко ломается. **Рекомендация:** разбить на явные шаги и покрыть тестом.

---

## 9. ℹ️ Форм-API уровня Symfony 2

Везде в формах/контроллерах используется устаревший API:
- типы форм по строковым именам (`'text'`, `'file'`, `'choice'`) и `getName()`;
- `createForm(new ChoiceLocalPath())` — передача **экземпляра** типа;
- `$form->getErrors()` с индексом `[0]` (`FormController:114`).

Всё это удалено в Symfony 3+. Бандл работоспособен только на Symfony 2.x. Это не баг сам по себе, но блокирует апгрейд. **Рекомендация:** см. [RECOMMENDATIONS.md](RECOMMENDATIONS.md), раздел модернизации.

---

## Не воспроизведено / требует запуска

Без `vendor/` и совместимого PHP не проверены динамически:
- фактическое поведение `getErrors()[0]` на конкретной версии Symfony;
- корректность DQL `DATETIME()` на используемой версии SQLite/Doctrine;
- гонки в `Notice::seeLater()` (два UPDATE без транзакции).

Эти пункты стоит перепроверить после восстановления окружения.
