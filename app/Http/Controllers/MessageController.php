<?php

namespace App\Http\Controllers;

use App\Models\Messages;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MessageController extends Controller
{
    public function index()
    {
        $messages = Messages::all();
        return response()->json($messages);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        $message = Messages::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Message created successfully!',
            'data' => $message
        ], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        try {
            $message = Messages::findOrFail($id);
            return response()->json(['message' => $message]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Message not found'], 404);
        }
    }

    public function update(Request $request, Messages $message)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        $message->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Message updated successfully!',
            'data' => $message
        ]);
    }

    public function destroy(Messages $message)
    {
        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully!'
        ]);
    }
}
