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

## Проверка

Backend-тесты:

```bash
php artisan test
```

Сборка frontend:

```bash
npm run build
```

## Объём этапов

Реализовано:

- каркас Laravel 12;
- локальные сервисы PostgreSQL и Redis;
- Inertia.js + Vue 3;
- авторизация;
- роли `owner`, `finance_manager`, `viewer`;
- CRM-навигация;
- дашборд-заглушка;
- CRUD клиентов;
- CRUD услуг;
- CRUD проектов;
- поиск, фильтры и пагинация в справочниках;
- архивирование вместо удаления;
- авторасчёт остатка по проекту;
- регулярные операции;
- начисления со snapshot-полями;
- единый реестр финансовых операций;
- защита от дублей финансовых операций по источнику.

Пока не реализовано:

- финансовое ядро;
- cron-генерация начислений;
- ручная оплата начислений;
- счета и акты;
- интеграция с Точка Банком;
- интеграция с DaData;
- отправка email;
- отчёты;
- постоянный журнал аудита.
