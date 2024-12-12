<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InvitonalCode;

class inviteController extends Controller
{
    public function checkInvitationalCode(Request $request)
    {
        try {
            // Validasi request
            $validated = $request->validate([
                'voucher_code' => 'required|string'
            ]);

            // Cari kode undangan di database
            $invitationalCode = InvitonalCode::where('voucher_code', $validated['voucher_code'])->first();

            if (!$invitationalCode) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Invalid invitational code'
                ], 404);
            }

            // Pastikan status_voc bernilai 1
            if ($invitationalCode->status_voc === 1) {
                return response()->json([
                    'valid' => true,
                    'data' => $invitationalCode
                ]);
            }

            return response()->json([
                'valid' => false,
                'message' => 'Invitational code is inactive'
            ], 400);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'An error occurred',
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
