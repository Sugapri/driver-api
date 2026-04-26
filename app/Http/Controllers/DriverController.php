<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; //Tambahkan ini untuk upload file

class DriverController extends Controller
{
    // ==================== UPDATE ONLINE STATUS ====================
    public function updateOnline(Request $request)
    {
        $request->validate(['is_online' => 'required|boolean']);

        $driver = auth()->user()->driver;
        if (!$driver) return response()->json(['success' => false, 'message' => 'Driver record not found'], 404);
        $driver->update([
            'is_online' => $request->is_online,
            'last_active' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    // ==================== UPDATE LOCATION ====================
    public function updateLocation(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'heading' => 'nullable|numeric',
        ]);

        $driver = auth()->user()->driver;
        if (!$driver) return response()->json(['success' => false, 'message' => 'Driver record not found'], 404);
        $driver->update([
            'latitude'    => $request->lat,
            'longitude'   => $request->lng,
            'last_active' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    // ==================== UPDATE AUTO BID ====================
    public function updateAutoBid(Request $request)
    {
        $request->validate(['auto_bid' => 'required|boolean']);

        $driver = auth()->user()->driver;
        $driver->update(['auto_bid' => $request->auto_bid]);

        return response()->json(['success' => true]);
    }

    // ==================== UPDATE CAPITAL ====================
    public function updateCapital(Request $request)
    {
        $request->validate(['capital' => 'required|numeric|min:0']);

        $driver = auth()->user()->driver;
        $driver->update(['capital' => $request->capital]);

        return response()->json(['success' => true]);
    }

    // ==================== UPDATE SERVICES ====================
    public function updateServices(Request $request)
    {
        $driver = auth()->user()->driver;
        $driver->update(['services' => $request->services]);

        return response()->json(['success' => true]);
    }

    // ==================== GET PROFILE (PENTING UNTUK FLUTTER) ====================
    public function me()
    {
        $user = auth()->user();
        $driver = $user->driver;

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found'
            ], 404);
        }

        $driverData = $driver->toArray();
        $driverData['name'] = $user->name;
        $driverData['email'] = $user->email;
        $driverData['phone'] = $user->phone;
        $driverData['photo_url'] = $user->photo_url;

        return response()->json([
            'success' => true,
            'data' => $driverData
        ]);
    }

    // ==================== UPLOAD PROFILE IMAGE ====================
    public function uploadProfileImage(Request $request)
    {
        $request->validate([
            'image' => 'required|string',  // Base64 encoded image dari Flutter
        ]);

        $user = auth()->user();

        $imageString = $request->image;
        if (preg_match('/^data:\w+\/[^;]+;base64,/', $imageString)) {
            $imageString = substr($imageString, strpos($imageString, ',') + 1);
        }

        $imageData = base64_decode($imageString, true);
        if ($imageData === false) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid base64 image data.',
            ], 422);
        }

        $imageName = 'profile_' . $user->id . '_' . time() . '.jpg';
        $path = 'uploads/profiles/' . $imageName;

        // Simpan ke storage/public/uploads/profiles
        Storage::disk('public')->put($path, $imageData);

        // Update photo_url di user
        $photoUrl = asset('storage/' . $path);
        $user->update(['photo_url' => $photoUrl]);

        return response()->json([
            'success' => true,
            'photo_url' => $photoUrl,
        ]);
    }

    // ==================== UPDATE PROFILE ====================
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $driver = $user->driver;

        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string',
            'plate_number' => 'nullable|string|unique:drivers,plate_number,' . ($driver ? $driver->id : 'null'),
        ]);

        $user = auth()->user();
        $driver = $user->driver;

        // Update data user
        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('phone')) $user->phone = $request->phone;
        $user->save();

        // Update data driver
        if ($driver && $request->has('plate_number')) {
            $driver->plate_number = $request->plate_number;
            $driver->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
        ]);
    }

}