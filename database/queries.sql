
-- ==========================================
-- LIBRARY MANAGEMENT SYSTEM
-- DATABASE QUERIES
-- ==========================================

USE library_db;

-- ==========================================
-- QUERY 1: Display books with their categories
-- and authors (JOIN)
-- ==========================================

SELECT
    b.book_id,
    b.isbn,
    b.title,
    c.name AS category,
    GROUP_CONCAT(a.name SEPARATOR ', ') AS authors,
    b.publisher,
    b.year_published,
    b.total_copies,
    b.available_copies
FROM books b
INNER JOIN categories c
    ON b.category_id = c.category_id
LEFT JOIN book_authors ba
    ON b.book_id = ba.book_id
LEFT JOIN authors a
    ON ba.author_id = a.author_id
GROUP BY
    b.book_id,
    b.isbn,
    b.title,
    c.name,
    b.publisher,
    b.year_published,
    b.total_copies,
    b.available_copies
ORDER BY b.title;


-- ==========================================
-- QUERY 2: Display borrowing records with
-- member and book information (JOIN)
-- ==========================================

SELECT
    l.loan_id,
    m.member_number,
    m.full_name AS member_name,
    b.title AS book_title,
    l.date_issued,
    l.due_date,
    l.date_returned,
    l.status
FROM loans l
INNER JOIN members m
    ON l.member_id = m.member_id
INNER JOIN books b
    ON l.book_id = b.book_id
ORDER BY l.date_issued DESC;


-- ==========================================
-- QUERY 3: Display authors and their books
-- (JOIN)
-- ==========================================

SELECT
    a.author_id,
    a.name AS author_name,
    b.book_id,
    b.title AS book_title
FROM authors a
INNER JOIN book_authors ba
    ON a.author_id = ba.author_id
INNER JOIN books b
    ON ba.book_id = b.book_id
ORDER BY a.name, b.title;


-- ==========================================
-- QUERY 4: Count books in each category
-- (AGGREGATE QUERY)
-- ==========================================

SELECT
    c.category_id,
    c.name AS category_name,
    COUNT(b.book_id) AS total_books
FROM categories c
LEFT JOIN books b
    ON c.category_id = b.category_id
GROUP BY
    c.category_id,
    c.name
ORDER BY total_books DESC;
