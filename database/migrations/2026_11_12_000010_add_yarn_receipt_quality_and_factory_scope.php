<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yarn_receipts', function (Blueprint $table) {
            $table->foreignId('yarn_quality_id')->nullable()->after('contract_id')->constrained('yarn_qualities')->restrictOnDelete();
            $table->foreignId('factory_id')->nullable()->after('yarn_quality_id')->constrained('factories')->restrictOnDelete();
            $table->string('factory_scope', 12)->default('single')->after('factory_id');
            $table->decimal('rate_per_lb', 14, 4)->nullable()->after('bags');
            $table->decimal('rate_per_bag', 14, 4)->nullable()->after('rate_per_lb');
        });
    }

    public function down(): void
    {
        Schema::table('yarn_receipts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('factory_id');
            $table->dropConstrainedForeignId('yarn_quality_id');
            $table->dropColumn(['factory_scope', 'rate_per_lb', 'rate_per_bag']);
        });
    }
};
