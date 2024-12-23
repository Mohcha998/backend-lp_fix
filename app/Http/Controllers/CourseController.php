<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\SchedulePrg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    // Create a new course
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'id_program' => 'required|exists:programs,id',
            'price' => 'required|integer',
            'course_type' => 'required|integer',
            'schedule' => 'required|array',
            'schedule.*.module' => 'required|integer',
            'schedule.*.months' => 'required|array',
            'schedule.*.start_date' => 'required|date',
            'schedule.*.end_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // $course_type = $request->schedule[0]['module'] <= 4 ? 1 : 2;

        $course = Course::create([
            'name' => $request->name,
            'id_program' => $request->id_program,
            'price' => $request->price,
            'course_type' => $request->course_type,
            'schedule' => $request->schedule,
        ]);

        foreach ($request->schedule as $scheduleData) {
            foreach ($scheduleData['months'] as $month) {
                SchedulePrg::create([
                    'course_id' => $course->id,
                    'month' => $month,
                    'year' => now()->year,
                    'module' => $scheduleData['module'],
                    'start_date' => $scheduleData['start_date'],
                    'end_date' => $scheduleData['end_date'],
                ]);
            }
        }

        return response()->json(['message' => 'Course created successfully', 'course' => $course], 201);
    }

    public function get_bydate()
    {
        $modul = Course::where('name', 'Modul')
            ->orderBy('created_at', 'desc')
            ->first();

        $fullProgram = Course::where('name', 'Fullprogram')
            ->orderBy('created_at', 'desc')
            ->first();

        $courses = collect([$modul, $fullProgram])->filter();


        return response()->json($courses, 200);
    }

    public function index()
    {
        return response()->json(Course::all());
    }

    public function show($id)
    {
        $course = Course::with('schedulePrg')->find($id);

        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }

        return response()->json($course, 200);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'id_program' => 'sometimes|exists:programs,id',
            'price' => 'sometimes|integer',
            'course_type' => 'sometimes|integer',
            'schedule' => 'sometimes|array',
            'schedule.*.module' => 'sometimes|integer',
            'schedule.*.months' => 'sometimes|array',
            'schedule.*.start_date' => 'sometimes|date',
            'schedule.*.end_date' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $course = Course::find($id);

        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }

        $course_type = $request->schedule[0]['module'] <= 4 ? 1 : 2;

        $course->update([
            'name' => $request->name,
            'id_program' => $request->id_program,
            'price' => $request->price,
            'course_type' => $course_type,
            'schedule' => $request->schedule,
        ]);

        SchedulePrg::where('course_id', $id)->delete();

        foreach ($request->schedule as $scheduleData) {
            foreach ($scheduleData['months'] as $month) {
                SchedulePrg::create([
                    'course_id' => $course->id,
                    'month' => $month,
                    'year' => now()->year,
                    'module' => $scheduleData['module'],
                    'start_date' => $scheduleData['start_date'],
                    'end_date' => $scheduleData['end_date'],
                ]);
            }
        }

        return response()->json(['message' => 'Course updated successfully', 'course' => $course], 200);
    }

    public function destroy($id)
    {
        $course = Course::find($id);

        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }

        SchedulePrg::where('course_id', $id)->delete();

        $course->delete();

        return response()->json(['message' => 'Course deleted successfully'], 200);
    }
}
