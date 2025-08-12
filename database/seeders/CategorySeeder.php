<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Elektronik',
                'icon' => 'category/Elektronik.webp',
                'childs' => ['Kulkas', 'TV', 'AC'],
            ],
            [
                'name' => 'Komputer & Laptop',
                'icon' => 'category/Komputer-Laptop.webp',
                'childs' => ['Laptop', 'Mouse', 'Keyboard'],
            ],
            [
                'name' => 'Handphone & Aksesoris',
                'icon' => 'category/Handphone.webp',
                'childs' => ['Handphone', 'Case', 'Powerbank'],
            ],
            [
                'name' => 'Fashion Pria',
                'icon' => 'category/Fashion-Pria.webp',
                'childs' => ['Jacket', 'Kaos', 'Kemeja', 'Celana'],
            ],
            [
                'name' => 'Sepatu Pria',
                'icon' => 'category/Sepatu.webp',
                'childs' => ['Sneakers', 'Running', 'Sandal'],
            ],
            [
                'name' => 'Jam Tangan',
                'icon' => 'category/Jam-Tangan.webp',
                'childs' => ['Jam Tangan Pria', 'Jam Tangan Wanita', 'Aksesoris Jam'],
            ],
            [
                'name' => 'Kesehatan',
                'icon' => 'category/Kesehatan.webp',
                'childs' => ['Obat-Obatan', 'Suplemen', 'Alat Kesehatan'],
            ],
            [
                'name' => 'Makanan & Minuman',
                'icon' => 'category/Makanan-Minuman.webp',
                'childs' => ['Makanan', 'Minuman'],
            ],
            [
                'name' => 'Perawatan & Kecantikan',
                'icon' => 'category/Makeup.webp',
                'childs' => ['Makeup', 'Skincare', 'Haircare'],
            ],
            [
                'name' => 'Perlengkapan Rumah Tangga',
                'icon' => 'category/Rumah-Tangga.webp',
                'childs' => ['Peralatan Dapur', 'Perabotan Rumah', 'Dekorasi'],
            ],
            [
                'name' => 'Fashion Wanita',
                'icon' => 'category/Fashion-Wanita.webp',
                'childs' => ['Dress', 'Atasan', 'Bawahan', 'Sepatu Wanita'],
            ],
            [
                'name' => 'Fashion Anak',
                'icon' => 'category/Fashion-Anak.webp',
                'childs' => ['Baju Anak', 'Celana Anak', 'Sepatu Anak'],
            ],
            [
                'name' => 'Ibu & Bayi',
                'icon' => 'category/Ibu-Bayi.webp',
                'childs' => ['Makanan Bayi', 'Perawatan Bayi', 'Mainan Bayi', 'Pakaian Bayi'],
            ],
            [
                'name' => 'Tas & Aksesoris',
                'icon' => 'category/Tas-Wanita.webp',
                'childs' => ['Tas Pria', 'Tas Wanita', 'Aksesoris'],
            ]

        ];

        foreach ($categories as $categoryPayload) {
            $category = Category::create([
                'slug' => Str::slug($categoryPayload['name']),
                'name' => $categoryPayload['name'],
                'icon' => $categoryPayload['icon'],
            ]);

            foreach ($categoryPayload['childs'] as $child) {
                $category->childs()->create([
                    'slug' => Str::slug($child),
                    'name' => $child,
                ]);
            }
        }
    }
}
