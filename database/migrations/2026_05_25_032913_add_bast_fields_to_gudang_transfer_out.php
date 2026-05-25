<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gudang_transfer_out', function (Blueprint $table) {
            $table->text('receive_kendala')->nullable()->after('receive_photo');
            $table->string('receive_received_by_name', 255)->nullable()->after('receive_kendala');
            $table->string('receive_pengirim_name', 255)->nullable()->after('receive_received_by_name');
            $table->timestamp('received_at')->nullable()->after('receive_pengirim_name');
        });

        Schema::table('gudang_transfer_out_details', function (Blueprint $table) {
            $table->enum('kondisi', ['baik', 'rusak', 'kurang'])->nullable()->after('received_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('gudang_transfer_out', function (Blueprint $table) {
            $table->dropColumn(['receive_kendala', 'receive_received_by_name', 'receive_pengirim_name', 'received_at']);
        });

        Schema::table('gudang_transfer_out_details', function (Blueprint $table) {
            $table->dropColumn('kondisi');
        });
    }
};
