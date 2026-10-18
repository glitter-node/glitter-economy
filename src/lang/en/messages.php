<?php

return array (
  'admin' => 
  array (
    'menu' => 
    array (
      'title' => 'Glitter Economy',
      'wallets' => 'Wallet Inspection',
      'transactions' => 'Transaction Inspection',
    ),
    'common' => 
    array (
      'back_to_wallets' => 'Back to wallet inspection',
    ),
    'wallets' => 
    array (
      'title' => 'Economy Wallets',
      'description' => 'Inspect economy wallet state with administrator permissions.',
      'lookup_title' => 'User Wallet Lookup',
      'lookup_description' => 'The current admin API supports single wallet lookup by user ID.',
      'user_id_placeholder' => 'User ID',
      'search' => 'Inspect',
      'search_placeholder' => 'User ID, email, name, or nickname',
      'empty' => 'No wallets found.',
      'total_wallets' => 'Total Wallets',
      'total_balance' => 'Total Balance',
      'total_locked_balance' => 'Total Locked Balance',
      'open_detail' => 'Open detail',
      'list_gap_title' => 'List API unavailable',
      'list_gap_message' => 'Wallet lists and summary metrics can be wired here when list and aggregate APIs are added.',
    ),
    'wallet_detail' => 
    array (
      'title' => 'Economy Wallet Detail',
      'description' => 'Inspect user wallet identity, status, and balances.',
      'identity_section' => 'Identity',
      'status_section' => 'Status',
    ),
    'transactions' => 
    array (
      'title' => 'Economy Transactions',
      'description' => 'Inspect the economy transaction ledger.',
      'filters_title' => 'Transaction Filters',
      'wallet_id_placeholder' => 'Wallet ID',
      'correlation_placeholder' => 'Correlation ID',
      'search' => 'Search',
      'empty' => 'No transactions found.',
      'type_placeholder' => 'Transaction Type',
      'reference_type_placeholder' => 'Reference Type',
      'reference_id_placeholder' => 'Reference ID',
      'date_from_placeholder' => 'Start Date',
      'date_to_placeholder' => 'End Date',
      'api_gap_title' => 'Transaction list API unavailable',
      'api_gap_message' => 'The current admin API does not provide a transaction list endpoint, so filter and table behavior is disabled.',
    ),
    'fields' => 
    array (
      'wallet_id' => 'Wallet ID',
      'uuid' => 'UUID',
      'user_id' => 'User ID',
      'wallet_type' => 'Wallet Type',
      'currency_code' => 'Currency Code',
      'balance' => 'Balance',
      'locked_balance' => 'Locked Balance',
      'available_balance' => 'Available Balance',
      'status' => 'Status',
      'transaction_type' => 'Transaction Type',
      'amount' => 'Amount',
      'balance_after' => 'Balance After',
      'occurred_at' => 'Occurred At',
    ),
  ),
  'api' => 
  array (
    'wallet_fetched' => 'Economy wallet fetched.',
    'wallets_fetched' => 'Economy wallets fetched.',
    'wallet_not_found' => 'Economy wallet not found.',
    'transactions_fetched' => 'Economy transactions fetched.',
    'wallet_credited' => 'Economy wallet credited.',
    'wallet_debited' => 'Economy wallet debited.',
    'wallet_transferred' => 'Economy wallet value transferred.',
    'hold_created' => 'Economy hold created.',
    'hold_released' => 'Economy hold released.',
    'hold_voided' => 'Economy hold voided.',
    'transfer_disabled' => 'Wallet transfers are disabled.',
    'mutation_rejected' => 'The economy mutation was rejected.',
    'mutation_failed' => 'The economy mutation failed.',
  ),
  'exceptions' => 
  array (
    'generic' => 'An economy error occurred.',
    'wallet_not_found' => 'Wallet not found. Wallet ID: :wallet_id',
    'insufficient_balance' => 'Available balance is insufficient.',
    'invalid_amount' => 'The economy amount is invalid.',
    'invalid_transfer' => 'The requested wallet transfer is invalid.',
    'decimal_math_unavailable' => 'Required decimal math support is unavailable.',
    'table_unavailable' => 'Economy table is unavailable. Table: :table',
    'hold_not_found' => 'Hold not found. Hold ID: :hold_id',
    'invalid_hold_state' => 'The requested operation is not allowed for the current hold state.',
    'mutation_deferred' => 'Economy balance mutation logic has not been implemented yet.',
    'settings_write_deferred' => 'Economy settings persistence has not been implemented yet.',
  ),
  'user' => 
  array (
    'wallet' => 
    array (
      'title' => 'My Wallet',
      'description' => 'View your wallet balances and recent economy transactions.',
      'wallet_status' => 'Wallet Status',
    ),
    'transactions' => 
    array (
      'title' => 'Recent Transactions',
      'empty' => 'No transactions found.',
    ),
    'fields' => 
    array (
      'balance' => 'Balance',
      'locked_balance' => 'Locked Balance',
      'available_balance' => 'Available Balance',
      'wallet_type' => 'Wallet Type',
      'currency_code' => 'Currency Code',
      'status' => 'Status',
      'transaction_type' => 'Transaction Type',
      'amount' => 'Amount',
      'balance_after' => 'Balance After',
      'occurred_at' => 'Occurred At',
    ),
  ),
);
