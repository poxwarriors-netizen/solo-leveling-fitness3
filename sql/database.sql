-- =====================================================
-- SOLO LEVELING FITNESS APP - COMPLETE DATABASE SCHEMA
-- =====================================================

CREATE DATABASE IF NOT EXISTS solo_leveling;
USE solo_leveling;

-- =====================================================
-- TABLE: users
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    join_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    
    -- Rank System
    current_rank ENUM('E', 'D', 'C', 'B', 'A', 'S') DEFAULT 'E',
    rank_progress INT DEFAULT 0, -- 0 to 100%
    total_xp BIGINT DEFAULT 0,
    current_streak INT DEFAULT 0,
    best_streak INT DEFAULT 0,
    total_workouts INT DEFAULT 0,
    
    -- Penalty System
    missed_days INT DEFAULT 0,
    last_workout_date DATE NULL,
    rest_days_used_this_week INT DEFAULT 0,
    
    -- Subscription
    subscription_type ENUM('none', 'monthly', 'yearly') DEFAULT 'none',
    subscription_expiry DATE NULL,
    subscription_start DATE NULL,
    
    -- Shadow Army
    shadow_army_power INT DEFAULT 0,
    shadow_coins INT DEFAULT 100, -- Starting coins
    
    -- Profile
    profile_image VARCHAR(255) DEFAULT 'default.jpg',
    bio TEXT,
    is_admin BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    
    INDEX idx_rank (current_rank),
    INDEX idx_xp (total_xp),
    INDEX idx_streak (current_streak)
);

-- =====================================================
-- TABLE: workout_logs
-- =====================================================
CREATE TABLE workout_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    workout_date DATE NOT NULL,
    
    -- Exercises
    pushups INT DEFAULT 0,
    situps INT DEFAULT 0,
    squats INT DEFAULT 0,
    running_km DECIMAL(5,2) DEFAULT 0,
    pullups INT DEFAULT 0,
    plank_seconds INT DEFAULT 0,
    
    -- Results
    total_exercises INT DEFAULT 0,
    completion_percentage DECIMAL(5,2) DEFAULT 0,
    xp_earned INT DEFAULT 0,
    shadow_coins_earned INT DEFAULT 0,
    
    -- Metadata
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, workout_date),
    UNIQUE KEY unique_daily_workout (user_id, workout_date)
);

-- =====================================================
-- TABLE: shadow_army
-- =====================================================
CREATE TABLE shadow_army (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    soldier_name VARCHAR(50) NOT NULL,
    soldier_rank ENUM('Shadow', 'Knight', 'Elite', 'General', 'Grand Marshal') DEFAULT 'Shadow',
    power_level INT DEFAULT 10,
    recruited_date DATE NOT NULL,
    last_battle_date DATE NULL,
    battles_won INT DEFAULT 0,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
);

-- =====================================================
-- TABLE: achievements
-- =====================================================
CREATE TABLE achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    achievement_name VARCHAR(100) NOT NULL,
    description TEXT,
    requirement_type ENUM('streak', 'workouts', 'rank', 'army_power', 'special') NOT NULL,
    requirement_value INT NOT NULL,
    xp_reward INT DEFAULT 0,
    coin_reward INT DEFAULT 0,
    badge_image VARCHAR(255),
    rarity ENUM('Common', 'Rare', 'Epic', 'Legendary', 'Mythic') DEFAULT 'Common'
);

-- =====================================================
-- TABLE: user_achievements
-- =====================================================
CREATE TABLE user_achievements (
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    earned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (user_id, achievement_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLE: guilds
-- =====================================================
CREATE TABLE guilds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guild_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    guild_leader_id INT NOT NULL,
    guild_rank ENUM('C', 'B', 'A', 'S') DEFAULT 'C',
    member_count INT DEFAULT 1,
    total_xp BIGINT DEFAULT 0,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guild_logo VARCHAR(255) DEFAULT 'default_guild.jpg',
    
    FOREIGN KEY (guild_leader_id) REFERENCES users(id),
    INDEX idx_rank (guild_rank)
);

-- =====================================================
-- TABLE: guild_members
-- =====================================================
CREATE TABLE guild_members (
    guild_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    role ENUM('Member', 'Officer', 'Leader') DEFAULT 'Member',
    
    PRIMARY KEY (guild_id, user_id),
    FOREIGN KEY (guild_id) REFERENCES guilds(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLE: partners (Discount Partners)
-- =====================================================
CREATE TABLE partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_name VARCHAR(100) NOT NULL,
    category ENUM('fitness', 'universal') NOT NULL,
    description TEXT,
    website_url VARCHAR(255),
    logo_image VARCHAR(255),
    discount_percent INT DEFAULT 0,
    min_rank_required ENUM('E', 'D', 'C', 'B', 'A', 'S') DEFAULT 'E',
    affiliate_link VARCHAR(500),
    commission_rate DECIMAL(5,2) DEFAULT 0, -- Percentage
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0
);

-- =====================================================
-- TABLE: affiliate_clicks
-- =====================================================
CREATE TABLE affiliate_clicks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    partner_id INT NOT NULL,
    click_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_rank_at_click VARCHAR(1) NOT NULL,
    ip_address VARCHAR(45),
    converted BOOLEAN DEFAULT FALSE,
    conversion_amount DECIMAL(10,2) DEFAULT 0,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (partner_id) REFERENCES partners(id),
    INDEX idx_user (user_id),
    INDEX idx_date (click_date)
);

-- =====================================================
-- TABLE: penalty_recovery
-- =====================================================
CREATE TABLE penalty_recovery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    recovery_date DATE NOT NULL,
    recovery_type ENUM('ads', 'coins', 'subscription') NOT NULL,
    ads_watched INT DEFAULT 0,
    coins_spent INT DEFAULT 0,
    progress_recovered INT DEFAULT 0, -- percentage points
    revenue_generated DECIMAL(10,2) DEFAULT 0,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_date (user_id, recovery_date)
);

-- =====================================================
-- TABLE: raids
-- =====================================================
CREATE TABLE raids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    raid_name VARCHAR(100) NOT NULL,
    raid_type ENUM('daily', 'weekly', 'monthly', 'special') NOT NULL,
    min_rank_required ENUM('E', 'D', 'C', 'B', 'A', 'S') DEFAULT 'E',
    xp_reward INT DEFAULT 0,
    coin_reward INT DEFAULT 0,
    item_reward VARCHAR(255),
    boss_name VARCHAR(100),
    boss_image VARCHAR(255),
    description TEXT,
    start_date DATE,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE
);

-- =====================================================
-- TABLE: raid_completions
-- =====================================================
CREATE TABLE raid_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    raid_id INT NOT NULL,
    completion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    performance_score INT DEFAULT 0,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (raid_id) REFERENCES raids(id),
    UNIQUE KEY unique_user_raid (user_id, raid_id)
);

-- =====================================================
-- INSERT SAMPLE DATA
-- =====================================================

-- Insert achievements
INSERT INTO achievements (achievement_name, description, requirement_type, requirement_value, xp_reward, coin_reward, rarity) VALUES
('First Gate', 'Complete your first workout', 'workouts', 1, 100, 50, 'Common'),
('Gate Crusher', '30-day workout streak', 'streak', 30, 500, 200, 'Rare'),
('Monarch\'s Path', '100-day workout streak', 'streak', 100, 2000, 1000, 'Epic'),
('Shadow Monarch', '365-day workout streak', 'streak', 365, 10000, 5000, 'Legendary'),
('Elite Hunter', 'Reach A-Rank', 'rank', 5, 1000, 500, 'Epic'),
('Shadow Ruler', 'Recruit 100 soldiers', 'army_power', 100, 1500, 750, 'Epic'),
('Demon Lord', 'Complete 50 raids', 'special', 50, 3000, 1500, 'Mythic');

-- Insert partners (fitness - all ranks)
INSERT INTO partners (partner_name, category, description, discount_percent, min_rank_required, affiliate_link, commission_rate) VALUES
('MuscleBlaze', 'fitness', 'Premium protein supplements', 10, 'E', 'https://www.muscleblaze.com/?aff=sololeveling', 8.5),
('HealthKart', 'fitness', 'Complete health store', 12, 'E', 'https://www.healthkart.com/?aff=sololeveling', 7.0),
('Decathlon', 'fitness', 'Sports equipment', 10, 'D', 'https://www.decathlon.in/?aff=sololeveling', 5.0),
('HRX by Hrithik', 'fitness', 'Premium activewear', 15, 'C', 'https://www.hrx.com/?aff=sololeveling', 9.0),
('BoldFit', 'fitness', 'Gym equipment', 10, 'B', 'https://www.boldfit.com/?aff=sololeveling', 6.5),
('GNC', 'fitness', 'Global nutrition center', 15, 'A', 'https://www.gnc.com/?aff=sololeveling', 10.0),
('Under Armour', 'fitness', 'Performance apparel', 20, 'S', 'https://www.underarmour.com/?aff=sololeveling', 8.0);

-- Insert partners (universal - A/S only)
INSERT INTO partners (partner_name, category, description, discount_percent, min_rank_required, affiliate_link, commission_rate) VALUES
('Amazon', 'universal', 'Everything store', 8, 'A', 'https://www.amazon.in/?tag=sololeveling05-21', 4.0),
('Flipkart', 'universal', 'Online shopping', 8, 'A', 'https://www.flipkart.com/?affid=sololeveling', 3.5),
('Swiggy', 'universal', 'Food delivery', 10, 'A', 'https://www.swiggy.com/?aff=sololeveling', 5.0),
('Zomato', 'universal', 'Restaurant delivery', 10, 'A', 'https://www.zomato.com/?aff=sololeveling', 5.0),
('Netflix', 'universal', 'Streaming service', 5, 'A', 'https://www.netflix.com/?aff=sololeveling', 15.0),
('Uber', 'universal', 'Ride sharing', 8, 'A', 'https://www.uber.com/?aff=sololeveling', 4.0),
('MakeMyTrip', 'universal', 'Travel bookings', 7, 'S', 'https://www.makemytrip.com/?aff=sololeveling', 6.0),
('Nykaa', 'universal', 'Beauty products', 10, 'S', 'https://www.nykaa.com/?aff=sololeveling', 7.5),
('PharmEasy', 'universal', 'Healthcare', 12, 'S', 'https://www.pharmeasy.in/?aff=sololeveling', 5.0),
('Myntra', 'universal', 'Fashion store', 10, 'S', 'https://www.myntra.com/?aff=sololeveling', 4.5);

-- Insert sample admin user (password: Admin@123)
INSERT INTO users (username, email, password_hash, full_name, is_admin, current_rank, shadow_coins) VALUES
('admin', 'admin@sololeveling.com', '$2y$10$YourHashHere', 'System Administrator', TRUE, 'S', 10000);

-- Insert sample raids
INSERT INTO raids (raid_name, raid_type, min_rank_required, xp_reward, coin_reward, boss_name, description) VALUES
('Low Orb Raid', 'daily', 'E', 50, 20, 'Low Orb Guardian', 'Clear the low-level gate'),
('High Orb Raid', 'weekly', 'C', 500, 200, 'High Orb Keeper', 'Challenge the high orb boss'),
('Demon Castle', 'monthly', 'A', 2000, 1000, 'Demon King', 'Invade the demon castle'),
('Jeju Island', 'special', 'B', 1000, 500, 'Ant King', 'Special Jeju Island raid event');

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

DELIMITER $$

-- Procedure to update user rank based on streak
CREATE PROCEDURE UpdateUserRank(IN p_user_id INT)
BEGIN
    DECLARE v_streak INT;
    DECLARE v_current_rank VARCHAR(1);
    DECLARE v_new_rank VARCHAR(1);
    
    -- Get user's current streak and rank
    SELECT current_streak, current_rank INTO v_streak, v_current_rank
    FROM users WHERE id = p_user_id;
    
    -- Determine new rank based on streak
    IF v_streak >= 60 THEN
        SET v_new_rank = 'S';
    ELSEIF v_streak >= 50 THEN
        SET v_new_rank = 'A';
    ELSEIF v_streak >= 40 THEN
        SET v_new_rank = 'B';
    ELSEIF v_streak >= 30 THEN
        SET v_new_rank = 'C';
    ELSEIF v_streak >= 20 THEN
        SET v_new_rank = 'D';
    ELSE
        SET v_new_rank = 'E';
    END IF;
    
    -- Update if rank changed
    IF v_new_rank != v_current_rank THEN
        UPDATE users 
        SET current_rank = v_new_rank,
            rank_progress = 0
        WHERE id = p_user_id;
    END IF;
END$$

-- Procedure to apply daily penalty
CREATE PROCEDURE ApplyDailyPenalties()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_user_id INT;
    DECLARE v_last_workout DATE;
    DECLARE v_subscription VARCHAR(10);
    DECLARE v_current_rank VARCHAR(1);
    
    DECLARE cur CURSOR FOR 
        SELECT id, last_workout_date, subscription_type, current_rank 
        FROM users WHERE is_active = TRUE;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_user_id, v_last_workout, v_subscription, v_current_rank;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Skip if user has subscription
        IF v_subscription = 'none' THEN
            -- Check if missed yesterday
            IF v_last_workout < CURDATE() - INTERVAL 1 DAY THEN
                -- Apply 3% penalty
                UPDATE users 
                SET rank_progress = GREATEST(rank_progress - 3, 0),
                    missed_days = missed_days + 1
                WHERE id = v_user_id;
                
                -- Check if need to rank down (7+ days missed)
                IF DATEDIFF(CURDATE(), v_last_workout) >= 7 THEN
                    -- Rank down logic
                    UPDATE users 
                    SET current_rank = CASE current_rank
                        WHEN 'S' THEN 'A'
                        WHEN 'A' THEN 'B'
                        WHEN 'B' THEN 'C'
                        WHEN 'C' THEN 'D'
                        WHEN 'D' THEN 'E'
                        ELSE 'E'
                    END,
                    rank_progress = 0
                    WHERE id = v_user_id;
                END IF;
            END IF;
        END IF;
    END LOOP;
    
    CLOSE cur;
END$$

DELIMITER ;

-- =====================================================
-- CREATE ADMIN USER (RUN THIS SEPARATELY)
-- =====================================================
-- Note: Generate actual password hash using PHP password_hash()
-- For testing, use password: Admin@123
-- Hash: $2y$10$YourGeneratedHashHere