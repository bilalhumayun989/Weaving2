<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('yarn_purchases', function (Blueprint $table) {
            $table->unsignedSmallInteger('payment_terms_days')->nullable()->after('total');
            $table->date('payment_due_on')->nullable()->after('payment_terms_days');
            $table->string('payment_terms_note', 255)->nullable()->after('payment_due_on');
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('yarn_purchase_id')->nullable()->after('factory_id')->constrained('yarn_purchases')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->dropConstrainedForeignId('yarn_purchase_id'));
        Schema::table('yarn_purchases', fn (Blueprint $table) => $table->dropColumn(['payment_terms_days', 'payment_due_on', 'payment_terms_note']));
    }
};
