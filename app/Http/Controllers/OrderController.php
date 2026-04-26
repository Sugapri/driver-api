<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // ==================== BUAT ORDER BARU (Dari Pelanggan / User) ====================
    public function store(Request $request)
    {
        $request->validate([
            'order_type'          => 'required|in:Ride,Food,Send,Shop,Car',
            'pickup_lat'          => 'required|numeric',
            'pickup_lng'          => 'required|numeric',
            'pickup_address'      => 'required|string|max:500',
            'destination_lat'     => 'nullable|numeric',
            'destination_lng'     => 'nullable|numeric',
            'destination_address' => 'nullable|string|max:500',
            'notes'               => 'nullable|string|max:500',
            'distance_km'         => 'required|numeric|min:0', // Jarak dari client (Google/OSRM)
            'payment_method'      => 'nullable|string',
        ]);

        // ==================== LOGIKA PERHITUNGAN TARIF DINAMIS ====================
        $distance    = $request->distance_km;
        $orderType   = $request->order_type;

        // Default: Motor (Ride)
        $suffix = 'motor';

        if ($orderType == 'Car') {
            $suffix = 'car';
        } elseif (in_array($orderType, ['Food', 'Send', 'Shop'])) {
            $suffix = 'service';
        }

        $baseFare    = Setting::get("{$suffix}_base_fare", 5000);
        $pricePerKm  = Setting::get("{$suffix}_price_per_km", 2500);
        $minFare     = Setting::get("{$suffix}_min_fare", 8800);

        // Peak Season Multiplier
        $peakIncrease = Setting::get('peak_season_increase', 0);
        $multiplier = 1 + ($peakIncrease / 100);

        $totalPrice = ($baseFare + ($distance * $pricePerKm)) * $multiplier;

        // Holiday Discount
        $holidayDiscount = Setting::get('holiday_discount', 0);
        $totalPrice = $totalPrice - $holidayDiscount;

        // Jika jarak <= 4km atau harga dibawah tarif minimal, gunakan tarif minimal
        if ($distance <= 4 || $totalPrice < $minFare) {
            $totalPrice = $minFare;
        }

        // Jika jarak <= 2km, beri diskon 15%
        if ($distance <= 2) {
            $totalPrice = $totalPrice * 0.85;
        }

        // ==================== POTONGAN APLIKASI & PAJAK ====================
        $appFeePercentage = Setting::get('app_fee_percentage', 20) / 100;
        $taxPercentage    = Setting::get('tax_percentage', 0.5) / 100;

        $appFee      = $totalPrice * $appFeePercentage;
        $taxFee      = $totalPrice * $taxPercentage;
        
        $driverTotal = $totalPrice - $appFee - $taxFee;

        $order = Order::create([
            'user_id'             => Auth::id(),
            'driver_id'           => null,
            'order_type'          => $request->order_type,
            'status'              => 'pending',
            'pickup_lat'          => $request->pickup_lat,
            'pickup_lng'          => $request->pickup_lng,
            'pickup_address'      => $request->pickup_address,
            'destination_lat'     => $request->destination_lat,
            'destination_lng'     => $request->destination_lng,
            'destination_address' => $request->destination_address,
            'notes'               => $request->notes,
            'distance_km'         => $distance,
            'estimated_price'     => $totalPrice, 
            'app_fee'             => $appFee,
            'driver_total'        => $driverTotal,
            'package_details'     => $request->package_details,
            'payment_method'      => $request->payment_method ?? 'Tunai',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil dibuat',
            'data'    => $order->load('user')
        ], 201);
    }

    // ==================== AMBIL ORDER AKTIF (Untuk Driver) ====================
    public function getActiveOrder()
    {
        $user = Auth::user();
        $driver = $user->driver;

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Anda bukan driver'
            ], 403);
        }

        $order = Order::where('driver_id', $driver->id)
                      ->whereIn('status', ['accepted', 'picked_up', 'on_trip'])
                      ->latest()
                      ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada order aktif saat ini'
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => $order->load('user')
        ]);
    }

    // ==================== UPDATE STATUS ORDER (Driver) ====================
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:accepted,arrived,picked_up,on_trip,completed,cancelled'
        ]);

        $order = Order::findOrFail($id);
        $driver = Auth::user()->driver;

        // Jika status bukan 'accepted', pastikan order ini milik driver tersebut
        if ($request->status !== 'accepted') {
            if (!$driver || $order->driver_id != $driver->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ini bukan milik Anda atau Anda tidak memiliki akses'
                ], 403);
            }
        } else {
            // Jika status 'accepted', pastikan order belum diambil driver lain
            if ($order->driver_id !== null && $order->driver_id != $driver->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ini sudah diambil oleh driver lain'
                ], 403);
            }
        }

        $updateData = [
            'status' => $request->status,
        ];

        if ($request->status === 'accepted') {
            $updateData['accepted_at'] = now();
            $updateData['driver_id'] = $driver->id;
        } elseif ($request->status === 'picked_up') {
            $updateData['picked_up_at'] = now();
        } elseif ($request->status === 'on_trip') {
            // Kita bisa tambahkan timestamp jika diperlukan nanti, sementara cukup status
        } elseif ($request->status === 'completed') {
            $updateData['completed_at'] = now();
            
            // Logic Saldo Driver (Gojek/Grab Style)
            DB::transaction(function() use ($order, $driver) {
                $commission = $order->estimated_price - $order->driver_total;

                if ($order->payment_method !== 'Tunai') {
                    // DIGITAL: Transparansi penuh (Masuk kotor, lalu potong komisi)
                    
                    // 1. Catat Pemasukan Kotor
                    $driver->increment('balance', $order->estimated_price);
                    Transaction::create([
                        'driver_id' => $driver->id,
                        'order_id'  => $order->id,
                        'amount'    => $order->estimated_price,
                        'type'      => 'income',
                        'description' => 'Pendapatan Order #' . $order->id,
                    ]);

                    // 2. Catat Potongan Komisi & Layanan
                    $driver->decrement('balance', $commission);
                    Transaction::create([
                        'driver_id' => $driver->id,
                        'order_id'  => $order->id,
                        'amount'    => -$commission,
                        'type'      => 'expense',
                        'description' => 'Potongan Komisi & Layanan #' . $order->id,
                    ]);
                } else {
                    // TUNAI: Driver sudah pegang cash, hanya potong saldo aplikasi untuk komisi
                    $driver->decrement('balance', $commission);
                    Transaction::create([
                        'driver_id' => $driver->id,
                        'order_id'  => $order->id,
                        'amount'    => -$commission,
                        'type'      => 'expense',
                        'description' => 'Potongan Komisi & Layanan (Tunai) #' . $order->id,
                    ]);
                }
            });
        } elseif ($request->status === 'cancelled') {
            $updateData['cancelled_at'] = now();
        }

        $order->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Status order berhasil diupdate',
            'data'    => $order->load('user')
        ]);
    }
        // ==================== ORDER TERSEDIA (untuk polling di HomeScreen) ====================
    public function available()
    {
        $orders = Order::with('user')
                        ->where('status', 'pending')
                        ->whereNull('driver_id')
                        ->latest()
                        ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    // ==================== TERIMA ORDER ====================
    public function accept(Request $request)
    {
        $request->validate(['order_id' => 'required|exists:orders,id']);

        $order = Order::findOrFail($request->order_id);
        $driver = auth()->user()->driver;

        if ($order->driver_id) {
            return response()->json([
                'success' => false,
                'message' => 'Order sudah diambil orang lain'
            ], 400);
        }

        $order->update([
            'driver_id' => $driver->id,
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil diambil',
            'data'    => $order->load('user')
        ]);
    }

    // ==================== CANCEL ORDER (Oleh Penumpang) ====================
    public function cancel($id)
    {
        $order = Order::findOrFail($id);

        if ($order->user_id != Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak membatalkan order ini'
            ], 403);
        }

        if (in_array($order->status, ['completed', 'finished', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak dapat dibatalkan dalam status ini'
            ], 400);
        }

        $order->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil dibatalkan',
            'data'    => $order
        ]);
    }

    // ==================== RIWAYAT ORDER ====================
    public function history()
    {
        $orders = Order::where('user_id', Auth::id())
                       ->orWhere('driver_id', Auth::user()->driver?->id)
                       ->with(['user', 'driver'])
                       ->latest()
                       ->get();

        return response()->json([
            'success' => true,
            'data'    => $orders
        ]);
    }

    // ==================== CHAT HISTORY (Last 24 Hours) ====================
    public function chatHistory()
    {
        $driver = Auth::user()->driver;
        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Anda bukan driver'
            ], 403);
        }

        $orders = Order::where('driver_id', $driver->id)
                       ->where('created_at', '>=', now()->subHours(24))
                       ->with('user')
                       ->latest()
                       ->get();

        return response()->json([
            'success' => true,
            'data'    => $orders
        ]);
    }

    // ==================== DETAIL ORDER (Untuk Penumpang/Driver) ====================
    public function show($id)
    {
        $order = Order::with(['driver.user', 'user'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan'
            ], 404);
        }

        $data = $order->toArray();
        if ($order->driver) {
            $data['driver_lat'] = $order->driver->latitude;
            $data['driver_lng'] = $order->driver->longitude;
            $data['driver']['name'] = $order->driver->user->name ?? 'Driver';
            $data['driver']['plate_number'] = $order->driver->plate_number;
            $data['driver']['phone'] = $order->driver->user->phone ?? '';
            $data['driver']['photo_url'] = $order->driver->user->photo_url ?? '';
        }

        return response()->json([
            'success' => true,
            'data'    => $data
        ]);
    }

    // ==================== HITUNG TARIF (Standalone) ====================
    public function calculateTariff(Request $request)
    {
        $request->validate([
            'pickup_lat'      => 'required|numeric',
            'pickup_lng'      => 'required|numeric',
            'destination_lat' => 'required|numeric',
            'destination_lng' => 'required|numeric',
        ]);

        $lat1 = $request->pickup_lat;
        $lon1 = $request->pickup_lng;
        $lat2 = $request->destination_lat;
        $lon2 = $request->destination_lng;
        $type = $request->order_type ?? 'Ride'; // Default to Ride if not specified

        // Haversine formula for distance
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        // Rounded to 1 decimal for display
        $distance = round($distance, 2);

        // Tariff Constants based on Order Type
        $suffix = 'motor';
        if ($type == 'Car') {
            $suffix = 'car';
        } elseif (in_array($type, ['Food', 'Send', 'Shop'])) {
            $suffix = 'service';
        }

        $baseFare    = Setting::get("{$suffix}_base_fare", 5000);
        $pricePerKm  = Setting::get("{$suffix}_price_per_km", 2500);
        $minFare     = Setting::get("{$suffix}_min_fare", 8800);

        // Peak Season & Holiday Logic
        $peakIncrease = Setting::get('peak_season_increase', 0);
        $multiplier = 1 + ($peakIncrease / 100);
        $holidayDiscount = Setting::get('holiday_discount', 0);

        $price = (($baseFare + ($distance * $pricePerKm)) * $multiplier) - $holidayDiscount;
        
        // Jika jarak <= 4km atau harga dibawah tarif minimal, gunakan tarif minimal
        if ($distance <= 4 || $price < $minFare) {
            $price = $minFare;
        }

        // Jika jarak <= 2km, beri diskon 15%
        if ($distance <= 2) {
            $price = $price * 0.85;
        }

        // Est Duration (avg 30 km/h)
        $duration = round($distance / 30 * 60);

        // App Fee & Driver Total
        $appFeePercent = Setting::get('app_fee_percentage', 20) / 100;
        $appFee = $price * $appFeePercent;

        return response()->json([
            'success' => true,
            'data'    => [
                'distance' => $distance,
                'price'    => round($price),
                'app_fee'  => round($appFee),
                'driver_total' => round($price - $appFee),
                'duration' => $duration
            ]
        ]);
    }

    // ==================== SUBMIT RATING (Dari Penumpang) ====================
    public function submitRating(Request $request, $id)
    {
        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan'
            ], 404);
        }

        // Pastikan order milik user yang sedang login
        if ($order->user_id != Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak memberikan rating untuk order ini'
            ], 403);
        }

        // Pastikan order sudah selesai
        if (!in_array($order->status, ['completed', 'finished'])) {
            return response()->json([
                'success' => false,
                'message' => 'Order belum selesai, tidak bisa memberikan rating'
            ], 400);
        }

        // Cek apakah sudah pernah di-rating
        if ($order->rating) {
            return response()->json([
                'success' => false,
                'message' => 'Order ini sudah pernah diberi rating'
            ], 400);
        }

        $order->update([
            'rating'         => $request->rating,
            'rating_comment' => $request->comment ?? '',
        ]);

        // Update rata-rata rating driver
        if ($order->driver_id) {
            $driver = $order->driver;
            if ($driver) {
                $avgRating = Order::where('driver_id', $driver->id)
                    ->whereNotNull('rating')
                    ->avg('rating');
                $driver->update(['rating' => round($avgRating, 2)]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Rating berhasil dikirim. Terima kasih!'
        ]);
    }
}
