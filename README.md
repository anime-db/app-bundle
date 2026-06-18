![Anime DB](http://anime-db.org/bundles/animedboffsite/images/logo.jpg)

[![Latest Stable Version](https://img.shields.io/packagist/v/anime-db/app-bundle.svg?maxAge=3600&label=stable)](https://packagist.org/packages/anime-db/app-bundle)
[![Latest Unstable Version](https://img.shields.io/packagist/vpre/anime-db/app-bundle.svg?maxAge=3600&label=unstable)](https://packagist.org/packages/anime-db/app-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/anime-db/app-bundle.svg?maxAge=3600)](https://packagist.org/packages/anime-db/app-bundle)
[![Build Status](https://img.shields.io/travis/anime-db/app-bundle.svg?maxAge=3600)](https://travis-ci.org/anime-db/app-bundle)
[![Coverage Status](https://img.shields.io/coveralls/anime-db/app-bundle.svg?maxAge=3600)](https://coveralls.io/github/anime-db/app-bundle?branch=master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/anime-db/app-bundle.svg?maxAge=3600)](https://scrutinizer-ci.com/g/anime-db/app-bundle/?branch=master)
[![SensioLabs Insight](https://img.shields.io/sensiolabs/i/b6199717-fce5-45f7-8e91-6fb083e0c38e.svg?maxAge=3600&label=SLInsight)](https://insight.sensiolabs.com/projects/b6199717-fce5-45f7-8e91-6fb083e0c38e)
[![StyleCI](https://styleci.io/repos/15072132/shield?branch=master)](https://styleci.io/repos/15072132)
[![License](https://img.shields.io/packagist/l/anime-db/app-bundle.svg?maxAge=3600)](https://github.com/anime-db/app-bundle)

# Anime DB #

This is the application for making your home collection anime<br />
The application is for home use only<br />

---

## О проекте (RU)

`anime-db/app-bundle` — **ядро (core bundle)** настольного приложения **AnimeDB** для ведения домашней коллекции аниме. Приложение запускается локально на компьютере пользователя (Windows или Linux/*nix), а работа ведётся через браузер.

Это бандл-библиотека Symfony 2, а не самостоятельное приложение: он подключается в host-приложение `anime-db/anime-db` вместе с родственными бандлами.

### Что внутри

- **Планировщик задач** — фоновое выполнение команд по расписанию (`animedb:task-scheduler`);
- **Уведомления** — сообщения пользователю с жизненным циклом и кэшируемой выдачей;
- **Плагины** — установка/удаление функциональности через Composer-пакеты типа `anime-db-plugin`;
- **Загрузчик** — скачивание изображений и favicon, загрузка картинок из форм;
- **Утилиты** — кроссплатформенная работа с файловой системой и пагинация;
- **Формы, Twig-расширения, локализация** (основная локаль — русская).

### Технологии

PHP 5.4+ · Symfony 2.x · Doctrine ORM + Migrations · SQLite · Guzzle 3 · Twig · PHPUnit 4.

### Установка зависимостей и тесты

```bash
composer install            # зависимости
./vendor/bin/phpunit        # все тесты
```

> ⚠️ Стек устаревший (PHP 5.4 / Symfony 2 / PHPUnit 4). На современных версиях PHP (8.x) он не устанавливается и не запускается — нужно совместимое окружение (например, Docker с PHP 5.6/7.x).

### Документация

Полная документация и аудит проекта — в каталоге [`docs/`](docs/):

- [docs/AUDIT.md](docs/AUDIT.md) — сводный аудит;
- [docs/PROJECT.md](docs/PROJECT.md) — структура и устройство;
- [docs/TECHNICAL.md](docs/TECHNICAL.md) — глубокая техническая часть;
- [docs/BUGS.md](docs/BUGS.md) — найденные баги и уязвимости;
- [docs/RECOMMENDATIONS.md](docs/RECOMMENDATIONS.md) — рекомендации по улучшению.

> ⚠️ **Безопасность:** в текущем коде есть критичная проблема — endpoint `/command/exec.html` исполняет произвольные команды. См. [docs/BUGS.md](docs/BUGS.md).

Для AI-агентов (Claude Code) точка входа — [CLAUDE.md](CLAUDE.md).

### Лицензия

GPL-3.0.
