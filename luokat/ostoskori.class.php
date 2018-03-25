<?php declare(strict_types=1);
/**
 * Class Ostoskori <p>
 * Sivuston yrityksen yhteisen ostoskorin toiminnan hallintaa varten.
 * @version 2017-04-09
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
	 * @var float $summa_yhteensa <p> Kaikkien tuotteiden yhteenlaskettu summa. Vain käytössä ostoskori-sivulla.
	 */
	public $summa_yhteensa = 0;
	/**
	 * @var int <p> Ostoskorin omistavan yrityksen ID.
	 */
	public $yritys_id = null;
	/**
	 * @var int <p> Ostoskorin ID tietokannassa.
	 * @deprecated Don't use. Käytä ->id muuttujaa sen sijaan
	 */
	public $ostoskori_id = null;
	/**
	 * @var int <p> Ostoskorin ID tietokannassa.
	 */
	public $id = null;
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
	 *                            <ul><li>-1 : Älä hae mitään tietoja (paitsi ID)
	 *                            <li> 0 : Hae vain montako eri tuotetta ostoskorissa on
	 *                            <li> 1 : Hae kaikkien tuotteiden ID:t ja kpl-määrät
	 *                            </ul>
	 */
	function __construct ( DByhteys $db, int $yritys_id, int $cart_mode = 0 ) {
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
	private function hae_cart_id ( DByhteys $db, int $yritys_id ) {
		$this->ostoskori_id = (int)$db->query( "SELECT id FROM ostoskori WHERE yritys_id = ? LIMIT 1", [ $yritys_id ] )->id;
		$this->id = $this->ostoskori_id;
	}

	/**
	 * Hakee ostoskorissa olevat tuotteet tietokannasta lokaaliin arrayhin.
	 * @param DByhteys $db
	 * @param boolean  $kaikki_tiedot [optional]<p> Haetaanko kaikki tiedot (tuotteet & kappalemäärä),
	 *                                vai vain montako eri tuotetta ostoskorissa on ja kpl-määrä yhteensä.
	 * @param bool     $tuote_luokka  [optional]<p> Haetaanko tuotteet Tuote-luokkaan. Hitaampi vaihtoehto.
	 */
	public function hae_ostoskorin_sisalto( DByhteys $db, bool $kaikki_tiedot = false,
											bool $tuote_luokka = false ) {
		$this->montako_tuotetta_kpl_maara_yhteensa = 0; // Varmuuden vuoksi nollataan
		$this->montako_tuotetta = 0; // Ditto
		$this->tuotteet = array();

		if ( !$kaikki_tiedot ) {
			$sql = "SELECT COUNT(tuote_id) AS count, IFNULL(SUM(kpl_maara), 0) AS kpl_maara 
					FROM ostoskori_tuote WHERE ostoskori_id = ?";
			$row = $db->query( $sql, [ $this->id ] );
			$this->montako_tuotetta = (int)$row->count;
			$this->montako_tuotetta_kpl_maara_yhteensa = (int)$row->kpl_maara;
		}
		else { // Hae kaikki tiedot
			if ( !$tuote_luokka ) {
				$this->tuotteet = array();
				$sql = "SELECT tuote_id, kpl_maara, tilaustuote FROM ostoskori_tuote WHERE ostoskori_id = ?";
				$db->prepare_stmt( $sql );
				$db->run_prepared_stmt( [ $this->id ] );
				while ( $row = $db->get_next_row() ) {
					$this->tuotteet[ $row->tuote_id ] = $row;
					$this->montako_tuotetta_kpl_maara_yhteensa += (int)$row->kpl_maara;
				}
				$this->montako_tuotetta = count( $this->tuotteet );
				$this->cart_mode = 1;
			}
			else { // Käytä Tuote-luokkaa
				$sql = "SELECT tuote.id, tuote.articleNo, tuote.brandNo, tuote.hankintapaikka_id,
							tuote.tuotekoodi, tuote.valmistaja, tuote.nimi,
							tuote.varastosaldo, tuote.minimimyyntiera, tuote.hyllypaikka,
							ALV_kanta.prosentti AS alv_prosentti,
							(tuote.hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS a_hinta,
							(tuote.hinta_ilman_alv) AS a_hinta_ilman_alv, kpl_maara, tilaustuote
						FROM ostoskori_tuote
						LEFT JOIN tuote ON tuote.id = ostoskori_tuote.tuote_id
						LEFT JOIN ALV_kanta ON tuote.ALV_kanta = ALV_kanta.kanta
						WHERE ostoskori_id = ?";
				$db->prepare_stmt( $sql );
				$db->run_prepared_stmt( [ $this->id ] );
				/** @var $row Tuote */
				while ( $row = $db->get_next_row( null, 'Tuote' ) ) {
					$row->haeTuoteryhmat( $db, false );
					$this->haeAlennukset( $db, $this->yritys_id, $row );
					$this->tuotteet[] = $row;
					$this->montako_tuotetta_kpl_maara_yhteensa += (int)$row->kpl_maara;
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
	 * @param bool     $tilaustuote <p> Tilataanko tuote suoraan tehtaalta
	 * @return int <p> Montako tuotetta lisätty tietokantaan (pitäisi olla yksi)
	 */
	public function lisaa_tuote( DByhteys $db, int $tuote_id, int $kpl_maara, bool $tilaustuote = false ) : int {
		// Tarkistetaan kpl_maara == 0 varalle.
		if ( $kpl_maara <= 0 ) {
			return $this->poista_tuote( $db, $tuote_id ); // Oletetaan, että poistaminen oli tarkoitus.
		} // Jos vaikka joku ei ymmärrä mitä "poista_tuote"-metodi mahdollisesti tekee.

		$sql = "INSERT INTO ostoskori_tuote (ostoskori_id, tuote_id, kpl_maara, tilaustuote) VALUE ( ?, ?, ?, ? )
 				ON DUPLICATE KEY UPDATE kpl_maara = VALUES(kpl_maara), tilaustuote = VALUES(tilaustuote)";
		$result = $db->query( $sql, [ $this->id, $tuote_id, $kpl_maara, $tilaustuote ] );

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
	 * @return int <p> Montako tuotetta poistettu tietokannasta (pitäisi olla yksi)
	 */
	public function poista_tuote( DByhteys $db, int $tuote_id) : int {
		$sql = "DELETE FROM ostoskori_tuote
  				WHERE ostoskori_id = ? AND tuote_id = ? ";
		$result = $db->query( $sql, [ $this->id, $tuote_id ] );

		if ( $result && ($this->cart_mode == 1) ) { // Jos successful delete, ja tuotteet haettu olioon.
			unset( $this->tuotteet[ $tuote_id ] ); // Poistetaan tuote lokaalista arraysta
		}

		return $result;
	}

	/**
	 * Tyhjentaa ostoskorin.
	 * @param DByhteys $db
	 * @return int <p> Montako tuotetta poistettu tietokannasta (pitäisi olla kaikki)
	 */
	public function tyhjenna_kori( DByhteys $db ) : int {
		return $db->query( "DELETE FROM ostoskori_tuote WHERE ostoskori_id = ?", [ $this->id ] );
	}

	/**
	 * @param DByhteys $db
	 * @param int      $yritys_id
	 * @param Tuote    $tuote
	 */
	function haeAlennukset ( DByhteys $db, int $yritys_id, Tuote $tuote ) {
		/*
		 * Tuotteiden normaalit määräalennukset.
		 */
		$sql = "SELECT maaraalennus_kpl, alennus_prosentti, alkuPvm, loppuPvm
				FROM tuote_erikoishinta
				WHERE tuote_id = ?
					AND (alkuPvm <= CURRENT_TIMESTAMP)
					AND (loppuPvm >= CURRENT_TIMESTAMP OR loppuPvm IS NULL)
				ORDER BY maaraalennus_kpl";
		$tuote_maaraalennukset = $db->query( $sql, [ $tuote->id ], FETCH_ALL );

		/*
		 * Yrityksekohtaiset määräalennukset (hakee eri taulusta).
		 */
		$sql = "SELECT maaraalennus_kpl, alennus_prosentti, alkuPvm, loppuPvm
				FROM tuoteyritys_erikoishinta
				WHERE tuote_id = ? AND yritys_id = ?
					AND (alkuPvm <= CURRENT_TIMESTAMP)
					AND (loppuPvm >= CURRENT_TIMESTAMP OR loppuPvm IS NULL)
				ORDER BY maaraalennus_kpl";
		$yritys_maaraalennukset = $db->query( $sql, [ $tuote->id, $yritys_id ], FETCH_ALL );

		/*
		 * Tuoteryhmäkohtaiset määräalennukset (hakee kolmannesta taulusta).
		 */
		$ryhma_maaraalennukset = [];
		if ( $tuote->tuoteryhmat ) {
			$inQuery = implode(',', array_fill(0, count($tuote->tuoteryhmat), '?'));
			$values = array_merge( [ $tuote->hankintapaikkaID, $yritys_id ], $tuote->tuoteryhmat );

			$sql = "SELECT maaraalennus_kpl, alennus_prosentti, alkuPvm, loppuPvm
					FROM tuoteryhma_erikoishinta
					WHERE hankintapaikka_id = ?
						AND (alkuPvm <= CURRENT_TIMESTAMP)
						AND (loppuPvm >= CURRENT_TIMESTAMP OR loppuPvm IS NULL)
						AND (yritys_id = 0 OR yritys_id = ?)
						AND tuoteryhma_id IN ( {$inQuery} )
					ORDER BY maaraalennus_kpl";
			$ryhma_maaraalennukset = $db->query( $sql, $values, FETCH_ALL );
		}

		$tuote->maaraalennukset = array_merge( $tuote_maaraalennukset, $yritys_maaraalennukset, $ryhma_maaraalennukset );
		asort( $tuote->maaraalennukset ); // Järjestää ne kpl-määrän mukaan, joten niiden läpikäynti helpompaa.
	}

	/**
	 * Palauttaa, onko olio käytettävissä, eikä NULL.
	 * @return bool
	 */
	public function isValid() : bool {
		return ($this->id !== null);
	}

	/**
	 * @param boolean $ilman_euro [optional] default=false <p> Tulostetaanko hinta ilman €-merkkiä.
	 * @return string
	 */
	function summa_toString ( bool $ilman_euro = false ) : string {
		return number_format( (double)$this->summa_yhteensa, 2, ',', '.' ) . ($ilman_euro ? '' : ' &euro;');
	}
}
