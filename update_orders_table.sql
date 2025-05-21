-- Dodanie brakujących kolumn do tabeli orders
ALTER TABLE orders
ADD COLUMN shipping_method VARCHAR(50) AFTER payment_method,
ADD COLUMN shipping_cost DECIMAL(10,2) DEFAULT 0.00 AFTER subtotal,
ADD COLUMN total DECIMAL(10,2) DEFAULT 0.00 AFTER shipping_cost,
ADD COLUMN status VARCHAR(50) DEFAULT 'pending' AFTER total,
ADD COLUMN order_date DATETIME DEFAULT CURRENT_TIMESTAMP AFTER status; 