# Undek 2.0

_IT Solutions Platform_

A full-stack Laravel platform for a digital agency: public landing page, client quote intake with skill-based specialist routing, an Upwork-style quotation → project → milestone pipeline with polling chat, roles (Owner/Admin/Staff/Client) plus department-based team tools, flexible employee compensation (hourly / fixed salary / fixed project price / % of project) with increments and promotions, a marketing email tool, self-serve certificates & letters, client/Undek project feedback, and owner/Management-only financial reports.

## Requirements

- PHP 8.2+
- Composer
- Node.js + npm
- MySQL (or MariaDB)

## Setup

```bash
git clone <this-repo-url>
cd Undek

composer install --prefer-dist
npm install

cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your database connection (defaults assume a local MySQL with a database named `undek`):

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=undek
DB_USERNAME=root
DB_PASSWORD=
```

Create the database (e.g. `CREATE DATABASE undek;` in phpMyAdmin/MySQL), then:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
```

Serve it with your webserver of choice pointed at `public/`, or for quick local testing:

```bash
php artisan serve
```

### Default login (local development only)

On a `local` or `testing` environment, seeding creates one owner account: `ceo@undek.com` / `password`.
It is **never** created on any other environment. On a server, create the first owner deliberately:

```bash
php artisan tinker --execute="\$u = App\Models\User::create(['name' => 'Your Name', 'email' => 'you@undek.com', 'password' => bcrypt('a-strong-password'), 'is_active' => true]); \$u->forceFill(['email_verified_at' => now()])->save(); \$u->assignRole('owner');"
```

### How access is organised

Defined in one place, `app/Support/Organization.php`. Each concept answers one question:

| Concept | Question | Values |
|---|---|---|
| Role | What can you do in the portal? | Owner (everything), Admin, Staff, Client |
| Department (team) | Which team are you in — and so which team tools do you get? | Management, Web Development, SEO & Marketing, Design & Video, IT Support, Sales |
| Position | What's your job title and seniority? | Titles ranked within a department, e.g. CEO & Founder, Co-Founder, Senior Developer |
| Employment type | What's your contract? | Full-time, part-time, contract, internship |
| Skills | What can you work on? | Used only to suggest people for work — never grants access |

Effective access = the role's permissions + the tools of every department the person holds a position in
(primary or secondary). Both are edited on the **Roles & Teams** screen.

### Notes

- `QUEUE_CONNECTION=database` — marketing campaign emails are queued. Run `php artisan queue:work` to actually send them (or `php artisan queue:work --once` for a single test send). Without a worker running, queued jobs just sit in the `jobs` table.
- `MAIL_MAILER=log` by default — outgoing mail is written to `storage/logs/laravel.log` instead of actually sending, until you configure a real mail driver.
- No AI provider is configured for the landing-page chat widget — it runs on a small rule-based responder (`app/Services/ChatbotResponder.php`) built from the live service catalog, not an external LLM.
