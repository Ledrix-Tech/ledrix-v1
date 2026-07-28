<?php

namespace App\Http\Middleware;

use App\Services\SectionMaintenanceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSectionMaintenance
{
    public function __construct(
        private readonly SectionMaintenanceService $maintenance,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $section = $this->maintenance->sectionForRequest($request);

        if ($section === null || ! $this->maintenance->isDown($section)) {
            return $next($request);
        }

        return response()->view('errors.section-maintenance', [
            'section' => $this->maintenance->label($section),
            'message' => $this->maintenance->message($section),
        ], 503);
    }
}
