<?php

namespace App\Http\Controllers;

use App\Models\Parents;
use App\Models\ProspectParent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentsController extends Controller
{
    public function double_sub(Request $request)
    {
        $validated = $request->validate([
            'father' => 'nullable|array',
            'father.name' => 'nullable|string',
            'father.email' => 'nullable|email',
            'father.phone' => 'nullable|string',
            'father.address' => 'nullable|string',
            'mother' => 'nullable|array',
            'mother.name' => 'nullable|string',
            'mother.email' => 'nullable|email',
            'mother.phone' => 'nullable|string',
            'mother.address' => 'nullable|string',
        ]);

        $userId = Auth::id();
        $user = User::find($userId);
        $userIdParent = $user->parent_id;

        $fatherData = $validated['father'];
        $fatherData['user_id'] = $userId;
        $fatherData['is_father'] = 1;
        $fatherData['id_parent'] = $userIdParent;

        $motherData = $validated['mother'];
        $motherData['user_id'] = $userId;
        $motherData['is_mother'] = 1;
        $motherData['id_parent'] = $userIdParent;

        $father = Parents::where('email', $fatherData['email'])->first();

        if ($father) {
            $father->update($fatherData);
        } else {
            $father = Parents::create($fatherData);
        }

        $mother = Parents::where('email', $motherData['email'])->first();

        if ($mother) {
            $mother->update($motherData);
        } else {
            $mother = Parents::create($motherData);
        }

        return response()->json([
            'message' => 'Parents saved or updated successfully',
            'father' => $father,
            'mother' => $mother
        ], 201);
    }

    public function getParentsData(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $parents = Parents::where('user_id', $user->id)->get();
        return response()->json($parents);
    }


    public function index()
    {
        $parents = Parents::with(['prospectParent', 'user'])->get();
        return response()->json($parents);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|integer',
            'id_parent' => 'required|integer',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:15',
            'is_father' => 'nullable|integer|in:0,1',
            'is_mother' => 'nullable|integer|in:0,1',
        ]);

        $parent = Parents::create($validatedData);

        return response()->json($parent, 201);
    }

    public function show($id)
    {
        $parent = Parents::with(['prospectParent', 'user'])->findOrFail($id);
        return response()->json($parent);
    }

    public function update(Request $request, $id)
    {
        $parent = Parents::findOrFail($id);

        $validatedData = $request->validate([
            'user_id' => 'sometimes|required|integer',
            'id_parent' => 'sometimes|required|integer',
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:15',
            'is_father' => 'nullable|integer|in:0,1',
            'is_mother' => 'nullable|integer|in:0,1',
        ]);

        $parent->update($validatedData);

        return response()->json($parent);
    }

    public function destroy($id)
    {
        $parent = Parents::findOrFail($id);
        $parent->delete();

        return response()->json(['message' => 'Parent deleted successfully']);
    }
}
