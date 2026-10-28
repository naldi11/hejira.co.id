<?php

/** Disposable MySQL verification; the configured operational database is never migrated or truncated. */
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$workspace = dirname(__DIR__, 2);
if (file_exists($workspace.'/bootstrap/cache/config.php')) throw new RuntimeException('Clear cached configuration before running isolated verification.');
$app = require $workspace.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$mysql = config('database.connections.mysql');
$operational = $mysql['database'];
$testDatabase = 'hejira_integrity_test_'.bin2hex(random_bytes(5));
if (!preg_match('/^hejira_integrity_test_[a-f0-9]{10}$/D', $testDatabase) || $testDatabase === $operational) throw new RuntimeException('Unsafe test database name.');
$dsn = 'mysql:host='.$mysql['host'].';port='.$mysql['port'].';charset=utf8mb4';
$admin = new PDO($dsn, $mysql['username'], $mysql['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$admin->exec('CREATE DATABASE `'.$testDatabase.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

function child(array $arguments, string $workspace): void
{
    $process = proc_open([PHP_BINARY, '-d', 'sys_temp_dir='.sys_get_temp_dir(), ...$arguments],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $workspace);
    if (!is_resource($process)) throw new RuntimeException('Cannot start verification process.');
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]); $code = proc_close($process);
    if ($code !== 0) throw new RuntimeException('Verification process failed: '.$output.' '.$error);
    echo trim($output), PHP_EOL;
}

try {
    foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => $testDatabase, 'DB_URL' => '',
        'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => 'array'] as $key => $value) {
        putenv($key.'='.$value); $_ENV[$key] = $value; $_SERVER[$key] = $value;
    }
    config(['database.default' => 'mysql', 'database.connections.mysql.database' => $testDatabase, 'database.connections.mysql.url' => null]);
    DB::purge('mysql');
    if (DB::connection()->getDatabaseName() !== $testDatabase) throw new RuntimeException('Test connection mismatch.');
    Artisan::call('migrate', ['--force' => true]);

    // Rehearse the schema upgrade on a legacy delivery document and verify its data survives.
    $user = App\Models\User::factory()->create();
    $legacy = App\Models\TransferOut::create(['transfer_number' => 'LEGACY-MIGRATION-CHECK', 'to_entity' => 'jihans', 'date' => today(), 'created_by' => $user->id]);
    $migration = require $workspace.'/database/migrations/2026_10_28_000001_enable_izzi_warehouse_shipments.php';
    $migration->down(); $migration->up();
    if ($legacy->fresh()->to_entity !== 'jihans') throw new RuntimeException('Legacy shipment was changed by schema upgrade.');
    echo json_encode(['migration_upgrade' => 'passed', 'database' => $testDatabase]), PHP_EOL;

    child(['vendor/phpunit/phpunit/phpunit', 'tests/Feature/Inventory/IzziWarehouseShipmentTest.php',
        'tests/Feature/Inventory/BusinessStockAccessTest.php', 'tests/Feature/Inventory/StockTransactionIntegrityTest.php',
        'tests/Feature/Security/StockAuditRegressionTest.php', '--no-progress', '--colors=never', '--do-not-cache-result',
        '--log-junit', 'output/izzi-shipment-mysql.xml'], $workspace);
    child(['tests/Integration/run-stock-concurrency.php'], $workspace);
} finally {
    DB::disconnect('mysql');
    if (!preg_match('/^hejira_integrity_test_[a-f0-9]{10}$/D', $testDatabase) || $testDatabase === $operational) throw new RuntimeException('Refusing unsafe test database cleanup.');
    $admin->exec('DROP DATABASE `'.$testDatabase.'`');
    echo json_encode(['test_database_removed' => $testDatabase]), PHP_EOL;
}
