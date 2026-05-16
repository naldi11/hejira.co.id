<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('barcode', 50)->unique()->nullable();
            $table->string('name', 200);
            $table->foreignId('category_id')->constrained('master_product_categories');
            $table->foreignId('unit_id')->constrained('master_units');
            $table->foreignId('brand_id')->nullable()->constrained('master_brands')->nullOnDelete();
            $table->string('rack', 20)->nullable();
            $table->enum('jenis', ['frozen', 'tortilla', 'bakery', 'bahan_baku', 'aksesoris', 'minuman', 'snack', 'selai', 'property', 'lainnya']);
            $table->decimal('hpp', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->integer('stock_min')->default(0);
            $table->enum('ppn_type', ['none', 'include', 'exclude'])->default('none');
            $table->decimal('ppn_rate', 5, 2)->default(11.00);
            $table->enum('product_type', ['INV', 'NON'])->default('INV');
            $table->enum('entity_scope', ['gudang', 'jihans', 'hendhys', 'all'])->default('all');
            $table->enum('status', ['active', 'discontinued'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('master_users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_products');
    }
};
