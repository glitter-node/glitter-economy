<?php

declare(strict_types=1);

namespace Modules\Glitter\Economy\Tests\Unit;

use Modules\Glitter\Economy\Module;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

require_once dirname(__DIR__, 2).'/module.php';

final class MenuAndFrontendI18nContractTest extends TestCase
{
    public function test_admin_menu_uses_translated_entity_names_and_preserves_canonical_metadata(): void
    {
        $menus = (new Module)->getAdminMenus();
        $parent = $menus[0];
        $wallets = $parent['children'][0];
        $transactions = $parent['children'][1];

        self::assertSame(['ko' => '이코노미 지갑 관리', 'en' => 'Economy Wallet Management'], $parent['name']);
        self::assertSame(['ko' => '지갑 관리', 'en' => 'Wallets'], $wallets['name']);
        self::assertSame(['ko' => '거래 내역', 'en' => 'Transactions'], $transactions['name']);

        self::assertSame(
            ['glitter-economy', '/admin/glitter-economy', 'glitter-economy.admin.access', 'fas fa-wallet', 50],
            [$parent['slug'], $parent['url'], $parent['permission'], $parent['icon'], $parent['order']],
        );
        self::assertSame(
            ['glitter-economy-wallets', '/admin/glitter-economy', 'glitter-economy.admin.access', 'fas fa-wallet', 1],
            [$wallets['slug'], $wallets['url'], $wallets['permission'], $wallets['icon'], $wallets['order']],
        );
        self::assertSame(
            ['glitter-economy-transactions', '/admin/glitter-economy/transactions', 'glitter-economy.transactions.view', 'fas fa-list', 2],
            [$transactions['slug'], $transactions['url'], $transactions['permission'], $transactions['icon'], $transactions['order']],
        );
    }

    public function test_every_frontend_translation_reference_resolves_in_korean_and_english(): void
    {
        $moduleRoot = dirname(__DIR__, 2);
        $translations = [];

        foreach (['ko', 'en'] as $locale) {
            $translations[$locale] = json_decode(
                (string) file_get_contents($moduleRoot."/resources/lang/{$locale}.json"),
                true,
                flags: JSON_THROW_ON_ERROR,
            );
        }

        $keys = [];
        foreach (['layouts', 'routes'] as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($moduleRoot.'/resources/'.$directory));
            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'json') {
                    continue;
                }
                preg_match_all('/\\$t:(glitter-economy\\.[A-Za-z0-9_.-]+)/', (string) file_get_contents($file->getPathname()), $matches);
                array_push($keys, ...$matches[1]);
            }
        }

        $keys = array_values(array_unique($keys));
        self::assertNotEmpty($keys);

        foreach ($keys as $key) {
            $path = explode('.', substr($key, strlen('glitter-economy.')));
            foreach ($translations as $locale => $catalog) {
                $resolved = $catalog;
                foreach ($path as $segment) {
                    self::assertIsArray($resolved, "{$locale}: {$key}의 중간 경로가 객체가 아닙니다.");
                    self::assertArrayHasKey($segment, $resolved, "{$locale}: {$key} 번역이 없습니다.");
                    $resolved = $resolved[$segment];
                }
                self::assertIsString($resolved, "{$locale}: {$key} 번역 결과가 문자열이 아닙니다.");
                self::assertNotSame($key, $resolved, "{$locale}: {$key}가 자기 자신으로 fallback 되었습니다.");
            }
        }
    }
}
