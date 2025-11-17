CREATE TABLE `antrean_farmasi_panggil` (

  `no_resep` VARCHAR(14) NOT NULL,

  `no_rawat` VARCHAR(17) NOT NULL,

  `nm_pasien` VARCHAR(40) NOT NULL,

  `nm_poli` VARCHAR(50) NOT NULL,

  `waktu_panggil` DATETIME NOT NULL,

  PRIMARY KEY (`no_resep`)

) ENGINE=InnoDB DEFAULT CHARSET=latin1;