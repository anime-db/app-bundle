---
tags: [memory/repo, architecture]
---

# Архитектура — anime-db/app-bundle

Бандл Symfony 2/3 (`AnimeDbAppBundle`), реализующий ядро приложения менеджера домашней коллекции аниме AnimeDB.

## Структура бандла

```
src/
  AnimeDbAppBundle.php          — точка входа бандла (пустая, без compiler pass)
  DependencyInjection/          — загружает parameters.yml + services.yml
  DoctrineMigrations/           — миграции Doctrine (версии по timestamp)
  Entity/                       — ORM-сущности
  Repository/                   — репозитории Doctrine
  Event/Listener/               — слушатели событий Symfony
  Event/Widget/                 — классы событий виджетов
  Command/                      — консольные команды
  Controller/                   — контроллеры Symfony
  Service/                      — сервисы приложения
  Util/                         — утилиты без состояния
  Form/Type/                    — кастомные типы форм
  DQL/                          — DQL-функции Doctrine
  Resources/config/             — services.yml, parameters.yml, routing.yml, config.yml
  Resources/translations/       — русские XLIFF-файлы (date, datechoice, messages, pagination)
  Resources/views/              — Twig-шаблоны
```

## Основные сущности

| Сущность | Таблица  | Назначение                                                                  |
|----------|----------|-----------------------------------------------------------------------------|
| `Task`   | `task`   | Запланированные задачи; `modify` — модификатор даты/времени PHP (`+1 hour`) |
| `Notice` | `notice` | Уведомления пользователю с жизненным циклом (создано → показано → закрыто)  |
| `Plugin` | `plugin` | Установленные Composer-пакеты типа `anime-db-plugin`                        |

`Task.modify` использует строки, совместимые с `strtotime` PHP. Если пусто — задача выполняется один раз и автоматически отключается после выполнения. Если задано — `Task::executed()` сдвигает `next_run` на модификатор в цикле, пока время не окажется в будущем.

## Ключевые сервисы

**`CommandExecutor`** (`anime_db.command`) — запускает shell/консольные команды двумя способами:
- `execute($cmd, timeout > 0)` — на переднем плане, бросает исключение при ненулевом коде выхода
- `execute($cmd, timeout <= 0)` — в фоне через `exec ... &` (Linux) или `popen start /b` (Windows)
- `send($cmd)` — отправляет сырой HTTP POST через сокет на собственный маршрут приложения `/command/exec` (неблокирующий, ответ не читается)
- `prepare()` переписывает `php app/console` на найденный путь к бинарнику PHP и экранированный путь к console; заменяет `/dev/null` → `nul` в Windows

**`CacheClearer`** (`anime_db.cache_clearer`) — удаляет каталог `<kernel.root_dir>/cache/`; молча подавляет `IOException`.

**`Downloader`** (`anime_db.downloader`) — скачивает удалённые файлы (изображения) в web root; обрабатывает favicon через опциональный прокси.

**`PhpFinder`** (`anime_db.php_finder`) — обёртка над `PhpExecutableFinder` Symfony; предоставляет путь к бинарнику PHP для `CommandExecutor`.

**`WidgetsContainer`** (`anime_db.widgets`) — рассылает события виджетов через event dispatcher; используется `TwigExtension` для рендера встроенных виджетов.

**`TwigExtension`** — регистрирует Twig-функции для маршрутизации, рендера фрагментов и вывода виджетов.

## Слушатели событий

| Слушатель | Прослушиваемые события                                   | Ответственность                                                                  |
|-----------|----------------------------------------------------------|----------------------------------------------------------------------------------|
| `Request` | `kernel.request`, `kernel.response`                      | Устанавливает локаль Gedmo translatable; добавляет заголовки кэша                 |
| `Console` | `console.command`                                        | Устанавливает локаль Gedmo translatable для CLI-команд                           |
| `Package` | `anime_db.package.installed/updated/removed`             | Регистрирует/удаляет сущности `Plugin`; настраивает драйвер кэша shmop            |
| `Project` | `anime_db.project.updated`, `anime_db.project.installed` | Ставит в очередь задачу `ProposeUpdate`; добавляет расширение shmop в composer.json, если его нет |
| `Entity`  | Doctrine `postRemove`, `postUpdate`                      | Удаляет осиротевшие файлы изображений с диска при удалении/обновлении сущности    |

## Утилита пагинации

`Util/Pagination/` — самостоятельный пагинатор:
- `Builder` (сервис `anime_db.pagination`) — фабрика объектов `Configuration`
- `Configuration` — хранит общее число страниц, текущую страницу, размер окна навигации
- `View` — вычисляет диапазон видимых страниц и порождает объекты `Node`
- `Node` — одна ссылка на страницу (номер + флаг активности)

## Поток регистрации плагина

1. Composer порождает событие `anime_db.package.installed` с `ComposerPackage`
2. Слушатель `Package` проверяет `getType() == 'anime-db-plugin'`
3. Вызывает API-клиент для получения метаданных плагина (title, description, logo)
4. Сохраняет/обновляет сущность `Plugin`; скачивает логотип через `Downloader`
5. При удалении: находит `Plugin` по имени и удаляет его из БД

## Настройка драйвера кэша

Слушатель `Package` переключает параметр `cache_time_keeper.driver`:
- Установка `anime-db/shmop` → драйвер меняется на `cache_time_keeper.driver.multi` (fast = shmop)
- Удаление `anime-db/shmop` → драйвер возвращается к `cache_time_keeper.driver.file`
