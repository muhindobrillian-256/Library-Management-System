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
Project documentation reviewed and updated

## 11 Psedocode

1. Main System Algorithm
BEGIN

    DISPLAY "LIBRARY MANAGEMENT SYSTEM"

    CONNECT TO DATABASE library_db

    IF database connection is successful THEN

        DISPLAY "1. Login"
        DISPLAY "2. Exit"

        INPUT choice

        IF choice = 1 THEN
            CALL LOGIN

        ELSE IF choice = 2 THEN
            DISPLAY "System Closed"
            STOP

        ELSE
            DISPLAY "Invalid Choice"
        ENDIF

    ELSE
        DISPLAY "Database Connection Failed"
    ENDIF

END

2. User Login and Authentication
   BEGIN LOGIN

    INPUT username
    INPUT password

    SEARCH users TABLE FOR username

    IF username EXISTS THEN

        VERIFY password USING password_hash

        IF password is correct THEN

            CREATE USER SESSION

            IF user_role = "Administrator" THEN
                DISPLAY ADMIN DASHBOARD

            ELSE IF user_role = "Librarian" THEN
                DISPLAY LIBRARIAN DASHBOARD
            ENDIF

        ELSE
            DISPLAY "Incorrect Password"
        ENDIF

    ELSE
        DISPLAY "Username Not Found"
    ENDIF

END LOGIN

3. Dashboard Management
  BEGIN DASHBOARD

    CHECK USER SESSION

    IF session is valid THEN

        COUNT total books FROM books TABLE

        COUNT total members FROM members TABLE

        COUNT total authors FROM authors TABLE

        COUNT available books FROM books TABLE

        COUNT active loans FROM loans TABLE

        COUNT overdue loans FROM loans TABLE

        DISPLAY total books
        DISPLAY total members
        DISPLAY total authors
        DISPLAY available books
        DISPLAY active loans
        DISPLAY overdue loans

    ELSE
        REDIRECT TO LOGIN PAGE
    ENDIF

END DASHBOARD

4. Book Management
This algorithm handles adding, viewing, updating and deleting books.

4.1 Add New Book

text
BEGIN ADD_BOOK

    INPUT book_title
    INPUT ISBN
    INPUT category_id
    INPUT publisher
    INPUT publication_year
    INPUT total_copies

    IF required fields are empty THEN

        DISPLAY "Please Fill All Required Fields"

    ELSE IF ISBN already EXISTS THEN

        DISPLAY "Book Already Exists"

    ELSE IF total_copies <= 0 THEN

        DISPLAY "Invalid Number of Copies"

    ELSE

        INSERT book details INTO books TABLE

        SET available_copies = total_copies

        DISPLAY "Book Added Successfully"

    ENDIF

END ADD_BOOK
4.2 View Books

text
BEGIN VIEW_BOOKS

    RETRIEVE all records FROM books TABLE

    IF records EXIST THEN

        FOR EACH book IN books TABLE

            DISPLAY book_id
            DISPLAY book_title
            DISPLAY ISBN
            DISPLAY category
            DISPLAY total_copies
            DISPLAY available_copies

        ENDFOR

    ELSE

        DISPLAY "No Books Available"

    ENDIF

END VIEW_BOOKS
4.3 Update Book Details

text
BEGIN UPDATE_BOOK

    INPUT book_id

    SEARCH books TABLE USING book_id

    IF book EXISTS THEN

        DISPLAY current book details

        INPUT updated book details

        VALIDATE entered information

        UPDATE books TABLE

        DISPLAY "Book Updated Successfully"

    ELSE

        DISPLAY "Book Not Found"

    ENDIF

END UPDATE_BOOK
4.4 Delete Book

text
BEGIN DELETE_BOOK

    INPUT book_id

    SEARCH books TABLE USING book_id

    IF book EXISTS THEN

        CHECK whether book has outstanding loans

        IF outstanding loans EXIST THEN

            DISPLAY "Cannot Delete Book With Active Loans"

        ELSE

            ASK user for confirmation

            IF confirmation = YES THEN

                DELETE book FROM books TABLE

                DELETE related book_authors records

                DISPLAY "Book Deleted Successfully"

            ENDIF

        ENDIF

    ELSE

        DISPLAY "Book Not Found"

    ENDIF

END DELETE_BOOK
5. Author Management
This algorithm manages library authors.

5.1 Add Author

text
BEGIN ADD_AUTHOR

    INPUT author_name
    INPUT biography

    IF author_name IS EMPTY THEN

        DISPLAY "Author Name Is Required"

    ELSE

        INSERT author details INTO authors TABLE

        DISPLAY "Author Added Successfully"

    ENDIF

END ADD_AUTHOR
5.2 View and Update Authors

text
BEGIN MANAGE_AUTHORS

    DISPLAY "1. View Authors"
    DISPLAY "2. Update Author"
    DISPLAY "3. Delete Author"

    INPUT choice

    IF choice = 1 THEN

        RETRIEVE all authors
        DISPLAY author records

    ELSE IF choice = 2 THEN

        INPUT author_id
        SEARCH author record

        IF author EXISTS THEN
            INPUT updated details
            UPDATE authors TABLE
            DISPLAY "Author Updated"
        ELSE
            DISPLAY "Author Not Found"
        ENDIF

    ELSE IF choice = 3 THEN

        INPUT author_id
        CHECK whether author is linked to books

        IF author can be deleted THEN
            DELETE author
            DISPLAY "Author Deleted"
        ELSE
            DISPLAY "Author Is Linked To Existing Books"
        ENDIF

    ELSE
        DISPLAY "Invalid Choice"

    ENDIF

END MANAGE_AUTHORS
6. Category Management
This algorithm manages book categories.


text
BEGIN MANAGE_CATEGORIES

    DISPLAY "1. Add Category"
    DISPLAY "2. View Categories"
    DISPLAY "3. Update Category"
    DISPLAY "4. Delete Category"

    INPUT choice

    IF choice = 1 THEN

        INPUT category_name

        IF category_name IS NOT EMPTY THEN

            INSERT category_name INTO categories TABLE

            DISPLAY "Category Added Successfully"

        ELSE

            DISPLAY "Category Name Required"

        ENDIF

    ELSE IF choice = 2 THEN

        RETRIEVE all categories
        DISPLAY categories

    ELSE IF choice = 3 THEN

        INPUT category_id
        SEARCH category

        IF category EXISTS THEN
            INPUT new_category_name
            UPDATE categories TABLE
            DISPLAY "Category Updated"
        ELSE
            DISPLAY "Category Not Found"
        ENDIF

    ELSE IF choice = 4 THEN

        INPUT category_id

        CHECK whether category is assigned to books

        IF category is not assigned THEN
            DELETE category
            DISPLAY "Category Deleted"
        ELSE
            DISPLAY "Cannot Delete Category In Use"
        ENDIF

    ELSE

        DISPLAY "Invalid Choice"

    ENDIF

END MANAGE_CATEGORIES
7. Member Management
This algorithm handles registration and management of library members.

7.1 Register New Member

text
BEGIN REGISTER_MEMBER

    INPUT member_name
    INPUT email
    INPUT phone_number
    INPUT address

    IF member_name IS EMPTY OR email IS EMPTY THEN

        DISPLAY "Required Information Missing"

    ELSE IF email already EXISTS THEN

        DISPLAY "Member Already Registered"

    ELSE

        GENERATE member_id

        INSERT member details INTO members TABLE

        DISPLAY "Member Registered Successfully"

    ENDIF

END REGISTER_MEMBER
7.2 View, Update and Delete Members

text
BEGIN MANAGE_MEMBERS

    DISPLAY "1. View Members"
    DISPLAY "2. Update Member"
    DISPLAY "3. Delete Member"

    INPUT choice

    IF choice = 1 THEN

        RETRIEVE all members
        DISPLAY member records

    ELSE IF choice = 2 THEN

        INPUT member_id

        SEARCH members TABLE

        IF member EXISTS THEN

            INPUT updated member details

            UPDATE members TABLE

            DISPLAY "Member Updated Successfully"

        ELSE

            DISPLAY "Member Not Found"

        ENDIF

    ELSE IF choice = 3 THEN

        INPUT member_id

        CHECK member's outstanding loans

        IF no outstanding loans EXIST THEN

            DELETE member

            DISPLAY "Member Deleted Successfully"

        ELSE

            DISPLAY "Cannot Delete Member With Outstanding Loans"

        ENDIF

    ELSE

        DISPLAY "Invalid Choice"

    ENDIF

END MANAGE_MEMBERS
8. Book Borrowing / Issuing
This algorithm allows librarians to issue books to registered members.


text
BEGIN ISSUE_BOOK

    INPUT member_id
    INPUT book_id
    INPUT borrowing_date
    INPUT due_date

    SEARCH members TABLE USING member_id

    IF member DOES NOT EXIST THEN

        DISPLAY "Member Not Registered"

    ELSE

        SEARCH books TABLE USING book_id

        IF book DOES NOT EXIST THEN

            DISPLAY "Book Not Found"

        ELSE

            IF available_copies > 0 THEN

                CHECK member borrowing eligibility

                IF member is eligible THEN

                    BEGIN DATABASE TRANSACTION

                    INSERT loan details INTO loans TABLE

                    SET loan_status = "Borrowed"

                    REDUCE available_copies BY 1

                    COMMIT DATABASE TRANSACTION

                    DISPLAY "Book Issued Successfully"

                ELSE

                    ROLLBACK TRANSACTION

                    DISPLAY "Member Cannot Borrow More Books"

                ENDIF

            ELSE

                DISPLAY "Book Currently Unavailable"

            ENDIF

        ENDIF

    ENDIF

END ISSUE_BOOK
9. Book Return Management
This algorithm processes returned books and updates available copies.


text
BEGIN RETURN_BOOK

    INPUT loan_id

    SEARCH loans TABLE USING loan_id

    IF loan DOES NOT EXIST THEN

        DISPLAY "Loan Record Not Found"

    ELSE

        IF loan_status = "Borrowed" THEN

            GET current_date

            BEGIN DATABASE TRANSACTION

            UPDATE loans TABLE

            SET return_date = current_date

            SET loan_status = "Returned"

            INCREASE available_copies BY 1

            COMMIT DATABASE TRANSACTION

            IF current_date > due_date THEN

                CALCULATE overdue_days

                DISPLAY "Book Returned Late"

                DISPLAY overdue_days

            ELSE

                DISPLAY "Book Returned On Time"

            ENDIF

            DISPLAY "Book Return Processed Successfully"

        ELSE

            DISPLAY "Book Has Already Been Returned"

        ENDIF

    ENDIF

END RETURN_BOOK
10. Search and Filter Records
This algorithm allows users to search for books and other library records.


text
BEGIN SEARCH_RECORDS

    DISPLAY "1. Search Books"
    DISPLAY "2. Search Members"
    DISPLAY "3. Search Authors"
    DISPLAY "4. Search Categories"

    INPUT search_choice

    INPUT search_keyword

    IF search_choice = 1 THEN

        SEARCH books USING
        book_title OR ISBN OR category

    ELSE IF search_choice = 2 THEN

        SEARCH members USING
        member_name OR email OR phone_number

    ELSE IF search_choice = 3 THEN

        SEARCH authors USING author_name

    ELSE IF search_choice = 4 THEN

        SEARCH categories USING category_name

    ELSE

        DISPLAY "Invalid Search Option"

    ENDIF

    IF matching records EXIST THEN

        DISPLAY matching records

    ELSE

        DISPLAY "No Matching Records Found"

    ENDIF

END SEARCH_RECORDS
11. Overdue Books Management
This algorithm identifies books that have not been returned by their due dates.


text
BEGIN CHECK_OVERDUE_BOOKS

    GET current_date

    RETRIEVE loans WHERE

        loan_status = "Borrowed"

        AND due_date < current_date

    IF overdue records EXIST THEN

        FOR EACH loan IN overdue records

            CALCULATE overdue_days

            DISPLAY member_name
            DISPLAY book_title
            DISPLAY due_date
            DISPLAY overdue_days

        ENDFOR

    ELSE

        DISPLAY "No Overdue Books Found"

    ENDIF

END CHECK_OVERDUE_BOOKS
12. Logout Management
This algorithm securely terminates a user's session.


text
BEGIN LOGOUT

    CHECK USER SESSION

    IF session EXISTS THEN

        DESTROY USER SESSION

        CLEAR SESSION DATA

        REDIRECT TO LOGIN PAGE

        DISPLAY "Logged Out Successfully"

    ELSE

        DISPLAY "No Active Session"

    ENDIF

END LOGOUT
13. System Security Algorithm
This algorithm describes the security procedures applied throughout the application.


text
BEGIN SECURITY_CHECK

    CHECK USER AUTHENTICATION

    IF user is NOT authenticated THEN

        REDIRECT TO LOGIN PAGE

    ELSE

        VERIFY USER ROLE AND PERMISSIONS

        VALIDATE ALL USER INPUT

        VERIFY CSRF TOKEN FOR STATE-CHANGING REQUESTS

        USE PREPARED SQL STATEMENTS

        ESCAPE OUTPUT BEFORE DISPLAYING

        VERIFY PASSWORD USING PASSWORD_HASH

        IF security checks PASS THEN

            ALLOW REQUEST PROCESSING

        ELSE

            REJECT REQUEST

            DISPLAY "Access Denied"

        ENDIF

    ENDIF

END SECURITY_CHECK
14. Overall System Flowchart
The following flowchart summarizes how the Library Management System operates from login to logout.

START

Connect to Database

User Login

Validate Username and Password

Invalid Login

Display error and retry

Valid Login

Open dashboard

Display Main Dashboard

Select Management Operation

Manage Books

Manage Authors

Manage Categories

Manage Members

Issue Books

Return Books

Search Records

View Reports

Save / Update Database Records

Logout

END

