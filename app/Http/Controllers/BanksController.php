<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use Illuminate\Http\Request;

class BanksController extends Controller
{
    public function index()
    {
        return response()->json(Bank::all());
    }
}
