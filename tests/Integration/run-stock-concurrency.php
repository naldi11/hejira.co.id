<?php

/** Real independent PHP/MySQL connections. Refuses to run against an operational database. */
use App\Http\Controllers\Hendhys\ProductionController;
use App\Http\Controllers\Jihans\TransactionController;
use App\Http\Controllers\Shared\ShiftController;
use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\HendhysProduction;
use App\Models\HendhysStockPusat;
use App\Models\HendhysTransaction;
use App\Models\JihansTransaction;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TransferOut;
use App\Models\Unit;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\SaleShiftGuard;
use App\Services\StockService;
use App\Services\TransferReceiving;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (DB::connection()->getDriverName() !== 'mysql' || ! preg_match('/^hejira_integrity_test_[a-f0-9]{10}$/D', DB::connection()->getDatabaseName())) {
    throw new RuntimeException('Concurrency tests require a dedicated hejira_integrity_test_<random> MySQL database.');
}
Carbon::setTestNow('2026-08-03 10:00:00');

function requestFor(array $data): Request
{
    $request = Request::create('/integrity-test', 'POST', $data);
    $request->setLaravelSession(app('session.store'));
    $request->setUserResolver(fn () => Auth::user());
    app()->instance('request', $request);

    return $request;
}

function waitFile(string $path): void
{
    $deadline = microtime(true) + 15;
    while (! file_exists($path)) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Concurrency barrier timed out.');
        }
        usleep(10000);
        clearstatcache(true, $path);
    }
}

if (($argv[1] ?? '') === 'worker') {
    $job = json_decode(file_get_contents($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    Auth::setUser(User::findOrFail($job['user']));
    $sale = isset($job['sale']) ? JihansTransaction::findOrFail($job['sale']) : null;
    $transfer = isset($job['transfer']) ? TransferOut::findOrFail($job['transfer']) : null;
    file_put_contents($job['ready'], 'ready');
    waitFile($job['barrier']);
    if (isset($job['wait_for'])) {
        waitFile($job['wait_for']);
    }
    try {
        switch ($job['operation']) {
            case 'void':
                app(TransactionController::class)->void(requestFor(['reason' => 'Uji pembatalan bersamaan']), $sale);
                break;
            case 'receive':
                app(TransferReceiving::class)->receive($transfer, $job['data'], Auth::user());
                break;
            case 'produce':
                app(ProductionController::class)->store(requestFor($job['data']));
                break;
            case 'open':
                app(ShiftController::class)->open(requestFor(['starting_cash' => 0]));
                break;
            case 'close':
            case 'hold_close':
                DB::transaction(function () use ($job) {
                    if ($job['operation'] === 'hold_close') {
                        app(SaleShiftGuard::class)->active(Auth::user());
                        file_put_contents($job['signal'], 'locked');
                        usleep(300000);
                    }
                    app(ShiftController::class)->close(requestFor(['actual_cash' => 0]));
                });
                break;
            case 'checkout':
            case 'hold_checkout':
                app(CheckoutService::class)->run(HendhysTransaction::class, ['checkout_key' => $job['key']], function () use ($job) {
                    if ($job['operation'] === 'hold_checkout') {
                        file_put_contents($job['signal'], 'locked');
                        usleep(300000);
                    }
                    $sale = HendhysTransaction::create(['transaction_number' => 'CNC-'.substr(str_replace('-', '', $job['key']), 0, 20), 'date' => today(), 'time' => '10:00:00',
                        'status' => 'paid', 'grand_total' => 1000, 'created_by' => Auth::id()]);
                    app(StockService::class)->debitHendhys($job['product'], 1, null, 'pos_sale', $sale->id, Auth::id());
                    $sale->payments()->create(['payment_method' => 'cash', 'payment_type' => 'tunai', 'amount' => 1000]);

                    return $sale;
                });
                break;
            default: throw new RuntimeException('Unknown concurrency operation.');
        }
        echo json_encode(['success' => true]), PHP_EOL;
    } catch (ValidationException $e) {
        echo json_encode(['success' => false, 'validation' => array_keys($e->errors())]), PHP_EOL;
    } catch (Throwable $e) {
        fwrite(STDERR, $e::class.': '.$e->getMessage());
        exit(1);
    }
    exit(0);
}

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

$workspace = dirname(__DIR__, 2);
$temp = $workspace.'/output/concurrency-'.bin2hex(random_bytes(5));
mkdir($temp);
$results = [];

function parallelJobs(array $jobs, string $temp): array
{
    $batch = bin2hex(random_bytes(4));
    $children = [];
    foreach ($jobs as $i => $job) {
        $job += ['barrier' => $temp.'/'.$batch.'.start', 'ready' => $temp.'/'.$batch.'.'.$i.'.ready'];
        $path = $temp.'/'.$batch.'.'.$i.'.json';
        file_put_contents($path, json_encode($job, JSON_THROW_ON_ERROR));
        $process = proc_open([PHP_BINARY, '-d', 'sys_temp_dir='.sys_get_temp_dir(), __FILE__, 'worker', $path],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2));
        if (! is_resource($process)) {
            throw new RuntimeException('Cannot start concurrent worker.');
        }
        fclose($pipes[0]);
        $children[] = [$process, $pipes, $job];
    }
    foreach ($children as [, , $job]) {
        waitFile($job['ready']);
    }
    file_put_contents($temp.'/'.$batch.'.start', 'go');
    $outputs = [];
    foreach ($children as [$process, $pipes]) {
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);
        check($code === 0, 'Concurrent worker failed: '.$stderr.' '.$stdout);
        $outputs[] = json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
    }

    return $outputs;
}

function person(string $role, Branch $branch): User
{
    Role::findOrCreate($role, 'web');
    $user = User::factory()->create(['entity' => $branch->entity, 'branch_id' => $branch->id]);
    $user->assignRole($role);

    return $user;
}

$suffix = bin2hex(random_bytes(4));
$centre = Branch::create(['code' => 'CNC-P-'.$suffix, 'name' => 'Concurrency Pusat', 'entity' => 'hendhys', 'type' => 'pusat', 'is_active' => true]);
$branch = Branch::create(['code' => 'CNC-B-'.$suffix, 'name' => 'Concurrency Cabang', 'entity' => 'hendhys', 'type' => 'cabang', 'is_active' => true]);
$jBranch = Branch::create(['code' => 'CNC-J-'.$suffix, 'name' => 'Concurrency Jihans', 'entity' => 'jihans', 'type' => 'cabang', 'is_active' => true]);
$unit = Unit::create(['name' => 'Concurrency '.$suffix, 'abbreviation' => 'PCS', 'entity_scope' => 'all']);
$category = ProductCategory::create(['name' => 'Concurrency '.$suffix, 'entity_scope' => 'all']);
$product = Product::create(['code' => 'CNC-'.$suffix, 'name' => 'Concurrency Product', 'category_id' => $category->id, 'unit_id' => $unit->id,
    'product_type' => 'INV', 'source_type' => 'purchased', 'entity_scope' => 'all', 'status' => 'active', 'selling_price' => 1000]);

try {
    $cashier = person('kasir_jihans', $jBranch);
    $admin = person('admin_jihans', $jBranch);
    CashierShift::create(['user_id' => $cashier->id, 'branch_id' => $jBranch->id, 'entity' => 'jihans', 'status' => 'open', 'opened_at' => now()->subMinute(), 'starting_cash' => 0]);
    $sale = JihansTransaction::create(['transaction_number' => 'CNC-VOID-'.$suffix, 'date' => today(), 'time' => '10:00:00', 'status' => 'paid', 'grand_total' => 5000, 'created_by' => $cashier->id]);
    $sale->details()->create(['product_id' => $product->id, 'unit_id' => $unit->id, 'product_name' => $product->name, 'quantity' => 5, 'price' => 1000, 'total' => 5000]);
    $job = ['operation' => 'void', 'user' => $admin->id, 'sale' => $sale->id];
    parallelJobs([$job, $job], $temp);
    check((int) DB::table('jihans_retail_stock')->where('product_id', $product->id)->value('quantity') === 5, 'Concurrent void duplicated stock.');
    $results[] = ['scenario' => 'two concurrent voids', 'passed' => true, 'restored' => 5];

    $receiver = person('kasir_hendhys', $branch);
    $transfer = TransferOut::create(['transfer_number' => 'CNC-TRF-'.$suffix, 'to_entity' => 'hendhys', 'branch_id' => $branch->id, 'date' => today(), 'status' => 'sent', 'created_by' => $receiver->id]);
    $detail = $transfer->details()->create(['product_id' => $product->id, 'unit_id' => $unit->id, 'quantity' => 5, 'hpp_price' => 1, 'total' => 5]);
    $job = ['operation' => 'receive', 'user' => $receiver->id, 'transfer' => $transfer->id, 'data' => ['received_quantities' => [$detail->id => 5]]];
    parallelJobs([$job, $job], $temp);
    check((int) DB::table('hendhys_stock_branch')->where('branch_id', $branch->id)->where('product_id', $product->id)->value('quantity') === 5, 'Concurrent receiving duplicated stock.');
    check(DB::table('receipt_confirmations')->where('receiptable_type', TransferOut::class)->where('receiptable_id', $transfer->id)->count() === 1, 'Concurrent receiving duplicated BAST.');
    $results[] = ['scenario' => 'two concurrent receipts', 'passed' => true, 'received' => 5, 'bast_count' => 1];

    $a = person('admin_hendhys', $centre);
    $b = person('admin_hendhys', $centre);
    $product->update(['source_type' => 'produced']);
    $prediction = HendhysProduction::create(['production_number' => 'CNC-PRED-'.$suffix, 'date' => today(), 'type' => 'prediksi', 'created_by' => $a->id]);
    $prediction->details()->create(['product_id' => $product->id, 'unit_id' => $unit->id, 'quantity_produced' => 10]);
    $payload = ['prediction_id' => $prediction->id, 'date' => today()->toDateString(), 'items' => [['product_id' => $product->id, 'unit_id' => $unit->id, 'quantity_produced' => 10]]];
    parallelJobs([['operation' => 'produce', 'user' => $a->id, 'data' => $payload], ['operation' => 'produce', 'user' => $b->id, 'data' => $payload]], $temp);
    check((int) HendhysStockPusat::where('product_id', $product->id)->value('quantity') === 10, 'Concurrent production duplicated stock.');
    check($prediction->fresh()->type === 'aktual', 'Prediction was not actualized.');
    $results[] = ['scenario' => 'two admins actualize one prediction', 'passed' => true, 'produced' => 10];

    $opener = person('kasir_hendhys', $centre);
    $job = ['operation' => 'open', 'user' => $opener->id];
    parallelJobs([$job, $job], $temp);
    check(CashierShift::where('user_id', $opener->id)->where('status', 'open')->count() === 1, 'Concurrent open created two active shifts.');
    $results[] = ['scenario' => 'two concurrent shift opens', 'passed' => true, 'open_shifts' => 1];

    foreach (['checkout_first', 'close_first'] as $order) {
        $user = person('kasir_hendhys', $centre);
        $shift = CashierShift::create(['user_id' => $user->id, 'branch_id' => $centre->id, 'entity' => 'hendhys', 'status' => 'open', 'opened_at' => now()->subMinute(), 'starting_cash' => 0]);
        $before = (int) HendhysStockPusat::where('product_id', $product->id)->value('quantity');
        $signal = $temp.'/'.$order.'.locked';
        $checkout = ['operation' => $order === 'checkout_first' ? 'hold_checkout' : 'checkout', 'user' => $user->id, 'product' => $product->id, 'key' => (string) Str::uuid()];
        $close = ['operation' => $order === 'close_first' ? 'hold_close' : 'close', 'user' => $user->id];
        if ($order === 'checkout_first') {
            $checkout['signal'] = $signal;
            $close['wait_for'] = $signal;
        } else {
            $close['signal'] = $signal;
            $checkout['wait_for'] = $signal;
        }
        $outcomes = parallelJobs([$checkout, $close], $temp);
        $saleCount = HendhysTransaction::where('shift_id', $shift->id)->count();
        $expected = $order === 'checkout_first' ? 1000 : 0;
        check($saleCount === ($expected ? 1 : 0), 'Checkout crossed a closed shift.');
        check((int) $shift->fresh()->expected_cash === $expected && $shift->fresh()->status === 'closed', 'Closing omitted or invented a sale.');
        check((int) HendhysStockPusat::where('product_id', $product->id)->value('quantity') === $before - ($expected ? 1 : 0), 'Checkout/close stock mismatch.');
        $results[] = ['scenario' => $order.' with concurrent closing/checkout', 'passed' => true, 'sales' => $saleCount, 'expected_cash' => $expected];
    }
    $report = ['database' => DB::connection()->getDatabaseName(), 'driver' => 'mysql', 'independent_worker_processes' => 12, 'scenarios' => $results];
    file_put_contents($workspace.'/output/stock-fix-concurrency.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    echo json_encode($report, JSON_THROW_ON_ERROR), PHP_EOL;
} finally {
    foreach (glob($temp.'/*') as $file) {
        unlink($file);
    }
    rmdir($temp);
}
