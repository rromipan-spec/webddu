-- Jalankan satu kali melalui phpMyAdmin sebelum deploy modul kredibilitas.
CREATE TABLE IF NOT EXISTS institution_profile (
    profile_key VARCHAR(60) PRIMARY KEY,
    profile_value MEDIUMTEXT NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_institution_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- INSERT IGNORE menjaga informasi yang sudah pernah Anda isi.
INSERT IGNORE INTO institution_profile (profile_key, profile_value) VALUES
('organization_name', 'Dompet Dana Umat'),
('parent_organization', 'Yayasan Daarul Uluum'),
('legal_entity_name', ''),
('deed_number', ''),
('ministry_number', ''),
('tax_number', ''),
('official_address', 'Jl. Durian Raya Jl. Bantar Kemang No.76/219, RT.004/RW.05, Baranangsiang, Kec. Bogor Tim., Kota Bogor, Jawa Barat 16143'),
('official_phone', '+62 851 2127 7046'),
('official_email', 'Admin@dompetdanaumat.com'),
('management_structure', ''),
('donation_accounts', ''),
('collection_reports', ''),
('beneficiary_documentation', ''),
('official_disclaimer', 'Donasi hanya disalurkan melalui rekening dan kanal resmi yang tercantum pada website Dompet Dana Umat. Selalu konfirmasikan informasi yang meragukan melalui WhatsApp resmi.'),
('privacy_contact', 'Admin@dompetdanaumat.com');
