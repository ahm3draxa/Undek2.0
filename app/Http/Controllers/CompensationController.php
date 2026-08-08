<?php

namespace App\Http\Controllers;

use App\Models\CompensationIncrement;
use App\Models\EmployeeProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompensationController extends Controller
{
    /**
     * Finance-wide view: every onboarded employee with department, position,
     * and their current standing compensation, for quick scanning.
     */
    public function index(): View
    {
        $employees = User::with(['employeeProfile.department', 'employeeProfile.position'])
            ->whereHas('employeeProfile')
            ->orderBy('name')
            ->get();

        return view('finance.compensation.index', compact('employees'));
    }

    /**
     * One employee's compensation detail: current rate, full adjustment
     * history, and the form to record a new increase/decrease.
     */
    public function show(User $employee): View
    {
        $employee->load(['employeeProfile.department', 'employeeProfile.position', 'compensationIncrements.createdBy']);
        abort_unless($employee->employeeProfile, 404);

        return view('finance.compensation.show', compact('employee'));
    }

    /**
     * Apply an annual (or ad-hoc) adjustment to whichever compensation field
     * the employee's current compensation type uses — percentage or fixed,
     * increase or decrease.
     */
    public function store(Request $request, User $employee): RedirectResponse
    {
        $profile = $employee->employeeProfile;
        abort_unless($profile, 422, 'This employee has no compensation profile yet.');

        $data = $request->validate([
            'type' => ['required', 'in:percentage,fixed'],
            'direction' => ['required', 'in:increase,decrease'],
            'value' => ['required', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $field = $profile->compensationField();
        $oldValue = (float) ($profile->{$field} ?? 0);
        $signed = $data['direction'] === 'decrease' ? -$data['value'] : $data['value'];
        $newValue = $data['type'] === 'percentage'
            ? round($oldValue * (1 + $signed / 100), 2)
            : round($oldValue + $signed, 2);
        $newValue = max($newValue, 0);

        $profile->update([$field => $newValue]);

        CompensationIncrement::create([
            'user_id' => $employee->id,
            'type' => $data['type'],
            'direction' => $data['direction'],
            'value' => $data['value'],
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'effective_date' => $data['effective_date'],
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('compensation.show', $employee)->with('status', 'Compensation adjustment applied.');
    }
}
