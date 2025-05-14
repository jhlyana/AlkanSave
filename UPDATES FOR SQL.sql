-- NOTE!!! IPASTE LANG NI LAHOS SA SQL THEN CLICK GO

-- new
ALTER TABLE Goal 
MODIFY COLUMN SavedAmount DECIMAL(10,2) NOT NULL DEFAULT 0;

--new 
ALTER TABLE Goal MODIFY SavedAmount DECIMAL(10,2) NOT NULL DEFAULT 0.00;

-- Add SavedAmount column if it doesn't exist
ALTER TABLE Goal 
ADD COLUMN IF NOT EXISTS SavedAmount DECIMAL(10,2) NOT NULL DEFAULT 0;

-- Add CompletionDate column if it doesn't exist
ALTER TABLE Goal 
ADD COLUMN IF NOT EXISTS CompletionDate DATE NULL;

-- Add Status column if it doesn't exist
ALTER TABLE Goal 
ADD COLUMN IF NOT EXISTS Status VARCHAR(50) NOT NULL DEFAULT 'Active';

-- Add IsDeleted column if it doesn't exist
ALTER TABLE Goal 
ADD COLUMN IF NOT EXISTS IsDeleted BOOLEAN NOT NULL DEFAULT FALSE;

-- Add UpdatedAt column if it doesn't exist
ALTER TABLE Goal 
ADD COLUMN IF NOT EXISTS UpdatedAt TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

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

-- Add IsDeleted column if it doesn't exist
ALTER TABLE SavingsTransaction 
ADD COLUMN IF NOT EXISTS IsDeleted BOOLEAN NOT NULL DEFAULT FALSE;

-- Add UpdatedAt column if it doesn't exist
ALTER TABLE SavingsTransaction 
ADD COLUMN IF NOT EXISTS UpdatedAt TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Create the Category table if it doesn't exist
CREATE TABLE IF NOT EXISTS Category (
    CategoryID INT AUTO_INCREMENT PRIMARY KEY,
    CategoryName VARCHAR(100) NOT NULL,
    UserID INT NULL, -- NULL means it's a system category
    DateCreated DATETIME NOT NULL,
    IsDeleted BOOLEAN NOT NULL DEFAULT FALSE,
    UpdatedAt TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

-- Add IsDeleted column if it doesn't exist
ALTER TABLE Category 
ADD COLUMN IF NOT EXISTS IsDeleted BOOLEAN NOT NULL DEFAULT FALSE;

-- Add UpdatedAt column if it doesn't exist
ALTER TABLE Category 
ADD COLUMN IF NOT EXISTS UpdatedAt TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

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