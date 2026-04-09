<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'stats' => [
                'active_members' => 0,
                'expired_members' => 0,
                'today_checkins' => 0,
                'monthly_revenue' => 0,
            ],
        ]);
    }
}