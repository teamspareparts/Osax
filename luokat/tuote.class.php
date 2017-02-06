<?php
/**
 * Class Tuote
 *
 * @version 2017-02-06
 */
class Tuote {
	/** @var int|NULL $id <p> Tuotteen ID meidän tietokannassa */ public $id = NULL;
	/** @var string $articleNo <p> Tunnus TecDocista */ public $articleNo = '[ArticleNo]';
	/** @var string $brandNo <p> Valmistajan tunnus TecDocista */ public $brandNo = '[BrandNo]';
	/** @var int $hankintapaikka_id <p> Hankintapaikan ID, meidän tietokannasta */ public $hankintapaikka_id = 0;
	/** @var string $tuotekoodi <p> Tuotteen koodi, TecDocista */ public $tuotekoodi = '[Tuotekoodi]';
	/** @var string $tilauskoodi <p> Koodi tilauskirjaa varten */ public $tilauskoodi = '[Tilauskoodi]';
	/** @var string $tuoteryhma <p> */ public $tuoteryhma = '[Tuoteryhmä]';

	/** @var string $nimi <p> */ public $nimi = '[Tuotteen nimi]';
	/** @var string $valmistaja <p> */ public $valmistaja = '[Tuotteen valmistaja]';

	/** @var float $a_hinta <p> */ public $a_hinta = 0.00;
	/** @var float $a_hinta_ilman_alv <p> */ public $a_hinta_ilman_alv = 0.00;
	/** @var float $a_hinta_alennettu <p> Alennus lasketaan luokan ulkopuolella. */ public $a_hinta_alennettu = 0.00;
	/** @var float $a_hinta_ilman_alv_alennettu <p> TODO: NOT IMPLEMENTED */ public $a_hinta_ilman_alv_alennettu = 0.00;
	/** @var float $alv_prosentti <p> */ public $alv_prosentti = 0.00;
	/** @var float $alennus_prosentti <p> */ public $alennus_prosentti = 0.00;

	/** @var int $kpl_maara <p> */ public $kpl_maara = 0;
	/** @var float $summa <p> */ public $summa = 0.00;

	/**
	 * <code>
	 * Array [
	 * 	kpl-määrä => [ kpl-määrä,
	 * 				   alennus-prosentti ], ...
	 * ]
	 * </code>
	 * @var array $maaraalennus_kpl_raja <p> Määräalennuksen kpl-rajat, ja alennusprosentit
	 */
	public $maaraalennukset = array();
	/** @var string $alennus_huomautus <p> */ public $alennus_huomautus = '---';

	/** @var float $ostohinta <p> Ylläpitoa varten */ public $ostohinta = 0.00;
	/** @var string $hyllypaikka <p> */ public $hyllypaikka = '[Hyllypaikka]';
	/** @var int $varastosaldo <p> */ public $varastosaldo = 0;
	/** @var int $minimimyyntiera <p> */ public $minimimyyntiera = 0;

	/**
	 * WIP Älä käytä konstruktoria.
	 * @param DByhteys $db [optional]
	 * @param int $id [optional]
	 */
	function __construct ( DByhteys $db = NULL, /*int*/ $id = NULL ) {
		if ( $id !== NULL ) { // Varmistetaan parametrin oikeellisuus
			$sql = "SELECT tuote.id, articleNo, brandNo, hankintapaikka_id, tuotekoodi, tilaus_koodi AS tilauskoodi, 
						varastosaldo, minimimyyntiera, valmistaja, nimi, ALV_kanta.prosentti AS alv_prosentti, 
						(hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS a_hinta,
						(hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS a_hinta_alennettu,
						hinta_ilman_alv AS a_hinta_ilman_alv, hinta_ilman_alv AS a_hinta_ilman_alv_alennettu,
						hyllypaikka, tuoteryhma, sisaanostohinta AS ostohinta
					FROM tuote
					LEFT JOIN ALV_kanta ON tuote.ALV_kanta = ALV_kanta.kanta
					WHERE tuote.id = ? LIMIT 1";
			$row = $db->query( $sql, [ $id ] );

			if ( $row ) { // Varmistetaan, että jokin tuote löytyi
				foreach ( $row as $property => $propertyValue ) {
					$this->{$property} = $propertyValue;
				}
			}
		}
	}

	/**
	 * @param DByhteys $db
	 * @param int $yritys_id
	 */
	function hae_alennukset ( DByhteys $db, /*int*/ $yritys_id ) {

		$sql = "SELECT maaraalennus_kpl, alennus_prosentti, alkuPvm, loppuPvm
				FROM tuote_erikoishinta
				WHERE tuote_id = ?
					AND (loppuPvm >= CURRENT_TIMESTAMP OR loppuPvm IS NULL)
				ORDER BY maaraalennus_kpl";
		$tuote_maaraalennukset = $db->query( $sql, [$this->id], DByhteys::FETCH_ALL );

		$sql = "SELECT maaraalennus_kpl, alennus_prosentti, alkuPvm, loppuPvm
				FROM tuoteyritys_erikoishinta
				WHERE tuote_id = ? AND yritys_id = ?
					AND (loppuPvm >= CURRENT_TIMESTAMP OR loppuPvm IS NULL)
				ORDER BY maaraalennus_kpl";
		$yritys_maaraalennukset = $db->query( $sql, [$this->id, $yritys_id], DByhteys::FETCH_ALL );

		$sql = "SELECT maaraalennus_kpl, alennus_prosentti, alkuPvm, loppuPvm
				FROM tuoteryhma_erikoishinta
				WHERE hankintapaikka_id = ? AND tuoteryhma = ?
					AND (loppuPvm >= CURRENT_TIMESTAMP OR loppuPvm IS NULL)
					AND (yritys_id = 0 OR yritys_id = ?)
				ORDER BY maaraalennus_kpl";
		$ryhma_maaraalennukset = $db->query( $sql, [$this->hankintapaikka_id, $this->tuoteryhma, $yritys_id],
			DByhteys::FETCH_ALL );

		$this->maaraalennukset = array_merge($tuote_maaraalennukset, $yritys_maaraalennukset, $ryhma_maaraalennukset);
		asort($this->maaraalennukset);
	}

	/**
	 * Parametrien kaikki eri yhdistelmät toimivat.
	 * @param boolean $ilman_alv [optional] default=false <p> Tulostetaanko hinta ilman ALV:ta.
	 * @param boolean $ilman_euro [optional] default=false <p> Tulostetaanko hinta ilman €-merkkiä.
	 * @param bool $ilman_alennus [optional] default=false <p> Tulostetaanko hinta ilman alennusta.
	 * @return string
	 */
	function a_hinta_toString ( /*bool*/ $ilman_alv = false, /*bool*/ $ilman_euro = false,
			/*bool*/ $ilman_alennus = false ) {

		if ( $ilman_alv && $ilman_alennus ) {		// Hinta ilman ALV:ta ja alennusta
			$hinta = $this->a_hinta_ilman_alv;

		} elseif ( !$ilman_alv && $ilman_alennus ) { // Hinta ALV:n kanssa, mutta ilman alennusta
			$hinta = $this->a_hinta;

		} elseif ( $ilman_alv && !$ilman_alennus ) { // Hinta ilman ALV:ta, mutta alennuksen kanssa
			$hinta = $this->a_hinta;

		} else {									// Hinta ALV:n ja alennuksen kanssa
			$hinta = $this->a_hinta_alennettu;
		}

		return number_format( (double)$hinta, 2, ',', '.' ) . ( $ilman_euro ? '' : ' &euro;' );
	}

	/**
	 * @param boolean $ilman_alv [optional] default=false <p> Tulostetaanko hinta ilman ALV:ta.
	 * @param boolean $ilman_euro [optional] default=false <p> Tulostetaanko hinta ilman €-merkkiä.
	 * @return string
	 */
	function summa_toString ( /*bool*/ $ilman_alv = false, /*bool*/ $ilman_euro = false ) {
		$summa = $ilman_alv ? ($this->a_hinta_ilman_alv*$this->kpl_maara) : $this->summa;
		return number_format( (double)$summa, 2, ',', '.' ) . ( $ilman_euro ? '' : ' &euro;' );
	}

	/**
	 * @param bool $ilman_pros [optional] default=false <p> Tulostetaanko ALV ilman %-merkkiä.
	 * @param bool $decimaalina [optional] default=false <p> Tulostetaanko ALV decimaalina (vai kokonaislukuna).
	 * @return string
	 */
	function alv_toString ( /*bool*/ $ilman_pros = false, /*bool*/ $decimaalina = false ) {
		if ( !$decimaalina ) {
			return round( (float)$this->alv_prosentti * 100 ) . ( $ilman_pros ? '' : ' &#37;' );
		} else
			return number_format( (double)$this->alv_prosentti, 2, ',' );
	}
}
