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
	public $tuotteet = array();

	/**
	 * @var int $montako_tuotetta <p> Montako eri tuotetta ostoskorissa on.
	 */
	public $montako_tuotetta = 0;

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
	 * Ostoskori constructor.
	 * @param int $yritys_id <p> Ostoskorin omistaja
	 * @param DByhteys $db <p> Tietokantayhteys, for obvious reasons. (Ostoskori on DB:ssä)
	 * @param int $cart_mode <p> Mitä tietoja haetaan tuotteista:
	 * 		<ul><li>-1 : Älä hae mitään tietoja
	 * 			<li>0 : Hae vain montako eri tuotetta ostoskorissa on
	 * 			<li>1 : Hae kaikkien tuotteiden ID:t ja kpl-määrät
	 * 		</ul>
	 */
	function __construct ( /*int*/ $yritys_id, DByhteys $db, /*int*/ $cart_mode = 0 ) {
		$this->yritys_id = $yritys_id;
		$this->db = $db;
		$this->hae_cart_id( $yritys_id );
		switch ( $cart_mode ) {
			case -1:
				break; // Do nothing
			case 0 :
				$this->fetchNumberOfDifferentProductsInCart();
				break;
			case 1 :
				$this->hae_ostoskorin_sisalto();
				break;
		}
		$this->hae_ostoskorin_sisalto();
	}

	/**
	 * @param $yritys_id
	 */
	private function hae_cart_id ( /*int*/ $yritys_id ) {
		$this->ostoskori_id =
			$this->db->query( "SELECT id FROM ostoskori WHERE yritys_id = ? LIMIT 1",
			[$yritys_id] );
	}

	/**
	 * Look, tämä metodi oli varsin vaikea nimetä. Jos keksit paremman nimen, niin siitä vaan.
	 */
	private function fetchNumberOfDifferentProductsInCart () {
		$this->montako_tuotetta = $this->db->query( "	
			SELECT COUNT(tuote_id) AS count FROM ostoskori_tuote WHERE ostoskori_id = ?",
			[$this->ostoskori_id] )['count'];
	}

	/**
	 * Hakee ostoskorissa olevat tuotteet tietokannasta lokaaliin arrayhin. Hakee vain ID:n ja kpl-maaran.
	 */
	private function hae_ostoskorin_sisalto () {
		$query = "	SELECT	tuote_id, kpl_maara
					FROM	ostoskori_tuote
					WHERE	ostoskori_id = ?";
		$this->db->prepare_stmt( $query );
		$this->db->run_prepared_stmt( [$this->ostoskori_id] );
		$row = $this->db->get_next_row( PDO::FETCH_OBJ );
		while ( $row ) {
			$this->tuotteet[] = $row->tuote_id;
			$this->tuotteet[$row->tuote_id][] = $row->tuote_id;
			$this->tuotteet[$row->tuote_id][] = $row->kpl_maara;
		}
	$this->montako_tuotetta = count($this->tuotteet);
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
