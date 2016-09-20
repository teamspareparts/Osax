<?php
/**
 * Class Ostoskori <p>
 * Sivuston yrityksen yhteisen ostoskorin toiminnan hallintaa varten.
 */
class Ostoskori {
	/**
	 * <code>
	 * Array [
	 * 		tuote_id => [ tuotteen id,
	 * 					  kpl-määrä ], ...
	 * ]
	 * </code>
	 * Indesit kokonaislukuja (0 ja 1)
	 * @var array <p> Ostoskorissa olevat tuotteet.
	 */
	public $tuotteet = NULL;

	/**
	 * @var int $montako_tuotetta <p> Montako eri tuotetta ostoskorissa on.
	 */
	public $montako_tuotetta = 0;

	/**
	 * @var int $montako_tuotetta_kpl_maara_yhteensa <p> Montako kappaletta eri tuotteita on yhteensä ostoskorissa.
	 */
	public $montako_tuotetta_kpl_maara_yhteensa = 0;

	/**
	 * @var int <p> Ostoskorin omistavan yrityksen ID.
	 */
	private $yritys_id = NULL;

	/**
	 * @var int <p> Ostoskorin ID tietokannassa.
	 */
	private $ostoskori_id = NULL;

	/**
	 * @var DByhteys <p> Tietokantayhteys ostoskorin toimintaa varten.
	 */
	private $db = NULL;

	/**
	 * @var int <p> Mitkä tiedot haettu. Sama kuin konstruktorin $cart_mode, mutta pysyvään
	 * tallenukseen.
	 * <ul><li>-1 : Älä hae mitään tietoja
	 * 		<li> 0 : Hae vain montako eri tuotetta ostoskorissa on
	 * 		<li> 1 : Hae kaikkien tuotteiden ID:t ja kpl-määrät</ul>
	 */
	public $cart_mode = 0;

	/**
	 * Ostoskori constructor.
	 * @param int $yritys_id <p> Ostoskorin omistaja
	 * @param DByhteys &$db <p> Tietokantayhteys, for obvious reasons. (Ostoskori on DB:ssä)
	 * @param int $cart_mode <p> Mitä tietoja haetaan tuotteista:
	 * 		<ul><li>-1 : Älä hae mitään tietoja
	 * 			<li> 0 : Hae vain montako eri tuotetta ostoskorissa on
	 * 			<li> 1 : Hae kaikkien tuotteiden ID:t ja kpl-määrät
	 * 		</ul>
	 */
	function __construct ( DByhteys &$db, /*int*/ $yritys_id, /*int*/ $cart_mode = 0 ) {
		$this->yritys_id = $yritys_id;
		$this->db = $db;
		$this->hae_cart_id( $yritys_id );
		$this->cart_mode = $cart_mode;
		switch ( $cart_mode ) {
			case -1:
				break; // Do nothing
			case 0 :
				$this->hae_ostoskorin_sisalto( FALSE );
				break;
			case 1 :
				$this->hae_ostoskorin_sisalto( TRUE );
				break;
		}
	}

	/**
	 * TODO: PhpDoc
	 * @param $yritys_id
	 */
	private function hae_cart_id ( /*int*/ $yritys_id ) {
		$this->ostoskori_id =
			$this->db->query( "SELECT id FROM ostoskori WHERE yritys_id = ? LIMIT 1", [$yritys_id] )
				->id;
	}

	/**
	 * Hakee ostoskorissa olevat tuotteet tietokannasta lokaaliin arrayhin. Hakee vain ID:n ja kpl-maaran.
	 * @param boolean $kaikki_tiedot <p> Haetaanko kaikki tiedot (tuotteet & kappalemäärä),
	 * 		vai vain montako eri tuotetta ostoskorissa on ( COUNT(tuote_id) ja SUM(kpl_maara) ).
	 */
	public function hae_ostoskorin_sisalto ( /*bool*/ $kaikki_tiedot = FALSE ) {
		if ( !$kaikki_tiedot ) {
			$sql = "SELECT COUNT(tuote_id) AS count, SUM(kpl_maara) AS kpl_maara 
					FROM ostoskori_tuote 
					WHERE ostoskori_id = ?";
			$row = $this->db->query( $sql, [$this->ostoskori_id] );
			$this->montako_tuotetta = $row->count;
			$this->montako_tuotetta_kpl_maara_yhteensa = $row->kpl_maara;
		} else {
			$this->montako_tuotetta_kpl_maara_yhteensa = 0; // Varmuuden vuoksi nollataan
			$this->montako_tuotetta = 0; // Varmuuden vuoksi nollataan
			$this->tuotteet = array();
			$sql = "SELECT tuote_id, kpl_maara
					FROM   ostoskori_tuote
					WHERE  ostoskori_id = ?";
			$this->db->prepare_stmt( $sql );
			$this->db->run_prepared_stmt( [$this->ostoskori_id] );
			$row = $this->db->get_next_row( );
			while ( $row ) {
				$this->tuotteet[$row->tuote_id][] = $row->tuote_id;
				$this->tuotteet[$row->tuote_id][] = $row->kpl_maara;
				$this->montako_tuotetta_kpl_maara_yhteensa += $row->kpl_maara;
				$row = $this->db->get_next_row();
			}
			$this->montako_tuotetta = count($this->tuotteet);
			$this->cart_mode = 1;
		}
	}

	/**
	 * TODO: PhpDoc
	 * @param int $tuote_id <p> Lisättävän tuotteen ID tietokannassa
	 * @param int $kpl_maara
	 * @return bool
	 */
	public function lisaa_tuote( /*int*/ $tuote_id, /*int*/ $kpl_maara ) {
		$sql = "INSERT INTO ostoskori_tuote (ostoskori_id, tuote_id, kpl_maara)
 				VALUE ( ?, ?, ? )
 				ON DUPLICATE KEY UPDATE kpl_maara=VALUES(kpl_maara)";
		$result = $this->db->query( $sql, [$this->ostoskori_id, $tuote_id, $kpl_maara] );

		if ( $result && ($this->cart_mode == 1) ) { // Jos successful delete, ja tuotteet haettu olioon.
			$this->tuotteet[$tuote_id][1] = $kpl_maara; // Päivitetään lokaali kpl-määrä
		}

		return $result;
	}

	/**
	 * TODO: PhpDoc
	 * @param $tuote_id <p> Poistettava tuote
	 * @return bool
	 */
	public function poista_tuote( /*int*/ $tuote_id) {
		$sql = "DELETE FROM ostoskori_tuote
  				WHERE ostoskori_id = ? AND tuote_id = ? ";
		$result = $this->db->query( $sql, [$this->ostoskori_id, $tuote_id] );

		if ( $result && ($this->cart_mode == 1) ) { // Jos successful delete, ja tuotteet haettu olioon.
			unset( $this->tuotteet[$tuote_id] ); // Poistetaan tuote lokaalista arraysta
		}

		return $result;
	}
}
