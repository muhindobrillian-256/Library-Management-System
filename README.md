# Library Management System

## 1. Project Description

The Library Management System is a web-based application developed using PHP, MySQL, HTML and CSS. It helps librarians manage books, authors, categories, members and borrowing activities.

## 2. Technologies Used

- PHP
- MySQL / MariaDB
- HTML
- CSS
- JavaScript
- XAMPP
- phpMyAdmin

## 3. Installation Instructions

1. Install XAMPP.
2. Copy the Library_system folder into `C:\xampp\htdocs\`.
3. Open the XAMPP Control Panel.
4. Start Apache and MySQL.
5. Open phpMyAdmin at `http://localhost/phpmyadmin/`.
6. Import `database/library_db.sql` to create the database and sample data.
7. Open the application at `http://localhost/Library_system/`.

## 4. Database

Database name: `library_db`

The database contains seven tables:

- authors
- books
- book_authors
- categories
- members
- users
- loans

## 5. Main Features

- Administrator and librarian login.
- Dashboard with library statistics.
- Add, view, edit and delete books.
- Manage authors and categories.
- Register and manage library members.
- Issue books to members.
- Process book returns.
- Search and filter records.
- Track overdue and outstanding loans.
- Manage available book copies.

## 6. Test Login Account

Username: `testadmin`

Password: `Library@2026`

This is a sample account for testing and demonstration.

## 7. Project Structure

- auth/ - Login and logout.
- books/ - Book management.
- members/ - Member management.
- loans/ - Borrowing and returns.
- authors/ - Author management.
- categories/ - Category management.
- dashboard/ - Main dashboard.
- config/ - Database connection.
- database/ - SQL files and ERD.
- includes/ - Shared PHP functions.

## 8. Security

The application uses prepared SQL statements, password hashing and verification, session authentication, input validation, output escaping and CSRF protection.

## 9. Developer

Library Management System - Database II CAT 2 Project.

## 10. Prerequisites

well done
