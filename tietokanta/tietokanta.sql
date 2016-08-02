CREATE TABLE IF NOT EXISTS `kayttaja` (
  `id` int(11) NOT NULL AUTO_INCREMENT, -- PK
  `sahkoposti` varchar(255) NOT NULL, -- UNIQUE KEY
  `salasana_hajautus` varchar(100) NOT NULL,
  `salasana_vaihdettu` datetime DEFAULT NULL,
  `etunimi` varchar(20) DEFAULT NULL,
  `sukunimi` varchar(20) DEFAULT NULL,
  `yritys` varchar(50) DEFAULT NULL,
  `puhelin` varchar(20) DEFAULT NULL,
  `y_tunnus` varchar(9) DEFAULT NULL, -- TODO: turha; poista. Y-tunnus pitäisi varmaan olla yrityksessä
  `yllapitaja` tinyint(1) NOT NULL DEFAULT '0',
  `aktiivinen` tinyint(1) NOT NULL DEFAULT '1',
  `demo` tinyint(1) NOT NULL DEFAULT '0', -- Välikaikainen tunnus sivuston demoamista varten
  `viime_sijainti` varchar(100) DEFAULT '',
  `luotu` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `voimassaolopvm` datetime DEFAULT NULL, -- Miten pitkään tunnus on voimassa, jos demo = 1
  `salasana_uusittava` tinyint(1) NOT NULL DEFAULT '0',
  `rahtimaksu` decimal(11,2) NOT NULL DEFAULT '15',
  `ilmainen_toimitus_summa_raja` decimal(11,2) NOT NULL DEFAULT '50', -- Default 1000
  `vahvista_eula` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`), 
  UNIQUE KEY (`sahkoposti`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1;

/* Meillä alkaa olla aika monta toiminnallisuutta, jotka ei viittaa yksittäiseen asiakkaaseen,
 vaan yritykseen. Tosin siinä tapauksessa meillä on aika monta ongelmaa, esim asiakkaan tiedoissa:
 esim. kuka saa muuttaa y-tunnusta, onko y-tunnus edes asiakkaan tiedoissa, 
 onko yrityksellä oma sivu? Note: vakoilin juuri osalinkkiä, niillä näyttäisi olevan yritystiedot erikseen. */
CREATE TABLE IF NOT EXISTS `yritys` (
  `id` int(11) NOT NULL AUTO_INCREMENT, -- PK
  `nimi` varchar(255) NOT NULL, -- UNIQUE KEY -- Koska ei niitä yrityksiä varmaan useampaa ole
  `y_tunnus` varchar(9) DEFAULT '',
  `sahkoposti` varchar(255) DEFAULT '',
  `puhelin` varchar(20) DEFAULT '',
  `katuosoite` varchar(255) DEFAULT '',
  `postinumero` varchar(10) DEFAULT '',
  `postitoimipaikka` varchar(255) DEFAULT '',
  /*
  Osalinkillä on myös faxnumero, ERP-numero, jälleenmyyjän ERP-numero, ja autofutur-asiakasnumero,
  mutta koska en tiedä mitä noilla tekisi en oikein halua lisätä niitä tietokantaan
  */
  PRIMARY KEY (`id`), UNIQUE KEY (`nimi`, `y_tunnus`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `tuote` (
  `id` int(11) NOT NULL AUTO_INCREMENT, -- PK
  `articleNo` varchar(20) NOT NULL, 
  `brandNo` int(11) NOT NULL,
  `hinta_ilman_ALV` decimal(11,2) NOT NULL DEFAULT '0.00',
  `ALV_kanta` tinyint(1) NOT NULL DEFAULT '0', -- Foreign KEY
  `varastosaldo` int(11) NOT NULL DEFAULT '0',
  `minimisaldo` int(11) NOT NULL DEFAULT '0', -- TODO: Poista; turha
  `minimimyyntiera` int(11) NOT NULL DEFAULT '1',
  `sisaanostohinta` int(11) NOT NULL DEFAULT '0',
  `yhteensa_kpl` int(11) NOT NULL DEFAULT '0', -- Mikä tämän tarkoitus on?
  `keskiostohinta` decimal(11,2) NOT NULL DEFAULT '0',
  `alennusera_kpl` int(11) NOT NULL DEFAULT '0', -- Maaraalennus_kpl -- Saattaa olla turha
  `alennusera_prosentti` decimal(3,2) NOT NULL default '0.00', -- Maaraalennus_pros -- Saattaa olla turha
  `aktiivinen` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`), UNIQUE KEY (`articleNo`, brandNo)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `kayttaja_tuote` (
  `id` int(11) NOT NULL AUTO_INCREMENT, -- PK
  `kayttaja_id` int(11) NOT NULL, -- PK; Foreign K
  `tuote_id` int(11) NOT NULL, -- PK; Foreign K
  `hinta` decimal(11,2) NOT NULL,
  PRIMARY KEY (`id`, `kayttaja_id`, `tuote_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `tilaus` (
  `id` int(11) NOT NULL AUTO_INCREMENT, -- PK
  `kayttaja_id` int(11) NOT NULL, -- Foreign KEY
  `kasitelty` tinyint(1) NOT NULL DEFAULT '0',
  `paivamaara` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pysyva_rahtimaksu` decimal(11,2) NOT NULL DEFAULT '15',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `tilaus_tuote` (
  `tilaus_id` int(11) NOT NULL, -- PK; Foreign K
  `tuote_id` int(11) NOT NULL, -- PK; Foreign K
  `pysyva_hinta` decimal(11,2) NOT NULL,
  `pysyva_alv` decimal(3,2) NOT NULL,
  `pysyva_alennus` decimal(3,2) NOT NULL DEFAULT '0.00',
  `kpl` int(11) NOT NULL,
  PRIMARY KEY (`tilaus_id`, `tuote_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `tilaus_toimitusosoite` (
  `tilaus_id` int(11) NOT NULL, -- PK; Foreign K
  `pysyva_etunimi` varchar(255) NOT NULL,
  `pysyva_sukunimi` varchar(255) NOT NULL,
  `pysyva_sahkoposti` varchar(255) NOT NULL,
  `pysyva_puhelin` varchar(20) NOT NULL,
  `pysyva_yritys` varchar(50) NOT NULL,
  `pysyva_katuosoite` varchar(255) NOT NULL,
  `pysyva_postinumero` varchar(10) NOT NULL,
  `pysyva_postitoimipaikka` varchar(255) NOT NULL,
  PRIMARY KEY (`tilaus_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `pw_reset` (
  `reset_key` varchar(100) NOT NULL, -- PK
  `user_id` varchar(255) COLLATE utf8_swedish_ci NOT NULL, -- PK; Foreign K
  `reset_exp_aika` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reset_key`, `user_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `ALV_kanta` (
  `kanta` tinyint(1) NOT NULL, -- PK
  `prosentti` decimal(3,2) NOT NULL, -- UNIQUE K
  PRIMARY KEY (`kanta`),
  UNIQUE KEY (`prosentti`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `toimitusosoite` (
  `kayttaja_id` int(11) NOT NULL, -- PK; Foreign K
  `osoite_id` tinyint(2) NOT NULL, -- PK
  `etunimi` varchar(255) DEFAULT '',
  `sukunimi` varchar(255) DEFAULT '',
  `sahkoposti` varchar(255) DEFAULT '',
  `puhelin` varchar(20) DEFAULT '',
  `yritys` varchar(50) DEFAULT '',
  `katuosoite` varchar(255) DEFAULT '',
  `postinumero` varchar(10) DEFAULT '',
  `postitoimipaikka` varchar(255) DEFAULT '',
  PRIMARY KEY (`kayttaja_id`, `osoite_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `tuote_ostopyynto` (
  `tuote_id` int(11) NOT NULL, -- PK; Foreign K
  `kayttaja_id` int(11) NOT NULL, -- PK; Foreign K
  `laskuri` int(4) DEFAULT '1', -- Ostopyyntöjen määrä
  PRIMARY KEY (`tuote_id`, `kayttaja_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `tuote_erikoishinta` (
  `id` int(11) NOT NULL AUTO_INCREMENT, -- PK
  `tuote_id` int(11) NOT NULL, -- Foreign KEY
  `kayttaja_id` int(11) NOT NULL, -- Foreign KEY
  `maaraalennus_kpl` int(11) DEFAULT '0',
  `maaraalennus_prosentti` decimal(3,2) DEFAULT '0.00',
  `yleinenalennus_prosentti` decimal(3,2) DEFAULT '0.00',
  `voimassaolopvm` datetime DEFAULT NULL, -- Jos tarjouksella on vanhenemisraja
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `ostoskori` (
  `id` int(11) NOT NULL AUTO_INCREMENT, -- PK
  `yritys_id` int(11) NOT NULL, -- Foreign KEY
  `kayttaja_id` int(11) NOT NULL, -- Foreign KEY -- Saattaa olla turha
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `ostoskori_tuote` (
  `ostoskori_id` int(11) NOT NULL, -- PK; Foreign K
  `tuote_id` int(11) NOT NULL, -- PK; Foreign K
  `kpl_maara` int(11) DEFAULT '1',
  PRIMARY KEY (`ostoskori_id`, `tuote_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `vaihtoehtoinen_toimittaja` (
  `brandNo` int(11) NOT NULL, -- PK; Foreign K
  `name` varchar(11) DEFAULT '',
  `street` varchar(50) DEFAULT '',
  `zip` varchar(11) DEFAULT '',
  `city` varchar(50) DEFAULT '',
  `phone` varchar(50) DEFAULT '',
  `fax` varchar(50) DEFAULT '',
  `email` varchar(50) DEFAULT '',
  `wwwURL` varchar(50) DEFAULT '',
  PRIMARY KEY (`ostoskori_id`, `tuote_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
