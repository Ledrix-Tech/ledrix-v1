<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\PackagePricing;
use App\Models\Central\SystemAnnouncement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SystemAnnouncementController extends Controller
{
    public function index()
    {
        $announcements = SystemAnnouncement::query()
            ->with('creator:id,name')
            ->orderByDesc('created_at')
            ->paginate(20);

        $plans = PackagePricing::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug']);

        return view('central.pages.announcements', compact('announcements', 'plans'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = Auth::guard('super_admin')->id();
        $data['is_dismissible'] = $request->boolean('is_dismissible');

        SystemAnnouncement::query()->create($data);

        return back()->with('success', 'Announcement created.');
    }

    public function update(Request $request, int $id)
    {
        $announcement = SystemAnnouncement::query()->findOrFail($id);
        $data = $this->validated($request);
        $data['is_dismissible'] = $request->boolean('is_dismissible');

        $announcement->update($data);

        return back()->with('success', 'Announcement updated.');
    }

    public function destroy(int $id)
    {
        SystemAnnouncement::query()->findOrFail($id)->delete();

        return back()->with('success', 'Announcement deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $planTargets = PackagePricing::query()
            ->pluck('slug')
            ->filter()
            ->map(fn ($slug) => 'plan_' . $slug)
            ->values()
            ->all();

        return $request->validate([
            'title'          => ['required', 'string', 'max:255'],
            'message'        => ['required', 'string', 'max:5000'],
            'type'           => ['required', Rule::in(['info', 'warning', 'success', 'danger'])],
            'target'         => ['required', Rule::in(array_merge(['all'], $planTargets))],
            'status'         => ['required', Rule::in(['active', 'inactive'])],
            'is_dismissible' => ['nullable', 'boolean'],
            'show_from'      => ['nullable', 'date'],
            'show_until'     => ['nullable', 'date', 'after_or_equal:show_from'],
        ]);
    }
}
