<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\SystemAnnouncement;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function dismiss(int $id)
    {
        $tenant = Auth::guard('tenant')->user();

        $announcement = SystemAnnouncement::query()->findOrFail($id);

        abort_unless($announcement->is_dismissible, 403, 'This announcement cannot be dismissed.');
        abort_unless($announcement->isVisibleToTenant($tenant), 404);

        $announcement->dismiss((int) $tenant->id);

        return back()->with('success', 'Announcement dismissed.');
    }
}
