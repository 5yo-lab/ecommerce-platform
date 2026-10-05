<?php

namespace App\Services;

use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesReportService
{
    public const PERIODS = ['daily', 'weekly', 'monthly'];

    public function build(string $period, ?string $from, ?string $to): array
    {
        $to = $to ? CarbonImmutable::parse($to)->endOfDay() : CarbonImmutable::now()->endOfDay();
        $from = $from ? CarbonImmutable::parse($from)->startOfDay() : $this->defaultFrom($period, $to);

        $bucket = match ($period) {
            'daily' => "DATE_FORMAT(created_at, '%Y-%m-%d')",
            'weekly' => "DATE_FORMAT(created_at, '%x-W%v')",
            'monthly' => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        $rows = Order::query()
            ->revenue()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("{$bucket} as period")
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(total) as revenue')
            ->selectRaw('ROUND(AVG(total), 2) as average')
            ->groupBy(DB::raw($bucket))
            ->orderBy('period')
            ->toBase()
            ->get();

        $orders = (int) $rows->sum('orders');
        $revenue = (float) $rows->sum('revenue');

        return [
            'rows' => $rows,
            'totals' => [
                'orders' => $orders,
                'revenue' => round($revenue, 2),
                'average' => $orders > 0 ? round($revenue / $orders, 2) : 0.0,
            ],
            'from' => $from,
            'to' => $to,
            'period' => $period,
        ];
    }

    private function defaultFrom(string $period, CarbonImmutable $to): CarbonImmutable
    {
        return match ($period) {
            'daily' => $to->subDays(29)->startOfDay(),
            'weekly' => $to->subWeeks(11)->startOfWeek(),
            'monthly' => $to->subMonths(11)->startOfMonth(),
        };
    }
}
