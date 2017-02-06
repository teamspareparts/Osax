<?php
/**
 * Class User
 * @version 2017-02-06
 */
class User {

	public $id = NULL;
	public $yritys_id = 0;

	public $etunimi = '';
	public $sukunimi = '';
	public $puhelin = '';
	public $sahkoposti = '';
	public $yrityksen_nimi = '';

	public $aktiivinen = NULL;
	public $yllapitaja = FALSE;
	public $demo = FALSE;
	public $vahvista_eula = TRUE;
	public $voimassaolopvm = NULL;
	public $salasana_uusittava = NULL;
	/** @var stdClass[] */
	public $toimitusosoitteet = array();

	public $yleinen_alennus = 0.00;
	public $rahtimaksu = 0.00;
	public $ilm_toim_sum_raja = 0.00;

	/**
	 * Käyttäjä-luokan konstruktori.
	 * Jos annettu parametrit, Hakee käyttäjän tiedot tietokannasta. Muuten ei tee mitään.
	 * Jos ei löydä käyttäjää ID:llä, niin kaikki olion arvot pysyvät default arvoissaan.
	 * Testaa löytyikö käyttäjä isValid-metodilla.
	 * @param DByhteys $db [optional]
	 * @param int $user_id [optional]
	 */
	function __construct ( DByhteys $db = NULL, /*int*/ $user_id = NULL ) {
		if ( $user_id !== NULL ) { // Varmistetaan parametrin oikeellisuus
			$sql = "SELECT kayttaja.id, kayttaja.yritys_id, kayttaja.sahkoposti, etunimi, sukunimi, 
						kayttaja.puhelin, yllapitaja, demo, kayttaja.voimassaolopvm, salasana_uusittava,
				  		vahvista_eula, kayttaja.aktiivinen, yritys.nimi AS yrityksen_nimi,
				  		yritys.alennus_prosentti AS yleinen_alennus,
				  		yritys.ilmainen_toimitus_summa_raja AS ilm_toim_sum_raja, yritys.rahtimaksu
					FROM kayttaja 
					JOIN yritys ON kayttaja.yritys_id = yritys.id
					WHERE kayttaja.id = ?
					LIMIT 1";
			$row = $db->query( $sql, [ $user_id ] );

			if ( $row ) { // Varmistetaan, että jokin asiakas löytyi
				foreach ( $row as $property => $propertyValue ) {
					$this->{$property} = $propertyValue;
				}
			}
		}
	}

	/**
	 * Palauttaa TRUE jos käyttäjä on ylläpitäjä, ja false muussa tapauksessa.
	 * @return bool <p> Ylläpitäjä-arvon tarkistuksen tulos
	 */
	public function isAdmin () {
		return ($this->yllapitaja === 1);
	}

    /**
     * Palauttaa TRUE jos käyttäjä on hyväksynyt käyttöehtosopimuksen, ja false muussa tapauksessa.
     * @return bool <p> Onko EULA hyväksytty.
     */
	public function eula_hyvaksytty() {
       return ($this->vahvista_eula === 0);
    }

	/**
	 * Palauttaa koko nimen; muotoiltuna, jos pituus liian pitkä.
	 * @return string
	 */
	public function kokoNimi() {
		$str = ( (strlen($this->etunimi) > 15)
			? (substr($this->etunimi, 0, 1) . ".")
			: $this->etunimi )
			. " {$this->sukunimi}";

		if ( strlen($str) > 30 ) {
			$str = substr($str, 0, 26) . "...";
		}
		return $str;
	}

	/**
	 * Hakee käyttäjän toimitusosoitteet, ja asettaa ne lokaaliin luokan muuttujaan "toimitusosoitteet".
	 * Toisella parametrilla voit määrittää, miten paljon tietoa tarkalleen haetaan.
	 * @param DByhteys $db
	 * @param int $to_id [optional] <p> Hae:
	 *        <ul><li> -2 : vain toimitusosoitteiden määrä (COUNT(id)) </li>
	 *            <li> -1 : kaikki toimitusosoitteet </li>
	 *            <li>  x : tietty toimitusosoite (x on osoitteen ID) </li>
	 *        </ul>
	 * @param bool $omat_tiedot <p> TEMP TODO: Korjaa kunnolla. Tämä on vain väliaikainen ratkaisu siihen,
	 * 		että tilaus käyttää assoc_arrayta, ja omat_tiedot objektia.
	 */
	public function haeToimitusosoitteet ( DByhteys $db, /*int*/ $to_id = -1, $omat_tiedot = false ) {
		if ($omat_tiedot) { $retType = PDO::FETCH_OBJ; } else { $retType = PDO::FETCH_ASSOC; }
		if ( $to_id == -2 ) {
			$sql = "SELECT COUNT(osoite_id) AS count FROM toimitusosoite WHERE kayttaja_id = ? ";
			$this->toimitusosoitteet['count'] = $db->query( $sql, [$this->id] )->count;

		} elseif ( $to_id == -1 ) {
			$sql = "SELECT	osoite_id, etunimi, sukunimi, sahkoposti, puhelin, yritys, 
						katuosoite, postinumero, postitoimipaikka, maa
					FROM	toimitusosoite
					WHERE	kayttaja_id = ?
					ORDER BY osoite_id";
			$this->toimitusosoitteet = $db->query( $sql, [$this->id], DByhteys::FETCH_ALL, $retType );

		} elseif ( $to_id >= 0 ) {
			$sql = "SELECT	osoite_id, etunimi, sukunimi, sahkoposti, puhelin, yritys, 
						katuosoite, postinumero, postitoimipaikka, maa
					FROM	toimitusosoite
					WHERE	kayttaja_id = ? AND osoite_id = ?
					ORDER BY osoite_id LIMIT 1";
			$this->toimitusosoitteet = $db->query( $sql, [$this->id, $to_id] );
		}
	}

	/**
	 * Vaihtaa salasanan käyttäjälle.
	 * Salasana hajautetaan metodissa. Salasanan pitää olla vähintään 8 merkkiä pitkä.
	 * @param DByhteys $db
	 * @param $uusi_salasana <p> Hajauttamaton uusi salasana, vähintään 8 merkkiä pitkä.
	 * @return bool <p> Palauttaa true, jos salasana > 8 ja vaihtaminen onnistui. Muuten false.
	 */
	public function vaihdaSalasana( DByhteys $db, /*string*/ $uusi_salasana ) {
		if ( strlen($uusi_salasana) >= 8 ) {
			$hajautettu_uusi_salasana = password_hash( $uusi_salasana, PASSWORD_DEFAULT );
			return $db->query(
				"UPDATE kayttaja SET salasana_hajautus = ? WHERE id = ?",
				[ $hajautettu_uusi_salasana, $this->id ] );
		} else {
			return false;
		}
	}

	/**
	 * Palauttaa, onko olio käytettävissä, eikä NULL.
	 * @return bool
	 */
	public function isValid () {
		return ( $this->id !== NULL );
	}

	/**
	 * @param boolean $ilman_euro [optional] default=false <p> Tulostetaanko hinta ilman €-merkkiä.
	 * @return string
	 */
	function rahtimaksu_toString ( /*bool*/ $ilman_euro = false ) {
		return number_format( (double)$this->rahtimaksu, 2, ',', '.' ) . ( $ilman_euro ? '' : ' &euro;' );
	}

	/**
	 * @param boolean $ilman_euro [optional] default=false <p> Tulostetaanko hinta ilman €-merkkiä.
	 * @return string
	 */
	function ilmToimRaja_toString ( /*bool*/ $ilman_euro = false ) {
		return number_format( (double)$this->ilm_toim_sum_raja, 2, ',', '.' ) . ( $ilman_euro ? '' : ' &euro;' );
	}
}
