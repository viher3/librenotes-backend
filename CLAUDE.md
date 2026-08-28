# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Detailed style guides live in `etc/docs/` (indexed by `etc/docs/Readme.md`): `Code.md`, `FolderStructure.md`, `Controllers.md`, `ExceptionHandling.md`, `Api.md`. This file summarizes them for day-to-day work — consult the source docs for full detail.

## Project Overview

Symfony 6.4 DDD application (PHP 8.3+) with CQRS, hexagonal architecture, and JWT authentication. API-first design with Swagger docs at `/api/docs`.

## Commands

All commands run inside Docker via the Makefile (`DOCKER_CLI = docker compose exec symfony-ddd-core-api-php`):

```bash
make docker/up                     # Start containers
make docker/down                   # Stop containers
make composer/install              # Install dependencies
make tests/unit/run                # Run unit tests
make tests/acceptance/run          # Run acceptance tests
make linters/run                   # Run static analysis (PHPStan, level 6)
make phpstan/baseline               # Regenerate phpstan-baseline.neon (see etc/docs/Code.md)
make doctrine/migrations/diff      # Generate a migration from the current mapping changes
make doctrine/migrations/migrate   # Apply pending migrations
make doctrine/migrations/status    # Show applied/pending migrations
make tests/db/reset                # Recreate the test database from migrations
make jwt/generate-keys             # Generate JWT RS256 keys
make cache/clear                   # Clear app cache
make container/bash                # Shell into PHP container
```

Schema changes always go through a migration: edit the `*.orm.xml` mapping, run `make doctrine/migrations/diff`, review the generated SQL, commit it, then `make doctrine/migrations/migrate`. `make doctrine/update/force-danger` (schema push with no history) exists only as a local debugging escape hatch — never use it for a real change.

Run a single test file:
```bash
docker compose exec symfony-ddd-core-api-php ./vendor/bin/phpunit /app/tests/Unit/User/SomeTest.php
```

## Architecture

### Layered DDD with Hexagonal Structure

Each bounded context (module) lives in `src/{Module}/` (see `etc/docs/FolderStructure.md`) and follows this layout:
```
src/{Module}/
├── Application/{UseCase}/        # One folder per use case: {UseCase}Command|Query.php + {UseCase}Handler.php (+ Response.php for queries)
├── Domain/
│   ├── Event/                    # Domain events (e.g. UserCreated.php)
│   ├── Model/                    # Entities, DTOs, ValueObjects, Collections
│   ├── Exception/                 # Domain exceptions (see Exception Handling below)
│   ├── Repository/               # Repository interfaces
│   ├── Service/                   # Domain services
│   └── Specification/             # Business rule specifications
└── Infrastructure/
    ├── DependencyInjection/Config/    # application.yaml, doctrine.yaml, routing.yaml, module.yaml (imports the others)
    ├── Persistence/Doctrine/
    │   ├── Definition/            # *.orm.xml mapping files
    │   ├── Repository/            # Doctrine{X}Repository implementations, extend DoctrineRepository
    │   ├── Specification/         # Doctrine implementations of Domain specifications
    │   └── Type/                  # Doctrine DBAL types converting ValueObjects to/from columns
    └── Ui/Controllers/             # HTTP controllers (API endpoints)
```

Example: `Application/Create/CreateUserCommand.php` + `CreateUserHandler.php`; `Application/Detail/UserDetailQuery.php` + `UserDetailHandler.php` + `UserDetailResponse.php`.

Each module's `module.yaml` is imported into `config/modules.yaml`; each module's `routing.yaml` is imported into `config/routes.yaml`.

**Current modules:** `User`, `Security`, `Shared`

**Core module** (`src/Core/`) provides base abstractions used by all modules: `AggregateRoot`, ValueObjects (`Uuid`, `StringValueObject`, `EmailValueObject`, `IntValueObject`, `DateValueObject`), bus interfaces, `DoctrineRepository`, exception base classes (see Exception Handling below), and `BaseApiController`.

### CQRS Bus System

Four Symfony Messenger buses configured in `config/packages/messenger.yaml`:

| Bus | Purpose | Handler Interface |
|-----|---------|-------------------|
| `transactional_command.bus` | State changes with DB transaction | `TransactionalCommandHandler` |
| `default_command.bus` | State changes without transaction | `CommandHandler` |
| `query.bus` | Read operations | `QueryHandler` |
| `event.bus` | Domain events | `EventHandler` |

Handlers are auto-tagged via `_instanceof` in `config/services.yaml`. Commands/Queries are `readonly` classes. Handlers implement `__invoke()`.

### Domain Events

Aggregate roots extend `AggregateRoot` which records domain events. Events are pulled and dispatched via `EventBus` after command execution.

### Persistence

Doctrine ORM with XML mapping files in each module's `Infrastructure/Persistence/Doctrine/Definition/` (`*.orm.xml`). Repository interfaces live in `Domain/Repository/`; implementations live in `Infrastructure/Persistence/Doctrine/Repository/` and extend `Core/Infrastructure/Persistence/Doctrine/DoctrineRepository`.

Entity properties are typed as domain ValueObjects, not primitives, so persisted invariants (UUID format, email format, ...) hold both in memory and on hydration from the database. Each VO that needs persisting has a matching Doctrine DBAL type in `Infrastructure/Persistence/Doctrine/Type/`, registered under `doctrine.dbal.types` in the module's `doctrine.yaml`, referenced by name from the `*.orm.xml` mapping (`<field type="user_email" .../>`). Concrete types extend one of the shared abstract bases in `App\Core\Infrastructure\Persistence\Doctrine\Type\` (`AbstractStringValueObjectType`, `AbstractTextValueObjectType`, `AbstractIntValueObjectType`, `AbstractBoolValueObjectType`, `AbstractDateTimeValueObjectType`, `AbstractJsonValueObjectType`) and only declare `getName()` and `valueObjectClass()`. Booleans and dates reuse the generic Core types `bool_vo` (`BoolValueObject`) and `datetime_vo` (`DateTimeValueObject`) registered in `config/packages/doctrine.yaml`. Doctrine types are not services — they stay excluded from the `App\` autowiring resource in `config/services.yaml`. Repository query methods (`findBy`, `find`, ...) keep accepting plain scalars; the type's `convertToDatabaseValue()` accepts both the VO and its raw primitive so existing lookups keep working unchanged.

### Routing

Each module defines its own routes in `src/{Module}/Infrastructure/DependencyInjection/Config/routing.yaml` (plain path/methods/controller entries, not attributes), imported into `config/routes.yaml`.

### Ui Controllers

Controllers live in `src/{Module}/Infrastructure/Ui/Controllers/` and extend `BaseApiController` (`src/Core/Infrastructure/Framework/Symfony/Controller/`), which wires the three buses and dispatches via `$this->ask()` (query), `$this->dispatch()` (default command), or `$this->dispatchTransactional()` (transactional command). Controllers validate request input with `validateInputData()` against an inline JSON Schema string, and declare a required `exceptions(): array` method mapping domain exception FQCNs to HTTP status codes (registered with `ApiExceptionsHttpStatusCodeHandler`, see Exception Handling below). See `etc/docs/Controllers.md`.

### Exception Handling

All exceptions extend one of two Core base classes in `src/Core/Domain/Exception/`:
- **`DomainError`** — recoverable business/domain errors (validation, business rules). Implements `errorCode(): string` (unique identifier consumed by the frontend) and `params(): array` (contextual data for traceability/UI hints — never sensitive internals).
- **`CriticalException`** — infrastructure/system-level failures that leave the service dysfunctional (DB connection loss, messaging outage, unavailable external services).

Other Core exceptions (`NotFoundException`, `InvalidArgumentException`, `InvalidValueException`, `InvalidPartialUpdateFieldsException`) extend these as needed. Each controller's `exceptions(): array` maps exception FQCNs to HTTP status codes, registered through `ApiExceptionsHttpStatusCodeHandler`. See `etc/docs/ExceptionHandling.md`.

### API Documentation & Responses

OpenAPI specs live under `etc/openapi/{module}/`, split into `paths.yaml` (endpoints) and `schemas.yaml` (reusable components), following the OpenAPI Specification; served at `/api/docs`. Responses are JSON with **snake_case** properties — see `etc/docs/Api.md` for the standard response shape (list/search endpoints use a `meta`/`data` envelope) to follow for new or updated endpoints.

### Authentication

JWT (RS256) via Firebase JWT library. Keys stored in `config/jwt/`. Security module handles login (`POST /login`) and token renewal (`PUT /token-renew`). Symfony security config in `config/packages/security.yaml`.

## Conventions

- `declare(strict_types=1)` in every PHP file
- Naming (`etc/docs/Code.md`): classes `PascalCase`, methods/variables `camelCase`, constants `UPPER_CASE`
- One class per file; filename matches the class name, in `PascalCase`
- Constructor property promotion and readonly properties/classes
- Value objects for all domain primitives (IDs extend `Uuid`, emails use `EmailValueObject`, etc.)
- Module-level service configuration imported via `config/modules.yaml` and `config/services/`
- Doctrine mappings are XML, not annotations/attributes
- Domain layer has zero framework dependencies
