<?php
/**
 * Class User
 * @version 2017-03-12 <p> eula_hyväksytty korvattu eulaHyvaksytty-metodilla
 */
class User {

	public $id = null;
	public $yritys_id = 0;

	public $etunimi = '';
	public $sukunimi = '';
	public $puhelin = '';
	public $sahkoposti = '';
	public $yrityksen_nimi = '';

	public $aktiivinen = null;
	public $yllapitaja = false;
	public $demo = false;
	public $vahvista_eula = true;
	public $voimassaolopvm = null;
	public $salasana_uusittava = null;
	/** @var stdClass[] */
	public $toimitusosoitteet = array();

	/** @var float <p> Yrityksen tiedoista. */ public $yleinen_alennus = 0.00;
	/** @var float <p> Yrityksen tiedoista. */ public $rahtimaksu = 0.00;
	/** @var float <p> Yrityksen tiedoista. */ public $ilm_toim_sum_raja = 0.00;
	/** @var int <p> Käyttäjän sallitut maksutavat. <br> 0: Paytrail, 1: lasku 14pv, >1: 501 HTTP */
	public $maksutapa = 0;

	/**
	 * Käyttäjä-luokan konstruktori.
	 * Jos annettu parametrit, Hakee käyttäjän tiedot tietokannasta. Muuten ei tee mitään.
	 * Jos ei löydä käyttäjää ID:llä, niin kaikki olion arvot pysyvät default arvoissaan.
	 * Testaa löytyikö käyttäjä isValid-metodilla.
	 * @param DByhteys $db      [optional]
	 * @param int      $user_id [optional]
	 */
	function __construct( DByhteys $db = null, /*int*/ $user_id = null ) {
		if ( $user_id !== null ) { // Varmistetaan parametrin oikeellisuus
			$sql = "SELECT kayttaja.id, kayttaja.yritys_id, kayttaja.sahkoposti, etunimi, sukunimi, 
						kayttaja.puhelin, yllapitaja, demo, kayttaja.voimassaolopvm, salasana_uusittava,
				  		vahvista_eula, kayttaja.aktiivinen, yritys.nimi AS yrityksen_nimi,
				  		yritys.alennus_prosentti AS yleinen_alennus, yritys.maksutapa,
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
	public function isAdmin() {
		return ($this->yllapitaja === 1);
	}

	/**
	 * Palauttaa TRUE jos käyttäjä on hyväksynyt käyttöehtosopimuksen, ja FALSE muussa tapauksessa.
	 * @return bool <p> Onko EULA hyväksytty.
	 */
	public function eulaHyvaksytty() {
		return ($this->vahvista_eula === 0);
	}

	/**
	 * Palauttaa koko nimen; muotoiltuna, jos pituus liian pitkä.
	 * Max. pituus 15 etunimelle, ja 30 merkkiä yhteensä. Lyhentää muotoon
	 * E. Eeeeeeeeeeeeeeeeeeeeeeee...
	 * @return string
	 */
	public function kokoNimi() {
		// Tarkistetaan etunimen pituus (jos liian pitkä, lyhennetään E.), ja lisätään sukunimi perään.
		$str = ((strlen( $this->etunimi ) > 15)
					? (substr( $this->etunimi, 0, 1 ) . ".")
					: $this->etunimi) . " {$this->sukunimi}";

		if ( strlen( $str ) > 30 ) {
			$str = substr( $str, 0, 26 ) . "...";
		}

		return $str;
	}

	/**
	 * Hakee käyttäjän toimitusosoitteet, ja asettaa ne lokaaliin luokan muuttujaan "toimitusosoitteet".
	 * Toisella parametrilla voit määrittää, miten paljon tietoa tarkalleen haetaan.
	 * @param DByhteys $db
	 * @param int      $to_id       [optional]
	 *                              <ul> <li> -2 : vain toimitusosoitteiden määrä (COUNT(id)) </li>
	 *                              <li> -1 : kaikki toimitusosoitteet </li>
	 *                              <li>  x : tietty toimitusosoite (x on osoitteen ID) </li> </ul>
	 * @param bool     $omat_tiedot <p> TEMP TODO: Korjaa kunnolla. Tämä on vain väliaikainen ratkaisu siihen,
	 *                              että tilaus käyttää assoc_arrayta, ja omat_tiedot objektia.
	 */
	public function haeToimitusosoitteet( DByhteys $db, /*int*/ $to_id = -1, $omat_tiedot = false ) {
		if ( $omat_tiedot ) {
			$retType = PDO::FETCH_OBJ;
		}
		else {
			$retType = PDO::FETCH_ASSOC;
		}
		if ( $to_id == -2 ) {
			$sql = "SELECT COUNT(osoite_id) AS count FROM toimitusosoite WHERE kayttaja_id = ? ";
			$this->toimitusosoitteet[ 'count' ] = $db->query( $sql, [ $this->id ] )->count;

		}
		elseif ( $to_id == -1 ) {
			$sql = "SELECT	osoite_id, etunimi, sukunimi, sahkoposti, puhelin, yritys, 
						katuosoite, postinumero, postitoimipaikka, maa
					FROM	toimitusosoite
					WHERE	kayttaja_id = ?
					ORDER BY osoite_id";
			$this->toimitusosoitteet = $db->query( $sql, [ $this->id ], DByhteys::FETCH_ALL, $retType );

		}
		elseif ( $to_id >= 0 ) {
			$sql = "SELECT	osoite_id, etunimi, sukunimi, sahkoposti, puhelin, yritys, 
						katuosoite, postinumero, postitoimipaikka, maa
					FROM	toimitusosoite
					WHERE	kayttaja_id = ? AND osoite_id = ?
					ORDER BY osoite_id LIMIT 1";
			$this->toimitusosoitteet = $db->query( $sql, [ $this->id, $to_id ] );
		}
	}

	/**
	 * Vaihtaa salasanan käyttäjälle.
	 * Salasana hajautetaan metodissa. Salasanan pitää olla vähintään 8 merkkiä pitkä.
	 * @param DByhteys $db
	 * @param string   $uusi_salasana <p> Hajauttamaton uusi salasana, vähintään 8 merkkiä pitkä.
	 * @return bool <p> Palauttaa true, jos salasana > 8 ja vaihtaminen onnistui. Muuten false.
	 */
	public function vaihdaSalasana( DByhteys $db, /*string*/ $uusi_salasana ) {
		if ( strlen( $uusi_salasana ) >= 8 ) {
			$hajautettu_uusi_salasana = password_hash( $uusi_salasana, PASSWORD_DEFAULT );

			return $db->query( "UPDATE kayttaja SET salasana_hajautus = ? WHERE id = ?",
							   [ $hajautettu_uusi_salasana, $this->id ] );
		}
		else {
			return false;
		}
	}

	/**
	 * Palauttaa, onko olio käytettävissä (ei NULL).
	 * @return bool
	 */
	public function isValid() {
		return ($this->id !== null);
	}

	/**
	 * @param boolean $ilman_euro [optional] default=false <p> Tulostetaanko hinta ilman €-merkkiä.
	 * @return string
	 */
	function rahtimaksu_toString( /*bool*/ $ilman_euro = false ) {
		return number_format( (float)$this->rahtimaksu, 2, ',', '.' ) . ($ilman_euro ? '' : ' &euro;');
	}

	/**
	 * @param boolean $ilman_euro [optional] default=false <p> Tulostetaanko hinta ilman €-merkkiä.
	 * @return string
	 */
	function ilmToimRaja_toString( /*bool*/ $ilman_euro = false ) {
		return number_format( (float)$this->ilm_toim_sum_raja, 2, ',', '.' ) . ($ilman_euro ? '' : ' &euro;');
	}
}
