<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $role = $request->input('role', 'user');

        $rules = [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
            'phone'    => 'nullable|string',
            'role'     => 'nullable|in:user,driver',
        ];

        // Plate number hanya wajib jika role adalah driver
        if ($role === 'driver') {
            $rules['plate_number'] = 'required|string|unique:drivers';
        }

        $request->validate($rules);

        // Buat User
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->phone,
            'role'     => $role,
        ]);

        // Buat Driver profile hanya jika role adalah driver
        if ($role === 'driver') {
            Driver::create([
                'user_id'       => $user->id,
                'plate_number'  => $request->plate_number,
                'level'         => 'Bronze',
                'level_number'  => 1,
                'rating'        => 0.00,
                'services'      => ['Ride' => true, 'Food' => true, 'Send' => true, 'Shop' => true],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil. Silakan login.',
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Buat token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        $driver = $user->driver;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => [
                    'id'        => $user->id,
                    'name'      => $user->name,
                    'email'     => $user->email,
                    'phone'     => $user->phone,
                    'photo_url' => $user->photo_url,
                    'role'      => $user->role,
                    'balance'   => $user->balance ?? 0,
                ],
                'driver' => $driver ? [
                    'id'           => $driver->id,
                    'user_id'      => $driver->user_id,
                    'plate_number' => $driver->plate_number,
                    'level'        => $driver->level,
                    'level_number' => $driver->level_number,
                    'total_orders' => $driver->total_orders,
                    'rating'       => $driver->rating,
                    'balance'      => $driver->balance,
                    'capital'      => $driver->capital,
                    'is_online'    => $driver->is_online,
                    'auto_bid'     => $driver->auto_bid,
                    'services'     => $driver->services,
                    'latitude'     => $driver->latitude,
                    'longitude'    => $driver->longitude,
                    'last_active'  => $driver->last_active,
                    'created_at'   => $driver->created_at,
                    'updated_at'   => $driver->updated_at,
                ] : null,
            ]
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user'    => $user,
            'driver'  => $user->driver,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }
    // ==================== CHANGE PASSWORD ====================
    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:6|confirmed',  // Pastikan ada 'new_password_confirmation' di request
        ]);

        $user = $request->user();

        if (!Hash::check($request->old_password, $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => ['Password lama salah.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah.',
        ]);
    }
}