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
                // $query->where(function ($q) {
                //     $q->where('payment__sps.payment_type', 1)
                //         ->where('payment__sps.status_pembayaran', 3);
                // })
                $query->orWhere(function ($q) {
                    $q->where('payment__sps.payment_type', 2)
                        ->where('payment__sps.status_pembayaran', 1);
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
        // Ambil data prospek
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
            'payment__sps.file as gambar',
            'users.name as user_name',
            'users.email as user_email',
            'users.id as user_id',
        )
            ->leftJoin('branches', 'prospect_parents.id_cabang', '=', 'branches.id')
            ->leftJoin('programs', 'prospect_parents.id_program', '=', 'programs.id')
            ->leftJoin('users', 'users.parent_id', '=', 'prospect_parents.id')
            ->leftJoin('payment__sps', 'prospect_parents.id', '=', 'payment__sps.id_parent')
            ->where('payment__sps.payment_type', 2)
            ->where('payment__sps.status_pembayaran', 1)
            ->whereNotNull('users.id')
            ->orderBy('prospect_parents.id', 'asc')
            ->get();

        $prospectsGrouped = $prospects->groupBy('id');

        $finalProspects = [];

        foreach ($prospectsGrouped as $prospectGroup) {
            $prospectWithImage = $prospectGroup->firstWhere('gambar', '!=', null);

            if (!$prospectWithImage) {
                $prospectWithImage = $prospectGroup->first();
            }

            if ($prospectWithImage->gambar) {
                $prospectWithImage->gambar = asset('http://127.0.0.1:8000/' . $prospectWithImage->gambar);
            } else {
                $prospectWithImage->gambar = asset('storage/images/default.jpg');
            }

            $prospectWithImage->children = Student::where('user_id', $prospectWithImage->user_id)
                ->get(['id as children', 'name as nama_murid', 'tgl_lahir', 'email as email_murid', 'phone as tlp_murid']);

            $finalProspects[] = $prospectWithImage;
        }

        return response()->json($finalProspects, 200);
    }

    //Distinc

    // public function callInterest()
    // {
    //     $prospects = ProspectParent::select(
    //         'prospect_parents.id', // Pilih hanya kolom ID yang unik
    //         'prospect_parents.name', // Menambahkan kolom yang relevan dari prospect_parent
    //         'branches.name as branch_name',
    //         'programs.name as program_name',
    //         'payment__sps.status_pembayaran as status',
    //         'payment__sps.id as id_payment',
    //         'branches.kode_cabang as cabang',
    //         'payment__sps.course as course',
    //         'payment__sps.num_children as children_count',
    //         'payment__sps.total as total',
    //         'payment__sps.status_pembayaran as status_pembayaran',
    //         'payment__sps.file as gambar',
    //         'users.name as user_name',
    //         'users.email as user_email',
    //         'users.id as user_id',
    //     )
    //         ->leftJoin('branches', 'prospect_parents.id_cabang', '=', 'branches.id')
    //         ->leftJoin('programs', 'prospect_parents.id_program', '=', 'programs.id')
    //         ->leftJoin('users', 'users.parent_id', '=', 'prospect_parents.id')
    //         ->leftJoin('payment__sps', 'prospect_parents.id', '=', 'payment__sps.id_parent')
    //         ->where('payment__sps.payment_type', 2)
    //         ->where('payment__sps.status_pembayaran', 1)
    //         ->whereNotNull('users.id')
    //         ->distinct('prospect_parents.id') // Pastikan ID tidak duplikat
    //         ->orderBy('prospect_parents.id', 'asc')
    //         ->get();

    //     foreach ($prospects as $prospect) {
    //         if ($prospect->gambar) {
    //             $prospect->gambar = asset('http://127.0.0.1:8000/' . $prospect->gambar);
    //         } else {
    //             $prospect->gambar = asset('storage/images/default.jpg');
    //         }

    //         $prospect->children = Student::where('user_id', $prospect->user_id)
    //             ->get(['id as children', 'name as nama_murid', 'tgl_lahir', 'email as email_murid', 'phone as tlp_murid']);
    //     }

    //     return response()->json($prospects, 200);
    // }


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

    public function exportSignUp()
    {
        $results = DB::table('prospect_parents')
            ->leftJoin('branches', 'prospect_parents.id_cabang', '=', 'branches.id')  // Gabungkan tabel branches
            ->leftJoin('programs', 'prospect_parents.id_program', '=', 'programs.id')  // Gabungkan tabel programs
            ->leftJoin('payment__sps', 'prospect_parents.id', '=', 'payment__sps.id_parent')  // Gabungkan tabel payment_sps
            ->leftJoin('users', 'users.parent_id', '=', 'prospect_parents.id')  // Gabungkan tabel users berdasarkan parent_id
            ->leftJoin('parents as father', function ($join) {
                $join->on('prospect_parents.id', '=', 'father.id_parent')
                    ->where('father.is_father', 1);  // Gabungkan tabel parents untuk data ayah
            })
            ->leftJoin('parents as mother', function ($join) {
                $join->on('prospect_parents.id', '=', 'mother.id_parent')
                    ->where('mother.is_mother', 1);  // Gabungkan tabel parents untuk data ibu
            })
            ->leftJoin('students', 'students.user_id', '=', 'users.id')  // Gabungkan tabel students berdasarkan user_id
            ->distinct()  // Mengambil data yang unik
            ->where('payment__sps.payment_type', '=', 2)  // Filter untuk payment_type = 2
            ->where('payment__sps.status_pembayaran', '=', 1)  // Filter untuk status_pembayaran = 1
            ->whereNotNull('users.id')
            ->select(
                'prospect_parents.id as id_prospect',
                'payment__sps.created_at as tanggal_bayar',
                'prospect_parents.name as nama_prospect',
                'prospect_parents.phone as hp_prospect',
                'prospect_parents.email as email_prospect',
                'programs.name as program',
                'father.name as nama_ayah',  // Nama ayah
                'father.phone as hp_ayah',   // Nomor HP ayah
                'father.email as email_ayah', // Email ayah
                'father.address as pekerjaan_ayah', // Alamat/pekerjaan ayah
                'mother.name as nama_ibu',  // Nama ibu
                'mother.phone as hp_ibu',   // Nomor HP ibu
                'mother.email as email_ibu', // Email ibu
                'mother.address as pekerjaan_ibu', // Alamat/pekerjaan ibu
                'students.name as nama_anak',
                'students.phone as hp_anak',
                'students.email as email_anak',
                DB::raw('TIMESTAMPDIFF(YEAR, students.tgl_lahir, CURDATE()) as usia_anak'),
                'students.tgl_lahir as tgl_lahir_anak',
                'students.asal_sekolah as sekolah_anak',
                'branches.name as cabang_kota',
                'prospect_parents.source as source',
                DB::raw("CASE
            WHEN payment__sps.status_pembayaran = 1 THEN 'lunas'
            ELSE 'belum lunas'END as status_transaksi")
            )
            ->orderBy('prospect_parents.id', 'asc')
            ->get();

        return response()->json($results);
    }
}
