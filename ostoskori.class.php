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
		$row = $this->db->query(
			"SELECT id FROM ostoskori WHERE yritys_id = ?",
			[$yritys_id]
		);
		$this->ostoskori_id = $row[0];
		$this->hae_ostoskori();
	}

	private function hae_ostoskori () {
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
	 * @return bool
	 */
	public function lisaa_tuote( /*int*/ $tuote_id, /*int*/ $kpl_maara ) {
		$query = "  INSERT INTO ostoskori_tuote (ostoskori_id, tuote_id, kpl_maara)
 					VALUE ( ?, ?, ? ) ";
		return $this->db->query( $query, [$this->ostoskori_id, $tuote_id, $kpl_maara] );
	}

	public function poista_tuote() {
		//Poista tuote ostoskorista tässä
	}

	public function onko_tuote_ostoskorissa() {
		//Tarkastaa onko tuote jo lisätty ostoskoriin.
		// Tosin voihan sen tehdä ON DUPLICATE KEY UPDATE:lla.
		// Keksin tässä vain metodeja tyhjästä.
	}
}
