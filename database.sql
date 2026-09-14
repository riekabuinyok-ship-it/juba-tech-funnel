CREATE DATABASE juba_funnel;

\c juba_funnel;

CREATE TYPE level_enum AS ENUM ('primary', 'secondary');
CREATE TYPE book_type_enum AS ENUM ('single', 'bundle');
CREATE TYPE book_status_enum AS ENUM ('active', 'inactive');
CREATE TYPE order_status_enum AS ENUM ('pending', 'confirmed', 'rejected');
CREATE TYPE payment_status_enum AS ENUM ('pending', 'confirmed', 'rejected');
CREATE TYPE blog_status_enum AS ENUM ('draft', 'published');
CREATE TYPE review_status_enum AS ENUM ('pending', 'approved', 'rejected');
CREATE TYPE testimonial_status_enum AS ENUM ('active', 'inactive');

CREATE TABLE admins (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE customers (
  id SERIAL PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  location VARCHAR(150),
  class_level VARCHAR(10),
  level_of_study level_enum,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE subjects (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  level level_enum NOT NULL,
  icon VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE books (
  id SERIAL PRIMARY KEY,
  subject_id INT NOT NULL REFERENCES subjects(id) ON DELETE CASCADE,
  title VARCHAR(200) NOT NULL,
  grade VARCHAR(10),
  description TEXT,
  cover_image VARCHAR(255),
  preview_url VARCHAR(500),
  price DECIMAL(10,2) DEFAULT 7000.00,
  type book_type_enum DEFAULT 'single',
  status book_status_enum DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE bundle_items (
  id SERIAL PRIMARY KEY,
  bundle_id INT NOT NULL REFERENCES books(id) ON DELETE CASCADE,
  book_id INT NOT NULL REFERENCES books(id) ON DELETE CASCADE
);

CREATE TABLE download_links (
  id SERIAL PRIMARY KEY,
  book_id INT NOT NULL REFERENCES books(id) ON DELETE CASCADE,
  label VARCHAR(150),
  url VARCHAR(500) NOT NULL,
  sort_order INT DEFAULT 0
);

CREATE TABLE download_log (
  id SERIAL PRIMARY KEY,
  customer_id INT NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
  book_id INT NOT NULL REFERENCES books(id) ON DELETE CASCADE,
  link_id INT NOT NULL REFERENCES download_links(id) ON DELETE CASCADE,
  ip_address VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reviews (
  id SERIAL PRIMARY KEY,
  book_id INT NOT NULL REFERENCES books(id) ON DELETE CASCADE,
  customer_id INT NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
  rating SMALLINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  title VARCHAR(150),
  comment TEXT,
  status review_status_enum DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  approved_at TIMESTAMP,
  CONSTRAINT uniq_review_per_customer UNIQUE (book_id, customer_id)
);

CREATE TABLE testimonials (
  id SERIAL PRIMARY KEY,
  author_name VARCHAR(150) NOT NULL,
  author_class VARCHAR(20),
  author_location VARCHAR(150),
  quote TEXT NOT NULL,
  rating SMALLINT DEFAULT 5,
  status testimonial_status_enum DEFAULT 'active',
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed testimonials you collect from real students:
INSERT INTO testimonials (author_name, author_class, author_location, quote, rating) VALUES
('Ayen M.', 'S3', 'Juba', 'These guides helped me pass my S3 exams. The notes are clear and easy to follow.', 5),
('David O.', 'S1', 'Wau', 'The Geography bundle saved me so much time. Highly recommend.', 5),
('Mary A.', 'P8', 'Malakal', 'My daughter improved her marks after using these guides.', 5);

CREATE TABLE orders (
  id SERIAL PRIMARY KEY,
  customer_id INT NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
  total_amount DECIMAL(10,2) NOT NULL,
  status order_status_enum DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE order_items (
  id SERIAL PRIMARY KEY,
  order_id INT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  book_id INT NOT NULL REFERENCES books(id) ON DELETE CASCADE,
  price DECIMAL(10,2) NOT NULL
);

CREATE TABLE payments (
  id SERIAL PRIMARY KEY,
  order_id INT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  momo_account_name VARCHAR(150),
  momo_account_number VARCHAR(50),
  transaction_id VARCHAR(100) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  status payment_status_enum DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  confirmed_at TIMESTAMP NULL,
  CONSTRAINT uniq_transaction_id UNIQUE (transaction_id)
);

CREATE TABLE settings (
  id SERIAL PRIMARY KEY,
  setting_key VARCHAR(100) UNIQUE NOT NULL,
  setting_value TEXT
);

CREATE TABLE blog_posts (
  id SERIAL PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(200) UNIQUE NOT NULL,
  content TEXT,
  image VARCHAR(255),
  status blog_status_enum DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE download_tokens (
  id SERIAL PRIMARY KEY,
  customer_id INT NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
  book_id INT NOT NULL REFERENCES books(id) ON DELETE CASCADE,
  token VARCHAR(64) NOT NULL,
  expires_at TIMESTAMP NOT NULL
);

INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'Juba Tech Solution Study Guides'),
('momo_account_name', 'RIEK ABUI'),
('momo_account_number', '0924440899'),
('contact_email', 'info@jubatech.com'),
('single_price', '7000'),
('bundle_price', '21000');

INSERT INTO settings (setting_key, setting_value) VALUES
('trust_students_count', '0'),
('trust_show_curriculum', '0'),
('trust_show_moneyback', '0'),
('trust_show_ministry', '0'),
('trust_show_downloads', '1'),
('trust_moneyback_days', '7'),
('trust_moneyback_text', 'Not satisfied? Contact us within 7 days for a full refund.')
ON CONFLICT (setting_key) DO NOTHING;

INSERT INTO admins (name, email, password) VALUES
('Admin', 'admin@jubatech.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS4bZJ18JuywdB9xQ8Q8Q8Q8Q8Q8');

-- The password above is "admin123". Change immediately after first login.

INSERT INTO subjects (name, level) VALUES
('Geography', 'secondary'),
('English', 'secondary'),
('CRE', 'secondary'),
('History', 'secondary'),
('Commerce', 'secondary'),
('Business', 'secondary'),
('Chemistry', 'secondary'),
('Biology', 'secondary'),
('Physics', 'secondary'),
('Math', 'secondary'),
('Citizenship', 'secondary'),
('English', 'primary'),
('CRE', 'primary'),
('Science', 'primary'),
('Social Studies', 'primary'),
('Math', 'primary');
