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

}
