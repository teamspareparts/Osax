/*
ColumnType  | Max Value (signed/unsigned)

  TINYINT   |   127 / 255
 SMALLINT   |   32767 / 65535
MEDIUMINT   |   8388607 / 16777215
      INT   |   2147483647 / 4294967295
   BIGINT   |   9223372036854775807 / 18446744073709551615
*/
  
CREATE TABLE IF NOT EXISTS yritys (
  id smallint UNSIGNED NOT NULL AUTO_INCREMENT, -- PK
  y_tunnus varchar(9) NOT NULL,  -- UK
  yritystunniste varchar(50) NOT NULL COMMENT 'Kirjautumista varten', -- UK
  nimi varchar(255) NOT NULL,
  katuosoite VARCHAR(255),
  postinumero varchar(10),
  postitoimipaikka VARCHAR(255),
  maa VARCHAR(255),
  puhelin varchar(20),
  www_url VARCHAR(255) COMMENT 'Yrityksen WWW-osoite',
  email VARCHAR(255),

  -- Mikä tämä on ?? Onko käyttäjän asettama? Kumpikin mahdollisuus, vai onko tarkoitus valita kumpi suunnittelussa?
  -- Pitääkö olla erillinen boolean kolumni jossa kerrotaan onko se URL vai polku?
  logo VARCHAR(255) COMMENT 'Tiedosto-polku, tai URL',

  PRIMARY KEY (id),
  UNIQUE KEY (y_tunnus),
  UNIQUE KEY (yritystunniste)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS pankkitili (
  yritys_id smallint UNSIGNED NOT NULL AUTO_INCREMENT, -- PK, FK
  -- Missä muodossa? Montako merkkiä? Pitääkö olla hajautettu (for security reasons)?
  -- Voiko usealla yrityksellä olla sama pankkitili?
  pankkitili VARCHAR(255) NOT NULL COMMENT '',
  PRIMARY KEY (yritys_id), UNIQUE KEY (yritys_id, pankkitili),
  CONSTRAINT fk_pankkitili_yritys FOREIGN KEY (yritys_id) REFERENCES yritys(id)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1 COMMENT 'Yrityksen pankkitili; One-to-Many';


CREATE TABLE IF NOT EXISTS kayttaja (
  id smallint UNSIGNED NOT NULL AUTO_INCREMENT, -- PK
  PRIMARY KEY (id)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1;






CREATE TABLE IF NOT EXISTS testi (
  id smallint UNSIGNED NOT NULL AUTO_INCREMENT, -- PK
  testi BOOL COMMENT 'testi äöå ?%&#',
  PRIMARY KEY (id)
) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci AUTO_INCREMENT=1 COMMENT 'testi taulu, missä tämä kommentti näkyy?';
