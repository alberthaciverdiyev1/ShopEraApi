<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // No `stores` table exists in this schema; keep the column without a foreign key.
            $table->unsignedBigInteger('store_id')->nullable()->after('user_id');
            $table->string('approval_status', 20)->default('approved')->after('store_id')->index();
            $table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['store_id', 'approval_status', 'approved_by', 'approved_at', 'rejection_reason']);
        });
    }
};
