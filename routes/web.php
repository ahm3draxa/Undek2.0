<?php

use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\CompensationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectFeedbackController;
use App\Http\Controllers\ProjectTeamMemberController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\QuoteRequestController;
use App\Http\Controllers\ReferenceDataController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TwoFactorAuthenticationController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Quote intake — open to guests and authenticated clients alike.
Route::get('/quote', [QuoteRequestController::class, 'create'])->name('quote-requests.create');
Route::post('/quote', [QuoteRequestController::class, 'store'])->middleware('throttle:public-forms')->name('quote-requests.store');
Route::get('/quote/thank-you', [QuoteRequestController::class, 'thanks'])->name('quote-requests.thanks');

Route::get('/privacy-policy', [HomeController::class, 'privacyPolicy'])->name('privacy-policy');

Route::post('/chatbot', [ChatbotController::class, 'respond'])->middleware('throttle:20,1')->name('chatbot.respond');

Route::get('/certificates/verify/{code}', [CertificateController::class, 'verify'])->name('certificates.verify');

// Marketing unsubscribe — signed links from campaign emails. The POST is also the
// one-click List-Unsubscribe target that mail clients call directly, so no CSRF.
Route::get('/unsubscribe/{email}', [MarketingController::class, 'unsubscribe'])->middleware('signed')->name('marketing.unsubscribe');
Route::post('/unsubscribe/{email}', [MarketingController::class, 'confirmUnsubscribe'])
    ->middleware(['signed', 'throttle:20,1'])
    ->withoutMiddleware(ValidateCsrfToken::class);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    // Self-service certificates & letters.
    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::post('/certificates/{type}', [CertificateController::class, 'generate'])->name('certificates.generate');
    Route::get('/certificates/{certificate}', [CertificateController::class, 'show'])->name('certificates.show');

    // Quote requests — viewable by owner/assignee, listed per role inside the controller.
    Route::get('/quote-requests', [QuoteRequestController::class, 'index'])->name('quote-requests.index');
    Route::get('/quote-requests/{quoteRequest}', [QuoteRequestController::class, 'show'])->name('quote-requests.show');
    Route::get('/quote-requests/{quoteRequest}/attachments/{attachment}', [QuoteRequestController::class, 'attachment'])->name('quote-requests.attachments.show');
    Route::get('/quote-requests/{quoteRequest}/messages', [MessageController::class, 'quoteRequestIndex'])->name('quote-requests.messages.index');
    Route::post('/quote-requests/{quoteRequest}/messages', [MessageController::class, 'storeQuoteRequestMessage'])->name('quote-requests.messages.store');

    Route::middleware('permission:assign specialist')->group(function () {
        Route::post('/quote-requests/{quoteRequest}/assign', [QuoteRequestController::class, 'assign'])->name('quote-requests.assign');
    });

    Route::middleware('permission:send quotation')->group(function () {
        Route::post('/quote-requests/{quoteRequest}/quotation', [QuotationController::class, 'store'])->name('quotations.store');
    });

    // Client-side counter-proposal — no 'send quotation' permission needed, the
    // controller checks the requester is this request's own client.
    Route::post('/quote-requests/{quoteRequest}/counter-proposal', [QuotationController::class, 'proposeCounter'])->name('quotations.counter');

    Route::post('/quotations/{quotation}/respond', [QuotationController::class, 'respond'])->name('quotations.respond');

    Route::middleware('permission:finalize project')->group(function () {
        Route::post('/quotations/{quotation}/finalize', [ProjectController::class, 'finalize'])->name('projects.finalize');
    });

    // Projects — viewable by client/lead/team members, listed per role inside the controller.
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');

    // Manual set-up (phone/WhatsApp clients, repeat work, internal projects).
    // Declared before /projects/{project} so "create" isn't read as an id.
    Route::middleware('permission:create projects')->group(function () {
        Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    });
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project}/messages', [MessageController::class, 'projectIndex'])->name('projects.messages.index');
    Route::post('/projects/{project}/messages', [MessageController::class, 'storeProjectMessage'])->name('projects.messages.store');
    Route::get('/projects/{project}/team-messages', [MessageController::class, 'projectTeamIndex'])->name('projects.team-messages.index');
    Route::post('/projects/{project}/team-messages', [MessageController::class, 'storeProjectTeamMessage'])->name('projects.team-messages.store');
    Route::post('/projects/{project}/feedback', [ProjectFeedbackController::class, 'store'])->name('projects.feedback.store');
    Route::patch('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');

    Route::post('/projects/{project}/milestones', [MilestoneController::class, 'store'])->name('milestones.store');
    Route::patch('/projects/{project}/milestones/{milestone}', [MilestoneController::class, 'update'])->name('milestones.update');
    Route::patch('/projects/{project}/milestones/{milestone}/assign', [MilestoneController::class, 'assign'])->name('milestones.assign');
    Route::post('/projects/{project}/milestones/{milestone}/submissions', [MilestoneController::class, 'submit'])->name('milestones.submit');
    Route::get('/projects/{project}/milestones/{milestone}/files/{file}', [MilestoneController::class, 'submissionFile'])->name('milestones.files.show');

    // My tasks — milestones assigned to me, and ones I've handed out.
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::delete('/projects/{project}/milestones/{milestone}', [MilestoneController::class, 'destroy'])->name('milestones.destroy');

    Route::middleware('permission:assign project team')->group(function () {
        Route::post('/projects/{project}/team', [ProjectTeamMemberController::class, 'store'])->name('projects.team.store');
        Route::delete('/projects/{project}/team/{member}', [ProjectTeamMemberController::class, 'destroy'])->name('projects.team.destroy');
    });

    // Marketing — CEO/admin, or any user holding a Marketing-category skill.
    Route::get('/marketing', [MarketingController::class, 'index'])->name('marketing.index');
    Route::post('/marketing', [MarketingController::class, 'store'])->name('marketing.store');

    // Service catalog management.
    Route::middleware('permission:manage services')->group(function () {
        Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
        Route::get('/services/create', [ServiceController::class, 'create'])->name('services.create');
        Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
        Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
        Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
    });

    // Reference data — departments, skill categories used across dropdowns.
    Route::middleware('permission:manage employees')->group(function () {
        Route::get('/reference-data', [ReferenceDataController::class, 'index'])->name('reference-data.index');
        Route::post('/reference-data/{type}', [ReferenceDataController::class, 'store'])->name('reference-data.store');
        Route::delete('/reference-data/{type}/{id}', [ReferenceDataController::class, 'destroy'])->name('reference-data.destroy');
    });

    // Positions — department-scoped rank ladders, each with its own permission set.
    Route::middleware('permission:manage employees')->group(function () {
        Route::get('/positions', [PositionController::class, 'index'])->name('positions.index');
        Route::get('/positions/create', [PositionController::class, 'create'])->name('positions.create');
        Route::post('/positions', [PositionController::class, 'store'])->name('positions.store');
        Route::get('/positions/{position}/edit', [PositionController::class, 'edit'])->name('positions.edit');
        Route::put('/positions/{position}', [PositionController::class, 'update'])->name('positions.update');
        Route::delete('/positions/{position}', [PositionController::class, 'destroy'])->name('positions.destroy');
    });

    // Employee & payroll management.
    Route::middleware('permission:manage employees')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::post('/employees/{employee}/promote', [EmployeeController::class, 'promote'])->name('employees.promote');
        Route::post('/employees/{employee}/transfer', [EmployeeController::class, 'transfer'])->name('employees.transfer');
        Route::post('/employees/{employee}/positions', [EmployeeController::class, 'addSecondaryPosition'])->name('employees.positions.store');
        Route::delete('/employees/{employee}/positions/{position}', [EmployeeController::class, 'removeSecondaryPosition'])->name('employees.positions.destroy');
        Route::post('/employees/{employee}/contributions', [EmployeeController::class, 'logContribution'])->name('employees.contributions.store');
        Route::post('/employees/{employee}/skills', [EmployeeController::class, 'addSkill'])->name('employees.skills.store');
        Route::delete('/employees/{employee}/skills/{skill}', [EmployeeController::class, 'removeSkill'])->name('employees.skills.destroy');
    });

    // Skill catalog — shared master list used by both services and employee profiles.
    Route::middleware('permission:manage skills')->group(function () {
        Route::get('/skills', [SkillController::class, 'index'])->name('skills.index');
        Route::post('/skills', [SkillController::class, 'store'])->name('skills.store');
        Route::delete('/skills/{skill}', [SkillController::class, 'destroy'])->name('skills.destroy');
    });

    Route::middleware('permission:manage payroll')->group(function () {
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('/employees/{employee}/payroll', [PayrollController::class, 'store'])->name('payroll.store');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/revenue.csv', [ReportController::class, 'exportRevenue'])->name('reports.revenue.csv');
        Route::get('/reports/payroll.csv', [ReportController::class, 'exportPayroll'])->name('reports.payroll.csv');

        Route::get('/finance/compensation', [CompensationController::class, 'index'])->name('compensation.index');
        Route::get('/finance/compensation/{employee}', [CompensationController::class, 'show'])->name('compensation.show');
        Route::post('/finance/compensation/{employee}', [CompensationController::class, 'store'])->name('compensation.store');
    });

    // User & role assignment — CEO/admin only, supports multiple CEOs.
    Route::middleware('permission:manage users')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::patch('/users/{user}/email', [UserController::class, 'updateEmail'])->name('users.update-email');
        Route::post('/users/{user}/disable-2fa', [UserController::class, 'disableTwoFactor'])->name('users.disable-2fa');
    });

    // Shared company inboxes (info@ / support@).
    Route::middleware('can:view any inbox')->group(function () {
        Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
        Route::post('/inbox/refresh', [InboxController::class, 'refresh'])->name('inbox.refresh');
        Route::get('/inbox/{message}', [InboxController::class, 'show'])->name('inbox.show');
        Route::get('/inbox/{message}/body', [InboxController::class, 'body'])->name('inbox.body');
        Route::post('/inbox/{message}/reply', [InboxController::class, 'reply'])->name('inbox.reply');
        Route::post('/inbox/{message}/unread', [InboxController::class, 'markUnread'])->name('inbox.unread');
        Route::get('/inbox/{message}/attachments/{attachment}', [InboxController::class, 'attachment'])->name('inbox.attachments.show');
    });

    // Roles & permissions — what each role is allowed to do.
    Route::middleware('permission:manage roles')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::put('/roles/departments/{department}', [RoleController::class, 'updateDepartment'])->name('roles.departments.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');

    // Two-factor authentication — profile-side setup and management.
    Route::post('/profile/two-factor', [TwoFactorAuthenticationController::class, 'enable'])->name('two-factor.enable');
    Route::post('/profile/two-factor/confirm', [TwoFactorAuthenticationController::class, 'confirm'])->name('two-factor.confirm');
    Route::post('/profile/two-factor/recovery-codes', [TwoFactorAuthenticationController::class, 'regenerateRecoveryCodes'])->name('two-factor.recovery-codes');
    Route::delete('/profile/two-factor', [TwoFactorAuthenticationController::class, 'destroy'])->name('two-factor.destroy');
});

require __DIR__.'/auth.php';
