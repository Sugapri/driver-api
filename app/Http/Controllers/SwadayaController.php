<?php

namespace App\Http\Controllers;

use App\Models\SwadayaProgram;
use App\Models\SwadayaParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SwadayaController extends Controller
{
    /**
     * Ambil semua program swadaya untuk driver tertentu
     */
    public function index($driverId)
    {
        try {
            $programs = SwadayaProgram::all()->map(function ($program) use ($driverId) {
                $participant = SwadayaParticipant::where('driver_id', $driverId)
                    ->where('swadaya_program_id', $program->id)
                    ->first();

                return [
                    'id' => $program->id,
                    'name' => $program->name,
                    'description' => $program->description,
                    'reward' => $program->reward,
                    'target' => $program->target,
                    'deadline_days' => $program->deadline_days,
                    'is_joined' => $participant !== null,
                    'progress' => $participant ? $participant->progress : 0,
                    'status' => $participant ? $this->getStatus($participant) : 'available',
                    'joined_at' => $participant ? $participant->joined_at : null,
                ];
            });

            return response()->json([
                'success' => true,
                'programs' => $programs
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data program',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ambil detail program swadaya
     */
    public function show($programId)
    {
        try {
            $program = SwadayaProgram::findOrFail($programId);

            return response()->json([
                'success' => true,
                'program' => [
                    'id' => $program->id,
                    'name' => $program->name,
                    'description' => $program->description,
                    'reward' => $program->reward,
                    'target' => $program->target,
                    'deadline_days' => $program->deadline_days,
                    'terms_conditions' => $program->terms_conditions,
                    'created_at' => $program->created_at,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Program tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Driver bergabung dengan program swadaya
     */
    public function join(Request $request, $driverId)
    {
        try {
            $validated = $request->validate([
                'program_id' => 'required|exists:swadaya_programs,id',
            ]);

            // Cek apakah sudah pernah join
            $existing = SwadayaParticipant::where('driver_id', $driverId)
                ->where('swadaya_program_id', $validated['program_id'])
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah bergabung dengan program ini'
                ], 400);
            }

            // Cek apakah program masih aktif
            $program = SwadayaProgram::findOrFail($validated['program_id']);
            if (!$program->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Program sudah tidak aktif'
                ], 400);
            }

            // Buat participant baru
            $participant = SwadayaParticipant::create([
                'driver_id' => $driverId,
                'swadaya_program_id' => $validated['program_id'],
                'progress' => 0,
                'joined_at' => now(),
                'deadline_at' => now()->addDays($program->deadline_days),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil bergabung dengan program',
                'participant' => $participant
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal bergabung dengan program',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update progress program (dipanggil oleh sistem)
     */
    public function updateProgress(Request $request, $driverId)
    {
        try {
            $validated = $request->validate([
                'program_id' => 'required|exists:swadaya_programs,id',
                'progress' => 'required|integer|min:0',
            ]);

            $participant = SwadayaParticipant::where('driver_id', $driverId)
                ->where('swadaya_program_id', $validated['program_id'])
                ->firstOrFail();

            // Update progress
            $participant->progress = $validated['progress'];

            // Cek apakah sudah selesai
            $program = SwadayaProgram::find($validated['program_id']);
            if ($participant->progress >= $program->target) {
                $participant->status = 'completed';
                $participant->completed_at = now();
                
                // Berikan reward ke driver (bisa ditambahkan logika)
                // $this->giveReward($driverId, $program->reward);
            }

            $participant->save();

            return response()->json([
                'success' => true,
                'message' => 'Progress diperbarui',
                'participant' => $participant
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal update progress'
            ], 500);
        }
    }

    /**
     * Riwayat program yang diikuti driver
     */
    public function history($driverId)
    {
        try {
            $participants = SwadayaParticipant::with('program')
                ->where('driver_id', $driverId)
                ->orderBy('joined_at', 'desc')
                ->get()
                ->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'program_name' => $p->program->name,
                        'reward' => $p->program->reward,
                        'progress' => $p->progress,
                        'target' => $p->program->target,
                        'status' => $p->status,
                        'joined_at' => $p->joined_at,
                        'completed_at' => $p->completed_at,
                        'deadline_at' => $p->deadline_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'history' => $participants
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat'
            ], 500);
        }
    }

    /**
     * Helper: Get status participant
     */
    private function getStatus($participant)
    {
        if ($participant->status === 'completed') {
            return 'completed';
        }
        
        if (now()->greaterThan($participant->deadline_at)) {
            return 'expired';
        }
        
        return 'active';
    }
}