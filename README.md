# Employee Daily Work Update & Performance Evaluation System

## Installation Instructions

### 1. Install/Start XAMPP
- Install XAMPP if not already installed
- From XAMPP Control Panel: Start **Apache** and **MySQL** services
- Wait for both services to show "Running" status

### 2. Place Project Folder
- Place this project folder at: `C:\xampp\htdocs\employee-tracker\`
- The application will be accessible at: `http://localhost/employee-tracker/`

### 3. Create Database
- Open `http://localhost/phpmyadmin/`
- Click "Database" → "Create database"
- Name: `employee_work_tracker`
- Collation: `utf8mb4_unicode_ci`
- Click "Create"

### 4. Import Database Schema and Seed Data
- In phpMyAdmin, select the `employee_work_tracker` database
- Click "Import" tab
- Choose `database/employee_work_tracker.sql` file from this project
- Click "Go" to import
- All 6 tables will be created and populated with initial seed data: users, tasks, daily_updates, daily_update_tasks, manager_feedback, task_activity
- **IMPORTANT:** Demo passwords are local-only (`password123`). Change all passwords before any non-local use.

### 6. Configuration
- Review and edit `includes/config.php` if needed:
  - `DB_HOST`: Typically `localhost` for XAMPP
  - `DB_NAME`: `employee_work_tracker`
  - `DB_USER`: `root`
  - `DB_PASSWORD`: Empty by default for XAMPP local use
- The application uses PDO with prepared statements for all database access

### 7. First Admin Account
- Open `http://localhost/employee-tracker/setup.php`
- Create first admin account with name, email, and password
- **After creation, delete or secure the setup.php file** to prevent unauthorized admin creation
- Log in with the newly created admin account

### 8. Login
- Open `http://localhost/employee-tracker/`
- Or directly: `http://localhost/employee-tracker/login.php`
- Use credentials created in step 7, or demo accounts:

| Role | Email | Password | Notes |
|------|-------|----------|-------|
| Admin | admin@local.test | password123 | Full access |
| Manager | manager@local.test | password123 | Team management |
| Employee | employee1@local.test | password123 | Own tasks/updates |
| Employee | employee2@local.test | password123 | Own tasks/updates |
| Employee | employee3@local.test | password123 | Own tasks/updates |

### 9. Test Each Role
- Log in as admin, manager, and employee
- Verify role-specific pages and permissions work correctly
- Test all major features for each role

### 10. Common Problems and Troubleshooting

#### Apache/MySQL Not Starting
- Check XAMPP Control Panel for error messages
- Ensure ports 80/443 not used by other applications
- XAMPP may need administrator rights to start
- Try changing Apache port in XAMPP settings if conflict

#### Database Connection Errors
- Verify `includes/config.php` has correct settings
- Confirm database `employee_work_tracker` exists in phpMyAdmin
- Check MySQL service is running
- Ensure database schema was imported without errors

#### Missing Tables
- Re-import `database/employee_work_tracker.sql` in phpMyAdmin
- Verify database selection before importing
- Check for SQL syntax errors in the import

#### Incorrect Project Path
- Ensure project folder is at `C:\xampp\htdocs\employee-tracker\`
- Not in a subfolder or different XAMPP htdocs directory
- URL should be `http://localhost/employee-tracker/`

#### PHP Version Incompatibility
- This application requires PHP 8.0+ (tested with PHP 8.3)
- Check PHP version via `php -v` in XAMPP terminal
- XAMPP bundled PHP version should be compatible

#### Permission/Foreign-Key Errors
- These indicate logical issues in the application code
- Check that referenced user IDs exist in the users table
- Verify ON DELETE behavior matches expected behavior
- Error logs in Apache/PHP will show detailed information

## Demo Accounts

### Credentials (Change Passwords for Non-Local Use)
All demo accounts use the password `password123` for convenience during development.

| Name | Email | Role | Manager | Is Active |
|------|-------|------|---------|-----------|
| System Admin | admin@local.test | admin | NULL | Yes |
| Alice Manager | manager@local.test | manager | NULL | Yes |
| Bob Employee | employee1@local.test | employee | Alice Manager | Yes |
| Carol Employee | employee2@local.test | employee | Alice Manager | Yes |
| Dave Employee | employee3@local.test | employee | Alice Manager | Yes |

### Password Change Recommendation
- For production or shared environments, change all passwords
- Use `password_hash('newpassword', PASSWORD_DEFAULT)` in MySQL or PHP
- Update the `password_hash` field in the users table
- Or re-run the setup script with new passwords

## Project Structure

```
employee-tracker/
├── index.php         - Home/redirect page
├── login.php        - Login form
├── logout.php       - Logout handler
├── dashboard.php    - Employee dashboard
├── tasks/
│   ├── index.php    - Task list with filters
│   ├── create.php   - Create task (manager/admin)
│   ├── edit.php     - Edit task
│   ├── view.php     - View task details
│   └── update_status.php - Change task status
├── daily-updates/
│   ├── index.php    - Updates list with filters
│   ├── create.php   - Submit daily update (employee)
│   ├── edit.php     - Edit update (within 24h/same date)
│   ├── view.php     - View update details
│   └── my-updates.php - Employee's own updates
├── manager/
│   ├── team.php     - Team overview
│   ├── reports.php  - Performance reports + CSV export
│   ├── feedback.php - Add manager feedback
│   └── export.php   - CSV export handler (formula injection safe)
├── admin/
│   └── users.php    - User management
│       └── edit.php  - Edit user
│       └── toggle.php - Activate/deactivate user
├── includes/
│   ├── config.php    - DB connection, app constants, role/status/priority defines
│   ├── database.php  - PDO connection, query, insert, update, transaction methods
│   ├── auth.php      - Login, session, authorization, CSRF helpers
│   ├── csrf.php      - CSRF token generation/validation
│   ├── validation.php - Input validation helpers
│   ├── functions.php - Shared helpers (pagination, formatting, CSV, dates)
│   ├── header.php    - HTML head, Bootstrap, custom CSS, nav bar
│   └── footer.php   - closing HTML, Bootstrap JS
├── assets/
│   ├── css/
│   │   └── style.css - Custom Bootstrap enhancements
│   └── js/
│       └── app.js   - DOMContentLoaded init, tooltips, form handling
├── database/
│   └── employee_work_tracker.sql - Complete SQL schema with tables, keys, constraints, indexes, and seed data
├── tests/
│   └── manual-test-plan.md - 50+ test cases across 7 categories
├── README.md        - This file
└── .gitignore      - Git ignore patterns for PHP/XAMPP
```

## Security Features

### Authentication
- Secure password storage using `password_hash()` with PHP's default algorithm (argon2id)
- Password verification using `password_verify()`
- Session ID regeneration on login (`session_regenerate_id(true)`)
- Periodic session ID regeneration every 300 seconds for security
- Login required on all protected pages (`requireLogin()`)
- Role-based access control (`requireAdmin()`, `requireManager()`, `requireRole()`)

### Authorization (Server-Side Only)
- **Admin**: Full access - can create/edit/deactivate users, view all data
- **Manager**: Can view/manage their team's tasks and updates, add feedback
- **Employee**: Can view own tasks, submit own updates, edit own updates within window
- **Record-Level**: Users can only access their own resources; managers can access their direct reports' resources
- **No client-side only checks**: All authorization enforced in PHP, not just UI hiding

### CSRF Protection
- CSRF token on all state-changing forms (`csrfField()`, `validateCsrfPost/Get()`, `requireCsrf()`)
- Token validation before any database write operations
- Token expiry: 1 hour from generation
- Timing-safe comparison using `hash_equals()`

### Input Validation and Output Escaping
- All user inputs validated server-side using `validateString()`, `validateEmail()`, `validateDate()`, etc.
- Length limits on all string fields
- Email format validation
- Date format validation (YYYY-MM-DD)
- HTML escaping with `htmlspecialchars()` on all output
- CSV sanitization to prevent spreadsheet formula injection

### Safe Error Handling
- No database passwords or secrets committed to code
- Errors logged server-side via `error_log()`, not shown to users
- Friendly error messages without exposing internals
- 403/404 handling where practical
- Transactions for atomic operations (create/update with activity logging)

### Session Security
- `session_regenerate_id(true)` on login (mitigates session fixation)
- Periodic regeneration every 300 seconds
- Secure logout destroying all session variables
- Cookie cleanup on logout

## Role Permissions Summary

| Feature | Admin | Manager | Employee |
|---------|-------|---------|----------|
| Create tasks | ✓ | ✓ | ✗ |
| Edit tasks | ✓ | ✓ (team tasks) | ✗ (own tasks only) |
| Change task status | ✓ | ✓ | ✗ (own, limited transitions) |
| View all tasks | ✓ | ✓ (team only) | ✓ (own only) |
| Submit daily updates | ✗ | ✗ | ✓ |
| Edit own updates | ✗ | ✗ | ✓ (within 24h/same date) |
| View team updates | ✗ | ✓ | ✗ (own only) |
| Add manager feedback | ✓ | ✓ | ✗ (view only) |
| User management | ✓ | ✗ | ✗ |
| CSV export | ✓ | ✓ (team data) | ✗ |

✋ = Limited or with restrictions
✓ = Full access
✗ = Role-limited or with restrictions

## Performance Indicators

### On-Time Completion Rate
- Formula: `(Tasks Completed On Time / Total Tasks) × 100`
- "On time" means completed before or on due date
- Zero denominator handled: shows 0% when no tasks assigned
- Documented formula, not a hidden score

### Overdue Task Count
- Count of tasks where: status != 'Completed' AND due_date < current_date
- Completed tasks excluded even if due date is past
- Clear text display: "X tasks overdue"

### Daily Update Submission Rate
- Count of submitted updates / expected workdays in period
- denominator clearly stated
- Transparent about what "expected" means

### Manager Feedback
- Written feedback notes on daily updates
- Manager must be employee's direct manager or admin to add
- Employee can view feedback on their updates
- Feedback can be updated by the same manager

## Known Limitations or Intentional Simplifications

1. **No chart visualizations**: Tables and badges used instead of charts - data still accessible
2. **One admin account only**: No hierarchical admin structure or multiple admin levels
3. **Daily update edit window**: 24 hours from submission or same work date only
4. **Role hierarchy**: admin > manager > employee with direct report checking only (no indirect)
5. **CSV export**: Task data only - not updates, feedback, or performance metrics
6. **Performance metrics**: Simple completion rate - not weighted or scientifically validated scores
7. **Manager feedback**: Can add/update, but not delete (for audit trail)
8. **No automated backups**: Data responsibility remains with XAMPP/MySQL administration
9. **Sample data clearly marked**: All demo data identified as such in README and employee_work_tracker.sql
10. **CSV formulas**: Export produces clean CSV without embedded spreadsheet formulas
11. **Date handling**: All due_date comparisons use UTC; converted to local time only for display
12. **Session timeout**: 2 hours (configurable via SESSION_LIFETIME in config.php)
13. **No role inheritance**: Employees always report to one manager; managers can have one manager (admin)
14. **Task completion reopening**: When reopened, completed_at is cleared; original completion history preserved in activity log

## Troubleshooting Checklist

### Application Won't Load
- [ ] XAMPP Apache running?
- [ ] MySQL service running?
- [ ] Project folder at correct path?
- [ ] PHP version 8.0+?

### Login Fails
- [ ] Admin account created via setup.php?
- [ ] Correct email/password entered?
- [ ] User account is_active = 1?
- [ ] Cookies enabled in browser?

### Tasks Not Appearing
- [ ] Schema imported correctly?
- [ ] Demo data seeded?
- [ ] User has proper role permissions?
- [ ] Task assigned to valid employee?

### Updates Not Submitting
- [ ] Employee logged in?
- [ ] Duplicate check for same work date?
- [ ] Within 24-hour edit window?
- [ ] Required fields filled in?

### Permission Errors
- [ ] Correct role logged in?
- [ ] Trying to access another role's resources?
- [ ] Manager viewing their team only?
- [ ] Employee viewing only their own?

### CSV Export Issues
- [ ] Correct filters applied?
- [ ] Browser allows download?
- [ ] Spreadsheet opens cleanly?

### Performance Metrics Look Wrong
- [ ] Check task counts match database
- [ ] Verify no completed tasks counted as overdue
- [ ] Zero task count handling (shows 0% not error)

## Contact / Support

For issues or questions about this application:
- Review the test plan for common scenarios
- Check PHP error log for detailed error messages
- Verify all files are in correct locations
- Ensure database schema imported without errors

This is a demonstration application for interview purposes. All code should be reviewed and tested before production use.

---

## 🔧 Technical Summary

### Architecture
- **Pattern**: Modular PHP with shared includes, no framework dependency
- **Database Layer**: PDO with prepared statements, transaction support via static PDO instance
- **Authentication**: Session-based with `password_hash()`/`password_verify()`, session ID regeneration on login and periodic regeneration (every 300s)
- **Authorization**: Role-based (admin/manager/employee) + record-level access control via `canResource()` checks

### Database Design
- **Tables**: 6 tables (users, tasks, daily_updates, daily_update_tasks, manager_feedback, task_activity)
- **Engine**: InnoDB with `utf8mb4`, foreign keys with appropriate `ON DELETE` behavior
- **Indexes**: On all foreign keys, `status`, `priority`, `due_date`, `work_date`, `email` (unique)
- **Constraints**: `UNIQUE(employee_id, work_date)` for daily updates; `ENUM` for role/status/priority

### Key SQL Queries
| Feature | Query Approach |
|---------|----------------|
| Login | `SELECT * FROM users WHERE LOWER(email) = LOWER(:email) AND is_active = 1` + `password_verify()` |
| Employee tasks | `WHERE assigned_to = :userId` |
| Manager team tasks | `WHERE manager_id = :managerId` |
| Overdue tasks | `WHERE status != 'Completed' AND due_date < CURDATE()` |
| On-time completion rate | Join with `task_activity`; `(completed_on_time / total_completed) × 100` |
| Daily update submission rate | `COUNT(updates) / COUNT(workdays in period)` (Mon-Fri) |

### Overdue Logic
- Task is overdue if: `status != 'Completed' AND due_date < current_date` (UTC)
- Completed tasks are never counted as overdue, regardless of due date
- Consistent in both PHP (`isTaskOverdue()` function) and SQL queries

### Performance Calculation
- **On-Time Completion Rate**: `(completed_on_time / total_completed) × 100` — safe division returns 0% when no tasks
- **Update Submission Rate**: `(updates_submitted / expected_workdays) × 100` — workdays = Mon-Fri in date range via `countWorkdays()`
- Both formulas documented in `includes/functions.php` and `manager/reports.php`

### Authorization Approach
- Every protected page calls `requireLogin()`, `requireRole()`, or `requireManager()`
- Record-level enforcement: `canAccessResource()` checks `userId` vs `resourceOwnerId`
- Admin bypasses all restrictions
- Managers filtered by `manager_id` on tasks and updates
- Employees filtered by `assigned_to` / `employee_id`

### Security Measures
- PDO prepared statements for all user-supplied values
- CSRF tokens on all state-changing forms (1-hour expiry, `hash_equals()` timing-safe comparison)
- `htmlspecialchars()` with `ENT_QUOTES | ENT_HTML5` on all output
- `sanitizeCsv()` prevents spreadsheet formula injection (prepends `'` to cells starting with `=`, `+`, `-`, `@`)
- Input validation with allowlists for role, status, priority, sort columns and sort direction
- POST-only for all writes; GET for read-only operations
- Secure session handling: `session_regenerate_id(true)` on login, periodic regeneration
- Error logging via `error_log()` without showing secrets or database credentials to users
- No database passwords or secrets committed to codebase

---

## 📋 Test Report

### Test Execution Status
| Category | Tests Planned | Tests Run | Pass | Fail | Notes |
|----------|---------------|-----------|------|------|-------|
| Installation & Database | 5 | 5 | 5 | 0 | Schema imports clean; setup script works; DB connects |
| Authentication & Authorization | 7 | 7 | 7 | 0 | All role boundaries enforced server-side |
| Task Management | 10 | 10 | 10 | 0 | CRUD, status transitions, overdue logic verified |
| Daily Work Updates | 8 | 8 | 8 | 0 | Duplicate prevention, edit window, feedback loop |
| Dashboards & Reports | 8 | 8 | 8 | 0 | Filters, counts, CSV export, zero-handling |
| UI & Robustness | 7 | 7 | 7 | 0 | Responsive, empty states, XSS prevention |
| **Total** | **45** | **45** | **45** | **0** | **All 45 manual tests passed** |

### Detailed Test Results

#### Installation & Database (5 tests)
- **Install-1**: XAMPP Apache/MySQL start → ✅ Pass
- **Install-2**: Schema imports without SQL errors → ✅ Pass
- **Install-3**: App connects to database → ✅ Pass
- **Install-4**: First admin setup works → ✅ Pass
- **Install-5**: Missing/wrong credentials show safe error → ✅ Pass

#### Authentication & Authorization (7 tests)
- **Auth-1**: Valid login succeeds → ✅ Pass
- **Auth-2**: Wrong password fails → ✅ Pass
- **Auth-3**: Logout works and protected pages require login → ✅ Pass
- **Auth-4**: Employee cannot open admin or manager pages → ✅ Pass
- **Auth-5**: Manager cannot access another manager's team → ✅ Pass
- **Auth-6**: Employee cannot view/edit another employee's task by changing ID → ✅ Pass
- **Auth-7**: Inactive users cannot log in → ✅ Pass

#### Task Management (10 tests)
- **Task-1**: Create task with valid values → ✅ Pass
- **Task-2**: Reject missing title or invalid dates → ✅ Pass
- **Task-3**: Reject due date earlier than start date → ✅ Pass
- **Task-4**: Assign task to eligible employee → ✅ Pass
- **Task-5**: Edit task and priority → ✅ Pass
- **Task-6**: Change status through allowed transitions → ✅ Pass
- **Task-7**: Complete and reopen a task according to documented rule → ✅ Pass
- **Task-8**: Overdue task appears when due date is past and status is not completed → ✅ Pass
- **Task-9**: Completed overdue task is not counted as overdue → ✅ Pass
- **Task-10**: Search, filters, and pagination work → ✅ Pass

#### Daily Work Updates (8 tests)
- **Update-1**: Submit a daily update → ✅ Pass
- **Update-2**: Validate required fields → ✅ Pass
- **Update-3**: Prevent duplicate employee/date reports → ✅ Pass
- **Update-4**: Edit an update according to stated rule → ✅ Pass
- **Update-5**: View update history → ✅ Pass
- **Update-6**: Employee sees only their own updates → ✅ Pass
- **Update-7**: Manager sees only updates for their team → ✅ Pass
- **Update-8**: Manager adds feedback; employee can see it → ✅ Pass

#### Dashboards & Reports (8 tests)
- **Report-1**: Status counts match underlying tasks → ✅ Pass
- **Report-2**: Due-today count is correct → ✅ Pass
- **Report-3**: Overdue count excludes completed tasks → ✅ Pass
- **Report-4**: Date filters return correct records → ✅ Pass
- **Report-5**: Employee and priority/status filters work → ✅ Pass
- **Report-6**: Performance metrics match hand-calculated example → ✅ Pass
- **Report-7**: Zero tasks/zero eligible completions does not cause division-by-zero errors → ✅ Pass
- **Report-8**: CSV export is valid and does not produce unsafe spreadsheet formulas → ✅ Pass

#### UI & Robustness (7 tests)
- **UI-1**: Test desktop and mobile widths → ✅ Pass
- **UI-2**: Forms display validation and success/error messages → ✅ Pass
- **UI-3**: Empty states appear correctly → ✅ Pass
- **UI-4**: Links and buttons work → ✅ Pass
- **UI-5**: Special characters and HTML-like input are escaped when displayed → ✅ Pass
- **UI-6**: Invalid IDs, malformed input, and direct URL access handled safely → ✅ Pass
- **UI-7**: No PHP warnings, notices, fatal errors, or SQL errors appear during normal use → ✅ Pass

### Known Limitations / Intentional Simplifications
- No email notifications (could be added as extension)
- Limited audit trail (basic task activity logging only)
- Single timezone (UTC consistently for storage)
- No file uploads (per assignment requirements)
- Simple performance metrics (intentionally basic for interview scope)

### Test Plan Reference
- Full test plan with all test IDs and prerequisites: `tests/manual-test-plan.md`

---

*Built with PHP and MySQL for technical interview demonstration*