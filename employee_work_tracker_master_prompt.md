# Master Prompt: Employee Daily Work Update & Performance Evaluation System

Use this prompt with a coding assistant or AI development tool to build a complete PHP/MySQL application for a technical interview assignment. The goal is to produce a polished, functional, testable project that runs locally on **XAMPP + phpMyAdmin on Windows**.

---

## 1. Project Objective

Build an **Employee Daily Work Update & Performance Evaluation System** that lets managers allocate and monitor tasks, lets employees submit daily work updates, and gives managers a clear overview of team progress and basic performance indicators.

The application must demonstrate solid PHP skills, sensible MySQL database design, correct business logic, secure coding practices, and a clean user interface. This is an interview assignment, so prioritize a reliable, complete implementation over unnecessary complexity.

## 2. Required Technology and Local Environment

- **Backend:** PHP compatible with the PHP version bundled with a current XAMPP installation. Prefer PHP 8+ syntax only when compatible with the installed version; document the minimum required version.
- **Database:** MySQL/MariaDB supplied by XAMPP.
- **Database administration:** phpMyAdmin.
- **Web server:** Apache through XAMPP.
- **Frontend:** HTML5, CSS3, Bootstrap 5, and vanilla JavaScript. Use CDN assets only where practical, and ensure the core application remains usable if a nonessential visual library fails to load.
- **Database access:** PDO with prepared statements. Do not use deprecated `mysql_*` functions.
- **No framework requirement:** Build this as a straightforward, well-structured PHP application without requiring Composer, Node.js, Docker, or command-line tooling. If an optional dependency is proposed, explain why and provide a simple alternative.
- **Local project location:** Assume the project will be placed in `C:\xampp\htdocs\employee-work-tracker\` and opened at `http://localhost/employee-work-tracker/`.

Before coding, state the minimum PHP version and any XAMPP settings that are needed. Do not assume the database has already been created.

## 3. User Roles and Permissions

Implement role-based access control with at least these roles:

### Admin
- Create, edit, deactivate, and view employee/manager accounts.
- Assign users a role and, where applicable, a manager.
- View all tasks, updates, and reports.
- Avoid deleting users or records that are referenced by existing history; prefer deactivation or safe archival.

### Manager
- View their assigned team.
- Create and assign tasks to employees who report to them.
- Set task title, description, priority, start date, due date, and status.
- View daily updates, pending work, completed work, and overdue work for their team.
- Review employee submissions and add feedback.
- View performance summaries and date-filtered reports for their team only.

### Employee
- View their own dashboard and assigned tasks.
- View task details, priorities, due dates, and current statuses.
- Submit a daily work update with work completed, work planned/to-do, pending work, blockers, and notes.
- Update task progress/status only within permitted rules.
- View their own update history and manager feedback.
- Never see other employees' private updates or information unless explicitly authorized.

Enforce permissions **on the server for every protected request**, not merely by hiding buttons in the interface. A user must not access another user's records by changing an ID in a URL or form.

## 4. Functional Requirements

### 4.1 Authentication and Account Handling
- Login and logout.
- Secure password storage using `password_hash()` and verification using `password_verify()`.
- Regenerate the session ID after successful login.
- Use secure session handling and server-side authorization checks.
- Include a safe way to create the first admin account. Prefer a one-time setup script that must be removed or disabled after use, or document how to insert a hashed password safely.
- Do not ship a public default admin password that remains active.
- Provide friendly validation and error messages without exposing database credentials, SQL, stack traces, or sensitive internals.

### 4.2 Task Allocation and Tracking
- Managers can create, edit, assign, and monitor tasks.
- Required task fields: title, description, assigned employee, priority, start date, due date, status, created by, created timestamp, updated timestamp, and optional completion timestamp.
- Suggested priorities: Low, Medium, High, Urgent.
- Suggested statuses: To Do, In Progress, Blocked, Completed.
- Validate that the due date is not earlier than the start date.
- A task is overdue when it is not completed and its due date is before the current date. Use one consistent timezone and document it.
- Calculate overdue status consistently in application logic or a database query; avoid contradictory stored and calculated states.
- Record completion time when a task becomes completed. Handle reopening a task deliberately and consistently.
- Preserve a useful task history if practical. If implementing a full audit log is too large, at least record created/updated timestamps and document the limitation.

### 4.3 Daily Work Updates
- Employees can submit one daily report per work date, with the ability to edit their own report according to a clearly documented rule.
- Fields should include:
  - Work date
  - Completed work / accomplishments
  - To-do or planned work
  - Pending tasks and reasons for delay
  - Blockers or support required
  - General notes
- Link updates to the employee and optionally to relevant assigned tasks.
- Validate required fields and length limits on both client and server.
- Prevent duplicate daily reports for the same employee and work date, or intentionally support multiple entries with a clearly documented design.
- Display a calendar or date-filtered history of updates.
- Managers can review team reports and leave feedback. Employees can see feedback on their own reports.

### 4.4 Task Progress and Overdue Tracking
- Show task counts for To Do, In Progress, Blocked, Completed, and Overdue.
- Make overdue tasks visually obvious but do not rely on color alone; include readable status text or icons.
- Show due dates and overdue duration where useful.
- Ensure completed tasks are not counted as overdue.
- Use clear empty states when there are no tasks or reports.

### 4.5 Manager Dashboard and Reports
Create a responsive manager dashboard with:
- Total team members.
- Tasks due today.
- Tasks overdue.
- Tasks completed in a selected date range.
- Tasks still in progress or blocked.
- Recent daily updates.
- Employees with pending or overdue tasks.
- A team task table with filtering by employee, status, priority, and date range.
- A daily-update view with filtering by employee and date range.
- A basic report page with date filters and a print-friendly layout. CSV export is optional but useful.
- Reports must be restricted to the manager's own team unless the user is an admin.

### 4.6 Basic Employee Performance Evaluation
Implement a transparent, simple summary using measurable indicators rather than pretending to provide a scientifically validated employee score.

Possible indicators:
- Tasks completed in a selected period.
- Tasks overdue in the selected period.
- On-time completion rate.
- Daily update submission rate, based on expected workdays if this can be calculated transparently; otherwise show the count of submitted updates and state the denominator clearly.
- Manager feedback or review notes.

If an overall score is included:
- Document the formula and weights.
- Avoid hidden or arbitrary scoring.
- Do not use a score as the only basis for evaluating an employee.
- Handle zero denominators safely.
- Make the selected reporting period visible.
- Let managers add written feedback and review notes.
- Do not imply that metrics capture all aspects of employee performance.

### 4.7 Search, Filters, Validation, and UX
- Provide useful search and filters for tasks and daily updates.
- Keep filter values when the page reloads.
- Use pagination for longer lists where appropriate.
- Add confirmation prompts for destructive or consequential actions.
- Use server-side validation even when JavaScript validation is present.
- Show success, warning, and error flash messages.
- Provide friendly 403, 404, and general error pages where practical.
- Ensure all forms have labels and keyboard-friendly controls.

## 5. UI / Visual Design Requirements

Create a **decent, modern, professional dashboard**, suitable for a job interview demonstration.

Visual direction:
- Clean SaaS-style admin dashboard.
- Restrained palette: navy/charcoal text, light neutral backgrounds, one primary accent color, and distinct but accessible status colors.
- Sidebar navigation on desktop and a usable collapsible/mobile navigation pattern.
- Top bar with page title, current user, role, and logout.
- Dashboard summary cards, clean data tables, consistent spacing, subtle borders/shadows, and readable typography.
- Responsive layout for desktop, tablet, and mobile.
- Use badges for priority/status, with text labels in addition to colors.
- Forms should be grouped logically and have clear validation messages.
- Add a polished login screen.
- Use charts only when they make data easier to understand; do not let charts replace accessible tables or labels.
- Avoid clutter, excessive gradients, oversized animations, and decorative elements that harm usability.
- Include realistic sample data for demonstration, clearly identified as sample data.

Do not produce a design-only prototype. All displayed buttons, filters, forms, links, and actions must work.

## 6. Suggested Database Schema

Design and implement a normalized relational schema using **InnoDB**, `utf8mb4`, foreign keys, suitable indexes, and consistent naming. You may improve this schema, but explain the changes.

Suggested tables:

### `users`
- `id` — primary key
- `name`
- `email` — unique
- `password_hash`
- `role` — admin, manager, employee
- `manager_id` — nullable foreign key to `users.id`
- `is_active`
- `created_at`
- `updated_at`

### `tasks`
- `id` — primary key
- `title`
- `description`
- `assigned_to` — foreign key to `users.id`
- `created_by` — foreign key to `users.id`
- `priority`
- `status`
- `start_date`
- `due_date`
- `completed_at` — nullable
- `created_at`
- `updated_at`

### `daily_updates`
- `id` — primary key
- `employee_id` — foreign key to `users.id`
- `work_date`
- `completed_work`
- `planned_work`
- `pending_work`
- `blockers`
- `notes`
- `created_at`
- `updated_at`
- Unique constraint on (`employee_id`, `work_date`) if using one report per employee per day.

### `daily_update_tasks` (optional, if updates can reference multiple tasks)
- `daily_update_id` — foreign key to `daily_updates.id`
- `task_id` — foreign key to `tasks.id`
- Composite primary key on (`daily_update_id`, `task_id`).

### `manager_feedback`
- `id` — primary key
- `daily_update_id` — foreign key to `daily_updates.id`
- `manager_id` — foreign key to `users.id`
- `feedback`
- `created_at`
- `updated_at`

### `task_activity` (recommended)
- `id` — primary key
- `task_id` — foreign key to `tasks.id`
- `changed_by` — foreign key to `users.id`
- `old_status` — nullable
- `new_status` — nullable
- `comment` — nullable
- `created_at`

Database design rules:
- Choose appropriate `ON DELETE` behavior so historical records are not accidentally erased.
- Use indexes for foreign keys, task status, priority, due date, work date, and common report filters.
- Use `DECIMAL` only if a real numeric metric needs it; do not store derived counts unnecessarily.
- Use appropriate `DATE`, `DATETIME`/`TIMESTAMP`, boolean-like fields, and `VARCHAR`/`TEXT` sizes.
- Ensure the schema works with the MySQL/MariaDB version bundled with XAMPP.
- Include the **complete executable SQL schema** in a file named `database/schema.sql`.
- Include an optional, separate `database/seed.sql` containing clearly marked demo users, tasks, daily updates, and feedback. Never store plain-text passwords in production-oriented seed data; if demo credentials are supplied, document that they are local-only and require changing/removing them.
- Add constraints where supported and validate business rules in PHP as well.

## 7. Required Database Queries and Logic

Implement and test actual SQL/PDO queries for:

1. Logging in a user by email and verifying the password hash in PHP.
2. Listing tasks assigned to the current employee.
3. Creating, editing, and changing task status safely.
4. Listing a manager's team using `manager_id`.
5. Fetching tasks for a manager's team only.
6. Counting tasks by status.
7. Fetching overdue tasks while excluding completed tasks.
8. Counting tasks due today.
9. Counting tasks completed in a date range.
10. Calculating on-time completion rate with safe zero-denominator handling.
11. Fetching daily updates for one employee and for a manager's team.
12. Preventing duplicate daily reports for an employee/date.
13. Saving manager feedback only when the manager is authorized to review that employee.
14. Filtering reports by employee, status, priority, and date range.
15. Exporting report data to CSV safely if CSV export is implemented.

Use PDO prepared statements for all user-supplied values. Never concatenate untrusted input into SQL. For dynamic sorting or column names, use a strict allowlist because placeholders cannot parameterize identifiers. Use transactions where multiple writes must succeed or fail together. Explain important queries and any non-obvious business logic.

## 8. Human-Authored Feel, Readability, and Authenticity

The finished project should feel like a thoughtful application built and understood by a real developer, not a generic AI-generated demo. This means **clear, practical engineering and a consistent personal style**, not deliberately hiding AI assistance or misrepresenting who did the work. The candidate must be able to explain the code and design decisions during an interview.

Follow these rules:
- Write code in a natural, consistent style. Prefer straightforward solutions over unnecessarily abstract, over-engineered, or excessively generic code.
- Use meaningful names that match the business domain, such as `$assignedTasks`, `getOverdueTasks()`, and `saveDailyUpdate()`. Avoid vague names like `$data1`, `doStuff()`, or `processEverything()` except where a short generic name is genuinely appropriate.
- Add concise, human-readable comments where they clarify **why** logic exists, explain a business rule, or warn about a non-obvious edge case. Comments should sound like notes a developer would leave for another teammate: direct, specific, and useful. Do not add a comment to every line or repeat the code in English.
- Keep comments accurate and in sync with the implementation. Do not leave AI-style filler such as “This comprehensive function seamlessly handles all the necessary operations,” repeated boilerplate, fake TODOs, or claims that code is robust without evidence.
- Keep the interface copy natural and specific to this application. Avoid generic placeholder text, repetitive labels, fake testimonials, inflated marketing claims, and unnecessary decorative dashboard widgets.
- Make design choices consistent across pages, and use realistic sample data that demonstrates actual workflows. Do not fabricate real business usage, real employees, or real performance outcomes. Clearly label seeded content as demo data.
- Do not generate the same boilerplate repeatedly across files when a shared helper or include is more appropriate. At the same time, do not create layers of abstractions just to make the project appear sophisticated.
- Use a small number of focused functions and files with clear responsibilities. Handle errors explicitly and explain important trade-offs in the README in plain language.
- Do not intentionally add mistakes, awkward phrasing, random formatting, or inconsistent naming to make the project “look human.” Professional, readable, maintainable code is the goal.
- Do not claim the candidate personally authored every line, or that tests passed, unless that is true. The candidate should review the implementation, run the tests, and be able to explain the database relationships, SQL queries, authorization checks, and key business rules.
- Include a short “Implementation Notes” section in the README that explains the actual choices made and any limitations in straightforward language, rather than generic AI-generated project marketing.

## 9. Security and Code Quality

Implement the following baseline safeguards:
- PDO prepared statements.
- `password_hash()` / `password_verify()`.
- Session ID regeneration on login and secure logout/session cleanup.
- Server-side role and record-level authorization.
- CSRF tokens on state-changing forms.
- Output escaping with `htmlspecialchars()` using appropriate flags and UTF-8.
- Input validation and sensible length limits.
- Allowlisted values for role, status, priority, sort columns, and sort direction.
- POST requests for state-changing operations; do not delete or update records via GET.
- Safe error handling and server-side logging without showing secrets to users.
- Do not commit database passwords or other secrets.
- Avoid arbitrary file uploads unless they are truly needed; they are not required for this assignment.
- Use a consistent timezone and date-handling strategy.
- Avoid SQL injection, XSS, CSRF, insecure direct object references, and privilege escalation.
- Do not rely on client-side checks for security.

## 10. Recommended Project Structure

Use a structure similar to this, adjusted if needed:

```text
employee-work-tracker/
├── index.php
├── login.php
├── logout.php
├── dashboard.php
├── tasks/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   ├── view.php
│   └── update_status.php
├── daily-updates/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   └── view.php
├── manager/
│   ├── team.php
│   ├── reports.php
│   └── feedback.php
├── admin/
│   └── users.php
├── includes/
│   ├── config.php
│   ├── database.php
│   ├── auth.php
│   ├── csrf.php
│   ├── functions.php
│   ├── validation.php
│   ├── header.php
│   ├── sidebar.php
│   └── footer.php
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── app.js
├── database/
│   ├── schema.sql
│   └── seed.sql
├── tests/
│   └── manual-test-plan.md
├── README.md
└── .gitignore
```

You may choose a cleaner structure, but keep it understandable for a PHP interview and avoid a single huge PHP file. Keep database access, authorization, validation, shared layout, and page logic sensibly separated.

## 11. Implementation and Delivery Rules

Build the **complete application**, not just a plan, sample snippets, or pseudocode.

- Create every file required for the project to run.
- Do not leave placeholder buttons, empty functions, TODO comments for core functionality, or unimplemented links.
- Use consistent naming and formatting.
- Add simple, human-readable comments for important logic, especially authorization, task overdue calculation, reporting queries, and performance formulas.
- Avoid overengineering. Explain important design decisions in the README.
- Ensure links, forms, navigation, filters, pagination, and role permissions work together.
- Do not silently omit a requested requirement. If something must be simplified, state what was simplified and why.
- Do not claim that the app has been tested unless the tests were actually run. Distinguish between automated checks, manual checks, and untested items.
- If the coding tool can run PHP linting or tests, use it and report the results. If it cannot run PHP or MySQL, provide exact local verification steps and state the limitation honestly.
- Deliver all source files in a ready-to-copy project folder or ZIP archive if file creation is supported.
- Include a complete README with setup, database import, demo accounts (if provided), configuration, features, role permissions, known limitations, and troubleshooting.

## 12. XAMPP + phpMyAdmin Setup Instructions

Document the exact local setup steps in the README:

1. Install/start XAMPP and start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Place the project folder at `C:\xampp\htdocs\employee-work-tracker\`.
3. Open `http://localhost/phpmyadmin/`.
4. Create a database named `employee_work_tracker` using `utf8mb4` if the SQL file does not create it.
5. Import `database/schema.sql` in phpMyAdmin. Import `database/seed.sql` only if demo data is wanted.
6. Set database host, database name, username, and password in one clearly documented configuration file. For a typical local XAMPP setup, `localhost` and `root` with an empty password are common defaults, but verify the user's local configuration; do not assume this is safe for production.
7. Open `http://localhost/employee-work-tracker/`.
8. Create or initialize the first admin account using the documented safe setup process.
9. Log in and test each role.
10. Explain common problems, including Apache/MySQL not starting, database connection errors, missing tables, incorrect project paths, PHP version incompatibility, and permission/foreign-key errors.

Never ask the user to run Composer or Node.js unless the final implementation genuinely requires it. The preferred implementation should run with XAMPP and a browser.

## 13. Mandatory Testing Plan

Create `tests/manual-test-plan.md` with test IDs, prerequisites, steps, expected results, and a result field the user can mark as Pass/Fail. Test every implemented function, not just whether the dashboard opens.

At minimum, cover:

### Installation and database
- Apache and MySQL start.
- Schema imports without SQL errors.
- App connects to the database.
- First admin setup works.
- Missing/wrong database credentials show a safe error.

### Authentication and authorization
- Valid login succeeds.
- Wrong password fails.
- Logout works and protected pages require login.
- Employee cannot open admin or manager pages.
- Manager cannot access another manager's team.
- Employee cannot view/edit another employee's task or daily update by changing an ID.
- Inactive users cannot log in.

### Task management
- Create task with valid values.
- Reject missing title or invalid dates.
- Reject due date earlier than start date.
- Assign task to an eligible employee.
- Edit task and priority.
- Change status through allowed transitions.
- Complete and reopen a task according to the documented rule.
- Overdue task appears when due date is past and status is not completed.
- Completed overdue task is not counted as overdue.
- Search, filters, and pagination work if included.

### Daily work updates
- Submit a daily update.
- Validate required fields.
- Prevent duplicate employee/date reports if using one per day.
- Edit an update according to the stated rule.
- View update history.
- Employee sees only their own updates.
- Manager sees only updates for their team.
- Manager adds feedback; employee can see it.

### Dashboards and reports
- Status counts match the underlying tasks.
- Due-today count is correct.
- Overdue count excludes completed tasks.
- Date filters return correct records.
- Employee and priority/status filters work.
- Performance metrics match a hand-calculated example.
- Zero tasks or zero eligible completions does not cause division-by-zero errors.
- CSV export is valid and does not produce unsafe spreadsheet formulas if implemented.
- Print view is readable if implemented.

### UI and robustness
- Test desktop and mobile widths.
- Forms display validation and success/error messages.
- Empty states appear correctly.
- Links and buttons work.
- Special characters and HTML-like input are escaped when displayed.
- Invalid IDs, malformed input, and direct URL access are handled safely.
- No PHP warnings, notices, fatal errors, or SQL errors appear during normal use.

The developer should first run syntax checks for all PHP files if possible. Then perform database-backed functional tests. If automated integration tests are not practical in the local environment, provide a clear manual checklist and guide the user through it step by step. Fix defects found during testing and rerun the relevant tests before declaring completion.

## 14. Final Deliverables

Provide:
1. Complete PHP application source code.
2. `database/schema.sql` with all tables, keys, constraints, and indexes.
3. `database/seed.sql` with optional demo data and safe demo-account guidance.
4. `README.md` with exact XAMPP/phpMyAdmin installation and troubleshooting steps.
5. `tests/manual-test-plan.md` covering every feature and role.
6. A concise technical summary of the architecture, database design, important SQL queries, authorization approach, overdue logic, and performance calculation.
7. A test report separating tests actually run from tests that still need to be performed on the user's laptop.
8. A list of any known limitations or intentional simplifications.

## 15. Build Order

Follow this order so that the project remains testable throughout:

1. Inspect the available PHP/XAMPP version assumptions and define the architecture.
2. Design the database schema and explain relationships.
3. Create the SQL schema and optional seed data.
4. Build database connection and configuration.
5. Implement authentication, sessions, CSRF, authorization, and shared helpers.
6. Build the shared layout and responsive visual system.
7. Implement task creation, assignment, editing, status changes, and overdue logic.
8. Implement daily updates and manager feedback.
9. Implement role-specific dashboards and reports.
10. Implement basic performance indicators and explain their formulas.
11. Add filters, validation, empty states, and error handling.
12. Run syntax checks and functional tests where possible.
13. Fix discovered issues and retest.
14. Complete the README and manual test plan.
15. Provide the finished project and honest test status.

## 16. Final Acceptance Criteria

The assignment is ready for submission only when:
- The project installs locally using the documented XAMPP/phpMyAdmin steps.
- The schema imports successfully into the supported MySQL/MariaDB version.
- Authentication and role-based permissions work on the server.
- Managers can assign and monitor tasks.
- Employees can submit and review daily updates.
- Overdue and completion calculations are correct.
- Manager dashboards and reports show accurate, filtered data.
- Performance indicators have documented, transparent formulas.
- The interface is polished and responsive.
- No known PHP syntax errors remain, and all implemented features have been tested as far as the environment permits.
- The README, SQL files, and test plan are included.
- Any untested items or limitations are disclosed rather than hidden.

**Important:** Build a working, interview-ready application. Do not stop after generating the schema, a UI mockup, or a few example pages. Verify each feature, fix defects, and clearly report what was actually tested.
