-- Run on the DB VM:  sudo mysql   (then paste this)
CREATE DATABASE REDEEMALL;

USE REDEEMALL;

CREATE TABLE cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nickname VARCHAR(50) NOT NULL,
    bank VARCHAR(100),
    last_four CHAR(4) NOT NULL
);

CREATE TABLE offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    merchant VARCHAR(100),
    value_text VARCHAR(100),
    expires_on DATE,
    is_used BOOLEAN DEFAULT FALSE,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE
);

INSERT INTO cards (nickname, bank, last_four) VALUES
('Travel card', 'Demo Bank', '1234'),
('Gold card', 'Demo Bank', '5678');

INSERT INTO offers (card_id, title, merchant, value_text, expires_on, is_used, image) VALUES
(1, 'Hotel Cashback', 'Demo Hotels', '$50 back', '2026-11-01', FALSE, 'hotel-cashback.png'),
(1, 'Airline Miles x3', 'Demo Airways', '3x miles', '2026-12-31', FALSE, 'flight-miles.png'),
(2, 'Dining Rewards', 'Demo Bistro', '15% off', '2026-11-20', FALSE, 'dining.png'),
(2, 'Online Shopping', 'Demo Store', '10% back', '2026-10-30', FALSE, 'shopping.png'),
(1, 'Fuel Surcharge', 'Demo Fuel', '1% fee waived', '2026-09-30', TRUE, 'fuel.png');

-- App user: can only sign in from the web subnet (WebSUB 10.0.1.0/24).
-- Replace the password with your own and never commit the real one.
CREATE USER 'redeemuser'@'10.0.1.%' IDENTIFIED BY 'ChooseAStrongPassword!';
GRANT ALL PRIVILEGES ON REDEEMALL.* TO 'redeemuser'@'10.0.1.%';
FLUSH PRIVILEGES;
