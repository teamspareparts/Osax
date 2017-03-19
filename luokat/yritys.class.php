<?php

/**
 * Class Yritys
 *
 * @version 2017-02-06
 */
class Yritys {

	public $id = null;
	public $aktiivinen = null;

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
	/** @var int <p> Käyttäjän sallitut maksutavat. <br>0: Paytrail, 1: lasku 14pv, >1: 501 HTTP */
	public $maksutapa = 0;

	/**
	 * Yritys-luokan konstruktori.<p>
	 * Jos annettu parametrit, hakee yrityksen tiedot tietokannasta. Muuten ei tee mitään.
	 * Jos ei löydä yritystä ID:llä, niin kaikki olion arvot pysyvät default arvoissaan.
	 * Testaa, löytyikö yritys metodilla <code>->isValid()</code>.
	 * @param DByhteys $db        [optional]
	 * @param int      $yritys_id [optional]
	 */
	function __construct( DByhteys $db = null, /*int*/ $yritys_id = null ) {
		if ( $yritys_id !== null ) { // Varmistetaan parametrin oikeellisuus
			$sql = "SELECT id, aktiivinen, nimi, sahkoposti, puhelin, y_tunnus, katuosoite, postinumero, 
						postitoimipaikka, maa, rahtimaksu, ilmainen_toimitus_summa_raja AS ilm_toim_sum_raja,
						alennus_prosentti AS yleinen_alennus, maksutapa
					FROM yritys 
					WHERE yritys.id = ? 
					LIMIT 1";
			$row = $db->query( $sql, [ $yritys_id ] );

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
	public function isValid() {
		return (($this->id !== null) && ($this->aktiivinen != 0));
	}
}
