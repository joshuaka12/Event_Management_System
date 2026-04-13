-- ============================================================
-- Campus Event Management System - Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS campus_ems CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campus_ems;

-- ============================================================
-- Table: users
-- Stores all user accounts (admin, organizer, student)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,          -- Hashed via password_hash()
    role        ENUM('admin','organizer','student') NOT NULL DEFAULT 'student',
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Table: events
-- Stores all campus events created by organizers/admins
-- ============================================================
CREATE TABLE IF NOT EXISTS events (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(200)  NOT NULL,
    description  TEXT          NOT NULL,
    event_date   DATETIME      NOT NULL,
    venue        VARCHAR(200)  NOT NULL,
    capacity     INT           NOT NULL DEFAULT 100,
    created_by   INT           NOT NULL,          -- FK → users.id
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Table: registrations
-- Tracks which students registered for which events
-- ============================================================
CREATE TABLE IF NOT EXISTS registrations (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,                  -- FK → users.id
    event_id       INT NOT NULL,                  -- FK → events.id
    status         ENUM('registered','cancelled') NOT NULL DEFAULT 'registered',
    registered_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id)  ON DELETE CASCADE,
    UNIQUE KEY unique_registration (user_id, event_id)  -- Prevent duplicates
) ENGINE=InnoDB;

-- ============================================================
-- Seed Data – Default Users
-- Passwords are all:  Password@123
-- ============================================================
INSERT INTO users (name, email, password, role) VALUES
('Super Admin',       'admin@campus.edu',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Event Organizer',   'organizer@campus.edu', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'organizer'),
('Jane Student',      'student@campus.edu',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- ============================================================
-- Seed Data – Sample Events
-- ============================================================
INSERT INTO events (title, description, event_date, venue, capacity, created_by) VALUES
('Tech Innovation Summit 2026',
 'Annual summit bringing together students, faculty, and industry leaders to explore cutting-edge technology trends. Keynotes, workshops, and networking sessions.',
 '2026-04-15 09:00:00', 'Main Auditorium', 300, 2),

('Cultural Night 2026',
 'A vibrant celebration of campus diversity featuring performances, food stalls, art exhibitions, and music from around the world.',
 '2026-04-22 18:00:00', 'Campus Grounds', 500, 2),

('Career Fair – Engineering & IT',
 'Meet recruiters from 50+ top companies. Bring your CV and be ready for on-spot interviews. Open to all engineering and IT students.',
 '2026-05-05 10:00:00', 'Sports Hall', 400, 2),

('Mental Health Awareness Workshop',
 'An interactive workshop on stress management, mindfulness, and maintaining well-being during academic life. Free refreshments provided.',
 '2026-05-10 14:00:00', 'Lecture Hall B', 80, 2),

('Startup Pitch Competition',
 'Present your startup idea to a panel of investors and mentors. Cash prizes and mentorship opportunities for top 3 teams.',
 '2026-05-20 09:00:00', 'Business School Atrium', 150, 2);

-- ============================================================
-- Seed Data – Sample Registrations
-- ============================================================
INSERT INTO registrations (user_id, event_id, status) VALUES
(3, 1, 'registered'),
(3, 3, 'registered');
