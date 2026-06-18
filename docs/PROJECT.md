---
title: Структура проекта anime-db/app-bundle
tags: [project/anime-db, app-bundle, docs/project]
updated: 2026-06-18
---

# Структура проекта — anime-db/app-bundle

> См. также: [AUDIT.md](AUDIT.md) · [TECHNICAL.md](TECHNICAL.md) · [BUGS.md](BUGS.md) · [RECOMMENDATIONS.md](RECOMMENDATIONS.md)

## Что это

`anime-db/app-bundle` — ядро (core bundle) приложения **AnimeDB**, настольного менеджера домашней коллекции аниме. Приложение задумано для локального («домашнего») использования: пользователь запускает его на своём ПК (Windows или *nix), а интерфейс открывает в браузере.

Бандл предоставляет инфраструктурный слой всего приложения:

- планировщик фоновых задач (Task Scheduler);
- систему уведомлений пользователю (Notice);
- механизм плагинов на базе Composer-пакетов (Plugin);
- загрузчик файлов/изображений (Downloader) и работу с favicon;
- утилиты файловой системы и пагинацию;
- набор форм, Twig-расширений и слушателей событий.

Это **библиотека-бандл** (`"type": "library"`), а не самостоятельное приложение. Он подключается в полное приложение `anime-db/anime-db` вместе с родственными бандлами (`api-client-bundle`, `cache-time-keeper-bundle` и др.).

## Технологический стек

| Слой        | Технология                                                       |
|-------------|------------------------------------------------------------------|
| Язык        | PHP `>=5.4.0` (по `composer.json`)                               |
| Фреймворк   | Symfony 2.x (форм-API и контроллеры — стиля Symfony 2)           |
| ORM         | Doctrine ORM + Doctrine Migrations                               |
| БД          | SQLite (DQL-функция `DATETIME` — SQLite-специфична)              |
| HTTP-клиент | Guzzle 3 (`Guzzle\Http\Client`)                                  |
| Шаблоны     | Twig                                                             |
| i18n        | Symfony Translation + Gedmo Translatable, основная локаль — `ru` |
| Тесты       | PHPUnit 4.8                                                      |
| Юникод      | `patchwork/utf8`                                                 |

## Карта каталогов

```
src/
  AnimeDbAppBundle.php          точка входа бандла (пустой класс Bundle)
  DependencyInjection/          загрузка parameters.yml + services.yml
  Command/                      консольные команды (3 шт.)
  Controller/                   HTTP-контроллеры (5 шт.)
  DQL/                          DQL-функция DATETIME для Doctrine
  DoctrineMigrations/           миграции схемы БД (6 версий)
  Entity/                       ORM-сущности: Task, Notice, Plugin + Field/Image
  Event/Listener/               слушатели: Request, Console, Entity, Project, Package
  Event/Widget/                 события виджетов: Get, StoreEvents
  Form/Type/                    кастомные типы форм + Help-extension
  Repository/                   репозитории Doctrine: Task, Notice
  Service/                      сервисы приложения (см. ниже)
  Util/                         Filesystem + Pagination (Builder/Configuration/View/Node)
  Resources/config/             services.yml, parameters.yml, routing.yml, config.yml
  Resources/translations/       русские XLIFF (date, datechoice, messages, pagination)
  Resources/views/              Twig-шаблоны
tests/                          зеркало src/ под PHPUnit
migrations.yml                  конфиг Doctrine Migrations
```

## Основные сущности (модель данных)

| Сущность | Таблица  | Назначение                                                                           |
|----------|----------|--------------------------------------------------------------------------------------|
| `Task`   | `task`   | Запланированная задача планировщика. Поле `modify` — модификатор PHP (`+1 hour`)     |
| `Notice` | `notice` | Уведомление пользователю с жизненным циклом: создано → показано → закрыто            |
| `Plugin` | `plugin` | Установленный Composer-пакет типа `anime-db-plugin`. ID — имя пакета (`vendor/name`) |

Вспомогательная не-ORM сущность: `Entity/Field/Image` — DTO для загрузки изображения (remote URL или локальный файл) с валидацией.

## Ключевые сервисы

| Сервис (DI id)                | Класс              | Роль                                                                |
|-------------------------------|--------------------|---------------------------------------------------------------------|
| `anime_db.command`            | `CommandExecutor`  | Запуск shell/console-команд (foreground / background / через сокет) |
| `anime_db.cache_clearer`      | `CacheClearer`     | Удаление каталога кэша Symfony                                      |
| `anime_db.downloader`         | `Downloader`       | Скачивание файлов/изображений, favicon                              |
| `anime_db.php_finder`         | `PhpFinder`        | Поиск пути к бинарнику PHP                                          |
| `anime_db.widgets`            | `WidgetsContainer` | Реестр виджетов по «местам» (places), через events                  |
| `anime_db.pagination`         | `Builder`          | Фабрика конфигураций пагинатора                                     |
| `anime_db.app.twig_extension` | `TwigExtension`    | Twig-функция `widgets()` и фильтр `favicon`                         |
| `anime_db.filesystem`         | `Util\Filesystem`  | Кроссплатформенные операции с ФС (статические методы)               |

## Слушатели событий

| Слушатель | События                                      | Ответственность                                                         |
|-----------|----------------------------------------------|-------------------------------------------------------------------------|
| `Request` | `kernel.request`, `kernel.response`          | Определение и установка локали (Gedmo); публичность ответа + revalidate |
| `Console` | `console.command`                            | Установка локали для CLI                                                |
| `Entity`  | Doctrine `postRemove`, `postUpdate`          | Удаление осиротевших файлов изображений с диска                         |
| `Project` | `anime_db.project.installed/updated`         | Перепланирование задачи `propose-update`; добавление пакета shmop       |
| `Package` | `anime_db.package.installed/updated/removed` | Регистрация/удаление `Plugin`; переключение драйвера кэша на shmop      |

## HTTP-маршруты

| Маршрут                   | Метод | URL                             | Контроллер              |
|---------------------------|-------|---------------------------------|-------------------------|
| `notice_show`             | GET   | `/notice/show.json`             | `Notice:show`           |
| `notice_close`            | POST  | `/notice/{id}/close.json`       | `Notice:close`          |
| `notice_see_later`        | POST  | `/notice/see_later.json`        | `Notice:seeLater`       |
| `form_local_path`         | GET   | `/form/local_path.html`         | `Form:localPath`        |
| `form_local_path_folders` | GET   | `/form/local_path/folders.json` | `Form:localPathFolders` |
| `form_image`              | GET   | `/form/image.html`              | `Form:image`            |
| `form_image_upload`       | POST  | `/form/imageUpload.json`        | `Form:imageUpload`      |
| `media_favicon`           | GET   | `/media/favicon/{host}.ico`     | `Media:favicon`         |
| `command_exec`            | POST  | `/command/exec.html`            | `Command:exec` ⚠️       |

⚠️ `command_exec` исполняет произвольную команду из POST-параметра — см. [BUGS.md](BUGS.md) и [AUDIT.md](AUDIT.md).

## Консольные команды

| Команда                    | Класс                   | Назначение                                                            |
|----------------------------|-------------------------|-----------------------------------------------------------------------|
| `animedb:task-scheduler`   | `TaskSchedulerCommand`  | Бесконечный цикл-демон: выбирает и запускает задачи из таблицы `task` |
| `animedb:clear-media-temp` | `ClearMediaTempCommand` | Чистит `web/media/tmp/` от файлов старше 1 часа                       |
| `animedb:propose-update`   | `ProposeUpdateCommand`  | Раз в 30 дней создаёт уведомление о необходимости обновления          |

## Внешние зависимости (рантайм)

- `patchwork/utf8` — корректная работа с путями в Юникоде на разных ОС;
- `anime-db/api-client-bundle` — API-клиент каталога (`@anime_db.api.client`, `@anime_db.client`);
- `anime-db/cache-time-keeper-bundle` — кэш-заголовки/ETag (`@cache_time_keeper`);
- `anime-db/anime-db` (dev) — host-приложение, поставляет `@anime_db.manipulator.composer`, `@anime_db.manipulator.parameters`.

Бандл **жёстко связан** с этими родственными бандлами через DI-ссылки в `services.yml`; в отрыве от них он не загрузится.
