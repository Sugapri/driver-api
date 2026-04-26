<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Ambil data profil user (penumpang)
     */
    public function me()
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'phone'     => $user->phone,
                'photo_url' => $user->photo_url,
                'role'      => $user->role,
                'balance'   => $user->balance ?? 0, // Mengambil dari DB jika ada, jika tidak 0
            ]
        ]);
    }

    /**
     * Perbarui profil user (penumpang)
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name'  => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string',
        ]);

        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('phone')) $user->phone = $request->phone;
        
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data'    => $user
        ]);
    }

    /**
     * Upload foto profil user (penumpang)
     */
    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'image' => 'required|string', // Base64
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

        $imageName = 'profile_user_' . $user->id . '_' . time() . '.jpg';
        $path = 'uploads/profiles/' . $imageName;

        Storage::disk('public')->put($path, $imageData);

        $photoUrl = asset('storage/' . $path);
        $user->update(['photo_url' => $photoUrl]);

        return response()->json([
            'success' => true,
            'photo_url' => $photoUrl,
        ]);
    }
}
