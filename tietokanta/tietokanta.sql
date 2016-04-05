CREATE TABLE IF NOT EXISTS `kayttaja` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `salasana_hajautus` varchar(100) COLLATE utf8_swedish_ci NOT NULL,
  `salasana_aika` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `etunimi` varchar(20) COLLATE utf8_swedish_ci NOT NULL,
  `sukunimi` varchar(20) COLLATE utf8_swedish_ci NOT NULL,
  `yritys` varchar(50) COLLATE utf8_swedish_ci NOT NULL,
  `sahkoposti` varchar(255) COLLATE utf8_swedish_ci DEFAULT NULL,
  `puhelin` varchar(20) COLLATE utf8_swedish_ci DEFAULT NULL,
  `yllapitaja` tinyint(1) NOT NULL DEFAULT '0',
  `aktiivinen` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `tuote` (
  `id` int(11) NOT NULL,
  `hinta` decimal(11,2) NOT NULL,
  `varastosaldo` int(11) NOT NULL DEFAULT '0',
  `minimisaldo` int(11) NOT NULL DEFAULT '0',
  `minimimyyntiera` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

CREATE TABLE IF NOT EXISTS `kayttaja_tuote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kayttaja_id` int(11) NOT NULL,
  `tuote_id` int(11) NOT NULL,
  `hinta` decimal(11,2) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `tilaus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kayttaja_id` int(11) NOT NULL,
  `kasitelty` tinyint(1) NOT NULL DEFAULT '0',
  `paivamaara` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `tilaus_tuote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tilaus_id` int(11) NOT NULL,
  `tuote_id` int(11) NOT NULL,
  `pysyva_hinta` decimal(11,2) NOT NULL,
  `kpl` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `pw_reset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reset_key` varchar(100) NOT NULL,
  `user_id` varchar(255) COLLATE utf8_swedish_ci NOT NULL,
  `reset_exp_aika` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1;