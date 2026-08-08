# Undek 2.0

A full-stack Laravel platform for a digital agency: public landing page, client quote intake with skill-based specialist routing, an Upwork-style quotation → project → milestone pipeline with polling chat, Spatie roles & permissions (CEO/Admin/Specialist/Expert/Employee/Intern/Client), flexible employee compensation (hourly / fixed salary / fixed project price / % of project) with increments and promotions, a marketing email tool, self-serve certificates & letters, client/Undek project feedback, and CEO-only financial reports.

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

### Default login

Seeding creates one CEO account:

- **Email:** `ceo@undek.com`
- **Password:** `password`

Change this before using the app for anything real — it's a local-dev placeholder, not a production credential.

### Notes

- `QUEUE_CONNECTION=database` — marketing campaign emails are queued. Run `php artisan queue:work` to actually send them (or `php artisan queue:work --once` for a single test send). Without a worker running, queued jobs just sit in the `jobs` table.
- `MAIL_MAILER=log` by default — outgoing mail is written to `storage/logs/laravel.log` instead of actually sending, until you configure a real mail driver.
- No AI provider is configured for the landing-page chat widget — it runs on a small rule-based responder (`app/Services/ChatbotResponder.php`) built from the live service catalog, not an external LLM.
