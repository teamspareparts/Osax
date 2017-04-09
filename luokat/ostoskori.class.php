<?php
/**
 * Class Ostoskori <p>
 * Sivuston yrityksen yhteisen ostoskorin toiminnan hallintaa varten.
 * @version 2017-02-06
 */
class Ostoskori {
	/**
	 * <code>
	 * Array [
	 *    tuote_id => [ tuotteen id,
	 *                  kpl-määrä ], ...
	 * ]
	 * </code>
	 * @var Tuote[] <p> Ostoskorissa olevat tuotteet.
	 */
	public $tuotteet = null;
	/**
	 * @var int $montako_tuotetta <p> Montako eri tuotetta ostoskorissa on.
	 */
	public $montako_tuotetta = 0;
	/**
	 * @var int $montako_tuotetta_kpl_maara_yhteensa <p> Montako kappaletta eri tuotteita on yhteensä ostoskorissa.
	 */
	public $montako_tuotetta_kpl_maara_yhteensa = 0;
	/**
	 * @var int $summa_yhteensa <p> Kaikkien tuotteiden yhteenlaskettu summa. Vain käytössä ostoskori-sivulla..
	 */
	public $summa_yhteensa = 0;
	/**
	 * @var int <p> Ostoskorin omistavan yrityksen ID.
	 */
	private $yritys_id = null;
	/**
	 * @var int <p> Ostoskorin ID tietokannassa.
	 */
	private $ostoskori_id = null;
	/**
	 * @var int <p> Mitkä tiedot haettu. Sama kuin konstruktorin $cart_mode, mutta pysyvään
	 * tallenukseen.
	 * <ul><li>-1 : Älä hae mitään tietoja
	 *        <li> 0 : Hae vain montako eri tuotetta ostoskorissa on
	 *        <li> 1 : Hae kaikkien tuotteiden ID:t ja kpl-määrät</ul>
	 */
	public $cart_mode = 0;

	/**
	 * Ostoskori constructor.
	 * @param int      $yritys_id <p> Ostoskorin omistaja
	 * @param DByhteys &$db       <p> Tietokantayhteys, for obvious reasons. (Ostoskori on DB:ssä)
	 * @param int      $cart_mode [optional] <p> Mitä tietoja haetaan tuotteista:
	 *                            <ul><li>-1 : Älä hae mitään tietoja
	 *                            <li> 0 : Hae vain montako eri tuotetta ostoskorissa on
	 *                            <li> 1 : Hae kaikkien tuotteiden ID:t ja kpl-määrät
	 *                            </ul>
	 */
	function __construct ( DByhteys $db, /*int*/ $yritys_id, /*int*/ $cart_mode = 0 ) {
		if ( $yritys_id !== null ) {
			$this->yritys_id = $yritys_id;
			$this->hae_cart_id( $db, $yritys_id );
			$this->cart_mode = $cart_mode;
			switch ( $cart_mode ) {
				case -1:
					break; // Do nothing
				case 0 :
					$this->hae_ostoskorin_sisalto( $db, false );
					break;
				case 1 :
					$this->hae_ostoskorin_sisalto( $db, true );
					break;
				case 2 :
					$this->hae_ostoskorin_sisalto( $db, true, true );
					break;
			}
		}
	}

	/**
	 * Hakee yrityksen ostoskorin ID:n
	 * @param DByhteys $db
	 * @param int      $yritys_id <p> Ostoskorin omistaja
	 */
	private function hae_cart_id ( DByhteys $db, /*int*/ $yritys_id ) {
		$this->ostoskori_id = $db->query( "SELECT id FROM ostoskori WHERE yritys_id = ? LIMIT 1", [ $yritys_id ] )->id;
	}

	/**
	 * Hakee ostoskorissa olevat tuotteet tietokannasta lokaaliin arrayhin.
	 * @param DByhteys $db
	 * @param boolean  $kaikki_tiedot [optional]<p> Haetaanko kaikki tiedot (tuotteet & kappalemäärä),
	 *                                vai vain montako eri tuotetta ostoskorissa on ja kpl-määrä yhteensä.
	 * @param bool     $tuote_luokka  [optional]<p> Haetaanko tuotteet Tuote-luokkaan. Hitaampi vaihtoehto.
	 */
	public function hae_ostoskorin_sisalto( DByhteys $db, /*bool*/ $kaikki_tiedot = false,
											/*bool*/ $tuote_luokka = false ) {
		$this->montako_tuotetta_kpl_maara_yhteensa = 0; // Varmuuden vuoksi nollataan
		$this->montako_tuotetta = 0; // Ditto
		$this->tuotteet = array();

		if ( !$kaikki_tiedot ) {
			$sql = "SELECT COUNT(tuote_id) AS count, IFNULL(SUM(kpl_maara), 0) AS kpl_maara 
					FROM ostoskori_tuote WHERE ostoskori_id = ?";
			$row = $db->query( $sql, [ $this->ostoskori_id ] );
			$this->montako_tuotetta = $row->count;
			$this->montako_tuotetta_kpl_maara_yhteensa = $row->kpl_maara;
		}
		else { // Hae kaikki tiedot
			if ( !$tuote_luokka ) {
				$this->tuotteet = array();
				$sql = "SELECT tuote_id, kpl_maara FROM ostoskori_tuote WHERE ostoskori_id = ?";
				$db->prepare_stmt( $sql );
				$db->run_prepared_stmt( [ $this->ostoskori_id ] );
				while ( $row = $db->get_next_row() ) {
					$this->tuotteet[ $row->tuote_id ] = $row;
					$this->montako_tuotetta_kpl_maara_yhteensa += $row->kpl_maara;
				}
				$this->montako_tuotetta = count( $this->tuotteet );
				$this->cart_mode = 1;
			}
			else { // Käytä Tuote-luokkaa
				$sql = "SELECT tuote.id, tuote.tuotekoodi, tuote.valmistaja, tuote.nimi,
							tuote.varastosaldo, tuote.minimimyyntiera, ALV_kanta.prosentti AS alv_prosentti,
							(tuote.hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS a_hinta,
							(tuote.hinta_ilman_alv) AS a_hinta_ilman_alv, kpl_maara, tuoteryhma, hyllypaikka
						FROM ostoskori_tuote
						LEFT JOIN tuote ON tuote.id = ostoskori_tuote.tuote_id
						LEFT JOIN ALV_kanta ON tuote.ALV_kanta = ALV_kanta.kanta
						WHERE ostoskori_id = ?";
				$db->prepare_stmt( $sql );
				$db->run_prepared_stmt( [ $this->ostoskori_id ] );
				/** @var $row Tuote */
				while ( $row = $db->get_next_row( null, 'tuote' ) ) {
					$row->haeAlennukset( $db, $this->yritys_id );
					$this->tuotteet[] = $row;
					$this->montako_tuotetta_kpl_maara_yhteensa += $row->kpl_maara;
					$this->montako_tuotetta += 1;
				}
			}
		}
	}

	/**
	 * Lisää tuote tietokantaan. Jos tuote on jo ostoskorissa, niin päivittää uuden kpl-määrän.
	 * Päivittää lisäksi paikalliseen arrayhin uuden kpl-määrän.
	 * Huom. Jos kpl-määrä = 0, tuote poistetaan.
	 * @param DByhteys $db
	 * @param int      $tuote_id  <p> Lisättävän tuotteen ID tietokannassa
	 * @param int      $kpl_maara <p> Montako tuotetta
	 * @return bool <p> Onnistuiko lisäys
	 */
	public function lisaa_tuote( DByhteys $db, /*int*/ $tuote_id, /*int*/ $kpl_maara ) {
		// Tarkistetaan kpl_maara == 0 varalle.
		if ( $kpl_maara <= 0 ) {
			return $this->poista_tuote( $db, $tuote_id ); // Oletetaan, että poistaminen oli tarkoitus.
		} // Jos vaikka joku ei ymmärrä mitä "poista_tuote"-metodi mahdollisesti tekee.

		$sql = "INSERT INTO ostoskori_tuote (ostoskori_id, tuote_id, kpl_maara) VALUE ( ?, ?, ? )
 				ON DUPLICATE KEY UPDATE kpl_maara = VALUES(kpl_maara)";
		$result = $db->query( $sql, [ $this->ostoskori_id, $tuote_id, $kpl_maara ] );

		$this->hae_ostoskorin_sisalto( $db, false );

		if ( $result && ($this->cart_mode == 1) ) { // Jos successful delete, ja tuotteet haettu olioon.
			$this->tuotteet[ $tuote_id ][ 1 ] = $kpl_maara; // Päivitetään lokaali kpl-määrä
		}

		return $result;
	}

	/**
	 * Poistaa tuotteen ostoskorista. Poistaa lisäksi paikallisesta arrayista.
	 * @param DByhteys $db
	 * @param int      $tuote_id <p> Poistettava tuote
	 * @return bool <p> Onnistuiko poisto
	 */
	public function poista_tuote( DByhteys $db, /*int*/ $tuote_id) {
		$sql = "DELETE FROM ostoskori_tuote
  				WHERE ostoskori_id = ? AND tuote_id = ? ";
		$result = $db->query( $sql, [ $this->ostoskori_id, $tuote_id ] );

		if ( $result && ($this->cart_mode == 1) ) { // Jos successful delete, ja tuotteet haettu olioon.
			unset( $this->tuotteet[ $tuote_id ] ); // Poistetaan tuote lokaalista arraysta
		}

		return $result;
	}

	/**
	 * Tyhjentaa ostoskorin.
	 * @param DByhteys $db
	 * @return bool <p> Onnistuiko tyhjennys
	 */
	public function tyhjenna_kori( DByhteys $db ) {
		return $db->query( "DELETE FROM ostoskori_tuote WHERE ostoskori_id = ?", [ $this->ostoskori_id ] );
	}

	/**
	 * Palauttaa, onko olio käytettävissä, eikä NULL.
	 * @return bool
	 */
	public function isValid() {
		return ($this->ostoskori_id !== null);
	}

	/**
	 * Palauttaa ostoskorissa olevien tuotteiden määrän.
	 * @return int tuotteiden maara
	 * @deprecated Minä juuri päätin, että me katsomme suoraan luokan muuttujan.
	 */
	public function get_tuotteiden_maara() {
		return $this->montako_tuotetta;
	}

	/**
	 * Palauttaa ostoskorissa olevien tuotteiden kappalemäärän yhteensä.
	 * @return int kaikkien tuotteiden kappalemäärä
	 * @deprecated Minä juuri päätin, että me katsomme suoraan luokan muuttujan.
	 */
	public function get_kaikkien_tuotteiden_kappalemaara() {
		return $this->montako_tuotetta_kpl_maara_yhteensa;
	}

	/**
	 * @param boolean $ilman_alv  [optional] default=false <p> Tulostetaanko hinta ilman ALV:ta.
	 * @param boolean $ilman_euro [optional] default=false <p> Tulostetaanko hinta ilman €-merkkiä.
	 * @return string
	 */
	function summa_toString ( /*bool*/ $ilman_euro = false ) {
		return number_format( (double)$this->summa_yhteensa, 2, ',', '.' ) . ($ilman_euro ? '' : ' &euro;');
	}
}
