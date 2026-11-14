-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 13, 2026 at 03:26 PM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `library_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `authors`
--

CREATE TABLE `authors` (
  `author_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `country` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `authors`
--

INSERT INTO `authors` (`author_id`, `name`, `country`) VALUES
(1, 'Chinua Achebe', 'Nigeria'),
(2, 'Ngugi wa Thiong\'o', 'Kenya'),
(3, 'William Shakespeare', 'England'),
(4, 'Jane Austen', 'England'),
(5, 'George Orwell', 'England');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `isbn` varchar(20) NOT NULL,
  `title` varchar(200) NOT NULL,
  `category_id` int(11) NOT NULL,
  `publisher` varchar(150) DEFAULT NULL,
  `year_published` int(11) DEFAULT NULL,
  `total_copies` int(11) NOT NULL DEFAULT 1,
  `available_copies` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `isbn`, `title`, `category_id`, `publisher`, `year_published`, `total_copies`, `available_copies`) VALUES
(1, '9780435905255', 'Things Fall Apart', 1, 'Heinemann', 1958, 3, 2),
(2, '9780141187761', 'A Grain of Wheat', 1, 'Penguin', 1967, 3, 2),
(3, '9780743477109', 'Hamlet', 5, 'Simon & Schuster', 1603, 3, 2),
(4, '9780141439518', 'Pride and Prejudice', 1, 'Penguin', 1813, 3, 3),
(5, '9780451524935', 'Animal Farm', 1, 'Signet Classics', 1945, 3, 2),
(6, '9780451526342', '1984', 1, 'Signet Classics', 1949, 3, 2),
(7, '9780141439600', 'Emma', 1, 'Penguin', 1815, 3, 2),
(8, '9780140449136', 'Macbeth', 5, 'Penguin', 1606, 3, 3),
(9, '9780140449266', 'Othello', 5, 'Penguin', 1603, 3, 3),
(10, '9780199535569', 'Sense and Sensibility', 1, 'Oxford', 1811, 3, 2);

-- --------------------------------------------------------

--
-- Table structure for table `book_authors`
--

CREATE TABLE `book_authors` (
  `book_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book_authors`
--

INSERT INTO `book_authors` (`book_id`, `author_id`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5),
(6, 5),
(7, 4),
(8, 3),
(9, 3),
(10, 4);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `description`) VALUES
(1, 'Fiction', 'Imaginative stories and novels'),
(3, 'History', 'Historical events and accounts'),
(4, 'Science', 'Scientific knowledge and discoveries'),
(5, 'Drama stories', 'Plays and dramatic literature'),
(6, 'principles of software engineering', 'coding');

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `loan_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `date_issued` date NOT NULL DEFAULT curdate(),
  `due_date` date NOT NULL,
  `date_returned` date DEFAULT NULL,
  `status` enum('borrowed','returned') NOT NULL DEFAULT 'borrowed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`loan_id`, `member_id`, `book_id`, `date_issued`, `due_date`, `date_returned`, `status`) VALUES
(1, 1, 1, '2026-10-07', '2026-10-21', '2026-10-12', 'returned'),
(2, 2, 2, '2026-10-22', '2026-11-05', NULL, 'borrowed'),
(3, 3, 3, '2026-11-08', '2026-11-22', NULL, 'borrowed'),
(4, 4, 4, '2026-10-02', '2026-10-16', '2026-10-17', 'returned'),
(5, 5, 5, '2026-11-01', '2026-11-15', NULL, 'borrowed'),
(6, 6, 6, '2026-10-12', '2026-10-26', '2026-10-27', 'returned'),
(7, 7, 7, '2026-11-09', '2026-11-23', NULL, 'borrowed'),
(8, 8, 8, '2026-10-17', '2026-10-31', '2026-11-01', 'returned'),
(9, 9, 9, '2026-09-27', '2026-10-11', '2026-10-12', 'returned'),
(10, 10, 10, '2026-11-10', '2026-11-24', NULL, 'borrowed'),
(11, 7, 8, '2026-11-12', '2026-11-26', '2026-11-12', 'returned');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `member_id` int(11) NOT NULL,
  `member_number` varchar(30) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `date_joined` date NOT NULL DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`member_id`, `member_number`, `full_name`, `email`, `phone`, `status`, `date_joined`) VALUES
(1, 'MEM001', 'Locha Lawrence', 'lochaawrence@gmail.com', '0701000001', 'active', '2026-11-11'),
(2, 'MEM002', 'Christabella Buberwa', 'christabella@gmail.com', '0701000002', 'active', '2026-11-11'),
(3, 'MEM003', 'Aine Benjamin', 'ainebenjamin@gmail.com', '0701000003', 'active', '2026-11-11'),
(4, 'MEM004', 'Muhindo Brillian', 'muhindobrillian@gmail.com', '0701000004', 'active', '2026-11-11'),
(5, 'MEM005', 'Cherop Devis', 'cheropdevis@gmail.com', '0701000005', 'active', '2026-11-11'),
(6, 'MEM006', 'Twinomujuni David', 'twinomujunidavid@gmail.com', '0701000006', 'active', '2026-11-11'),
(7, 'MEM007', 'Adungo Gabriel', 'gabrieladungo@gmail.com', '0701000007', 'active', '2026-11-11'),
(8, 'MEM008', 'Atim Ritah', 'atimritah@gmail.com', '0701000008', 'active', '2026-11-11'),
(9, 'MEM009', 'Robert Ochieng', 'robert@example.com', '0701000009', 'suspended', '2026-11-11'),
(10, 'MEM010', 'Faith Namuli', 'faith@example.com', '0701000010', 'active', '2026-11-11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `role` enum('admin','librarian') NOT NULL DEFAULT 'librarian',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `role`, `created_at`) VALUES
(1, 'testadmin', '$2y$12$Z/RWbxNSdk9Zua0aQXXGgO96cD5rsPYvUa.bSfgHexwPAIfEPKr8m', 'Library Administrator', 'admin', '2026-11-12 13:33:25');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `authors`
--
ALTER TABLE `authors`
  ADD PRIMARY KEY (`author_id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`),
  ADD UNIQUE KEY `isbn` (`isbn`),
  ADD KEY `fk_book_category` (`category_id`);

--
-- Indexes for table `book_authors`
--
ALTER TABLE `book_authors`
  ADD PRIMARY KEY (`book_id`,`author_id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`loan_id`),
  ADD KEY `idx_loan_member` (`member_id`),
  ADD KEY `idx_loan_book` (`book_id`),
  ADD KEY `idx_loan_status` (`status`),
  ADD KEY `idx_loan_due_date` (`due_date`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`member_id`),
  ADD UNIQUE KEY `member_number` (`member_number`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `authors`
--
ALTER TABLE `authors`
  MODIFY `author_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `loan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `fk_book_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `book_authors`
--
ALTER TABLE `book_authors`
  ADD CONSTRAINT `book_authors_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `book_authors_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `authors` (`author_id`);

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`),
  ADD CONSTRAINT `loans_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
