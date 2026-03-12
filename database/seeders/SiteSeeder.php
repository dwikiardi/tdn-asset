<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    public function run()
    {
        $bali  = Region::where('code', 'BALI')->first();
        $pusat = Region::where('code', 'PUSAT')->first();
        $ntb   = Region::where('code', 'NTB')->first();

        if (!$pusat || !$bali) {
            $this->command->warn('⚠  Region belum ada, jalankan RegionSeeder terlebih dahulu.');
            return;
        }

        $sites = [
            // Gudang & Kantor
            [
                'name'      => 'Gudang Pusat DWIKI',
                'code'      => 'GDG-PUSAT',
                'type'      => Site::TYPE_WAREHOUSE,
                'region_id' => $pusat->id,
                'address'   => 'Jl. Raya Kuta No.1, Bali',
                'pic_name'  => 'Admin Gudang',
            ],
            // POP Bali
            [
                'name'      => 'POP Denpasar',
                'code'      => 'POP-DPS',
                'type'      => Site::TYPE_POP,
                'region_id' => $bali->id,
                'address'   => 'Jl. Teuku Umar, Denpasar',
                'pic_name'  => 'Teknisi POP Dps',
            ],
            [
                'name'      => 'POP Kuta',
                'code'      => 'POP-KTA',
                'type'      => Site::TYPE_POP,
                'region_id' => $bali->id,
                'address'   => 'Jl. Legian, Kuta',
            ],
            [
                'name'      => 'Hub Ubud',
                'code'      => 'HUB-UBD',
                'type'      => Site::TYPE_HUB,
                'region_id' => $bali->id,
                'address'   => 'Jl. Monkey Forest, Ubud',
            ],
            // NTB
            [
                'name'      => 'POP Mataram',
                'code'      => 'POP-MTR',
                'type'      => Site::TYPE_POP,
                'region_id' => $ntb ? $ntb->id : $bali->id,
                'address'   => 'Jl. Pejanggik, Mataram',
            ],
        ];

        foreach ($sites as $data) {
            Site::updateOrCreate(['code' => $data['code']], array_merge($data, ['is_active' => true]));
        }

        $this->command->info('✅ Sites seeded: ' . count($sites) . ' lokasi.');
    }
}
