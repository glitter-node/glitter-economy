<?php

return array (
  'admin' => 
  array (
    'menu' => 
    array (
      'title' => '글리터 이코노미',
      'wallets' => '지갑 조회',
      'transactions' => '거래 조회',
    ),
    'common' => 
    array (
      'back_to_wallets' => '지갑 조회로 돌아가기',
    ),
    'wallets' => 
    array (
      'title' => '이코노미 지갑',
      'description' => '관리자 권한으로 이코노미 지갑 상태를 조회합니다.',
      'lookup_title' => '사용자 지갑 조회',
      'lookup_description' => '현재 제공되는 관리자 API는 사용자 ID 기반 단건 지갑 조회를 지원합니다.',
      'user_id_placeholder' => '사용자 ID',
      'search' => '조회',
      'search_placeholder' => '사용자 ID, 이메일, 이름, 닉네임',
      'empty' => '조회된 지갑이 없습니다.',
      'total_wallets' => '전체 지갑',
      'total_balance' => '전체 잔액',
      'total_locked_balance' => '전체 잠금 잔액',
      'open_detail' => '상세 보기',
      'list_gap_title' => '목록 API 미제공',
      'list_gap_message' => '지갑 목록과 집계 API가 추가되면 이 영역에 목록과 요약 지표를 연결할 수 있습니다.',
    ),
    'wallet_detail' => 
    array (
      'title' => '이코노미 지갑 상세',
      'description' => '사용자 지갑의 식별 정보, 상태, 잔액을 확인합니다.',
      'identity_section' => '식별 정보',
      'status_section' => '상태 정보',
    ),
    'transactions' => 
    array (
      'title' => '이코노미 거래',
      'description' => '이코노미 거래 원장 조회 화면입니다.',
      'filters_title' => '거래 필터',
      'wallet_id_placeholder' => '지갑 ID',
      'correlation_placeholder' => '상관관계 ID',
      'search' => '검색',
      'empty' => '조회된 거래가 없습니다.',
      'type_placeholder' => '거래 유형',
      'reference_type_placeholder' => '참조 유형',
      'reference_id_placeholder' => '참조 ID',
      'date_from_placeholder' => '시작일',
      'date_to_placeholder' => '종료일',
      'api_gap_title' => '거래 목록 API 미제공',
      'api_gap_message' => '현재 관리자 API에는 거래 목록 조회 엔드포인트가 없어 필터와 표 동작은 비활성화되어 있습니다.',
    ),
    'fields' => 
    array (
      'wallet_id' => '지갑 ID',
      'uuid' => 'UUID',
      'user_id' => '사용자 ID',
      'wallet_type' => '지갑 유형',
      'currency_code' => '통화 코드',
      'balance' => '잔액',
      'locked_balance' => '잠금 잔액',
      'available_balance' => '사용 가능 잔액',
      'status' => '상태',
      'transaction_type' => '거래 유형',
      'amount' => '금액',
      'balance_after' => '거래 후 잔액',
      'occurred_at' => '발생 일시',
    ),
  ),
  'api' => 
  array (
    'wallet_fetched' => '이코노미 지갑을 조회했습니다.',
    'wallets_fetched' => '이코노미 지갑 목록을 조회했습니다.',
    'wallet_not_found' => '이코노미 지갑을 찾을 수 없습니다.',
    'transactions_fetched' => '이코노미 거래 내역을 조회했습니다.',
    'wallet_credited' => '이코노미 지갑에 금액을 적립했습니다.',
    'wallet_debited' => '이코노미 지갑에서 금액을 차감했습니다.',
    'wallet_transferred' => '이코노미 지갑 금액을 이체했습니다.',
    'hold_created' => '이코노미 보류를 생성했습니다.',
    'hold_released' => '이코노미 보류를 해제했습니다.',
    'hold_voided' => '이코노미 보류를 취소했습니다.',
    'transfer_disabled' => '지갑 이체가 비활성화되어 있습니다.',
    'mutation_rejected' => '이코노미 변경 요청이 거부되었습니다.',
    'mutation_failed' => '이코노미 변경 처리에 실패했습니다.',
  ),
  'exceptions' => 
  array (
    'generic' => '이코노미 처리 중 오류가 발생했습니다.',
    'wallet_not_found' => '지갑을 찾을 수 없습니다. 지갑 ID: :wallet_id',
    'insufficient_balance' => '사용 가능한 잔액이 부족합니다.',
    'invalid_amount' => '이코노미 금액이 유효하지 않습니다.',
    'invalid_transfer' => '요청한 지갑 이체가 유효하지 않습니다.',
    'decimal_math_unavailable' => '필수 decimal 연산 지원을 사용할 수 없습니다.',
    'table_unavailable' => '이코노미 테이블을 사용할 수 없습니다. 테이블: :table',
    'hold_not_found' => '보류 내역을 찾을 수 없습니다. 보류 ID: :hold_id',
    'invalid_hold_state' => '현재 보류 상태에서는 요청한 작업을 수행할 수 없습니다.',
    'mutation_deferred' => '이코노미 잔액 변경 로직은 아직 구현되지 않았습니다.',
    'settings_write_deferred' => '이코노미 설정 저장은 아직 구현되지 않았습니다.',
  ),
  'user' => 
  array (
    'wallet' => 
    array (
      'title' => '내 지갑',
      'description' => '내 지갑 잔액과 최근 이코노미 거래 내역을 확인합니다.',
      'wallet_status' => '지갑 상태',
    ),
    'transactions' => 
    array (
      'title' => '최근 거래',
      'empty' => '조회된 거래가 없습니다.',
    ),
    'fields' => 
    array (
      'balance' => '잔액',
      'locked_balance' => '잠금 잔액',
      'available_balance' => '사용 가능 잔액',
      'wallet_type' => '지갑 유형',
      'currency_code' => '통화 코드',
      'status' => '상태',
      'transaction_type' => '거래 유형',
      'amount' => '금액',
      'balance_after' => '거래 후 잔액',
      'occurred_at' => '발생 일시',
    ),
  ),
);
