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
use App\Http\Controllers\inviteController;

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
        inviteController $inviteController
    ) {
        $this->messageController = $messageController;
        $this->whatsappController = $whatsappController;
        $this->invoiceController = $invoiceController;
        $this->inviteController = $inviteController;
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
                'is_sp' => 1,
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
                    'status_pembayaran' => 1,
                    'payment_type' => 1,
                    'biaya_admin' => 0,
                    'total' => 0,
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

            // Send WhatsApp message
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
                'redirect' => '/sp-ps/thanks', // Redirect to 'thanks' page
                'open_payment_link' => $invoiceLink // Provide the Xendit payment link
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
}
