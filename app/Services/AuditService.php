<?php
namespace App\Services;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
class AuditService
{
    public static function record(string $action, Model $record, array $details = []): void
    {
        AuditLog::create([
            'user_id'=>Auth::id(), 'action'=>$action,
            'record_type'=>class_basename($record), 'record_id'=>$record->getKey(),
            'details'=>$details ?: null, 'ip_address'=>request()->ip(),
        ]);
    }
    public static function adminOnly(): void
    {
        abort_unless(Auth::check() && Auth::user()->role === 'admin', 403, 'Administrator permission required.');
    }
}
