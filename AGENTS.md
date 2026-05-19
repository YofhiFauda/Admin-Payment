# Repository Guidelines

## Project Structure & Module Organization

This is a Laravel 12 admin payment application with Vite-managed frontend assets. Core PHP code lives in `app/` for controllers, models, services, events, jobs, middleware, notifications, and console commands. Routes are in `routes/`; Blade views and frontend code are in `resources/views`, `resources/js`, and `resources/css`. Database migrations, factories, and seeders are in `database/`. Tests are split into `tests/Unit` and `tests/Feature`. Deployment and operations files live in `docker/`, `scripts/`, `docs/`, `Dockerfile`, and `docker-compose.yaml`.

## Build, Test, and Development Commands

- `composer install` and `npm install`: install PHP and Node dependencies.
- `composer run setup`: bootstrap dependencies, `.env`, app key, migrations, and assets.
- `composer run dev`: run Laravel, queue listener, logs, and Vite together.
- `php artisan serve`: run only the Laravel HTTP server.
- `npm run dev`: run only the Vite development server.
- `npm run build`: build production frontend assets.
- `composer run test` or `php artisan test`: run PHPUnit test suites.
- `npm test`: run Vitest frontend tests.
- `./vendor/bin/pint`: format PHP code with Laravel Pint.

## Coding Style & Naming Conventions

Follow `.editorconfig`: UTF-8, LF line endings, 4-space indentation, final newline, and trimmed trailing whitespace; YAML uses 2 spaces. Use Laravel conventions: StudlyCase classes, singular models such as `Transaction`, controllers ending in `Controller`, and descriptive migration names. Keep Blade partials under feature folders such as `resources/views/transactions/partials`. Put reusable business logic in `app/Services`.

## Testing Guidelines

Use PHPUnit for PHP tests. Put isolated domain tests in `tests/Unit` and request, auth, database, or workflow tests in `tests/Feature`. Name tests after behavior, for example `TransactionApprovalTest.php`. The test environment uses MySQL database `admin_payment_testing`, array cache/session drivers, sync queues, and disabled Pulse/Nightwatch. Run `php artisan test` before backend submissions; run `npm test` for JavaScript changes.

## Commit & Pull Request Guidelines

Recent history uses Conventional Commit-style subjects such as `feat: add hardened Nginx configuration...`. Keep commits scoped and imperative with prefixes like `feat:`, `fix:`, `docs:`, `test:`, or `refactor:`. Pull requests should include a problem summary, approach, test results, linked issues when available, and screenshots for UI changes. Call out migration, queue, Reverb, Horizon, Docker, or environment-variable impact.

## Security & Configuration Tips

Do not commit real secrets from `.env`; update `.env.example` or `.env.production.template` for new required variables. Review `config/`, `docker/nginx`, and `docs/security/SECURITY_CHECKLIST.md` when changing auth, API docs, log viewer access, webhooks, or proxy behavior.
