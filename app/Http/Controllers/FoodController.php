<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\FoodCategory;
use Illuminate\Http\Request;

class FoodController extends Controller
{
    /**
     * Get list of all restaurants with optional filters
     */
    public function index(Request $request)
    {
        $query = Restaurant::query();

        // Filter by Search
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by Promo
        if ($request->has('promo')) {
            $query->where('is_promo', true);
        }

        // Sorting Logic (Distance is usually done in DB but for simplicity we return all)
        if ($request->sort == 'rating') {
            $query->orderBy('rating', 'desc');
        }

        $restaurants = $query->get();

        return response()->json([
            'success' => true,
            'data' => $restaurants
        ]);
    }

    /**
     * Get list of food categories
     */
    public function categories()
    {
        $categories = FoodCategory::all();
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get detail of a restaurant and its menu items
     */
    public function show($id)
    {
        $restaurant = Restaurant::with(['foodItems.category'])->find($id);

        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Restoran tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $restaurant
        ]);
    }
}
