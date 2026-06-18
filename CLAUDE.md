# CLAUDE.md

Этот файл даёт указания Claude Code (claude.ai/code) при работе с кодом в этом репозитории.

## Указатель документации
- [.claude-docs/architecture.md](.claude-docs/architecture.md) — структура бандла, ключевые сервисы, поток событий
- [.claude-docs/gotchas.md](.claude-docs/gotchas.md) — неочевидные подводные камни

## Команды

```bash
# Установить зависимости
composer install

# Установить dev-зависимости
composer install --dev

# Запустить все тесты
./vendor/bin/phpunit

# Запустить один тестовый файл
./vendor/bin/phpunit tests/Service/CacheClearerTest.php

# Запустить один тестовый метод
./vendor/bin/phpunit --filter testMethodName tests/Service/CacheClearerTest.php
```

## Границы

### ОБЯЗАТЕЛЬНО
- Все классы — в пространстве имён `AnimeDb\Bundle\AppBundle\` (PSR-4, отображается на `src/`)
- Новые миграции Doctrine кладутся в `src/DoctrineMigrations/` с соглашением об именовании `Version<timestamp>_<описание>.php`
- Сервисы должны регистрироваться в `src/Resources/config/services.yml`

### ЗАПРЕЩЕНО
- Не поднимать минимальную версию PHP выше объявленной в `composer.json` (`>=5.4.0`)
- Не использовать `exec()` / `popen()` напрямую для фоновых команд — всё через `CommandExecutor`
