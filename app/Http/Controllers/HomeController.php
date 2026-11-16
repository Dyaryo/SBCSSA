<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return match (true) {
            $user->isSupervisor() => redirect()->route('supervisor.dashboard'),
            $user->isGameMaster() => redirect()->route('gm.dashboard'),
            default => redirect()->route('pos.dashboard'),
        };
    }
}
