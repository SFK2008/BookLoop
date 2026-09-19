-- Database creation schema for BookLoop Platform

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Books Table
CREATE TABLE IF NOT EXISTS books (
    book_id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(150) NOT NULL,
    genre VARCHAR(50) NOT NULL,
    condition VARCHAR(50) NOT NULL,
    status VARCHAR(50) DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 3. Exchange Requests Table
CREATE TABLE IF NOT EXISTS requests (
    exchange_id SERIAL PRIMARY KEY,
    book_id INT NOT NULL,
    requester_id INT NOT NULL,
    owner_id INT NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    request_date DATE DEFAULT CURRENT_DATE,
    CONSTRAINT fk_book FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    CONSTRAINT fk_requester FOREIGN KEY (requester_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_owner FOREIGN KEY (owner_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 4. Digital Notes Table
CREATE TABLE IF NOT EXISTS notes (
    note_id SERIAL PRIMARY KEY,
    book_id INT NOT NULL,
    uploaded_by INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(20) NOT NULL,
    upload_date DATE DEFAULT CURRENT_DATE,
    CONSTRAINT fk_note_book FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    CONSTRAINT fk_note_user FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 5. Reviews Table (Post-MVP Extension)[cite: 1]
CREATE TABLE IF NOT EXISTS reviews (
    review_id SERIAL PRIMARY KEY,
    exchange_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_exchange FOREIGN KEY (exchange_id) REFERENCES requests(exchange_id) ON DELETE CASCADE
);

-- Seed Initial Test Data[cite: 1]
INSERT INTO users (name, email, password) VALUES 
('Alice Johnson', 'alice@example.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1z2.qK1uG8eG8uV3h4q8z5X8y5X8y5X'),
('Bob Smith', 'bob@example.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1z2.qK1uG8eG8uV3h4q8z5X8y5X8y5X');

INSERT INTO books (user_id, title, author, genre, condition, status) VALUES 
(1, 'Clean Code', 'Robert C. Martin', 'Technology', 'Good', 'Available'),
(2, 'Dune', 'Frank Herbert', 'Fiction', 'New', 'Available');