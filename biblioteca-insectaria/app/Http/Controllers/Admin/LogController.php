<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserActionLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = UserActionLog::query()
            ->with(['user', 'actor'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.logs.index', compact('logs'));
    }
}
