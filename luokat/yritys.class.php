<?php

/**
 * Class Yritys
 */
class Yritys {

	public $id = NULL;
    public $aktiivinen = NULL;

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
	public $yleinen_alennus = 0.00;

	/**
	 * Yritys-luokan konstruktori.<p>
	 * Jos annettu parametrit, hakee yrityksen tiedot tietokannasta. Muuten ei tee mitään.
	 * Jos ei löydä yritystä ID:llä, niin kaikki olion arvot pysyvät default arvoissaan.
	 * Testaa, löytyikö yritys metodilla .isValid().
	 * @param DByhteys $db [optional]
	 * @param int $yritys_id [optional]
	 */
	function __construct ( DByhteys $db = NULL, /*int*/ $yritys_id = NULL ) {
		if ( $yritys_id !== NULL ) { // Varmistetaan parametrin oikeellisuus
			$sql = "SELECT id, aktiivinen, nimi, sahkoposti, puhelin, y_tunnus, katuosoite, postinumero, 
						postitoimipaikka, maa, rahtimaksu, ilmainen_toimitus_summa_raja AS ilm_toim_sum_raja,
						alennus_prosentti AS yleinen_alennus
					FROM yritys 
					WHERE yritys.id = ? 
					LIMIT 1";
			$row = $db->query( $sql, [$yritys_id] );

			if ( $row ) { // Varmistetaan, että jokin asiakas löytyi
				foreach ( $row as $property => $propertyValue ) {
					$this->{$property} = $propertyValue;
				}
			}
		}
	}

	/**
	 * Palauttaa, onko olio käytettävissä, eikä NULL.
	 * @return bool
	 */
	public function isValid () {
		return ( ($this->id !== NULL ) && ($this->aktiivinen != 0) );
	}
}
