-- SQL Script to check and fix the AlkanSave database tables
-- Run this in phpMyAdmin SQL tab or using the MySQL command line client

-- Step 1: Make sure we're using the correct database
USE alkansave;

-- Step 2: Check and fix the Goal table
-- Check if the Goal table exists
SELECT EXISTS (
    SELECT 1 
    FROM information_schema.tables 
    WHERE table_schema = 'alkansave' 
    AND table_name = 'Goal'
) AS Goal_table_exists;

-- Check if SavedAmount column exists in Goal table
SELECT EXISTS (
    SELECT 1 
    FROM information_schema.columns 
    WHERE table_schema = 'alkansave' 
    AND table_name = 'Goal' 
    AND column_name = 'SavedAmount'
) AS SavedAmount_column_exists;

-- Add SavedAmount column if it doesn't exist
ALTER TABLE Goal 
ADD COLUMN IF NOT EXISTS SavedAmount DECIMAL(10,2) NOT NULL DEFAULT 0;

-- Check if CompletionDate column exists in Goal table
SELECT EXISTS (
    SELECT 1 
    FROM information_schema.columns 
    WHERE table_schema = 'alkansave' 
    AND table_name = 'Goal' 
    AND column_name = 'CompletionDate'
) AS CompletionDate_column_exists;

-- Add CompletionDate column if it doesn't exist
ALTER TABLE Goal 
ADD COLUMN IF NOT EXISTS CompletionDate DATE NULL;

-- Check if Status column exists in Goal table
SELECT EXISTS (
    SELECT 1 
    FROM information_schema.columns 
    WHERE table_schema = 'alkansave' 
    AND table_name = 'Goal' 
    AND column_name = 'Status'
) AS Status_column_exists;

-- Add Status column if it doesn't exist
ALTER TABLE Goal 
ADD COLUMN IF NOT EXISTS Status VARCHAR(50) NOT NULL DEFAULT 'Active';

-- Check if IsDeleted column exists in Goal table
SELECT EXISTS (
    SELECT 1 
    FROM information_schema.columns 
    WHERE table_schema = 'alkansave' 
    AND table_name = 'Goal' 
    AND column_name = 'IsDeleted'
) AS IsDeleted_column_exists;

-- Add IsDeleted column if it doesn't exist
ALTER TABLE Goal 
ADD COLUMN IF NOT EXISTS IsDeleted BOOLEAN NOT NULL DEFAULT FALSE;

-- Check if UpdatedAt column exists in Goal table
SELECT EXISTS (
    SELECT 1 
    FROM information_schema.columns 
    WHERE table_schema = 'alkansave' 
    AND table_name = 'Goal' 
    AND column_name = 'UpdatedAt'
) AS UpdatedAt_column_exists;

-- Add UpdatedAt column if it doesn't exist
ALTER TABLE Goal 
ADD COLUMN IF NOT EXISTS UpdatedAt TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Step 3: Check and fix the SavingsTransaction table
-- Check if the SavingsTransaction table exists
SELECT EXISTS (
    SELECT 1 
    FROM information_schema.tables 
    WHERE table_schema = 'alkansave' 
    AND table_name = 'SavingsTransaction'
) AS SavingsTransaction_table_exists;

-- Create the SavingsTransaction table if it doesn't exist
CREATE TABLE IF NOT EXISTS SavingsTransaction (
    TransactionID INT AUTO_INCREMENT PRIMARY KEY,
    GoalID INT NOT NULL,
    Amount DECIMAL(10,2) NOT NULL,
    DateSaved DATE NOT NULL,
    IsDeleted BOOLEAN NOT NULL DEFAULT FALSE,
    CreatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (GoalID) REFERENCES Goal(GoalID)
);

-- Check if IsDeleted column exists in SavingsTransaction table
SELECT EXISTS (
    SELECT 1 
    FROM information_schema.columns 
    WHERE table_schema = 'alkansave' 
    AND table_name = 'SavingsTransaction' 
    AND column_name = 'IsDeleted'
) AS Transaction_IsDeleted_column_exists;

-- Add IsDeleted column if it doesn't exist
ALTER TABLE SavingsTransaction 
ADD COLUMN IF NOT EXISTS IsDeleted BOOLEAN NOT NULL DEFAULT FALSE;

-- Check if UpdatedAt column exists in SavingsTransaction table
SELECT EXISTS (
    SELECT 1 
    FROM information_schema.columns 
    WHERE table_schema = 'alkansave' 
    AND table_name = 'SavingsTransaction' 
    AND column_name = 'UpdatedAt'
) AS Transaction_UpdatedAt_column_exists;

-- Add UpdatedAt column if it doesn't exist
ALTER TABLE SavingsTransaction 
ADD COLUMN IF NOT EXISTS UpdatedAt TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Step 4: Check and fix the Category table
-- Check if the Category table exists
SELECT EXISTS (
    SELECT 1 
    FROM information_schema.tables 
    WHERE table_schema = 'alkansave' 
    AND table_name = 'Category'
) AS Category_table_exists;

-- Create the Category table if it doesn't exist
CREATE TABLE IF NOT EXISTS Category (
    CategoryID INT AUTO_INCREMENT PRIMARY KEY,
    CategoryName VARCHAR(100) NOT NULL,
    UserID INT NULL, -- NULL means it's a system category
    DateCreated DATETIME NOT NULL,
    IsDeleted BOOLEAN NOT NULL DEFAULT FALSE,
    UpdatedAt TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Check if IsDeleted column exists in Category table
SELECT EXISTS (
    SELECT 1 
    FROM information_schema.columns 
    WHERE table_schema = 'alkansave' 
    AND table_name = 'Category' 
    AND column_name = 'IsDeleted'
) AS Category_IsDeleted_column_exists;

-- Add IsDeleted column if it doesn't exist
ALTER TABLE Category 
ADD COLUMN IF NOT EXISTS IsDeleted BOOLEAN NOT NULL DEFAULT FALSE;

-- Check if UpdatedAt column exists in Category table
SELECT EXISTS (
    SELECT 1 
    FROM information_schema.columns 
    WHERE table_schema = 'alkansave' 
    AND table_name = 'Category' 
    AND column_name = 'UpdatedAt'
) AS Category_UpdatedAt_column_exists;

-- Add UpdatedAt column if it doesn't exist
ALTER TABLE Category 
ADD COLUMN IF NOT EXISTS UpdatedAt TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Step 5: Create default system categories if they don't exist
INSERT IGNORE INTO Category (CategoryName, UserID, DateCreated, IsDeleted) 
VALUES 
('Education', NULL, NOW(), FALSE),
('Emergency', NULL, NOW(), FALSE),
('Travel', NULL, NOW(), FALSE),
('Housing', NULL, NOW(), FALSE);

-- Step 6: Update SavedAmount in Goal table based on SavingsTransaction
-- This will fix any goals that have incorrect SavedAmount values
UPDATE Goal g
JOIN (
    SELECT GoalID, COALESCE(SUM(Amount), 0) as TotalSaved
    FROM SavingsTransaction
    WHERE IsDeleted = FALSE
    GROUP BY GoalID
) s ON g.GoalID = s.GoalID
SET g.SavedAmount = s.TotalSaved,
    g.Status = IF(s.TotalSaved >= g.TargetAmount, 'Completed', 'Active'),
    g.CompletionDate = IF(s.TotalSaved >= g.TargetAmount, IFNULL(g.CompletionDate, CURDATE()), NULL);

-- Step 7: Show the final table structures
DESCRIBE Goal;
DESCRIBE SavingsTransaction;
DESCRIBE Category;

-- Step 8: Show some sample data
SELECT * FROM Goal LIMIT 5;
SELECT * FROM SavingsTransaction LIMIT 5;
SELECT * FROM Category;