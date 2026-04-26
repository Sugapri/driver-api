<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\FoodCategory;
use App\Models\FoodItem;
use Illuminate\Database\Seeder;

class GoFoodSeeder extends Seeder
{
    public function run()
    {
        // 1. Create Categories
        $categories = [
            ['name' => 'Cepat Saji', 'icon' => 'fastfood'],
            ['name' => 'Tradisional', 'icon' => 'restaurant'],
            ['name' => 'Kopi', 'icon' => 'coffee'],
            ['name' => 'Sehat', 'icon' => 'eco'],
        ];

        foreach ($categories as $cat) {
            FoodCategory::create($cat);
        }

        $catCepatSaji = FoodCategory::where('name', 'Cepat Saji')->first();
        $catTradisional = FoodCategory::where('name', 'Tradisional')->first();
        $catKopi = FoodCategory::where('name', 'Kopi')->first();
        $catSehat = FoodCategory::where('name', 'Sehat')->first();

        // 2. Create Restaurants
        $restaurants = [
            [
                'name' => 'Burger King Sudirman',
                'description' => 'Rajanya Burger Bakar.',
                'address' => 'Jl. Jend. Sudirman No. 10',
                'latitude' => -6.2247,
                'longitude' => 106.8077,
                'rating' => 4.7,
                'delivery_time' => '15-25 min',
                'is_promo' => true,
                'items' => [
                    ['name' => 'Whopper Jr.', 'price' => 35000, 'cat' => $catCepatSaji->id],
                    ['name' => 'Cheese Burger', 'price' => 45000, 'cat' => $catCepatSaji->id],
                ]
            ],
            [
                'name' => 'Soto Ayam Lamongan Pak Jon',
                'description' => 'Soto ayam asli Lamongan dengan koya gurih.',
                'address' => 'Jl. Bendungan Hilir No. 5',
                'latitude' => -6.2162,
                'longitude' => 106.8115,
                'rating' => 4.8,
                'delivery_time' => '10-20 min',
                'is_promo' => false,
                'items' => [
                    ['name' => 'Soto Ayam Spesial', 'price' => 25000, 'cat' => $catTradisional->id],
                    ['name' => 'Es Jeruk Manis', 'price' => 10000, 'cat' => $catTradisional->id],
                ]
            ],
            [
                'name' => 'Kopi Kenangan Mantan',
                'description' => 'Kopi susunya Indonesia.',
                'address' => 'Mall Grand Indonesia',
                'latitude' => -6.1951,
                'longitude' => 106.8231,
                'rating' => 4.9,
                'delivery_time' => '5-15 min',
                'is_promo' => true,
                'items' => [
                    ['name' => 'Kopi Kenangan Mantan', 'price' => 18000, 'cat' => $catKopi->id],
                    ['name' => 'Dua Shot Iced Shaken', 'price' => 22000, 'cat' => $catKopi->id],
                ]
            ],
            [
                'name' => 'SaladStop! Senayan City',
                'description' => 'Eat Wide Awake.',
                'address' => 'Senayan City Lt. 5',
                'latitude' => -6.2274,
                'longitude' => 106.7974,
                'rating' => 4.6,
                'delivery_time' => '25-35 min',
                'is_promo' => false,
                'items' => [
                    ['name' => 'Hail Caesar Salad', 'price' => 85000, 'cat' => $catSehat->id],
                    ['name' => 'Tuna San Wrap', 'price' => 95000, 'cat' => $catSehat->id],
                ]
            ],
            [
                'name' => 'Mie Gacoan Tebet',
                'description' => 'Mie Pedas No. 1 di Indonesia.',
                'address' => 'Jl. Tebet Raya No. 12',
                'latitude' => -6.2291,
                'longitude' => 106.8481,
                'rating' => 4.8,
                'delivery_time' => '20-40 min',
                'is_promo' => true,
                'items' => [
                    ['name' => 'Mie Gacoan Level 3', 'price' => 15000, 'cat' => $catCepatSaji->id],
                    ['name' => 'Udang Rambutan', 'price' => 12000, 'cat' => $catCepatSaji->id],
                ]
            ],
        ];

        foreach ($restaurants as $resData) {
            $items = $resData['items'];
            unset($resData['items']);

            $restaurant = Restaurant::create($resData);

            foreach ($items as $item) {
                FoodItem::create([
                    'restaurant_id' => $restaurant->id,
                    'food_category_id' => $item['cat'],
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'description' => 'Deskripsi lezat untuk ' . $item['name'],
                ]);
            }
        }
    }
}
