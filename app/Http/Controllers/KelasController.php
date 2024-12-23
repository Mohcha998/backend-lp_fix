<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KelasController extends Controller
{
    /**
     * Fetch all classes.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $kelas = Kelas::all();
            return response()->json($kelas, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch classes.'], 500);
        }
    }

    /**
     * Create a new class.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id_schedule' => 'required|exists:schedule_prgs,id',
            'id_coach' => 'required|integer',
            'name' => 'required|string',
            'day' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
        ]);

        try {
            $kelas = Kelas::create($request->all());
            return response()->json($kelas, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create class.'], 500);
        }
    }

    /**
     * Update an existing class.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'id_schedule' => 'sometimes|exists:schedule_prgs,id',
            'id_coach' => 'sometimes|integer',
            'name' => 'sometimes|string',
            'day' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
        ]);

        try {
            $kelas = Kelas::findOrFail($id);
            $kelas->update($request->all());
            return response()->json($kelas, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update class.'], 500);
        }
    }

    /**
     * Delete a class.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $kelas = Kelas::findOrFail($id);
            $kelas->delete();
            return response()->json(['message' => 'Class deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete class.'], 500);
        }
    }
}
