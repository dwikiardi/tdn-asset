<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            // === AUTH & USERS ===
            UserSeeder::class,          // Admin login awal: admin / admin123

            // === MASTER DATA ===
            RegionSeeder::class,        // Wilayah / Regional
            SiteSeeder::class,          // Lokasi: Gudang, POP, Hub, dll
            CategorySeeder::class,      // Kategori aset (hardware, dll)
            SupplierSeeder::class,      // Data supplier

            // Seeder lama (companies, divisions, regionals, employees) dipertahankan
            // tapi data di-skip jika tabel tidak relevan
            // CompanySeeder::class, -- sudah tidak digunakan (pakai regions)
            // RegionalSeeder::class,  -- sudah tidak digunakan (pakai regions)
            // DivisionSeeder::class,  -- sudah tidak digunakan (pakai sites)
            // EmployeeSeeder::class,  -- staff dari Tridatu Netmon via API
        ]);
    }
}
