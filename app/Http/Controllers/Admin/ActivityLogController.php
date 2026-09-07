<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): Response
    {
        abort_unless(request()->user()->hasRole('super-admin'), 403);

        return Inertia::render('admin/activity-logs/index', ['logs' => ActivityLog::query()->with('user:id,name')->latest()->paginate(30)]);
    }
}
