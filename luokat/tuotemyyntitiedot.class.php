<?php
/**
 * Created by PhpStorm.
 * User: jjarv
 * Date: 15/01/2018
 * Time: 15:50
 */

class tuoteMyyntitiedot {

	/**
	 * @param DByhteys $db
	 * @param int      $id
	 * @param string   $hyllypaikka
	 * @return array
	 */
	public static function haeVastaavat( DByhteys $db, int $id, string $hyllypaikka ) {
		$sql = "SELECT id, articleNo, nimi, valmistaja, varastosaldo
				FROM tuote 
				WHERE hyllypaikka = ? AND id != ?";

		return $db->query( $sql, [ $hyllypaikka, $id ], FETCH_ALL );
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
				WHERE tuote_id = ?";

		return $db->query( $sql, [ $tuoteID ], FETCH_ALL );
	}
}
