<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yarn_qualities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('count', 80)->nullable();
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        Schema::table('yarn_purchases', function (Blueprint $table) {
            $table->foreignId('yarn_quality_id')->nullable()->after('party_id')->constrained('yarn_qualities')->restrictOnDelete();
            $table->enum('factory_scope', ['single', 'all'])->default('single')->after('factory_id');
        });
    }

    public function down(): void
    {
        Schema::table('yarn_purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('yarn_quality_id');
            $table->dropColumn('factory_scope');
        });
        Schema::dropIfExists('yarn_qualities');
    }
};
