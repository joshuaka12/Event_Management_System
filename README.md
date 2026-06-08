

# 🎓 Campus Event Management System (Campus EMS)

Campus EMS is a web-based application designed to help universities and colleges manage campus events efficiently. The system allows administrators, event organizers, and students to interact through a secure role-based platform.

Built using **PHP, MySQL, HTML, CSS, and JavaScript**, the project does not rely on any frameworks, making it easy to understand, customize, and deploy.

##  Features

### Administrator

Administrators have full control over the system and can:

* View platform statistics, including users, events, and registrations
* Manage all users and events
* Delete any event or user account when necessary
* Access organizer functionalities

### Event Organizer

Organizers can:

* Create and manage campus events
* Edit or delete events they have created
* View students registered for their events
* Monitor event-related statistics from their dashboard

### Student

Students can:

* Browse available events
* Search for events by keyword
* Register for upcoming events
* Cancel registrations when needed
* View their registered events and recommended upcoming events

## 🛡️ Security

The system includes several security measures to ensure data protection and safe user access:

* Passwords are securely stored using bcrypt hashing
* Prepared statements are used to prevent SQL injection attacks
* User-generated content is sanitized to reduce XSS risks
* Sessions are regenerated during login to prevent session fixation
* Role-based access control protects restricted pages
* Duplicate event registrations are prevented
* Event capacity limits are enforced on the server side
* State-changing actions require POST requests

## 🗄️ Database Structure

The application uses three main tables:

### Users

Stores account information for administrators, organizers, and students.

### Events

Stores event details such as title, description, venue, date, capacity, and creator information.

### Registrations

Tracks student registrations for events and prevents duplicate registrations.

## 🚀 Installation

### 1. Clone or Download the Project

Place the project folder inside your web server directory:

**XAMPP**

```text
htdocs/campus_ems
```

**WAMP**

```text
www/campus_ems
```

### 2. Create the Database

Open phpMyAdmin and create a database named:

```sql
campus_ems
```

Import the provided `database.sql` file to create all required tables and sample data.

### 3. Configure the Database Connection

Open `config/db.php` and update the database credentials if necessary:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'campus_ems');
```

### 4. Run the Application

Start Apache and MySQL, then visit:

```text
http://localhost/campus_ems/
```



## 🎨 User Experience

The interface is designed to be clean, modern, and responsive. It includes:

* Mobile-friendly layouts
* Responsive navigation menu
* Interactive event cards
* Dashboard statistics
* Smooth animations and visual feedback
* Easy-to-use event management tools

## 🔧 Future Improvements

Some ideas for future development include:

* Email notifications for registrations and event updates
* Event image uploads
* Advanced search and filtering
* Pagination for large datasets
* Event categories and tags
* QR code check-in system
* Attendance tracking and reporting

## 📚 Technologies Used

* PHP
* MySQL
* HTML5
* CSS3
* JavaScript
* Bootstrap Icons / Font Awesome (optional)

## 👨‍💻 Author

This project was developed as a campus event management solution to simplify event planning, registration, and administration within educational institutions.

Feel free to fork, modify, and improve the project to suit your own requirements.

This version reads more like something a developer would naturally write on GitHub while still looking professional and polished.
