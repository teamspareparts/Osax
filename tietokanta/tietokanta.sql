CREATE TABLE IF NOT EXISTS `kayttaja` (
  `id` int(11) NOT NULL AUTO_INCREMENT, -- PK
  `sahkoposti` varchar(255) NOT NULL, -- UNIQUE KEY
  `salasana_hajautus` varchar(100) NOT NULL,
  `salasana_vaihdettu` timestamp DEFAULT CURRENT_TIMESTAMP,
  `etunimi` varchar(20) DEFAULT NULL,
  `sukunimi` varchar(20) DEFAULT NULL,
  `yritys_id` int(11) DEFAULT NULL, -- Foreign K
  `puhelin` varchar(20) DEFAULT NULL,
  `yllapitaja` tinyint(1) NOT NULL DEFAULT '0',
  `aktiivinen` tinyint(1) NOT NULL DEFAULT '1',
  `demo` tinyint(1) NOT NULL DEFAULT '0', -- Välikaikainen tunnus sivuston demoamista varten
  `viime_sijainti` varchar(100) DEFAULT '',
  `luotu` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `voimassaolopvm` timestamp DEFAULT 0, -- Miten pitkään tunnus on voimassa, jos demo = 1
  `salasana_uusittava` tinyint(1) NOT NULL DEFAULT '0',
  `rahtimaksu` decimal(11,2) NOT NULL DEFAULT '15',
  `ilmainen_toimitus_summa_raja` decimal(11,2) NOT NULL DEFAULT '1000',
  `vahvista_eula` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`), UNIQUE KEY (`sahkoposti`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `yritys` (
  `id` int(11) NOT NULL AUTO_INCREMENT, -- PK
  `nimi` varchar(255) NOT NULL, -- UNIQUE KEY
  `y_tunnus` varchar(9) NOT NULL,  -- UNIQUE KEY
  `sahkoposti` varchar(255) DEFAULT '',
  `puhelin` varchar(20) DEFAULT '',
  `katuosoite` varchar(255) DEFAULT '',
  `postinumero` varchar(10) DEFAULT '',
  `postitoimipaikka` varchar(255) DEFAULT '',
  `maa` VARCHAR(200) DEFAULT 'Suomi',
  `aktiivinen` tinyint(1) DEFAULT '1',
  `rahtimaksu` decimal(11,2) NOT NULL DEFAULT '15',
  `ilmainen_toimitus_summa_raja` decimal(11,2) NOT NULL DEFAULT '1000',
  PRIMARY KEY (`id`), UNIQUE KEY (`nimi`, `y_tunnus`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `tuote` (
  `id` int(11) NOT NULL AUTO_INCREMENT, -- PK
  `articleNo` varchar(30) NOT NULL, -- UNIQUE KEY
  `brandNo` int(11) NOT NULL, -- UNIQUE KEY
  `hankintapaikka_id` int(11) NOT NULL, -- UNIQUE KEY
  `tuotekoodi` varchar(30) NOT NULL, -- Tuotteen näkyvä koodi. Muotoa hankintapaikka_id-articleNo (100-QTB249)
  `tilaus_koodi` varchar(30) NOT NULL, -- Koodi, jota käytetään tilauskirjaa tehdessä.
  `hinta_ilman_ALV` decimal(11,2) NOT NULL DEFAULT '0.00',
  `ALV_kanta` tinyint(1) NOT NULL DEFAULT '0', -- Foreign KEY
  `varastosaldo` int(11) NOT NULL DEFAULT '0',
  `minimimyyntiera` int(11) NOT NULL DEFAULT '1',
  `sisaanostohinta` decimal(11,2) NOT NULL DEFAULT '0',
  `yhteensa_kpl` int(11) NOT NULL DEFAULT '0', -- Tämän avulla lasketaan keskiostohinta.
  `keskiostohinta` decimal(11,2) NOT NULL DEFAULT '0',
  `alennusera_kpl` int(11) NOT NULL DEFAULT '0', -- Maaraalennus_kpl -- Saattaa olla turha
  `alennusera_prosentti` decimal(3,2) NOT NULL default '0.00', -- Maaraalennus_pros -- Saattaa olla turha
  `aktiivinen` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`), UNIQUE KEY (`articleNo`, brandNo, hankintapaikka_id)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `kayttaja_tuote` (
  `id` int(11) NOT NULL AUTO_INCREMENT, -- PK
  `kayttaja_id` int(11) NOT NULL, -- PK Foreign K
  `tuote_id` int(11) NOT NULL, -- PK Foreign K
  `hinta` decimal(11,2) NOT NULL,
  PRIMARY KEY (`id`, `kayttaja_id`, `tuote_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `tilaus` (
  `id` int(11) NOT NULL AUTO_INCREMENT, -- PK
  `kayttaja_id` int(11) NOT NULL, -- Foreign KEY
  `kasitelty` tinyint(1) NOT NULL DEFAULT '0',
  `paivamaara` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pysyva_rahtimaksu` decimal(11,2) NOT NULL DEFAULT '15',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `tilaus_tuote` (
  `tilaus_id` int(11) NOT NULL, -- PK Foreign K
  `tuote_id` int(11) NOT NULL, -- PK Foreign K
  `pysyva_hinta` decimal(11,2) NOT NULL,
  `pysyva_alv` decimal(3,2) NOT NULL,
  `pysyva_alennus` decimal(3,2) NOT NULL DEFAULT '0.00',
  `kpl` int(11) NOT NULL,
  PRIMARY KEY (`tilaus_id`, `tuote_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `tilaus_toimitusosoite` (
  `tilaus_id` int(11) NOT NULL, -- PK Foreign K
  `pysyva_etunimi` varchar(255) NOT NULL,
  `pysyva_sukunimi` varchar(255) NOT NULL,
  `pysyva_sahkoposti` varchar(255) NOT NULL,
  `pysyva_puhelin` varchar(20) NOT NULL,
  `pysyva_yritys` varchar(50) NOT NULL,
  `pysyva_katuosoite` varchar(255) NOT NULL,
  `pysyva_postinumero` varchar(10) NOT NULL,
  `pysyva_postitoimipaikka` varchar(255) NOT NULL,
  `pysyva_maa` varchar(200) DEFAULT 'Suomi',
  PRIMARY KEY (`tilaus_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `pw_reset` (
  `kayttaja_id` int(11) NOT NULL, -- PK Foreign K
  `reset_key_hash` varchar(40) NOT NULL, -- PK
  `reset_exp_aika` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `kaytetty` tinyint(1) NOT NULL DEFAULT 0, -- Onko avain jo käytetty
  PRIMARY KEY (`kayttaja_id`, `reset_key_hash`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `ALV_kanta` (
  `kanta` tinyint(1) NOT NULL, -- PK
  `prosentti` decimal(3,2) NOT NULL, -- UNIQUE K
  PRIMARY KEY (`kanta`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `toimitusosoite` (
  `kayttaja_id` int(11) NOT NULL, -- PK Foreign K
  `osoite_id` tinyint(2) NOT NULL, -- PK
  `etunimi` varchar(255) DEFAULT '',
  `sukunimi` varchar(255) DEFAULT '',
  `sahkoposti` varchar(255) DEFAULT '',
  `puhelin` varchar(20) DEFAULT '',
  `yritys` varchar(50) DEFAULT '',
  `katuosoite` varchar(255) DEFAULT '',
  `postinumero` varchar(10) DEFAULT '',
  `postitoimipaikka` varchar(255) DEFAULT '',
  `maa` varchar(200) DEFAULT 'Suomi',
  PRIMARY KEY (`kayttaja_id`, `osoite_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

/* Valikoimassa olevaa tuotetta varten (varastosaldo == 0) */
CREATE TABLE IF NOT EXISTS `tuote_ostopyynto` (
  `tuote_id` int(11) NOT NULL, -- PK Foreign K
  `kayttaja_id` int(11) NOT NULL, -- PK Foreign K
  `pvm` timestamp DEFAULT CURRENT_TIMESTAMP, -- PK
  PRIMARY KEY (`tuote_id`, `kayttaja_id`, `pvm`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

/* Ei-valikoimassa olevat tuotteet. */
CREATE TABLE IF NOT EXISTS `tuote_hankintapyynto` (
  `articleNo` varchar(20) NOT NULL, -- PK
  `brandName` varchar(30) NOT NULL, -- PK
  `kayttaja_id` int(11) NOT NULL, -- PK Foreign K
  `korvaava_okey` boolean NOT NULL DEFAULT 1,
  `selitys` varchar(1000) DEFAULT NULL,
  `pvm` timestamp DEFAULT CURRENT_TIMESTAMP, -- PK
  PRIMARY KEY (`articleNo`, `brandName`, `kayttaja_id`, `pvm`)
  /* Jotenkin minusta tuntuu, että ei pitäisi olla SQL-taulua neljällä PK:lla */
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `tuote_erikoishinta` (
  `id` int(11) NOT NULL AUTO_INCREMENT, -- PK
  `tuote_id` int(11) DEFAULT NULL, -- Foreign KEY
  `yritys_id` int(11) DEFAULT NULL, -- Foreign KEY
  `kayttaja_id` int(11) DEFAULT NULL, -- Foreign KEY
  `maaraalennus_kpl` int(11) DEFAULT '0',
  `maaraalennus_prosentti` decimal(3,2) DEFAULT '0.00',
  `yleinenalennus_prosentti` decimal(3,2) DEFAULT '0.00',
  `voimassaolopvm` timestamp DEFAULT 0, -- Jos tarjouksella on vanhenemisraja
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `ostoskori` (
  `id` int(11) NOT NULL AUTO_INCREMENT, -- PK
  `yritys_id` int(11) NOT NULL, -- Foreign KEY
  `kayttaja_id` int(11) NOT NULL, -- Foreign KEY -- Saattaa olla turha
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `ostoskori_tuote` (
  `ostoskori_id` int(11) NOT NULL, -- PK Foreign K
  `tuote_id` int(11) NOT NULL, -- PK Foreign K
  `kpl_maara` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ostoskori_id`, `tuote_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `hankintapaikka` (
  `id` int(11) NOT NULL AUTO_INCREMENT, -- PK
  `nimi` varchar(11) NOT NULL, -- UNIQUE K
  `katuosoite` varchar(50) DEFAULT '',
  `postinumero` varchar(11) DEFAULT '',
  `kaupunki` varchar(50) DEFAULT '',
  `maa` varchar(50) DEFAULT '',
  `puhelin` varchar(50) DEFAULT '',
  `yhteyshenkilo_nimi` varchar(50) DEFAULT '',
  `yhteyshenkilo_puhelin` varchar(50) DEFAULT '',
  `yhteyshenkilo_email` varchar(50) DEFAULT '',
  `email` varchar(50) DEFAULT '',
  `fax` varchar(50) DEFAULT '',
  `www_url` varchar(50) DEFAULT '',
  `tilaustapa` varchar(50) DEFAULT '',
  PRIMARY KEY (`id`), UNIQUE KEY (`nimi`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `valmistajan_hankintapaikka` (
  `brandId` int(11) NOT NULL, -- PK
  `hankintapaikka_id` int(11) NOT NULL,  -- PK
  `brandName` varchar(50) NOT NULL,
  `hinnaston_sisaanajo_pvm` DATETIME DEFAULT NULL,
  PRIMARY KEY (`brandId`, `hankintapaikka_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `etusivu_uutinen` (
  `id` INT(11) NOT NULL AUTO_INCREMENT, -- PK
  `tyyppi` TINYINT(1) NOT NULL DEFAULT 1,
  `otsikko` VARCHAR(50) NOT NULL,
  `teksti` VARCHAR(10000) NOT NULL, -- Max. pituus noin 16k.
  `aktiivinen` BOOLEAN NOT NULL DEFAULT 1,
  `pvm` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
