<div align="center">

<br />

# 🗺️ NearTrip
### Local Tourist Day-Visit Planner

**Discover, Plan & Explore attractions within a 25 km radius of Dorawaka & Warakapola, Sri Lanka**

<br />

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mariadb.org/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Leaflet](https://img.shields.io/badge/Leaflet.js-Interactive_Maps-199900?style=for-the-badge&logo=leaflet&logoColor=white)](https://leafletjs.com/)
[![XAMPP](https://img.shields.io/badge/XAMPP-Apache-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)](https://www.apachefriends.org/)
[![License](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)](LICENSE)

<br />

</div>

---

## 📖 Table of Contents

- [Overview](#-overview)
- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Project Structure](#-project-structure)
- [Database Schema](#-database-schema)
- [Getting Started](#-getting-started)
- [Application URLs](#-application-urls)
- [User Roles](#-user-roles)
- [Default Credentials](#-default-credentials)
- [Troubleshooting](#-troubleshooting)
- [Contributing](#-contributing)
- [License](#-license)

---

## 🌟 Overview

**NearTrip** is a full-stack PHP/MySQL web application built to solve a real problem — the lack of centralized, reliable tourism information for the **Dorawaka & Warakapola** region of the **Kegalle District, Sabaragamuwa Province, Sri Lanka**.

Many of Sri Lanka's breathtaking local destinations remain under-documented and difficult to discover online. NearTrip bridges this gap by providing:

- 📍 A curated, verified directory of tourist attractions within a **25 km radius**
- 🗓️ A drag-and-arrange **one-day itinerary planner** so visitors can build a personalized schedule
- 🗺️ An **interactive Leaflet.js map** with live GPS-coordinate markers and rich popups
- 🛡️ A dedicated **Safety & Travel Guidelines** page with emergency hotlines
- 🔐 A **role-based admin panel** for content management and user administration

> Built to promote local heritage and improve visitor safety in the Kegalle District.

---

## ✨ Features

### 🌍 Public Features

| Feature | Description |
|---|---|
| **Home Page** | Dynamic time-based greeting, hero section, feature highlights, and About section |
| **Destinations Explorer** | Grid of all attractions with live search and multi-criteria category filtering |
| **Attraction Detail Pages** | Full detail view: description, GPS coords, opening hours, fees, facilities, travel tips, safety guidelines |
| **Interactive Map** | Leaflet.js map centered on Dorawaka (7.1592°N, 80.2020°E) with dynamically placed markers and rich popups |
| **Itinerary Planner** | Session-based trip builder — add, remove, reorder, and save personalized one-day itineraries |
| **Safety Guidelines** | Emergency contact hotlines, per-attraction safety information, and verified travel tips |
| **Responsive Design** | Mobile-first layout with pure CSS hamburger navigation and flexible grids |

### 🔐 Authentication & User Features

| Feature | Description |
|---|---|
| **Registration / Login** | Email + password authentication with PHP `password_hash` / `password_verify` |
| **Session Management** | 30-minute inactivity timeout with automatic session destruction and user notification |
| **Session Fixation Protection** | `session_regenerate_id(true)` on every successful login |
| **User Dashboard** | View saved itineraries and quick-action planning buttons |
| **User Profile** | Update account details and change password securely |
| **Role-Based Navigation** | Navigation menu adapts dynamically to Guest / User / Admin roles |

### 🛠️ Admin Panel Features

| Feature | Description |
|---|---|
| **Admin Dashboard** | Live statistics cards (total attractions, categories, active mapped spots) + recent attractions table |
| **Attraction Management** | Full CRUD — add, edit, delete attractions with all 14 fields (including GPS, fees, facilities, tips) |
| **Category Management** | Create and manage attraction categories |
| **User Management** | View all registered accounts, promote/demote roles (`admin` ↔ `user`) |
| **Route Guards** | All admin pages protected by `requireAdmin()` — unauthorized access redirects to login |

### 🔒 Security Highlights

- All SQL queries use **MySQLi Prepared Statements** with bound parameters
- All output escaped with `htmlspecialchars()` and `strip_tags()`
- Input sanitized with `trim()`, `intval()`, and strict type casting
- Server-side validation on all form submissions
- PHP `password_hash()` (BCRYPT) for credential storage — no plain-text passwords
- Session timeout (30 min inactivity) with forced re-authentication
- Role-based route guards: `requireLogin()`, `requireAdmin()`, `requireUser()`

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | PHP 8.0+ (Procedural with OOP MySQLi) |
| **Database** | MariaDB 10.4 / MySQL (via MySQLi extension) |
| **Frontend** | HTML5, Vanilla CSS, vanilla JavaScript |
| **Mapping** | Leaflet.js 1.9.4 + OpenStreetMap tiles |
| **UI Framework** | Bootstrap 5 (bundled locally in `assets/vendor/`) |
| **Icons** | Font Awesome 6.5.1 (CDN) |
| **Typography** | Google Fonts — Poppins (300, 400, 500, 600, 700) |
| **Server** | Apache (XAMPP for Windows) |
| **Charset** | UTF-8 (`utf8mb4_general_ci`) |
| **Timezone** | Asia/Colombo (UTC+5:30) |

---

## 📁 Project Structure

```
neartrip/
│
├── index.php                    # Home page (hero, about, features)
├── places.php                   # Destinations listing with search & category filter
├── details.php                  # Single attraction detail page
├── map.php                      # Interactive Leaflet.js map
├── plan.php                     # One-day itinerary planner
├── itinerary.php                # Itinerary redirect handler
├── safety.php                   # Safety guidelines & emergency contacts
├── 404.php                      # Custom 404 error page
├── neartrip.sql                 # Complete database dump (schema + seed data)
├── SETUP_GUIDE.txt              # Plain-text setup instructions
│
├── includes/                    # Shared PHP components
│   ├── config.php               # DB connection with multi-port fallback
│   ├── auth.php                 # Session, timeout, role helpers & route guards
│   ├── functions.php            # Global helpers (render_no_results)
│   ├── header.php               # Global HTML <head> + role-aware navbar
│   ├── footer.php               # Global footer with quick links
│   └── no_results.php           # Reusable empty-state UI component
│
├── admin/                       # Admin panel (requireAdmin() protected)
│   ├── login.php                # Login & registration (dual-tab form)
│   ├── logout.php               # Session destruction & redirect
│   ├── dashboard.php            # Stats overview + recent attractions table
│   ├── attractions.php          # Attraction listing with edit/delete
│   ├── add_attraction.php       # Add new attraction (14-field form)
│   ├── edit_attraction.php      # Edit existing attraction
│   ├── delete_attraction.php    # Delete attraction with confirmation
│   ├── categories.php           # Category management CRUD
│   └── users.php                # User management & role promotion
│
├── user/                        # User area (requireUser() protected)
│   ├── dashboard.php            # User dashboard with saved itineraries
│   └── profile.php              # Profile & password management
│
├── assets/
│   ├── css/
│   │   ├── style.css            # Master stylesheet (~82 KB, custom design system)
│   │   └── admin.css            # Admin panel stylesheet
│   ├── js/
│   │   ├── main.js              # Global JavaScript
│   │   ├── map.js               # Leaflet.js map initialization
│   │   └── itinerary.js         # Itinerary reorder logic
│   ├── vendor/
│   │   ├── bootstrap/           # Bootstrap 5 (local bundle)
│   │   └── leaflet/             # Leaflet.js (local bundle)
│   ├── logo.png                 # NearTrip brand logo
│   ├── hero.webp                # Hero background image
│   └── *.webp                   # Attraction images
│
├── images/                      # Additional static images
├── uploads/
│   └── attractions/             # Target directory for uploaded images
└── docs/
    ├── User_Guide.pdf           # End-user documentation
    └── Admin_Guide.pdf          # Administrator documentation
```

---

## 🗄️ Database Schema

The `neartrip` database contains **5 tables**:

```
neartrip
├── administrator        ← User & admin accounts (unified table, role enum)
├── category             ← Attraction categories
├── attraction           ← Core records with 14 fields (inc. GPS)
├── itinerary            ← Saved one-day trip plans (per user)
└── itinerary_attraction ← Junction table: itinerary ↔ attraction
```

---

## 🚀 Getting Started

### Prerequisites

- **OS:** Windows (XAMPP recommended)
- **XAMPP** with Apache + MySQL/MariaDB enabled
- **PHP 8.0+** with extensions: `mysqli`, `mysqlnd`, `mbstring`, `json`, `session`
- **Browser** with internet access (CDN fonts, icons, and OpenStreetMap tiles)
- **Git** (optional, for cloning)

---

### Installation

**Step 1 — Clone into your XAMPP web root:**

```bash
cd C:\xampp\htdocs
git clone https://github.com/YOUR-USERNAME/neartrip.git neartrip
```

Or download and extract the ZIP into `C:\xampp\htdocs\neartrip`.

**Step 2 — Start XAMPP services:**

Open **XAMPP Control Panel** and start:
- ✅ Apache
- ✅ MySQL

---

### Database Setup

**Step 3 — Create the database:**

Go to [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)

1. Click **New** in the left sidebar
2. Create a database named exactly **`neartrip`**
3. Set collation to `utf8mb4_general_ci`

**Step 4 — Import the SQL dump:**

1. Select the `neartrip` database
2. Open the **Import** tab
3. Choose `neartrip.sql` from the project root
4. Click **Go** and wait for the success message

> ⚠️ **Important:** The SQL dump does **not** create the database itself. You must create it first. Do not import into an already-populated database without clearing the tables first.

**Command-line alternative:**

```bash
# XAMPP with port 3307
C:\xampp\mysql\bin\mysql.exe -u root -P 3307 neartrip < neartrip.sql

# Standard port 3306
C:\xampp\mysql\bin\mysql.exe -u root -P 3306 neartrip < neartrip.sql
```

---

### Configuration

**Step 5 — Set the correct port in `includes/config.php`:**

```php
define('DB_SERVER',   '127.0.0.1');
define('DB_PORT',     3307);        // ← Change to 3306 if needed
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');          // ← Add password if your MySQL requires one
define('DB_NAME',     'neartrip');
```

> 💡 **Finding your MySQL port:** Open XAMPP Control Panel → MySQL → **Config** → `my.ini` and look for the `port=` value.

The app has multi-port fallback logic, but explicitly setting the correct port is strongly recommended.

---

## 🔗 Application URLs

| Page | URL |
|---|---|
| 🏠 Home | `http://localhost/neartrip/` |
| 📍 Destinations | `http://localhost/neartrip/places.php` |
| 🗺️ Interactive Map | `http://localhost/neartrip/map.php` |
| 🗓️ Itinerary Planner | `http://localhost/neartrip/plan.php` |
| 🛡️ Safety Guidelines | `http://localhost/neartrip/safety.php` |
| 🔐 Login / Register | `http://localhost/neartrip/admin/login.php` |
| 📊 Admin Dashboard | `http://localhost/neartrip/admin/dashboard.php` |
| ➕ Add Attraction | `http://localhost/neartrip/admin/add_attraction.php` |
| 🏷️ Manage Categories | `http://localhost/neartrip/admin/categories.php` |
| 👥 User Management | `http://localhost/neartrip/admin/users.php` |
| 👤 My Dashboard | `http://localhost/neartrip/user/dashboard.php` |
| ⚙️ My Profile | `http://localhost/neartrip/user/profile.php` |

---

## 👥 User Roles

NearTrip uses a **single `administrator` table** with an `ENUM('admin', 'user')` role column:

| Role | Access |
|---|---|
| **Guest** | Public pages only: home, destinations, map, safety, attraction details |
| **User** | Guest access + itinerary planning, saving itineraries, personal dashboard & profile |
| **Admin** | Full access: all user features + complete admin panel (CRUD on attractions, categories, users) |

### Promoting a User to Admin

After registering, run this in phpMyAdmin (**SQL tab**):

```sql
UPDATE administrator
SET role = 'admin'
WHERE email = 'your-email@example.com';
```

Log out and log back in for the role change to take effect.

---

## 🔑 Default Credentials

> ⚠️ The SQL dump contains **bcrypt-hashed passwords**, not plain text. Contact the project author for the actual login passwords, or create a new admin as described below.

| Account | Email | Role |
|---|---|---|
| Default Admin | `admin@neartrip.com` | `admin` |
| Default User | `user@neartrip.com` | `user` |

### Creating a Fresh Admin Account

1. Go to: `http://localhost/neartrip/admin/login.php?action=register`
2. Register with your email and a strong password
3. Promote to admin via phpMyAdmin (see above)
4. Log in at: `http://localhost/neartrip/admin/login.php`

---

## 🛠️ Troubleshooting

| Problem | Solution |
|---|---|
| **Database connection error** | Confirm MySQL is running. Verify `DB_PORT` in `includes/config.php` matches the port shown in XAMPP. Most common mismatch: config uses `3307` but MySQL runs on `3306`. |
| **Import errors / missing tables** | Ensure the `neartrip` database exists **before** importing. Use a clean, empty database when retrying. |
| **Admin pages redirect to login** | Verify the account has role exactly `admin` in phpMyAdmin, then log out and log back in to refresh the session. |
| **Images not displaying** | Check that `image_url` is a valid path relative to the project root (e.g., `assets/dunumala.webp`) and the file physically exists. |
| **Map not loading / blank map** | Verify `latitude` and `longitude` are non-null and non-zero in the database. Confirm the browser can reach Leaflet CDN and OpenStreetMap tile servers (internet required). |
| **Blank page / white screen** | Enable PHP error reporting or check the Apache error log. Ensure `mysqli` and `mysqlnd` extensions are enabled in `php.ini`. |
| **Session keeps expiring quickly** | Default inactivity timeout is **30 minutes**. Adjust `SESSION_TIMEOUT_DURATION` in `includes/auth.php`. |
| **Registration always creates user role** | By design. Public registration always creates a `user` role. Promote to `admin` manually via phpMyAdmin. |

---

## 🤝 Contributing

Contributions, bug reports, and feature requests are welcome!

1. **Fork** the repository
2. **Create** a feature branch: `git checkout -b feature/your-feature-name`
3. **Commit** your changes with a descriptive message: `git commit -m "feat: add your feature"`
4. **Push** to the branch: `git push origin feature/your-feature-name`
5. **Open** a Pull Request with a clear description of your changes

### Coding Standards

- Use **MySQLi prepared statements** for **all** database queries — never raw string interpolation
- Escape all output with `htmlspecialchars()`
- Follow the existing file/folder naming conventions
- Passwords must be hashed with PHP `password_hash()` — never store plain text
- Test on both port `3306` and `3307` configurations

---

## 📄 License

This project is licensed under the **MIT License** — see the [LICENSE](LICENSE) file for full details.

---

<div align="center">

**Built with ❤️ to promote local tourism in Kegalle District, Sri Lanka**

*NearTrip — Centralizing reliable travel details within 25 km of Dorawaka & Warakapola*

⭐ Star this repo if you find it useful!

</div>
