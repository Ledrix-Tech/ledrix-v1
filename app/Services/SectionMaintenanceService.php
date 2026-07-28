<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SectionMaintenanceService
{
    public function down(string $section, ?string $message = null): void
    {
        $this->assertSectionExists($section);

        Cache::forever($this->cacheKey($section), [
            'down' => true,
            'message' => $message,
            'since' => now()->toIso8601String(),
        ]);
    }

    public function up(string $section): void
    {
        $this->assertSectionExists($section);

        Cache::forget($this->cacheKey($section));
    }

    public function isDown(string $section): bool
    {
        if (! $this->sectionExists($section)) {
            return false;
        }

        return (bool) data_get(Cache::get($this->cacheKey($section)), 'down', false);
    }

    public function message(string $section): ?string
    {
        return data_get(Cache::get($this->cacheKey($section)), 'message');
    }

    public function sectionForRequest(Request $request): ?string
    {
        if ($this->shouldBypass($request)) {
            return null;
        }

        foreach (config('maintenance-sections.sections', []) as $key => $section) {
            if ($this->requestMatchesSection($request, $section['paths'] ?? [])) {
                return $key;
            }
        }

        return null;
    }

    public function label(string $section): string
    {
        return (string) config("maintenance-sections.sections.{$section}.label", ucfirst($section));
    }

    /** @return list<string> */
    public function sections(): array
    {
        return array_keys(config('maintenance-sections.sections', []));
    }

    private function shouldBypass(Request $request): bool
    {
        foreach (config('maintenance-sections.bypass_paths', []) as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $paths */
    private function requestMatchesSection(Request $request, array $paths): bool
    {
        foreach ($paths as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        return false;
    }

    private function cacheKey(string $section): string
    {
        return config('maintenance-sections.cache_prefix', 'section-maintenance') . ':' . $section;
    }

    private function sectionExists(string $section): bool
    {
        return array_key_exists($section, config('maintenance-sections.sections', []));
    }

    private function assertSectionExists(string $section): void
    {
        if (! $this->sectionExists($section)) {
            throw new \InvalidArgumentException("Unknown maintenance section [{$section}].");
        }
    }
}
