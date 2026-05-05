CREATE DATABASE IF NOT EXISTS top3_tasks
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE top3_tasks;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','user') DEFAULT 'user',
    remember_token VARCHAR(100) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status ENUM('captured','active','nudged','escalated','snoozed','blocked','completed') DEFAULT 'captured',
    priority ENUM('normal','important','urgent') DEFAULT 'normal',
    is_urgent BOOLEAN DEFAULT FALSE,
    due_at DATETIME NULL,
    deadline_type ENUM('none','soft','hard') DEFAULT 'none',
    reminder_at DATETIME NULL,
    last_nudged_at DATETIME NULL,
    nudge_count INT UNSIGNED DEFAULT 0,
    source ENUM('message','verbal','thought','interruption','other') DEFAULT 'other',
    notes TEXT NULL,
    snoozed_until DATETIME NULL,
    blocked_reason TEXT NULL,
    recurrence_type ENUM('none','fixed','after_completion','reminder_only') DEFAULT 'none',
    recurrence_interval VARCHAR(50) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    INDEX tasks_user_status_index (user_id, status),
    INDEX tasks_user_due_index (user_id, due_at),
    INDEX tasks_user_reminder_index (user_id, reminder_at),
    INDEX tasks_user_snooze_index (user_id, snoozed_until),
    CONSTRAINT tasks_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE push_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    endpoint VARCHAR(500) NOT NULL,
    public_key TEXT NOT NULL,
    auth_token VARCHAR(255) NOT NULL,
    content_encoding VARCHAR(30) DEFAULT 'aes128gcm',
    user_agent TEXT NULL,
    last_seen_at DATETIME NULL,
    failed_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY push_subscriptions_endpoint_unique (endpoint),
    INDEX push_subscriptions_user_id_index (user_id),
    CONSTRAINT push_subscriptions_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
