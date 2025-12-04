-- 1. Tabel Mapping (Sesuai Request Anda)
CREATE TABLE IF NOT EXISTS `satu_sehat_mapping_lab` (
  `id_template` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `system` varchar(100) DEFAULT 'http://loinc.org',
  `display` varchar(255) DEFAULT NULL,
  `sampel_code` varchar(50) DEFAULT NULL,
  `sampel_system` varchar(100) DEFAULT 'http://snomed.info/sct',
  `sampel_display` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_template`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 2. Tabel Referensi LOINC (Import data LOINC ke sini)
CREATE TABLE IF NOT EXISTS `satu_sehat_ref_loinc` (
  `loinc_num` varchar(20) NOT NULL,
  `component` varchar(255) DEFAULT NULL,
  `long_common_name` text DEFAULT NULL,
  PRIMARY KEY (`loinc_num`),
  FULLTEXT KEY `idx_search` (`loinc_num`,`long_common_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 3. Tabel Referensi SNOMED (Khusus Sampel/Specimen)
-- Import data SNOMED (hierarchy Specimen) ke sini
CREATE TABLE IF NOT EXISTS `satu_sehat_ref_snomed` (
  `conceptId` varchar(20) NOT NULL,
  `term` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`conceptId`),
  FULLTEXT KEY `idx_term` (`term`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- DATA SAMPEL AWAL (Agar aplikasi bisa ditest langsung)
INSERT IGNORE INTO `satu_sehat_ref_snomed` VALUES 
('119364003', 'Serum specimen'),
('119361006', 'Plasma specimen'),
('122555007', 'Venous blood specimen'),
('119339001', 'Stool specimen'),
('122575003', 'Urine specimen');

INSERT IGNORE INTO `satu_sehat_ref_loinc` VALUES 
('718-7', 'Hemoglobin', 'Hemoglobin [Mass/volume] in Blood'),
('4548-4', 'HbA1c', 'Hemoglobin A1c/Hemoglobin.total in Blood'),
('14749-6', 'Glukosa', 'Glucose [Mass/volume] in Serum or Plasma --1.5 hours post meal');