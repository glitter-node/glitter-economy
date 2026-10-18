<?php

namespace Modules\Glitter\Economy;

use App\Extension\AbstractModule;

class Module extends AbstractModule
{
    /**
     * Module roles created during installation.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRoles(): array
    {
        return [
            [
                'identifier' => 'glitter-economy.manager',
                'name' => [
                    'ko' => '이코노미 관리자',
                    'en' => 'Economy Manager',
                ],
                'description' => [
                    'ko' => '이코노미 지갑, 거래, 조정 내역을 관리하는 역할',
                    'en' => 'Role for managing economy wallets, transactions, and adjustments',
                ],
            ],
            [
                'identifier' => 'glitter-economy.auditor',
                'name' => [
                    'ko' => '이코노미 감사자',
                    'en' => 'Economy Auditor',
                ],
                'description' => [
                    'ko' => '이코노미 원장과 감사 내역을 조회하는 역할',
                    'en' => 'Role for viewing economy ledgers and audit records',
                ],
            ],
        ];
    }

    /**
     * Module permissions.
     *
     * Gnuboard7 stores module permissions as {module}.{category}.{action}.
     *
     * @return array<string, mixed>
     */
    public function getPermissions(): array
    {
        return [
            'name' => [
                'ko' => '글리터 이코노미',
                'en' => 'Glitter Economy',
            ],
            'description' => [
                'ko' => '글리터 이코노미 모듈 권한',
                'en' => 'Glitter Economy module permissions',
            ],
            'categories' => [
                [
                    'identifier' => 'wallet',
                    'type' => 'user',
                    'resource_route_key' => 'wallet',
                    'owner_key' => 'user_id',
                    'name' => [
                        'ko' => '지갑',
                        'en' => 'Wallet',
                    ],
                    'description' => [
                        'ko' => '이코노미 지갑 조회와 이체 권한',
                        'en' => 'Permissions for viewing wallets and transferring value',
                    ],
                    'permissions' => [
                        [
                            'action' => 'view',
                            'name' => [
                                'ko' => '지갑 조회',
                                'en' => 'View Wallet',
                            ],
                            'description' => [
                                'ko' => '이코노미 지갑 잔액과 상태를 조회',
                                'en' => 'View economy wallet balance and status',
                            ],
                            'type' => 'user',
                            'roles' => ['admin', 'manager', 'user', 'glitter-economy.manager', 'glitter-economy.auditor'],
                        ],
                        [
                            'action' => 'transfer',
                            'name' => [
                                'ko' => '지갑 이체',
                                'en' => 'Transfer Wallet Value',
                            ],
                            'description' => [
                                'ko' => '이코노미 지갑 간 가치를 이체',
                                'en' => 'Transfer value between economy wallets',
                            ],
                            'type' => 'user',
                            'roles' => ['admin', 'manager', 'user', 'glitter-economy.manager'],
                        ],
                    ],
                ],
                [
                    'identifier' => 'transactions',
                    'type' => 'admin',
                    'resource_route_key' => 'transaction',
                    'owner_key' => 'user_id',
                    'name' => [
                        'ko' => '거래',
                        'en' => 'Transactions',
                    ],
                    'description' => [
                        'ko' => '이코노미 원장 거래 조회와 내보내기 권한',
                        'en' => 'Permissions for viewing and exporting economy ledger transactions',
                    ],
                    'permissions' => [
                        [
                            'action' => 'view',
                            'name' => [
                                'ko' => '거래 조회',
                                'en' => 'View Transactions',
                            ],
                            'description' => [
                                'ko' => '이코노미 원장 거래 내역을 조회',
                                'en' => 'View economy ledger transactions',
                            ],
                            'type' => 'admin',
                            'roles' => ['admin', 'manager', 'glitter-economy.manager', 'glitter-economy.auditor'],
                        ],
                        [
                            'action' => 'export',
                            'name' => [
                                'ko' => '거래 내보내기',
                                'en' => 'Export Transactions',
                            ],
                            'description' => [
                                'ko' => '이코노미 원장 거래 내역을 내보내기',
                                'en' => 'Export economy ledger transactions',
                            ],
                            'type' => 'admin',
                            'roles' => ['admin', 'glitter-economy.manager', 'glitter-economy.auditor'],
                        ],
                    ],
                ],
                [
                    'identifier' => 'adjust',
                    'type' => 'admin',
                    'name' => [
                        'ko' => '잔액 조정',
                        'en' => 'Adjustments',
                    ],
                    'description' => [
                        'ko' => '관리자 보정 거래 권한',
                        'en' => 'Permission for administrative adjustment transactions',
                    ],
                    'permissions' => [
                        [
                            'action' => 'access',
                            'name' => [
                                'ko' => '잔액 조정',
                                'en' => 'Adjust Balance',
                            ],
                            'description' => [
                                'ko' => '관리자 보정 거래를 생성',
                                'en' => 'Create administrative adjustment transactions',
                            ],
                            'type' => 'admin',
                            'roles' => ['admin', 'glitter-economy.manager'],
                        ],
                    ],
                ],
                [
                    'identifier' => 'audit',
                    'type' => 'admin',
                    'name' => [
                        'ko' => '감사',
                        'en' => 'Audit',
                    ],
                    'description' => [
                        'ko' => '이코노미 감사 권한',
                        'en' => 'Economy audit permission',
                    ],
                    'permissions' => [
                        [
                            'action' => 'access',
                            'name' => [
                                'ko' => '감사 접근',
                                'en' => 'Access Audit',
                            ],
                            'description' => [
                                'ko' => '이코노미 감사 데이터를 조회',
                                'en' => 'View economy audit data',
                            ],
                            'type' => 'admin',
                            'roles' => ['admin', 'glitter-economy.auditor'],
                        ],
                    ],
                ],
                [
                    'identifier' => 'admin',
                    'type' => 'admin',
                    'name' => [
                        'ko' => '관리',
                        'en' => 'Administration',
                    ],
                    'description' => [
                        'ko' => '이코노미 모듈 관리자 권한',
                        'en' => 'Economy module administration permission',
                    ],
                    'permissions' => [
                        [
                            'action' => 'access',
                            'name' => [
                                'ko' => '관리 접근',
                                'en' => 'Access Administration',
                            ],
                            'description' => [
                                'ko' => '이코노미 모듈 관리 기능에 접근',
                                'en' => 'Access economy module administration features',
                            ],
                            'type' => 'admin',
                            'roles' => ['admin', 'glitter-economy.manager'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * No install seeders are registered in the design baseline.
     *
     * @return array<int, class-string<\Illuminate\Database\Seeder>>
     */
    public function getSeeders(): array
    {
        return [];
    }

    /**
     * No hook listeners are registered until business logic is introduced.
     *
     * @return array<int, class-string>
     */
    public function getHookListeners(): array
    {
        return [];
    }

    /**
     * Module admin menu definitions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAdminMenus(): array
    {
        return [
            [
                "name" => [
                    "ko" => "이코노미 지갑 관리",
                    "en" => "Economy Wallet Management",
                ],
                "slug" => "glitter-economy",
                "icon" => "fas fa-wallet",
                "order" => 50,
                "permission" => "glitter-economy.admin.access",
                "children" => [
                    [
                        "name" => [
                            "ko" => "지갑 관리",
                            "en" => "Wallets",
                        ],
                        "slug" => "glitter-economy-wallets",
                        "url" => "/admin/glitter-economy",
                        "icon" => "fas fa-wallet",
                        "order" => 1,
                        "permission" => "glitter-economy.admin.access",
                    ],
                    [
                        "name" => [
                            "ko" => "거래 내역",
                            "en" => "Transactions",
                        ],
                        "slug" => "glitter-economy-transactions",
                        "url" => "/admin/glitter-economy/transactions",
                        "icon" => "fas fa-list",
                        "order" => 2,
                        "permission" => "glitter-economy.transactions.view",
                    ],
                ],
            ],
        ];
    }
}
