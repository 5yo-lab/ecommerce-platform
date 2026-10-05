<?php

namespace App\Http\Requests\Admin;

use App\Services\SalesReportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'period' => ['nullable', Rule::in(SalesReportService::PERIODS)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }

    public function period(): string
    {
        return $this->validated('period') ?? 'daily';
    }
}
