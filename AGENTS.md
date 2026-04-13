# Инструкции для AI-агентов и автоматизации

Этот документ задаёт ожидаемое поведение для любых агентов (Cursor, CLI и т.д.), которые выполняют команды в терминале по этому репозиторию.

## Контейнеры: кто их запускает

**Разработчик** перед сессией с агентом поднимает Docker Compose (см. [README.md](README.md): профили `dev` или `prod`).

**Агент не поднимает и не пересобирает** стек самостоятельно: не выполнять `docker compose up`, `down`, `restart`, `build`, `pull` и т.п., если пользователь явно не попросил.

## Где выполнять консольные команды проекта

Все команды, которые используют **стек приложения** (PHP, Composer, `bin/console`, npm, node, npx, миграции, тесты, линтеры, сборка), нужно запускать **внутри уже работающих контейнеров** через `docker compose exec` из **корня репозитория** (рядом с `compose.yml`).

Не запускать на хосте в каталогах `backend/` и `frontend/` те же инструменты, что уже есть в образах, если только пользователь не указал иное.

### Сервисы (профиль `dev`)

| Назначение | Имя сервиса | Пример |
|------------|-------------|--------|
| Symfony, PHP, Composer | `backend-dev` | `docker compose exec backend-dev php bin/console cache:clear` |
| Node, npm, Vite | `frontend-dev` | `docker compose exec frontend-dev npm run lint` |
| PostgreSQL | `postgres` | `docker compose exec postgres psql -U app -d app` |

Для профиля **prod** используйте `backend-prod` и `frontend-prod`, если стек уже запущен с `--profile prod`.

Пути в контейнерах: backend — `/var/www/html`, frontend — `/app`.

## Что можно на хосте

Команды вроде `git`, общие утилиты ОС, а также работа с файлами без привязки к PHP/Node окружению проекта — по обычным правилам среды.

Дублирующее правило для Cursor: [.cursor/rules/agent-container-commands.mdc](.cursor/rules/agent-container-commands.mdc).

## Архитектурные правила (API)

1. Использовать паттерн **CQRS**.
2. Валидацию входных данных выполнять через `MapRequestPayload` и атрибуты в специальных DTO-моделях запроса, создаваемых под каждую отдельную ручку.
3. Query-параметры и Body описывать **в отдельных DTO**.
4. Если у ручки нет входных данных, её контроллер размещать в `src/Controller`.
5. Если у ручки есть входные данные (в том числе query-параметры), создавать одну вложенную папку в `src/Controller` с говорящим названием (например, `src/Controller/ConnectTelegramIntegration`) и размещать внутри контроллер и DTO запроса.
6. По паттерну CQRS для каждой пары команда + хендлер создавать отдельную папку с говорящим названием в `src/Command` и складывать внутрь связанные файлы.

Дублирующее правило для Cursor: [.cursor/rules/agent-architecture-api.mdc](.cursor/rules/agent-architecture-api.mdc).

