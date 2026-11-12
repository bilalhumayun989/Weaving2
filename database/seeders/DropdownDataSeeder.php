<?php

namespace Database\Seeders;

use App\Models\Factory;
use App\Models\FabricQuality;
use App\Models\Party;
use Illuminate\Database\Seeder;

class DropdownDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Demo Factory 1', 'Demo Factory 2', 'Demo Factory 3'] as $name) {
            Factory::firstOrCreate(['name' => $name]);
        }

        Party::firstOrCreate(
            ['name' => 'Demo Buyer'],
            ['type' => 'customer']
        );
        Party::firstOrCreate(
            ['name' => 'Demo Yarn Supplier'],
            ['type' => 'supplier']
        );

        foreach ([60, 66] as $picks) {
            FabricQuality::firstOrCreate(
                ['name' => "Demo Quality - {$picks} Picks"],
                [
                    'picks' => $picks,
                    'notes' => 'Demo dropdown record; confirm specifications before use.',
                ]
            );
        }
    }
}
