CREATE TABLE check_ins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    check_in_date DATE NOT NULL,
    weight DECIMAL(5,1) DEFAULT NULL,
    waist DECIMAL(5,1) DEFAULT NULL,
    steps INT UNSIGNED DEFAULT NULL,
    activities VARCHAR(255) DEFAULT NULL,
    mood VARCHAR(20) DEFAULT NULL,
    period_status VARCHAR(20) DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY unique_check_in_date (check_in_date)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

  CREATE TABLE challenges (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    target_days SMALLINT UNSIGNED NOT NULL DEFAULT 45,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX challenge_status (status)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

INSERT INTO challenges (
    name,
    target_days,
    start_date,
    end_date,
    status
) VALUES (
    '45-Day Challenge',
    45,
    '2026-09-14',
    '2026-10-30',
    'active'
);

CREATE TABLE sos_interactions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    challenge_id INT UNSIGNED DEFAULT NULL,
    category VARCHAR(50) NOT NULL,
    subcategory VARCHAR(50) DEFAULT NULL,
    motivation_style VARCHAR(20) DEFAULT NULL,
    user_detail VARCHAR(255) DEFAULT NULL,
    ai_push TEXT,
    immediate_action TEXT,
    why_it_works TEXT,
    real_fact TEXT,
    outcome VARCHAR(30) DEFAULT NULL,
    favorite TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX sos_category (category),
    INDEX sos_created_at (created_at)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;