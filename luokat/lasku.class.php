<?php declare(strict_types=1);

/**
 * Class Lasku extends Tilaus
 */
class Lasku extends Tilaus {

	public $laskuHeader = null;

	/** @var Yritys */
	public $osax = null;


	/**
	 * @param DByhteys $db
	 * @param int      $tilaus_id
	 * @param int      $indev
	 */
	function __construct( DByhteys $db, int $tilaus_id = null, $indev = 1 ) {

		parent::__construct( $db, $tilaus_id );

		$this->osax = new Yritys( $db, 1 );

		$this->laskuHeader = ( $indev )
			? "<h2 style='color: red;'>InDev testilasku</h2>"
			: "<img src='../img/osax_logo.jpg' alt='Osax.fi'>";

	}

	function luoLaskunNumero( DByhteys $db ) {
		if ( $this->laskunro === null ) {
			$sql = "SELECT laskunro FROM laskunumero LIMIT 1";
			$row = $this->db->query( $sql );
			$this->laskunro = $row->laskunro;
			$sql = "UPDATE laskunumero SET laskunro = laskunro + 1 LIMIT 1";
			$this->db->query( $sql );
			$sql = "UPDATE tilaus SET laskunro = ? WHERE id = ? LIMIT 1";
			$this->db->query( $sql, [ $this->laskunro, $this->tilaus_nro ] );
		}
	}

	/**
	 * @param bool $alku <p> Onko tulostus laskun alkuun vai loppuun? Lopussa pidempi teksti.
	 * @return string
	 */
	function maksutapa_toString ( bool $alku = true ) {
		return ($this->maksutapa)
			? ( $alku ? "Lasku 14 pv." : "! Maksetaan laskulla &mdash; maksuaika 14 päivää !" )
			: ( $alku ? "e-korttimaksu" : "! Maksettu korttiveloituksena tilausta tehdessä !" );
	}
}
