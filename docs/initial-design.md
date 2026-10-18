# Glitter Economy Initial Design

## Scope

Glitter Economy is the shared economy infrastructure module for Gnuboard7. It is intended to support community points, ecommerce rewards, auctions, attendance rewards, advertising rewards, commissions, credits, and future wallet-like use cases.

This design baseline now includes initial migrations, models, repositories, DTOs, service-level mutation logic, and an initial HTTP API boundary. Layouts and installed runtime activation remain deferred.

## Ledger Model

Economy is ledger-first.

`economy_transactions` is the source of truth. Every balance-changing operation must be represented as immutable ledger entries with enough metadata to trace actor, subject, reason, source module, correlation id, and reversal relationships.

`economy_wallets` is a balance cache, not the source of truth. Wallet balances exist to make reads efficient and must be rebuildable from `economy_transactions`.

`economy_holds` is required from v1. Auction deposits, escrow, order pending payments, and commission settlement all need locked balance. Holds reserve available balance without rewriting settled ledger history.

## Initial Domain Objects

- Wallet
- Transaction
- Hold
- Transfer
- RewardRule
- ExpirationPolicy

## Initial Permissions

- `glitter-economy.wallet.view`
- `glitter-economy.wallet.transfer`
- `glitter-economy.transactions.view`
- `glitter-economy.transactions.export`
- `glitter-economy.adjust.access`
- `glitter-economy.audit.access`
- `glitter-economy.admin.access`

Gnuboard7 persists static module permissions as `{module}.{category}.{action}` and `PermissionMiddleware` checks the identifier together with its `admin` or `user` type. Flat identifiers such as `glitter-economy.adjust` cannot be created by the current module permission contract, so the `adjust`, `audit`, and `admin` categories consistently use the `access` action.

## Initial Hook Surface

- `glitter-economy.wallet.before_credit`
- `glitter-economy.wallet.after_credit`
- `glitter-economy.wallet.before_debit`
- `glitter-economy.wallet.after_debit`
- `glitter-economy.wallet.before_transfer`
- `glitter-economy.wallet.after_transfer`
- `glitter-economy.hold.before_create`
- `glitter-economy.hold.after_create`
- `glitter-economy.hold.before_release`
- `glitter-economy.hold.after_release`
- `glitter-economy.hold.before_void`
- `glitter-economy.hold.after_void`

## Initial Settings Categories

- wallet
- transfer
- expiration
- reward

## Deferred Implementation

- Admin and user layout JSON are deferred.
- Module install, update, activation, and actual migration execution are deferred.
- Hook listener implementation is deferred.

## Initial Schema Baseline

The first migration baseline follows current Gnuboard7 conventions:

- Primary keys use auto-incrementing unsigned bigint `id` columns.
- Public identifiers use UUID columns.
- Value amounts use `decimal(18, 2)` for wallet and ledger precision headroom.
- User references point to `users.id` and use `nullOnDelete()` so ledger records are not deleted with users.
- Ledger and hold references to wallets use `restrictOnDelete()` so business deletion cannot be delegated to database cascade.
- `economy_wallets` uses soft deletes because it is a balance cache and can be closed without deleting ledger history.
- `economy_transactions` and `economy_holds` do not use soft deletes in the initial baseline.
- Runtime DB inspection on this host found no existing economy tables and no initialized `users` table, so migrations were authored from repository migration conventions rather than an existing production schema.

## Hardened Migration Rationale

The migration baseline intentionally relies on Laravel table prefix handling by using logical table names such as `economy_wallets` and `users`. With `DB_PREFIX=g7_`, `migrate --pretend` renders these as `g7_economy_wallets`, `g7_economy_transactions`, `g7_economy_holds`, and `g7_users`.

Foreign keys to `users` are not made optional in the schema. The module requires the core user table to exist before its migrations are executed. If `users` or the prefixed `g7_users` table is absent, actual migration must remain deferred rather than silently creating ledger tables without referential integrity.

Indexes and foreign keys use explicit names where they are module-owned. UUID columns use explicitly named unique constraints and do not add redundant non-unique UUID indexes.

`down()` methods disable foreign key checks only around the guarded drop and always re-enable them in `finally` blocks.

Model skeletons intentionally do not define `activityLogFields` yet. Wallet balances are cache state derived from the ledger, transactions are immutable source-of-truth records, and holds are lifecycle records that should get purpose-built audit events once services define mutation boundaries.

## Repository and Service Boundary

Repository contracts live under `src/Contracts/Repositories` for this module. Concrete repositories live under `src/Repositories` and are table-aware: read methods return `null` or an empty collection when the module tables are absent, while mutation methods fail fast with a runtime exception until migrations are installed.

Services depend on repository interfaces rather than concrete repositories. Wallet lookup/creation methods delegate to repositories, transaction listing delegates to the transaction repository, and service-level mutations use repository locking primitives plus ledger writes inside database transactions.

`EconomyServiceProvider` exists only to bind repository interfaces to implementations. It performs no database access in register or boot.

## Domain Input Contract Boundary

Wallet mutation inputs are represented as immutable value objects under `src/DTO` before any balance mutation service is implemented. The initial DTO set is `CreditRequestData`, `DebitRequestData`, `TransferRequestData`, `HoldRequestData`, and `ReleaseHoldRequestData`.

These objects represent already-validated domain input only. They do not run request validation, authorization checks, balance checks, or table queries. HTTP validation is the responsibility of FormRequest classes, and service methods receive validated DTOs rather than raw request arrays for mutation workflows.

Mutation-heavy methods in `EconomyService`, `WalletService`, and `HoldService` accept these DTOs. Credit, debit, transfer, hold creation, hold release, and hold void now have initial service implementations; held debit/capture remains deferred until a dedicated DTO is designed.

## Exception Policy

Domain exceptions live under `src/Exceptions` and extend `EconomyException` where appropriate. Exception messages are produced from module translation keys such as `glitter-economy::messages.exceptions.wallet_not_found`; visible exception text is not hardcoded inside service or exception constructors.

The initial exception set covers missing wallets, insufficient available balance, invalid amounts, unavailable economy tables, missing holds, invalid hold state transitions, deferred mutation logic, and deferred settings persistence.

## Settings Service Boundary

`EconomySettingsService` implements the project `ModuleSettingsInterface` pattern. It reads module defaults from `config/settings/defaults.json` directly and intentionally avoids calling `module_setting` or `module_settings` from inside the module settings service to prevent recursive delegation. It performs no database or table access during construction.

Settings writes remain deferred because this step only introduces safe read-side settings access and domain input contracts.

## Concurrency and Mutation Policy

The official mutation policy is documented in `docs/mutation-policy.md`. Balance-changing operations must run inside `DB::transaction(...)`, lock affected wallet rows with `lockForUpdate()` before balance calculation, and keep ledger rows plus wallet cache updates atomic.

Transfers must lock wallet rows in deterministic ascending id order. Holds reserve available balance by increasing the locked-balance cache without reducing settled balance; held debits reduce both settled balance and locked balance while creating a debit ledger entry.

`locked_balance` is the official wallet cache column name. `economy_holds` remains the reservation entity/table, while locked balance is the wallet state used by auctions, escrow, pending order payments, commission settlement, and future locked-value use cases.

## Implemented Mutation Services

Initial service-level mutation logic now exists for credit, debit, transfer, hold create, hold release, and hold void. The implementation uses repository locking primitives, `DB::transaction(...)`, ledger rows, and wallet cache updates in the same transaction.

Held debit/capture is still deferred until a dedicated DTO and API boundary are designed.

## Initial HTTP API Boundary

The bundled module exposes `src/routes/api.php` for automatic module route discovery, but this host does not install, update, or activate the module as part of the API skeleton step.

FormRequests contain input validation only and always return `true` from `authorize()`. Authentication and permission checks remain route middleware responsibilities. Controllers convert validated input plus trusted route/authentication values into immutable DTOs before calling `EconomyService`.

User routes use user-type permissions only:

- Wallet and own transaction reads: `glitter-economy.wallet.view`
- Transfer: `glitter-economy.wallet.transfer`

There is no separate user-type transaction permission in the initial permission surface, so own transaction history intentionally shares `wallet.view`. The admin-only `glitter-economy.transactions.view` permission is not applied to user routes.

Admin routes use admin-type permissions only:

- Wallet lookup: `glitter-economy.admin.access`
- Credit, debit, hold create, hold release, and hold void: `glitter-economy.adjust.access`

`module.php` explicitly marks the `wallet` category as `user` and all other permission categories as `admin`. The `user` role is assigned only to user-type wallet permissions. The `admin` role and the module-owned `glitter-economy.manager` and `glitter-economy.auditor` roles receive the permissions appropriate to their responsibilities.

User transfer requests accept only the target wallet id and amount. The source wallet id is resolved from the authenticated user, preventing callers from selecting another user's source wallet. The endpoint also checks `transfer.enabled` before invoking the mutation service.

## Pre-install Integration Conventions

Gnuboard7 discovers module service providers from installed module paths using the `src/Providers/*ServiceProvider.php` file and namespace convention. `EconomyServiceProvider` therefore requires no provider field in `module.json`; it becomes discoverable only after the deferred module lifecycle copies and activates the bundled source.

API routes use the module-standard `src/routes/api.php` path. They are loaded under the module API prefix only for an installed and active module. Backend translations use `src/lang/{locale}`; a root-level `lang` directory is not part of module translation discovery. Frontend route manifests under `resources/routes/admin.json` and `resources/routes/user.json` remain deferred with layout work.

Composer maps `Modules\Glitter\Economy\` to `src/` and module seeders to `database/seeders/`, and declares `ext-bcmath` because mutation arithmetic requires it. Composer and module manifest versions are kept synchronized. Settings defaults remain at `config/settings/defaults.json`, matching both the module fallback convention and `EconomySettingsService`.
