<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use App\Models\ProspectParent;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected $messageController;

    public function __construct(
        MessageController $messageController,
        WhatsAppController $whatsappController,
    ) {
        $this->messageController = $messageController;
        $this->whatsappController = $whatsappController;
    }

    public function getUserParentData(Request $request)
    {
        $user = Auth::user();

        $parent = $user->parent;

        return response()->json([
            'parent' => $parent,
        ]);
    }

    public function getUserData(Request $request)
    {
        $user = Auth::user();

        return response()->json([
            'user' => $user,
        ]);
    }


    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'phone' => 'required|numeric',
            'parent_id' => 'nullable|exists:prospect_parents,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $parent = ProspectParent::where('phone', $request->phone)
            ->where('email', $request->email)
            ->first();

        if (!$parent) {
            $parent = ProspectParent::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'id_program' => $request->id_program,
                'is_sp' => 0,
            ]);
        }

        $parentId = $parent->id;

        $phoneNumber = (string) $request->phone;
        $password = substr($phoneNumber, -4);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'parent_id' => $parentId,
            'password' => Hash::make($password),
        ]);

        $messageId = 3;

        $response = $this->messageController->show($messageId);
        $messagesData = $response->getData()->message;

        if (!$messagesData || empty($messagesData)) {
            return response()->json(['error' => 'No messages found'], 500);
        }

        $messagesData = json_decode(json_encode($messagesData), true);
        $messageText = $messagesData['message'];
        $messageText = Str::replace([
            '{name}',
            '{email}',
            '{password}'
        ], [
            $request->name,
            $request->email,
            $password
        ], $messageText);

        $sendMessageRequest = new Request([
            'phone' => $request->phone,
            'message' => $messageText,
        ]);

        $sendMessageResponse = $this->whatsappController->sendMessage($sendMessageRequest);

        if (!$sendMessageResponse) {
            return response()->json(['error' => 'Failed to send WhatsApp message'], 500);
        }

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token,
            ]);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Reset password link sent to your email']);
        }

        return response()->json(['message' => 'Unable to send reset password link'], 500);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password reset successfully']);
        }

        return response()->json(['message' => 'Failed to reset password'], 500);
    }
}
