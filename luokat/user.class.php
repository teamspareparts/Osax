<?php

/**
 * Class User
 */
class User {

	public $id;
	public $yritys_id = 0;

	public $sahkoposti = '';

	public $etunimi = '';
	public $sukunimi = '';
	public $puhelin = 0;

	public $yllapitaja = FALSE;
	public $demo = FALSE;
	public $voimassaolopvm = NULL;
	public $salasana_uusittava = NULL;

	public $toimitusosoitteet = NULL;

	/**
	 * user constructor.
	 * @param DByhteys $db
	 * @param int $user_id
	 */
	function __construct ( DByhteys $db, /*int*/ $user_id ) {
		$this->id = $user_id;
		$sql_q = "SELECT id, yritys_id, sahkoposti, etunimi, sukunimi, puhelin,
						 yllapitaja, demo, voimassaolopvm, salasana_uusittava 
				  FROM kayttaja 
				  WHERE id = ?
				  LIMIT 1";
		$foo = $db->query( $sql_q, [$user_id] );

		$this->yritys_id = $foo->yritys_id;
		$this->sahkoposti = $foo->sahkoposti;

		$this->etunimi = $foo->etunimi;
		$this->sukunimi = $foo->sukunimi;
		$this->puhelin = $foo->puhelin;

		$this->yllapitaja = $foo->yllapitaja;
		$this->demo = $foo->demo;
		$this->voimassaolopvm = $foo->voimassaolopvm;
		$this->salasana_uusittava = $foo->salasana_uusittava;
	}

	/**
	 * Palauttaa koko nimen; muotoiltuna, jos pituus liian pitkä.
	 * @return string
	 */
	function kokoNimi() {
		$str = ( (strlen($this->etunimi) > 15)
				? (substr($this->etunimi, 0, 1) . ".")
				: $this->etunimi )
			. " " . $this->sukunimi;

		if ( strlen($str) > 30 ) {
			$str = substr($str, 0, 26) . "...";
		}
		return $str;
	}

	/**
	 * @param DByhteys $db
	 * @param int $to_id [optional]
	 */
	function haeToimitusosoitteet ( DByhteys $db, /*int*/ $to_id = -1 ) {
		$sql_query = "	
				SELECT	etunimi, sukunimi, sahkoposti, puhelin, yritys, 
					katuosoite, postinumero, postitoimipaikka, maa
				FROM	toimitusosoite
				WHERE	kayttaja_id = ? ";
		if ( $to_id != -1 ) {
			$sql_query .= "AND osoite_id = ? ORDER BY osoite_id LIMIT 1";
			$this->toimitusosoitteet = $db->query( $sql_query, [$this->id, $to_id] );
		}
		$sql_query .= "ORDER BY osoite_id";
		$this->toimitusosoitteet = $db->query( $sql_query, [$this->id], FETCH_ALL );
	}
}
