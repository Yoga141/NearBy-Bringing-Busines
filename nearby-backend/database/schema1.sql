CREATE DATABASE IF NOT EXISTS nearby_balikpapan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nearby_balikpapan;

-- Akun (user biasa & pemilik UMKM sama-sama di sini, dibedakan lewat role)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'umkm_owner') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Token login sederhana (pengganti session, aman dipakai lintas origin dev)
CREATE TABLE auth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Data referensi (bukan dummy UMKM, cuma daftar pilihan dropdown)
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);
INSERT INTO categories (name) VALUES
('Kuliner'), ('Fashion'), ('Kerajinan Tangan'), ('Jasa'),
('Otomotif'), ('Kecantikan & Kesehatan'), ('Pertanian & Perikanan'), ('Teknologi'), ('Lainnya');

CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);
INSERT INTO locations (name) VALUES
('Balikpapan Utara'), ('Balikpapan Selatan'), ('Balikpapan Timur'),
('Balikpapan Barat'), ('Balikpapan Tengah'), ('Balikpapan Kota');

-- Profil UMKM (1 akun owner = 1 profil usaha)
CREATE TABLE umkm_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    business_name VARCHAR(150) NOT NULL,
    category_id INT NULL,
    location_id INT NULL,
    address_detail VARCHAR(255) NULL,
    description TEXT NULL,
    instagram VARCHAR(150) NULL,
    facebook VARCHAR(150) NULL,
    whatsapp VARCHAR(30) NULL,
    tiktok VARCHAR(150) NULL,
    website VARCHAR(150) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL
);

-- Foto tempat usaha (bisa banyak foto per UMKM)
CREATE TABLE umkm_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    umkm_id INT NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (umkm_id) REFERENCES umkm_profiles(id) ON DELETE CASCADE
);

-- Produk & harga
CREATE TABLE umkm_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    umkm_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (umkm_id) REFERENCES umkm_profiles(id) ON DELETE CASCADE
);
