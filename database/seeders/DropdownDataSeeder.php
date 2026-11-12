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
        foreach (['Factory 1', 'Factory 2', 'Factory 3'] as $name) {
            Factory::firstOrCreate(['name' => $name]);
        }

        Party::firstOrCreate(
            ['name' => 'Hamaza'],
            ['type' => 'customer']
        );
        Party::firstOrCreate(
            ['name' => 'ALi'],
            ['type' => 'supplier']
        );

        foreach ([60, 66] as $picks) {
            FabricQuality::firstOrCreate(
                ['name' => "Quality - {$picks} Picks"],
                [
                    'picks' => $picks,
                    'notes' => 'Demo dropdown record; confirm specifications before use.',
                ]
            );
        }
    }
}
