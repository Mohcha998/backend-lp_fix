<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InvitonalCode;
use App\Models\ProspectParent;

class inviteController extends Controller
{
    public function checkInvitationalCode(Request $request)
    {
        try {
            $validated = $request->validate([
                'voucher_code' => 'required|string'
            ]);

            $invitationalCode = InvitonalCode::where('voucher_code', $validated['voucher_code'])->first();

            if (!$invitationalCode) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Invalid invitational code'
                ], 404);
            }

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

    public function create(Request $request)
    {
        $validated = $request->validate([
            'id_cabang' => 'required|integer',
            'voucher_code' => 'required|string|unique:invitonal_codes,voucher_code',
            'qty' => 'nullable|integer',
            'status_voc' => 'nullable|boolean',
            'type' => 'nullable|integer'
        ]);

        $invitonalCode = InvitonalCode::create($validated);

        return response()->json([
            'success' => true,
            'data' => $invitonalCode
        ], 201);
    }

    public function index()
    {
        $invitonalCodes = InvitonalCode::all();

        return response()->json([
            'success' => true,
            'data' => $invitonalCodes
        ]);
    }

    public function show($id)
    {
        $invitonalCode = InvitonalCode::find($id);

        if (!$invitonalCode) {
            return response()->json([
                'success' => false,
                'message' => 'Invitational code not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $invitonalCode
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_cabang' => 'sometimes|required|integer',
            'voucher_code' => 'sometimes|required|string|unique:invitonal_codes,voucher_code,' . $id,
            'qty' => 'nullable|integer',
            'status_voc' => 'nullable|boolean',
            'type' => 'nullable|integer'
        ]);

        $invitonalCode = InvitonalCode::find($id);

        if (!$invitonalCode) {
            return response()->json([
                'success' => false,
                'message' => 'Invitational code not found'
            ], 404);
        }

        $invitonalCode->update($validated);

        return response()->json([
            'success' => true,
            'data' => $invitonalCode
        ]);
    }

    public function delete($id)
    {
        $invitonalCode = InvitonalCode::find($id);

        if (!$invitonalCode) {
            return response()->json([
                'success' => false,
                'message' => 'Invitational code not found'
            ], 404);
        }

        $invitonalCode->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invitational code deleted successfully'
        ]);
    }

    public function validateVoucher(Request $request)
    {
        $request->validate([
            'voucher_code' => 'required|string',
        ]);

        $user = auth()->user();
        $idParent = $user->parent_id;

        $parent = ProspectParent::with('program')->find($user->parent_id);

        $voucher = InvitonalCode::where('voucher_code', $request->voucher_code)
            ->where('status_voc', 1)
            ->where('type', 2)
            ->where('id_cabang', $parent->id_cabang)
            ->first();

        if (!$voucher) {
            return response()->json(['message' => 'Voucher not valid or expired'], 400);
        }

        if ($voucher->qty == 0) {
            return response()->json(['message' => 'Voucher has expired'], 400);
        }

        return response()->json([
            'diskon' => $voucher->diskon ?? 0,
        ], 200);
    }
}
