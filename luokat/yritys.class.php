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

	/**
	 * Yritys constructor. <p>
	 * Hakee yrityksen tiedot tietokannasta.
	 * Jos 1. parametri on NULL, niin ei tee mitään. Jos ei löydä yritystä ID:llä, niin kaikki
	 *  olion arvot pysyvät default arvoissaan. Testaa, löytyikö yritys metodilla .isValid().
	 * @param DByhteys $db
	 * @param int $yritys_id
	 */
	function __construct ( DByhteys $db, /*int*/ $yritys_id ) {
		if ( $yritys_id !== NULL ) { // Varmistetaan parametrin oikeellisuus
			$sql = "SELECT id, aktiivinen, nimi, sahkoposti, puhelin, y_tunnus, 
						katuosoite, postinumero, postitoimipaikka, maa, rahtimaksu, ilmainen_toimitus_summa_raja
					FROM yritys
					WHERE id = ?
					LIMIT 1";
			$foo = $db->query( $sql, [$yritys_id] );

			if ( $foo ) { // Varmistetaan, että jokin yritys löytyi
				$this->id 			= $foo->id;
                $this->aktiivinen   = $foo->aktiivinen;
				$this->nimi			= $foo->nimi;
				$this->sahkoposti	= $foo->sahkoposti;
				$this->puhelin		= $foo->puhelin;
				$this->y_tunnus		= $foo->y_tunnus;

				$this->katuosoite	= $foo->katuosoite;
				$this->postinumero	= $foo->postinumero;
				$this->postitoimipaikka = $foo->postitoimipaikka;
				$this->maa			= $foo->maa;

				$this->rahtimaksu	= $foo->rahtimaksu;
				$this->ilm_toim_sum_raja = $foo->ilmainen_toimitus_summa_raja;
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
