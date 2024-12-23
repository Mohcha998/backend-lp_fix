<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ProspectParent;
use Illuminate\Support\Facades\Log;

class CallApiController extends Controller
{
    public function getUserParentData(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $parent = ProspectParent::with('program')->find($user->parent_id);

        if (!$parent) {
            return response()->json(['error' => 'Parent not found'], 404);
        }

        return response()->json([
            'parent' => $parent,
            'program' => $parent->program,
        ]);
    }
}
