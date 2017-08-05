<?php
/**
 * Class Tuote
 *
 * @version 2017-03-12 <p> a_hinta_toString metodin bugikorjaus, ja alennus_toString lisäys.
 */
class Tuote {
	/** @var int|NULL $id <p> Tuotteen ID meidän tietokannassa */ public $id = NULL;
	/** @var string $articleNo <p> Tunnus TecDocista */ public $articleNo = '[ArticleNo]';
	/** @var string $brandNo <p> Valmistajan tunnus TecDocista */ public $brandNo = '[BrandNo]';
	/** @var int $hankintapaikkaID <p> Hankintapaikan ID, meidän tietokannasta */ public $hankintapaikkaID = 0;
	/** @var string $tuotekoodi <p> Tuotteen koodi, TecDocista */ public $tuotekoodi = '[Tuotekoodi]';
	/** @var string $tilauskoodi <p> Koodi tilauskirjaa varten */ public $tilauskoodi = '[Tilauskoodi]';
	/** @var float $ostohinta <p> Ylläpitoa varten */ public $ostohinta = 0.00;
	/** @var string $hyllypaikka <p> */ public $hyllypaikka = '[Hyllypaikka]';
	/** @var int $varastosaldo <p> */ public $varastosaldo = 0;
	/** @var int $tehdassaldo <p> */ public $tehdassaldo = 0;
	/** @var int $minimimyyntiera <p> */ public $minimimyyntiera = 0;

	/** @var string $nimi <p> */ public $nimi = '[Tuotteen nimi]';
	/** @var string $valmistaja <p> */ public $valmistaja = '[Tuotteen valmistaja]';

	/** @var array $tuoteryhmat <p> Kaikkien ryhmien ID, joissa tuote on. */ public $tuoteryhmat = array();

	/** @var float $a_hinta_ilman_alv <p> Kappale-hinta ilman alennusta, tai ALV:ta */ public $a_hinta_ilman_alv = 0.00;
	/** @var float $a_hinta <p> Kappale-hinta ALV:n kanssa (ilman alennusta) */ public $a_hinta = 0.00;
	/** @var float $a_hinta_alennettu <p> With ALV ja alennus. */ public $a_hinta_alennettu = 0.00;
	/** @var float $a_hinta_alennettu <p> w/out ALV, mutta w/ alennus. */ public $a_hinta_alennettu_ilman_alv = 0.00;

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

	/**
	 * @param DByhteys $db [optional]
	 * @param int      $id [optional] <p> Halutun tuotteen ID tietokannassa
	 */
	function __construct ( DByhteys $db = null, /*int*/ $id = null ) {
		if ( $id !== null ) { // Varmistetaan parametrin oikeellisuus
			$sql = "SELECT tuote.id, articleNo, brandNo, hankintapaikka_id, tuotekoodi,
						tilauskoodi, varastosaldo, minimimyyntiera, valmistaja, nimi,
						ALV_kanta.prosentti AS alv_prosentti, hyllypaikka, sisaanostohinta AS ostohinta, 
						(hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS a_hinta,
						(hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS a_hinta_alennettu,
						hinta_ilman_alv AS a_hinta_ilman_alv, hinta_ilman_alv AS a_hinta_alennettu_ilman_alv,
						toimittaja_tehdassaldo.tehdassaldo
					FROM tuote
					LEFT JOIN ALV_kanta ON tuote.ALV_kanta = ALV_kanta.kanta
					LEFT JOIN toimittaja_tehdassaldo 
						ON tuote.hankintapaikka_id = toimittaja_tehdassaldo.hankintapaikka_id
							AND tuote.articleNo = toimittaja_tehdassaldo.tuote_articleNo
					WHERE tuote.id = ? LIMIT 1";
			// a_hinta_alennettu on sama, jotta a_hinta_toString() toimii.
			// Se on kuitenkin tarkoitus laskea manuaalisti jälkeenpäin, koska alennukset haetaan erikseen.
			$row = $db->query( $sql, [ $id ] );

			if ( $row ) { // Varmistetaan, että jokin tuote löytyi
				foreach ( $row as $property => $propertyValue ) {
					$this->{$property} = $propertyValue;
				}
			}

			//$this->haeTuoteryhmat($db);
		}
	}

	/**
	 * @param DByhteys $db
	 */
	function haeTuoteryhmat( DByhteys $db ) {
		$rows = $db->query( "SELECT tuoteryhma_id FROM tuoteryhma_tuote WHERE tuote_id = ?",
							[ $this->id ], DByhteys::FETCH_ALL, PDO::FETCH_NUM );
		foreach ( $rows as $row ) {
			$this->tuoteryhmat[] = $row[0];
		}
	}

	/**
	 * @param DByhteys $db
	 * @param int      $yritys_id
	 */
	function haeAlennukset ( DByhteys $db, /*int*/ $yritys_id ) {
		$sql = "SELECT maaraalennus_kpl, alennus_prosentti, alkuPvm, loppuPvm
				FROM tuote_erikoishinta
				WHERE tuote_id = ?
					AND (alkuPvm <= CURRENT_TIMESTAMP)
					AND (loppuPvm >= CURRENT_TIMESTAMP OR loppuPvm IS NULL)
				ORDER BY maaraalennus_kpl";
		$tuote_maaraalennukset = $db->query( $sql, [ $this->id ], DByhteys::FETCH_ALL );

		$sql = "SELECT maaraalennus_kpl, alennus_prosentti, alkuPvm, loppuPvm
				FROM tuoteyritys_erikoishinta
				WHERE tuote_id = ? AND yritys_id = ?
					AND (alkuPvm <= CURRENT_TIMESTAMP)
					AND (loppuPvm >= CURRENT_TIMESTAMP OR loppuPvm IS NULL)
				ORDER BY maaraalennus_kpl";
		$yritys_maaraalennukset = $db->query( $sql, [ $this->id, $yritys_id ], DByhteys::FETCH_ALL );

		if ( $this->tuoteryhmat ) {
			$inQuery = implode(',', array_fill(0, count($this->tuoteryhmat), '?'));
			$values = array_merge( [ $this->hankintapaikkaID, $yritys_id ], $this->tuoteryhmat );

			$sql = "SELECT maaraalennus_kpl, alennus_prosentti, alkuPvm, loppuPvm
					FROM tuoteryhma_erikoishinta
					WHERE hankintapaikka_id = ?
						AND (alkuPvm <= CURRENT_TIMESTAMP)
						AND (loppuPvm >= CURRENT_TIMESTAMP OR loppuPvm IS NULL)
						AND (yritys_id = 0 OR yritys_id = ?)
						AND tuoteryhma_id IN ( {$inQuery} )
					ORDER BY maaraalennus_kpl";
			$ryhma_maaraalennukset = $db->query( $sql, $values, DByhteys::FETCH_ALL );
		} else {
			$ryhma_maaraalennukset = []; }

		$this->maaraalennukset = array_merge( $tuote_maaraalennukset, $yritys_maaraalennukset, $ryhma_maaraalennukset );
		asort( $this->maaraalennukset );
	}

	/**
	 * Palauttaa hinnan valittujen parametrien mukaan. Defaultina tulostaa verollisen alennetun hinnan.
	 * @param boolean $ilman_alv     [optional] default=false <p> Tulostetaanko hinta ilman ALV:ta.
	 * @param boolean $ilman_euro    [optional] default=false <p> Tulostetaanko hinta ilman €-merkkiä.
	 * @param bool    $ilman_alennus [optional] default=false <p> Tulostetaanko hinta ilman alennusta.
	 * @param int     $dec_count     [optional] default=2 <p> Kuinka monta desimaalia.
	 * @return string
	 */
	function a_hinta_toString ( /*bool*/ $ilman_alv = false, /*bool*/ $ilman_euro = false,
			/*bool*/ $ilman_alennus = false, /*int*/ $dec_count = 2 ) {

		// Hinta ilman ALV:ta ja alennusta
		if ( $ilman_alv && $ilman_alennus ) {
			return number_format( (float)$this->a_hinta_ilman_alv, $dec_count, ',', '.' ) . ($ilman_euro ? '' : ' &euro;');
		}
		// Hinta ALV:n kanssa, mutta ilman alennusta
		elseif ( !$ilman_alv && $ilman_alennus ) {
			return number_format( (float)$this->a_hinta, $dec_count, ',', '.' ) . ($ilman_euro ? '' : ' &euro;');
		}
		// Hinta ilman ALV:ta, mutta alennuksen kanssa
		elseif ( $ilman_alv && !$ilman_alennus ) {
			return number_format( (float)$this->a_hinta_alennettu_ilman_alv, $dec_count, ',', '.' ) . ($ilman_euro ? '' : ' &euro;');
		}
		// Hinta ALV:n ja alennuksen kanssa
		else {
			return number_format( (float)$this->a_hinta_alennettu, $dec_count, ',', '.' ) . ($ilman_euro ? '' : ' &euro;');
		}
	}

	/**
	 * @param boolean $ilman_alv  [optional] default=false <p> Tulostetaanko hinta ilman ALV:ta.
	 * @param boolean $ilman_euro [optional] default=false <p> Tulostetaanko hinta ilman €-merkkiä.
	 * @param int     $dec_count  [optional] default=2 <p> Kuinka monta desimaalia.
	 * @return string
	 */
	function summa_toString ( /*bool*/ $ilman_alv = false, /*bool*/ $ilman_euro = false, /*int*/ $dec_count = 2 ) {
		$summa = $ilman_alv ? ($this->a_hinta_alennettu_ilman_alv * $this->kpl_maara) : $this->summa;

		return number_format( (float)$summa, $dec_count, ',', '.' ) . ($ilman_euro ? '' : ' &euro;');
	}

	/**
	 * Palauttaa ALV:n. Mahdollinen formaatti: [0,]xx[ %]
	 * @param bool $ilman_pros  [optional] default=false <p> Tulostetaanko ALV ilman %-merkkiä.
	 * @param bool $decimaalina [optional] default=false <p> Tulostetaanko ALV decimaalina (vai kokonaislukuna).
	 * @return string
	 */
	function alv_toString ( /*bool*/ $ilman_pros = false, /*bool*/ $decimaalina = false ) {
		if ( !$decimaalina ) {
			return round( (float)$this->alv_prosentti * 100 ) . ($ilman_pros ? '' : ' &#37;');
		}
		else {
			return number_format( (float)$this->alv_prosentti, 2, ',' );
		}
	}

	/**
	 * @param bool $ilman_pros  [optional] default=false <p> Tulostetaanko ALV ilman %-merkkiä.
	 * @param bool $decimaalina [optional] default=false <p> Tulostetaanko ALV decimaalina (vai kokonaislukuna).
	 * @return string
	 */
	function alennus_toString ( /*bool*/ $ilman_pros = false, /*bool*/ $decimaalina = false ) {
		if ( !$decimaalina ) {
			return round( (float)$this->alennus_prosentti * 100 ) . ($ilman_pros ? '' : ' &#37;');
		}
		else {
			return number_format( (double)$this->alennus_prosentti, 2, ',' );
		}
	}
}
