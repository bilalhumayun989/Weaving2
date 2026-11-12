<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contracts', function(Blueprint $t) {
            // New calculations are snapshotted so future master-data edits do not
            // silently change historical bag requirements or rates.
            $t->json('parta_snapshot')->nullable();
        });
        Schema::table('yarn_receipts', function(Blueprint $t) {
            // Informational only; does not change 100 lbs/bag.
            $t->unsignedInteger('cones_per_bag')->nullable();
        });
        Schema::table('yarn_purchases', function(Blueprint $t) {
            $t->unsignedInteger('cones_per_bag')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('yarn_purchases', fn(Blueprint $t) => $t->dropColumn('cones_per_bag'));
        Schema::table('yarn_receipts', fn(Blueprint $t) => $t->dropColumn('cones_per_bag'));
        Schema::table('contracts', fn(Blueprint $t) => $t->dropColumn('parta_snapshot'));
    }
};
