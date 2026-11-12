<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('factory_scope', 12)->default('single')->after('factory_id');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', fn (Blueprint $table) => $table->dropColumn('factory_scope'));
    }
};
