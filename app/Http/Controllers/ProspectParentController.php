<?php

namespace App\Http\Controllers;

use App\Models\ProspectParent;
use Illuminate\Http\Request;

class ProspectParentController extends Controller
{
    public function index()
    {
        $prospects = ProspectParent::select(
            'prospect_parents.*',
            'branches.name as branch_name',
            'programs.name as program_name',
            'payment__sps.status_pembayaran as status'
        )
            ->leftJoin('branches', 'prospect_parents.id_cabang', '=', 'branches.id')
            ->leftJoin('programs', 'prospect_parents.id_program', '=', 'programs.id')
            ->leftJoin('payment__sps', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->distinct()
            ->where('payment__sps.payment_type', '=', 1)
            ->whereIn('payment__sps.status_pembayaran', [0, 2])
            ->orderBy('id', 'asc')
            ->get();

        return response()->json($prospects, 200);
    }

    public function callSP()
    {
        $prospects = ProspectParent::select(
            'prospect_parents.*',
            'branches.name as branch_name',
            'programs.name as program_name',
            'payment__sps.status_pembayaran as status'
        )
            ->leftJoin('branches', 'prospect_parents.id_cabang', '=', 'branches.id')
            ->leftJoin('programs', 'prospect_parents.id_program', '=', 'programs.id')
            ->leftJoin('payment__sps', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->distinct()
            ->whereNull('tgl_checkin')
            ->where('payment__sps.payment_type', '=', 1)
            ->where('payment__sps.status_pembayaran', '=', 1)
            ->orderBy('id', 'asc')
            ->get();

        return response()->json($prospects, 200);
    }

    public function callPrg()
    {
        $prospects = ProspectParent::select(
            'prospect_parents.*',
            'branches.name as branch_name',
            'programs.name as program_name',
            'payment__sps.status_pembayaran as status',
            'users.name as user_name',
            'users.email as user_email'
        )
            ->leftJoin('branches', 'prospect_parents.id_cabang', '=', 'branches.id')
            ->leftJoin('programs', 'prospect_parents.id_program', '=', 'programs.id')
            ->leftJoin('users', 'users.parent_id', '=', 'prospect_parents.id')
            ->leftJoin('payment__sps', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->where(function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('payment__sps.payment_type', 1)
                        ->whereNotNull('prospect_parents.tgl_checkin');
                })->orWhere(function ($subQuery) {
                    $subQuery->where('payment__sps.payment_type', 2)
                        ->where('payment__sps.status_pembayaran', [0, 2]);
                });
            })
            ->distinct()
            ->orderBy('prospect_parents.id', 'asc')
            ->get();

        return response()->json($prospects, 200);
    }


    public function checkin($id)
    {
        $prospect = ProspectParent::find($id);

        if (!$prospect) {
            return response()->json(['message' => 'Prospect not found'], 404);
        }

        $prospect->tgl_checkin = now();
        $prospect->save();

        return response()->json(['message' => 'Check-in successful', 'prospect' => $prospect]);
    }


    public function countProspectsWithPaymentTypeOne()
    {
        $count = ProspectParent::select()
            ->leftJoin('payment__sps', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->where('payment__sps.payment_type', '=', 1)
            ->whereIn('payment__sps.status_pembayaran', [0, 2, null])
            ->count();

        return response()->json(['count' => $count], 200);
    }

    public function countProspectsPending()
    {
        $count = ProspectParent::select()
            ->leftJoin('payment__sps', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->where('payment__sps.payment_type', '=', 1)
            ->where('payment__sps.status_pembayaran', '=', 0)
            ->count();

        return response()->json(['count' => $count], 200);
    }

    public function countProspectsExpired()
    {
        $count = ProspectParent::select()
            ->leftJoin('payment__sps', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->where('payment__sps.payment_type', '=', 1)
            ->where('payment__sps.status_pembayaran', '=', 2)
            ->count();

        return response()->json(['count' => $count], 200);
    }

    public function countProspectsPaid()
    {
        $count = ProspectParent::select()
            ->leftJoin('payment__sps', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->where('payment__sps.payment_type', '=', 1)
            ->where('payment__sps.status_pembayaran', '=', 1)
            ->count();

        return response()->json(['count' => $count], 200);
    }

    public function show($id)
    {
        $prospect = ProspectParent::findOrFail($id);
        return response()->json($prospect);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:prospect_parents,email',
            'phone' => 'nullable|numeric',
            'source' => 'nullable|string|max:255',
            'id_cabang' => 'nullable|exists:branches,id',
            'id_program' => 'nullable|exists:programs,id',
            'call' => 'nullable|integer|min:0',
            'tgl_checkin' => 'nullable|date',
            'invitional_code' => 'nullable|string|max:255',
        ]);

        $prospect = ProspectParent::create($validated);

        return response()->json($prospect, 201);
    }

    public function update(Request $request, $id)
    {
        $prospectParent = ProspectParent::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:prospect_parents,email,' . $prospectParent->id,
            'phone' => 'nullable|numeric',
            'source' => 'nullable|string|max:255',
            'id_cabang' => 'nullable|exists:branches,id',
            'id_program' => 'nullable|exists:programs,id',
            'call' => 'nullable|integer|min:0',
            'tgl_checkin' => 'nullable|date',
            'invitional_code' => 'nullable|string|max:255',
        ]);

        $prospectParent->update($validated);

        return response()->json($prospectParent, 200);
    }


    public function destroy($id)
    {
        $prospectParent = ProspectParent::findOrFail($id);

        $prospectParent->delete();

        return response()->json(['message' => 'Prospect parent deleted successfully'], 200);
    }
}
