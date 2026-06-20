CREATE DATABASE IF NOT EXISTS `busamas`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `busamas`;

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `master_barang` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kode_barang` VARCHAR(20) NOT NULL,
    `nama_barang` VARCHAR(150) NOT NULL,
    `ukuran` VARCHAR(50) NOT NULL,
    `isi_default` VARCHAR(50) NULL,
    `satuan_default` VARCHAR(50) NULL,
    `harga_default` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `jumlah_alias` INT UNSIGNED NOT NULL DEFAULT 0,
    `jumlah_transaksi` INT UNSIGNED NOT NULL DEFAULT 0,
    `jumlah_invoice` INT UNSIGNED NOT NULL DEFAULT 0,
    `alias` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `master_barang_kode_barang_unique` (`kode_barang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `master_customers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kode_customer` VARCHAR(20) NOT NULL,
    `nama_customer` VARCHAR(150) NULL,
    `nama_laundry` VARCHAR(150) NOT NULL,
    `no_telepon` VARCHAR(50) NULL,
    `alamat_default` TEXT NULL,
    `jumlah_alias` INT UNSIGNED NOT NULL DEFAULT 0,
    `jumlah_invoice` INT UNSIGNED NOT NULL DEFAULT 0,
    `alias` TEXT NULL,
    `alamat_lain` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `master_customers_kode_customer_unique` (`kode_customer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `master_sales` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kode_sales` VARCHAR(20) NOT NULL,
    `nama_sales` VARCHAR(150) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `master_sales_kode_sales_unique` (`kode_sales`),
    UNIQUE KEY `master_sales_nama_sales_unique` (`nama_sales`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoices` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kode_invoice` VARCHAR(20) NOT NULL,
    `nomor_invoice` VARCHAR(50) NOT NULL,
    `tanggal_invoice` VARCHAR(50) NULL,
    `nomor_surat_jalan` VARCHAR(50) NULL,
    `tanggal_surat_jalan` VARCHAR(50) NULL,
    `po_number` VARCHAR(50) NULL,
    `kode_sales_1` VARCHAR(20) NULL,
    `nama_sales_1` VARCHAR(150) NULL,
    `kode_sales_2` VARCHAR(20) NULL,
    `nama_sales_2` VARCHAR(150) NULL,
    `komisi_sales_1_persen` DECIMAL(8,4) NOT NULL DEFAULT 0,
    `komisi_sales_2_persen` DECIMAL(8,4) NOT NULL DEFAULT 0,
    `kode_customer` VARCHAR(20) NULL,
    `nama_customer_master` VARCHAR(150) NULL,
    `nama_customer_invoice` VARCHAR(150) NULL,
    `nama_laundry_invoice` VARCHAR(150) NULL,
    `no_telepon` VARCHAR(50) NULL,
    `alamat` TEXT NULL,
    `total_item` INT UNSIGNED NOT NULL DEFAULT 0,
    `total_qty` DECIMAL(15,4) NOT NULL DEFAULT 0,
    `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `harga_normal_pricelist` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `discount_persen` DECIMAL(8,4) NOT NULL DEFAULT 0,
    `discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `total_harga_jual` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `total_pembelian_barang` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `total_utang_pembelian_barang` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `status_pembelian_barang` VARCHAR(20) NOT NULL DEFAULT 'Lunas',
    `file_invoice` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `invoices_kode_invoice_unique` (`kode_invoice`),
    UNIQUE KEY `invoices_nomor_invoice_unique` (`nomor_invoice`),
    KEY `invoices_kode_customer_index` (`kode_customer`),
    CONSTRAINT `invoices_kode_customer_foreign`
        FOREIGN KEY (`kode_customer`) REFERENCES `master_customers` (`kode_customer`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoice_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kode_invoice` VARCHAR(20) NOT NULL,
    `nomor_invoice` VARCHAR(50) NOT NULL,
    `tanggal_invoice` VARCHAR(50) NULL,
    `kode_customer` VARCHAR(20) NULL,
    `kode_barang` VARCHAR(20) NULL,
    `nama_barang_master` VARCHAR(150) NULL,
    `ukuran_master` VARCHAR(50) NULL,
    `nama_barang_invoice` VARCHAR(150) NULL,
    `isi_invoice` VARCHAR(50) NULL,
    `jumlah` DECIMAL(15,4) NOT NULL DEFAULT 0,
    `satuan` VARCHAR(50) NULL,
    `harga` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `total` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `file_invoice` VARCHAR(255) NULL,
    `baris` INT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `invoice_items_kode_invoice_index` (`kode_invoice`),
    KEY `invoice_items_kode_barang_index` (`kode_barang`),
    CONSTRAINT `invoice_items_kode_invoice_foreign`
        FOREIGN KEY (`kode_invoice`) REFERENCES `invoices` (`kode_invoice`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `invoice_items_kode_barang_foreign`
        FOREIGN KEY (`kode_barang`) REFERENCES `master_barang` (`kode_barang`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
