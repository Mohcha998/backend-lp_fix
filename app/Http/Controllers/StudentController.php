<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $students = Student::where('user_id', $user->id)->get();
        return response()->json($students);
    }

    public function show($id)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->where('id', $id)->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        return response()->json($student);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->where('id', $id)->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        Log::info('Request data:', $request->all());

        $validatedData = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string',
            'tgl_lahir' => 'nullable|date',
            'asal_sekolah' => 'nullable|string|max:255',
            'perubahan' => 'nullable|string|max:255',
            'kelebihan' => 'nullable|string|max:255',
            'dirawat' => 'nullable|boolean',
            'kondisi' => 'nullable|string|max:255',
            'tindakan' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:255',
            'hubungan_eme' => 'nullable|string|max:255',
            'emergency_call' => 'nullable|string|max:255',
            'status' => 'nullable|boolean',
            'id_branch' => 'nullable|integer',
            'id_kelas' => 'nullable|string',
        ]);

        // Menangani kemungkinan data boolean yang berupa string
        if (isset($validatedData['dirawat'])) {
            $validatedData['dirawat'] = filter_var($validatedData['dirawat'], FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($validatedData['status'])) {
            $validatedData['status'] = filter_var($validatedData['status'], FILTER_VALIDATE_BOOLEAN);
        }

        try {
            $student->update($validatedData);
            return response()->json($student);
        } catch (\Exception $e) {
            Log::error('Update error:', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }


    public function destroy($id)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->where('id', $id)->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        $student->delete();

        return response()->json(['message' => 'Student deleted successfully']);
    }
}
