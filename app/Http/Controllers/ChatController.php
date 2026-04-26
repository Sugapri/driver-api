<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function getMessages($orderId)
    {
        $order = Order::findOrFail($orderId);
        
        // Pastikan user atau driver terlibat dalam order ini
        $userId = Auth::id();
        $driverId = Auth::user()->driver?->id;

        if ($order->user_id !== $userId && $order->driver_id !== $driverId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this chat.'
            ], 403);
        }

        $messages = ChatMessage::where('order_id', $orderId)
                        ->orderBy('created_at', 'asc')
                        ->get();

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    public function store(Request $request, $orderId)
    {
        $request->validate([
            'text' => 'required|string|max:1000',
            'senderType' => 'required|in:driver,user'
        ]);

        $order = Order::findOrFail($orderId);

        // Pastikan user atau driver terlibat dalam order ini
        $userId = Auth::id();
        $driverId = Auth::user()->driver?->id;

        if ($order->user_id !== $userId && $order->driver_id !== $driverId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this chat.'
            ], 403);
        }

        $message = ChatMessage::create([
            'order_id' => $orderId,
            'sender_type' => $request->senderType,
            'text' => $request->text,
        ]);

        return response()->json([
            'success' => true,
            'data' => $message
        ]);
    }
}
