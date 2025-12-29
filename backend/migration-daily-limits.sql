-- Migration: Add daily limit fields to campaigns table
-- Execute this script in Supabase SQL Editor if you already have the database set up
-- This adds the daily dispatch limit functionality

-- Add new columns to campaigns table
ALTER TABLE campaigns
ADD COLUMN IF NOT EXISTS daily_limit INTEGER NOT NULL DEFAULT 0,
ADD COLUMN IF NOT EXISTS dispatched_today INTEGER NOT NULL DEFAULT 0,
ADD COLUMN IF NOT EXISTS last_dispatch_date TIMESTAMP WITH TIME ZONE;

-- Add comments for documentation
COMMENT ON COLUMN campaigns.daily_limit IS 'Maximum number of dispatches allowed per day (0 = unlimited)';
COMMENT ON COLUMN campaigns.dispatched_today IS 'Number of dispatches sent today';
COMMENT ON COLUMN campaigns.last_dispatch_date IS 'Date of last dispatch (used to reset daily count)';

-- Optional: Set default daily limit for existing active campaigns
-- Uncomment the line below if you want to set a default daily limit of 100 for all existing campaigns
-- UPDATE campaigns SET daily_limit = 100 WHERE daily_limit = 0 AND status IN ('active', 'paused');
