<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppController extends Controller
{
    protected $apiUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->apiUrl = env('PATI_WA_API_URL');
        $this->apiKey = env('PATI_WA_API_KEY');
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|regex:/^\+?[1-9]\d{1,14}$/',
            'message' => 'required|string',
        ]);

        $phone = $validated['phone'];
        $message = $validated['message'];

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/send-message', [
                'phone' => $phone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                return response()->json(['message' => 'Pesan berhasil dikirim']);
            } else {
                return response()->json([
                    'error' => $response->json()['message'] ?? 'Gagal mengirim pesan',
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
