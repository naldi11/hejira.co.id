<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('hendhys_transfer_to_branch', function (Blueprint $table) {
            $table->text('receive_notes')->nullable()->after('notes');
            $table->string('receive_photo', 255)->nullable()->after('receive_notes');
        });
    }
    public function down(): void {
        Schema::table('hendhys_transfer_to_branch', function (Blueprint $table) {
            $table->dropColumn(['receive_notes', 'receive_photo']);
        });
    }
};
