<?php

namespace App\Http\Controllers;

use App\Models\PayrollAdjustment;
use App\Models\Project;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to] = $this->range($request);

        $revenueByService = Quotation::where('quotations.status', 'accepted')
            ->whereBetween('quotations.updated_at', [$from, $to])
            ->join('quote_requests', 'quotations.quote_request_id', '=', 'quote_requests.id')
            ->join('services', 'quote_requests.service_id', '=', 'services.id')
            ->selectRaw('services.name as service, sum(quotations.budget) as total, count(*) as deals')
            ->groupBy('services.name')
            ->orderByDesc('total')
            ->get();

        $totalRevenue = (float) $revenueByService->sum('total');

        $payrollByType = PayrollAdjustment::whereBetween('effective_date', [$from, $to])
            ->selectRaw('type, sum(amount) as total, count(*) as entries')
            ->groupBy('type')
            ->orderByDesc('total')
            ->get();

        $totalPayroll = (float) $payrollByType->sum('total');

        $deliveredProjects = Project::whereIn('status', ['completed', 'delivered'])
            ->whereBetween('delivered_at', [$from, $to])
            ->count();

        $activeCompensationRunRate = \App\Models\EmployeeProfile::whereNull('end_date')
            ->where('compensation_type', 'fixed_salary')
            ->sum('base_salary');

        return view('reports.index', compact(
            'from', 'to', 'revenueByService', 'totalRevenue',
            'payrollByType', 'totalPayroll', 'deliveredProjects', 'activeCompensationRunRate'
        ));
    }

    public function exportRevenue(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        $rows = Quotation::where('quotations.status', 'accepted')
            ->whereBetween('quotations.updated_at', [$from, $to])
            ->join('quote_requests', 'quotations.quote_request_id', '=', 'quote_requests.id')
            ->join('services', 'quote_requests.service_id', '=', 'services.id')
            ->select('quotations.id', 'services.name as service', 'quote_requests.title', 'quotations.budget', 'quotations.currency', 'quotations.updated_at')
            ->orderBy('quotations.updated_at')
            ->get();

        return $this->csv('revenue-report', ['Quotation ID', 'Service', 'Project title', 'Budget', 'Currency', 'Accepted at'], $rows->map(fn ($r) => [
            $r->id, $r->service, $r->title, number_format((float) $r->budget, 2), $r->currency, $r->updated_at,
        ]));
    }

    public function exportPayroll(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        $rows = PayrollAdjustment::with('user')->whereBetween('effective_date', [$from, $to])->orderBy('effective_date')->get();

        return $this->csv('payroll-report', ['Employee', 'Type', 'Amount', 'Currency', 'Effective date', 'Reason'], $rows->map(fn ($r) => [
            $r->user->name ?? '—', str_replace('_', ' ', $r->type), number_format((float) $r->amount, 2), $r->currency, $r->effective_date->toDateString(), $r->reason,
        ]));
    }

    /**
     * Malformed 'from'/'to' query params (hand-edited URLs, or someone probing
     * for a crash) used to reach Carbon::parse() unvalidated and throw an
     * uncaught 500 — validate first so bad input just falls back to the
     * default range instead of blowing up the page.
     */
    private function range(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = ! empty($validated['from']) ? Carbon::parse($validated['from']) : now()->startOfYear();
        $to = ! empty($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : now()->endOfDay();

        return [$from, $to];
    }

    private function csv(string $filename, array $header, \Illuminate\Support\Collection $rows): Response
    {
        $lines = [implode(',', array_map($this->escapeCsv(...), $header))];

        foreach ($rows as $row) {
            $lines[] = implode(',', array_map($this->escapeCsv(...), $row));
        }

        return response(implode("\r\n", $lines), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'-'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    private function escapeCsv(mixed $value): string
    {
        $value = (string) $value;

        return str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")
            ? '"'.str_replace('"', '""', $value).'"'
            : $value;
    }
}
