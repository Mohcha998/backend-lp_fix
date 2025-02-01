<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProspectParent;
use App\Models\InvitonalCode;
use App\Models\Payment_Sps;
use App\Models\Messages;
use App\Models\Student;
use App\Models\Course;
use App\Models\Parents;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\PaymentSpController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ParentsController;
use App\Http\Controllers\inviteController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
// use PhpParser\Node\NullableType;

class HandleSubmitController extends Controller
{
    protected $messageController;
    protected $whatsappController;
    protected $invoiceController;
    protected $inviteController;

    public function __construct(
        MessageController $messageController,
        WhatsAppController $whatsappController,
        PaymentSpController $invoiceController,
        inviteController $inviteController,
        AuthController $authController,
        ParentsController $parentsController
    ) {
        $this->messageController = $messageController;
        $this->whatsappController = $whatsappController;
        $this->invoiceController = $invoiceController;
        $this->inviteController = $inviteController;
        $this->authController = $authController;
        $this->parentController = $parentsController;
    }

    public function handleFormSubmission(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string',
            'email' => 'required|email',
            'source' => 'nullable|string|max:255',
            'invitional_code' => 'nullable|string|max:255',
            'id_cabang' => 'required|integer',
            'id_program' => 'required|integer',
            'is_sp' => 'nullable|integer',
        ]);

        try {
            $prospect = ProspectParent::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'source' => $validated['source'],
                'id_cabang' => $validated['id_cabang'],
                'id_program' => $validated['id_program'],
                'is_sp' => $validated['invitional_code'] ? 0 : 1,
                'invitional_code' => $validated['invitional_code'] ?? null
            ]);

            $idParent = $prospect->id;
            $invoiceLink = '';

            if (!empty($validated['invitional_code'])) {
                $invCode = InvitonalCode::where('voucher_code', $validated['invitional_code'])
                    ->where('status_voc', 1)
                    ->where('type', 1)
                    ->where('id_cabang', $validated['id_cabang'])
                    ->first();

                if (!$invCode) {
                    return response()->json(['error' => 'Invalid invitational code or mismatched branch'], 400);
                }

                if ($invCode->qty <= 0) {
                    return response()->json(['error' => 'This invitational code is no longer available'], 400);
                }

                $lastPayment = Payment_Sps::latest('id')->first();
                $lastId = $lastPayment ? $lastPayment->id : 0;
                $nextId = $lastId + 1;

                $paymentData = [
                    'id_parent' => $idParent,
                    'link_pembayaran' => null,
                    'no_invoice' => 'MREVID-' . now()->format('Ymd') . str_pad($nextId, 5, '0', STR_PAD_LEFT),
                    'no_pemesanan' => 'ORDER' . time(),
                    'date_paid' => now(),
                    'status_pembayaran' => 3,
                    'payment_type' => 1,
                    'biaya_admin' => 0,
                    'total' => 0,
                    'is_inv' => 1,
                ];
                $paymentSp = Payment_Sps::create($paymentData);

                $invCode->decrement('qty', 1);

                $messageId = 2;
            } else {
                $messageId = 1;

                $invoiceRequest = new Request([
                    'id_parent' => $idParent,
                    'total' => 99000,
                    'payer_email' => $validated['email'],
                    'payment_type' => 1,
                    'description' => "Pembayaran untuk SP untuk {$validated['name']}"
                ]);

                $invoiceResponse = $this->invoiceController->createInvoice($invoiceRequest);
                $invoiceLink = $invoiceResponse->getData()->checkout_link;
            }

            $response = $this->messageController->show($messageId);
            $messagesData = $response->getData()->message;

            if (!$messagesData || empty($messagesData)) {
                return response()->json(['error' => 'No messages found'], 500);
            }

            $messagesData = json_decode(json_encode($messagesData), true);
            $messageText = $messagesData['message'];
            $messageText = Str::replace([
                '{name}',
                '{name2}',
                '{email}',
                '{payment_link}'
            ], [
                $validated['name'],
                $validated['name'],
                $validated['email'],
                $invoiceLink
            ], $messageText);

            $sendMessageRequest = new Request([
                'phone' => $validated['phone'],
                'message' => $messageText,
            ]);

            $sendMessageResponse = $this->whatsappController->sendMessage($sendMessageRequest);

            if (!$sendMessageResponse) {
                return response()->json(['error' => 'Failed to send WhatsApp message'], 500);
            }

            // Return JSON response
            return response()->json([
                'message' => 'Form submitted successfully',
                'redirect' => '/sp-ps/thanks',
                'invoice_link' => $invoiceLink
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }

    public function HandlePayment(Request $request)
    {
        $validated = $request->validate([
            'program_id' => 'required|integer',
            'num_children' => 'required|integer|min:1',
            'voucher_code' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        $idParent = $user->parent_id;
        $user_id = $user->id;
        $emailParent = $user->email;
        $nameParent = $user->name;

        $parent = ProspectParent::with('program')->find($user->parent_id);

        try {
            $selectedProgram = Course::findOrFail($validated['program_id']);
            $totalAmount = $selectedProgram->price * $validated['num_children'];

            $voucherDiscount = 0;

            if ($validated['voucher_code']) {
                $voucher = InvitonalCode::where('voucher_code', $validated['voucher_code'])
                    ->where('status_voc', 1)
                    ->where('type', 2)
                    ->where('id_cabang', $parent->id_cabang)
                    ->first();

                if (!$voucher) {
                    return response()->json(['error' => 'Invalid or expired voucher'], 400);
                }

                if ($voucher->qty > 0) {
                    $voucherDiscount = $voucher->diskon;
                    $totalAmount -= $voucherDiscount;
                    $voucher->decrement('qty', 1);
                } else {
                    return response()->json(['error' => 'Voucher out of stock'], 400);
                }
            }

            for ($i = 0; $i < $validated['num_children']; $i++) {
                Student::create([
                    'id_user_fthr' => $idParent,
                    'user_id' => $user_id,
                    'id_course' => $validated['program_id'],
                    'id_program' => $parent->id_program,
                    'name' => "Child $i",
                ]);
            }

            $invoiceRequest = new Request([
                'id_parent' => $idParent,
                'total' => $totalAmount,
                'payer_email' => $emailParent,
                'payment_type' => 2,
                'description' => "Pembayaran untuk Program}"
            ]);

            $invoiceResponse = $this->invoiceController->createInvoice($invoiceRequest);
            $invoiceLink = $invoiceResponse->getData()->checkout_link;

            $message = Messages::find(4);
            $messageText = str_replace(
                ['{name}', '{num_children}', '{total_amount}', '{voucher_discount}'],
                [$user->name, $validated['num_children'], $totalAmount, $voucherDiscount],
                $message->message
            );

            $sendMessageRequest = new Request([
                'phone' => $user->phone,
                'message' => $messageText,
            ]);

            $sendMessageResponse = $this->whatsappController->sendMessage($sendMessageRequest);

            if (!$sendMessageResponse) {
                return response()->json(['error' => 'Failed to send WhatsApp message'], 500);
            }

            $invoiceAll = $invoiceResponse->getData()->no_invoice;

            return response()->json([
                'message' => 'Form submitted successfully',
                'payment_link' => $invoiceLink,
                'no_invoice' => $invoiceAll,
                'redirect' => '/admin/invoice'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }

    public function handleManualPayment(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'required|integer',
            'program_id' => 'required|integer',
            'course' => 'required|in:1,2',
            'num_children' => 'required|integer|min:1',
            'voucher_code' => 'nullable|string|max:255',
            'total' => 'required|numeric|min:1',
            'payment_status' => 'required|in:0,1',
            'payment_method' => 'nullable|string',
        ]);

        $idParent = $validated['parent_id'];
        $parent = ProspectParent::with('program')->find($idParent);

        if (!$parent) {
            return response()->json(['error' => 'Parent not found'], 404);
        }

        $user_id = auth()->id();
        $emailParent = $parent->email;
        $nameParent = $parent->name;
        $phoneParent = $parent->phone;

        try {
            $selectedProgram = Course::findOrFail($validated['program_id']);
            $totalAmount = $validated['total'];
            $voucherDiscount = 0;

            if ($validated['voucher_code']) {
                $voucher = InvitonalCode::where('voucher_code', $validated['voucher_code'])
                    ->where('status_voc', 1)
                    ->where('type', 2)
                    ->where('id_cabang', $parent->id_cabang)
                    ->first();

                if (!$voucher) {
                    return response()->json(['error' => 'Invalid or expired voucher'], 400);
                }

                if ($voucher->qty > 0) {
                    $voucherDiscount = $voucher->diskon;
                    $totalAmount -= $voucherDiscount;
                    $voucher->decrement('qty', 1);
                } else {
                    return response()->json(['error' => 'Voucher out of stock'], 400);
                }
            }

            $lastPayment = Payment_Sps::latest('id')->first();
            $lastId = $lastPayment ? $lastPayment->id : 0;
            $nextId = $lastId + 1;

            $payment = Payment_Sps::create([
                'id_parent' => $idParent,
                'course' => $validated['course'],
                'num_children' => $validated['num_children'],
                'voucher_code' => $validated['voucher_code'],
                'link_pembayaran' => null,
                'no_invoice' => 'MREVID-' . now()->format('Ymd') . str_pad($nextId, 5, '0', STR_PAD_LEFT),
                'no_pemesanan' => 'ORDER' . time(),
                'date_paid' => now(),
                'status_pembayaran' => $validated['payment_status'],
                'payment_type' => 2,
                'biaya_admin' => 0,
                'total' => $totalAmount,
            ]);

            if ($validated['payment_status'] == 0) {
                return response()->json(['message' => 'Invoice created successfully, but payment is pending'], 200);
            }

            $users = new Request([
                'name' => $nameParent,
                'email' => $emailParent,
                'phone' => $phoneParent,
                'parent_id' => $idParent
            ]);

            $authResponse = $this->authController->register($users);
            $userData = $authResponse->getData();
            if (isset($userData->user->id)) {
                $userID = $userData->user->id;
            } else {
                return response()->json(['error' => 'User ID not found'], 400);
            }

            $parentprg = new Request([
                'user_id' => $userID,
                'id_parent' => $idParent,
                'name' => $nameParent,
                'email' => $emailParent,
                'phone' => $phoneParent,
                'is_father' => 1
            ]);

            $prgResponse = $this->parentController->store($parentprg);
            $ParentIDPrg = $prgResponse->getData();
            if (!empty($ParentIDPrg)) {
                $ParentPrg = $ParentIDPrg->id;
            } else {
                return response()->json(['error' => 'Parent ID not found'], 400);
            }

            $students = [];
            for ($i = 0; $i < $validated['num_children']; $i++) {
                $password = bcrypt(Str::random(8));
                $student = Student::create([
                    'id_user_fthr' => $ParentPrg,
                    'user_id' => $userID,
                    'id_course' => $validated['course'],
                    'id_program' => $parent->id_program,
                    'id_branch' => $parent->id_cabang,
                    'name' => "Child $i",
                    'email' => "child{$i}_$emailParent",
                    'password' => $password,
                ]);
                $students[] = $student;
            }

            $noInvoice = $payment->no_invoice;

            $messageText = "Dear $nameParent,\n"
                . "This is your Invoice Number $noInvoice\n"
                . "Your payment for {$validated['num_children']} children has been received.\n"
                . "Program: {$selectedProgram->name}\n"
                . "Total Amount: $totalAmount\n"
                . "Students:\n";

            foreach ($students as $index => $student) {
                $messageText .= ($index + 1) . ". {$student->name} - {$student->email}\n";
            }

            $sendMessageRequest = new Request([
                'phone' => $parent->phone,
                'message' => $messageText,
            ]);

            $sendMessageResponse = $this->whatsappController->sendMessage($sendMessageRequest);

            if (!$sendMessageResponse) {
                return response()->json(['error' => 'Failed to send WhatsApp message'], 500);
            }

            return response()->json(['message' => 'Payment processed successfully, students created, and message sent'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }

    public function handleManualPaymentUpdate(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|integer',
            'program_id' => 'required|integer',
            'payment_id' => 'nullable|integer',
            'course' => 'required|in:1,2',
            'num_children' => 'required|integer|min:1',
            'voucher_code' => 'nullable|string|max:255',
            'total' => 'required|numeric|min:1',
            'payment_status' => 'required|in:0,1',
            'payment_method' => 'nullable|string',
        ]);

        $payment = Payment_Sps::find($validated['payment_id']);

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        // Fetch the associated parent from the payment object
        $parent = $payment->parent;

        // Ensure that $parent is not null
        if (!$parent) {
            return response()->json(['error' => 'Parent not found'], 404);
        }

        $emailParent = $parent->email;
        $nameParent = $parent->name;
        $phoneParent = (string) $parent->phone;

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        try {
            $selectedProgram = Course::findOrFail($validated['program_id']);
            $totalAmount = $validated['total'];
            $voucherDiscount = 0;

            if ($validated['voucher_code']) {
                $voucher = InvitonalCode::where('voucher_code', $validated['voucher_code'])
                    ->where('status_voc', 1)
                    ->where('type', 2)
                    ->where('id_cabang', $parent->id_cabang)
                    ->first();

                if (!$voucher) {
                    return response()->json(['error' => 'Invalid or expired voucher'], 400);
                }

                if ($voucher->qty > 0) {
                    $voucherDiscount = $voucher->diskon;
                    $totalAmount -= $voucherDiscount;
                    $voucher->decrement('qty', 1);
                } else {
                    return response()->json(['error' => 'Voucher out of stock'], 400);
                }
            }

            $payment->update([
                'id_program' => $validated['program_id'],
                'course' => $validated['course'],
                'num_children' => $validated['num_children'],
                'voucher_code' => $validated['voucher_code'],
                'status_pembayaran' => $validated['payment_status'],
                'total' => $totalAmount,
                'date_paid' => now(),
            ]);

            if ($validated['payment_status'] == 0) {
                return response()->json(['message' => 'Invoice updated successfully, but payment is pending'], 200);
            }

            $users = new Request([
                'name' => $nameParent,
                'email' => $emailParent,
                'phone' => $phoneParent,
                'parent_id' => $parent->id,
            ]);

            $authResponse = $this->authController->register($users);
            $userData = $authResponse->getData();

            if (isset($userData->user->id)) {
                $userID = $userData->user->id;
            } else {
                return response()->json(['error' => 'User ID not found'], 400);
            }

            $parentprg = new Request([
                'user_id' => $userID,
                'id_parent' => $parent->id,
                'name' => $nameParent,
                'email' => $emailParent,
                'phone' => $phoneParent,
                'is_father' => 1,
            ]);

            $prgResponse = $this->parentController->store($parentprg);
            $ParentIDPrg = $prgResponse->getData();

            if (!empty($ParentIDPrg)) {
                $ParentPrg = $ParentIDPrg->id;
            } else {
                return response()->json(['error' => 'Parent ID not found'], 400);
            }

            $students = [];
            for ($i = 0; $i < $validated['num_children']; $i++) {
                $password = bcrypt(Str::random(8));
                $student = Student::create([
                    'id_user_fthr' => $ParentPrg,
                    'user_id' => $userID,
                    'id_course' => $validated['course'],
                    'id_program' => $parent->id_program,
                    'id_branch' => $parent->id_cabang,
                    'name' => "Child $i",
                    'email' => "child{$i}_$emailParent",
                    'password' => $password,
                ]);
                $students[] = $student;
            }

            $noInvoice = $payment->no_invoice;

            $messageText = "Dear $nameParent,\n"
                . "This is your Invoice Number $noInvoice\n"
                . "Your payment for {$validated['num_children']} children has been received.\n"
                . "Program: {$selectedProgram->name}\n"
                . "Total Amount: $totalAmount\n"
                . "Students:\n";

            foreach ($students as $index => $student) {
                $messageText .= ($index + 1) . ". {$student->name} - {$student->email}\n";
            }

            $sendMessageRequest = new Request([
                'phone' => $parent->phone,
                'message' => $messageText,
            ]);

            $sendMessageResponse = $this->whatsappController->sendMessage($sendMessageRequest);

            if (!$sendMessageResponse) {
                return response()->json(['error' => 'Failed to send WhatsApp message'], 500);
            }

            return response()->json(['message' => 'Payment updated successfully, students created, and message sent'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }

    //Submit Untuk di LP Registration

    public function registration_submit(Request $request)
    {
        // Mendapatkan data JSON dari body request
        $data = json_decode($request->getContent(), true);

        // Validasi data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:255',
            'jmlh_anak' => 'nullable|integer',
            'students.*.id_course' => 'required|integer',
            'students.*.program' => 'required|integer',
            'students.*.id_branch' => 'required|integer',
        ]);

        try {
            $prospectParent = ProspectParent::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'id_cabang' => $data['students'][0]['id_branch'],
                'id_program' => $data['students'][0]['program'],
                'jmlh_anak' => $data['jmlh_anak'],
            ]);

            $defaultPassword = substr($data['phone'], -4);

            $fatherUser = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'parent_id' => $prospectParent->id,
                'password' => Hash::make($defaultPassword),
            ]);

            if (isset($data['students']) && is_array($data['students'])) {
                foreach ($data['students'] as $student) {
                    Student::create([
                        'user_id' => $fatherUser->id,
                        'id_course' => $student['id_course'],
                        'id_program' => $student['program'],
                        'id_branch' => $student['id_branch'],
                        'status' => '0',
                    ]);
                }
            }

            return response()->json([
                'parent_id' => $prospectParent->id,
                'user_id' => $fatherUser->id,
                'students' => isset($data['students']) ? Student::where('user_id', $fatherUser->id)->get(['id']) : []
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }


    public function paymentRegis(Request $request)
    {
        $validated = $request->validate([
            'tipe_pembayaran' => 'nullable|string|max:255',
            'pembayaran' => 'nullable|string|max:255',
            'jenis_bayar' => 'nullable|string|max:255',
            'nama_bank' => 'nullable|string|max:255',
            'nama_kartu' => 'nullable|string|max:255',
            'nama_bankdc' => 'nullable|string|max:255',
            'nama_kartudc' => 'nullable|string|max:255',
            'id_parent' => 'required|integer',
            'bulan_cicilan' => 'nullable|string|max:255',
            'jumlah' => 'nullable|integer',
            'upload_bukti' => 'nullable|file|mimes:jpg,png,pdf|max:10240',
        ]);

        try {

            $total = $validated['jumlah'];
            $total = str_replace(['Rp', '.'], '', $total);
            $total = (int) $total;
            // Proses upload file jika ada
            if ($request->hasFile('upload_bukti')) {
                $file = $request->file('upload_bukti');
                $fileName = time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/bukti', $fileName);
                $filePath = 'storage/bukti/' . $fileName;
            }

            // Mengambil data orang tua dan program terkait
            $parentprg = ProspectParent::join('programs', 'programs.id', '=', 'prospect_parents.id_program')
                ->where('prospect_parents.id', $validated['id_parent'])
                ->select('prospect_parents.*', 'programs.name as program_name')
                ->first();

            $paymentId = null;
            $invoiceLink = null;

            if (!empty($validated['tipe_pembayaran']) && $validated['tipe_pembayaran'] === 'edc') {
                // Jika metode pembayaran adalah EDC
                $lastPayment = Payment_Sps::latest('id')->first();
                $nextId = $lastPayment ? $lastPayment->id + 1 : 1;

                $paymentData = [
                    'id_parent' => $validated['id_parent'],
                    'no_invoice' => 'MREVID-' . now()->format('Ymd') . str_pad($nextId, 5, '0', STR_PAD_LEFT),
                    'no_pemesanan' => 'ORDER' . time(),
                    'date_paid' => now(),
                    'status_pembayaran' => $validated['pembayaran'],
                    'description' => "Pembayaran Program {$parentprg->program_name}",
                    'payment_type' => 2,
                    'biaya_admin' => 0,
                    'nama_bank' => $validated['nama_bank'] ?? $validated['nama_bankdc'] ?? null,
                    'nama_kartu' => $validated['nama_kartu'] ?? $validated['nama_kartudc'] ?? null,
                    'bulan_cicilan' => $validated['bulan_cicilan'],
                    'file' => isset($filePath) ? $filePath : null,
                    'total' => $total,
                    'is_inv' => 0,
                ];

                $payments = Payment_Sps::create($paymentData);
                $paymentId = $payments->id;
            } else {
                // Jika metode pembayaran bukan EDC, buat invoice melalui Xendit
                $invoiceRequest = new Request([
                    'id_parent' => $validated['id_parent'],
                    'total' => $total,
                    'payer_email' => $parentprg->email,
                    'payment_type' => 2,
                    'description' => "Pembayaran Program {$parentprg->program_name}"
                ]);

                $invoiceResponse = $this->invoiceController->createInvoice($invoiceRequest);
                $invoiceLink = $invoiceResponse->getData()->checkout_link;
                $invoiceData = $invoiceResponse->getData();

                Log::info('Invoice Response:', (array) $invoiceData);
                $paymentId = property_exists($invoiceData, 'id') ? $invoiceData->id : null;
            }

            return response()->json([
                'message' => 'Form submitted successfully',
                'invoice_link' => $invoiceLink,
                'id_payment' => $paymentId
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }


    public function saveStudentParent(Request $request)
    {
        try {
            // Validasi request
            $validated = $request->validate([
                'father_name' => 'nullable|string|max:255',
                'father_phone' => 'nullable|string|max:15',
                'father_email' => 'nullable|email|max:255',
                'mother_name' => 'nullable|string|max:255',
                'mother_phone' => 'nullable|string|max:15',
                'mother_email' => 'nullable|email|max:255',
                'user_id' => 'nullable|string|max:15',
                'id_parent' => 'nullable|string|max:15',
                'students' => 'nullable|array',
                'students.*.student_id' => 'nullable|exists:students,id',
                'students.*.student_full_name' => 'nullable|string|max:255',
                'students.*.student_birthdate' => 'nullable|date',
                'students.*.student_gender' => 'nullable|string|in:P,L',
                'students.*.student_school' => 'nullable|string|max:255',
                'students.*.student_phone' => 'nullable|string|max:15',
                'students.*.student_email' => 'nullable|email|max:255',
                'students.*.jadwal_kelas' => 'nullable|string|in:kamis_1600_1800,jumat_1600_1800,sabtu_1300_1500,sabtu_1600_1800',
            ]);

            // Simpan data ayah
            $father = Parents::create([
                'name' => $request->father_name,
                'phone' => $request->father_phone,
                'email' => $request->father_email,
                'user_id' => $validated['user_id'],
                'id_parent' => $validated['id_parent'],
            ]);

            // Simpan data ibu
            $mother = Parents::create([
                'name' => $request->mother_name,
                'phone' => $request->mother_phone,
                'email' => $request->mother_email,
                'user_id' => $validated['user_id'],
                'id_parent' => $validated['id_parent'],
            ]);

            // Loop untuk memperbarui data student
            foreach ($request->students as $studentData) {
                $student = Student::find($studentData['student_id']);
                if ($student) {
                    $student->update([
                        'name' => $studentData['student_full_name'],
                        'tgl_lahir' => $studentData['student_birthdate'],
                        'jenis_kelamin' => $studentData['student_gender'],
                        'asal_sekolah' => $studentData['student_school'],
                        'phone' => $studentData['student_phone'],
                        'email' => $studentData['student_email'],
                        'jadwal' => $studentData['jadwal_kelas'],
                        'id_user_fthr' => $father->id,
                        'id_user_mthr' => $mother->id,
                        'status' => 1,
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Parent and Student data saved/updated successfully',
                'data' => [
                    'father' => $father,
                    'mother' => $mother,
                ],
            ]);
        } catch (\Exception $e) {
            // Tangani error dan kirimkan pesan error
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while saving data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function checkUser(Request $request)
    {
        $email = $request->input('email');
        $phone = $request->input('phone');

        // Cari prospect parent berdasarkan email atau phone
        $prospectParent = ProspectParent::where('email', $email)
            ->orWhere('phone', $phone)
            ->first();

        if (!$prospectParent) {
            return response()->json(['exists' => false]);
        }

        // Ambil user terkait prospect parent
        $user = User::where('parent_id', $prospectParent->id)->first();

        if (!$user) {
            return response()->json([
                'exists' => true,
                'message' => 'User tidak ditemukan untuk parent ini.'
            ]);
        }

        // Cek apakah user memiliki student dengan status = 1
        $hasActiveStudents = Student::where('user_id', $user->id)
            ->where('status', 1)
            ->exists();

        if ($hasActiveStudents) {
            return response()->json([
                'exists' => true,
                'message' => 'Anda sudah pernah mendaftar dan tidak bisa lanjut.'
            ]);
        }

        // Cek pembayaran
        $payment = Payment_Sps::where('id_parent', $prospectParent->id)->first();

        if ($payment) {
            if ($payment->status_pembayaran == 0) {
                return response()->json([
                    'exists' => true,
                    'step' => 2,
                    'message' => 'Pembayaran belum selesai. Lanjut ke Step 3.'
                ]);
            } elseif ($payment->status_pembayaran == 1) {
                return response()->json([
                    'exists' => true,
                    'step' => 3,
                    'message' => 'Pembayaran selesai. Lanjut ke Step 4.'
                ]);
            }
        }

        return response()->json([
            'exists' => true,
            'step' => 2,
            'message' => 'Email atau Nomor HP sudah terdaftar. Lanjut ke Step 2.'
        ]);
    }
}
