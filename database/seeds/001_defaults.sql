USE valour_empire;

INSERT IGNORE INTO roles (id, name, label) VALUES
 (1, 'admin', 'Administrator'),
 (2, 'staff', 'Staff');

INSERT IGNORE INTO services (name, pricing_model, price_per_kg, workflow) VALUES
 ('Wash & Iron',  'per_item', 0,    'pending,washing,ironing,ready,delivered'),
 ('Wash Only',    'per_item', 0,    'pending,washing,ready,delivered'),
 ('Iron Only',    'per_item', 0,    'pending,ironing,ready,delivered'),
 ('Dry Cleaning', 'per_item', 0,    'pending,dry_cleaning,ironing,ready,delivered'),
 ('Wash by Weight (kg)', 'per_kg', 1500, 'pending,washing,ready,delivered');

INSERT IGNORE INTO services (name, pricing_model, price_per_hour, workflow) VALUES
 ('Machine time (self-service)', 'per_hour', 0, 'pending,in_use,delivered');

INSERT IGNORE INTO clothing_types (name, display_name, price_per_item) VALUES
 ('shirt',   'Shirt',   500),
 ('trouser', 'Trouser', 600),
 ('suit',    'Suit (2-piece)', 2500),
 ('gown',    'Gown',    1500),
 ('native',  'Native / Senator', 1500),
 ('bedsheet','Bedsheet', 1000),
 ('duvet',   'Duvet',   2500),
 ('towel',   'Towel',   500);

INSERT IGNORE INTO expense_categories (name, is_direct_cost) VALUES
 ('Detergent & supplies', 1), ('Electricity', 1), ('Fuel / generator', 1), ('Water', 1),
 ('Salaries & wages', 0), ('Rent', 0), ('Transport', 0), ('Repairs & maintenance', 0), ('Other', 0);

INSERT IGNORE INTO settings (`key`, `value`) VALUES
 ('business_name', ''),
 ('business_logo_url', ''),
 ('business_address', ''),
 ('business_phone', ''),
 ('business_email', ''),
 ('bank_name', ''),
 ('bank_account_number', ''),
 ('bank_account_name', ''),
 ('invoice_template', 'classic'),
 ('invoice_prefix', 'VE'),
 ('invoice_footer', 'Thank you for your business. Items not collected within 30 days may be disposed of.');
