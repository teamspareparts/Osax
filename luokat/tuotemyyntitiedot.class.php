<?php
/**
 * Created by PhpStorm.
 * User: jjarv
 * Date: 15/01/2018
 * Time: 15:50
 */

class tuoteMyyntitiedot {

	/**
	 * Hakee vastaavat tuotteet annetulle ID:lle ja hyllypaikalle.
	 * @param DByhteys $db
	 * @param int      $tuoteID
	 * @param string   $hyllypaikka
	 * @return Tuote[]
	 */
	public static function haeVastaavat( DByhteys $db, int $tuoteID, string $hyllypaikka ) {
		$sql = "SELECT id, articleNo, nimi, valmistaja, varastosaldo
				FROM tuote 
				WHERE hyllypaikka = ? AND id != ?";

		return $db->query( $sql, [ $hyllypaikka, $tuoteID ], FETCH_ALL, null, 'Tuote' );
	}

	/**
	 * Haetaan montako tuotetta on ostotilauskirjalla (lähetetyt).
	 * Käytössä ostopyynnöissä (yp_hankintapyynnöt) ylläpidolle tiedoksi, että tuotetta on jo tilauksessa.
	 * @param DByhteys $db
	 * @param int      $tuoteID
	 * @return array
	 */
	public static function otkTuotettaTilattu( DByhteys $db, int $tuoteID ) {
		$sql = "SELECT SUM(kpl) AS kpl_maara
				FROM ostotilauskirja_tuote_arkisto
				INNER JOIN ostotilauskirja_arkisto 
					ON ostotilauskirja_arkisto.ostotilauskirja_id = ostotilauskirja_tuote_arkisto.ostotilauskirja_id
				WHERE tuote_id = ?
					AND ostotilauskirja_arkisto.hyvaksytty = FALSE";

		return $db->query( $sql, [ $tuoteID ], FETCH_ALL );
	}

	/**
	 * Tuotteen vuosimyynti (from now to -1 year)
	 * @param DByhteys $db
	 * @param int      $tuoteID
	 * @return array
	 */
	public static function tuotteenVuosimyynti( DByhteys $db, int $tuoteID ) {
		$sql = "SELECT count(tilaus_id) AS tilausten_maara, sum(kpl) AS kpl_maara, 
					SUM(pysyva_hinta * (1+pysyva_alv) * (1-pysyva_alennus))/count(tilaus_id) AS keskimyyntihinta
				FROM tilaus_tuote
				INNER JOIN tilaus 
					ON tilaus_tuote.tilaus_id = tilaus.id
					AND tilaus.paivamaara > DATE_SUB(NOW(),INTERVAL 1 YEAR)
					AND tilaus.maksettu = TRUE 
				WHERE tuote_id = ?";

		return $db->query( $sql, [ $tuoteID ], FETCH_ALL );
	}

	/**
	 * Tuotteen kokonaismyynti yhteensä
	 * @param DByhteys $db
	 * @param int      $tuoteID
	 * @return array
	 */
	public static function tuotteenKokonaisMyynti( DByhteys $db, int $tuoteID ) {
		$sql = "SELECT count(tilaus_id) AS tilausten_maara, sum(kpl) AS kpl_maara, 
					SUM(pysyva_hinta * (1+pysyva_alv) * (1-pysyva_alennus))/count(tilaus_id) AS keskimyyntihinta
				FROM tilaus_tuote
				INNER JOIN tilaus 
					ON tilaus_tuote.tilaus_id = tilaus.id
					AND tilaus.maksettu = TRUE 
				WHERE tuote_id = ?";

		return $db->query( $sql, [ $tuoteID ], FETCH_ALL );
	}

	/**
	 * Hyllypaikan vuosimyynti (from now to -1 year)
	 * @param DByhteys $db
	 * @param string   $hyllypaikka
	 * @return array
	 */
	public static function hyllypaikanVuosimyynti( DByhteys $db, string $hyllypaikka ) {
		$sql = "SELECT count(tilaus_id) AS tilausten_maara, sum(kpl) AS kpl_maara, 
					SUM(pysyva_hinta * (1+pysyva_alv) * (1-pysyva_alennus))/count(tilaus_id) AS keskimyyntihinta
				FROM tilaus_tuote
				INNER JOIN tilaus 
					ON tilaus_tuote.tilaus_id = tilaus.id
					AND tilaus.paivamaara > DATE_SUB(NOW(),INTERVAL 1 YEAR)
					AND tilaus.maksettu = TRUE
				INNER JOIN tuote
					ON tuote.hyllypaikka = ?";

		return $db->query( $sql, [ $hyllypaikka ], FETCH_ALL );
	}

	/**
	 * Hyllypaikan vuosimyynti (from now to -1 year)
	 * @param DByhteys $db
	 * @param string   $hyllypaikka
	 * @return array
	 */
	public static function hyllypaikanKokonaisMyynti( DByhteys $db, string $hyllypaikka ) {
		$sql = "SELECT count(tilaus_id) AS tilausten_maara, sum(kpl) AS kpl_maara, 
					SUM(pysyva_hinta * (1+pysyva_alv) * (1-pysyva_alennus))/count(tilaus_id) AS keskimyyntihinta
				FROM tilaus_tuote
				INNER JOIN tilaus 
					ON tilaus_tuote.tilaus_id = tilaus.id
					AND tilaus.maksettu = TRUE 
				INNER JOIN tuote
					ON tuote.hyllypaikka = ?";

		return $db->query( $sql, [ $hyllypaikka ], FETCH_ALL );
	}
}
