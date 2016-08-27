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
	 */
	function __construct ( /*int*/ $yritys_id, DByhteys $db ) {
		$this->yritys_id = $yritys_id;
		$this->db = $db;
		$this->hae_cart_id( $yritys_id );
		$this->hae_ostoskorin_sisalto();
	}

	/**
	 * @param $yritys_id
	 * @return bool
	 */
	private function hae_cart_id ( /*int*/ $yritys_id ) {
		$row = $this->db->query( "SELECT EXISTS(SELECT 1 FROM ostoskori WHERE id = ? LIMIT 1)",
			[$yritys_id]);
		if ( $row ) {
			$this->db->query( "INSERT INTO ostoskori (yritys_id) VALUES ( ? )",
				[$yritys_id])['id']; //Koska se on array, ja id on indeksillä 0.
		} else
		return true;
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
