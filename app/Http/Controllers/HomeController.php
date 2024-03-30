<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function profile()
    {
        $user = auth()->user();

        return response()->json([
            'data' => [
                'status' => true,
                'data' => $user
            ]
        ]);
    }

    public  function search($phone)
    {
        $user = User::where('phone', $phone)->first();

        return response()->json([
            'data' => [
                'status' => true,
                'data' => $user
            ]
        ]);
    }
}
