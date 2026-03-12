<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run()
    {
        $regions = [
            ['name' => 'Bali',                'code' => 'BALI',   'description' => 'Wilayah Bali'],
            ['name' => 'Jakarta',             'code' => 'JKT',    'description' => 'Wilayah Jakarta & Sekitarnya'],
            ['name' => 'Jawa Timur',          'code' => 'JATIM',  'description' => 'Wilayah Jawa Timur'],
            ['name' => 'Nusa Tenggara Barat', 'code' => 'NTB',    'description' => 'Wilayah Lombok & NTB'],
            ['name' => 'Pusat',               'code' => 'PUSAT',  'description' => 'Kantor & Gudang Pusat'],
        ];

        foreach ($regions as $data) {
            Region::updateOrCreate(['code' => $data['code']], array_merge($data, ['is_active' => true]));
        }

        $this->command->info('✅ Regions seeded: ' . count($regions) . ' wilayah.');
    }
}
