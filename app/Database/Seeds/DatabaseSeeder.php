<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * php spark db:seed DatabaseSeeder — every seeder of the application. Each
 * one only adds missing rows, so running it again changes nothing.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(OtherManagementSectionsSeeder::class);
        $this->call(CountrySeeder::class);
        $this->call(PageMetaTagsSeeder::class);
        $this->call(StoreSeeder::class);
        $this->call(EventsUpdatesSeeder::class);
    }
}
