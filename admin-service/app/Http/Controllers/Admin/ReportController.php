<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportRequest;
use App\Services\SalesReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(ReportRequest $request, SalesReportService $reports): View
    {
        return view('reports.index', [
            'report' => $reports->build($request->period(), $request->validated('from'), $request->validated('to')),
            'periods' => SalesReportService::PERIODS,
        ]);
    }

    public function csv(ReportRequest $request, SalesReportService $reports): StreamedResponse
    {
        $report = $reports->build($request->period(), $request->validated('from'), $request->validated('to'));

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['period', 'orders', 'revenue', 'average_order_value']);
            foreach ($report['rows'] as $row) {
                fputcsv($out, [$row->period, $row->orders, $row->revenue, $row->average]);
            }
            fputcsv($out, ['TOTAL', $report['totals']['orders'], $report['totals']['revenue'], $report['totals']['average']]);
            fclose($out);
        }, $this->filename($report, 'csv'), ['Content-Type' => 'text/csv']);
    }

    public function pdf(ReportRequest $request, SalesReportService $reports): Response
    {
        $report = $reports->build($request->period(), $request->validated('from'), $request->validated('to'));

        return Pdf::loadView('reports.pdf', ['report' => $report])->download($this->filename($report, 'pdf'));
    }

    private function filename(array $report, string $extension): string
    {
        return sprintf('sales-%s-%s-to-%s.%s', $report['period'], $report['from']->format('Ymd'), $report['to']->format('Ymd'), $extension);
    }
}
