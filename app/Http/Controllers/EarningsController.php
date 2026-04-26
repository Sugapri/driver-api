<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EarningsController extends Controller
{
    public function summary()
    {
        $driverId = Auth::user()->driver?->id;

        if (!$driverId) {
            return response()->json([
                'success' => false,
                'message' => 'Anda bukan driver'
            ], 403);
        }

        // Hitung pendapatan hari ini
        $today = Order::where('driver_id', $driverId)
                    ->where('status', 'completed')
                    ->whereDate('completed_at', Carbon::today())
                    ->sum('driver_total');

        // Hitung pendapatan minggu ini (7 hari terakhir termasuk hari ini)
        $week = Order::where('driver_id', $driverId)
                    ->where('status', 'completed')
                    ->where('completed_at', '>=', Carbon::now()->subDays(7))
                    ->sum('driver_total');

        // Hitung pendapatan bulan ini
        $month = Order::where('driver_id', $driverId)
                    ->where('status', 'completed')
                    ->whereMonth('completed_at', Carbon::now()->month)
                    ->whereYear('completed_at', Carbon::now()->year)
                    ->sum('driver_total');

        // Rangkuman Masuk/Keluar (7 hari terakhir)
        $totalIncome = Transaction::where('driver_id', $driverId)
                                    ->where('type', 'income')
                                    ->where('created_at', '>=', Carbon::now()->subDays(7))
                                    ->sum('amount');
        
        $totalExpense = Transaction::where('driver_id', $driverId)
                                    ->where('type', 'expense')
                                    ->where('created_at', '>=', Carbon::now()->subDays(7))
                                    ->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'today' => (float)$today,
                'week' => (float)$week,
                'month' => (float)$month,
                'week_income' => (float)$totalIncome,
                'week_expense' => (float)abs($totalExpense)
            ]
        ]);
    }

    public function transactions()
    {
        $driverId = Auth::user()->driver?->id;

        if (!$driverId) {
            return response()->json([
                'success' => false,
                'message' => 'Anda bukan driver'
            ], 403);
        }

        $transactions = Transaction::where('driver_id', $driverId)
                                    ->where('created_at', '>=', Carbon::now()->subDays(7))
                                    ->latest()
                                    ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }
}
