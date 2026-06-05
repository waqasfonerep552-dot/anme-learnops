# ANME LearnOps

Professional business layer for a Moodle training platform.

The business website handles:

- Public course store and checkout
- Easypaisa payment records
- Orders and enrolments
- Student dashboard
- Admin dashboard and reports
- Role/permission control through Spatie
- Moodle API integration through a service layer

Moodle handles:

- Actual LMS courses
- Learning access
- Progress/completion data

The Moodle plugin is not modified by this website. The system only calls the existing Moodle webservice APIs.

## Tech Stack

- Modern PHP business platform
- Built-in authentication starter
- Tailwind/Vite
- Spatie role permissions
- Queue jobs for Moodle fulfilment
- Scheduler commands for Moodle sync

## Important Environment Variables

Copy `.env.example` to `.env`, then set:

```env
APP_NAME="ANME LearnOps"
APP_URL=http://127.0.0.1:8000

MOODLE_BASE_URL=http://moodle.test
MOODLE_REST_URL=http://moodle.test/webservice/rest/server.php
MOODLE_WS_TOKEN=your_moodle_token

EASYPAISA_MODE=sandbox
EASYPAISA_STORE_ID=
EASYPAISA_MERCHANT_ID=
EASYPAISA_HASH_KEY=
EASYPAISA_CHECKOUT_URL=
```

If `EASYPAISA_CHECKOUT_URL` is empty, the app shows a local payment-pending page with a testing callback button.

## Setup

```bash
composer install
npm install
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Open:

```text
http://127.0.0.1:8000
```

Seeded admin:

```text
Email: admin@example.com
Password: Password123!
```

## Main Flow

1. Student opens the course store.
2. Student selects a course.
3. Checkout creates student account, order and payment records.
4. Easypaisa confirms payment.
5. Queue job calls Moodle `create_user`.
6. Queue job calls Moodle `enrol_user`.
7. Scheduler syncs progress from Moodle.
8. Admin sees orders, students, revenue and activity.

## Existing Moodle APIs Used

### Where to Add Moodle Token

Create the token inside Moodle:

`Site administration > Server > Web services > Manage tokens`

Then add it to the website `.env`:

```env
MOODLE_BASE_URL=http://moodle.test
MOODLE_REST_URL=http://moodle.test/webservice/rest/server.php
MOODLE_WS_TOKEN=paste_your_moodle_token_here
```

After changing `.env`, run:

```bash
php artisan config:clear
php artisan cache:clear
```

Open the diagnostics page:

```text
/admin/settings/moodle-diagnostics
```

This page verifies whether the website can call Moodle custom plugin APIs and Moodle built-in APIs.

### Custom Plugin APIs

- `local_custom_webservice_create_user`
- `local_custom_webservice_enrol_user`
- `local_custom_webservice_suspend_user`
- `local_custom_webservice_get_course_users_progress`

These come from the existing Moodle plugin at `local/custom_webservice`. The website does not modify the Moodle plugin.

### Moodle Built-in APIs

- `core_course_get_courses`
- `core_course_get_categories`
- `core_user_get_users_by_field`

Possible future built-in APIs:

- `core_completion_get_activities_completion_status`
- `gradereport_user_get_grade_items`
- `core_enrol_get_enrolled_users`
- `core_user_get_users`

## Useful Commands

```bash
php artisan moodle:sync-courses
php artisan moodle:sync-progress
php artisan queue:work
php artisan schedule:work
php artisan test
```

### Permanent Queue Worker on Windows

For local Windows hosting, install the queue worker permanently. It tries Windows Task Scheduler first; if Windows blocks that, it creates a current-user Startup fallback:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\install-queue-worker-task.ps1
```

Useful controls:

```powershell
Get-ScheduledTask -TaskName "ANME LearnOps Queue Worker"
powershell -ExecutionPolicy Bypass -File .\scripts\start-queue-worker.ps1
powershell -ExecutionPolicy Bypass -File .\scripts\uninstall-queue-worker-task.ps1
```

### Permanent Scheduler on Windows

The scheduler runs Moodle course/progress sync commands. It also tries Windows Task Scheduler first, then falls back to current-user Startup:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\install-scheduler-worker-task.ps1
```

Useful controls:

```powershell
Get-ScheduledTask -TaskName "ANME LearnOps Scheduler"
powershell -ExecutionPolicy Bypass -File .\scripts\start-scheduler-worker.ps1
powershell -ExecutionPolicy Bypass -File .\scripts\uninstall-scheduler-worker-task.ps1
```

### Moodle Public URL via Ngrok

For local development, one ngrok domain is used for both apps:

- Business platform: `https://your-ngrok-domain.ngrok-free.dev`
- ANME Academy/Moodle: `https://your-ngrok-domain.ngrok-free.dev/academy`

After Laragon/Apache is restarted and the `/academy` alias is active, switch the app to public Moodle mode:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\activate-moodle-ngrok.ps1
```

To return to local Moodle mode:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\use-local-moodle.ps1
```

## Admin Pages

- `/admin`
- `/admin/orders`
- `/admin/students`
- `/admin/courses`
- `/admin/reports`
- `/admin/activity`
- `/admin/settings`
- `/admin/settings/production-readiness`
- `/admin/settings/moodle-diagnostics`

## Production Notes

- Set `APP_DEBUG=false`
- Use real Easypaisa credentials
- Set `EASYPAISA_WEBHOOK_SECRET`
- Set `EASYPAISA_HASH_KEY` so paid webhooks require a valid signature
- Easypaisa paid webhooks must include matching `reference_no`, `amount`, `currency`, `transaction_id` and fresh `timestamp`
- Run queue worker permanently
- Run scheduler permanently
- Keep Moodle token in `.env`
- Do not expose Moodle token in browser/client code
- Manual Easypaisa receipts are stored privately and served only through authenticated order access
