# Campus Event Management System (Campus EMS)

A full-featured, role-based campus event management web application built with **PHP**, **MySQL**, **HTML**, **CSS**, and **JavaScript** — no frameworks required.

---

## 📁 Project Structure

```
campus_ems/
├── config/
│   └── db.php                  # Database connection config
├── auth/
│   └── auth.php                # Session helpers, role guards, flash messages
├── admin/
│   ├── dashboard.php           # Admin overview: users, events, stats
│   ├── delete_event.php        # Admin: delete any event
│   └── delete_user.php         # Admin: remove a user account
├── organizer/
│   ├── dashboard.php           # Organizer stats + event list
│   ├── create_event.php        # Create OR edit an event (dual-mode)
│   ├── manage_events.php       # View attendee lists per event
│   └── delete_event.php        # Organizer: delete own event
├── student/
│   ├── dashboard.php           # Student's registered events + suggestions
│   └── register_event.php      # Register for / cancel an event
├── includes/
│   ├── header.php              # Shared nav, HTML <head>, flash message
│   └── footer.php              # Shared footer, JS includes
├── assets/
│   ├── css/style.css           # Full design system
│   └── js/main.js              # Nav toggle, search, animations
├── index.php                   # Public homepage – browse & search events
├── register.php                # New user registration
├── login.php                   # Login page
├── logout.php                  # Session destroy + redirect
├── event_details.php           # Full event detail page
├── database.sql                # Complete DB schema + seed data
└── README.md                   # This file
```

---

## ⚙️ Requirements

| Requirement | Minimum Version |
|---|---|
| PHP | 7.4+ (8.x recommended) |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Web Server | Apache (XAMPP/WAMP) or Nginx |
| PHP Extension | `mysqli` enabled |

---

## 🚀 Setup Instructions

### Step 1 — Clone / Copy the project

Place the `campus_ems/` folder inside your web server's document root:

- **XAMPP (Windows/macOS):** `C:/xampp/htdocs/campus_ems/`
- **WAMP (Windows):** `C:/wamp64/www/campus_ems/`
- **Linux/macOS Apache:** `/var/www/html/campus_ems/`

### Step 2 — Create the Database

1. Open **phpMyAdmin** (http://localhost/phpmyadmin) or your MySQL client.
2. Import the database file:
   - Click **Import** → choose `database.sql` → click **Go**.
   
   **Or** run it from the terminal:
   ```bash
   mysql -u root -p < /path/to/campus_ems/database.sql
   ```
   
   This creates the `campus_ems` database, all three tables, and seeds demo data.

### Step 3 — Configure Database Credentials

Open `config/db.php` and edit:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // ← your MySQL username
define('DB_PASS', '');          // ← your MySQL password (often blank on XAMPP)
define('DB_NAME', 'campus_ems');
```

### Step 4 — Launch

Open your browser and navigate to:

```
http://localhost/campus_ems/
```

---

## 🔑 Demo Accounts

All demo accounts share the password: **`password`**

| Role | Email | Password |
|---|---|---|
| Admin | admin@campus.edu | password |
| Organizer | organizer@campus.edu | password |
| Student | student@campus.edu | password |

> **Note:** The SQL seed data uses a bcrypt hash for `"password"`. The login page shows these credentials as a hint.

---

## 👥 User Roles & Permissions

### 🔴 Admin
- View platform-wide statistics (users, events, registrations)
- View and delete **any** event
- View and remove **any** user account
- Access the organizer tools (create/edit events)

### 🟢 Organizer
- Create new events with title, description, date/time, venue, capacity
- Edit and delete their **own** events
- View the list of students registered for each event
- Dashboard with personal event stats

### 🔵 Student
- Browse and search all events
- Register for upcoming events (capacity-enforced, duplicate-protected)
- Cancel registrations
- Dashboard showing registered events + upcoming suggestions

---

## 🛡️ Security Features

| Feature | Implementation |
|---|---|
| Password hashing | `password_hash(PASSWORD_BCRYPT)` / `password_verify()` |
| SQL injection prevention | MySQLi **prepared statements** throughout |
| XSS prevention | `htmlspecialchars()` on all output (via `e()` helper) |
| Session fixation prevention | `session_regenerate_id(true)` on login |
| Role-based access control | `require_login($roles)` guard on every protected page |
| Duplicate registrations | `UNIQUE KEY (user_id, event_id)` + PHP pre-check |
| Capacity enforcement | Server-side check before every registration |
| CSRF (basic) | Form POST required for state-changing actions |

---

## 🗄️ Database Schema

### `users`
| Column | Type | Notes |
|---|---|---|
| id | INT AUTO_INCREMENT | Primary key |
| name | VARCHAR(100) | Display name |
| email | VARCHAR(150) UNIQUE | Login identifier |
| password | VARCHAR(255) | bcrypt hash |
| role | ENUM('admin','organizer','student') | Access level |
| created_at | DATETIME | Auto-set |

### `events`
| Column | Type | Notes |
|---|---|---|
| id | INT AUTO_INCREMENT | Primary key |
| title | VARCHAR(200) | Event name |
| description | TEXT | Full description |
| event_date | DATETIME | When the event occurs |
| venue | VARCHAR(200) | Location |
| capacity | INT | Max attendees |
| created_by | INT (FK → users.id) | Who created it |
| created_at | DATETIME | Auto-set |

### `registrations`
| Column | Type | Notes |
|---|---|---|
| id | INT AUTO_INCREMENT | Primary key |
| user_id | INT (FK → users.id) | Student |
| event_id | INT (FK → events.id) | Event |
| status | ENUM('registered','cancelled') | Registration state |
| registered_at | DATETIME | Auto-set |
| — | UNIQUE(user_id, event_id) | Prevents duplicates |

---

## 🎨 Design Notes

- **Fonts:** DM Serif Display (headings) + DM Sans (body) via Google Fonts
- **Color palette:** Warm parchment background, deep campus-red accent, teal secondary
- **Responsive:** Mobile-first with hamburger nav, fluid grids
- **Animations:** Stat number count-up, event card hover lift, flash slide-in

---

## 🔧 Customisation Tips

- **Add a new role:** Add to the `ENUM` in `users`, update `require_login()` calls, create a new dashboard folder.
- **Email notifications:** Hook into registration/cancellation handlers and use PHP `mail()` or a library like PHPMailer.
- **Event images:** Add an `image` column to `events`, handle file uploads in `create_event.php`.
- **Pagination:** The admin user table currently shows 10; wrap queries with `LIMIT`/`OFFSET`.
- **Production:** Set `display_errors = Off`, use HTTPS, restrict DB user permissions.

---

## 📄 License

MIT — free to use, modify, and distribute.
