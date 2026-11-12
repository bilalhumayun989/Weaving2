<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('yarn_receipts', function (Blueprint $table) {
            $table->string('yarn_type', 10)->nullable()->after('received_on');
        });

        Schema::table('yarn_purchases', function (Blueprint $table) {
            $table->foreignId('contract_id')->nullable()->after('factory_id')->constrained()->restrictOnDelete();
            $table->string('yarn_type', 10)->nullable()->after('purchased_on');
        });
    }

    public function down(): void
    {
        Schema::table('yarn_purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contract_id');
            $table->dropColumn('yarn_type');
        });
        Schema::table('yarn_receipts', fn (Blueprint $table) => $table->dropColumn('yarn_type'));
    }
};
