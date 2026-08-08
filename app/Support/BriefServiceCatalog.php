<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Questionnair;
use Illuminate\Support\Collection;

final class BriefServiceCatalog
{
    /** @var array<string, string> */
    public const SERVICE_VIEWS = [
        'Logo Design' => 'logo',
        'Video Animation' => 'video-animation',
        'Content Writing' => 'content',
        'Website Design & Development' => 'web',
        'Social Media Marketing' => 'social-media',
        'Merchandise' => 'merchandise',
        'Domain & Hosting' => 'domain-hosting',
        'Online Reputation Management' => 'online-reputation',
        'Ebook Design & Formatting Brief' => 'ebook',
    ];

    public static function viewKeyFor(?string $serviceName): ?string
    {
        if ($serviceName === null || $serviceName === '') {
            return null;
        }

        return self::SERVICE_VIEWS[$serviceName] ?? null;
    }

    public static function hasQuestionnaire(?string $serviceName): bool
    {
        return self::viewKeyFor($serviceName) !== null;
    }

    /** @return list<string> */
    public static function questionnaireServiceNames(): array
    {
        return array_keys(self::SERVICE_VIEWS);
    }

    /**
     * One brief tab per qualifying order (same order the seller sees in the hub).
     *
     * @param  iterable<int, Order>|Collection<int, Order>|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Contracts\Pagination\LengthAwarePaginator  $orders
     */
    public static function filterOrdersForBriefs(iterable $orders): Collection
    {
        $items = self::normalizeOrders($orders);

        return $items
            ->filter(fn ($order) => $order instanceof Order && self::hasQuestionnaire($order->service_name))
            ->values();
    }

    /**
     * @param  iterable<int, Order>|Collection<int, Order>|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Contracts\Pagination\LengthAwarePaginator  $orders
     * @return Collection<int, Order>
     */
    private static function normalizeOrders(iterable $orders): Collection
    {
        if ($orders instanceof \Illuminate\Contracts\Pagination\Paginator) {
            return collect($orders->items());
        }

        if ($orders instanceof Collection) {
            return $orders;
        }

        return collect($orders);
    }

    /** @return array<string, mixed> */
    public static function metaForView(?Questionnair $questionnair): array
    {
        return $questionnair?->meta ?? [];
    }

    public static function attachmentUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    public static function briefStatus(?Questionnair $brief): string
    {
        if (! $brief) {
            return 'pending';
        }

        if ($brief->status === 'completed') {
            return 'completed';
        }

        if ($brief->status === 'progress') {
            return 'in_progress';
        }

        $query = $brief->meta['query'] ?? null;

        if (is_array($query) && collect($query)->filter(fn ($v) => filled($v))->isNotEmpty()) {
            return 'in_progress';
        }

        return 'pending';
    }

    public static function briefStatusLabel(string $status): string
    {
        return match ($status) {
            'completed' => 'Completed',
            'in_progress' => 'In progress',
            default => 'Pending',
        };
    }

    public static function briefStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'completed' => 'bg-success',
            'in_progress' => 'bg-warning text-dark',
            default => 'bg-secondary',
        };
    }
}
