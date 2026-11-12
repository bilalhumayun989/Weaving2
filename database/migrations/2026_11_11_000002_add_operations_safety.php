<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role', 20)->default('operator');
        });
        Schema::table('payments', function (Blueprint $t) {
            $t->timestamp('voided_at')->nullable();
            $t->text('void_reason')->nullable();
        });
        Schema::table('yarn_purchases', function (Blueprint $t) {
            $t->timestamp('voided_at')->nullable();
            $t->text('void_reason')->nullable();
        });
        Schema::table('kachi_parchis', function (Blueprint $t) {
            $t->timestamp('voided_at')->nullable();
            $t->text('void_reason')->nullable();
        });
        Schema::table('checking_reports', function (Blueprint $t) {
            $t->timestamp('voided_at')->nullable();
            $t->text('void_reason')->nullable();
        });
        Schema::table('contracts', function (Blueprint $t) {
            $t->text('parta_details')->nullable();
            $t->text('close_reason')->nullable();
            $t->date('closed_on')->nullable();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action', 120);
            $t->string('record_type', 120);
            $t->unsignedBigInteger('record_id');
            $t->json('details')->nullable();
            $t->string('ip_address', 64)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['record_type','record_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::table('contracts', fn (Blueprint $t) => $t->dropColumn(['parta_details','close_reason','closed_on']));
        Schema::table('checking_reports', fn (Blueprint $t) => $t->dropColumn(['voided_at','void_reason']));
        Schema::table('kachi_parchis', fn (Blueprint $t) => $t->dropColumn(['voided_at','void_reason']));
        Schema::table('yarn_purchases', fn (Blueprint $t) => $t->dropColumn(['voided_at','void_reason']));
        Schema::table('payments', fn (Blueprint $t) => $t->dropColumn(['voided_at','void_reason']));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('role'));
    }
};
