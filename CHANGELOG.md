# Changelog

All notable changes to this module will be documented in this file.

## [0.9.13] - 2026-08-12

### Fixed

- Replaced unresolved administrator sidebar translation keys with multilingual menu entity names.
- Clarified the parent menu as "Economy Wallet Management" and normalized the wallet and transaction child labels.
- Added a regression contract for menu metadata and Korean/English frontend translation resolution.

## [0.9.12] - 2026-06-15

### Added

- Added rollback-only administrator adjustment rejection checks for zero credit, zero debit, and debit above available balance.

## [0.9.11] - 2026-06-15

### Added

- Added a non-persistent CLI regression check for the bundled and active administrator credit/debit layout actions.

## [0.9.10] - 2026-06-14

### Fixed

- Aligned administrator adjustment request bodies with the established full-form `apiCall` binding pattern.
- Corrected adjustment error toasts to use the layout engine's supported `error` callback context.

## [0.9.9] - 2026-06-14

### Added

- Added permission-gated administrator manual credit and debit controls to the wallet list.
- Added user-ID based administrator credit and debit endpoints that reuse the existing wallet mutation services.

## [0.9.8] - 2026-06-14

### Changed

- User wallet GET now safely initializes a zero-balance wallet for the current authenticated user without creating a ledger transaction.
- Made wallet get-or-create resilient to concurrent requests and removed the obsolete user-wallet 404 error-page mapping.

## [0.9.7] - 2026-06-14

### Fixed

- Added frontend Korean and English module translation JSON so wallet and transaction layouts resolve their `$t:glitter-economy.*` keys.

## [0.9.3] - 2026-06-14

### Added

- Added the `glitter-economy:test` post-install verification command.
- Added rollback-by-default verification for credit, transfer, hold release, hold void, wallet caches, and ledger rows.

### Fixed

- Derived distinct transfer-out and transfer-in idempotency keys so a transfer can create both ledger rows without violating the unique key constraint.

## [0.9.2] - 2026-06-14

### Changed

- Synchronized Composer and module manifest versions after the pre-install integration audit.
- Documented provider, API route, backend translation, frontend route manifest, Composer autoload, and settings-default discovery conventions.
- Removed the obsolete root-level translation directory and refreshed the bundled README to reflect the current pre-install implementation boundary.

## [0.9.1] - 2026-06-14

### Changed

- Normalized permission documentation on the G7-required `{module}.{category}.{action}` scheme, including `adjust.access`, `audit.access`, and `admin.access`.
- Explicitly declared user/admin category types in `module.php` while preserving the existing route permission identifiers and role assignments.

## [0.9.0] - 2026-06-14

### Added

- Initial validated HTTP API boundary with wallet mutation FormRequests, wallet/transaction/hold Resources, and separate admin/user controllers.
- User wallet, transaction history, and settings-gated transfer routes using user-type wallet permissions.
- Admin wallet lookup, credit, debit, hold create, hold release, and hold void routes using existing admin-type permissions.

### Deferred

- Module install, update, activation, actual migrations, and layout JSON remain deferred.

## [0.8.1] - 2026-06-14

### Hardened

- Removed float fallbacks from service and wallet cache decimal arithmetic; mutation math now requires bcmath and fails through translated domain exceptions when unavailable.
- Replaced generic same-wallet transfer failure with a translated invalid transfer domain error.
- Updated design documents to reflect implemented service mutations and the remaining held debit/capture deferral.

## [0.8.0] - 2026-06-14

### Added

- Service-level wallet credit, debit, and transfer mutation logic using transactions, wallet row locks, ledger rows, wallet cache updates, and economy hooks.
- Service-level hold create, release, and void logic using transactions, wallet/hold locks, ledger rows, wallet cache updates, hold status updates, and economy hooks.
- `hold_void` transaction type for explicit hold void ledger entries.
- Economy service delegation to wallet and hold services so mutation behavior has one implementation path.

### Deferred

- Held debit/capture remains deferred until a dedicated DTO and API contract are introduced.

## [0.7.0] - 2026-06-14

### Added

- Repository primitives for future wallet locking, balance cache updates, ledger entry creation, transaction UUID lookup, hold locking, and hold status updates.
- Repository boundary notes for table-aware reads and mutation-time `EconomyTableUnavailableException` failures.

## [0.6.0] - 2026-06-14

### Changed

- Standardized wallet cache naming on `locked_balance` across migrations, models, repositories, and design documents.
- Standardized transaction balance snapshot naming on `locked_balance_after`.

## [0.5.0] - 2026-06-14

### Added

- Official concurrency, locking, and wallet mutation policy documentation.
- Pseudo-code for credit, debit, transfer, hold creation, hold release, and held debit flows.
- Scenario test design for future mutation coverage without requiring a working database.

## [0.4.0] - 2026-06-14

### Added

- Immutable domain input DTOs for credit, debit, transfer, hold creation, and hold release/void requests.
- Domain exception skeletons with module translation keys.
- Economy settings service skeleton using the module settings interface without constructor-time DB access.
- Service method signatures updated to accept validated DTOs for deferred mutation workflows.

## [0.3.0] - 2026-06-14

### Added

- Repository contracts and table-aware repository skeletons for wallets, transactions, and holds.
- Service skeletons for economy, wallet, and hold boundaries without irreversible ledger logic.
- Economy service provider for repository interface bindings without DB access.
- Repository and service boundary notes in the initial design document.

## [0.2.0] - 2026-06-14

### Added

- Initial migration skeletons for `economy_wallets`, `economy_transactions`, and `economy_holds`.
- Explicit migration constraint naming and defensive rollback hardening.
- Initial model skeletons for `EconomyWallet`, `EconomyTransaction`, and `EconomyHold`.
- Initial enum skeletons for `EconomyTransactionType` and `EconomyHoldStatus`.
- Schema decision notes in the initial design document.

## [0.1.0] - 2026-06-14

### Added

- Initial bundled module skeleton for `glitter-economy`.
- Module metadata, Composer autoload metadata, roles, permission baseline, and safe settings defaults.
- Initial design document defining the ledger-first model, domain objects, permissions, hooks, and settings categories.
