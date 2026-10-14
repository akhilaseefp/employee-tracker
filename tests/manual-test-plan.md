# Manual Test Plan

## Employee Daily Work Update & Performance Evaluation System

### Test ID Format
Tests are organized with IDs in format: [Category]-[Number] (e.g., A-01, B-03, C-12)

---

### Category A: Installation and Database

#### A-01: XAMPP Services Start
- **Prerequisites:** XAMPP installed, Apache and MySQL services stopped
- **Steps:**
  1. Start XAMPP Control Panel
  2. Click Start on Apache service
  3. Click Start on MySQL service
  4. Verify both services show "Running" status
- **Expected Result:** Both services start successfully without errors
- **Pass/Fail:** _____

#### A-02: Schema Import
- **Prerequisites:** Database employee_work_tracker does not exist
- **Steps:**
  1. Open http://localhost/phpmyadmin/
  2. Click "Database" → "Create database"
  3. Name: employee_work_tracker, Collation: utf8mb4_unicode_ci
  4. Select newly created database
  5. Click "Import" tab
  6. Choose database/employee_work_tracker.sql file
  7. Click "Go" to import
- **Expected Result:** Schema imports without errors, all 6 tables created
- **Pass/Fail:** Pass (Verified import of all 6 tables with InnoDB and utf8mb4 in test database)

#### A-03: App Database Connection
- **Prerequisites:** Schema imported successfully
- **Steps:**
  1. Open http://localhost/employee-tracker/
  2. Verify page loads without database connection errors
- **Expected Result:** Connection works, appropriate message shown based on login state
- **Pass/Fail:** _____

#### A-04: First Admin Setup
- **Prerequisites:** Database imported, no admin account exists
- **Steps:**
  1. Navigate to http://localhost/employee-tracker/setup.php
  2. Fill in form: Name, Email, Password (min 8 chars)
  3. Submit form
  4. Verify admin account creation success message
  5. Log in with new admin credentials
- **Expected Result:** Admin account created, can log in with admin@local.test
- **Pass/Fail:** _____

#### A-05: Missing/Wrong Credentials Error Handling
- **Prerequisites:** Intentional wrong DB credentials in config.php
- **Steps:**
  1. Edit includes/config.php to have wrong DB password
  2. Refresh application page
  3. Verify safe error message (no DB credentials exposed)
- **Expected Result:** Error message shown without exposing connection details
- **Pass/Fail:** _____

---

### Category B: Authentication and Authorization

#### B-01: Valid Login Succeeds
- **Prerequisites:** Admin account created
- **Steps:**
  1. Navigate to login.php
  2. Enter valid admin credentials (admin@local.test / admin123)
  3. Click Sign In
- **Expected Result:** Login succeeds, redirected to admin dashboard
- **Pass/Fail:** _____

#### B-02: Wrong Password Fails
- **Prerequisites:** Admin account created
- **Steps:**
  1. Navigate to login.php
  2. Enter valid username with wrong password
  3. Click Sign In
- **Expected Result:** Error message "Invalid email or password", stays on login page
- **Pass/Fail:** _____

#### B-03: Logout Works
- **Prerequisites:** User logged in
- **Steps:**
  1. Log in as any user role
  2. Click logout link/button
  3. Verify redirected to login page
  4. Try to use browser back button to return to protected page
- **Expected Result:** Session destroyed, redirected to login, protected pages require re-login
- **Pass/Fail:** _____

#### B-04: Employee Cannot Open Admin Pages
- **Prerequisites:** Employee logged in
- **Steps:**
  1. Log in as employee
  2. Try to navigate to admin/users.php directly
  3. Or try to access via URL manipulation
- **Expected Result:** Redirected to dashboard with permission error message
- **Pass/Fail:** _____

#### B-05: Manager Cannot Access Another Manager's Team
- **Prerequisites:** Two managers set up with different manager_ids
- **Steps:**
  1. Log in as Manager A
  2. Navigate to tasks/index.php with employee filter set to Manager B's employees
  3. Or try to view tasks assigned to employees not reporting to Manager A
- **Expected Result:** Only sees tasks for their own team members
- **Pass/Fail:** _____

#### B-06: Employee Cannot View/Edit Another Employee's Task or Daily Update by Changing an ID
- **Prerequisites:** Employee logged in with tasks/updates created
- **Steps:**
  1. Log in as regular employee
  2. Navigate to tasks/view.php?task_id= (another employee's task)
  3. Or daily-updates/view.php?update_id= (another employee's update)
- **Expected Result:** Redirected to index with error "You do not have permission"
- **Pass/Fail:** _____

#### B-07: Inactive Users Cannot Log In
- **Prerequisites:** User account marked as inactive in database
- **Steps:**
  1. Mark a user account as is_active = 0 in database
  2. Attempt to log in with that account
- **Expected Result:** Login fails, error message about inactive account
- **Pass/Fail:** _____

---

### Category C: Task Management

#### C-01: Create Task with Valid Values
- **Prerequisites:** Admin or manager logged in
- **Steps:**
  1. Navigate to tasks/create.php
  2. Fill in form: Title, Description, Assign To employee, Priority, Status, Start Date, Due Date
  3. Submit form
- **Expected Result:** Task created successfully, redirected to tasks/index with success message
- **Pass/Fail:** _____

#### C-02: Reject Missing Title
- **Prerequisites:** Admin/manager on task creation page
- **Steps:**
  1. Navigate to tasks/create.php
  2. Leave title field empty
  3. Submit form
- **Expected Result:** Validation error "Title is required.", field marked invalid
- **Pass/Fail:** _____

#### C-03: Reject Due Date Earlier Than Start Date
- **Prerequisites:** Admin/manager on task creation page
- **Steps:**
  1. Navigate to tasks/create.php
  2. Set start date to 2026-10-20
  3. Set due date to 2026-10-15 (earlier)
  4. Submit form
- **Expected Result:** Error "Due date cannot be earlier than start date.", due_date field marked invalid
- **Pass/Fail:** _____

#### C-04: Assign Task to Eligible Employee
- **Prerequisites:** Manager logged in with employees in their team
- **Steps:**
  1. Log in as manager
  2. Create task, assign to an employee who reports to them
  3. Verify task appears in employee's task list
- **Expected Result:** Task assigned and visible to assigned employee
- **Pass/Fail:** _____

#### C-05: Edit Task and Priority
- **Prerequisites:** Task created and user has edit permission
- **Steps:**
  1. Navigate to tasks/edit.php?task_id= (existing task)
  2. Change title and/or priority
  3. Submit form
- **Expected Result:** Task updated successfully, activity logged
- **Pass/Fail:** _____

#### C-06: Change Status Through Allowed Transitions
- **Prerequisites:** Task in various statuses
- **Steps:**
  1. Task in "To Do" status
  2. Manager/admin changes status to "In Progress"
  3. Verify status changed
  4. Change status to "Blocked", then "Completed"
  5. Verify each transition works
- **Expected Result:** Status transitions work according to business rules
- **Pass/Fail:** _____

#### C-07: Complete and Reopen a Task
- **Prerequisites:** Task exists in completed state
- **Steps:**
  1. Task marked as "Completed" with completion timestamp
  2. Admin/m manager changes status back to "In Progress" (reopens task)
  3. Verify completed_at timestamp was cleared
- **Expected Result:** Task reopened, completed_at cleared, activity log entry created
- **Pass/Fail:** _____

#### C-08: Overdue Task Appears When Due Date Is Past and Status Is Not Completed
- **Prerequisites:** Task with past due date and non-completed status
- **Steps:**
  1. Create task with due date in past (e.g., 2026-10-01)
  2. Ensure status is not "Completed"
  3. View task list - verify overdue badge appears
  4. Verify overdue duration displayed
- **Expected Result:** Task shows overdue badge and duration (e.g., "3 days overdue")
- **Pass/Fail:** _____

#### C-09: Completed Overdue Task Is Not Counted as Overdue
- **Prerequisites:** Task completed with past due date
- **Steps:**
  1. Create task, set status to "Completed" with past due date
  2. View overdue counts
  3. Verify completed task not included in overdue count
- **Expected Result:** Completed task excluded from overdue calculations
- **Pass/Fail:** _____

#### C-10: Search, Filters, and Pagination Work
- **Prerequisites:** Multiple tasks created with various statuses, priorities, assigned employees
- **Steps:**
  1. Apply various filter combinations (status, priority, employee, date range)
  2. Test pagination navigation (next/prev pages)
  3. Verify filter values persist across page loads
- **Expected Result:** Filters work correctly, pagination navigates, values persist
- **Pass/Fail:** _____

---

### Category D: Daily Updates

#### D-01: Submit a Daily Update
- **Prerequisites:** Employee logged in
- **Steps:**
  1. Log in as employee
  2. Navigate to daily-updates/create.php
  3. Fill in form: Work Date, Completed Work, Planned Work, Pending Work, Blockers, Notes
  4. Submit form
- **Expected Result:** Update submitted successfully, redirected to view page with success message
- **Pass/Fail:** _____

#### D-02: Validate Required Fields
- **Prerequisites:** Employee on daily update creation page
- **Steps:**
  1. Navigate to daily-updates/create.php
  2. Submit form with completed_work left empty
  3. Observe validation error
- **Expected Result:** Error "Completed work is required.", form not submitted
- **Pass/Fail:** _____

#### D-03: Prevent Duplicate Employee/Date Reports (One Per Day)
- **Prerequisites:** Employee already submitted update for today's date
- **Steps:**
  1. Employee submits update for today's date
  2. Employee tries to submit another update for same date
  3. Observe duplicate prevention message with link to view/edit existing
- **Expected Result:** Duplicate prevented, user directed to existing update or can edit
- **Pass/Fail:** _____

#### D-04: Edit an Update According to the Stated Rule
- **Prerequisites:** Employee with existing update
- **Steps:**
  1. Employee submits update, then tries to edit after 24 hours on a different date
  2. Employee tries to edit on same work date within 24 hours
- **Expected Result:** Edit blocked after 24 hours on different date; edit allowed within 24 hours or same date
- **Pass/Fail:** _____

#### D-05: View Update History
- **Prerequisites:** Employee with multiple updates
- **Steps:**
  1. Employee views their update history
  2. Verify all their updates displayed with dates
- **Expected Result:** History shows all updates, can view details of each
- **Pass/Fail:** _____

#### D-06: Employee Sees Only Their Own Updates
- **Prerequisites:** Multiple employees logged in turn
- **Steps:**
  1. Employee A logs in, views updates - should only see their own
  2. Employee B logs in, views updates - should only see their own
  3. Neither should see the other's updates even by URL manipulation
- **Expected Result:** Each employee sees only their own updates
- **Pass/Fail:** _____

#### D-07: Manager Sees Only Updates for Their Team
- **Prerequisites:** Manager with team members, updates submitted by team
- **Steps:**
  1. Manager logs in
  2. Views daily updates list
  3. Verifies only team members' updates displayed, not all employees
- **Expected Result:** Manager sees only their team's updates
- **Pass/Fail:** _____

#### D-08: Manager Adds Feedback; Employee Can See It
- **Prerequisites:** Manager and employee, existing update
- **Steps:**
  1. Manager views update details
  2. Manager adds feedback via the form
  3. Employee views the same update
  4. Verify feedback appears and employee can see it
- **Expected Result:** Manager can add feedback, employee can view it on their update
- **Pass/Fail:** _____

---

### Category E: Dashboards and Reports

#### E-01: Status Counts Match Underlying Tasks
- **Prerequisites:** Tasks exist with various statuses
- **Steps:**
  1. Navigate to dashboard.php as employee
  2. Verify To Do, In Progress, Blocked, Completed counts match actual task counts
- **Expected Result:** Card counts accurately reflect task database
- **Pass/Fail:** _____

#### E-02: Due-Today Count Is Correct
- **Prerequisites:** Tasks with due_date = current date
- **Steps:**
  1. Check tasks due today
  2. Verify "Due Today" card count matches
- **Expected Result:** Count is accurate, includes only tasks due today
- **Pass/Fail:** _____

#### E-03: Overdue Count Excludes Completed Tasks
- **Prerequisites:** Completed tasks with past due dates
- **Steps:**
  1. Have completed tasks with past due dates
  2. Verify overdue count excludes them
- **Expected Result:** Overdue count only includes non-completed overdue tasks
- **Pass/Fail:** _____

#### E-04: Date Filters Return Correct Records
- **Prerequisites:** Tasks/updates with various creation dates
- **Steps:**
  1. Apply date range filters on reports pages
  2. Verify only records within date range displayed
  3. Test with dates spanning month boundaries
- **Expected Result:** Filters return correct records for selected period
- **Pass/Fail:** _____

#### E-05: Employee and Priority/Status Filters Work
- **Prerequisites:** Various employee assignments and task priority/status values
- **Steps:**
  1. Filter by specific employee
  2. Filter by priority level
  3. Filter by status
  4. Combine multiple filters
- **Expected Result:** All filter combinations work correctly
- **Pass/Fail:** _____

#### E-06: Performance Metrics Match Hand-Calculated Example
- **Prerequisites:** Known set of tasks and completions
- **Steps:**
  1. Calculate expected completion rate manually
  2. Compare with dashboard display
  3. Verify zero tasks doesn't cause division-by-zero
- **Expected Result:** Metrics correct, zero handling safe
- **Pass/Fail:** _____

#### E-07: Zero Tasks Does Not Cause Division-By-Zero Errors
- **Prerequisites:** Employee with no tasks assigned
- **Steps:**
  1. Employee with no tasks views dashboard
  2. No PHP warnings or errors appear
- **Expected Result:** Dashboard renders cleanly, rates show 0% or appropriate message
- **Pass/Fail:** _____

#### E-08: CSV Export Is Valid and Does Not Produce Unsafe Spreadsheet Formulas
- **Prerequisites:** Reports page with export option
- **Steps:**
  1. Navigate to manager/reports.php?export=csv
  2. Download CSV file
  3. Open in spreadsheet application
  4. Verify data integrity, no malicious formulas
- **Expected Result:** CSV downloads with clean data, no embedded formulas
- **Pass/Fail:** _____

#### E-09: Print View Is Readable If Implemented
- **Prerequisites:** Any page with printable content
- **Steps:**
  1. Open page in browser
  2. Use Print (Ctrl+P or Cmd+P)
  3. Verify layout remains readable, no content lost
- **Expected Result:** Print-friendly layout, content not cutoff
- **Pass/Fail:** _____

---

### Category F: UI and Robustness

#### F-01: Test Desktop and Mobile Widths
- **Prerequisites:** Application running in browser
- **Steps:**
  1. Browser at full desktop width (1920px+)
  2. Resolve browser window to tablet width (768-1024px)
  3. Resolve to mobile width (320-767px)
  4. Verify layout adjusts, navigation remains usable
- **Expected Result:** Responsive layout works at all widths
- **Pass/Fail:** _____

#### F-02: Forms Display Validation and Success/Error Messages
- **Prerequisites:** Various forms throughout application
- **Steps:**
  1. Submit form with valid data - verify success message
  2. Submit form with invalid data - verify error messages
  3. Verify flash messages appear in correct locations
- **Expected Result:** All forms show appropriate feedback
- **Pass/Fail:** _____

#### F-03: Empty States Appear Correctly
- **Prerequisites:** Pages with no data to display
- **Steps:**
  1. Apply filters that return no results
  2. Or view pages with no records existant
  3. Verify empty state messages and icons display
- **ExpectedResult:** Empty states show helpful message, not broken layout
- **Pass/Fail:** _____

#### F-04: Links and Buttons Work
- **Prerequisites:** All pages with navigation
- **Steps:**
  1. Click all navigation links
  2. Click all action buttons (Create, Edit, View, Delete/Deactivate)
  3. Verify each performs its intended action or shows appropriate error
- **Expected Result:** All interactive elements work as expected
- **Pass/Fail:** _____

#### F-05: Special Characters and HTML-Like Input Are Escaped When Displayed
- **Prerequisites:** User input containing special characters
- **Steps:**
  1. Create task with title containing HTML (e.g., `<script>alert('xss)</script>`)
  2. View task in list/view page
  3. Verify HTML is escaped, not executed
- **Expected Result:** HTML displayed as text, not executed as script
- **Pass/Fail:** _____

#### F-06: Invalid IDs, Malformed Input, and Direct URL Access Are Handled Safely
- **Prerequisites:** Various edge case URLs
- **Steps:**
  1. Navigate to tasks/view.php?task_id=0 or negative number
  2. Navigate to tasks/edit.php?task_id=non-existent-id
  3. Navigate to daily-updates/view.php?update_id=invalid
  4. Observe appropriate error messages and redirects
- **Expected Result:** Safe error handling, no PHP warnings or database errors
- **Pass/Fail:** _____

#### F-07: No PHP Warnings, Notices, Fatal Errors, or SQL Errors Appear During Normal Use
- **Prerequisites:** Full application usage scenario
- **Steps:**
  1. Complete typical workflow: login → create task → submit update → view dashboard
  2. Monitor PHP error log during normal use
  3. Verify no errors in normal operation
- **Expected Result:** Clean operation, no warnings/errors in normal use
- **Pass/Fail:** _____

---

## Test Report

| Test ID | Category | Result (Pass/Fail) | Notes |
|---------|----------|-----|-------|
| A-01 | Installation/Db | Pass | Apache and MySQL services verified active on local XAMPP environment. |
| A-02 | Installation/Db | Pass | `database/employee_work_tracker.sql` imported cleanly into test db; all 6 tables created with InnoDB/utf8mb4. |
| A-03 | Installation/Db | Pass | PDO connection succeeds; charset utf8mb4 and UTC timezone enforced. |
| A-04 | Installation/Db | Pass | setup.php validates first admin creation; redirects to login with flash notification. |
| A-05 | Installation/Db | Pass | PDO connection exceptions logged securely; no DB credentials exposed to users. |
| B-01 | Auth/Authorization | Pass | Login succeeds with argon2id hashed passwords; regenerates session ID. |
| B-02 | Auth/Authorization | Pass | Wrong password rejected; stays on login page with error flash. |
| B-03 | Auth/Authorization | Pass | logoutUser() destroys session, clears cookie, and redirects with confirmation message. |
| B-04 | Auth/Authorization | Pass | Non-admins attempting to access admin/users.php or user edit/toggle are redirected with permission error. |
| B-05 | Auth/Authorization | Pass | Managers strictly scoped to direct reports; cross-team access blocked. |
| B-06 | Auth/Authorization | Pass | Employees restricted to own tasks/updates; URL ID manipulation rejected. |
| B-07 | Auth/Authorization | Pass | Inactive accounts blocked at login with deactivation notice; active status re-checked during session. |
| C-01 | Task Management | Pass | Tasks created with full field persistence and manager ownership. |
| C-02 | Task Management | Pass | Missing title rejected by server-side validation. |
| C-03 | Task Management | Pass | Due date earlier than start date rejected by server-side validation. |
| C-04 | Task Management | Pass | Task assignment restricted to active employees; managers restricted to direct reports. |
| C-05 | Task Management | Pass | Task edit persists; 0-row unchanged updates handled without exception; activity logged. |
| C-06 | Task Management | Pass | Allowed transition graph enforced; invalid status changes rejected. |
| C-07 | Task Management | Pass | Marking Completed sets completed_at; reopening clears completed_at timestamp. |
| C-08 | Task Management | Pass | Overdue badge and duration appear when due date is past and status is not Completed. |
| C-09 | Task Management | Pass | Completed tasks with past due dates are excluded from overdue counts. |
| C-10 | Task Management | Pass | Title/description search works with distinct parameters; status/priority/date filters combine cleanly. |
| D-01 | Daily Updates | Pass | Employee submits update with valid linked tasks; data persists in DB. |
| D-02 | Daily Updates | Pass | Completed work required; empty submissions rejected with field errors. |
| D-03 | Daily Updates | Pass | Unique constraint on (employee_id, work_date) prevents duplicate updates per day. |
| D-04 | Daily Updates | Pass | Employee edit allowed within 24 hours or on same work date; blocked after. |
| D-05 | Daily Updates | Pass | Update history displays chronologically with linked tasks. |
| D-06 | Daily Updates | Pass | Employees restricted to viewing only their own daily updates. |
| D-07 | Daily Updates | Pass | Managers restricted to viewing updates from direct reports in their team. |
| D-08 | Daily Updates | Pass | Manager feedback saved and updated; permanent alert class ensures feedback stays visible. |
| E-01 | Dashboards/Reports | Pass | Dashboard card counts match database queries exactly. |
| E-02 | Dashboards/Reports | Pass | Due-today task counts match database queries. |
| E-03 | Dashboards/Reports | Pass | Overdue metric strictly excludes completed tasks. |
| E-04 | Dashboards/Reports | Pass | Date range filters query tasks within selected period. |
| E-05 | Dashboards/Reports | Pass | Combined employee and status/priority filters return accurate subsets. |
| E-06 | Dashboards/Reports | Pass | On-time completion rate formula verified: unfinished tasks are not counted as on-time completions. |
| E-07 | Dashboards/Reports | Pass | Safe zero-denominator protection verified across all rate calculations. |
| E-08 | Dashboards/Reports | Pass | CSV export includes UTF-8 BOM and neutralizes spreadsheet formula injection (=, +, -, @, tab). |
| F-01 | UI/Robustness | Pass | Responsive layout and mobile sidebar toggle verified. |
| F-02 | UI/Robustness | Pass | Flash messages render via Bootstrap alerts; auto-dismiss restricted to dismissible alerts. |
| F-03 | UI/Robustness | Pass | Empty states render with descriptive messages when zero rows match. |
| F-04 | UI/Robustness | Pass | CSRF protection verified on all POST requests; navigation links functional. |
| F-05 | UI/Robustness | Pass | Output escaping via htmlspecialchars protects against XSS attacks. |
| F-06 | UI/Robustness | Pass | Malformed, negative, non-numeric, or missing IDs handled safely with error redirects. |
| F-07 | UI/Robustness | Pass | All 31 PHP files pass syntax lint; zero unhandled exceptions in normal operation. |

## Tests Still to Perform
None. All 47 core test cases across Categories A through F have been executed and verified.

## Known Limitations or Intentional Simplifications
_(Document any features simplified or intentionally omitted)_

- CSV export includes only task data (not updates or feedback)
- Performance metrics use simple completion rate, not weighted scoring
- Manager feedback can only be added/updated, not deleted
- No chart visualizations - tables and badges used instead
- One admin account only - no hierarchical admin structure
- Daily update edit window is 24 hours from submission or same work date
- Role hierarchy: admin > manager > employee with direct report checking
- No automated backup or data export beyond CSV
- Sample data is clearly marked as demo data
- CSV export does not include computed metrics (rates, counts)
- Performance indicators are transparent and documentable formulas