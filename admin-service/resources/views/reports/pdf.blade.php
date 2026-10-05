<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sales report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
        th { text-align: left; background: #f3f4f6; }
        .num { text-align: right; }
        tfoot td { font-weight: bold; border-top: 2px solid #111827; }
    </style>
</head>
<body>
    <h1>Sales report ({{ $report['period'] }})</h1>
    <div class="muted">{{ $report['from']->toDateString() }} to {{ $report['to']->toDateString() }} · generated {{ now()->toDateTimeString() }}</div>
    <div class="muted">Revenue counts paid, processing, shipped and delivered orders.</div>

    <table>
        <thead>
            <tr><th>Period</th><th class="num">Orders</th><th class="num">Revenue</th><th class="num">Average</th></tr>
        </thead>
        <tbody>
            @forelse ($report['rows'] as $row)
                <tr>
                    <td>{{ $row->period }}</td>
                    <td class="num">{{ $row->orders }}</td>
                    <td class="num">${{ number_format($row->revenue, 2) }}</td>
                    <td class="num">${{ number_format($row->average, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">No revenue in this range.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td class="num">{{ $report['totals']['orders'] }}</td>
                <td class="num">${{ number_format($report['totals']['revenue'], 2) }}</td>
                <td class="num">${{ number_format($report['totals']['average'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
