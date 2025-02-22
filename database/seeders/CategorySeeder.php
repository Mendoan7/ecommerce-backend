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
                'name' => 'Fashion Pria',
                'icon' => 'category/Fashion-Pria.webp',
                'childs' => ['Jacket', 'Kaos', 'Kemeja'],
            ],
            [
                'name' => 'Fashion Wanita',
                'icon' => 'category/Fashion-Wanita.webp',
                'childs' => ['Dress', 'Atasan', 'Bawahan'],
            ],
            [
                'name' => 'Handphone',
                'icon' => 'category/Handphone.webp',
                'childs' => ['Handphone', 'Case', 'Powerbank'],
            ],
            [
                'name' => 'Komputer & Laptop',
                'icon' => 'category/Komputer-Laptop.webp',
                'childs' => ['Laptop', 'Mouse', 'Keyboard'],
            ],
            [
                'name' => 'Makanan & Minuman',
                'icon' => 'category/Makanan-Minuman.webp',
                'childs' => ['Makanan', 'Minuman'],
            ],
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
