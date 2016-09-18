<?php

/**
 * Class Yritys
 */
class Yritys {

	public $id;

	public $nimi = '';
	public $sahkoposti = '';
	public $puhelin = '';
	public $y_tunnus = '';

	public $katuosoite = '';
	public $postinumero = '';
	public $postitoimipaikka = '';
	public $maa = '';

	public $rahtimaksu = 0.00;
	public $ilm_toim_sum_raja = 0.00;

	/**
	 * Yritys constructor.
	 * @param DByhteys $db
	 * @param $yritys_id
	 */
	function __construct ( DByhteys $db, /*int*/ $yritys_id ) {
		$this->id = $yritys_id;
		$sql_q = "SELECT id, nimi, sahkoposti, puhelin, y_tunnus, 
					katuosoite, postinumero, postitoimipaikka, maa, rahtimaksu, ilmainen_toimitus_summa_raja
				  FROM yritys
				  WHERE id = ?
				  LIMIT 1";
		$foo = $db->query( $sql_q, [$yritys_id] );

		$this->nimi = $foo->nimi;
		$this->sahkoposti = $foo->sahkoposti;
		$this->puhelin = $foo->puhelin;
		$this->y_tunnus = $foo->y_tunnus;

		$this->katuosoite = $foo->katuosoite;
		$this->postinumero = $foo->postinumero;
		$this->postitoimipaikka = $foo->postitoimipaikka;
		$this->maa = $foo->maa;

		$this->rahtimaksu = $foo->rahtimaksu;
		$this->ilmainen_toimitus_summa_raja = $foo->ilmainen_toimitus_summa_raja;
	}
}
