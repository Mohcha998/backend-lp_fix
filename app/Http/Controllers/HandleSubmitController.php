<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProspectParent;
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
        ]);

        try {
            $prospect = ProspectParent::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'source' => $validated['source'],
                'id_cabang' => $validated['id_cabang'],
                'id_program' => $validated['id_program'],
                'invitional_code' => $validated['invitional_code'] ?? null
            ]);

            $idParent = $prospect->id;
            $invoiceLink = '';

            if (empty($validated['invitional_code'])) {
                $invoiceRequest = new Request([
                    'id_parent' => $idParent,
                    'total' => 99000,
                    'payer_email' => $validated['email'],
                    'description' => "Pembayaran untuk SP untuk {$validated['name']}"
                ]);

                $invoiceResponse = $this->invoiceController->createInvoice($invoiceRequest);
                $invoiceLink = $invoiceResponse->getData()->checkout_link;
            }

            $messageId = !empty($validated['invitional_code']) ? 9 : 7;

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

            if (!empty($validated['invitional_code'])) {
                $codeRequest = new Request(['voucher_code' => $validated['invitional_code']]);
                $codeResult = $this->inviteController->checkInvitationalCode($codeRequest);
                $codeResultData = json_decode($codeResult->getContent(), true);

                if ($codeResultData['valid'] && $codeResultData['data']['id'] === 1 && $codeResultData['data']['status_voc'] === 1) {
                    return response()->json(['message' => 'Form submitted successfully', 'redirect' => '/sp-ps/thanks']);
                } else {
                    return response()->json(['error' => 'Invalid invitational code'], 400);
                }
            }

            return response()->json(['message' => 'Form submitted successfully', 'invoice_link' => $invoiceLink]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }
}
