-- Add cancelledByUser column to track user cancellations
-- This adds only 1 byte per row - the minimal solution
-- Run this SQL in your phpMyAdmin or MySQL client

ALTER TABLE `bookings` 
ADD COLUMN `cancelledByUser` TINYINT(1) DEFAULT 0 COMMENT 'Track if cancelled by user (1) or admin (0)' AFTER `bookingStatus`;

-- Set existing cancelled bookings to 0 (assume admin cancelled)
UPDATE `bookings` SET `cancelledByUser` = 0 WHERE `bookingStatus` = 'cancelled';
