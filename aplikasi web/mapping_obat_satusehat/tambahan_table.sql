-- 1. Tabel Kamus KFA (Harus diisi data KFA agar bisa dicari)
-- Kamerad bisa import data CSV KFA Kemenkes ke tabel ini nanti.
CREATE TABLE IF NOT EXISTS `satu_sehat_ref_kfa` (
  `kfa_code` varchar(50) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `system` varchar(100) DEFAULT 'http://sys-ids.kemkes.go.id/kfa',
  PRIMARY KEY (`kfa_code`),
  KEY `idx_nama` (`display_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 2. Tabel Referensi Bentuk Sediaan (Form)
CREATE TABLE IF NOT EXISTS `satu_sehat_ref_form` (
  `code` varchar(50) NOT NULL,
  `display` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Insert Data Umum Bentuk Sediaan (Sampel)
INSERT IGNORE INTO `satu_sehat_ref_form` (`code`, `display`) VALUES
('BS066', 'Tablet'), ('BS019', 'Kapsul'), ('BS055', 'Sirup'), 
('BS034', 'Larutan Injeksi'), ('BS035', 'Infus'), ('BS030', 'Krim'), 
('BS049', 'Serbuk Injeksi'), ('BS059', 'Supositoria');

-- 3. Tabel Referensi Rute (ATC Baku)
CREATE TABLE IF NOT EXISTS `satu_sehat_ref_route` (
  `code` varchar(50) NOT NULL,
  `display` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Insert Data Baku ATC (Sesuai kesepakatan kita sebelumnya)
INSERT IGNORE INTO `satu_sehat_ref_route` (`code`, `display`) VALUES
('O', 'Oral (Minum)'),
('P', 'Parenteral (Suntik/Infus)'),
('Topical', 'Topikal (Oles/Kulit/Mata/Telinga)'),
('R', 'Rectal (Anus)'),
('N', 'Nasal (Hidung)'),
('V', 'Vaginal'),
('Inhal', 'Inhalasi (Uap)');