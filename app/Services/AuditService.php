<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    /**
     * Record an administrative / transactional action. Audit rows are never
     * updated or deleted by the application.
     */
    public function log(
        string $action,
        ?Model $entity = null,
        ?User $actor = null,
        array $before = [],
        array $after = []
    ): AuditLog {
        $request = app()->runningInConsole() ? null : request();

        return AuditLog::create([
            'actor_user_id' => $actor?->id,
            'actor_role' => $actor?->role,
            'action' => $action,
            'entity_type' => $entity ? class_basename($entity) : null,
            'entity_id' => $entity?->getKey(),
            'before' => $before ?: null,
            'after' => $after ?: null,
            'ip_address' => $request?->ip(),
            'workstation_id' => $request && $request->hasSession()
                ? $request->session()->get('workstation_id')
                : null,
            'created_at' => now(),
        ]);
    }
}
