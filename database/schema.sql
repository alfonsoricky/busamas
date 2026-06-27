CREATE DATABASE IF NOT EXISTS `busamas`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `busamas`;

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) NOT NULL DEFAULT 'admin',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    `komisi_sales_terbayar` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `komisi_sales_belum_terbayar` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `status_pembayaran_komisi_sales` VARCHAR(50) NULL,
    `tanggal_transfer_komisi_sales` DATE NULL,
    `komisi_manager_terbayar` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `komisi_manager_utang` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `tanggal_transfer_komisi_manager` DATE NULL,
    `tanggal_transfer_komisi_admin` DATE NULL,
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
    `status_pembayaran` VARCHAR(20) NOT NULL DEFAULT 'Lunas',
    `tanggal_pembayaran` DATE NULL,
    `pph_final_terbayar` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `pph_final_belum_terbayar` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `komisi_admin_terbayar` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `komisi_admin_belum_terbayar` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `biaya_kirim` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `biaya_admin_bank` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `total_pembelian_barang` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `total_utang_pembelian_barang` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `status_pembelian_barang` VARCHAR(20) NOT NULL DEFAULT 'Lunas',
    `tanggal_transfer_pembelian_barang` DATE NULL,
    `google_drive_file_id` VARCHAR(200) NULL,
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

CREATE TABLE IF NOT EXISTS `operational_expenses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tanggal` DATE NULL,
    `bulan_pnl` TINYINT UNSIGNED NULL COMMENT 'Bulan PNL (1-12) sesuai blok visual Excel, bisa berbeda dari tanggal transaksi',
    `tahun_pnl` SMALLINT UNSIGNED NULL COMMENT 'Tahun PNL sesuai blok visual Excel',
    `kategori` VARCHAR(50) NOT NULL DEFAULT 'operational' COMMENT 'Kategori: operational atau bonus',
    `nama_pengeluaran` VARCHAR(255) NOT NULL,
    `jumlah` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `status_pembayaran` VARCHAR(50) NOT NULL DEFAULT 'Hutang',
    `tanggal_pembayaran` DATE NULL,
    `keterangan` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `operational_expenses_bulan_pnl_index` (`tahun_pnl`, `bulan_pnl`, `kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `partner_prive` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tanggal` DATE NOT NULL,
    `bulan_pnl` TINYINT UNSIGNED NULL COMMENT 'Bulan periode prive (1-12)',
    `tahun_pnl` SMALLINT UNSIGNED NULL COMMENT 'Tahun periode prive',
    `partner` VARCHAR(150) NOT NULL,
    `jumlah` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `status_pembayaran` VARCHAR(50) NOT NULL DEFAULT 'Lunas',
    `tanggal_transfer` DATE NULL,
    `keterangan` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `partner_prive_period_index` (`tahun_pnl`, `bulan_pnl`, `partner`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chart_of_accounts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(20) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `type` ENUM('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL,
    `normal_balance` ENUM('debit', 'credit') NOT NULL,
    `parent_id` BIGINT UNSIGNED NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `chart_of_accounts_code_unique` (`code`),
    KEY `chart_of_accounts_parent_id_index` (`parent_id`),
    CONSTRAINT `chart_of_accounts_parent_id_foreign`
        FOREIGN KEY (`parent_id`) REFERENCES `chart_of_accounts` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `journal_entries` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `entry_date` DATE NOT NULL,
    `posted_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `source_type` VARCHAR(50) NOT NULL,
    `source_id` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `journal_entries_source_unique` (`source_type`, `source_id`),
    KEY `journal_entries_entry_date_index` (`entry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `journal_lines` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `journal_entry_id` BIGINT UNSIGNED NOT NULL,
    `account_id` BIGINT UNSIGNED NOT NULL,
    `debit` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `credit` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `memo` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `journal_lines_journal_entry_id_index` (`journal_entry_id`),
    KEY `journal_lines_account_id_index` (`account_id`),
    CONSTRAINT `journal_lines_journal_entry_id_foreign`
        FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `journal_lines_account_id_foreign`
        FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `chart_of_accounts` (`code`, `name`, `type`, `normal_balance`) VALUES
('1100', 'Kas / Bank', 'asset', 'debit'),
('1200', 'Piutang Usaha', 'asset', 'debit'),
('2100', 'Hutang Pembelian Barang', 'liability', 'credit'),
('2110', 'Hutang Operasional', 'liability', 'credit'),
('2200', 'Hutang Komisi Sales', 'liability', 'credit'),
('2210', 'Hutang Komisi Manager', 'liability', 'credit'),
('2220', 'Hutang Komisi Admin', 'liability', 'credit'),
('2300', 'Hutang PPh Final', 'liability', 'credit'),
('3100', 'Modal Pemilik', 'equity', 'credit'),
('3200', 'Laba Ditahan', 'equity', 'credit'),
('3300', 'Laba Tahun Berjalan', 'equity', 'credit'),
('4100', 'Pendapatan Penjualan', 'revenue', 'credit'),
('4110', 'Diskon Penjualan', 'expense', 'debit'),
('5100', 'HPP / Pembelian Barang', 'expense', 'debit'),
('6100', 'Beban Komisi Sales', 'expense', 'debit'),
('6110', 'Beban Komisi Manager', 'expense', 'debit'),
('6120', 'Beban Komisi Admin', 'expense', 'debit'),
('6200', 'Beban Operasional', 'expense', 'debit'),
('6210', 'Beban Bonus', 'expense', 'debit'),
('6300', 'Biaya Kirim', 'expense', 'debit'),
('6400', 'Biaya Admin Bank', 'expense', 'debit'),
('6500', 'Beban PPh Final', 'expense', 'debit')
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `type` = VALUES(`type`),
    `normal_balance` = VALUES(`normal_balance`),
    `is_active` = 1;

SET FOREIGN_KEY_CHECKS = 1;
