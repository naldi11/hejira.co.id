<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jihans_production_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_number')->unique();
            $table->enum('type', ['prediksi', 'aktual'])->default('aktual');
            $table->date('date');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('overridden_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('master_users')->nullOnDelete();
        });

        Schema::create('jihans_production_session_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('karyawan_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('jihans_production_sessions')->cascadeOnDelete();
            $table->foreign('karyawan_id')->references('id')->on('master_karyawan')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('master_products')->cascadeOnDelete();
        });

        // Migrate data
        $oldSessions = DB::table('jihans_tortilla_sessions')->get();
        foreach ($oldSessions as $old) {
            $newSessionId = DB::table('jihans_production_sessions')->insertGetId([
                'session_number' => $old->session_number,
                'type'           => $old->type ?? 'aktual',
                'date'           => $old->date,
                'notes'          => $old->notes,
                'created_by'     => $old->created_by,
                'overridden_at'  => $old->overridden_at ?? null,
                'created_at'     => $old->created_at,
                'updated_at'     => $old->updated_at,
            ]);

            $oldDetails = DB::table('jihans_tortilla_session_details')
                ->where('session_id', $old->id)
                ->get();

            foreach ($oldDetails as $detail) {
                $variantMap = [
                    $old->tb_product_id => $detail->tb_qty,
                    $old->ts_product_id => $detail->ts_qty,
                    $old->tk_product_id => $detail->tk_qty,
                    $old->tc_product_id => $detail->tc_qty,
                    $old->kribab_product_id => $detail->kribab_qty,
                    $old->hitam_besar_product_id => $detail->hitam_besar_qty ?? 0,
                    $old->hitam_sedang_product_id => $detail->hitam_sedang_qty ?? 0,
                    $old->hitam_mini_product_id => $detail->hitam_mini_qty ?? 0,
                    $old->albaik_besar_product_id => $detail->albaik_besar_qty ?? 0,
                    $old->albaik_sedang_product_id => $detail->albaik_sedang_qty ?? 0,
                    $old->albaik_mini_product_id => $detail->albaik_mini_qty ?? 0,
                    $old->regular_besar_product_id => $detail->regular_besar_qty ?? 0,
                    $old->regular_sedang_product_id => $detail->regular_sedang_qty ?? 0,
                    $old->regular_mini_product_id => $detail->regular_mini_qty ?? 0,
                    $old->lentur_besar_product_id => $detail->lentur_besar_qty ?? 0,
                    $old->lentur_sedang_product_id => $detail->lentur_sedang_qty ?? 0,
                    $old->lentur_mini_product_id => $detail->lentur_mini_qty ?? 0,
                ];

                foreach ($variantMap as $pid => $qty) {
                    if ($pid && $qty > 0) {
                        // verify product exists
                        $productExists = DB::table('master_products')->where('id', $pid)->exists();
                        if ($productExists) {
                            DB::table('jihans_production_session_details')->insert([
                                'session_id'  => $newSessionId,
                                'karyawan_id' => $detail->karyawan_id,
                                'product_id'  => $pid,
                                'quantity'    => $qty,
                                'created_at'  => $detail->created_at,
                                'updated_at'  => $detail->updated_at,
                            ]);
                        }
                    }
                }
            }
        }

        Schema::dropIfExists('jihans_tortilla_session_details');
        Schema::dropIfExists('jihans_tortilla_sessions');
        Schema::dropIfExists('jihans_production_configs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jihans_production_session_details');
        Schema::dropIfExists('jihans_production_sessions');
    }
};
