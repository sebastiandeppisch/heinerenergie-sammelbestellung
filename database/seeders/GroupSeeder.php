<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 5 main groups
        Group::factory(5)->create()->each(function ($mainGroup) {
            // For each main group, create 2-4 child groups
            Group::factory(fake()->numberBetween(2, 4))
                ->create(['parent_id' => $mainGroup->id])
                ->each(function ($childGroup) {
                    // For some child groups, create 1-3 sub-child groups
                    if (fake()->boolean(70)) {
                        Group::factory(fake()->numberBetween(1, 3))
                            ->create(['parent_id' => $childGroup->id]);
                    }
                });
        });
    }
}
