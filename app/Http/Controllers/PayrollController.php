<?php

namespace App\Http\Controllers;

use App\Models\PayrollAdjustment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function index(): View
    {
        $adjustments = PayrollAdjustment::with(['user', 'createdBy'])->latest('effective_date')->paginate(20);

        $stats = [
            'total' => PayrollAdjustment::count(),
            'this_month' => PayrollAdjustment::whereMonth('effective_date', now()->month)->whereYear('effective_date', now()->year)->sum('amount'),
            'bonuses' => PayrollAdjustment::where('type', 'bonus')->count(),
            'deductions' => PayrollAdjustment::where('type', 'deduction')->count(),
        ];

        return view('payroll.index', compact('adjustments', 'stats'));
    }

    public function store(Request $request, User $employee): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:salary_change,bonus,share_grant,deduction,reimbursement'],
            'amount' => ['required', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'reason' => ['nullable', 'string'],
            'effective_date' => ['required', 'date'],
        ]);

        $employee->payrollAdjustments()->create($data + [
            'currency' => $data['currency'] ?? 'USD',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('employees.show', $employee)->with('status', 'Payroll adjustment recorded.');
    }
}
