CREATE TABLE IF NOT EXISTS `kayttaja` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `salasana_hajautus` varchar(100) COLLATE utf8_swedish_ci NOT NULL,
  `salasana_vaihdettu` datetime DEFAULT NULL,
  `etunimi` varchar(20) COLLATE utf8_swedish_ci DEFAULT NULL,
  `sukunimi` varchar(20) COLLATE utf8_swedish_ci DEFAULT NULL,
  `yritys` varchar(50) COLLATE utf8_swedish_ci DEFAULT NULL,
  `sahkoposti` varchar(255) COLLATE utf8_swedish_ci DEFAULT NULL,
  `puhelin` varchar(20) COLLATE utf8_swedish_ci DEFAULT NULL,
  `y_tunnus` varchar(9) COLLATE utf8_swedish_ci DEFAULT NULL,
  `yllapitaja` tinyint(1) NOT NULL DEFAULT '0',
  `aktiivinen` tinyint(1) NOT NULL DEFAULT '1',
  `demo` tinyint(1) NOT NULL DEFAULT '0',
  `viime_sijainti` varchar(100) COLLATE utf8_swedish_ci DEFAULT '',
  `luotu` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `voimassaolopvm` datetime DEFAULT NULL,
  `salasana_uusittava` tinyint(1) NOT NULL DEFAULT '0',
  `rahtimaksu` decimal(11,2) NOT NULL DEFAULT '15',
  `ilmainen_toimitus_summa_raja` decimal(11,2) NOT NULL DEFAULT '50', -- Default 1000
  PRIMARY KEY (`id`, `sahkoposti`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `tuote` (
  `id` int(11) NOT NULL,
  `hinta_ilman_ALV` decimal(11,2) NOT NULL,
  `ALV_kanta` tinyint(1) NOT NULL,
  `varastosaldo` int(11) NOT NULL DEFAULT '0',
  `minimisaldo` int(11) NOT NULL DEFAULT '0',
  `minimimyyntiera` int(11) NOT NULL DEFAULT '0',
  `sisaanostohinta` int(11) NOT NULL DEFAULT '0',
  `yhteensa_kpl` int(11) NOT NULL DEFAULT '0',
  `keskiostohinta` decimal(11,2) NOT NULL DEFAULT '0',
  `alennusera_kpl` int(11) NOT NULL DEFAULT '0',
  `alennusera_prosentti` decimal(3,2) NOT NULL default '0.00',
  `aktiivinen` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `kayttaja_tuote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kayttaja_id` int(11) NOT NULL,
  `tuote_id` int(11) NOT NULL,
  `hinta` decimal(11,2) NOT NULL,
  PRIMARY KEY (`id`, `kayttaja_id`, `tuote_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `tilaus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kayttaja_id` int(11) NOT NULL,
  `kasitelty` tinyint(1) NOT NULL DEFAULT '0',
  `paivamaara` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pysyva_rahtimaksu` decimal(11,2) NOT NULL DEFAULT '15',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `tilaus_tuote` (
  `tilaus_id` int(11) NOT NULL,
  `tuote_id` int(11) NOT NULL,
  `pysyva_hinta` decimal(11,2) NOT NULL,
  `pysyva_alv` decimal(3,2) NOT NULL,
  `pysyva_alennus` decimal(3,2) NOT NULL DEFAULT '0.00',
  `kpl` int(11) NOT NULL,
  PRIMARY KEY (`tilaus_id`, `tuote_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `tilaus_toimitusosoite` (
  `tilaus_id` int(11) NOT NULL,
  `pysyva_etunimi` varchar(255) COLLATE NOT NULL,
  `pysyva_sukunimi` varchar(255) COLLATE NOT NULL,
  `pysyva_sahkoposti` varchar(255) COLLATE NOT NULL,
  `pysyva_puhelin` varchar(20) COLLATE NOT NULL,
  `pysyva_yritys` varchar(50) COLLATE NOT NULL,
  `pysyva_katuosoite` varchar(255) COLLATE NOT NULL,
  `pysyva_postinumero` varchar(10) COLLATE NOT NULL,
  `pysyva_postitoimipaikka` varchar(255) NOT NULL,
  PRIMARY KEY (`tilaus_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `pw_reset` (
  `reset_key` varchar(100) NOT NULL,
  `user_id` varchar(255) COLLATE utf8_swedish_ci NOT NULL,
  `reset_exp_aika` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reset_key`, `user_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `ALV_kanta` (
  `kanta` tinyint(1) NOT NULL,
  `prosentti` decimal(3,2) NOT NULL,
  PRIMARY KEY (`kanta`),
  UNIQUE KEY (`prosentti`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `toimitusosoite` (
  `kayttaja_id` int(11) NOT NULL, -- PK
  `osoite_id` tinyint(2) NOT NULL, -- PK
  `etunimi` varchar(255) DEFAULT '',
  `sukunimi` varchar(255) DEFAULT '',
  `sahkoposti` varchar(255) DEFAULT '',
  `puhelin` varchar(20) DEFAULT '',
  `yritys` varchar(50) DEFAULT '',
  `katuosoite` varchar(255) DEFAULT '',
  `postinumero` varchar(10) DEFAULT '',
  `postitoimipaikka` varchar(255) DEFAULT ''
  PRIMARY KEY (`kayttaja_id`, `osoite_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `asiakas_hinta` (
  `kayttaja_id` int(11) NOT NULL, -- PK
  `tuote_id` int(11) NOT NULL, -- PK
  `hinta` decimal(11,2) NOT NULL,
  PRIMARY KEY (`kayttaja_id`, `tuote_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `tuote_search` (
  `tuote_id` int(11) NOT NULL, -- PK
  `search_no` varchar(20) NOT NULL, -- PK
PRIMARY KEY (`tuote_id`, `search_no`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `tuote_oe` (
  `tuote_id` int(11) NOT NULL, -- PK
  `oe_number` varchar(20) NOT NULL, -- PK
PRIMARY KEY (`tuote_id`, `oe_number`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;