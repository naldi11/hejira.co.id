<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_branches', function (Blueprint $table) {
            $table->string('entity', 20)->nullable()->after('type')
                  ->comment('gudang | hendhys | jihans | owner');
        });

        // Populate entity based on branch code prefix
        DB::table('master_branches')->get()->each(function ($branch) {
            $code   = strtoupper(trim($branch->code ?? ''));
            $entity = 'hendhys'; // default fallback

            if (str_starts_with($code, 'HB') || str_starts_with($code, 'HND')) {
                $entity = 'hendhys';
            } elseif (str_starts_with($code, 'JF') || str_starts_with($code, 'IZ')) {
                $entity = 'jihans';
            } elseif (str_starts_with($code, 'GD') || str_starts_with($code, 'GU')) {
                $entity = 'gudang';
            }

            DB::table('master_branches')->where('id', $branch->id)->update(['entity' => $entity]);
        });
    }

    public function down(): void
    {
        Schema::table('master_branches', function (Blueprint $table) {
            $table->dropColumn('entity');
        });
    }
};
