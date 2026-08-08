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

        return view('payroll.index', compact('adjustments'));
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
