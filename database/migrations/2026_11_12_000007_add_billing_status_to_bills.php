<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->string('billing_status', 12)->default('final')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('bills', fn (Blueprint $table) => $table->dropColumn('billing_status'));
    }
};
