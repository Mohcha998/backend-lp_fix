<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProspectParent;
use App\Models\InvitonalCode;
use App\Models\Payment_Sps;
use App\Models\Messages;
use App\Models\Student;
use App\Models\Course;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\PaymentSpController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ParentsController;
use App\Http\Controllers\inviteController;
use PhpParser\Node\NullableType;

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
        $phoneParent = $parent->phone;

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
}
