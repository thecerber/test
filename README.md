# Monorepo: Symfony + React + Docker Compose

Стек: **Symfony 7.3** (PHP 8.4), **React + TypeScript** (Vite), **PostgreSQL 16**, **Docker Compose**.

## Структура

| Путь | Описание |
|------|----------|
| [backend](backend) | Symfony API, Nginx + PHP-FPM в одном образе |
| [frontend](frontend) | React + TS, Vite (dev) / Nginx (prod) |
| [compose.yml](compose.yml) | Сервисы и профили `dev` / `prod` |

## Переменные окружения

Скопируйте [.env.example](.env.example) в `.env` в корне репозитория при необходимости (порты, пароли БД, `APP_SECRET`).

Для локальной разработки **без** Docker настройте [backend/.env](backend/.env) (`DATABASE_URL`, `APP_SECRET`).

## Режим разработки (Docker)

Поднимает `postgres`, `backend-dev` (код с хоста + volume `vendor`), `frontend-dev` (HMR).

```bash
docker compose --profile dev up --build
```

- Frontend: <http://localhost:5173>
- Backend API: <http://localhost:8080/api/health>
- PostgreSQL на хосте: `localhost:5432` (логин/пароль по умолчанию `app` / `app`)

Запросы с фронта идут на `/api/...`; Vite проксирует их на `backend-dev:8080`.

### Сидирование данных (фикстуры)

Из **корня репозитория** (стек с профилем `dev` уже запущен) сначала примените миграции, затем загрузите демо-данные:

```bash
docker compose --profile dev exec backend-dev php bin/console doctrine:migrations:migrate --no-interaction
docker compose --profile dev exec backend-dev php bin/console doctrine:fixtures:load --no-interaction
```

Фикстура [DemoShopOrdersFixture](backend/src/DataFixtures/DemoShopOrdersFixture.php): один магазин, 10 заказов, по одной записи в `telegram_send_log` на заказ (7 со статусом `SENT`, 3 — `FAILED`). Команда `doctrine:fixtures:load` **полностью очищает БД** и заново создаёт строки из фикстур. Фикстуры подключены только в окружениях `dev` и `test` ([DoctrineFixturesBundle](https://symfony.com/bundles/DoctrineFixturesBundle/current/index.html)).

После этого можем открыть интерфейс по ссылке: http://localhost:5173/shops/1/growth/telegram

### Тесты backend (Docker)

Из **корня репозитория** при запущенном стеке `dev`:

```bash
docker compose exec backend-dev composer test
```

Команда `composer test` в `backend` автоматически:
- создаёт `app_test`, если БД ещё не существует (`doctrine:database:create --env=test --if-not-exists`);
- запускает `phpunit`.

Для запуска одного файла тестов:

```bash
docker compose exec backend-dev php bin/phpunit tests/Functional/ConnectTelegramIntegrationControllerTest.php
```

Для запуска одного тест-метода:

```bash
docker compose exec backend-dev php bin/phpunit tests/Functional/ConnectTelegramIntegrationControllerTest.php --filter testReturns404ForUnknownShop
```

## Продакшен-профиль (Docker)

Поднимает `postgres`, `backend-prod` (код из образа, без bind-mount), `frontend-prod` (сборка + Nginx, прокси `/api/` → `backend-prod`).

```bash
docker compose --profile prod up --build
```

- Сайт: <http://localhost:3000>
- API с браузера: тот же origin, путь `/api/health`

Профили `dev` и `prod` **не смешивайте** на одних и тех же портах (оба backend по умолчанию слушают `8080` на хосте).

После изменений кода в `prod` пересоберите образы: `docker compose --profile prod build --no-cache` (или `up --build`).

## Локально без Docker

**Backend:**

```bash
cd backend
composer install
# Убедитесь, что PostgreSQL запущен и DATABASE_URL в .env верный
symfony server:start   # или php -S 127.0.0.1:8080 -t public
```

**Frontend:**

```bash
cd frontend
npm install
# Прокси /api на Symfony (по умолчанию 127.0.0.1:8080)
VITE_API_TARGET=http://127.0.0.1:8080 npm run dev
```

## Проверка

1. Откройте `/api/health` у backend — ожидается JSON `{"status":"ok","database":"ok"}`.
2. Откройте главную страницу frontend — должен отобразиться ответ health.

## Упрощения

- Нет никакой авторизации
- Нет возможности создать новый магазин
- Можно было бы реализовать логику таймстампов на уровне БД (`created_at`, `updated_at`)
- Постгрес поддерживает upsert (`INSERT ... ON CONFLICT DO UPDATE`, аналог `REPLACE INTO` как в мускуле)
- В тестах много копипасты, по хорошему нужны билдеры для сущностей и вспомогательный общий код
- Маскировать botToken и chatId лучше на фронте, всё равно туда нужно передать реальные значения для возможности редактировать через форму интеграции на странице статуса (по условиям ручки /connect эти поля обязательны, но можно и по-другому переиграть)
- Улучшить на фронте вывод ошибок валидации - с бэка возвращать сразу все ошибки и на фронте показывать из под соответствующими полями форм.

!!! Не удалось протестировать реальную отправку через телеграм, даже под VPN из нескольких стран у меня локально не взлетело (Failed to open stream: Connection timed out)

