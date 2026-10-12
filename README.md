# E-Health Government Service Portal

B.Sc. CSIT (6th Semester) project, National Infotech College, Tribhuvan University.
Plain **PHP 8 + MySQL + Bootstrap 5**, runs on **XAMPP** (tested on Ubuntu).

---

## 1. Run it on Ubuntu with XAMPP (step by step)

**Requirement:** XAMPP with **PHP 8.0 or newer** (any XAMPP 8.x download).

### Step 1. Install XAMPP (skip if already installed)
```bash
# download the Linux installer from https://www.apachefriends.org then:
cd ~/Downloads
chmod +x xampp-linux-x64-*-installer.run
sudo ./xampp-linux-x64-*-installer.run        # installs into /opt/lampp
```

### Step 2. Free ports 80 and 3306 (only if Ubuntu's own Apache/MySQL are installed)
```bash
sudo systemctl stop apache2 mysql 2>/dev/null
sudo systemctl disable apache2 mysql 2>/dev/null
```

### Step 3. Start XAMPP
```bash
sudo /opt/lampp/lampp start
```
You should see `Apache is running` and `MySQL is running`.

### Step 4. Copy the project into htdocs
```bash
unzip ehealth-portal.zip
sudo cp -r ehealth-portal /opt/lampp/htdocs/
sudo chmod -R 755 /opt/lampp/htdocs/ehealth-portal
```

### Step 5. Import the database (choose ONE way)
**A. phpMyAdmin:** open <http://localhost/phpmyadmin> -> **Import** tab -> *Choose file* ->
`/opt/lampp/htdocs/ehealth-portal/database/ehealth_db.sql` -> **Import**.
(The script creates the database `ehealth_portal` itself.)

**B. Terminal:**
```bash
/opt/lampp/bin/mysql -u root < /opt/lampp/htdocs/ehealth-portal/database/ehealth_db.sql
```

### Step 6. Open the website
<http://localhost/ehealth-portal/>

### Demo accounts
| Role | Email | Password |
|---|---|---|
| Administrator | admin@ehealth.gov.np | Admin@123 |
| Doctor | doctor1@ehealth.gov.np (to doctor6@) | Doctor@123 |
| Citizen | citizen1@example.com (to citizen3@) | Citizen@123 |

New citizens can also self-register on the Register page.

### Stop XAMPP
```bash
sudo /opt/lampp/lampp stop
```

### Troubleshooting
| Problem | Fix |
|---|---|
| "Database connection failed" | MySQL not running (`sudo /opt/lampp/lampp start`) or SQL not imported (Step 5). If you set a MySQL root password, put it in `config/config.php` (`DB_PASS`). |
| Apache will not start (port 80 busy) | Step 2, or `sudo fuser -k 80/tcp` then start again. |
| MySQL will not start (port 3306 busy) | `sudo systemctl stop mysql` then `sudo /opt/lampp/lampp startmysql`. |
| 403 Forbidden / blank page | `sudo chmod -R 755 /opt/lampp/htdocs/ehealth-portal` |
| CSS/icons missing | Project must be inside `htdocs`. The URL path is auto-detected, so any folder name works. |
| Want to reset all data | Import `database/ehealth_db.sql` again (it drops and recreates the tables). |

---

## 2. What the synopsis asked for, and where it is in the code

| Synopsis module | Implemented in |
|---|---|
| Citizen registration and login | `register.php`, `login.php`, `logout.php`, `includes/auth.php` |
| Health service information management | `services.php` (public), `admin/services.php`, `admin/service_form.php` |
| Online appointment request and scheduling | `citizen/book.php` (service -> doctor -> date -> free slot), `api/doctors.php`, `api/slots.php` |
| Doctor / service provider management | `admin/doctors.php`, `admin/doctor_form.php`, public `doctors.php` |
| Health record management with authorized access | `doctor/record_add.php`, `doctor/records.php`, `citizen/records.php`, `admin/records.php`, `record.php` (printable) |
| Appointment and request status tracking | `citizen/appointments.php`, status workflow in `includes/functions.php`, `notifications.php` |
| Administrator dashboard and user management | `admin/dashboard.php`, `admin/users.php`, `admin/user_form.php`, `admin/reports.php`, `admin/logs.php` |

**Roles:** Citizen, Doctor (authorized service provider), Administrator, each with its own panel and menu.

### Appointment status workflow
```
pending -> approved -> completed (-> doctor writes health record)
   |           |
   +-> rejected  +-> cancelled      (citizen, doctor or admin can cancel)
```
Every change sends a notification to the citizen and is written to the activity log.

---

## 3. Database (matches your ER diagram)

Tables: `users`, `health_services`, `doctors`, `appointments`, `health_records`
(+ `notifications`, `activity_logs`). Relationships:

* USER **books** APPOINTMENT (`appointments.user_id`)
* APPOINTMENT is **for** HEALTH_SERVICE (`service_id`) and **assigned to** DOCTOR (`doctor_id`)
* DOCTOR **creates** HEALTH_RECORD (`doctor_id`), USER **owns** HEALTH_RECORD (`user_id`)

Additions beyond the ER diagram (mention these in your viva):
* `doctors.user_id`: links a doctor to a login account so doctors can sign in.
* `appointments.reason`, `appointments.admin_note`: visit reason and remarks from doctor/admin.
* `health_records.appointment_id`: links a record to the visit it came from.
* `notifications`: "receive service-related notifications" (synopsis abstract).
* `activity_logs`: traceability of actions.
* `users.gender, dob, address, status, failed_logins, locked_until`: profile and security.

**Service requests:** a citizen's request for a service is the appointment request. It starts as
`pending` and is tracked through approval, rejection, completion or cancellation.

---

## 4. Security measures (objective: "role-based access and basic security")

* Passwords hashed with `password_hash()` (bcrypt); never stored in plain text.
* **Prepared statements (PDO)** everywhere, so SQL injection is prevented.
* Output escaped with `htmlspecialchars` (`e()`), so XSS is prevented.
* **CSRF token** on every form; logout is POST-only.
* **Role-based access control**: `require_role()` on every protected page; citizens only see their own data; doctors only act on their own appointments and see records of their own patients; admins see everything, and each record view is logged.
* Session hardening: HttpOnly + SameSite cookies, session ID regenerated at login.
* **Brute-force protection**: 5 wrong passwords lock the account for 10 minutes.
* Double-booking prevention: database row lock plus slot re-check inside a transaction.
* Inactive users cannot log in; CSV export neutralises spreadsheet formula injection.

---

## 5. Folder structure
```
ehealth-portal/
├── index.php, services.php, doctors.php, about.php, contact.php   public pages
├── login.php, register.php, logout.php, profile.php               authentication / profile
├── notifications.php, record.php                                  shared by all roles
├── citizen/   dashboard, book, appointments, records
├── doctor/    dashboard, appointments, patients, records, record_add
├── admin/     dashboard, appointments, services, doctors, users, records, reports, logs (+ forms)
├── api/       doctors.php, slots.php  (JSON for the booking form)
├── includes/  db.php, functions.php, auth.php, layout.php
├── config/    config.php  (DB settings, slot times, holiday)
├── database/  ehealth_db.sql
└── assets/    css, js, vendor (Bootstrap 5, Bootstrap Icons, Chart.js: works offline)
```

### Settings you can change (`config/config.php`)
`DB_*` (database), `SLOT_START` / `SLOT_END` / `SLOT_MINUTES` (clinic hours), `MAX_BOOKING_DAYS`, `OFF_DAY` (6 = Saturday).

---

## 6. Suggested demo flow (for your presentation)
1. Home -> Services -> Doctors (public information).
2. Register a new citizen -> log in -> **Book appointment** (pick service, doctor, date, slot).
3. Log in as **doctor1** -> My Appointments -> **Approve** -> later **Complete + record** -> write diagnosis/prescription.
4. Back as the citizen: see the **notifications**, status change and the **health record** (View / Print).
5. Log in as **admin**: dashboard chart, manage services/doctors/users, **Reports** (CSV export), **Activity Log**.
6. Show security: try opening `/admin/dashboard.php` as a citizen (blocked), or a wrong password five times (account lock).

## 7. Future work (from the synopsis conclusion)
SMS/e-mail notifications, uploading medical documents, mobile app, integration with other government systems.

*This is an academic prototype and not a certified medical system.*
# ehealthportal
