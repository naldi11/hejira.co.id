# POS Customer Manual Input + Autocomplete Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the Hendhys POS customer dropdowns (Retail Umum / Guest) with free-text inputs for Nama Pelanggan and Nomor Telp, with autocomplete suggestions sourced from past transactions. Remove the Hendhys customer master data UI.

**Architecture:** Add `customer_phone` to both transaction tables via migrations, expose a search API endpoint on PosController, update the POS Alpine.js component to replace dropdown state with name/phone state + debounced autocomplete, propagate the new fields through the checkout → store flow and the hold → pending flow.

**Tech Stack:** Laravel 13, Alpine.js v3, Tailwind CSS (Material Design tokens), MySQL

---

## File Map

| File | Action | Purpose |
|---|---|---|
| `database/migrations/..._add_phone_to_hendhys_transactions.php` | Create | Add `customer_phone` column |
| `database/migrations/..._add_phone_to_hendhys_pending_transactions.php` | Create | Add `customer_phone` column |
| `app/Models/HendhysTransaction.php` | Modify | Add `customer_phone` to `$fillable` |
| `app/Models/HendhysPendingTransaction.php` | Modify | Add `customer_phone` to `$fillable` |
| `app/Http/Controllers/Hendhys/PosController.php` | Modify | Add `customerSearch()`, update `store()` |
| `app/Http/Controllers/Hendhys/PendingController.php` | Modify | Update `store()` to accept `customer_phone` |
| `routes/hendhys.php` | Modify | Add customer-search route, remove customers resource |
| `resources/views/layouts/hendhys.blade.php` | Modify | Remove "Pelanggan" from Master Data sidebar menu |
| `resources/views/hendhys/pos/index.blade.php` | Modify | Replace dropdowns with name+phone inputs + autocomplete, update Alpine.js state/methods |
| `resources/views/hendhys/pos/checkout.blade.php` | Modify | Add `customer_phone` to localStorage payload sent to `processCheckout` |

---

## Task 1: Database Migrations

**Files:**
- Create: `database/migrations/2026_05_21_000001_add_customer_phone_to_hendhys_transactions.php`
- Create: `database/migrations/2026_05_21_000002_add_customer_phone_to_hendhys_pending_transactions.php`

- [ ] **Step 1: Create migration for hendhys_transactions**

```php
<?php
// database/migrations/2026_05_21_000001_add_customer_phone_to_hendhys_transactions.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('hendhys_transactions', function (Blueprint $table) {
            $table->string('customer_phone', 20)->nullable()->after('customer_name');
        });
    }
    public function down(): void {
        Schema::table('hendhys_transactions', function (Blueprint $table) {
            $table->dropColumn('customer_phone');
        });
    }
};
```

- [ ] **Step 2: Create migration for hendhys_pending_transactions**

```php
<?php
// database/migrations/2026_05_21_000002_add_customer_phone_to_hendhys_pending_transactions.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('hendhys_pending_transactions', function (Blueprint $table) {
            $table->string('customer_phone', 20)->nullable()->after('customer_name');
        });
    }
    public function down(): void {
        Schema::table('hendhys_pending_transactions', function (Blueprint $table) {
            $table->dropColumn('customer_phone');
        });
    }
};
```

- [ ] **Step 3: Run migrations**

```bash
php artisan migrate
```

Expected output:
```
INFO  Running migrations.
2026_05_21_000001_add_customer_phone_to_hendhys_transactions ......... DONE
2026_05_21_000002_add_customer_phone_to_hendhys_pending_transactions .. DONE
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_05_21_000001_add_customer_phone_to_hendhys_transactions.php database/migrations/2026_05_21_000002_add_customer_phone_to_hendhys_pending_transactions.php
git commit -m "feat: add customer_phone column to hendhys transaction tables"
```

---

## Task 2: Update Models

**Files:**
- Modify: `app/Models/HendhysTransaction.php`
- Modify: `app/Models/HendhysPendingTransaction.php`

- [ ] **Step 1: Add `customer_phone` to HendhysTransaction fillable**

In `app/Models/HendhysTransaction.php`, change the `$fillable` array from:
```php
protected $fillable = [
    'transaction_number', 'branch_id', 'date', 'time', 'customer_name',
    'customer_id', 'customer_type', 'subtotal', 'discount_amount',
    'ppn_type', 'tax_amount', 'other_costs', 'grand_total',
    'status', 'notes', 'created_by'
];
```
To:
```php
protected $fillable = [
    'transaction_number', 'branch_id', 'date', 'time', 'customer_name',
    'customer_phone', 'customer_id', 'customer_type', 'subtotal', 'discount_amount',
    'ppn_type', 'tax_amount', 'other_costs', 'grand_total',
    'status', 'notes', 'created_by'
];
```

- [ ] **Step 2: Add `customer_phone` to HendhysPendingTransaction fillable**

In `app/Models/HendhysPendingTransaction.php`, change `$fillable` from:
```php
protected $fillable = [
    'pending_number', 'branch_id', 'date', 'customer_name', 
    'customer_id', 'customer_type', 'notes', 'created_by'
];
```
To:
```php
protected $fillable = [
    'pending_number', 'branch_id', 'date', 'customer_name',
    'customer_phone', 'customer_id', 'customer_type', 'notes', 'created_by'
];
```

- [ ] **Step 3: Commit**

```bash
git add app/Models/HendhysTransaction.php app/Models/HendhysPendingTransaction.php
git commit -m "feat: add customer_phone to Hendhys transaction model fillables"
```

---

## Task 3: Backend — Customer Search API + Route

**Files:**
- Modify: `app/Http/Controllers/Hendhys/PosController.php`
- Modify: `routes/hendhys.php`

- [ ] **Step 1: Add `customerSearch()` method to PosController**

Add this method after `heldStock()` in `app/Http/Controllers/Hendhys/PosController.php`:

```php
public function customerSearch(Request $request)
{
    $q = $request->get('q', '');
    if (strlen($q) < 2) {
        return response()->json([]);
    }

    $user = auth()->user();

    $query = \App\Models\HendhysTransaction::query()
        ->whereNotNull('customer_name')
        ->where('customer_name', '!=', '')
        ->where('customer_name', 'like', '%' . $q . '%');

    if ($user->branch->type === 'cabang') {
        $query->where('branch_id', $user->branch_id);
    } else {
        $query->whereNull('branch_id');
    }

    $results = $query
        ->select('customer_name', 'customer_phone')
        ->distinct()
        ->orderBy('customer_name')
        ->limit(8)
        ->get();

    return response()->json($results);
}
```

- [ ] **Step 2: Add route in `routes/hendhys.php`**

Add this line immediately after the existing `held-stock` route (before the `pos.store` route):

```php
Route::get('/pos/customer-search', [PosController::class, 'customerSearch'])->name('pos.customer-search');
```

So the POS routes block becomes:
```php
Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
Route::get('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
Route::get('/pos/held-stock', [PosController::class, 'heldStock'])->name('pos.held-stock');
Route::get('/pos/customer-search', [PosController::class, 'customerSearch'])->name('pos.customer-search');
Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
Route::get('/pos/{transaction}/receipt', [PosController::class, 'receipt'])->name('pos.receipt');
```

- [ ] **Step 3: Update `store()` in PosController to handle `customer_phone`**

In the `store()` method, find the `$request->validate([...])` block and add:
```php
'customer_phone' => 'nullable|string|max:20',
```

Then in the `HendhysTransaction::create([...])` call, add:
```php
'customer_phone' => $request->customer_phone,
```

The full updated create call inside the DB transaction:
```php
$transaction = HendhysTransaction::create([
    'transaction_number' => $this->numbers->generateYearly('HTRX', 'hendhys_transactions', 'transaction_number'),
    'branch_id' => $branchId,
    'date' => now()->toDateString(),
    'time' => now()->toTimeString(),
    'customer_id' => null,
    'customer_name' => $request->customer_name,
    'customer_phone' => $request->customer_phone,
    'customer_type' => 'retail',
    'subtotal' => $request->subtotal,
    'discount_amount' => $request->discount_amount ?? 0,
    'ppn_type' => $request->ppn_type,
    'tax_amount' => $request->tax_amount ?? 0,
    'other_costs' => $request->other_costs ?? 0,
    'grand_total' => $request->grand_total,
    'status' => 'paid',
    'notes' => $request->notes,
    'created_by' => $user->id
]);
```

- [ ] **Step 4: Verify route registered**

```bash
php artisan route:list --path=hendhys/pos
```

Expected: `hendhys.pos.customer-search` appears in the list.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Hendhys/PosController.php routes/hendhys.php
git commit -m "feat: add customer search API and update POS store to accept customer_phone"
```

---

## Task 4: Update PendingController

**Files:**
- Modify: `app/Http/Controllers/Hendhys/PendingController.php`

- [ ] **Step 1: Add `customer_phone` to validation and create in `store()`**

In `store()`, add to `$request->validate([...])`:
```php
'customer_phone' => 'nullable|string|max:20',
```

In `HendhysPendingTransaction::create([...])`, add:
```php
'customer_phone' => $request->customer_phone,
'customer_type' => 'retail',
```

The full updated create call:
```php
$pending = HendhysPendingTransaction::create([
    'pending_number' => $this->numbers->generateYearly('HPND', 'hendhys_pending_transactions', 'pending_number'),
    'branch_id' => $branchId,
    'date' => now()->toDateString(),
    'customer_id' => null,
    'customer_name' => $request->customer_name,
    'customer_phone' => $request->customer_phone,
    'customer_type' => 'retail',
    'notes' => $request->notes,
    'created_by' => $user->id
]);
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Controllers/Hendhys/PendingController.php
git commit -m "feat: update PendingController store to accept customer_phone"
```

---

## Task 5: Remove Customer Master from Sidebar + Routes

**Files:**
- Modify: `resources/views/layouts/hendhys.blade.php`
- Modify: `routes/hendhys.php`

- [ ] **Step 1: Remove "Pelanggan" from sidebar in `hendhys.blade.php`**

Find this array in the sidebar foreach (around line 194):
```php
['route' => 'hendhys.master.products.index',   'label' => 'Produk'],
['route' => 'hendhys.master.categories.index',  'label' => 'Kategori'],
['route' => 'hendhys.master.units.index',       'label' => 'Satuan'],
['route' => 'hendhys.master.brands.index',      'label' => 'Brand'],
['route' => 'hendhys.master.jenis.index',       'label' => 'Jenis'],
['route' => 'hendhys.master.customers.index',   'label' => 'Pelanggan']
```

Remove the last line (Pelanggan), leaving:
```php
['route' => 'hendhys.master.products.index',   'label' => 'Produk'],
['route' => 'hendhys.master.categories.index',  'label' => 'Kategori'],
['route' => 'hendhys.master.units.index',       'label' => 'Satuan'],
['route' => 'hendhys.master.brands.index',      'label' => 'Brand'],
['route' => 'hendhys.master.jenis.index',       'label' => 'Jenis'],
```

- [ ] **Step 2: Remove customer resource route from `routes/hendhys.php`**

Find and remove this line:
```php
Route::resource('customers', \App\Http\Controllers\Master\CustomerController::class)->except(['show']);
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/layouts/hendhys.blade.php routes/hendhys.php
git commit -m "feat: remove Hendhys customer master from sidebar and routes"
```

---

## Task 6: POS View — Replace Dropdowns with Manual Input + Autocomplete

**Files:**
- Modify: `resources/views/hendhys/pos/index.blade.php`

This is the largest task. It has two parts: the HTML UI and the Alpine.js JavaScript.

### Part A — HTML: Replace the customer section

- [ ] **Step 1: Replace the Customer & Type section in the cart sidebar**

Find this block (around line 160–174):
```html
{{-- Customer & Type --}}
<div class="shrink-0 p-sm bg-surface border-b border-outline-variant flex gap-sm">
    <select x-model="customerType"
        class="flex-1 text-[12px] border border-outline-variant rounded-lg bg-surface-container-lowest focus:ring-0 focus:border-primary font-bold text-on-surface py-2 px-2 cursor-pointer outline-none">
        <option value="retail">Retail Umum</option>
        <option value="agen">Agen Grosir</option>
    </select>
    <select x-model="customerId"
        class="flex-1 text-[12px] border border-outline-variant rounded-lg bg-surface-container-lowest focus:ring-0 focus:border-primary font-bold text-on-surface py-2 px-2 cursor-pointer outline-none">
        <option value="">Guest</option>
        @foreach($customers as $c)
            <option value="{{ $c->id }}">{{ $c->name }}</option>
        @endforeach
    </select>
</div>
```

Replace with:
```html
{{-- Customer Info: Manual Input + Autocomplete --}}
<div class="shrink-0 px-sm pt-sm pb-xs bg-surface border-b border-outline-variant space-y-xs" @click.outside="customerSuggestions = []">
    {{-- Nama Pelanggan --}}
    <div class="relative">
        <div class="flex items-center bg-surface-container-low rounded-t-lg border-b border-outline-variant focus-within:border-primary focus-within:border-b-2 transition-all px-xs">
            <span class="material-symbols-outlined text-outline text-[16px] shrink-0 mr-xs">person</span>
            <input
                type="text"
                x-model="customerName"
                @input.debounce.400ms="searchCustomers()"
                @keydown.escape="customerSuggestions = []"
                @focus="customerName.length >= 2 && searchCustomers()"
                placeholder="Nama Pelanggan (opsional)"
                class="w-full bg-transparent border-none focus:ring-0 text-[12px] font-medium text-on-surface placeholder-on-surface-variant py-sm px-0 outline-none"
                autocomplete="off"
            />
            <button x-show="customerName" @click="customerName = ''; customerPhone = ''; customerSuggestions = []"
                class="shrink-0 text-outline hover:text-on-surface transition-colors">
                <span class="material-symbols-outlined text-[14px]">close</span>
            </button>
        </div>
        {{-- Autocomplete Dropdown --}}
        <div x-show="customerSuggestions.length > 0"
             class="absolute left-0 right-0 top-full z-50 bg-surface-container-lowest border border-outline-variant rounded-b-lg shadow-lg overflow-hidden">
            <template x-for="(s, i) in customerSuggestions" :key="i">
                <button
                    @click="selectCustomer(s)"
                    class="w-full flex items-center gap-sm px-sm py-xs hover:bg-surface-container text-left transition-colors border-b border-outline-variant/50 last:border-0">
                    <span class="material-symbols-outlined text-[14px] text-on-surface-variant shrink-0">history</span>
                    <div class="min-w-0">
                        <p class="font-label-sm text-label-sm font-bold text-on-surface truncate" x-text="s.customer_name"></p>
                        <p class="text-[10px] text-on-surface-variant" x-text="s.customer_phone || 'Tanpa nomor telp'"></p>
                    </div>
                </button>
            </template>
        </div>
    </div>
    {{-- Nomor Telp --}}
    <div class="flex items-center bg-surface-container-low rounded-t-lg border-b border-outline-variant focus-within:border-primary focus-within:border-b-2 transition-all px-xs mb-xs">
        <span class="material-symbols-outlined text-outline text-[16px] shrink-0 mr-xs">call</span>
        <input
            type="tel"
            x-model="customerPhone"
            placeholder="Nomor Telp (opsional)"
            class="w-full bg-transparent border-none focus:ring-0 text-[12px] font-medium text-on-surface placeholder-on-surface-variant py-sm px-0 outline-none"
            autocomplete="off"
        />
    </div>
</div>
```

### Part B — Alpine.js: Update state and methods

- [ ] **Step 2: Replace `customerType` and `customerId` state with `customerName`, `customerPhone`, `customerSuggestions`**

Find:
```js
customerType: 'retail',
customerId: '',
```

Replace with:
```js
customerName: '',
customerPhone: '',
customerSuggestions: [],
```

- [ ] **Step 3: Update `loadResumeCart()` to use new fields**

Find:
```js
this.customerType = data.customerType || 'retail';
this.customerId = data.customerId || '';
```

Replace with:
```js
this.customerName = data.customerName || '';
this.customerPhone = data.customerPhone || '';
```

- [ ] **Step 4: Remove `getItemPrice()` agen logic and update**

Since `customerType` is gone, `getItemPrice()` now always returns retail price. Find:
```js
getItemPrice(item) {
    return this.customerType === 'agen' && item.price_agen > 0 ? Number(item.price_agen) : Number(item.price);
},
```

Replace with:
```js
getItemPrice(item) {
    return Number(item.price);
},
```

Also find and remove the agen badge in cart items template:
```html
<span x-show="customerType === 'agen' && item.price_agen > 0"
    class="ml-xs px-1 text-[9px] bg-tertiary-container text-on-tertiary-container rounded">AGEN</span>
```
(This line is now gone since we redesigned cart items in the previous task — verify it's not present.)

- [ ] **Step 5: Replace `getCustomerName()` with new method**

Find:
```js
getCustomerName() {
    if (!this.customerId) return 'Guest (Umum)';
    const select = document.querySelector('select[x-model="customerId"]');
    return select && select.selectedIndex > 0 ? select.options[select.selectedIndex].text : 'Customer';
},
```

Replace with:
```js
getCustomerName() {
    return this.customerName || 'Guest';
},
```

- [ ] **Step 6: Add `searchCustomers()` and `selectCustomer()` methods**

Add these two methods after `getCustomerName()`:

```js
async searchCustomers() {
    if (this.customerName.length < 2) {
        this.customerSuggestions = [];
        return;
    }
    try {
        const res = await fetch(`{{ route("hendhys.pos.customer-search") }}?q=` + encodeURIComponent(this.customerName));
        this.customerSuggestions = await res.json();
    } catch (e) {
        this.customerSuggestions = [];
    }
},

selectCustomer(suggestion) {
    this.customerName = suggestion.customer_name;
    this.customerPhone = suggestion.customer_phone || '';
    this.customerSuggestions = [];
},
```

- [ ] **Step 7: Update `holdTransaction()` payload**

Find the payload object inside `holdTransaction()`:
```js
const payload = {
    customer_type: this.customerType,
    customer_id: this.customerId,
    customer_name: this.getCustomerName(),
    ...
};
```

Replace with:
```js
const payload = {
    customer_name: this.customerName,
    customer_phone: this.customerPhone,
    customer_type: 'retail',
    customer_id: null,
    notes,
    items: this.cart.map(i => ({
        product_id: i.product_id,
        quantity: i.qty,
        price: this.getItemPrice(i),
        discount: 0,
        total: this.getItemTotal(i)
    }))
};
```

- [ ] **Step 8: Update `goToCheckout()` localStorage payload**

Find:
```js
localStorage.setItem('hendhys_pos_cart', JSON.stringify({
    items: this.cart.map(i => ({ ...i, price: this.getItemPrice(i), total: this.getItemTotal(i) })),
    subtotal: this.subtotal, discount: this.discount, ppnType: this.ppnType,
    taxAmount: this.taxAmount, grandTotal: this.grandTotal,
    customerType: this.customerType, customerId: this.customerId, customerName: this.getCustomerName()
}));
```

Replace with:
```js
localStorage.setItem('hendhys_pos_cart', JSON.stringify({
    items: this.cart.map(i => ({ ...i, price: this.getItemPrice(i), total: this.getItemTotal(i) })),
    subtotal: this.subtotal, discount: this.discount, ppnType: this.ppnType,
    taxAmount: this.taxAmount, grandTotal: this.grandTotal,
    customerName: this.customerName, customerPhone: this.customerPhone
}));
```

- [ ] **Step 9: Commit**

```bash
git add resources/views/hendhys/pos/index.blade.php
git commit -m "feat: replace POS customer dropdowns with manual name/phone input + autocomplete"
```

---

## Task 7: Update Checkout View

**Files:**
- Modify: `resources/views/hendhys/pos/checkout.blade.php`

The checkout reads from `cartData` (localStorage). We need to pass `customer_phone` in the payload to `pos.store`.

- [ ] **Step 1: Add `customer_phone` to the `processCheckout()` payload**

In `checkout.blade.php`, find the `payload` object inside `processCheckout()`:

```js
const payload = {
    customer_type: this.cartData.customerType,
    customer_id: this.cartData.customerId,
    customer_name: this.cartData.customerName,
    ...
};
```

Replace with:

```js
const payload = {
    customer_type: 'retail',
    customer_id: null,
    customer_name: this.cartData.customerName || '',
    customer_phone: this.cartData.customerPhone || '',
    subtotal: this.cartData.subtotal,
    discount_amount: this.cartData.discount,
    ppn_type: this.cartData.ppnType,
    tax_amount: this.cartData.taxAmount,
    other_costs: 0,
    grand_total: this.cartData.grandTotal,
    payment_method: this.paymentMethod,
    amount_paid: this.amountPaid,
    bank_name: this.paymentMethod === 'transfer' ? this.bankName : null,
    reference_number: this.paymentMethod === 'transfer' ? this.refNumber : null,
    items: this.cartData.items.map(item => ({
        product_id: item.product_id,
        quantity: item.qty,
        price: item.price,
        discount: 0,
        total: item.total
    }))
};
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/hendhys/pos/checkout.blade.php
git commit -m "feat: pass customer_phone from checkout view to POS store endpoint"
```

---

## Task 8: Remove `$customers` from PosController index + view

The POS view `index.blade.php` no longer needs the `$customers` collection (it was used for the Guest dropdown). Clean it up.

**Files:**
- Modify: `app/Http/Controllers/Hendhys/PosController.php`
- Modify: `resources/views/hendhys/pos/index.blade.php`

- [ ] **Step 1: Remove `$customers` from PosController `index()`**

Find:
```php
$customers = Customer::where('is_active', true)->orderBy('name')->get();

return view('hendhys.pos.index', compact('products', 'customers'));
```

Replace with:
```php
return view('hendhys.pos.index', compact('products'));
```

Also remove the `use App\Models\Hendhys\Customer;` import if it's only used for this.

- [ ] **Step 2: Remove `$customers` Blade foreach from POS view**

The old dropdown had:
```html
@foreach($customers as $c)
    <option value="{{ $c->id }}">{{ $c->name }}</option>
@endforeach
```
This was removed in Task 6 Step 1. Verify it's no longer present in the file.

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Hendhys/PosController.php resources/views/hendhys/pos/index.blade.php
git commit -m "chore: remove unused customers query from POS controller and view"
```

---

## Self-Review

### Spec Coverage Check
- [x] Replace "Retail Umum" / "Guest" dropdowns → Task 6 Part A
- [x] Add Nama Pelanggan + Nomor Telp inputs → Task 6 Part A
- [x] Autocomplete ≥ 2 chars, query `customer_name LIKE` → Task 3 Step 1, Task 6 Steps 6
- [x] Click suggestion → fill name + phone → Task 6 Step 6 (`selectCustomer`)
- [x] `GET /hendhys/pos/customer-search?q=` endpoint → Task 3
- [x] `customer_phone` migration for transactions → Task 1
- [x] `customer_phone` migration for pending → Task 1
- [x] `customer_type` stays but defaults to 'retail' → Tasks 3, 4, 6, 7
- [x] Remove Pelanggan from sidebar → Task 5 Step 1
- [x] Remove customer resource routes → Task 5 Step 2
- [x] Table/model NOT deleted → not in plan (correct)
- [x] Update `holdTransaction()` → Task 6 Step 7
- [x] Update `goToCheckout()` → Task 6 Step 8
- [x] Checkout payload updated → Task 7
- [x] PendingController updated → Task 4

### Placeholder Scan
- No TBD, TODO, or vague steps found.

### Type Consistency
- `customerName` / `customerPhone` / `customerSuggestions` defined in Task 6 Step 2 and used consistently in Steps 3–8.
- `customer_phone` column name consistent across migrations, models, controllers, and views.
- `selectCustomer(s)` defined in Step 6 and called in Step 1 template — consistent.
