# Glitter Economy Mutation Policy

This document defines the official concurrency, locking, and mutation policy for wallet mutation services. Initial credit, debit, transfer, hold create, hold release, and hold void service logic is implemented; held debit/capture remains deferred.

## Existing Project Patterns Reviewed

- Gnuboard7 services commonly wrap related writes with `DB::transaction(...)`.
- Existing bundled ecommerce repositories expose lock-specific lookup methods, for example `findByTypeForUpdate()` and `findByIdForUpdate()` using `lockForUpdate()`.
- Order placement groups order, payment, stock, coupon, and cart side effects in a single transaction when those writes must commit together.
- This module will follow the same boundary: service methods own transaction scope, repositories provide table-aware lookup/write primitives, and lock-specific reads must be explicit.

## Source Of Truth

- `economy_transactions` is the source of truth.
- `economy_wallets` is a derived balance cache.
- Wallet cache fields must be rebuildable from `economy_transactions` plus active hold state.
- No balance-changing operation may update wallet cache fields unless it also creates the required transaction row when the operation changes settled balance.
- No settled-balance transaction row may be created without updating the wallet cache in the same database transaction.

## Cache Field Names

`locked_balance` is the official wallet cache column name for reserved value. `economy_holds` remains the reservation entity and table; `locked_balance` is the wallet state derived from active holds and held debits.

## Transaction Boundary

All balance-changing operations must run inside `DB::transaction(...)`.

Inside the transaction:

1. Lock every wallet row that will be read for balance calculation with `lockForUpdate()`.
2. Recompute available balance from locked wallet state.
3. Validate domain invariants.
4. Create the ledger transaction row when settled balance changes.
5. Update wallet cache fields in the same transaction.
6. Update hold lifecycle fields in the same transaction when a hold is involved.
7. Dispatch hooks only when the hook contract is designed for transactional timing. Until then, hook dispatch remains deferred.

## Wallet Locking

- Single-wallet operations lock that wallet row before reading `balance` or `locked_balance`.
- Multi-wallet operations must lock rows in deterministic ascending `id` order to reduce deadlock risk.
- Transfer logic must lock both source and target wallets before calculating either side.
- Hold debit logic must lock the wallet row and the hold row before calculating balance or hold state.
- Future repository methods should make lock intent explicit, for example `findByIdForUpdate(int $id)` or `lockByIds(array $ids)`.

## Balance Invariants

- `available_balance = balance - locked_balance`.
- Negative available balance is forbidden unless `wallet.allow_negative_balance` is enabled.
- Hold creation increases `locked_balance` and does not reduce `balance`.
- Hold release or void decreases `locked_balance` and does not change `balance`.
- Debit from a hold creates a debit transaction and reduces both `balance` and `locked_balance`.
- Credit increases `balance`; `locked_balance` is unchanged.
- Standard debit decreases `balance`; `locked_balance` is unchanged.
- Transfers are two-sided: debit the source wallet and credit the target wallet in one database transaction.

## Idempotency

Idempotency key support is planned but not implemented yet.

Future mutation services should accept an optional idempotency key for externally retried operations. When present, the service must check the unique idempotency key inside the same transaction before creating a transaction row. Replays should return the existing transaction result rather than double-posting ledger rows.

## Pseudo-Code: Credit

```text
credit(CreditRequestData data):
  return DB::transaction:
    wallet = wallets.findByIdForUpdate(data.walletId)
    assert wallet exists
    assert amount is positive decimal(18,2)

    before = snapshot(wallet)
    after.balance = before.balance + amount
    after.locked_balance = before.locked_balance
    after.available_balance = after.balance - after.locked_balance

    create economy_transactions row:
      wallet_id = wallet.id
      transaction_type = credit
      amount = amount
      balance_after = after.balance
      locked_balance_after = after.locked_balance
      available_balance_after = after.available_balance
      source/correlation/metadata from data.context

    update economy_wallets cache with after values
    return transaction
```

## Pseudo-Code: Debit

```text
debit(DebitRequestData data):
  return DB::transaction:
    wallet = wallets.findByIdForUpdate(data.walletId)
    assert wallet exists
    assert amount is positive decimal(18,2)

    before = snapshot(wallet)
    before_available = before.balance - before.locked_balance
    assert before_available >= amount unless wallet.allow_negative_balance is enabled

    after.balance = before.balance - amount
    after.locked_balance = before.locked_balance
    after.available_balance = after.balance - after.locked_balance
    assert after.available_balance >= 0 unless wallet.allow_negative_balance is enabled

    create debit transaction row with after values
    update wallet cache with after values
    return transaction
```

## Pseudo-Code: Transfer

```text
transfer(TransferRequestData data):
  return DB::transaction:
    wallet_ids = sort ascending [data.fromWalletId, data.toWalletId]
    locked_wallets = wallets.lockByIds(wallet_ids)
    source = locked_wallets[data.fromWalletId]
    target = locked_wallets[data.toWalletId]
    assert source and target exist
    assert source.id != target.id
    assert amount is positive decimal(18,2)

    source_available = source.balance - source.locked_balance
    assert source_available >= amount unless source.allow_negative_balance is enabled

    source_after.balance = source.balance - amount
    source_after.locked_balance = source.locked_balance
    source_after.available_balance = source_after.balance - source_after.locked_balance

    target_after.balance = target.balance + amount
    target_after.locked_balance = target.locked_balance
    target_after.available_balance = target_after.balance - target_after.locked_balance

    create source debit transaction row
    create target credit transaction row linked by correlation_id
    update source wallet cache
    update target wallet cache
    return transfer result containing both transaction ids
```

## Pseudo-Code: Create Hold

```text
createHold(HoldRequestData data):
  return DB::transaction:
    wallet = wallets.findByIdForUpdate(data.walletId)
    assert wallet exists
    assert amount is positive decimal(18,2)

    before_available = wallet.balance - wallet.locked_balance
    assert before_available >= amount unless wallet.allow_negative_balance is enabled

    after.balance = wallet.balance
    after.locked_balance = wallet.locked_balance + amount
    after.available_balance = after.balance - after.locked_balance
    assert after.available_balance >= 0 unless wallet.allow_negative_balance is enabled

    create economy_holds row with active status
    update wallet cache with after values
    return hold
```

## Pseudo-Code: Release Hold

```text
releaseHold(ReleaseHoldRequestData data):
  return DB::transaction:
    hold = holds.findByIdForUpdate(data.holdId)
    assert hold exists and hold is active
    wallet = wallets.findByIdForUpdate(hold.walletId)
    assert wallet exists

    after.balance = wallet.balance
    after.locked_balance = wallet.locked_balance - hold.amount
    after.available_balance = after.balance - after.locked_balance
    assert after.locked_balance >= 0

    update hold status to released, set released_by/released_at
    update wallet cache with after values
    return hold
```

## Pseudo-Code: Debit Held Amount

```text
debitHeldAmount(holdId, context):
  return DB::transaction:
    hold = holds.findByIdForUpdate(holdId)
    assert hold exists and hold is active
    wallet = wallets.findByIdForUpdate(hold.walletId)
    assert wallet exists

    after.balance = wallet.balance - hold.amount
    after.locked_balance = wallet.locked_balance - hold.amount
    after.available_balance = after.balance - after.locked_balance
    assert after.locked_balance >= 0
    assert after.available_balance >= 0 unless wallet.allow_negative_balance is enabled

    create debit transaction row:
      wallet_id = wallet.id
      amount = hold.amount
      balance_after = after.balance
      locked_balance_after = after.locked_balance
      available_balance_after = after.available_balance
      source/correlation/metadata from hold and context

    update hold status to captured, set captured_at
    update wallet cache with after values
    return transaction and hold
```

## Scenario Test Design

These scenarios do not require a working database yet. They should become unit or feature tests after the migration lifecycle is safe on this host.

- Credit creates one transaction and updates wallet balance in the same transaction.
- Debit refuses insufficient available balance when negative balance is disabled.
- Debit allows negative available balance only when wallet settings explicitly allow it.
- Transfer locks wallet ids in ascending order regardless of source/target order.
- Transfer creates exactly two transaction rows sharing a correlation id.
- Hold creation increases locked balance and leaves settled balance unchanged.
- Hold release decreases locked balance and leaves settled balance unchanged.
- Held debit decreases both settled balance and locked balance and creates one debit transaction.
- Any failure after transaction row creation rolls back both transaction row and wallet cache update.
- Retried operations with a future idempotency key do not double-post transaction rows.

## Repository Primitives

Repository primitives for future mutation services are intentionally narrow and table-aware.

- Wallet reads include `findById`, `findByIdForUpdate`, `findByUserId`, and `findByUserIdForUpdate`.
- Wallet cache writes use `updateBalances(EconomyWallet $wallet, string $balance, string $lockedBalance)`, which derives `available_balance` as `balance - locked_balance` without applying business validation.
- Transaction writes use `createLedgerEntry(array $data)`; `create(array $data)` remains a compatibility alias.
- Transaction reads include `findByUuid(string $uuid)`.
- Hold reads include `findByIdForUpdate(int $id)`.
- Hold lifecycle writes use `updateStatus(EconomyHold $hold, EconomyHoldStatus $status, array $data = [])` after the service has selected and locked the hold when needed.

Read methods return `null` or an empty collection when module tables are absent. Mutation methods throw `EconomyTableUnavailableException` when required tables are absent. Repositories do not enforce balance rules, hold state rules, permissions, idempotency, or transfer invariants; those remain service responsibilities.

## Implemented Mutation Boundary

The initial service implementation follows this policy for direct wallet mutations and hold reserve/release/void flows. Hold void creates a `hold_void` ledger row because it changes the locked-balance cache even though settled balance is unchanged. `WalletService` owns credit, debit, and transfer. `HoldService` owns hold creation, release, and void. `EconomyService` delegates to those services so mutation behavior has a single implementation path.

Held debit/capture remains deferred because no dedicated DTO exists yet. It must not be improvised through release or debit APIs; a future `CaptureHoldRequestData` or equivalent contract should define actor, source, idempotency, and metadata semantics before implementation.

Hook dispatch currently happens inside the same database transaction as the mutation. Hook listeners for these mutation hooks must therefore avoid slow external I/O and must throw only when they intend to abort the mutation.

## HTTP Mutation Boundary

HTTP mutation endpoints validate shape and scalar constraints with FormRequests, then build immutable DTOs in controllers. FormRequests do not perform authorization, settings checks, ownership checks, repository reads, or mutation logic.

Trusted actor fields override caller-provided context at the controller boundary. Admin mutations derive `created_by` or `released_by` from the authenticated administrator. User transfers derive `created_by` and the source wallet from the authenticated user.

The user transfer endpoint checks the read-only `transfer.enabled` setting before calling `EconomyService::transfer()`. This is an API availability boundary; balance, locking, and transfer invariants remain service responsibilities. The initial API does not implement idempotency, transfer fees, daily limits, hold capture, or automatic wallet creation.
