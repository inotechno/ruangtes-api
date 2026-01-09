<?php

namespace Database\Seeders;

use App\Models\TestCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Tes Kepribadian',
                'description' => 'Tes untuk mengukur kepribadian, karakter, dan sifat-sifat individu',
                'sort_order' => 1,
            ],
            [
                'name' => 'Tes Inteligensi',
                'description' => 'Tes untuk mengukur kemampuan intelektual dan kognitif',
                'sort_order' => 2,
            ],
            [
                'name' => 'Tes Bakat & Minat',
                'description' => 'Tes untuk mengetahui bakat, minat, dan potensi karir',
                'sort_order' => 3,
            ],
            [
                'name' => 'Tes Kesehatan Mental',
                'description' => 'Tes untuk menilai kondisi kesehatan mental dan psikologis',
                'sort_order' => 4,
            ],
            [
                'name' => 'Tes Kompetensi',
                'description' => 'Tes untuk mengukur kompetensi dan kemampuan kerja',
                'sort_order' => 5,
            ],
            [
                'name' => 'Tes Seleksi Karyawan',
                'description' => 'Tes untuk proses seleksi dan rekrutmen karyawan',
                'sort_order' => 6,
            ],
        ];

        foreach ($categories as $category) {
            TestCategory::updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'is_active' => true,
                    'sort_order' => $category['sort_order'],
                ]
            );
        }
    }
}
