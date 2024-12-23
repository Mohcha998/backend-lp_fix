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
            ->leftJoin('payment_sps', 'prospect_parents.id', '=', 'payment_sps.id_parent')
            ->get();

        return response()->json($prospects, 200);
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
