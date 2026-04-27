CREATE DATABASE IF NOT EXISTS appliances_inventory
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE appliances_inventory;

CREATE TABLE IF NOT EXISTS products (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(150)  NOT NULL,
    price        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock        INT           NOT NULL DEFAULT 0,
    categories   VARCHAR(100)  NOT NULL DEFAULT 'Other',
    image        VARCHAR(255)  DEFAULT NULL,
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO products (product_name, price, stock, categories) VALUES
('Samsung 2HP Inverter AC',    45999.00, 12, 'Air Conditioner'),
('LG 1.5HP Window Type AC',    22500.00,  8, 'Air Conditioner'),
('Bosch 12-Place Dishwasher',  38750.00,  5, 'Dishwasher'),
('Midea Countertop Dishwasher',12990.00, 10, 'Dishwasher'),
('Sharp 25L Microwave Oven',    6499.00, 20, 'Microwave'),
('Panasonic 32L Microwave',     9200.00, 15, 'Microwave'),
('Hanabishi Electric Oven',     4299.00, 18, 'Oven'),
('Electrolux 65L Oven',        14500.00,  7, 'Oven'),
('Samsung Side-by-Side Ref',   68000.00,  4, 'Refrigerator'),
('Condura 2-Door Refrigerator',18999.00,  9, 'Refrigerator'),
('LG Front Load Washer',       35800.00,  6, 'Washer'),
('Whirlpool Top Load Washer',  17500.00, 11, 'Washer');
