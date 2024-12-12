<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index()
    {
        return response()->json(Program::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $program = Program::create($validated);

        return response()->json($program, 201);
    }

    public function show(Program $program)
    {
        return response()->json($program);
    }

    public function update(Request $request, Program $program)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $program->update($validated);

        return response()->json($program);
    }

    public function destroy(Program $program)
    {
        $program->delete();

        return response()->json(['message' => 'Program deleted']);
    }
}
