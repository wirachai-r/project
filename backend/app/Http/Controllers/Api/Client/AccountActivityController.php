<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\AccountActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountActivityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $activities = AccountActivity::query()
            ->where('user_id', $request->user()->user_id)
            ->latest()
            ->paginate(min(max($request->integer('per_page', 20), 1), 50));

        return response()->json($activities);
    }
}
