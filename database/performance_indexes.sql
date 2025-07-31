-- Performance optimization indexes for Tournament Points System
-- Task 13.1: Add database indexes for frequent queries

USE tournament_points;

-- Additional indexes for leaderboard queries (most frequent)
-- Composite index for leaderboard calculation with tiebreaker
ALTER TABLE users ADD INDEX idx_leaderboard (role, total_points DESC, created_at ASC);

-- Composite index for point transactions leaderboard queries
ALTER TABLE point_transactions ADD INDEX idx_leaderboard_points (user_id, type, timestamp);

-- Index for active events queries
ALTER TABLE events ADD INDEX idx_active_events (is_active, start_date, end_date);

-- Composite index for tournament queries by event and status
ALTER TABLE tournaments ADD INDEX idx_event_status (event_id, status, scheduled_time);

-- Index for claims by user and status (for preventing duplicates and reporting)
ALTER TABLE claims ADD INDEX idx_user_status (user_id, status, timestamp);

-- Index for products by stand and active status
ALTER TABLE products ADD INDEX idx_stand_active (stand_id, is_active, points_required);

-- Composite index for point transactions reporting
ALTER TABLE point_transactions ADD INDEX idx_reporting (type, source, timestamp, assigned_by);

-- Index for notifications queries
ALTER TABLE notifications ADD INDEX idx_user_notifications (user_id, is_read, type, created_at DESC);

-- Index for stands by event
ALTER TABLE stands ADD INDEX idx_event_stands (event_id, manager_id);

-- Composite index for claims reporting
ALTER TABLE claims ADD INDEX idx_claims_reporting (stand_id, status, timestamp);

-- Index for tournament participants (JSON queries are slower, but this helps with joins)
ALTER TABLE tournaments ADD INDEX idx_tournament_time_status (scheduled_time, status);

-- Additional indexes for complex reporting queries
ALTER TABLE point_transactions ADD INDEX idx_tournament_user_time (tournament_id, user_id, timestamp);
ALTER TABLE claims ADD INDEX idx_product_time (product_id, timestamp, status);

-- Index for user search by nickname (for assistant interface)
ALTER TABLE users ADD INDEX idx_nickname_search (nickname, role);

-- Analyze tables to update statistics after adding indexes
ANALYZE TABLE users, events, tournaments, point_transactions, stands, products, claims, notifications;