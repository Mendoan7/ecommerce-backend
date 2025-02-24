<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function(){

            for ($productCount=1; $productCount <= 100; $productCount++) {

                $payload = [
                    'name' => 'Produk ' . $productCount,
                    'slug' => 'produk-' . $productCount,
                    'seller_id' => User::inRandomOrder()->first()->id,
                    'category_id' => Category::whereNotNull('parent_id')->inRandomOrder()->first()->id,
                    'description' => 'Deskripsi produk ' . $productCount . '. Lorem ipsum',
                    'stock' => rand(1, 100),
                    'weight' => rand(1, 100),
                    'length' => rand(1, 100),
                    'width' => rand(1, 100),
                    'height' => rand(1, 100),
                    'video' => 'attachment.mp4',
                    'price' => rand(10000, 100000),
                    'images' => [
                        'attachment1.jpg',
                        'attachment2.jpg',
                        'attachment3.jpg',
                        'attachment4.jpg',
                    ],
                    'variations' => [
                        [
                            'name' => 'Warna',
                            'values' => ['Hitam', 'Kuning', 'Biru']
                        ],
                        [
                            'name' => 'Ukuran',
                            'values' => ['M', 'L', 'XL']
                        ],
                    ],
                    'reviews' => [
                        [
                            'user_id' => User::inRandomOrder()->first()->id,
                            'star_seller' => rand(1, 5),
                            'star_courier' => rand(1, 5),
                            'variations' => 'Warna: Hijau, Ukuran: XL',
                            'description' => 'Produk Bagus!',
                            'attachments' => [
                                'attachment1.jpg',
                                'attachment2.jpg',
                                'attachment3.jpg',
                                'attachment4.jpg',
                            ],
                            'show_username' => rand(0, 1),
                        ],
                        [
                            'user_id' => User::inRandomOrder()->first()->id,
                            'star_seller' => rand(1, 5),
                            'star_courier' => rand(1, 5),
                            'variations' => 'Warna: Hijau, Ukuran: XL',
                            'description' => 'Produk Bagus!',
                            'attachments' => [
                                'attachment1.jpg',
                                'attachment2.jpg',
                                'attachment3.jpg',
                                'attachment4.jpg',
                            ],
                            'show_username' => rand(0, 1),
                        ],
                    ]
                ];

                $product = Product::create([
                    'name' => $payload['name'],
                    'slug' => $payload['slug'],
                    'seller_id' => $payload['seller_id'],
                    'category_id' => $payload['category_id'],
                    'description' => $payload['description'],
                    'stock' => $payload['stock'],
                    'weight' => $payload['weight'],
                    'length' => $payload['length'],
                    'width' => $payload['width'],
                    'height' => $payload['height'],
                    'video' => $payload['video'],
                    'price' => $payload['price'],
                ]);

                shuffle($payload['images']);
                foreach ($payload['images'] as $image) {
                    $product->images()->create([
                        'image' => $image
                    ]);
                }

                shuffle($payload['variations']);
                foreach ($payload['variations'] as $variation) {
                    $product->variations()->create($variation);
                }

                shuffle($payload['reviews']);
                foreach ($payload['reviews'] as $review) {
                    $product->reviews()->create($review);
                }

            }

        });
    }
}
