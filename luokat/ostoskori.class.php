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
	 * @var int[] <p> Ostoskorissa olevat tuotteet.
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
	function __construct ( DByhteys $db, /*int*/ $yritys_id, /*int*/ $cart_mode = 0 ) {
		if ( $yritys_id !== NULL ) {
			$this->yritys_id = $yritys_id;
			$this->hae_cart_id( $db, $yritys_id );
			$this->cart_mode = $cart_mode;
			switch ( $cart_mode ) {
				case -1:
					break; // Do nothing
				case 0 :
					$this->hae_ostoskorin_sisalto( $db, FALSE );
					break;
				case 1 :
					$this->hae_ostoskorin_sisalto( $db, TRUE );
					break;
			}
		}
	}


	/**
	 * Hakee yrityksen ostoskorin ID:n
	 * @param DByhteys $db
	 * @param $yritys_id <p> Ostoskorin omistaja
	 */
	private function hae_cart_id ( DByhteys $db, /*int*/ $yritys_id ) {
		$this->ostoskori_id =
			$db->query( "SELECT id FROM ostoskori WHERE yritys_id = ? LIMIT 1", [$yritys_id] )
			->id;
	}

    /**
     * Palauttaa ostoskorissa olevien tuotteiden määrän.
     * @return int tuotteiden maara
     */
    public function hae_tuotteiden_maara() {
        return $this->montako_tuotetta;
    }

    /**
     * Palauttaa ostoskorissa olevien tuotteiden kappalemäärän yhteensä.
     * @return int kaikkien tuotteiden kappalemäärä
     */
    public function hae_kaikkien_tuotteiden_kappalemaara() {
        return $this->montako_tuotetta_kpl_maara_yhteensa;
    }

        /**
	 * Hakee ostoskorissa olevat tuotteet tietokannasta lokaaliin arrayhin. Hakee vain ID:n ja kpl-maaran.
	 * @param DByhteys $db
	 * @param boolean $kaikki_tiedot <p> Haetaanko kaikki tiedot (tuotteet & kappalemäärä),
	 *        vai vain montako eri tuotetta ostoskorissa on ( COUNT(tuote_id) ja SUM(kpl_maara) ).
	 */
	public function hae_ostoskorin_sisalto ( DByhteys $db, /*bool*/ $kaikki_tiedot = FALSE ) {
		if ( !$kaikki_tiedot ) {
			$sql = "SELECT COUNT(tuote_id) AS count, SUM(kpl_maara) AS kpl_maara 
					FROM ostoskori_tuote 
					WHERE ostoskori_id = ?";
			$row = $db->query( $sql, [$this->ostoskori_id] );
			$this->montako_tuotetta = $row->count;
			$this->montako_tuotetta_kpl_maara_yhteensa = $row->kpl_maara;
		} else {
			$this->montako_tuotetta_kpl_maara_yhteensa = 0; // Varmuuden vuoksi nollataan
			$this->montako_tuotetta = 0; // Ditto
			$this->tuotteet = array();
			$sql = "SELECT tuote_id, kpl_maara
					FROM   ostoskori_tuote
					WHERE  ostoskori_id = ?";
			$db->prepare_stmt( $sql );
			$db->run_prepared_stmt( [$this->ostoskori_id] );
			$row = $db->get_next_row( );
			while ( $row ) { //TODO: Miksei vaan [$row->tuote_id][] = $row; ?
				$this->tuotteet[$row->tuote_id][] = $row->tuote_id;
				$this->tuotteet[$row->tuote_id][] = $row->kpl_maara;
				$this->montako_tuotetta_kpl_maara_yhteensa += $row->kpl_maara;
				$row = $db->get_next_row();
			}
			$this->montako_tuotetta = count($this->tuotteet);
			$this->cart_mode = 1;
		}
	}

	/**
	 * Lisää tuote tietokantaan. Jos tuote on jo ostoskorissa, niin päivittää uuden kpl-määrän.
	 * Päivittää lisäksi paikalliseen arrayhin uuden kpl-määrän.
	 * Huom. Jos kpl-määrä = 0, tuote poistetaan.
	 * @param DByhteys $db
	 * @param int $tuote_id <p> Lisättävän tuotteen ID tietokannassa
	 * @param int $kpl_maara <p> Montako tuotetta
	 * @return bool <p> Onnistuiko lisäys
	 */
	public function lisaa_tuote( DByhteys $db, /*int*/ $tuote_id, /*int*/ $kpl_maara ) {
		if ( $kpl_maara <= 0 ) { // Tarkistetaan kpl_maara == 0 varalle.
			return $this->poista_tuote( $db, $tuote_id ); // Oletetaan, että poistaminen oli tarkoitus.
		} // Jos vaikka joku ei ymmärrä mitä "poista_tuote"-metodi mahdollisesti tekee.
		$sql = "INSERT INTO ostoskori_tuote (ostoskori_id, tuote_id, kpl_maara)
 				VALUE ( ?, ?, ? )
 				ON DUPLICATE KEY UPDATE kpl_maara = ? ";
		$result = $db->query( $sql, [$this->ostoskori_id, $tuote_id, $kpl_maara, $kpl_maara]);

        $sql = "SELECT COUNT(*) AS tuotteet_kpl, SUM(kpl_maara) AS tuotteet_kpl_yhteensa FROM ostoskori_tuote WHERE ostoskori_id = ? ";
        $kappaleet = $db->query( $sql, [$this->ostoskori_id], NULL, PDO::FETCH_OBJ);
        $this->montako_tuotetta = $kappaleet->tuotteet_kpl;
        $this->montako_tuotetta_kpl_maara_yhteensa = $kappaleet->tuotteet_kpl_yhteensa;

		if ( $result && ($this->cart_mode == 1) ) { // Jos successful delete, ja tuotteet haettu olioon.
			$this->tuotteet[$tuote_id][1] = $kpl_maara; // Päivitetään lokaali kpl-määrä
		}

		return $result;
	}

	/**
	 * Poistaa tuotteen ostoskorista. Poistaa lisäksi paikallisesta arrayista.
	 * @param DByhteys $db
	 * @param $tuote_id <p> Poistettava tuote
	 * @return bool <p> Onnistuiko poisto
	 */
	public function poista_tuote( DByhteys $db, /*int*/ $tuote_id) {
		$sql = "DELETE FROM ostoskori_tuote
  				WHERE ostoskori_id = ? AND tuote_id = ? ";
		$result = $db->query( $sql, [$this->ostoskori_id, $tuote_id] );

		if ( $result && ($this->cart_mode == 1) ) { // Jos successful delete, ja tuotteet haettu olioon.
			unset( $this->tuotteet[$tuote_id] ); // Poistetaan tuote lokaalista arraysta
		}

		return $result;
	}

	/**
	 * Tyhjentaa ostoskorin.
	 * @param DByhteys $db
	 * @return bool <p> Onnistuiko tyhjennys
	 */
	public function tyhjenna_kori( DByhteys $db ) {
		return $db->query( "DELETE FROM ostoskori_tuote WHERE ostoskori_id = ?",
			[$this->ostoskori_id] );
	}

	/**
	 * Palauttaa, onko olio käytettävissä, eikä NULL.
	 * @return bool
	 */
	public function isValid () {
		return ( $this->ostoskori_id !== NULL );
	}
}
