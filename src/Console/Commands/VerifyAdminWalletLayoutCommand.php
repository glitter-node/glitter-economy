<?php

namespace Modules\Glitter\Economy\Console\Commands;

use Illuminate\Console\Command;
use JsonException;
use RuntimeException;
use Throwable;

class VerifyAdminWalletLayoutCommand extends Command
{
    protected $signature = 'glitter-economy:verify-layout';

    protected $description = 'Verify the non-persistent admin wallet credit and debit layout actions';

    public function handle(): int
    {
        $paths = [
            'bundled' => base_path('modules/_bundled/glitter-economy/resources/layouts/admin/admin_glitter_economy_wallets.json'),
            'active' => base_path('modules/glitter-economy/resources/layouts/admin/admin_glitter_economy_wallets.json'),
        ];

        try {
            $layouts = [];

            foreach ($paths as $name => $path) {
                $layouts[$name] = $this->loadLayout($path);
                $this->verifyLayout($name, $layouts[$name]);
            }

            $this->assertSame($layouts['bundled'], $layouts['active'], 'active layout matches bundled layout');
            $this->info('PASS active layout matches bundled layout');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error("FAIL {$exception->getMessage()}");

            return self::FAILURE;
        }
    }

    /** @return array<string, mixed> */
    private function loadLayout(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("layout file not found: {$path}");
        }

        try {
            $layout = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("invalid layout JSON: {$path}: {$exception->getMessage()}", previous: $exception);
        }

        if (! is_array($layout)) {
            throw new RuntimeException("layout root must be an object: {$path}");
        }

        return $layout;
    }

    /** @param array<string, mixed> $layout */
    private function verifyLayout(string $name, array $layout): void
    {
        $components = $this->collectComponents($layout['slots'] ?? []);
        $form = $this->findComponentById($components, 'manual_adjustment');

        $this->assertSame('Form', $form['name'] ?? null, "{$name} adjustment component is a Form");
        $this->assertSame('adjustmentForm', $form['dataKey'] ?? null, "{$name} adjustment Form binds adjustmentForm");
        $this->info("PASS {$name} adjustment Form binding");

        $this->verifyButtonAction($name, $components, 'credit');
        $this->verifyButtonAction($name, $components, 'debit');
    }

    /** @param array<int, array<string, mixed>> $components */
    private function verifyButtonAction(string $layoutName, array $components, string $operation): void
    {
        $endpoint = "/api/modules/glitter-economy/admin/wallets/{$operation}";
        $apiCall = null;

        foreach ($components as $component) {
            if (($component['name'] ?? null) !== 'Button') {
                continue;
            }

            foreach ($component['actions'] ?? [] as $action) {
                if (($action['type'] ?? null) !== 'click' || ($action['handler'] ?? null) !== 'sequence') {
                    continue;
                }

                foreach ($action['actions'] ?? [] as $sequenceAction) {
                    if (($sequenceAction['handler'] ?? null) === 'apiCall' && ($sequenceAction['target'] ?? null) === $endpoint) {
                        $apiCall = $sequenceAction;
                        break 3;
                    }
                }
            }
        }

        if (! is_array($apiCall)) {
            throw new RuntimeException("{$layoutName} {$operation} Button click sequence apiCall was not found");
        }

        $this->assertSame('POST', $apiCall['params']['method'] ?? null, "{$layoutName} {$operation} apiCall method");
        $this->assertSame('{{_local.adjustmentForm}}', $apiCall['params']['body'] ?? null, "{$layoutName} {$operation} apiCall body binding");

        $refetchesWallets = false;
        foreach ($apiCall['onSuccess'] ?? [] as $successAction) {
            if (($successAction['handler'] ?? null) === 'refetchDataSource'
                && ($successAction['params']['dataSourceId'] ?? null) === 'wallets') {
                $refetchesWallets = true;
                break;
            }
        }

        $this->assertSame(true, $refetchesWallets, "{$layoutName} {$operation} success refetches wallets");
        $this->info("PASS {$layoutName} {$operation} Button POST action and wallets refetch");
    }

    /** @return array<int, array<string, mixed>> */
    private function collectComponents(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $components = [];
        if (isset($value['type'], $value['name'])) {
            $components[] = $value;
        }

        foreach ($value as $child) {
            $components = [...$components, ...$this->collectComponents($child)];
        }

        return $components;
    }

    /**
     * @param array<int, array<string, mixed>> $components
     * @return array<string, mixed>
     */
    private function findComponentById(array $components, string $id): array
    {
        foreach ($components as $component) {
            if (($component['id'] ?? null) === $id) {
                return $component;
            }
        }

        throw new RuntimeException("component not found: {$id}");
    }

    private function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message);
        }
    }
}
