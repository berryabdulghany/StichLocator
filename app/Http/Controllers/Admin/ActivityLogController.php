<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Admin;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public const ACTIONS = [
        'created', 'updated', 'deleted', 'restored', 'force_deleted', 'published', 'unpublished',
        'dismissed', 'exported', 'login', 'logout', 'password',
    ];

    public function index(Request $request)
    {
        $filters = [
            'admin' => $request->integer('admin') ?: null,
            'action' => in_array($request->query('action'), self::ACTIONS, true) ? $request->query('action') : null,
        ];

        $logs = ActivityLog::with('admin')
            ->when($filters['admin'], fn ($query, $admin) => $query->where('admin_id', $admin))
            ->when($filters['action'], fn ($query, $action) => $query->where('action', $action))
            ->latest('created_at')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.activity', [
            'logs' => $logs,
            'filters' => $filters,
            'admins' => Admin::orderBy('name')->get(['id', 'name']),
            'actions' => self::ACTIONS,
        ]);
    }
}
