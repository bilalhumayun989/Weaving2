<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('yarn_supplier', 20)->default('customer');
        });

        DB::table('contracts')->where('type', 'fabric_rate')->update(['yarn_supplier' => 'factory']);
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('yarn_supplier');
        });
    }
};
