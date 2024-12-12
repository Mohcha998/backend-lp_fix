<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        return response()->json(Branch::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_cabang' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'kota' => 'required|string|max:255',
        ]);

        $branch = Branch::create($validated);

        return response()->json($branch, 201);
    }

    public function show(Branch $branch)
    {
        return response()->json($branch);
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'kode_cabang' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'kota' => 'required|string|max:255',
        ]);

        $branch->update($validated);

        return response()->json($branch);
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();

        return response()->json(['message' => 'Branch deleted']);
    }
}
