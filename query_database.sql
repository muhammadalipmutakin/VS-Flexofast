-- 1. Perintah untuk membuat database
CREATE DATABASE `vs-flexo`;

-- 2. Pilih database yang baru dibuat
USE `vs-flexo`;

-- 3. Perintah untuk membuat tabel 'item'
CREATE TABLE `item` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `item` VARCHAR(255) NOT NULL,
    `desc` TEXT,
    `brand` VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL
);

-- 4. Perintah untuk membuat tabel 'email'
CREATE TABLE `email` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `role` ENUM('atasan_1', 'atasan_2') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL
);

-- 5. Perintah untuk membuat tabel 'outbound_request'
CREATE TABLE `outbound_request` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `order_date` DATE NULL,                
    `sla_date` DATE NULL,                  
    `order_number` VARCHAR(255) NULL,      
    `id_item` INT NOT NULL,                
    `qty` INT NULL,                        
    `keterangan` TEXT NULL,                
    `jenis` ENUM('butuh_validasi', 'butuh_replen') NOT NULL, 
    `is_confirm` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    FOREIGN KEY (`id_item`) REFERENCES `item`(`id`)
);


-- 6. Perintah untuk membuat tabel 'konfirmasi' (Tabel baru)
CREATE TABLE `konfirmasi` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_outbond_request` INT NOT NULL UNIQUE, -- Unique agar 1 OR hanya bisa 1 konfirmasi
    `supply_qty` INT NULL,
    `discrepancy` INT NULL, -- supply_qty - request_qty
    `status` ENUM('Fullfill Pas', 'Fullfill Lebih', 'Kurang Supply') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_outbond_request`) REFERENCES `outbound_request`(`id`)
);