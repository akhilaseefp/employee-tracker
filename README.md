# Employee Daily Work Update & Performance Evaluation System

A secure, modular, role-based PHP and MySQL web application designed to track employee daily work submissions, manage task lifecycles, facilitate manager feedback, and calculate transparent performance evaluation metrics.

Built for deployment on local development stacks such as **XAMPP** (Apache, MariaDB/MySQL, PHP 8.2+).

## System Overview

The **Employee Daily Work Update & Performance Evaluation System** bridges the gap between daily activity reporting and project task management. It provides:

- **Structured Daily Reporting:** Employees submit end-of-day reports categorized by completed work, planned work, pending items, blockers, and notes.
- **Direct Task Linkage:** Daily updates explicitly reference specific assigned project tasks via many-to-many relationships (`daily_update_tasks`).
- **Strict Team Scoping:** Managers are strictly confined to their direct reports; they cannot view, modify, or export data belonging to other managers' teams.
- **Audit Trails:** Every status transition on a task is logged with timestamp, author, previous status, new status, and optional commentary in `task_activity`.
- **Transparent Metrics:** Key performance indicators such as the **On-Time Completion Rate** and **Overdue Task Count** follow clear, documented formulas without hidden scoring logic.

---

## Technology Stack

| Layer | Technology | Details |
| :--- | :--- | :--- |
| **Backend Language** | **PHP 8.0+** | Developed and tested on **PHP 8.2** and **PHP 8.3**. Native vanilla PHP architecture with strict typing, modular separation of concerns, and clean helper functions. |
| **Database** | **MySQL 8.0+ / MariaDB 10.4+** | **InnoDB** storage engine across all tables with explicit foreign key constraints, UTF-8 (`utf8mb4_unicode_ci`) character encoding, and UTC timezone alignment. |
| **Data Access Layer** | **PDO (PHP Data Objects)** | 100% prepared statements with parameterized queries. Native prepare mode (`ATTR_EMULATE_PREPARES => false`), exception mode (`ERRMODE_EXCEPTION`), and identifier whitelisting. |
| **Styling & UI Framework** | **Bootstrap 5.3.0** | Fully responsive layout (desktop, tablet, mobile), card components, modal dialogs, status badges, alert notifications, and dropdowns. |
| **Custom Styling** | **Vanilla CSS3** | Custom typography, sidebar transition drawers, custom scrollbars, and modern UI enhancements located in `assets/css/style.css`. |
| **Icons & Typography** | **FontAwesome 6.4.0 & Google Fonts** | Vector icons for UI actions and states; modern **Inter** font family for clean readability. |
| **Client-Side Scripting** | **Vanilla JavaScript (ES6)** | Lightweight script (`assets/js/app.js`) handling mobile sidebar toggling, selective alert dismissal (preserving permanent feedback cards), and interactive delete/status confirmations. |
| **Web Server Environment** | **Apache 2.4+ (XAMPP)** | Local web server configured under `C:\xampp\htdocs\employee-tracker`. |

---

## Architecture & Project Structure

```text
employee-tracker/
├── admin/                        # Administrator-only controllers and views
│   ├── users.php                 # User directory, role overview, and active status toggle
│   └── users/
│       ├── edit.php              # Edit user details (name, email, role, manager assignment)
│       └── toggle.php            # POST endpoint to activate/deactivate accounts
├── assets/                       # Static public web assets
│   ├── css/
│   │   └── style.css             # Custom CSS enhancements for Bootstrap components
│   └── js/
│       └── app.js                # Client-side scripts, sidebar toggling, alert handling
├── daily-updates/                # Daily work update submission and review
│   ├── create.php                # Submit daily update with task linking checkboxes
│   ├── edit.php                  # Edit existing update (enforces 24h / same-day window)
│   ├── index.php                 # List updates (scoped by user role and team)
│   ├── my-updates.php            # Router redirecting employees to their personal list
│   └── view.php                  # Detailed update report with permanent manager feedback
├── database/                     # Database definitions and seed records
│   └── employee_work_tracker.sql # Consolidated schema (6 tables) and valid demo seed data
├── includes/                     # Reusable shared core modules
│   ├── auth.php                  # Authentication, session timeout, active status verification
│   ├── config.php                # Global constants, database credentials, timezone setup
│   ├── csrf.php                  # CSRF token generation, form field helper, token validator
│   ├── database.php              # Singleton PDO wrapper with query/insert/update/transaction helpers
│   ├── footer.php                # Shared page footer, JavaScript bundles, closing tags
│   ├── functions.php             # Flash messaging, redirection, badges, date formatters
│   ├── header.php                # HTML document head, navigation bar, responsive sidebar
│   ├── sidebar.php               # Sidebar compatibility stub
│   └── validation.php            # Central input validators, CSV sanitization, URL generator
├── manager/                      # Manager-specific tools and reports
│   ├── export.php                # Standalone team task CSV export with formula protection
│   ├── feedback.php              # POST handler for adding/editing manager feedback
│   ├── reports.php               # Team performance dashboard, filterable task grid, CSV export
│   └── team.php                  # Manager team overview showing direct reports and workload
├── tasks/                        # Task lifecycle management
│   ├── create.php                # Task creation with employee team assignment validation
│   ├── edit.php                  # Task editing with manager boundary checks
│   ├── index.php                 # Filterable, searchable, paginated task list
│   ├── update_status.php         # POST handler enforcing state transition business rules
│   └── view.php                  # Task details, status transition form, audit activity timeline
├── tests/                        # Verification and QA documentation
│   └── manual-test-plan.md       # Comprehensive 47-case manual and automated test plan
├── .gitignore                    # Git ignore file for local files, logs, and caches
├── dashboard.php                 # Role-aware central dashboard
├── index.php                     # Application entry router
├── login.php                     # User login interface with CSRF and error notifications
├── logout.php                    # Secure session termination handler
├── README.md                     # Comprehensive project documentation
└── setup.php                     # First-time initial administrator setup wizard
```

## Feature Walkthrough — What You Can Do

### Authentication & Account Management
- **Secure Sign-In:** Users authenticate using their email and password. Passwords are encrypted using PHP's native `password_hash()` (Argon2id/Bcrypt).
- **Session Protection:** Session IDs are regenerated upon login and rotated periodically every 300 seconds to prevent session fixation attacks.
- **Inactivity Timeout:** Sessions automatically expire after 2 hours (`SESSION_LIFETIME = 7200` seconds) of inactivity.
- **Real-Time Deactivation Gate:** During every authenticated request, `requireLogin()` checks the database to confirm `is_active = 1`. If an administrator deactivates an account while the user is logged in, their session is immediately revoked, and they are redirected with an explanatory notice.
- **Self-Lockout Prevention:** Administrators cannot deactivate their own accounts or remove their own admin privileges.

### Task Management & State Transitions
- **Granular Task Metadata:** Every task records title, description, priority (`Low`, `Medium`, `High`, `Urgent`), status (`To Do`, `In Progress`, `Blocked`, `Completed`), assigned employee, responsible manager, start date, due date, creation date, and completion timestamp.
- **Allowed Transition Graph:** State changes follow a strict business workflow:
  - `To Do` $\longleftrightarrow$ `In Progress` $\longleftrightarrow$ `Blocked`
  - `In Progress` / `Blocked` / `To Do` $\longrightarrow$ `Completed` (Managers and Admins only)
  - `Completed` $\longrightarrow$ `In Progress` / `To Do` (Reopen: Managers and Admins only; automatically clears `completed_at`)
- **Completion Timestamp Tracking:** Setting a task to `Completed` automatically sets `completed_at = NOW()`. Reopening a task automatically resets `completed_at = NULL`.
- **Audit Logging:** Every state change inserts an audit record into `task_activity`, storing the user ID, old status, new status, timestamp, and optional user comment.

### Daily Work Updates & Task Linking
- **Structured EOD Reporting:** Employees record their daily progress through 5 structured fields:
  1. *Completed Work:* Tasks and activities finished during the day.
  2. *Planned Work:* Scheduled goals for the next workday.
  3. *Pending Work:* Ongoing work in progress.
  4. *Blockers / Obstacles:* Technical, operational, or dependency blockers.
  5. *Additional Notes:* Context, links, or general commentary.
- **Task Association:** Checkboxes dynamically list all active tasks assigned to the employee. Selected tasks are linked in the `daily_update_tasks` relationship table. Submissions attempting to link tasks belonging to other employees are rejected on the server.
- **Single Update Per Work Date:** A database unique constraint on `(employee_id, work_date)` ensures employees cannot submit duplicate reports for the same workday.
- **Edit Window Enforcement:** Employees may edit their reports only within **24 hours of submission** or on the **same calendar work date**. Once the window closes, the report is locked for archival integrity.

### Manager Feedback & Evaluations
- **Direct Review:** Managers reviewing a direct report's daily update can write structured feedback notes via `manager/feedback.php`.
- **Permanent Display:** Saved feedback is rendered inside a prominent card styled with the `.alert-permanent` CSS class, ensuring it remains permanently visible on screen and is not dismissed by client-side auto-dismiss scripts.
- **Feedback Updates:** If a manager submits updated feedback for the same daily update, the existing record is updated in-place with a revised `updated_at` timestamp rather than creating confusing duplicates.

### Performance Metrics & Team Reports
- **On-Time Completion Rate:** Calculated using the transparent formula:
  $$\text{On-Time Completion Rate} = \left( \frac{\text{Tasks Completed on or before Due Date}}{\text{Total Assigned Tasks}} \right) \times 100$$
  *Critical Fix:* Unfinished tasks (even if their due date is in the future) are **never** counted as on-time completions. If total tasks is zero, the rate safely displays `0%` to prevent division-by-zero errors.
- **Overdue Task Tracking:** Tasks are flagged as overdue if:
  $$\text{status} \neq \text{'Completed'} \quad \text{AND} \quad \text{due\_date} < \text{CURDATE()}$$
  Completed tasks with past due dates are excluded from overdue tallies.
- **Scoping Safety:** If a manager has no direct reports assigned, team queries enforce `AND 1=0`, preventing company-wide data leakage.

### Secure CSV Export
- Available from both the **Reports** page (`manager/reports.php?export=csv`) and the standalone export endpoint (`manager/export.php`).
- **Excel Compatibility:** Exports include a UTF-8 Byte Order Mark (`\xEF\xBB\xBF`) ensuring special characters and accents open cleanly in Microsoft Excel.
- **Formula Injection Neutralization:** Cell values starting with spreadsheet formula trigger characters (`=`, `+`, `-`, `@`, `\t`, `\r`) are automatically escaped with a leading apostrophe (`'`), preventing malicious code execution in spreadsheet software.

---

## Security Architecture

The application is engineered to adhere to modern OWASP security practices:

1. **SQL Injection Defense:** All user input is parameterized using PDO prepared statements. Dynamic table and column identifiers are validated against strict alphanumeric regex patterns (`^[a-zA-Z0-9_]+$`) and wrapped in backticks.
2. **Cross-Site Scripting (XSS) Defense:** All dynamic output rendered into HTML templates is sanitized using `htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
3. **Cross-Site Request Forgery (CSRF) Defense:** Every state-changing form includes a cryptographically secure token (`generateCsrfToken()`) validated via `validateCsrfToken()` with timing-safe string comparison (`hash_equals()`).
4. **Credential Safety:** Error handling catches PDO exceptions and logs technical stack traces to server error logs (`error_log()`). Generic user-friendly alerts are presented to the browser, ensuring database credentials and internal file paths are never disclosed.
5. **Session Fixation & Hijacking Defense:** Regenerates session IDs upon authentication and every 5 minutes. Uses `httponly` session cookie parameters.
6. **Spreadsheet Formula Injection Defense:** Neutralizes CSV injection attack vectors across all export utilities.

---

## Installation & Setup Guide

### 1. Prerequisites
- **XAMPP** (or WampServer / LAMP stack) with:
  - **Apache 2.4+**
  - **PHP 8.0+** (PHP 8.2 or 8.3 recommended) with `pdo_mysql`, `mbstring`, and `openssl` extensions enabled.
  - **MySQL 8.0+** or **MariaDB 10.4+**

### 2. Project Placement
Place the repository into your XAMPP web root directory:
```text
C:\xampp\htdocs\employee-tracker
```
Confirm the directory layout matches:
```text
C:\xampp\htdocs\employee-tracker\index.php
C:\xampp\htdocs\employee-tracker\includes\config.php
...
```

### 3. Start Web and Database Services
Open the **XAMPP Control Panel** and click **Start** for:
- **Apache** (ports 80, 443)
- **MySQL** (port 3306)

### 4. Database Setup & Import
1. Open your web browser and navigate to **phpMyAdmin**:
   ```text
   http://localhost/phpmyadmin/
   ```
2. Click **New** (left sidebar) or the **Databases** tab.
3. Enter database name: `employee_work_tracker`.
4. Select collation: `utf8mb4_unicode_ci`.
5. Click **Create**.
6. Select the newly created `employee_work_tracker` database.
7. Click the **Import** tab at the top.
8. Click **Choose File** and select the consolidated schema file:
   ```text
   C:\xampp\htdocs\employee-tracker\database\employee_work_tracker.sql
   ```
9. Click **Import** (or **Go**) at the bottom.
10. Confirm all 6 tables are created and populated:
    - `users` (5 seed accounts)
    - `tasks` (6 seed tasks)
    - `daily_updates` (3 seed reports)
    - `daily_update_tasks` (3 relationship mappings)
    - `manager_feedback` (2 evaluation notes)
    - `task_activity` (4 audit entries)

### 5. Configuration (Optional)
Inspect [includes/config.php](file:///c:/xampp/htdocs/employee-tracker/includes/config.php) to confirm database settings match your environment:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'employee_work_tracker');
define('DB_USER', 'root');
define('DB_PASSWORD', ''); // Default XAMPP has no password
define('APP_URL', 'http://localhost/employee-tracker');
define('TIMEZONE', 'UTC');
```

### 6. First-Time Setup Wizard (Alternative to SQL Seed)
If starting with an empty database without seed users:
1. Navigate to: `http://localhost/employee-tracker/setup.php`
2. Fill in the **First Admin Account** form (Name, Email, Password).
3. Once created, the setup wizard automatically locks to prevent unauthorized reuse.

---

## Demo Accounts & Credentials

The seed database is pre-configured with the following local testing accounts. All accounts share the password: `password123`.

| Role | Name | Email | Password | Scope & Responsibilities |
| :--- | :--- | :--- | :--- | :--- |
| **Administrator** | Alice Johnson | `admin@local.test` | `password123` | Full access: User management, all tasks, company-wide reports, system settings. |
| **Manager** | Alice Johnson | `manager@local.test` | `password123` | Direct supervisor over Bob, Carol, and Dave. Task creation, team performance review, CSV export. |
| **Employee 1** | Bob Smith | `employee1@local.test` | `password123` | Direct report of Manager 1. Assigned to Task 1 & Task 2. Daily updates and task status updates. |
| **Employee 2** | Carol Williams | `employee2@local.test` | `password123` | Direct report of Manager 1. Assigned to Task 4 & Task 5. Daily updates and task status updates. |
| **Employee 3** | Dave Miller | `employee3@local.test` | `password123` | Direct report of Manager 1. Assigned to Task 3 & Task 6. Daily updates and task status updates. |