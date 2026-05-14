# Agency CRM MVP

CRM MVP для digital-агентства на Laravel 12.

## Стек

- PHP 8.4
- Laravel 12
- PostgreSQL
- Redis
- Inertia.js
- Vue 3
- Tailwind CSS
- интерфейс в стиле Vuexy

Купленный исходный пакет Vuexy хранится вне этого репозитория. Не коммитить архив шаблона, файлы лицензии, `.env`, дампы базы, API-ключи и production-доступы.

## Локальный запуск

Установить зависимости:

```bash
composer install
npm install
```

Подготовить окружение:

```bash
cp .env.example .env
php artisan key:generate
```

Запустить PostgreSQL и Redis:

```bash
docker compose up -d pgsql redis
```

Применить миграции и создать тестовых пользователей:

```bash
php artisan migrate:fresh --seed
```

Запустить приложение:

```bash
php artisan serve
npm run dev
```

Открыть:

```text
http://127.0.0.1:8000
```

## Локальные пользователи

Пароль у всех тестовых пользователей:

```text
password
```

Аккаунты:

```text
owner@example.com
finance@example.com
viewer@example.com
```

## Точка Банк

Локально интеграция работает в безопасном sandbox-stub режиме: CRM создаёт тестовые внешние ID и сохраняет ответы без обращения к реальному API и без реальных ключей.

Текущие переменные:

```env
TOCHKA_SANDBOX=true
TOCHKA_USE_STUB=true
TOCHKA_BASE_URL=https://enter.tochka.com/sandbox/v2
TOCHKA_TOKEN=sandbox.jwt.token
TOCHKA_CLIENT_ID=
TOCHKA_CUSTOMER_CODE=
TOCHKA_WEBHOOK_PUBLIC_URL=
TOCHKA_WEBHOOK_PUBLIC_KEY_URL=https://enter.tochka.com/.well-known/jwks.json
TOCHKA_TIMEOUT=15
```

Когда появится публичный HTTPS-домен, нужно будет:

- указать `TOCHKA_WEBHOOK_PUBLIC_URL`, например `https://crm.example.ru/webhooks/tochka`;
- получить в интернет-банке JWT-токен, `client_id` и `customerCode`;
- заменить `TOCHKA_TOKEN`, `TOCHKA_CLIENT_ID`, `TOCHKA_CUSTOMER_CODE`;
- переключить `TOCHKA_USE_STUB=false` после проверки payload по актуальной документации Точки.

Локальный webhook endpoint уже есть:

```text
POST /webhooks/tochka
```

## Проверка

Backend-тесты:

```bash
php artisan test
```

Сборка frontend:

```bash
npm run build
```

## Реализовано

- каркас Laravel 12;
- PostgreSQL и Redis;
- Inertia.js + Vue 3;
- авторизация;
- роли `owner`, `finance_manager`, `viewer`;
- русскоязычная CRM-навигация;
- дашборд;
- справочники клиентов, услуг, проектов и получателей выплат;
- регулярные операции;
- начисления;
- финансовые операции;
- счета и акты;
- выплаты, зарплаты и ПФ;
- отчёты;
- настройки;
- журнал аудита;
- sandbox-заготовка интеграции с Точка Банком;
- ИИ-предзаполнение карточки клиента в sandbox-stub режиме;
- DaData-заготовка;
- email-отправка через локальный mailer/log.
