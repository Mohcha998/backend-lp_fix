<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function show($id)
    {
        $branch = Branch::findOrFail($id);
        return response()->json($branch);
    }


    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);

        $validated = $request->validate([
            'kode_cabang' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'kota' => 'required|string|max:255',
        ]);

        $branch->update($validated);

        return response()->json($branch);
    }


    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);

        $branch->delete();

        return response()->json(['message' => 'Branch deleted']);
    }

    public function branch_std()
    {
        $branch = Branch::select(
            'branches.*',
            DB::raw('COUNT(students.id) as total_students'),
        )
            ->leftJoin('students', 'branches.id', '=', 'students.id_branch')
            ->groupBy('branches.id')
            ->orderBy('total_students', 'desc')
            ->get();

        return response()->json($branch, 200);
    }

    public function branch_total()
    {
        $branch = Branch::select(
            'branches.*',
            DB::raw('(SELECT COUNT(*) FROM students WHERE students.status = 1) as total_students_all')
        )
            ->leftJoin('students', 'branches.id', '=', 'students.id_branch')
            ->groupBy('branches.id')
            ->orderBy('branches.id', 'asc')
            ->get();

        return response()->json($branch, 200);
    }

    public function topThreeBranches()
    {
        $branches = Branch::select(
            'branches.*',
            DB::raw('COUNT(CASE WHEN students.status = 1 THEN students.id END) as total_students')
        )
            ->leftJoin('students', 'branches.id', '=', 'students.id_branch')
            ->groupBy('branches.id')
            ->orderBy('total_students', 'desc')
            ->take(3)
            ->get();

        return response()->json($branches, 200);
    }

    public function branch_revenue()
    {
        $branches = Branch::select(
            'branches.*',
            DB::raw('CAST(SUM(payment__sps.total) AS UNSIGNED) as total_revenue')
        )
            ->leftJoin('prospect_parents', 'branches.id', '=', 'prospect_parents.id_cabang')
            ->leftJoin('payment__sps', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->groupBy('branches.id')
            ->orderBy('total_revenue', 'desc')
            ->get();

        $totalRevenue = $branches->sum('total_revenue');

        return response()->json([
            'branches' => $branches,
            'total_revenue' => $totalRevenue,
        ], 200);
    }

    public function branch_revtop()
    {
        $branches = Branch::select(
            'branches.*',
            DB::raw('SUM(payment__sps.total) as total_revenue')
        )
            ->leftJoin('prospect_parents', 'branches.id', '=', 'prospect_parents.id_cabang')
            ->leftJoin('payment__sps', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->groupBy('branches.id')
            ->orderBy('total_revenue', 'desc')
            ->take(3)
            ->get();

        return response()->json($branches, 200);
    }

    // public function branch_revenue_month()
    // {
    //     $branches = Branch::select(
    //         'branches.*',
    //         DB::raw('CAST(SUM(payment__sps.total) AS UNSIGNED) as total_revenue')
    //     )
    //         ->leftJoin('prospect_parents', 'branches.id', '=', 'prospect_parents.id_cabang')
    //         ->leftJoin('payment__sps', 'prospect_parents.id', '=', 'payment__sps.id_parent')
    //         ->where->whereBetween('payment__sps.created_at', [
    //             now()->startOfMonth()->subMonths(2),
    //             now()->endOfMonth(),
    //         ])
    //         ->groupBy('branches.id')
    //         ->orderBy('total_revenue', 'desc')
    //         ->get();

    //     $totalRevenue = $branches->sum('total_revenue');

    //     return response()->json([
    //         'branches' => $branches,
    //         'total_revenue' => $totalRevenue,
    //     ], 200);
    // }

    public function branch_revenue_month()
    {
        $branches = Branch::select(
            'branches.*',
            DB::raw('CAST(SUM(payment__sps.total) AS UNSIGNED) as total_revenue')
        )
            ->leftJoin('prospect_parents', 'branches.id', '=', 'prospect_parents.id_cabang')
            ->leftJoin('payment__sps', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->whereBetween('payment__sps.created_at', [
                now()->startOfMonth()->subMonths(2),
                now()->endOfMonth(),
            ])
            ->groupBy('branches.id')
            ->orderBy('total_revenue', 'desc')
            ->get();

        $totalRevenue = $branches->sum('total_revenue');

        return response()->json([
            'branches' => $branches,
            'total_revenue' => $totalRevenue,
        ], 200);
    }

    public function branch_revtop_month()
    {
        $branches = Branch::select(
            'branches.*',
            DB::raw('SUM(payment__sps.total) as total_revenue')
        )
            ->leftJoin('prospect_parents', 'branches.id', '=', 'prospect_parents.id_cabang')
            ->leftJoin('payment__sps', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->whereBetween('payment__sps.created_at', [
                now()->startOfMonth()->subMonths(2),
                now()->endOfMonth(),
            ])
            ->groupBy('branches.id')
            ->orderBy('total_revenue', 'desc')
            ->take(3)
            ->get();

        return response()->json($branches, 200);
    }
}
