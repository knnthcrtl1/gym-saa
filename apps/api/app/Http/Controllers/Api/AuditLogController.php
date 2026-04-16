<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\AuthorizesGymPermissions;
use App\Support\BelongsToTenant;
use App\Support\GymPermission;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    use AuthorizesGymPermissions;
    use BelongsToTenant;

    public function index(Request $request)
    {
        $this->requirePermission($request, GymPermission::AUDIT_LOGS_VIEW);

        $query = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(AuditLog::with('actor'), $request),
            $request,
        );

        if ($request->filled('action')) {
            $query->where('action', $request->string('action')->toString());
        }

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->string('auditable_type')->toString());
        }

        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->integer('actor_id'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date('date_from')->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date('date_to')->endOfDay());
        }

        return response()->json(
            $query->latest()->paginate($request->integer('per_page', 15))
        );
    }
}
