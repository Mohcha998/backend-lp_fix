<?php

namespace App\Http\Controllers;

use App\Models\ProspectParent;
use App\Models\Student;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProspectParentController extends Controller
{
    public function index()
    {
        $prospects = ProspectParent::select(
            'prospect_parents.*',
            'branches.name as branch_name',
            'programs.name as program_name',
            'branches.kode_cabang as cabang',
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
            'branches.kode_cabang as cabang',
            'payment__sps.status_pembayaran as status'
        )
            ->leftJoin('branches', 'prospect_parents.id_cabang', '=', 'branches.id')
            ->leftJoin('programs', 'prospect_parents.id_program', '=', 'programs.id')
            ->leftJoin('payment__sps', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->distinct()
            ->whereNull('tgl_checkin')
            ->where('payment__sps.payment_type', '=', 1)
            ->where('payment__sps.status_pembayaran', '=', 1)
            ->whereNull('prospect_parents.tgl_checkin')
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
            'branches.kode_cabang as cabang',
            'users.name as user_name',
            'users.email as user_email'
        )
            ->leftJoin('branches', 'prospect_parents.id_cabang', '=', 'branches.id')
            ->leftJoin('programs', 'prospect_parents.id_program', '=', 'programs.id')
            ->leftJoin('users', 'users.parent_id', '=', 'prospect_parents.id')
            ->leftJoin('payment__sps', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->where('payment__sps.payment_type', '=', 1)
            ->where('payment__sps.status_pembayaran', '=', 1 && 'payment__sps.status_pembayaran', '=', 1)
            // ->whereNull('prospect_parents.tgl_checkin')
            ->whereNull('users.id')
            ->whereDoesntHave('payments', function ($query) {
                $query->where('payment__sps.payment_type', 2);
            })
            ->distinct()
            ->orderBy('prospect_parents.id', 'asc')
            ->get();

        return response()->json($prospects, 200);
    }

    public function callNoSp()
    {
        $prospects = ProspectParent::select(
            'prospect_parents.*',
            'branches.name as branch_name',
            'programs.name as program_name',
            'payment__sps.status_pembayaran as status',
            'branches.kode_cabang as cabang',
            'users.name as user_name',
            'users.email as user_email'
        )
            ->leftJoin('branches', 'prospect_parents.id_cabang', '=', 'branches.id')
            ->leftJoin('programs', 'prospect_parents.id_program', '=', 'programs.id')
            ->leftJoin('users', 'users.parent_id', '=', 'prospect_parents.id')
            ->leftJoin('payment__sps', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('payment__sps.payment_type', 1)
                        ->where('payment__sps.status_pembayaran', 3);
                })->orWhere(function ($q) {
                    $q->where('payment__sps.payment_type', 2)
                        ->where('payment__sps.status_pembayaran', 0);
                });
            })
            // ->whereNull('prospect_parents.tgl_checkin')
            // ->whereNull('users.id')
            ->distinct()
            ->orderBy('prospect_parents.id', 'asc')
            ->get();

        return response()->json($prospects, 200);
    }

    public function callInterest()
    {
        $prospects = ProspectParent::select(
            'prospect_parents.*',
            'branches.name as branch_name',
            'programs.name as program_name',
            'payment__sps.status_pembayaran as status',
            'payment__sps.id as id_payment',
            'branches.kode_cabang as cabang',
            'payment__sps.course as course',
            'payment__sps.num_children as children_count',
            'payment__sps.total as total',
            'payment__sps.status_pembayaran as status_pembayaran',
            'users.name as user_name',
            'users.email as user_email',
            'users.id as user_id'
        )
            ->leftJoin('branches', 'prospect_parents.id_cabang', '=', 'branches.id')
            ->leftJoin('programs', 'prospect_parents.id_program', '=', 'programs.id')
            ->leftJoin('users', 'users.parent_id', '=', 'prospect_parents.id')
            ->leftJoin('payment__sps', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->where('payment__sps.payment_type', 2)
            ->where('payment_sps.status_pembayaran', 1)
            ->whereNotNull('users.id')
            // ->whereNotNull('prospect_parents.tgl_checkin')
            ->distinct()
            ->orderBy('prospect_parents.id', 'asc')
            ->get();

        foreach ($prospects as $prospect) {
            $prospect->children = Student::where('user_id', $prospect->user_id)
                ->get(['id as children', 'name as nama_murid', 'tgl_lahir', 'email as email_murid', 'phone as tlp_murid']);
        }

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
            'call2' => 'nullable|integer|min:0',
            'call3' => 'nullable|integer|min:0',
            'call4' => 'nullable|integer|min:0',
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
            'call2' => 'nullable|integer|min:0',
            'call3' => 'nullable|integer|min:0',
            'call4' => 'nullable|integer|min:0',
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

    public function countPaidToday()
    {
        // Mendapatkan tanggal hari ini
        $today = Carbon::now('Asia/Jakarta')->toDateString(); // Memastikan waktu dalam zona waktu yang tepat


        // Menghitung jumlah pembayaran yang dilakukan hari ini, berdasarkan created_at atau updated_at
        $count = DB::table('payment__sps')
            ->join('prospect_parents', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->where('payment__sps.payment_type', 1)
            ->where('payment__sps.status_pembayaran', 1) // Hanya status pembayaran yang sudah selesai
            ->where(function ($query) use ($today) {
                $query->whereDate('payment__sps.created_at', '=', $today) // Filter berdasarkan created_at
                    ->orWhereDate('payment__sps.updated_at', '=', $today); // Atau berdasarkan updated_at
            })
            ->count(); // Menghitung jumlah pembayaran

        return response()->json(['count' => $count], 200);
    }

    // Count
    public function countPendingToday()
    {
        // Mendapatkan tanggal hari ini
        $today = Carbon::today();

        // Menghitung jumlah pembayaran yang dilakukan hari ini, berdasarkan created_at atau updated_at
        $count = DB::table('payment__sps')
            ->join('prospect_parents', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->where('payment__sps.payment_type', 1)
            ->where('payment__sps.status_pembayaran', 0) // Hanya status pembayaran yang sudah selesai
            ->where(function ($query) use ($today) {
                $query->whereDate('payment__sps.created_at', '=', $today) // Filter berdasarkan created_at
                    ->orWhereDate('payment__sps.updated_at', '=', $today); // Atau berdasarkan updated_at
            })
            ->count(); // Menghitung jumlah pembayaran

        return response()->json(['count' => $count], 200);
    }

    public function countLeadsToday()
    {
        // Mendapatkan tanggal hari ini
        $today = Carbon::today();

        // Menghitung jumlah pembayaran yang dilakukan hari ini, berdasarkan created_at atau updated_at
        $count = DB::table('payment__sps')
            ->join('prospect_parents', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->where('payment__sps.payment_type', 1)
            ->whereIn('payment__sps.status_pembayaran', [0, 1, 2]) // Hanya status pembayaran yang sudah selesai
            ->where(function ($query) use ($today) {
                $query->whereDate('payment__sps.created_at', '=', $today) // Filter berdasarkan created_at
                    ->orWhereDate('payment__sps.updated_at', '=', $today); // Atau berdasarkan updated_at
            })
            ->count(); // Menghitung jumlah pembayaran

        return response()->json(['count' => $count], 200);
    }


    // Count
    public function countFreeToday()
    {
        // Mendapatkan tanggal hari ini
        $today = Carbon::today();

        // Menghitung jumlah pembayaran yang dilakukan hari ini, berdasarkan created_at atau updated_at
        $count = DB::table('payment__sps')
            ->join('prospect_parents', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->where('payment__sps.payment_type', 1)
            ->where('payment__sps.status_pembayaran', 1) // Hanya status pembayaran yang sudah selesai
            ->where('payment__sps.total', 0)
            ->where(function ($query) use ($today) {
                $query->whereDate('payment__sps.created_at', '=', $today) // Filter berdasarkan created_at
                    ->orWhereDate('payment__sps.updated_at', '=', $today); // Atau berdasarkan updated_at
            })
            ->count(); // Menghitung jumlah pembayaran

        return response()->json(['count' => $count], 200);
    }

    // Count
    public function countExpiredToday()
    {
        // Mendapatkan tanggal hari ini
        $today = Carbon::today();

        // Menghitung jumlah pembayaran yang dilakukan hari ini, berdasarkan created_at atau updated_at
        $count = DB::table('payment__sps')
            ->join('prospect_parents', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->where('payment__sps.payment_type', 1)
            ->where('payment__sps.status_pembayaran', 2) // Hanya status pembayaran yang sudah selesai
            ->where(function ($query) use ($today) {
                $query->whereDate('payment__sps.created_at', '=', $today) // Filter berdasarkan created_at
                    ->orWhereDate('payment__sps.updated_at', '=', $today); // Atau berdasarkan updated_at
            })
            ->count(); // Menghitung jumlah pembayaran

        return response()->json(['count' => $count], 200);
    }

    // Count
    public function countHadirToday()
    {
        $today = Carbon::today();

        $count = DB::table('payment__sps')
            ->join('prospect_parents', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->where('payment__sps.payment_type', 1)
            ->where('payment__sps.status_pembayaran', 1)
            ->whereNotNull('prospect_parents.tgl_checkin') // Memastikan ada tgl_checkin
            ->whereDate('prospect_parents.tgl_checkin', '=', $today) // Mengambil data berdasarkan tgl_checkin yang sesuai dengan hari ini
            ->where(function ($query) use ($today) {
                $query->whereDate('prospect_parents.tgl_checkin', '=', $today)
                    ->orWhereDate('prospect_parents.tgl_checkin', '=', $today);
            })
            ->count();

        return response()->json(['count' => $count], 200);
    }
}
