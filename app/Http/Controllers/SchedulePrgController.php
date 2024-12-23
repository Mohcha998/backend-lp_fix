<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SchedulePrg;

class SchedulePrgController extends Controller
{
    public function index()
    {
        $schedules = SchedulePrg::all();
        return response()->json($schedules);
    }

    public function store(Request $request)
    {
        $schedule = SchedulePrg::create($request->only('course_id', 'modul', 'month'));
        return response()->json($schedule);
    }
}
