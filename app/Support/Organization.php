<?php

namespace App\Support;

/**
 * The one place that defines how Undek is organised. Each concept answers
 * exactly one question:
 *
 *   Role             — what can you do in the portal?   (owner, admin, staff, client)
 *   Department       — which team are you in?           (and so which team tools you get)
 *   Position         — what's your job title and seniority within that team?
 *   Employment type  — what's your contract?            (full-time … internship)
 *   Skills           — what can you work on?            (matching people to work, never access)
 *
 * Someone's effective permissions are their role's permissions plus the team
 * tools of every department they hold a position in (primary or secondary).
 * Positions themselves no longer grant anything.
 */
class Organization
{
    public const OWNER = 'owner';

    public const ADMIN = 'admin';

    public const STAFF = 'staff';

    public const CLIENT = 'client';

    /** Role => label, most to least privileged. */
    public const ROLES = [
        self::OWNER => 'Owner',
        self::ADMIN => 'Admin',
        self::STAFF => 'Staff',
        self::CLIENT => 'Client',
    ];

    /** Roles held by Undek's own people (everyone but clients). */
    public const STAFF_ROLES = [self::OWNER, self::ADMIN, self::STAFF];

    /** Permission => what it allows, as shown on the Roles & Teams screen. */
    public const PERMISSIONS = [
        'manage all work' => 'See and manage every quote request and project',
        'create projects' => 'Create projects manually (without a quote request)',
        'assign specialist' => 'Assign a quote request to someone to price it',
        'finalize project' => 'Turn an accepted quotation into a project',
        'send quotation' => 'Price quote requests assigned to them and negotiate with the client',
        'lead projects' => 'Be chosen as a project lead',
        'assign project team' => 'Add and remove people on projects they lead',
        'manage milestones' => 'Add and update milestones on projects they work on',
        'send marketing' => 'Send marketing email campaigns',
        'view info inbox' => 'Read and reply to info@ mail',
        'view support inbox' => 'Read and reply to support@ mail',
        'manage payroll' => 'Payroll, compensation and finance reports',
        'manage employees' => 'Employees, positions and departments',
        'manage services' => 'The service catalogue',
        'manage skills' => 'The skill list',
        'manage users' => 'User accounts (owners can only be changed by owners)',
        'manage roles' => 'What each role and team is allowed to do',
    ];

    /** Base permissions by role. Owners always hold every permission. */
    public const ROLE_PERMISSIONS = [
        self::ADMIN => [
            'manage all work', 'create projects', 'assign specialist', 'finalize project',
            'assign project team', 'manage milestones', 'manage employees', 'manage services',
            'manage skills', 'manage users',
        ],
        self::STAFF => ['manage milestones'],
        self::CLIENT => [],
    ];

    private const DELIVERY = ['lead projects', 'assign project team'];

    /**
     * Departments (slug => name and team tools). Each service belongs to one
     * department; team tools are permissions everyone in the team receives.
     */
    public const DEPARTMENTS = [
        'management' => ['name' => 'Management', 'permissions' => ['view info inbox', 'manage payroll']],
        'web-development' => ['name' => 'Web Development', 'permissions' => self::DELIVERY],
        'seo-marketing' => ['name' => 'SEO & Marketing', 'permissions' => [...self::DELIVERY, 'send marketing']],
        'design-video' => ['name' => 'Design & Video', 'permissions' => self::DELIVERY],
        'it-support' => ['name' => 'IT Support', 'permissions' => [...self::DELIVERY, 'view support inbox']],
        'sales' => ['name' => 'Sales', 'permissions' => ['send quotation']],
    ];

    public const EMPLOYMENT_TYPES = [
        'full_time' => 'Full-time',
        'part_time' => 'Part-time',
        'contractor' => 'Contract',
        // Stored as 'intern' for backwards compatibility; this is the single
        // source of truth for "is this person an intern".
        'intern' => 'Internship',
    ];

    public const SKILL_LEVELS = [
        'beginner' => 'Beginner',
        'intermediate' => 'Intermediate',
        'advanced' => 'Advanced',
    ];

    /** What someone does on a particular project (the lead is set separately). */
    public const PROJECT_ROLES = ['Member', 'Reviewer', 'Sales contact'];

    public static function roleLabel(?string $role): string
    {
        return self::ROLES[$role] ?? ($role ? ucfirst($role) : '—');
    }
}
