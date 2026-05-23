# Payment Methods, Karyawan & Produksi Tortilla — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tambah master Metode Pembayaran (dipakai di POS Hendhys + Jihan's), master Karyawan (Jihan's), dan modul Produksi Tortilla per karyawan dengan rekap mingguan untuk penggajian.

**Architecture:** Tiga fitur independen yang diimplementasi berurutan: (1) master data baru mengikuti pola `ScopesMasterData` yang sudah ada, (2) modul tortilla sebagai tabel terpisah dari `jihans_productions`, (3) integrasi POS mengganti enum hardcode `cash|transfer` dengan FK ke `master_payment_methods`.

**Tech Stack:** Laravel 11, MySQL, Blade + Alpine.js, TomSelect 2.3.1 (CDN per-page), maatwebsite/excel, Tailwind CSS (Material Design 3 tokens)

**Spec:** `docs/superpowers/specs/2026-05-23-payment-methods-karyawan-tortilla-design.md`

---

## File Map

**Baru dibuat:**
- `database/migrations/..._create_master_payment_methods_table.php`
- `database/migrations/..._create_master_karyawan_table.php`
- `database/migrations/..._create_master_production_rates_table.php`
- `database/migrations/..._create_jihans_tortilla_sessions_table.php`
- `database/migrations/..._create_jihans_tortilla_session_details_table.php`
- `database/migrations/..._add_payment_method_id_to_transaction_payments.php`
- `app/Models/PaymentMethod.php`
- `app/Models/Karyawan.php`
- `app/Models/ProductionRate.php`
- `app/Models/JihansTortillaSession.php`
- `app/Models/JihansTortillaSessionDetail.php`
- `app/Http/Controllers/Master/PaymentMethodController.php`
- `app/Http/Controllers/Master/KaryawanController.php`
- `app/Http/Controllers/Master/ProductionRateController.php`
- `app/Http/Controllers/Jihans/TortillaProductionController.php`
- `app/Exports/Jihans/TortillaRecapExport.php`
- `database/seeders/ProductionRateSeeder.php`
- `resources/views/master/payment-methods/index.blade.php`
- `resources/views/master/payment-methods/form.blade.php`
- `resources/views/master/karyawan/index.blade.php`
- `resources/views/master/karyawan/form.blade.php`
- `resources/views/master/production-rates/edit.blade.php`
- `resources/views/jihans/tortilla-productions/index.blade.php`
- `resources/views/jihans/tortilla-productions/form.blade.php`
- `resources/views/jihans/tortilla-productions/show.blade.php`
- `resources/views/jihans/tortilla-productions/recap.blade.php`
- `tests/Feature/Master/PaymentMethodTest.php`
- `tests/Feature/Master/KaryawanTest.php`
- `tests/Feature/Jihans/TortillaProductionTest.php`

**Dimodifikasi:**
- `routes/jihans.php` — tambah payment-methods, karyawan, production-rates, tortilla-productions
- `routes/hendhys.php` — tambah payment-methods
- `app/Http/Controllers/Jihans/PosController.php` — ganti validasi payment_method_id
- `app/Http/Controllers/Hendhys/PosController.php` — ganti validasi payment_method_id
- `database/seeders/DatabaseSeeder.php` — tambah ProductionRateSeeder

---

## Task 1: Migrations

**Files:**
- Create: 6 migration files

- [ ] **Step 1.1: Buat migration master_payment_methods**

```bash
php artisan make:migration create_master_payment_methods_table
```

Isi file yang dibuat:
```php
public function up(): void
{
    Schema::create('master_payment_methods', function (Blueprint $table) {
        $table->id();
        $table->enum('entity_scope', ['gudang', 'jihans', 'hendhys', 'all'])->default('jihans');
        $table->string('name', 100);
        $table->string('bank_name', 100)->nullable();
        $table->string('account_number', 50)->nullable();
        $table->string('account_name', 100)->nullable();
        $table->string('image', 255)->nullable();
        $table->boolean('is_active')->default(true);
        $table->softDeletes();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('master_payment_methods');
}
```

- [ ] **Step 1.2: Buat migration master_karyawan**

```bash
php artisan make:migration create_master_karyawan_table
```

```php
public function up(): void
{
    Schema::create('master_karyawan', function (Blueprint $table) {
        $table->id();
        $table->enum('entity_scope', ['gudang', 'jihans', 'hendhys', 'all'])->default('jihans');
        $table->string('name', 150);
        $table->string('phone', 20)->nullable();
        $table->text('address')->nullable();
        $table->boolean('is_active')->default(true);
        $table->softDeletes();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('master_karyawan');
}
```

- [ ] **Step 1.3: Buat migration master_production_rates**

```bash
php artisan make:migration create_master_production_rates_table
```

```php
public function up(): void
{
    Schema::create('master_production_rates', function (Blueprint $table) {
        $table->id();
        $table->enum('entity_scope', ['gudang', 'jihans', 'hendhys', 'all'])->default('jihans');
        $table->decimal('tb_rate', 15, 2)->default(0);
        $table->decimal('ts_rate', 15, 2)->default(0);
        $table->decimal('tk_rate', 15, 2)->default(0);
        $table->decimal('tc_rate', 15, 2)->default(0);
        $table->decimal('kribab_rate', 15, 2)->default(0);
        $table->text('notes')->nullable();
        $table->foreignId('updated_by')->nullable()->constrained('master_users');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('master_production_rates');
}
```

- [ ] **Step 1.4: Buat migration jihans_tortilla_sessions**

```bash
php artisan make:migration create_jihans_tortilla_sessions_table
```

```php
public function up(): void
{
    Schema::create('jihans_tortilla_sessions', function (Blueprint $table) {
        $table->id();
        $table->string('session_number', 30)->unique();
        $table->date('date');
        $table->text('notes')->nullable();
        $table->foreignId('created_by')->constrained('master_users');
        $table->timestamps();
        $table->index('date');
    });
}

public function down(): void
{
    Schema::dropIfExists('jihans_tortilla_sessions');
}
```

- [ ] **Step 1.5: Buat migration jihans_tortilla_session_details**

```bash
php artisan make:migration create_jihans_tortilla_session_details_table
```

```php
public function up(): void
{
    Schema::create('jihans_tortilla_session_details', function (Blueprint $table) {
        $table->id();
        $table->foreignId('session_id')->constrained('jihans_tortilla_sessions')->cascadeOnDelete();
        $table->foreignId('karyawan_id')->constrained('master_karyawan');
        $table->integer('tb_qty')->default(0);
        $table->integer('ts_qty')->default(0);
        $table->integer('tk_qty')->default(0);
        $table->integer('tc_qty')->default(0);
        $table->integer('kribab_qty')->default(0);
        $table->decimal('tb_rate', 15, 2)->default(0);
        $table->decimal('ts_rate', 15, 2)->default(0);
        $table->decimal('tk_rate', 15, 2)->default(0);
        $table->decimal('tc_rate', 15, 2)->default(0);
        $table->decimal('kribab_rate', 15, 2)->default(0);
        $table->decimal('total_amount', 15, 2)->default(0);
        $table->timestamps();
        $table->unique(['session_id', 'karyawan_id']);
    });
}

public function down(): void
{
    Schema::dropIfExists('jihans_tortilla_session_details');
}
```

- [ ] **Step 1.6: Buat migration add payment_method_id ke tabel payment**

```bash
php artisan make:migration add_payment_method_id_to_transaction_payments
```

```php
public function up(): void
{
    Schema::table('jihans_transaction_payments', function (Blueprint $table) {
        $table->foreignId('payment_method_id')->nullable()->after('transaction_id')
              ->constrained('master_payment_methods')->nullOnDelete();
        $table->enum('payment_method', ['cash', 'transfer'])->nullable()->change();
    });

    Schema::table('hendhys_transaction_payments', function (Blueprint $table) {
        $table->foreignId('payment_method_id')->nullable()->after('transaction_id')
              ->constrained('master_payment_methods')->nullOnDelete();
        $table->enum('payment_method', ['cash', 'transfer'])->nullable()->change();
    });
}

public function down(): void
{
    Schema::table('jihans_transaction_payments', function (Blueprint $table) {
        $table->dropForeign(['payment_method_id']);
        $table->dropColumn('payment_method_id');
        $table->enum('payment_method', ['cash', 'transfer'])->nullable(false)->change();
    });

    Schema::table('hendhys_transaction_payments', function (Blueprint $table) {
        $table->dropForeign(['payment_method_id']);
        $table->dropColumn('payment_method_id');
        $table->enum('payment_method', ['cash', 'transfer'])->nullable(false)->change();
    });
}
```

- [ ] **Step 1.7: Jalankan semua migrations**

```bash
php artisan migrate
```

Expected output: semua migration baru berhasil dijalankan (tidak ada error).

- [ ] **Step 1.8: Commit**

```bash
git add database/migrations/
git commit -m "feat: add migrations for payment methods, karyawan, production rates & tortilla sessions"
```

---

## Task 2: Models

**Files:**
- Create: `app/Models/PaymentMethod.php`, `app/Models/Karyawan.php`, `app/Models/ProductionRate.php`, `app/Models/JihansTortillaSession.php`, `app/Models/JihansTortillaSessionDetail.php`

- [ ] **Step 2.1: Buat PaymentMethod model**

`app/Models/PaymentMethod.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use SoftDeletes;

    protected $table = 'master_payment_methods';

    protected $fillable = [
        'entity_scope', 'name', 'bank_name', 'account_number',
        'account_name', 'image', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
```

- [ ] **Step 2.2: Buat Karyawan model**

`app/Models/Karyawan.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Karyawan extends Model
{
    use SoftDeletes;

    protected $table = 'master_karyawan';

    protected $fillable = [
        'entity_scope', 'name', 'phone', 'address', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function tortillaDetails(): HasMany
    {
        return $this->hasMany(JihansTortillaSessionDetail::class, 'karyawan_id');
    }
}
```

- [ ] **Step 2.3: Buat ProductionRate model**

`app/Models/ProductionRate.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionRate extends Model
{
    protected $table = 'master_production_rates';

    protected $fillable = [
        'entity_scope', 'tb_rate', 'ts_rate', 'tk_rate',
        'tc_rate', 'kribab_rate', 'notes', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tb_rate'    => 'decimal:2',
            'ts_rate'    => 'decimal:2',
            'tk_rate'    => 'decimal:2',
            'tc_rate'    => 'decimal:2',
            'kribab_rate'=> 'decimal:2',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
```

- [ ] **Step 2.4: Buat JihansTortillaSession model**

`app/Models/JihansTortillaSession.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JihansTortillaSession extends Model
{
    protected $table = 'jihans_tortilla_sessions';

    protected $fillable = ['session_number', 'date', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function details(): HasMany
    {
        return $this->hasMany(JihansTortillaSessionDetail::class, 'session_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

- [ ] **Step 2.5: Buat JihansTortillaSessionDetail model**

`app/Models/JihansTortillaSessionDetail.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JihansTortillaSessionDetail extends Model
{
    protected $table = 'jihans_tortilla_session_details';

    protected $fillable = [
        'session_id', 'karyawan_id',
        'tb_qty', 'ts_qty', 'tk_qty', 'tc_qty', 'kribab_qty',
        'tb_rate', 'ts_rate', 'tk_rate', 'tc_rate', 'kribab_rate',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'tb_qty'      => 'integer',
            'ts_qty'      => 'integer',
            'tk_qty'      => 'integer',
            'tc_qty'      => 'integer',
            'kribab_qty'  => 'integer',
            'tb_rate'     => 'decimal:2',
            'ts_rate'     => 'decimal:2',
            'tk_rate'     => 'decimal:2',
            'tc_rate'     => 'decimal:2',
            'kribab_rate' => 'decimal:2',
            'total_amount'=> 'decimal:2',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(JihansTortillaSession::class, 'session_id');
    }
}
```

- [ ] **Step 2.6: Commit**

```bash
git add app/Models/
git commit -m "feat: add PaymentMethod, Karyawan, ProductionRate, JihansTortillaSession models"
```

---

## Task 3: Seeder ProductionRate

**Files:**
- Create: `database/seeders/ProductionRateSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 3.1: Buat seeder**

`database/seeders/ProductionRateSeeder.php`:
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionRateSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('master_production_rates')->updateOrInsert(
            ['entity_scope' => 'jihans'],
            [
                'tb_rate'     => 0,
                'ts_rate'     => 0,
                'tk_rate'     => 0,
                'tc_rate'     => 0,
                'kribab_rate' => 0,
                'notes'       => 'Default rate — harap diisi oleh admin.',
                'updated_by'  => null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );
    }
}
```

- [ ] **Step 3.2: Register di DatabaseSeeder**

Edit `database/seeders/DatabaseSeeder.php`, tambah setelah `UnitSeeder::class`:
```php
$this->call([
    RolePermissionSeeder::class,
    BranchSeeder::class,
    UnitSeeder::class,
    UserSeeder::class,
    ProductionRateSeeder::class,
]);
```

- [ ] **Step 3.3: Jalankan seeder**

```bash
php artisan db:seed --class=ProductionRateSeeder
```

Expected: 1 row inserted di `master_production_rates`.

- [ ] **Step 3.4: Commit**

```bash
git add database/seeders/
git commit -m "feat: add ProductionRateSeeder with default jihans rates"
```

---

## Task 4: Master PaymentMethod — Controller & Routes

**Files:**
- Create: `app/Http/Controllers/Master/PaymentMethodController.php`
- Modify: `routes/jihans.php`, `routes/hendhys.php`

- [ ] **Step 4.1: Buat PaymentMethodController**

`app/Http/Controllers/Master/PaymentMethodController.php`:
```php
<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentMethodController extends Controller
{
    use ScopesMasterData;

    public function __construct(private ActivityLogService $logger) {}

    public function index(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $methods = PaymentMethod::whereIn('entity_scope', [$info['scope'], 'all'])
            ->orderBy('name')
            ->get();

        return view('master.payment-methods.index', [
            'methods'      => $methods,
            'layout'       => $info['layout'],
            'routePrefix'  => $info['route'],
            'currentScope' => $info['scope'],
        ]);
    }

    public function create(Request $request)
    {
        $info = $this->getScopeInfo($request);
        return view('master.payment-methods.form', [
            'layout'       => $info['layout'],
            'routePrefix'  => $info['route'],
            'currentScope' => $info['scope'],
        ]);
    }

    public function store(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'bank_name'      => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'account_name'   => 'nullable|string|max:100',
            'image'          => 'nullable|image|max:2048',
            'is_active'      => 'boolean',
            'entity_scope'   => 'nullable|in:gudang,jihans,hendhys,all',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('payment-methods', 'public');
        }

        $data['entity_scope'] = $request->input('entity_scope', $info['scope']);
        $data['is_active']    = $request->boolean('is_active', true);

        $method = PaymentMethod::create($data);
        $this->logger->log('create', 'master.payment_method', "Tambah metode: {$method->name}", $method);

        return redirect()->route($info['route'] . 'payment-methods.index')
            ->with('success', "Metode pembayaran {$method->name} berhasil ditambahkan.");
    }

    public function edit(Request $request, PaymentMethod $paymentMethod)
    {
        $info = $this->getScopeInfo($request);
        return view('master.payment-methods.form', [
            'method'       => $paymentMethod,
            'layout'       => $info['layout'],
            'routePrefix'  => $info['route'],
            'currentScope' => $info['scope'],
        ]);
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $info = $this->getScopeInfo($request);
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'bank_name'      => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'account_name'   => 'nullable|string|max:100',
            'image'          => 'nullable|image|max:2048',
            'is_active'      => 'boolean',
            'entity_scope'   => 'nullable|in:gudang,jihans,hendhys,all',
        ]);

        if ($request->hasFile('image')) {
            if ($paymentMethod->image) {
                Storage::disk('public')->delete($paymentMethod->image);
            }
            $data['image'] = $request->file('image')->store('payment-methods', 'public');
        }

        $old = $paymentMethod->toArray();
        $data['is_active']    = $request->boolean('is_active', true);
        $data['entity_scope'] = $request->input('entity_scope', $paymentMethod->entity_scope);
        $paymentMethod->update($data);

        $this->logger->log('update', 'master.payment_method', "Update metode: {$paymentMethod->name}", $paymentMethod, $old, $paymentMethod->fresh()->toArray());

        return redirect()->route($info['route'] . 'payment-methods.index')
            ->with('success', "Metode {$paymentMethod->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, PaymentMethod $paymentMethod)
    {
        $info = $this->getScopeInfo($request);
        if ($paymentMethod->image) {
            Storage::disk('public')->delete($paymentMethod->image);
        }
        $name = $paymentMethod->name;
        $paymentMethod->delete();
        $this->logger->log('delete', 'master.payment_method', "Hapus metode: $name");

        return redirect()->route($info['route'] . 'payment-methods.index')
            ->with('success', "Metode $name berhasil dihapus.");
    }
}
```

- [ ] **Step 4.2: Tambah routes di routes/jihans.php**

Di dalam blok `Route::prefix('master')->name('master.')`, tambah setelah blok `brands`:
```php
Route::resource('payment-methods', \App\Http\Controllers\Master\PaymentMethodController::class)->except(['show']);
Route::resource('karyawan', \App\Http\Controllers\Master\KaryawanController::class)->except(['show']);
Route::get('production-rates', [\App\Http\Controllers\Master\ProductionRateController::class, 'edit'])->name('production-rates.edit');
Route::put('production-rates', [\App\Http\Controllers\Master\ProductionRateController::class, 'update'])->name('production-rates.update');
```

- [ ] **Step 4.3: Tambah routes di routes/hendhys.php**

Di dalam blok `Route::prefix('master')->name('master.')`, tambah setelah blok `brands`:
```php
Route::resource('payment-methods', \App\Http\Controllers\Master\PaymentMethodController::class)->except(['show']);
```

- [ ] **Step 4.4: Commit**

```bash
git add app/Http/Controllers/Master/PaymentMethodController.php routes/jihans.php routes/hendhys.php
git commit -m "feat: add PaymentMethodController with CRUD and scoped routes"
```

---

## Task 5: Master PaymentMethod — Views

**Files:**
- Create: `resources/views/master/payment-methods/index.blade.php`
- Create: `resources/views/master/payment-methods/form.blade.php`

- [ ] **Step 5.1: Buat index view**

`resources/views/master/payment-methods/index.blade.php`:
```blade
@extends($layout ?? 'layouts.gudang')
@section('title', 'Metode Pembayaran')
@section('page-title', 'Master Data — Metode Pembayaran')

@section('content')
<div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full bg-surface">

    @if(session('success'))
        <div class="mb-md bg-primary-container text-on-primary-container p-sm rounded-lg border border-primary/20 flex items-center gap-sm">
            <span class="material-symbols-outlined text-primary">check_circle</span>
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-lg gap-md">
        <div>
            <h2 class="font-headline-md text-headline-md text-on-background">Metode Pembayaran</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-xs">{{ $methods->count() }} metode terdaftar</p>
        </div>
        <a href="{{ route(($routePrefix ?? 'master.') . 'payment-methods.create') }}"
            class="inline-flex items-center gap-sm px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-all self-start sm:self-auto">
            <span class="material-symbols-outlined text-[18px]">add</span>
            Tambah Metode
        </a>
    </div>

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant">
                        <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant">Nama</th>
                        <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant">Bank</th>
                        <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant">No. Rekening</th>
                        <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant">Gambar</th>
                        <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant">Status</th>
                        <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($methods as $method)
                    <tr class="border-b border-surface-container hover:bg-surface-container-lowest/80 transition-colors">
                        <td class="px-md py-sm font-label-lg font-bold text-on-surface">{{ $method->name }}</td>
                        <td class="px-md py-sm font-body-md text-on-surface-variant">{{ $method->bank_name ?? '-' }}</td>
                        <td class="px-md py-sm font-body-md text-on-surface-variant">{{ $method->account_number ?? '-' }}</td>
                        <td class="px-md py-sm">
                            @if($method->image)
                                <img src="{{ Storage::url($method->image) }}" alt="{{ $method->name }}" class="h-10 w-16 object-contain rounded border border-outline-variant">
                            @else
                                <span class="text-on-surface-variant font-body-sm">-</span>
                            @endif
                        </td>
                        <td class="px-md py-sm">
                            <span class="inline-flex items-center px-sm py-xs rounded-full font-label-sm text-label-sm {{ $method->is_active ? 'bg-tertiary-container text-on-tertiary-container' : 'bg-surface-container text-on-surface-variant' }}">
                                {{ $method->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-md py-sm text-right">
                            <div class="flex items-center justify-end gap-sm">
                                <a href="{{ route(($routePrefix ?? 'master.') . 'payment-methods.edit', $method) }}"
                                    class="inline-flex items-center gap-xs px-sm py-xs bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-sm hover:bg-surface-container-high transition-colors shadow-sm">
                                    <span class="material-symbols-outlined text-[14px]">edit</span>Edit
                                </a>
                                <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'payment-methods.destroy', $method) }}"
                                    onsubmit="return confirm('Hapus {{ $method->name }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center gap-xs px-sm py-xs bg-surface-container border border-outline-variant text-error rounded-lg font-label-sm hover:bg-error-container transition-colors shadow-sm">
                                        <span class="material-symbols-outlined text-[14px]">delete</span>Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-md py-lg text-center text-on-surface-variant font-body-md">Belum ada metode pembayaran.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
```

- [ ] **Step 5.2: Buat form view**

`resources/views/master/payment-methods/form.blade.php`:
```blade
@extends($layout ?? 'layouts.gudang')
@section('title', isset($method) ? 'Edit Metode Pembayaran' : 'Tambah Metode Pembayaran')
@section('page-title', 'Master Data — ' . (isset($method) ? 'Edit' : 'Tambah') . ' Metode Pembayaran')

@section('content')
<div class="p-margin-mobile md:p-margin-desktop w-full bg-surface">

    @if($errors->any())
    <div class="mb-md bg-error-container text-on-error-container p-sm rounded-lg border border-error/20">
        <div class="flex items-start gap-sm">
            <span class="material-symbols-outlined text-error mt-[2px]">error</span>
            <ul class="list-disc pl-md text-sm space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    </div>
    @endif

    <form method="POST"
        action="{{ isset($method) ? route(($routePrefix ?? 'master.') . 'payment-methods.update', $method) : route(($routePrefix ?? 'master.') . 'payment-methods.store') }}"
        enctype="multipart/form-data"
        class="space-y-lg">
        @csrf
        @if(isset($method)) @method('PUT') @endif

        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm">
            <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant rounded-t-xl">
                <h3 class="font-label-lg font-semibold text-on-surface-variant uppercase tracking-wider">Informasi Metode Pembayaran</h3>
            </div>
            <div class="p-md grid grid-cols-1 md:grid-cols-2 gap-md">

                <div class="md:col-span-2">
                    <label class="block font-label-sm text-on-surface-variant mb-xs">Nama Metode <span class="text-error">*</span></label>
                    <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                        <input type="text" name="name" value="{{ old('name', $method->name ?? '') }}" required
                            placeholder="cth: BCA Transfer, QRIS Jihan's, Tunai"
                            class="bg-transparent border-none focus:ring-0 w-full font-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                    </div>
                    @error('name')<p class="text-error font-label-sm mt-xs">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-xs">Nama Bank</label>
                    <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                        <input type="text" name="bank_name" value="{{ old('bank_name', $method->bank_name ?? '') }}"
                            placeholder="cth: BCA, Mandiri, BRI (kosongkan untuk Tunai)"
                            class="bg-transparent border-none focus:ring-0 w-full font-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                    </div>
                </div>

                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-xs">Nomor Rekening</label>
                    <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                        <input type="text" name="account_number" value="{{ old('account_number', $method->account_number ?? '') }}"
                            placeholder="cth: 1234567890"
                            class="bg-transparent border-none focus:ring-0 w-full font-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                    </div>
                </div>

                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-xs">Atas Nama</label>
                    <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                        <input type="text" name="account_name" value="{{ old('account_name', $method->account_name ?? '') }}"
                            placeholder="cth: Jihan Santoso"
                            class="bg-transparent border-none focus:ring-0 w-full font-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                    </div>
                </div>

                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-xs">Gambar QR / Logo Bank <span class="text-on-surface-variant/60 font-label-sm">(opsional, max 2MB)</span></label>
                    @if(isset($method) && $method->image)
                        <div class="mb-xs">
                            <img src="{{ Storage::url($method->image) }}" class="h-16 object-contain border border-outline-variant rounded-lg p-xs" alt="Gambar saat ini">
                        </div>
                    @endif
                    <input type="file" name="image" accept="image/*"
                        class="w-full text-sm text-on-surface-variant file:mr-sm file:py-xs file:px-sm file:rounded-lg file:border-0 file:text-sm file:bg-primary-container file:text-on-primary-container hover:file:bg-primary hover:file:text-on-primary transition-colors">
                    @error('image')<p class="text-error font-label-sm mt-xs">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-sm">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                        {{ old('is_active', $method->is_active ?? true) ? 'checked' : '' }}
                        class="w-4 h-4 accent-primary">
                    <label for="is_active" class="font-label-md text-on-surface cursor-pointer">Aktif</label>
                </div>

            </div>
        </div>

        <div class="flex items-center justify-end gap-md pb-lg">
            <a href="{{ route(($routePrefix ?? 'master.') . 'payment-methods.index') }}"
                class="inline-flex items-center gap-sm px-md py-sm bg-surface-container border border-outline-variant text-on-surface-variant rounded-lg font-label-lg hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>Batal
            </a>
            <button type="submit"
                class="inline-flex items-center gap-sm px-lg py-sm bg-primary text-on-primary rounded-lg font-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-colors">
                <span class="material-symbols-outlined text-[18px]">save</span>
                {{ isset($method) ? 'Perbarui' : 'Simpan' }}
            </button>
        </div>
    </form>
</div>
@endsection
```

- [ ] **Step 5.3: Commit**

```bash
git add resources/views/master/payment-methods/
git commit -m "feat: add payment methods index and form views"
```

---

## Task 6: Master Karyawan — Controller, Routes & Views

**Files:**
- Create: `app/Http/Controllers/Master/KaryawanController.php`
- Create: `resources/views/master/karyawan/index.blade.php`
- Create: `resources/views/master/karyawan/form.blade.php`

- [ ] **Step 6.1: Buat KaryawanController**

`app/Http/Controllers/Master/KaryawanController.php`:
```php
<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class KaryawanController extends Controller
{
    use ScopesMasterData;

    public function __construct(private ActivityLogService $logger) {}

    public function index(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $q = Karyawan::whereIn('entity_scope', [$info['scope'], 'all']);

        if ($search = $request->search) {
            $q->where('name', 'like', "%$search%");
        }

        if ($request->status !== null && $request->status !== '') {
            $q->where('is_active', $request->status);
        }

        $karyawans = $q->orderBy('name')->paginate(20)->withQueryString();

        return view('master.karyawan.index', [
            'karyawans'    => $karyawans,
            'layout'       => $info['layout'],
            'routePrefix'  => $info['route'],
            'currentScope' => $info['scope'],
        ]);
    }

    public function create(Request $request)
    {
        $info = $this->getScopeInfo($request);
        return view('master.karyawan.form', [
            'layout'       => $info['layout'],
            'routePrefix'  => $info['route'],
            'currentScope' => $info['scope'],
        ]);
    }

    public function store(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $data = $request->validate([
            'name'         => 'required|string|max:150',
            'phone'        => 'nullable|string|max:20',
            'address'      => 'nullable|string',
            'is_active'    => 'boolean',
            'entity_scope' => 'nullable|in:gudang,jihans,hendhys,all',
        ]);

        $data['entity_scope'] = $request->input('entity_scope', $info['scope']);
        $data['is_active']    = $request->boolean('is_active', true);

        $karyawan = Karyawan::create($data);
        $this->logger->log('create', 'master.karyawan', "Tambah karyawan: {$karyawan->name}", $karyawan);

        return redirect()->route($info['route'] . 'karyawan.index')
            ->with('success', "Karyawan {$karyawan->name} berhasil ditambahkan.");
    }

    public function edit(Request $request, Karyawan $karyawan)
    {
        $info = $this->getScopeInfo($request);
        return view('master.karyawan.form', [
            'karyawan'     => $karyawan,
            'layout'       => $info['layout'],
            'routePrefix'  => $info['route'],
            'currentScope' => $info['scope'],
        ]);
    }

    public function update(Request $request, Karyawan $karyawan)
    {
        $info = $this->getScopeInfo($request);
        $data = $request->validate([
            'name'         => 'required|string|max:150',
            'phone'        => 'nullable|string|max:20',
            'address'      => 'nullable|string',
            'is_active'    => 'boolean',
            'entity_scope' => 'nullable|in:gudang,jihans,hendhys,all',
        ]);

        $old = $karyawan->toArray();
        $data['is_active']    = $request->boolean('is_active', true);
        $data['entity_scope'] = $request->input('entity_scope', $karyawan->entity_scope);
        $karyawan->update($data);

        $this->logger->log('update', 'master.karyawan', "Update karyawan: {$karyawan->name}", $karyawan, $old, $karyawan->fresh()->toArray());

        return redirect()->route($info['route'] . 'karyawan.index')
            ->with('success', "Karyawan {$karyawan->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, Karyawan $karyawan)
    {
        $info = $this->getScopeInfo($request);
        $name = $karyawan->name;
        $karyawan->delete();
        $this->logger->log('delete', 'master.karyawan', "Hapus karyawan: $name");

        return redirect()->route($info['route'] . 'karyawan.index')
            ->with('success', "Karyawan $name berhasil dihapus.");
    }
}
```

- [ ] **Step 6.2: Buat karyawan index view**

`resources/views/master/karyawan/index.blade.php`:
```blade
@extends($layout ?? 'layouts.jihans')
@section('title', 'Karyawan')
@section('page-title', 'Master Data — Karyawan')

@section('content')
<div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full bg-surface">

    @if(session('success'))
    <div class="mb-md bg-primary-container text-on-primary-container p-sm rounded-lg border border-primary/20 flex items-center gap-sm">
        <span class="material-symbols-outlined text-primary">check_circle</span>{{ session('success') }}
    </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-lg gap-md">
        <div>
            <h2 class="font-headline-md text-headline-md text-on-background">Karyawan</h2>
            <p class="font-body-md text-on-surface-variant mt-xs">{{ $karyawans->total() }} karyawan terdaftar</p>
        </div>
        <a href="{{ route(($routePrefix ?? 'master.') . 'karyawan.create') }}"
            class="inline-flex items-center gap-sm px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-all self-start sm:self-auto">
            <span class="material-symbols-outlined text-[18px]">add</span>Tambah Karyawan
        </a>
    </div>

    {{-- Filter --}}
    <form method="GET" class="flex flex-wrap gap-sm mb-md">
        <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama karyawan..."
                class="bg-transparent border-none focus:ring-0 w-full font-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
        </div>
        <select name="status" class="border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface font-body-md py-sm px-sm focus:ring-0 focus:border-primary outline-none">
            <option value="">Semua Status</option>
            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Nonaktif</option>
        </select>
        <button type="submit" class="px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg">Cari</button>
    </form>

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant">
                        <th class="px-md py-sm font-label-lg text-on-surface-variant">Nama</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant">Telepon</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant">Status</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($karyawans as $k)
                    <tr class="border-b border-surface-container hover:bg-surface-container-lowest/80 transition-colors">
                        <td class="px-md py-sm font-label-lg font-bold text-on-surface">{{ $k->name }}</td>
                        <td class="px-md py-sm font-body-md text-on-surface-variant">{{ $k->phone ?? '-' }}</td>
                        <td class="px-md py-sm">
                            <span class="inline-flex items-center px-sm py-xs rounded-full font-label-sm {{ $k->is_active ? 'bg-tertiary-container text-on-tertiary-container' : 'bg-surface-container text-on-surface-variant' }}">
                                {{ $k->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-md py-sm text-right">
                            <div class="flex items-center justify-end gap-sm">
                                <a href="{{ route(($routePrefix ?? 'master.') . 'karyawan.edit', $k) }}"
                                    class="inline-flex items-center gap-xs px-sm py-xs bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-sm hover:bg-surface-container-high transition-colors shadow-sm">
                                    <span class="material-symbols-outlined text-[14px]">edit</span>Edit
                                </a>
                                <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'karyawan.destroy', $k) }}"
                                    onsubmit="return confirm('Hapus {{ $k->name }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center gap-xs px-sm py-xs bg-surface-container border border-outline-variant text-error rounded-lg font-label-sm hover:bg-error-container transition-colors shadow-sm">
                                        <span class="material-symbols-outlined text-[14px]">delete</span>Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-md py-lg text-center text-on-surface-variant font-body-md">Belum ada karyawan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($karyawans->hasPages())
        <div class="px-md py-sm border-t border-outline-variant">{{ $karyawans->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
```

- [ ] **Step 6.3: Buat karyawan form view**

`resources/views/master/karyawan/form.blade.php`:
```blade
@extends($layout ?? 'layouts.jihans')
@section('title', isset($karyawan) ? 'Edit Karyawan' : 'Tambah Karyawan')
@section('page-title', 'Master Data — ' . (isset($karyawan) ? 'Edit' : 'Tambah') . ' Karyawan')

@section('content')
<div class="p-margin-mobile md:p-margin-desktop w-full bg-surface">

    @if($errors->any())
    <div class="mb-md bg-error-container text-on-error-container p-sm rounded-lg border border-error/20">
        <div class="flex items-start gap-sm">
            <span class="material-symbols-outlined text-error mt-[2px]">error</span>
            <ul class="list-disc pl-md text-sm space-y-1">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    </div>
    @endif

    <form method="POST"
        action="{{ isset($karyawan) ? route(($routePrefix ?? 'master.') . 'karyawan.update', $karyawan) : route(($routePrefix ?? 'master.') . 'karyawan.store') }}"
        class="space-y-lg">
        @csrf
        @if(isset($karyawan)) @method('PUT') @endif

        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm">
            <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant rounded-t-xl">
                <h3 class="font-label-lg font-semibold text-on-surface-variant uppercase tracking-wider">Data Karyawan</h3>
            </div>
            <div class="p-md grid grid-cols-1 md:grid-cols-2 gap-md">

                <div class="md:col-span-2">
                    <label class="block font-label-sm text-on-surface-variant mb-xs">Nama Karyawan <span class="text-error">*</span></label>
                    <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                        <input type="text" name="name" value="{{ old('name', $karyawan->name ?? '') }}" required
                            class="bg-transparent border-none focus:ring-0 w-full font-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                    </div>
                    @error('name')<p class="text-error font-label-sm mt-xs">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-xs">Telepon</label>
                    <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                        <input type="text" name="phone" value="{{ old('phone', $karyawan->phone ?? '') }}"
                            class="bg-transparent border-none focus:ring-0 w-full font-body-md text-on-surface py-sm px-sm outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-sm mt-auto pb-sm">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                        {{ old('is_active', $karyawan->is_active ?? true) ? 'checked' : '' }}
                        class="w-4 h-4 accent-primary">
                    <label for="is_active" class="font-label-md text-on-surface cursor-pointer">Aktif</label>
                </div>

                <div class="md:col-span-2">
                    <label class="block font-label-sm text-on-surface-variant mb-xs">Alamat</label>
                    <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                        <textarea name="address" rows="3"
                            class="bg-transparent border-none focus:ring-0 w-full font-body-md text-on-surface py-sm px-sm outline-none resize-none">{{ old('address', $karyawan->address ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-md pb-lg">
            <a href="{{ route(($routePrefix ?? 'master.') . 'karyawan.index') }}"
                class="inline-flex items-center gap-sm px-md py-sm bg-surface-container border border-outline-variant text-on-surface-variant rounded-lg font-label-lg hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>Batal
            </a>
            <button type="submit"
                class="inline-flex items-center gap-sm px-lg py-sm bg-primary text-on-primary rounded-lg font-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-colors">
                <span class="material-symbols-outlined text-[18px]">save</span>
                {{ isset($karyawan) ? 'Perbarui' : 'Simpan' }}
            </button>
        </div>
    </form>
</div>
@endsection
```

- [ ] **Step 6.4: Commit**

```bash
git add app/Http/Controllers/Master/KaryawanController.php resources/views/master/karyawan/
git commit -m "feat: add KaryawanController and CRUD views"
```

---

## Task 7: Master Tarif Produksi

**Files:**
- Create: `app/Http/Controllers/Master/ProductionRateController.php`
- Create: `resources/views/master/production-rates/edit.blade.php`

- [ ] **Step 7.1: Buat ProductionRateController**

`app/Http/Controllers/Master/ProductionRateController.php`:
```php
<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ProductionRate;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class ProductionRateController extends Controller
{
    use ScopesMasterData;

    public function __construct(private ActivityLogService $logger) {}

    public function edit(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $rate = ProductionRate::where('entity_scope', $info['scope'])->first();

        return view('master.production-rates.edit', [
            'rate'         => $rate,
            'layout'       => $info['layout'],
            'routePrefix'  => $info['route'],
            'currentScope' => $info['scope'],
        ]);
    }

    public function update(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $data = $request->validate([
            'tb_rate'     => 'required|numeric|min:0',
            'ts_rate'     => 'required|numeric|min:0',
            'tk_rate'     => 'required|numeric|min:0',
            'tc_rate'     => 'required|numeric|min:0',
            'kribab_rate' => 'required|numeric|min:0',
            'notes'       => 'nullable|string',
        ]);

        $data['updated_by']   = auth()->id();
        $data['entity_scope'] = $info['scope'];

        ProductionRate::updateOrCreate(
            ['entity_scope' => $info['scope']],
            $data
        );

        $this->logger->log('update', 'master.production_rate', "Update tarif produksi {$info['scope']}");

        return redirect()->route($info['route'] . 'production-rates.edit')
            ->with('success', 'Tarif produksi berhasil diperbarui.');
    }
}
```

- [ ] **Step 7.2: Buat view edit tarif**

`resources/views/master/production-rates/edit.blade.php`:
```blade
@extends($layout ?? 'layouts.jihans')
@section('title', 'Tarif Produksi')
@section('page-title', 'Master Data — Tarif Produksi')

@section('content')
<div class="p-margin-mobile md:p-margin-desktop w-full bg-surface">

    @if(session('success'))
    <div class="mb-md bg-primary-container text-on-primary-container p-sm rounded-lg border border-primary/20 flex items-center gap-sm">
        <span class="material-symbols-outlined text-primary">check_circle</span>{{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-md bg-error-container text-on-error-container p-sm rounded-lg border border-error/20">
        <ul class="list-disc pl-md text-sm">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'production-rates.update') }}" class="space-y-lg">
        @csrf @method('PUT')

        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm">
            <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant rounded-t-xl flex items-center justify-between">
                <h3 class="font-label-lg font-semibold text-on-surface-variant uppercase tracking-wider">Tarif Upah per Satuan Produksi</h3>
                @if($rate)
                <span class="font-label-sm text-on-surface-variant">Terakhir diupdate: {{ $rate->updated_at->format('d M Y H:i') }} oleh {{ $rate->updatedBy->name ?? '-' }}</span>
                @endif
            </div>
            <div class="p-md grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-md">

                @foreach([
                    'tb_rate'     => 'TB (Tortilla Besar)',
                    'ts_rate'     => 'TS (Tortilla Sedang)',
                    'tk_rate'     => 'TK (Tortilla Kecil)',
                    'tc_rate'     => 'TC',
                    'kribab_rate' => 'KRIBAB',
                ] as $field => $label)
                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-xs">{{ $label }} <span class="text-on-surface-variant/60">(Rp/pcs)</span></label>
                    <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                        <input type="number" name="{{ $field }}" step="1" min="0"
                            value="{{ old($field, $rate?->$field ?? 0) }}" required
                            class="bg-transparent border-none focus:ring-0 w-full font-body-md text-on-surface py-sm px-sm outline-none">
                    </div>
                </div>
                @endforeach

            </div>
            <div class="px-md pb-md">
                <label class="block font-label-sm text-on-surface-variant mb-xs">Catatan</label>
                <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                    <input type="text" name="notes" value="{{ old('notes', $rate->notes ?? '') }}"
                        placeholder="Opsional"
                        class="bg-transparent border-none focus:ring-0 w-full font-body-md text-on-surface py-sm px-sm outline-none">
                </div>
            </div>
        </div>

        <div class="flex justify-end pb-lg">
            <button type="submit"
                class="inline-flex items-center gap-sm px-lg py-sm bg-primary text-on-primary rounded-lg font-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-colors">
                <span class="material-symbols-outlined text-[18px]">save</span>Simpan Tarif
            </button>
        </div>
    </form>
</div>
@endsection
```

- [ ] **Step 7.3: Commit**

```bash
git add app/Http/Controllers/Master/ProductionRateController.php resources/views/master/production-rates/
git commit -m "feat: add ProductionRateController and tarif edit view"
```

---

## Task 8: Tortilla Production — Controller & Routes

**Files:**
- Create: `app/Http/Controllers/Jihans/TortillaProductionController.php`
- Modify: `routes/jihans.php`

- [ ] **Step 8.1: Buat TortillaProductionController**

`app/Http/Controllers/Jihans/TortillaProductionController.php`:
```php
<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Models\JihansTortillaSession;
use App\Models\JihansTortillaSessionDetail;
use App\Models\Karyawan;
use App\Models\ProductionRate;
use App\Services\NumberGeneratorService;
use App\Exports\Jihans\TortillaRecapExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TortillaProductionController extends Controller
{
    public function __construct(private NumberGeneratorService $numbers) {}

    public function index(Request $request)
    {
        $q = JihansTortillaSession::with(['creator', 'details.karyawan']);

        if ($request->filled('date_from')) {
            $q->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('date', '<=', $request->date_to);
        }

        $sessions = $q->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(20)->withQueryString();

        return view('jihans.tortilla-productions.index', compact('sessions'));
    }

    public function create()
    {
        $karyawans = Karyawan::where('is_active', true)
            ->whereIn('entity_scope', ['jihans', 'all'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $rate = ProductionRate::where('entity_scope', 'jihans')->first()
            ?? new ProductionRate(['tb_rate'=>0,'ts_rate'=>0,'tk_rate'=>0,'tc_rate'=>0,'kribab_rate'=>0]);

        return view('jihans.tortilla-productions.form', compact('karyawans', 'rate'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date'                        => 'required|date',
            'notes'                       => 'nullable|string',
            'items'                       => 'required|array|min:1',
            'items.*.karyawan_id'         => 'required|exists:master_karyawan,id|distinct',
            'items.*.tb_qty'              => 'required|integer|min:0',
            'items.*.ts_qty'              => 'required|integer|min:0',
            'items.*.tk_qty'              => 'required|integer|min:0',
            'items.*.tc_qty'              => 'required|integer|min:0',
            'items.*.kribab_qty'          => 'required|integer|min:0',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $rate = ProductionRate::where('entity_scope', 'jihans')->first()
                    ?? new ProductionRate(['tb_rate'=>0,'ts_rate'=>0,'tk_rate'=>0,'tc_rate'=>0,'kribab_rate'=>0]);

                $session = JihansTortillaSession::create([
                    'session_number' => $this->numbers->generateYearly('JTS', 'jihans_tortilla_sessions', 'session_number'),
                    'date'           => $request->date,
                    'notes'          => $request->notes,
                    'created_by'     => auth()->id(),
                ]);

                foreach ($request->items as $item) {
                    $tb    = (int) $item['tb_qty'];
                    $ts    = (int) $item['ts_qty'];
                    $tk    = (int) $item['tk_qty'];
                    $tc    = (int) $item['tc_qty'];
                    $kribab= (int) $item['kribab_qty'];

                    $total = ($tb * $rate->tb_rate)
                           + ($ts * $rate->ts_rate)
                           + ($tk * $rate->tk_rate)
                           + ($tc * $rate->tc_rate)
                           + ($kribab * $rate->kribab_rate);

                    JihansTortillaSessionDetail::create([
                        'session_id'   => $session->id,
                        'karyawan_id'  => $item['karyawan_id'],
                        'tb_qty'       => $tb,
                        'ts_qty'       => $ts,
                        'tk_qty'       => $tk,
                        'tc_qty'       => $tc,
                        'kribab_qty'   => $kribab,
                        'tb_rate'      => $rate->tb_rate,
                        'ts_rate'      => $rate->ts_rate,
                        'tk_rate'      => $rate->tk_rate,
                        'tc_rate'      => $rate->tc_rate,
                        'kribab_rate'  => $rate->kribab_rate,
                        'total_amount' => $total,
                    ]);
                }
            });

            return redirect()->route('jihans.tortilla-productions.index')
                ->with('success', 'Sesi produksi tortilla berhasil disimpan.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function show(JihansTortillaSession $tortillaProduction)
    {
        $tortillaProduction->load(['creator', 'details.karyawan']);
        return view('jihans.tortilla-productions.show', ['session' => $tortillaProduction]);
    }

    public function recap(Request $request)
    {
        $from = $request->filled('date_from') ? $request->date_from : now()->startOfWeek()->toDateString();
        $to   = $request->filled('date_to')   ? $request->date_to   : now()->endOfWeek()->toDateString();

        $rows = JihansTortillaSessionDetail::query()
            ->join('jihans_tortilla_sessions', 'jihans_tortilla_session_details.session_id', '=', 'jihans_tortilla_sessions.id')
            ->whereDate('jihans_tortilla_sessions.date', '>=', $from)
            ->whereDate('jihans_tortilla_sessions.date', '<=', $to)
            ->selectRaw('
                jihans_tortilla_session_details.karyawan_id,
                COUNT(DISTINCT jihans_tortilla_session_details.session_id) as total_hadir,
                SUM(tb_qty) as tb, SUM(ts_qty) as ts,
                SUM(tk_qty) as tk, SUM(tc_qty) as tc,
                SUM(kribab_qty) as kribab,
                SUM(total_amount) as total_upah
            ')
            ->groupBy('jihans_tortilla_session_details.karyawan_id')
            ->with('karyawan')
            ->get();

        return view('jihans.tortilla-productions.recap', compact('rows', 'from', 'to'));
    }

    public function recapExport(Request $request)
    {
        $from = $request->date_from ?? now()->startOfWeek()->toDateString();
        $to   = $request->date_to   ?? now()->endOfWeek()->toDateString();

        return Excel::download(
            new TortillaRecapExport($from, $to),
            "rekap-tortilla-{$from}-sd-{$to}.xlsx"
        );
    }
}
```

- [ ] **Step 8.2: Tambah routes di routes/jihans.php**

Setelah blok `productions`, tambah:
```php
// Produksi Tortilla Karyawan
Route::get('/tortilla-productions/recap', [\App\Http\Controllers\Jihans\TortillaProductionController::class, 'recap'])->name('tortilla-productions.recap');
Route::get('/tortilla-productions/recap/export', [\App\Http\Controllers\Jihans\TortillaProductionController::class, 'recapExport'])->name('tortilla-productions.recap.export');
Route::resource('tortilla-productions', \App\Http\Controllers\Jihans\TortillaProductionController::class)->except(['edit', 'update', 'destroy']);
```

> **Penting:** Route `recap` dan `recap/export` HARUS dideklarasikan SEBELUM `Route::resource(...)` agar tidak bentrok dengan `{tortillaProduction}` parameter.

- [ ] **Step 8.3: Commit**

```bash
git add app/Http/Controllers/Jihans/TortillaProductionController.php routes/jihans.php
git commit -m "feat: add TortillaProductionController with store, show, recap, and export"
```

---

## Task 9: Tortilla Production — Views (form, index, show)

**Files:**
- Create: `resources/views/jihans/tortilla-productions/form.blade.php`
- Create: `resources/views/jihans/tortilla-productions/index.blade.php`
- Create: `resources/views/jihans/tortilla-productions/show.blade.php`

- [ ] **Step 9.1: Buat form view**

`resources/views/jihans/tortilla-productions/form.blade.php`:
```blade
@extends('layouts.jihans')
@section('title', 'Input Produksi Tortilla')
@section('page-title', 'Produksi Tortilla — Input Sesi')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
@endpush

@section('content')
<div class="p-margin-mobile md:p-margin-desktop w-full bg-surface" x-data="tortillaForm({{ $karyawans->toJson() }}, {{ json_encode(['tb'=>(float)$rate->tb_rate,'ts'=>(float)$rate->ts_rate,'tk'=>(float)$rate->tk_rate,'tc'=>(float)$rate->tc_rate,'kribab'=>(float)$rate->kribab_rate]) }})">

    @if($errors->any())
    <div class="mb-md bg-error-container text-on-error-container p-sm rounded-lg border border-error/20">
        <div class="flex items-start gap-sm">
            <span class="material-symbols-outlined text-error mt-[2px]">error</span>
            <ul class="list-disc pl-md text-sm space-y-1">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    </div>
    @endif

    <form action="{{ route('jihans.tortilla-productions.store') }}" method="POST" class="space-y-lg">
        @csrf

        {{-- Header --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm">
            <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant rounded-t-xl">
                <h3 class="font-label-lg font-semibold text-on-surface-variant uppercase tracking-wider">Informasi Sesi</h3>
            </div>
            <div class="p-md grid grid-cols-1 md:grid-cols-2 gap-md">
                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-xs">Tanggal <span class="text-error">*</span></label>
                    <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                        <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                            class="bg-transparent border-none focus:ring-0 w-full font-body-md text-on-surface py-sm px-sm outline-none">
                    </div>
                </div>
                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-xs">Catatan</label>
                    <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                        <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Opsional"
                            class="bg-transparent border-none focus:ring-0 w-full font-body-md text-on-surface py-sm px-sm outline-none">
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabel Karyawan --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm">
            <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant rounded-t-xl flex items-center justify-between">
                <h3 class="font-label-lg font-semibold text-on-surface-variant uppercase tracking-wider">Data Produksi per Karyawan</h3>
                <button type="button" @click="addRow"
                    class="text-sm bg-primary-container text-on-primary-container hover:bg-primary hover:text-on-primary px-3 py-1.5 rounded-lg font-medium transition-colors flex items-center gap-1 shadow-sm">
                    <span class="material-symbols-outlined text-[16px]">add</span>Tambah Karyawan
                </button>
            </div>
            <div class="overflow-x-auto p-md">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-on-surface-variant text-label-sm font-bold uppercase tracking-wider border-b border-outline-variant">
                            <th class="pb-3 pt-2 px-2 min-w-[180px]">Karyawan</th>
                            <th class="pb-3 pt-2 px-2 w-20 text-center">TB</th>
                            <th class="pb-3 pt-2 px-2 w-20 text-center">TS</th>
                            <th class="pb-3 pt-2 px-2 w-20 text-center">TK</th>
                            <th class="pb-3 pt-2 px-2 w-20 text-center">TC</th>
                            <th class="pb-3 pt-2 px-2 w-20 text-center">KRIBAB</th>
                            <th class="pb-3 pt-2 px-2 w-32 text-right">Total</th>
                            <th class="pb-3 pt-2 px-2 w-12 text-center">Hapus</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="item.id">
                            <tr class="border-b border-outline-variant/30 last:border-0">
                                <td class="py-2 px-2">
                                    <select :name="`items[${index}][karyawan_id]`"
                                        :id="`karyawan_select_${item.id}`"
                                        x-model="item.karyawan_id"
                                        class="w-full border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface font-body-sm py-sm px-sm focus:ring-0 focus:border-primary outline-none ts-karyawan" required>
                                        <option value="">-- Pilih Karyawan --</option>
                                        @foreach($karyawans as $k)
                                        <option value="{{ $k->id }}">{{ $k->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                @foreach(['tb','ts','tk','tc','kribab'] as $field)
                                <td class="py-2 px-2">
                                    <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                                        <input type="number" step="1" min="0"
                                            :name="`items[${index}][{{ $field }}_qty]`"
                                            x-model.number="item.{{ $field }}"
                                            @input="item.{{ $field }} = Math.floor(item.{{ $field }})"
                                            class="bg-transparent border-none focus:ring-0 w-full font-body-sm text-on-surface text-center py-sm px-xs outline-none">
                                    </div>
                                </td>
                                @endforeach
                                <td class="py-2 px-2 text-right font-label-md text-on-surface" x-text="'Rp ' + rowTotal(item).toLocaleString('id-ID')"></td>
                                <td class="py-2 px-2 text-center">
                                    <button type="button" @click="removeRow(index)" x-show="items.length > 1"
                                        class="text-error hover:bg-error-container p-1.5 rounded-lg transition-colors flex items-center justify-center mx-auto">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-outline-variant bg-surface-container-low">
                            <td colspan="6" class="px-2 py-sm font-label-lg font-bold text-on-surface">Grand Total Sesi</td>
                            <td class="px-2 py-sm text-right font-label-lg font-bold text-primary" x-text="'Rp ' + grandTotal.toLocaleString('id-ID')"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="flex items-center justify-end gap-md pb-lg">
            <a href="{{ route('jihans.tortilla-productions.index') }}"
                class="inline-flex items-center gap-sm px-md py-sm bg-surface-container border border-outline-variant text-on-surface-variant rounded-lg font-label-lg hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>Batal
            </a>
            <button type="submit"
                class="inline-flex items-center gap-sm px-lg py-sm bg-primary text-on-primary rounded-lg font-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-colors">
                <span class="material-symbols-outlined text-[18px]">save</span>Simpan Sesi
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('tortillaForm', (karyawanList, rates) => ({
        items: [{ id: Date.now(), karyawan_id: '', tb: 0, ts: 0, tk: 0, tc: 0, kribab: 0 }],
        tsInstances: {},

        rowTotal(item) {
            return (item.tb * rates.tb) + (item.ts * rates.ts) + (item.tk * rates.tk)
                 + (item.tc * rates.tc) + (item.kribab * rates.kribab);
        },

        get grandTotal() {
            return this.items.reduce((sum, item) => sum + this.rowTotal(item), 0);
        },

        addRow() {
            this.items.push({ id: Date.now(), karyawan_id: '', tb: 0, ts: 0, tk: 0, tc: 0, kribab: 0 });
            this.$nextTick(() => this.initTomSelect());
        },

        removeRow(index) {
            const id = this.items[index].id;
            if (this.tsInstances[id]) { this.tsInstances[id].destroy(); delete this.tsInstances[id]; }
            if (this.items.length > 1) this.items.splice(index, 1);
        },

        initTomSelect() {
            this.items.forEach(item => {
                if (this.tsInstances[item.id]) return;
                const el = document.getElementById(`karyawan_select_${item.id}`);
                if (!el) return;
                this.tsInstances[item.id] = new TomSelect(el, {
                    create: false,
                    placeholder: '-- Pilih Karyawan --',
                    onChange: (val) => { item.karyawan_id = val; }
                });
            });
        },

        init() { this.$nextTick(() => this.initTomSelect()); }
    }));
});
</script>
@endpush
@endsection
```

- [ ] **Step 9.2: Buat index view**

`resources/views/jihans/tortilla-productions/index.blade.php`:
```blade
@extends('layouts.jihans')
@section('title', 'Produksi Tortilla')
@section('page-title', 'Produksi Tortilla')

@section('content')
<div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full bg-surface">

    @if(session('success'))
    <div class="mb-md bg-primary-container text-on-primary-container p-sm rounded-lg border border-primary/20 flex items-center gap-sm">
        <span class="material-symbols-outlined text-primary">check_circle</span>{{ session('success') }}
    </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-lg gap-md">
        <div>
            <h2 class="font-headline-md text-headline-md text-on-background">Produksi Tortilla</h2>
            <p class="font-body-md text-on-surface-variant mt-xs">Riwayat sesi produksi per karyawan</p>
        </div>
        <div class="flex items-center gap-sm self-start sm:self-auto flex-wrap">
            <a href="{{ route('jihans.tortilla-productions.recap') }}"
                class="inline-flex items-center gap-sm px-md py-sm bg-secondary-container text-on-secondary-container rounded-lg font-label-lg hover:bg-secondary hover:text-on-secondary transition-all">
                <span class="material-symbols-outlined text-[18px]">summarize</span>Rekap Mingguan
            </a>
            <a href="{{ route('jihans.tortilla-productions.create') }}"
                class="inline-flex items-center gap-sm px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-all">
                <span class="material-symbols-outlined text-[18px]">add</span>Input Sesi
            </a>
        </div>
    </div>

    {{-- Filter tanggal --}}
    <form method="GET" class="flex flex-wrap gap-sm mb-md items-end">
        <div>
            <label class="block font-label-sm text-on-surface-variant mb-xs">Dari</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                class="border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface font-body-md py-sm px-sm focus:ring-0 focus:border-primary outline-none">
        </div>
        <div>
            <label class="block font-label-sm text-on-surface-variant mb-xs">Sampai</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                class="border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface font-body-md py-sm px-sm focus:ring-0 focus:border-primary outline-none">
        </div>
        <button type="submit" class="px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg">Filter</button>
    </form>

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant">
                        <th class="px-md py-sm font-label-lg text-on-surface-variant">No. Sesi</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant">Tanggal</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant text-center">Karyawan</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant text-right">Total Upah</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                    <tr class="border-b border-surface-container hover:bg-surface-container-lowest/80 transition-colors">
                        <td class="px-md py-sm font-label-md font-mono text-on-surface">{{ $session->session_number }}</td>
                        <td class="px-md py-sm font-body-md text-on-surface">{{ $session->date->format('d M Y') }}</td>
                        <td class="px-md py-sm text-center font-body-md text-on-surface-variant">{{ $session->details->count() }} orang</td>
                        <td class="px-md py-sm text-right font-label-md font-bold text-on-surface">
                            Rp {{ number_format($session->details->sum('total_amount'), 0, ',', '.') }}
                        </td>
                        <td class="px-md py-sm text-right">
                            <a href="{{ route('jihans.tortilla-productions.show', $session) }}"
                                class="inline-flex items-center gap-xs px-sm py-xs bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-sm hover:bg-surface-container-high transition-colors shadow-sm">
                                <span class="material-symbols-outlined text-[14px]">visibility</span>Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-md py-lg text-center text-on-surface-variant font-body-md">Belum ada sesi produksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sessions->hasPages())
        <div class="px-md py-sm border-t border-outline-variant">{{ $sessions->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
```

- [ ] **Step 9.3: Buat show view**

`resources/views/jihans/tortilla-productions/show.blade.php`:
```blade
@extends('layouts.jihans')
@section('title', 'Detail Sesi ' . $session->session_number)
@section('page-title', 'Detail Sesi Produksi Tortilla')

@section('content')
<div class="p-margin-mobile md:p-margin-desktop w-full bg-surface">
    <div class="mb-lg flex items-center gap-md">
        <a href="{{ route('jihans.tortilla-productions.index') }}"
            class="inline-flex items-center gap-xs px-sm py-xs bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-sm hover:bg-surface-container-high transition-colors">
            <span class="material-symbols-outlined text-[14px]">arrow_back</span>Kembali
        </a>
    </div>

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm mb-lg">
        <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant rounded-t-xl">
            <h3 class="font-label-lg font-semibold text-on-surface-variant uppercase tracking-wider">Informasi Sesi</h3>
        </div>
        <div class="p-md grid grid-cols-2 md:grid-cols-4 gap-md">
            <div><p class="font-label-sm text-on-surface-variant mb-xs">No. Sesi</p><p class="font-label-lg font-mono text-on-surface">{{ $session->session_number }}</p></div>
            <div><p class="font-label-sm text-on-surface-variant mb-xs">Tanggal</p><p class="font-label-lg text-on-surface">{{ $session->date->format('d M Y') }}</p></div>
            <div><p class="font-label-sm text-on-surface-variant mb-xs">Dibuat oleh</p><p class="font-label-lg text-on-surface">{{ $session->creator->name ?? '-' }}</p></div>
            <div><p class="font-label-sm text-on-surface-variant mb-xs">Catatan</p><p class="font-body-md text-on-surface-variant">{{ $session->notes ?: '-' }}</p></div>
        </div>
    </div>

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm">
        <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant rounded-t-xl">
            <h3 class="font-label-lg font-semibold text-on-surface-variant uppercase tracking-wider">Detail per Karyawan</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container border-b border-outline-variant">
                        <th class="px-md py-sm font-label-lg text-on-surface-variant">Karyawan</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant text-center">TB</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant text-center">TS</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant text-center">TK</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant text-center">TC</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant text-center">KRIBAB</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant text-right">Total Upah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($session->details as $detail)
                    <tr class="border-b border-surface-container hover:bg-surface-container-lowest/80 transition-colors">
                        <td class="px-md py-sm font-label-md font-bold text-on-surface">{{ $detail->karyawan->name ?? '-' }}</td>
                        <td class="px-md py-sm text-center font-body-md text-on-surface">{{ $detail->tb_qty }}</td>
                        <td class="px-md py-sm text-center font-body-md text-on-surface">{{ $detail->ts_qty }}</td>
                        <td class="px-md py-sm text-center font-body-md text-on-surface">{{ $detail->tk_qty }}</td>
                        <td class="px-md py-sm text-center font-body-md text-on-surface">{{ $detail->tc_qty }}</td>
                        <td class="px-md py-sm text-center font-body-md text-on-surface">{{ $detail->kribab_qty }}</td>
                        <td class="px-md py-sm text-right font-label-md font-bold text-primary">Rp {{ number_format($detail->total_amount, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-outline-variant bg-surface-container-low">
                        <td colspan="6" class="px-md py-sm font-label-lg font-bold text-on-surface">Total Sesi</td>
                        <td class="px-md py-sm text-right font-label-lg font-bold text-primary">Rp {{ number_format($session->details->sum('total_amount'), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
```

- [ ] **Step 9.4: Commit**

```bash
git add resources/views/jihans/tortilla-productions/
git commit -m "feat: add tortilla production form, index, and show views"
```

---

## Task 10: Tortilla Rekap & Export

**Files:**
- Create: `resources/views/jihans/tortilla-productions/recap.blade.php`
- Create: `app/Exports/Jihans/TortillaRecapExport.php`

- [ ] **Step 10.1: Buat recap view**

`resources/views/jihans/tortilla-productions/recap.blade.php`:
```blade
@extends('layouts.jihans')
@section('title', 'Rekap Mingguan Tortilla')
@section('page-title', 'Rekap Mingguan — Produksi Tortilla')

@section('content')
<div class="p-margin-mobile md:p-margin-desktop w-full bg-surface">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-lg gap-md">
        <div>
            <h2 class="font-headline-md text-headline-md text-on-background">Rekap Mingguan</h2>
            <p class="font-body-md text-on-surface-variant mt-xs">{{ \Carbon\Carbon::parse($from)->format('d M Y') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</p>
        </div>
        <a href="{{ route('jihans.tortilla-productions.recap.export', ['date_from' => $from, 'date_to' => $to]) }}"
            class="inline-flex items-center gap-sm px-md py-sm bg-tertiary-container text-on-tertiary-container rounded-lg font-label-lg hover:bg-tertiary hover:text-on-tertiary transition-all self-start sm:self-auto">
            <span class="material-symbols-outlined text-[18px]">download</span>Export Excel
        </a>
    </div>

    {{-- Filter --}}
    <form method="GET" class="flex flex-wrap gap-sm mb-md items-end">
        <div>
            <label class="block font-label-sm text-on-surface-variant mb-xs">Dari</label>
            <input type="date" name="date_from" value="{{ $from }}"
                class="border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface font-body-md py-sm px-sm focus:ring-0 outline-none">
        </div>
        <div>
            <label class="block font-label-sm text-on-surface-variant mb-xs">Sampai</label>
            <input type="date" name="date_to" value="{{ $to }}"
                class="border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface font-body-md py-sm px-sm focus:ring-0 outline-none">
        </div>
        <button type="submit" class="px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg">Tampilkan</button>
    </form>

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant">
                        <th class="px-md py-sm font-label-lg text-on-surface-variant">Karyawan</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant text-center">Hadir</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant text-center">TB</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant text-center">TS</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant text-center">TK</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant text-center">TC</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant text-center">KRIBAB</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant text-right">Total Upah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                    <tr class="border-b border-surface-container hover:bg-surface-container-lowest/80 transition-colors">
                        <td class="px-md py-sm font-label-md font-bold text-on-surface">{{ $row->karyawan->name ?? '-' }}</td>
                        <td class="px-md py-sm text-center font-body-md text-on-surface-variant">{{ $row->total_hadir }}x</td>
                        <td class="px-md py-sm text-center font-body-md text-on-surface">{{ number_format($row->tb) }}</td>
                        <td class="px-md py-sm text-center font-body-md text-on-surface">{{ number_format($row->ts) }}</td>
                        <td class="px-md py-sm text-center font-body-md text-on-surface">{{ number_format($row->tk) }}</td>
                        <td class="px-md py-sm text-center font-body-md text-on-surface">{{ number_format($row->tc) }}</td>
                        <td class="px-md py-sm text-center font-body-md text-on-surface">{{ number_format($row->kribab) }}</td>
                        <td class="px-md py-sm text-right font-label-md font-bold text-primary">Rp {{ number_format($row->total_upah, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-md py-lg text-center text-on-surface-variant font-body-md">Tidak ada data di rentang tanggal ini.</td></tr>
                    @endforelse
                </tbody>
                @if($rows->isNotEmpty())
                <tfoot>
                    <tr class="border-t-2 border-outline-variant bg-surface-container-low font-bold">
                        <td class="px-md py-sm font-label-lg text-on-surface">TOTAL</td>
                        <td class="px-md py-sm text-center font-label-md text-on-surface-variant">-</td>
                        <td class="px-md py-sm text-center font-label-md text-on-surface">{{ number_format($rows->sum('tb')) }}</td>
                        <td class="px-md py-sm text-center font-label-md text-on-surface">{{ number_format($rows->sum('ts')) }}</td>
                        <td class="px-md py-sm text-center font-label-md text-on-surface">{{ number_format($rows->sum('tk')) }}</td>
                        <td class="px-md py-sm text-center font-label-md text-on-surface">{{ number_format($rows->sum('tc')) }}</td>
                        <td class="px-md py-sm text-center font-label-md text-on-surface">{{ number_format($rows->sum('kribab')) }}</td>
                        <td class="px-md py-sm text-right font-label-lg font-bold text-primary">Rp {{ number_format($rows->sum('total_upah'), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
```

- [ ] **Step 10.2: Buat TortillaRecapExport**

`app/Exports/Jihans/TortillaRecapExport.php`:
```php
<?php

namespace App\Exports\Jihans;

use App\Models\JihansTortillaSessionDetail;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TortillaRecapExport implements FromArray, WithTitle, WithHeadings, WithEvents
{
    private array $data;

    public function __construct(private string $from, private string $to)
    {
        $rows = JihansTortillaSessionDetail::query()
            ->join('jihans_tortilla_sessions', 'jihans_tortilla_session_details.session_id', '=', 'jihans_tortilla_sessions.id')
            ->whereDate('jihans_tortilla_sessions.date', '>=', $from)
            ->whereDate('jihans_tortilla_sessions.date', '<=', $to)
            ->selectRaw('
                jihans_tortilla_session_details.karyawan_id,
                COUNT(DISTINCT jihans_tortilla_session_details.session_id) as total_hadir,
                SUM(tb_qty) as tb, SUM(ts_qty) as ts,
                SUM(tk_qty) as tk, SUM(tc_qty) as tc,
                SUM(kribab_qty) as kribab,
                SUM(total_amount) as total_upah
            ')
            ->groupBy('jihans_tortilla_session_details.karyawan_id')
            ->with('karyawan')
            ->get();

        $this->data = $rows->map(fn($r) => [
            $r->karyawan->name ?? '-',
            $r->total_hadir,
            (int) $r->tb,
            (int) $r->ts,
            (int) $r->tk,
            (int) $r->tc,
            (int) $r->kribab,
            (float) $r->total_upah,
        ])->toArray();
    }

    public function title(): string { return 'Rekap Produksi'; }

    public function headings(): array
    {
        return ['Karyawan', 'Hadir', 'TB', 'TS', 'TK', 'TC', 'KRIBAB', 'Total Upah (Rp)'];
    }

    public function array(): array { return $this->data; }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = count($this->data) + 1;

                $sheet->getStyle("A1:H1")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '6c2f00']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("A1:H{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle('thin');

                foreach (range('A', 'H') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $sheet->getStyle("B2:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("H2:H{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

                $sheet->setCellValue("A1", "Rekap Produksi Tortilla {$this->from} s/d {$this->to}");
                $sheet->mergeCells("A1:H1");
                $sheet->insertNewRowBefore(2, 1);
                $sheet->getStyle("A2:H2")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F5E6DC']],
                ]);
            }
        ];
    }
}
```

- [ ] **Step 10.3: Commit**

```bash
git add resources/views/jihans/tortilla-productions/recap.blade.php app/Exports/Jihans/
git commit -m "feat: add tortilla recap view and TortillaRecapExport"
```

---

## Task 11: POS Integration — Payment Methods

**Files:**
- Modify: `app/Http/Controllers/Jihans/PosController.php`
- Modify: `app/Http/Controllers/Hendhys/PosController.php`
- Modify: `resources/views/jihans/pos/index.blade.php` *(update dropdown)*
- Modify: `resources/views/hendhys/pos/index.blade.php` *(update dropdown)*

- [ ] **Step 11.1: Update Jihans PosController store method**

Di `app/Http/Controllers/Jihans/PosController.php`, ganti validasi `payment_method` dan bagian save payment:

Ganti baris validasi:
```php
'payment_method'    => 'required|in:cash,transfer',
```
Dengan:
```php
'payment_method_id' => 'required|exists:master_payment_methods,id',
```

Tambah import di atas class:
```php
use App\Models\PaymentMethod;
```

Cari dan ganti bagian create payment (biasanya di akhir transaction block), dari:
```php
$trx->payments()->create([
    'payment_method'   => $request->payment_method,
    'amount'           => $request->amount_paid,
    'bank_name'        => $request->bank_name,
    'reference_number' => $request->reference_number,
]);
```
Menjadi:
```php
$trx->payments()->create([
    'payment_method_id' => $request->payment_method_id,
    'payment_method'    => null,
    'amount'            => $request->amount_paid,
    'bank_name'         => $request->bank_name,
    'reference_number'  => $request->reference_number,
]);
```

Update method `index` untuk pass payment methods ke view. Tambah setelah query `$customers`:
```php
$paymentMethods = PaymentMethod::where('is_active', true)
    ->whereIn('entity_scope', ['jihans', 'all'])
    ->orderBy('name')
    ->get(['id', 'name', 'bank_name', 'image']);
```

Update `return view(...)`:
```php
return view('jihans.pos.index', compact('products', 'customers', 'customerTypes', 'paymentMethods'));
```

- [ ] **Step 11.2: Update Hendhys PosController store method**

Di `app/Http/Controllers/Hendhys/PosController.php`, lakukan perubahan yang sama:

Ganti validasi:
```php
'payment_method' => 'required|in:cash,transfer',
```
Dengan:
```php
'payment_method_id' => 'required|exists:master_payment_methods,id',
```

Tambah import:
```php
use App\Models\PaymentMethod;
```

Ganti bagian create HendhysTransactionPayment:
```php
HendhysTransactionPayment::create([
    'transaction_id'    => $transactionId,  // atau sesuai variable yang ada
    'payment_method_id' => $request->payment_method_id,
    'payment_method'    => null,
    'amount'            => $request->amount_paid,
    'bank_name'         => $request->bank_name,
    'reference_number'  => $request->reference_number,
]);
```

Tambah ke method `index` sebelum return view:
```php
$paymentMethods = PaymentMethod::where('is_active', true)
    ->whereIn('entity_scope', ['hendhys', 'all'])
    ->orderBy('name')
    ->get(['id', 'name', 'bank_name', 'image']);
```

Update `return view('hendhys.pos.index', ...)` tambah `$paymentMethods` di compact.

- [ ] **Step 11.3: Update POS views — dropdown payment**

Di view POS Jihans (`resources/views/jihans/pos/index.blade.php`), cari bagian dropdown `payment_method` (biasanya ada `<select name="payment_method">` atau input radio/select dengan value `cash`/`transfer`) dan ganti dengan:

```html
<select name="payment_method_id" id="payment_method_id" required
    class="w-full border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface font-body-md py-sm px-sm focus:ring-0 focus:border-primary outline-none">
    <option value="">-- Pilih Metode Pembayaran --</option>
    @foreach($paymentMethods as $pm)
    <option value="{{ $pm->id }}" data-has-image="{{ $pm->image ? '1' : '0' }}" data-image="{{ $pm->image ? Storage::url($pm->image) : '' }}">
        {{ $pm->name }}{{ $pm->bank_name ? ' (' . $pm->bank_name . ')' : '' }}
    </option>
    @endforeach
</select>
```

Lakukan hal yang sama di view POS Hendhys (`resources/views/hendhys/pos/index.blade.php`).

> **Catatan:** Sesuaikan dengan Alpine.js binding yang sudah ada di view tersebut. Jika ada `x-model="paymentMethod"` atau similar, sesuaikan nama variabel Alpine.

- [ ] **Step 11.4: Verifikasi tidak ada referensi lama**

```bash
grep -r "in:cash,transfer" app/Http/Controllers/
```

Expected: tidak ada hasil (semua sudah diganti).

- [ ] **Step 11.5: Commit**

```bash
git add app/Http/Controllers/Jihans/PosController.php app/Http/Controllers/Hendhys/PosController.php resources/views/jihans/pos/ resources/views/hendhys/pos/
git commit -m "feat: integrate master payment methods into POS (replace hardcoded cash/transfer enum)"
```

---

## Task 12: Verifikasi Akhir

- [ ] **Step 12.1: Jalankan semua migration ulang dari awal (opsional, di dev)**

```bash
php artisan migrate:fresh --seed
```

- [ ] **Step 12.2: Cek routes terdaftar**

```bash
php artisan route:list --name=jihans.master.payment-methods
php artisan route:list --name=jihans.master.karyawan
php artisan route:list --name=jihans.master.production-rates
php artisan route:list --name=jihans.tortilla-productions
php artisan route:list --name=hendhys.master.payment-methods
```

Expected: semua route terdaftar tanpa error.

- [ ] **Step 12.3: Clear cache**

```bash
php artisan config:clear && php artisan route:clear && php artisan view:clear
```

- [ ] **Step 12.4: Final commit**

```bash
git add -A
git commit -m "feat: complete payment methods, karyawan & tortilla production module"
```

---

## Ringkasan Urutan Implementasi

1. Task 1 — Migrations (semua sekaligus)
2. Task 2 — Models
3. Task 3 — Seeder
4. Task 4 — PaymentMethod Controller + Routes
5. Task 5 — PaymentMethod Views
6. Task 6 — Karyawan Controller + Views
7. Task 7 — Production Rate Controller + View
8. Task 8 — TortillaProduction Controller + Routes
9. Task 9 — Tortilla Views (form, index, show)
10. Task 10 — Recap + Export
11. Task 11 — POS Integration
12. Task 12 — Verifikasi
