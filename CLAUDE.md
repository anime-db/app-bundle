# CLAUDE.md

Этот файл даёт указания Claude Code (claude.ai/code) при работе с кодом в этом репозитории.

## TL;DR

`anime-db/app-bundle` — ядро настольного приложения AnimeDB (домашняя коллекция аниме). Бандл-библиотека **Symfony 2.x / PHP 5.4**, работает внутри host-приложения `anime-db/anime-db`. Планировщик задач, уведомления, плагины (Composer-пакеты), загрузчик изображений, пагинация.

## ⚠️ Важное (читать до изменений)

- 🔴 **RCE:** `Controller/CommandController::execAction` (маршрут `command_exec`, `/command/exec.html`) исполняет произвольную команду из POST-параметра `command` без аутентификации. Это известная проблема — см. [docs/BUGS.md](docs/BUGS.md) #1. Не «чинить» точечно — переработать дизайн.
- **Стек заморожен:** Symfony 2.x форм-API (строковые типы, передача экземпляров типов, `ContainerAwareCommand`, Guzzle 3). Несовместим с Symfony 3+. Не апгрейдить «по пути».
- **Окружение:** `vendor/` обычно отсутствует; локальный PHP — 8.3, на котором стек **не запустится**. Для тестов нужен PHP 5.6/7.x (Docker).
- **Метаданные расходятся:** `composer.json` декларирует `php >=5.4` и `branch-alias 0.4.x`, но активная ветка — `2.x`.
- **БД — SQLite:** DQL-функция `DATETIME` (`src/DQL/Datetime.php`) SQLite-специфична.

## Указатель документации

### Agent docs (.claude-docs/)

Тонкий L1b-слой для агентов. Начинать с `index.md`.

| Файл                                                         | Когда читать                                                            |
|--------------------------------------------------------------|-------------------------------------------------------------------------|
| [.claude-docs/index.md](.claude-docs/index.md)               | роутинг; начинать отсюда при сомнениях                                  |
| [.claude-docs/architecture.md](.claude-docs/architecture.md) | устройство бандла: структура, сервисы, события                          |
| [.claude-docs/gotchas.md](.claude-docs/gotchas.md)           | нетривиальные ловушки и «так и задумано»                                |
| [.claude-docs/glossary.md](.claude-docs/glossary.md)         | имя/термин читается неоднозначно: опечатки в коде, исторический нейминг |
| [.claude-docs/context.md](.claude-docs/context.md)           | кто вызывает код, модель угроз, статус легаси-решений                   |

**docs/ (развёрнуто, для людей):**
- [docs/AUDIT.md](docs/AUDIT.md) — сводный аудит
- [docs/PROJECT.md](docs/PROJECT.md) — структура: каталоги, сущности, сервисы, маршруты, команды
- [docs/TECHNICAL.md](docs/TECHNICAL.md) — глубокая техника: планировщик, плагины, downloader, пагинация, локаль
- [docs/BUGS.md](docs/BUGS.md) — баги и уязвимости с серьёзностью и фиксами
- [docs/RECOMMENDATIONS.md](docs/RECOMMENDATIONS.md) — что улучшить (безопасность, корректность, модернизация)

## Команды

```bash
composer install                                  # зависимости (нужен совместимый PHP)
./vendor/bin/phpunit                              # все тесты
./vendor/bin/phpunit tests/Service/DownloaderTest.php   # один файл
./vendor/bin/phpunit --filter testPrepare         # один метод
```

## Карта кода (быстрая навигация)

- `src/Command/` — `TaskSchedulerCommand` (демон-цикл), `ClearMediaTempCommand`, `ProposeUpdateCommand`
- `src/Controller/` — Notice, Form, Media, Command (⚠️), Base
- `src/Entity/` — `Task`, `Notice`, `Plugin`, `Field/Image`
- `src/Service/` — `CommandExecutor`, `Downloader`, `CacheClearer`, `PhpFinder`, `WidgetsContainer`, `TwigExtension`
- `src/Event/Listener/` — `Request`, `Console`, `Entity`, `Project`, `Package`
- `src/Util/` — `Filesystem`, `Pagination/{Builder,Configuration,View,Node}`
- `src/Resources/config/` — `services.yml`, `parameters.yml`, `routing.yml`, `config.yml`

## Границы

### ОБЯЗАТЕЛЬНО
- Пространство имён `AnimeDb\Bundle\AppBundle\` (PSR-4 → `src/`)
- Новые сервисы — в `src/Resources/config/services.yml`
- Новые миграции — в `src/DoctrineMigrations/` (`Version<timestamp>_<описание>.php`)
- Проверять результат `findOneBy`/`find` на null перед использованием

### ЗАПРЕЩЕНО
- Не поднимать минимальную версию PHP выше `composer.json` (`>=5.4.0`) без согласования
- Не использовать `exec()`/`popen()` напрямую — через `CommandExecutor`
- Не доверять пользовательскому вводу в путях файлов (favicon host, имена загрузок)
