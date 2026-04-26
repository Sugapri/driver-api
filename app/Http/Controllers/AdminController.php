<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function getStats()
    {
        $driverOnline = Driver::where('is_online', true)->count();
        $orderActive = Order::whereNotIn('status', ['completed', 'cancelled'])->count();
        $pendingVerification = Driver::where('status', 'pending')->count();
        
        // Revenue 24 jam terakhir
        $revenue24h = Order::where('status', 'completed')
            ->where('completed_at', '>=', now()->subDay())
            ->sum('app_fee');

        // Total Revenue
        $totalRevenue = Order::where('status', 'completed')->sum('app_fee');

        return response()->json([
            'success' => true,
            'stats' => [
                'driver_online' => $driverOnline,
                'order_active' => $orderActive,
                'pending_verification' => $pendingVerification,
                'revenue_24h' => "Rp " . number_format($revenue24h, 0, ',', '.'),
                'total_revenue' => "Rp " . number_format($totalRevenue, 0, ',', '.')
            ]
        ]);
    }

    public function listDrivers()
    {
        $drivers = Driver::with('user')->get();
        return response()->json([
            'success' => true,
            'data' => $drivers->map(function($d) {
                 return [
                     'id' => $d->id,
                     'name' => $d->user->name,
                     'phone' => $d->user->phone,
                     'plate_number' => $d->plate_number,
                     'balance' => $d->balance,
                     'status' => $d->status,
                     'is_online' => $d->is_online,
                     'photo_url' => $d->user->photo_url
                 ];
            })
        ]);
    }

    public function verifyDriver(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:verified,rejected']);
        
        $driver = Driver::findOrFail($id);
        $driver->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => "Driver status updated to " . $request->status
        ]);
    }

    public function listOrders()
    {
        $orders = Order::with(['user', 'driver.user'])->latest()->limit(50)->get();
        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function listPassengers()
    {
        $users = User::whereDoesntHave('driver')->where('role', 'user')->get();
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function getPricing()
    {
        return response()->json([
            'success' => true,
            'data' => [
                // Motor (Ride)
                'motor_base_fare' => Setting::get('motor_base_fare', 5000),
                'motor_price_per_km' => Setting::get('motor_price_per_km', 2500),
                'motor_min_fare' => Setting::get('motor_min_fare', 8800),

                // Mobil (Car)
                'car_base_fare' => Setting::get('car_base_fare', 10000),
                'car_price_per_km' => Setting::get('car_price_per_km', 5000),
                'car_min_fare' => Setting::get('car_min_fare', 15000),

                // Layanan (Food, Send, Shop)
                'service_base_fare' => Setting::get('service_base_fare', 8000),
                'service_price_per_km' => Setting::get('service_price_per_km', 3000),
                'service_min_fare' => Setting::get('service_min_fare', 10000),

                'peak_season_increase' => Setting::get('peak_season_increase', 0),
                'holiday_discount' => Setting::get('holiday_discount', 0),
                'app_fee_percentage' => Setting::get('app_fee_percentage', 20),
                'tax_percentage' => Setting::get('tax_percentage', 0.5),
            ]
        ]);
    }

    public function updatePricing(Request $request)
    {
        $request->validate([
            'motor_base_fare' => 'required|numeric',
            'motor_price_per_km' => 'required|numeric',
            'motor_min_fare' => 'required|numeric',
            'car_base_fare' => 'required|numeric',
            'car_price_per_km' => 'required|numeric',
            'car_min_fare' => 'required|numeric',
            'service_base_fare' => 'required|numeric',
            'service_price_per_km' => 'required|numeric',
            'service_min_fare' => 'required|numeric',
            'peak_season_increase' => 'required|numeric',
            'holiday_discount' => 'required|numeric',
            'app_fee_percentage' => 'required|numeric',
            'tax_percentage' => 'required|numeric',
        ]);

        $settings = $request->only([
            'motor_base_fare', 'motor_price_per_km', 'motor_min_fare',
            'car_base_fare', 'car_price_per_km', 'car_min_fare',
            'service_base_fare', 'service_price_per_km', 'service_min_fare',
            'peak_season_increase', 'holiday_discount', 'app_fee_percentage', 'tax_percentage'
        ]);

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tarif per kategori dan pajak berhasil diperbarui'
        ]);
    }

    public function resetRevenue()
    {
        Order::truncate(); // Menghapus semua data pesanan secara permanen
        return response()->json([
            'success' => true,
            'message' => 'Seluruh data pendapatan dan order telah dibersihkan'
        ]);
    }
}
