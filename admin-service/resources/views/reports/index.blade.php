<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Sales reports') }}</h2>
    </x-slot>

    @php
        $query = ['period' => $report['period'], 'from' => $report['from']->toDateString(), 'to' => $report['to']->toDateString()];
    @endphp

    <x-card>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div>
                    <x-input-label for="period" value="Group by" />
                    <x-select-input id="period" name="period" class="mt-1" :options="collect($periods)->mapWithKeys(fn ($p) => [$p => ucfirst($p)])" :selected="$report['period']" />
                </div>
                <div>
                    <x-input-label for="from" value="From" />
                    <x-text-input id="from" name="from" type="date" :value="$query['from']" class="mt-1 text-sm" />
                </div>
                <div>
                    <x-input-label for="to" value="To" />
                    <x-text-input id="to" name="to" type="date" :value="$query['to']" class="mt-1 text-sm" />
                </div>
                <x-secondary-button type="submit">{{ __('Apply') }}</x-secondary-button>
            </form>
            <div class="flex gap-4 text-sm">
                <a href="{{ route('reports.csv', $query) }}" class="text-indigo-600 hover:underline">{{ __('Export CSV') }}</a>
                <a href="{{ route('reports.pdf', $query) }}" class="text-indigo-600 hover:underline">{{ __('Export PDF') }}</a>
            </div>
        </div>
    </x-card>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
        <x-card><div class="text-sm text-gray-500">Revenue</div><div class="mt-1 text-2xl font-semibold">${{ number_format($report['totals']['revenue'], 2) }}</div></x-card>
        <x-card><div class="text-sm text-gray-500">Orders</div><div class="mt-1 text-2xl font-semibold">{{ number_format($report['totals']['orders']) }}</div></x-card>
        <x-card><div class="text-sm text-gray-500">Average order value</div><div class="mt-1 text-2xl font-semibold">${{ number_format($report['totals']['average'], 2) }}</div></x-card>
    </div>

    <x-card title="Revenue by {{ ['daily' => 'day', 'weekly' => 'week', 'monthly' => 'month'][$report['period']] }}">
        <p class="mb-4 text-xs text-gray-500">Counts paid, processing, shipped and delivered orders; pending and cancelled orders are excluded.</p>
        <table class="min-w-full text-sm">
            <thead class="text-left text-gray-500">
                <tr><th class="py-2">Period</th><th class="py-2 text-right">Orders</th><th class="py-2 text-right">Revenue</th><th class="py-2 text-right">Average</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($report['rows'] as $row)
                    <tr>
                        <td class="py-2">{{ $row->period }}</td>
                        <td class="py-2 text-right">{{ $row->orders }}</td>
                        <td class="py-2 text-right">${{ number_format($row->revenue, 2) }}</td>
                        <td class="py-2 text-right">${{ number_format($row->average, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-4 text-gray-500">No revenue in this range.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-card>
</x-app-layout>
