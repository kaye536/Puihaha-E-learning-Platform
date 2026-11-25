# Membership Management and Transaction Processing System

Laravel 12 + MySQL application for managing members, membership categories/levels,
and membership transactions (applications, renewals, payments, approvals), with a built-in
**e-learning** module (courses, lessons, quizzes and certificates) for members.

## Requirements
PHP 8.2+, Composer, MySQL 8 (or MariaDB 10.4+) — e.g. XAMPP/Laragon. No Node/npm build step is needed (plain CSS/JS in `public/`).

## Setup
```bash
composer install                    # skip if the vendor/ folder is already present
cp .env.example .env                # skip if .env exists
php artisan key:generate            # skip if APP_KEY is already set
```
1. Create an empty MySQL database named **membership_system** (phpMyAdmin or `CREATE DATABASE membership_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`).
2. Check `DB_USERNAME` / `DB_PASSWORD` in `.env` (defaults: `root` / empty).
3. Run:
```bash
php artisan migrate --seed
php artisan serve
```
4. Open http://localhost:8000 and sign in with **admin@example.com / password**.

Run the automated tests (they use an in-memory SQLite database): `php artisan test`

## Role-based access control (RBAC)
Every staff account has one role. Roles and their permissions live in **`config/rbac.php`**; each permission is registered as a Laravel Gate and enforced on the server by controller middleware (`can:...`), with menus and buttons hidden when a role lacks the permission. Administrators manage accounts under **Staff & Roles**.

| Permission | Admin | Registrar | Cashier | Viewer |
|---|:-:|:-:|:-:|:-:|
| View dashboard | ✓ | ✓ | ✓ | ✓ |
| View members, histories, records | ✓ | ✓ | ✓ | ✓ |
| Register / edit / deactivate / delete members | ✓ | ✓ | – | – |
| View categories and levels | ✓ | ✓ | – | ✓ |
| Add / edit / delete categories and levels | ✓ | – | – | – |
| View transactions | ✓ | ✓ | ✓ | ✓ |
| Create applications and renewals | ✓ | ✓ | – | – |
| Approve / cancel transactions | ✓ | ✓ | – | – |
| View payments and receipts | ✓ | ✓ | ✓ | ✓ |
| Record payments | ✓ | – | ✓ | – |
| Manage staff accounts and roles | ✓ | – | – | – |
| View courses and learner progress | ✓ | ✓ | – | ✓ |
| Create / edit / delete courses, lessons and quiz questions | ✓ | – | – | – |

An extra **Instructor** role (dashboard + courses, view and manage) can be assigned from Staff & Roles for people who only build courses.

- New accounts default to **Viewer** (least privilege). Existing accounts become **Administrator** when the RBAC migration runs, so nobody is locked out.
- Deactivated accounts cannot sign in and are signed out on their next request.
- You cannot change your own role or deactivate yourself, so the system always keeps an administrator.
- To add or change a permission, edit `config/rbac.php` (and add a `can:` check to the route/controller that needs it).
- Seeding in a local environment also creates `registrar@`, `cashier@` and `viewer@example.com` (password `password`) to try each role.

## Member portal
Applicants have no login. When staff **approve a member's first application**, the system creates a *Member* login (username = the member's email) and emails an invitation with a signed link, valid for 7 days and usable once, to choose a password. Staff can also send or resend an invitation from the member's page (**Portal access**). Everyone can use **Forgot password** on the sign-in page.

Members land on **My Membership** and can only ever see their own record:
- status, membership number, level, category, start and expiry dates, and expiry reminders;
- the request in progress (reference, amount, balance) and instructions to pay at the office;
- **History & Receipts**: every application, renewal and payment, with printable official receipts;
- **Renew**: request a renewal (same or different level) from `MEMBERSHIP_RENEWAL_WINDOW_DAYS` (default 90) days before expiry, or any time after it lapses. This creates a Pending renewal and emails a confirmation; payment is still taken by the cashier and approved by staff, so the existing rules apply;
- update their own phone number and address (name and email changes go through staff).

Members cannot open any staff screen, and staff roles cannot open the portal. Member logins are managed from the member's page, not from Staff & Roles. A login is removed automatically if its member record is deleted.

## E-learning
Members learn inside the member portal (**Learning** in the sidebar); staff build the courses under **Courses**.

**For staff** (`courses.view` / `courses.manage`, see `config/rbac.php`): create a course (title, summary, pass mark, optional minimum membership level, published or draft), add **lessons** (Markdown text and/or a YouTube/Vimeo/https video link, ordered) and an optional multiple-choice **final quiz**. The course page shows every learner's progress, best quiz score and certificate number. Courses with learners cannot be deleted (unpublish them instead); membership levels used by a course cannot be deleted.

**For members** (`learning.access`):
- Only members with an **active** membership can enrol, and a course can require a minimum level (for example *Silver and above*, judged by the level fee).
- Enrol → read the lessons → press *Mark complete* on each → the **final quiz** unlocks once every lesson is done. The quiz can be retaken; each attempt shows which answers were right.
- Reaching the pass mark (or finishing the last lesson, when a course has no quiz) completes the course and issues a numbered, printable **certificate** (`CRT-YYYY-00001`). Certificates stay available even if the membership later lapses.
- Members only ever see their own enrolments, attempts and certificates.

Rules live in `app/Services/LearningService.php`; tables: `courses`, `lessons`, `questions`, `enrollments`, `lesson_completions`, `quiz_attempts` (migration `2026_11_26_000001_create_elearning_tables.php`). Run `php artisan migrate` on an existing install; `php artisan migrate --seed` on a fresh one adds two sample courses and an `instructor@example.com` demo login (local environment, password `password`). Tests: `tests/Feature/ELearningTest.php`.

## Database design
| Table | Purpose / keys |
|---|---|
| `users` | Staff and member-portal accounts (Laravel authentication) with a `role`, an `is_active` flag (RBAC), and for members a unique FK → `members` (`member_id`) plus `portal_activated_at` |
| `membership_categories` | `name` unique |
| `membership_levels` | `name` unique, `fee`, `validity_months`, `benefits` |
| `members` | `membership_number` unique, `email` unique, FK → category, FK → level, `status`, `membership_start`, `membership_expiry` |
| `membership_transactions` | `reference_number` unique, FK → member, FK → level, `type` (application/renewal), `amount` (fee snapshot), `status`, `period_start/end`, FK → users (`processed_by`) |
| `payments` | `receipt_number` unique, FK → membership_transactions, `amount`, `payment_method`, `payment_date`, FK → users (`received_by`) |

Relationships: Category 1—N Member, Level 1—N Member, Level 1—N Transaction, Member 1—N Transaction, Transaction 1—N Payment. All foreign keys use `RESTRICT` so referenced data cannot be removed accidentally.

## Business rules (implemented in `app/Services/MembershipService.php`)
- A member belongs to exactly one category and one level; the membership number (`MEM-YYYY-00001`) is generated automatically and is unique.
- Registering a member can create the **application** transaction (`TXN-YYYYMMDD-0001`, status *Pending*, amount = level fee).
- **Renewals** are only allowed for members with an approved membership; **applications** only for members without one. Only one open (pending/paid) transaction per member.
- **Payments** (`OR-YYYYMMDD-0001`) must belong to a *pending* transaction and may not exceed the remaining balance. Partial payments are supported; the transaction becomes **Paid** when the balance reaches zero.
- Only **Paid** transactions can be **Approved**. Approval activates the member, sets the validity period (renewals continue after the current expiry) and applies the transaction's level.
- Only pending transactions without payments can be **Cancelled**.
- Approved transactions/active members become **Expired** after the validity end date (checked on page loads and by the daily scheduled command `php artisan membership:sync-expirations`).
- Members with transactions cannot be deleted — deactivate them instead. Categories/levels in use cannot be deleted.

## Pages
Login · Courses, lessons and quizzes (staff) · Learning, lesson reader, quiz and certificate (members) · Dashboard (total/active members, pending transactions, collected payments) · Members list/search/register/edit/view · Member transaction history · Printable membership record · Categories · Levels · Transactions (create, view, approve, cancel) · Payments (record, list) · Printable official receipt.

## Suggested demonstration flow
1. Log in → Dashboard. 2. Add a category and a level. 3. Register a member (leave "create application" checked). 4. Open **Transactions** → record a partial payment, then the rest (try overpaying to see validation). 5. Approve the paid transaction (try approving an unpaid one). 6. View the member's history, print the receipt and membership record. 7. Create a renewal. 8. Deactivate / reactivate a member.
