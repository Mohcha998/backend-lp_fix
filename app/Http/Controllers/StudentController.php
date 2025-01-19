<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{

    public function studentall()
    {
        $prospects = Student::select(
            'students.*',
            'branches.name as branch_name',
            'programs.name as program_name',
            'courses.name as course_name',
            'kelas.name as kelas_name'
        )
            ->leftJoin('branches', 'students.id_branch', '=', 'branches.id')
            ->leftJoin('programs', 'students.id_program', '=', 'programs.id')
            ->leftJoin('courses', 'students.id_course', '=', 'courses.id')
            ->leftJoin('kelas', 'students.id_kelas', '=', 'kelas.id')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json($prospects, 200);
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

    public function student_last_three_months()
    {
        $startDate1 = Carbon::now()->startOfMonth()->startOfDay()->toDateTimeString();
        $endDate1 = Carbon::now()->endOfMonth()->endOfDay()->toDateTimeString();

        $startDate2 = Carbon::now()->subMonth()->startOfMonth()->startOfDay()->toDateTimeString();
        $endDate2 = Carbon::now()->subMonth()->endOfMonth()->endOfDay()->toDateTimeString();

        $startDate3 = Carbon::now()->subMonths(2)->startOfMonth()->startOfDay()->toDateTimeString();
        $endDate3 = Carbon::now()->subMonths(2)->endOfMonth()->endOfDay()->toDateTimeString();

        $students = Student::selectRaw('
        SUM(CASE WHEN created_at >= ? AND created_at <= ? THEN 1 ELSE 0 END) as month_1,
        SUM(CASE WHEN created_at >= ? AND created_at <= ? THEN 1 ELSE 0 END) as month_2,
        SUM(CASE WHEN created_at >= ? AND created_at <= ? THEN 1 ELSE 0 END) as month_3
    ', [$startDate3, $endDate3, $startDate2, $endDate2, $startDate1, $endDate1])
            ->first();

        $month_1 = $students->month_1 ?? 0;
        $month_2 = $students->month_2 ?? 0;
        $month_3 = $students->month_3 ?? 0;

        $labels = [
            Carbon::now()->subMonths(2)->format('F Y'),
            Carbon::now()->subMonth()->format('F Y'),
            Carbon::now()->format('F Y'),
        ];

        $series = [
            [$month_1, $month_2, $month_3],
        ];

        return response()->json([
            'labels' => $labels,
            'series' => $series,
        ], 200);
    }
}
