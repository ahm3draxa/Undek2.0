<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $profile = $user->employeeProfile;
        abort_unless($profile, 404, "You don't have an employee profile yet.");

        $eligibility = [
            Certificate::INTERNSHIP_CERTIFICATE => $profile->employment_type === 'intern',
            Certificate::EXPERIENCE_LETTER => true,
            Certificate::EARNINGS_LETTER => $profile->compensationValue() !== null,
        ];

        $certificates = $user->certificates;

        return view('certificates.index', compact('eligibility', 'certificates', 'profile'));
    }

    public function generate(Request $request, string $type): RedirectResponse
    {
        abort_unless(array_key_exists($type, Certificate::TYPES), 404);

        $user = $request->user();
        $profile = $user->employeeProfile;
        abort_unless($profile, 404);

        if ($type === Certificate::INTERNSHIP_CERTIFICATE) {
            abort_unless($profile->employment_type === 'intern', 422, 'This certificate is only available to interns.');
        }
        if ($type === Certificate::EARNINGS_LETTER) {
            abort_unless($profile->compensationValue() !== null, 422, 'No compensation is set on your profile yet.');
        }

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'type' => $type,
            'code' => Certificate::generateCode(),
            'issued_at' => now(),
            'snapshot' => [
                'name' => $user->name,
                'email' => $user->email,
                'position' => $profile->position->name ?? null,
                'department' => $profile->department->name ?? null,
                'employment_type' => $profile->employment_type,
                'hire_date' => optional($profile->hire_date)->toDateString(),
                'end_date' => optional($profile->end_date)->toDateString(),
                'compensation_type' => $profile->compensation_type,
                'compensation_value' => $profile->compensationValue(),
            ],
        ]);

        return redirect()->route('certificates.show', $certificate);
    }

    public function show(Request $request, Certificate $certificate): View
    {
        $user = $request->user();
        abort_unless($certificate->user_id === $user->id || $user->hasAnyRole(['ceo', 'admin']), 403);

        return view('certificates.show', compact('certificate'));
    }

    public function verify(string $code): View
    {
        $certificate = Certificate::where('code', $code)->first();

        return view('certificates.verify', compact('certificate', 'code'));
    }
}
