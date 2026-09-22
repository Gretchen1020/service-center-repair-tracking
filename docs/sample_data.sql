-- Sample data for Service Center Job Card & Repair Tracking System
-- Run this AFTER docs/schema.sql has created the admins, customers, and jobs tables.
-- Uses fixed/explicit ids so job numbers match exactly what the Day 10 test
-- doc (Day10_FinalDemo_Module_Testing.docx) references by name.
--
-- Covers:
--   - All 5 statuses (Received, Checking, Repairing, Ready, Delivered)
--   - All 3 payment states referenced in TC-03: fully paid (JC-0001),
--     partially paid (JC-0002), quoted-but-unpaid (JC-0003)
--   - Customers spread across different regions (for a more realistic demo)
--   - Missing optional fields on a couple of rows, to exercise those code
--     paths during the live demo: customer 2 has no address, job 3 has no
--     technician, job 4 has no model.

-- ---------------------------------------------------
-- Sample customers (6)
-- ---------------------------------------------------
INSERT INTO customers (id, name, mobile, address) VALUES
(1, 'Ananya Rao',    '9876543210',        'Indiranagar, Bengaluru'),
(2, 'Rohan Mehta',   '9123456780',        NULL),                        -- no address on file
(3, 'Fatima Ansari', '+91 98765 00011',   'Hazratganj, Lucknow'),
(4, 'Arjun Nair',    '9000000001',        'Fort Kochi, Kerala'),
(5, 'Harpreet Kaur', '9000000002',        'Lawrence Road, Amritsar'),
(6, 'Sourav Ghosh',  '9000000003',        'Salt Lake, Kolkata');

-- ---------------------------------------------------
-- Sample job cards (7) — mix of statuses and payment states
-- ---------------------------------------------------
INSERT INTO jobs (id, job_no, customer_id, device_name, model, complaint, technician, estimate, final_cost, paid_amount, status) VALUES
(1, 'JC-0001', 1, 'Laptop',     'Dell Inspiron 15',   'Not powering on',                'Suresh Patil', 2500.00, 2800.00, 2800.00, 'Delivered'),   -- fully paid
(2, 'JC-0002', 2, 'Smartphone', 'Samsung Galaxy M31', 'Cracked screen',                 'Suresh Patil', 3200.00, 3200.00, 1500.00, 'Ready'),       -- partially paid
(3, 'JC-0003', 3, 'Printer',    'HP DeskJet 2131',    'Paper jam, not printing',        NULL,           800.00,  800.00,  0.00,    'Checking'),    -- quoted, unpaid; no technician on file
(4, 'JC-0004', 4, 'Laptop',     NULL,                 'Overheating, fan noise',         'Anil Kamble',  1800.00, 0.00,    0.00,    'Repairing'),    -- not yet quoted; no model on file
(5, 'JC-0005', 5, 'Smartphone', 'OnePlus Nord CE2',   'Battery draining fast',          'Anil Kamble',  1200.00, 0.00,    0.00,    'Received'),     -- not yet quoted
(6, 'JC-0006', 1, 'Tablet',     'Lenovo Tab M10',     'Touch screen not responding',    'Anil Kamble',  1500.00, 1500.00, 1500.00, 'Delivered'),   -- fully paid, repeat customer
(7, 'JC-0007', 6, 'Smartphone', 'Redmi Note 11',      'Charging port loose',            'Suresh Patil', 600.00,  650.00,  300.00,  'Ready');        -- partially paid, final cost above estimate
