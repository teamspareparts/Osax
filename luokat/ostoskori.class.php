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
	 */
	public $cart_mode = 0;

	/**
	 * Ostoskori constructor.
	 * @param int $yritys_id <p> Ostoskorin omistaja
	 * @param DByhteys &$db <p> Tietokantayhteys, for obvious reasons. (Ostoskori on DB:ssä)
	 * @param int $cart_mode <p> Mitä tietoja haetaan tuotteista:
	 * 		<ul><li>-1 : Älä hae mitään tietoja
	 * 			<li>0 : Hae vain montako eri tuotetta ostoskorissa on
	 * 			<li>1 : Hae kaikkien tuotteiden ID:t ja kpl-määrät
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
	 * @param $yritys_id
	 */
	private function hae_cart_id ( /*int*/ $yritys_id ) {
		$this->ostoskori_id =
			$this->db->query(
				"SELECT id FROM ostoskori WHERE yritys_id = ? LIMIT 1",
				[$yritys_id] ) ->id;
	}

	/**
	 * Hakee ostoskorissa olevat tuotteet tietokannasta lokaaliin arrayhin. Hakee vain ID:n ja kpl-maaran.
	 * @param boolean $kaikki_tiedot <p> Haetaanko kaikki tiedot (tuotteet & kappalemäärä),
	 * 		vai vain montako eri tuotetta ostoskorissa on ( COUNT(tuote_id) ja SUM(kpl_maara) ).
	 */
	public function hae_ostoskorin_sisalto ( /*boolean*/ $kaikki_tiedot = FALSE ) {
		if ( !$kaikki_tiedot ) {
			$row = $this->db->query(
				"SELECT COUNT(tuote_id) AS count, SUM(kpl_maara) AS kpl_maara FROM ostoskori_tuote WHERE ostoskori_id = ?",
				[$this->ostoskori_id] );
			$this->montako_tuotetta = $row->count;
			$this->montako_tuotetta_kpl_maara_yhteensa = $row->kpl_maara;
		} else {
			$this->tuotteet = array();
			$query = "	SELECT	tuote_id, kpl_maara
					FROM	ostoskori_tuote
					WHERE	ostoskori_id = ?";
			$this->db->prepare_stmt( $query );
			$this->db->run_prepared_stmt( [$this->ostoskori_id] );
			$row = $this->db->get_next_row( );
			while ( $row ) {
				$this->tuotteet[] = $row->tuote_id;
				$this->tuotteet[$row->tuote_id][] = $row->tuote_id;
				$this->tuotteet[$row->tuote_id][] = $row->kpl_maara;
				$this->montako_tuotetta_kpl_maara_yhteensa += $row->kpl_maara;
				$row = $this->db->get_next_row( );
			}
			$this->montako_tuotetta = count($this->tuotteet);
		}
	}

	/**
	 * @param int $tuote_id <p> Lisättävän tuotteen ID tietokannassa
	 * @param int $kpl_maara
	 * @return bool
	 */
	public function lisaa_tuote( /*int*/ $tuote_id, /*int*/ $kpl_maara ) {
		$query = "  INSERT INTO ostoskori_tuote (ostoskori_id, tuote_id, kpl_maara)
 					VALUE ( ?, ?, ? )
 					ON DUPLICATE KEY UPDATE kpl_maara=VALUES(kpl_maara)";
		return $this->db->query( $query, [$this->ostoskori_id, $tuote_id, $kpl_maara] );
	}

	/**
	 * @param $tuote_id <p> Poistettava tuote
	 * @return bool
	 */
	public function poista_tuote( /*int*/ $tuote_id) {
		$query = "  DELETE FROM ostoskori_tuote
  					WHERE ostoskori_id = ? AND tuote_id = ? ";
		return $this->db->query( $query, [$this->ostoskori_id, $tuote_id] );
	}
}
