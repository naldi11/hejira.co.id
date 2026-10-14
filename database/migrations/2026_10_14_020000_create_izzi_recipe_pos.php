<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        // Keep the old units' values and rows intact while adding a new unit.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE master_users MODIFY entity ENUM('gudang','jihans','hendhys','izzi','owner','all') NOT NULL DEFAULT 'all'");
            foreach (['master_products', 'master_units'] as $table) DB::statement("ALTER TABLE {$table} MODIFY entity_scope ENUM('gudang','jihans','hendhys','izzi','all') NOT NULL DEFAULT 'all'");
        }
        if (DB::getDriverName() === 'sqlite') {
            // SQLite enum changes rebuild tables. Skip a completed change when resuming.
            foreach (['master_users' => 'entity', 'master_products' => 'entity_scope', 'master_units' => 'entity_scope'] as $table => $column) {
                $sql = DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$table])->sql;
                if (str_contains($sql, "'izzi'")) continue;
                $values = ['gudang','jihans','hendhys','izzi','all'];
                if ($table === 'master_users') $values[] = 'owner';
                Schema::table($table, fn (Blueprint $t) => $t->enum($column, $values)->default('all')->change());
            }
        }
        foreach (['admin_izzi', 'kasir_izzi'] as $name) Role::findOrCreate($name, 'web');
        if (!Schema::hasTable('izzi_ingredients')) Schema::create('izzi_ingredients', function (Blueprint $t) {
            $t->foreignId('product_id')->primary()->constrained('master_products');
            $t->string('purchase_unit', 40);
            $t->unsignedBigInteger('purchase_factor')->default(1000); // Thousandths of the base unit.
            $t->timestamps();
        });
        if (!Schema::hasTable('izzi_menus')) Schema::create('izzi_menus', function (Blueprint $t) {
            $t->id(); $t->string('code', 40)->unique(); $t->string('name', 200); $t->string('category', 80);
            $t->unsignedBigInteger('price'); $t->boolean('is_active')->default(true);
            $t->unsignedInteger('recipe_version')->default(1); $t->json('recipe');
            $t->foreignId('updated_by')->constrained('master_users'); $t->timestamps();
            $t->index(['is_active','name']); $t->index('name');
        });
        if (!Schema::hasTable('izzi_recipe_versions')) Schema::create('izzi_recipe_versions', function (Blueprint $t) {
            $t->id(); $t->foreignId('menu_id')->constrained('izzi_menus'); $t->unsignedInteger('version');
            $t->string('menu_name'); $t->unsignedBigInteger('price'); $t->json('recipe');
            $t->foreignId('created_by')->constrained('master_users'); $t->string('actor_name'); $t->timestamp('created_at');
            $t->unique(['menu_id', 'version']);
        });
        if (!Schema::hasTable('izzi_stock')) Schema::create('izzi_stock', function (Blueprint $t) {
            $t->id(); $t->foreignId('branch_id')->constrained('master_branches');
            $t->foreignId('product_id')->constrained('master_products'); $t->foreignId('unit_id')->constrained('master_units');
            $t->unsignedBigInteger('quantity')->default(0); $t->timestamp('last_updated');
            $t->unique(['branch_id', 'product_id']);
        });
        if (!Schema::hasTable('izzi_sequences')) Schema::create('izzi_sequences', function (Blueprint $t) {
            $t->id(); $t->string('scope', 20); $t->date('date'); $t->unsignedInteger('last_number')->default(0);
            $t->unique(['scope', 'date']);
        });
        if (!Schema::hasTable('izzi_receipts')) Schema::create('izzi_receipts', function (Blueprint $t) {
            $t->id(); $t->uuid('request_key')->unique(); $t->string('fingerprint', 64); $t->string('number', 40)->unique();
            $t->foreignId('branch_id')->constrained('master_branches'); $t->string('branch_name');
            $t->string('origin'); $t->string('evidence_reference')->nullable(); $t->text('notes'); $t->timestamp('occurred_at');
            $t->json('items'); $t->foreignId('created_by')->constrained('master_users'); $t->string('actor_name'); $t->timestamp('created_at');
            $t->index(['branch_id', 'created_at']);
        });
        if (!Schema::hasTable('izzi_sales')) Schema::create('izzi_sales', function (Blueprint $t) {
            $t->id(); $t->uuid('checkout_key')->unique(); $t->string('checkout_fingerprint', 64);
            $t->string('number', 40)->unique(); $t->foreignId('branch_id')->constrained('master_branches'); $t->string('branch_name');
            $t->foreignId('shift_id')->constrained('master_cashier_shifts'); $t->string('status', 24)->default('completed');
            $t->string('customer_name', 150)->nullable(); $t->unsignedBigInteger('total'); $t->unsignedBigInteger('net_total');
            $t->string('payment_type', 20); $t->unsignedBigInteger('amount_paid'); $t->string('payment_reference')->nullable();
            $t->foreignId('created_by')->constrained('master_users'); $t->string('actor_name'); $t->timestamp('created_at');
            $t->index(['branch_id', 'created_at']); $t->index(['shift_id', 'payment_type']); $t->index('created_at');
        });
        if (!Schema::hasTable('izzi_sale_lines')) Schema::create('izzi_sale_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('sale_id')->constrained('izzi_sales'); $t->foreignId('menu_id')->constrained('izzi_menus');
            $t->string('menu_name'); $t->string('menu_code'); $t->unsignedInteger('recipe_version');
            $t->unsignedInteger('quantity'); $t->unsignedInteger('cancelled_quantity')->default(0);
            $t->unsignedInteger('unused_quantity')->default(0); $t->unsignedInteger('wasted_quantity')->default(0);
            $t->unsignedBigInteger('price'); $t->unique(['sale_id', 'menu_id']);
        });
        if (!Schema::hasTable('izzi_sale_ingredients')) Schema::create('izzi_sale_ingredients', function (Blueprint $t) {
            $t->id(); $t->foreignId('line_id')->constrained('izzi_sale_lines');
            $t->foreignId('product_id')->constrained('master_products'); $t->foreignId('unit_id')->constrained('master_units');
            $t->string('product_name'); $t->string('product_code'); $t->string('unit_name');
            $t->unsignedBigInteger('per_menu'); $t->unsignedBigInteger('quantity');
            $t->unsignedBigInteger('restored_quantity')->default(0); $t->unsignedBigInteger('wasted_quantity')->default(0);
            $t->unique(['line_id', 'product_id']);
        });
        if (!Schema::hasTable('izzi_cancellations')) Schema::create('izzi_cancellations', function (Blueprint $t) {
            $t->id(); $t->uuid('request_key')->unique(); $t->string('fingerprint', 64);
            $t->foreignId('sale_id')->constrained('izzi_sales'); $t->text('reason'); $t->json('items');
            $t->unsignedBigInteger('refund_amount'); $t->foreignId('created_by')->constrained('master_users');
            $t->string('actor_name'); $t->timestamp('created_at');
        });
        if (!Schema::hasTable('izzi_stock_movements')) Schema::create('izzi_stock_movements', function (Blueprint $t) {
            $t->id(); $t->foreignId('branch_id')->constrained('master_branches');
            $t->foreignId('product_id')->constrained('master_products'); $t->foreignId('unit_id')->constrained('master_units');
            $t->string('type', 8); $t->string('source', 40); $t->unsignedBigInteger('reference_id')->nullable();
            $t->foreignId('sale_id')->nullable()->constrained('izzi_sales');
            $t->bigInteger('quantity'); $t->bigInteger('quantity_before'); $t->bigInteger('quantity_after');
            $t->text('notes'); $t->foreignId('created_by')->constrained('master_users'); $t->string('actor_name')->nullable(); $t->timestamp('created_at');
            $t->index(['branch_id', 'id']);
        });
        if (!Schema::hasColumn('stock_opname_counts', 'quantity_scale')) Schema::table('stock_opname_counts', fn (Blueprint $t) => $t->unsignedInteger('quantity_scale')->default(1));
        if (DB::getDriverName() === 'mysql') Schema::table('master_cashier_shifts', function (Blueprint $t) {
            $t->bigInteger('expected_cash')->nullable()->change();
            $t->bigInteger('actual_cash')->nullable()->change();
            $t->bigInteger('discrepancy')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('stock_opname_counts', fn (Blueprint $t) => $t->dropColumn('quantity_scale'));
        foreach (['izzi_stock_movements','izzi_cancellations','izzi_sale_ingredients','izzi_sale_lines','izzi_sales','izzi_receipts','izzi_sequences','izzi_stock','izzi_recipe_versions','izzi_menus','izzi_ingredients'] as $table) Schema::dropIfExists($table);
        // Do not remove roles or enum values: existing accounts/master data may still use them.
    }
};
