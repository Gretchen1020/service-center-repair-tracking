-- Sample data for service_center_db
-- Run this AFTER importing docs/schema.sql (or on a database that already
-- has the customers/jobs tables and at least the built-in admin row).
-- Safe to run once; running it twice will insert a second copy of each row
-- (job_no values are unique, so a second run will fail on the duplicate
-- JC-1001..JC-1006 job numbers rather than silently duplicating them).

INSERT INTO customers (name, mobile, address) VALUES
('Ananya Rao',      '9876543210', 'Indiranagar, Bengaluru'),
('Rohan Mehta',     '9123456780', NULL),
('Fatima Ansari',   '+91 98765 00011', 'Hazratganj, Lucknow'),
('Arjun Nair',      '9000000001', 'Fort Kochi, Kerala'),
('Harpreet Kaur',   '9000000002', 'Lawrence Road, Amritsar'),
('Sourav Ghosh',    '9000000003', 'Salt Lake, Kolkata');

-- Grabs the ids just inserted above, in the same order, so this script
-- still works no matter what ids already exist in the table.
SET @c1 = (SELECT id FROM customers WHERE name = 'Ananya Rao');
SET @c2 = (SELECT id FROM customers WHERE name = 'Rohan Mehta');
SET @c3 = (SELECT id FROM customers WHERE name = 'Fatima Ansari');
SET @c4 = (SELECT id FROM customers WHERE name = 'Arjun Nair');
SET @c5 = (SELECT id FROM customers WHERE name = 'Harpreet Kaur');
SET @c6 = (SELECT id FROM customers WHERE name = 'Sourav Ghosh');

INSERT INTO jobs (job_no, customer_id, device_name, model, complaint, technician, estimate, final_cost, paid_amount, status) VALUES
('JC-1001', @c1, 'Laptop', 'Dell 5490',      'Screen flickers on startup',        'Ravi',  2500.00, 0.00,    0.00,    'Received'),
('JC-1002', @c2, 'Phone',  'Redmi Note 9',   'Battery drains fast',               'Sam',   1200.00, 1500.00, 500.00,  'Repairing'),
('JC-1003', @c3, 'Tablet', 'iPad 7',         'Not charging',                     'Ravi',  3000.00, 3200.00, 3200.00, 'Delivered'),
('JC-1004', @c4, 'Printer','HP LaserJet M15','Paper jam every few pages',         'Sam',   800.00,  800.00,  0.00,    'Checking'),
('JC-1005', @c5, 'Laptop', 'Lenovo IdeaPad', 'Keyboard keys not responding',      'Ravi',  1800.00, 1800.00, 900.00,  'Ready'),
('JC-1006', @c6, 'Phone',  'iPhone 11',      'Cracked screen, touch unresponsive','Sam',   4500.00, 4700.00, 2000.00, 'Received');
