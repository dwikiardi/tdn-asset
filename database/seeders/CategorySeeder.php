<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            [
                "name" => "LAPTOP",
            ],
            [
                "name" => "SMARTPHONE",
            ],
            [
                "name" => "FINGERPRINT",
            ],
            [
                "name" => "PRINTER",
            ],
            [
                "name" => "PC",
            ],
            [
                "name" => "PROJECTOR",
            ],
        ];

        Category::insertOrIgnore($categories);
    }
}
