<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add to jihans_production_config
        Schema::table('jihans_production_config', function (Blueprint $table) {
            $variants = [
                'hitam_besar', 'hitam_sedang', 'hitam_mini',
                'albaik_besar', 'albaik_sedang', 'albaik_mini',
                'regular_besar', 'regular_sedang', 'regular_mini',
                'lentur_besar', 'lentur_sedang', 'lentur_mini'
            ];
            foreach ($variants as $variant) {
                $table->foreignId($variant . '_product_id')->nullable()->after('kribab_product_id')->constrained('master_products')->nullOnDelete();
            }
        });

        // 2. Add to jihans_tortilla_sessions
        Schema::table('jihans_tortilla_sessions', function (Blueprint $table) {
            $variants = [
                'hitam_besar', 'hitam_sedang', 'hitam_mini',
                'albaik_besar', 'albaik_sedang', 'albaik_mini',
                'regular_besar', 'regular_sedang', 'regular_mini',
                'lentur_besar', 'lentur_sedang', 'lentur_mini'
            ];
            foreach ($variants as $variant) {
                $table->unsignedBigInteger($variant . '_product_id')->nullable()->after('kribab_product_id');
                $table->foreign($variant . '_product_id')->references('id')->on('master_products')->nullOnDelete();
            }
        });

        // 3. Add to jihans_tortilla_session_details
        Schema::table('jihans_tortilla_session_details', function (Blueprint $table) {
            $variants = [
                'hitam_besar', 'hitam_sedang', 'hitam_mini',
                'albaik_besar', 'albaik_sedang', 'albaik_mini',
                'regular_besar', 'regular_sedang', 'regular_mini',
                'lentur_besar', 'lentur_sedang', 'lentur_mini'
            ];
            foreach ($variants as $variant) {
                $table->unsignedInteger($variant . '_qty')->default(0)->after('kribab_qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jihans_production_config', function (Blueprint $table) {
            $variants = [
                'hitam_besar', 'hitam_sedang', 'hitam_mini',
                'albaik_besar', 'albaik_sedang', 'albaik_mini',
                'regular_besar', 'regular_sedang', 'regular_mini',
                'lentur_besar', 'lentur_sedang', 'lentur_mini'
            ];
            foreach ($variants as $variant) {
                $table->dropForeign([$variant . '_product_id']);
                $table->dropColumn($variant . '_product_id');
            }
        });

        Schema::table('jihans_tortilla_sessions', function (Blueprint $table) {
            $variants = [
                'hitam_besar', 'hitam_sedang', 'hitam_mini',
                'albaik_besar', 'albaik_sedang', 'albaik_mini',
                'regular_besar', 'regular_sedang', 'regular_mini',
                'lentur_besar', 'lentur_sedang', 'lentur_mini'
            ];
            foreach ($variants as $variant) {
                $table->dropForeign([$variant . '_product_id']);
                $table->dropColumn($variant . '_product_id');
            }
        });

        Schema::table('jihans_tortilla_session_details', function (Blueprint $table) {
            $variants = [
                'hitam_besar', 'hitam_sedang', 'hitam_mini',
                'albaik_besar', 'albaik_sedang', 'albaik_mini',
                'regular_besar', 'regular_sedang', 'regular_mini',
                'lentur_besar', 'lentur_sedang', 'lentur_mini'
            ];
            foreach ($variants as $variant) {
                $table->dropColumn($variant . '_qty');
            }
        });
    }
};
