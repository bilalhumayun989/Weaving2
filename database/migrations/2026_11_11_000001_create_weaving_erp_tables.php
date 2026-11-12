<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('factories', function (Blueprint $t) {
            $t->id(); $t->string('name')->unique(); $t->boolean('active')->default(true); $t->timestamps();
        });
        Schema::create('parties', function (Blueprint $t) {
            $t->id(); $t->string('name')->unique(); $t->enum('type', ['customer','supplier','both'])->default('both');
            $t->string('phone')->nullable(); $t->text('address')->nullable();
            $t->decimal('opening_receivable', 16, 2)->default(0); $t->decimal('opening_payable', 16, 2)->default(0); $t->timestamps();
        });
        Schema::create('fabric_qualities', function (Blueprint $t) {
            $t->id(); $t->string('name')->unique(); $t->decimal('picks', 10, 2)->nullable();
            $t->string('warp_count')->nullable(); $t->string('weft_count')->nullable(); $t->string('width')->nullable();
            $t->json('parta_inputs')->nullable(); $t->text('notes')->nullable(); $t->timestamps();
        });
        Schema::create('contracts', function (Blueprint $t) {
            $t->id(); $t->string('reference')->unique(); $t->foreignId('factory_id')->constrained()->restrictOnDelete();
            $t->foreignId('party_id')->constrained()->restrictOnDelete(); $t->foreignId('fabric_quality_id')->constrained()->restrictOnDelete();
            $t->enum('type', ['job_work','fabric_rate']); $t->decimal('target_metres', 14, 2)->nullable();
            $t->decimal('rate_per_pick', 14, 4)->nullable(); $t->decimal('picks_at_contract', 10, 2)->nullable(); $t->decimal('rate_per_metre', 14, 4)->nullable();
            $t->decimal('required_bags', 14, 3)->nullable(); $t->date('contract_date');
            $t->enum('status', ['active','closed'])->default('active'); $t->text('notes')->nullable(); $t->timestamps();
        });
        Schema::create('yarn_receipts', function (Blueprint $t) {
            $t->id(); $t->foreignId('contract_id')->constrained()->restrictOnDelete();
            $t->date('received_on'); $t->decimal('bags', 14, 3); $t->string('reference')->nullable(); $t->text('notes')->nullable(); $t->timestamps();
        });
        Schema::create('yarn_purchases', function (Blueprint $t) {
            $t->id(); $t->foreignId('factory_id')->constrained()->restrictOnDelete();
            $t->foreignId('party_id')->constrained()->restrictOnDelete(); $t->date('purchased_on'); $t->string('invoice_no')->nullable();
            $t->string('yarn_description'); $t->decimal('bags', 14, 3); $t->decimal('rate_per_bag', 14, 4);
            $t->decimal('total', 16, 2); $t->text('notes')->nullable(); $t->timestamps();
        });
        Schema::create('checking_reports', function (Blueprint $t) {
            $t->id(); $t->string('reference')->unique(); $t->date('checked_on');
            $t->foreignId('factory_id')->constrained()->restrictOnDelete(); $t->foreignId('fabric_quality_id')->constrained()->restrictOnDelete();
            $t->foreignId('contract_id')->nullable()->constrained()->restrictOnDelete();
            $t->enum('entry_type', ['job_work','own_stock','own_contract']); $t->text('notes')->nullable(); $t->timestamps();
        });
        Schema::create('thaans', function (Blueprint $t) {
            $t->id(); $t->foreignId('checking_report_id')->constrained()->restrictOnDelete();
            $t->foreignId('factory_id')->constrained()->restrictOnDelete(); $t->foreignId('fabric_quality_id')->constrained()->restrictOnDelete();
            $t->foreignId('contract_id')->nullable()->constrained()->restrictOnDelete();
            $t->enum('ownership', ['job_work','own']); $t->string('thaan_no'); $t->decimal('metres', 14, 2);
            $t->enum('status', ['available','dispatched','accepted'])->default('available'); $t->timestamps();
            $t->unique(['factory_id','thaan_no']);
        });
        Schema::create('kachi_parchis', function (Blueprint $t) {
            $t->id(); $t->string('reference')->unique(); $t->date('dispatched_on');
            $t->foreignId('factory_id')->constrained()->restrictOnDelete(); $t->foreignId('party_id')->constrained()->restrictOnDelete();
            $t->foreignId('contract_id')->nullable()->constrained()->restrictOnDelete();
            $t->enum('kind', ['job_work','fabric_sale']); $t->decimal('rate_per_metre', 14, 4)->nullable();
            $t->enum('status', ['dispatched','pending_returns','settled'])->default('dispatched'); $t->timestamps();
        });
        Schema::create('kachi_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('kachi_parchi_id')->constrained()->restrictOnDelete();
            $t->foreignId('thaan_id')->constrained()->restrictOnDelete(); $t->decimal('metres', 14, 2);
            $t->enum('state', ['dispatched','accepted','returned'])->default('dispatched');
            $t->unique(['kachi_parchi_id','thaan_id']); $t->timestamps();
        });
        Schema::create('pakki_parchis', function (Blueprint $t) {
            $t->id(); $t->foreignId('kachi_parchi_id')->unique()->constrained()->restrictOnDelete(); $t->date('accepted_on');
            $t->decimal('accepted_metres', 14, 2); $t->decimal('rejected_metres', 14, 2); $t->unsignedInteger('accepted_thaan_count');
            $t->timestamps();
        });
        Schema::create('bills', function (Blueprint $t) {
            $t->id(); $t->string('reference')->unique(); $t->foreignId('kachi_parchi_id')->unique()->constrained()->restrictOnDelete();
            $t->foreignId('factory_id')->constrained()->restrictOnDelete(); $t->foreignId('party_id')->constrained()->restrictOnDelete();
            $t->foreignId('contract_id')->nullable()->constrained()->restrictOnDelete();
            $t->enum('type', ['conversion','fabric_sale']); $t->date('billed_on');
            $t->decimal('accepted_metres', 14, 2); $t->decimal('picks', 10, 2)->nullable();
            $t->decimal('rate', 14, 4); $t->decimal('total', 16, 2); $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id(); $t->foreignId('party_id')->constrained()->restrictOnDelete();
            $t->foreignId('factory_id')->nullable()->constrained()->restrictOnDelete();
            $t->enum('kind', ['received','paid']); $t->date('paid_on'); $t->decimal('amount', 16, 2);
            $t->string('reference')->nullable(); $t->text('notes')->nullable(); $t->timestamps();
        });
        Schema::create('ledger_entries', function (Blueprint $t) {
            $t->id(); $t->foreignId('party_id')->constrained()->restrictOnDelete();
            $t->foreignId('factory_id')->nullable()->constrained()->restrictOnDelete();
            $t->enum('account_type', ['receivable','payable']); $t->date('entry_on'); $t->string('description');
            $t->decimal('debit', 16, 2)->default(0); $t->decimal('credit', 16, 2)->default(0);
            $t->string('source_type'); $t->unsignedBigInteger('source_id'); $t->timestamps();
            $t->unique(['source_type','source_id','account_type']);
            $t->index(['party_id','account_type','entry_on','id']);
        });
    }
    public function down(): void
    {
        foreach (['ledger_entries','payments','bills','pakki_parchis','kachi_items','kachi_parchis','thaans','checking_reports','yarn_purchases','yarn_receipts','contracts','fabric_qualities','parties','factories'] as $table) Schema::dropIfExists($table);
    }
};
