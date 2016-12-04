<?php
/**
 * Class Tuote
 */
class Tuote {
	/** @var int|NULL $id <p> Tuotteen ID meidän tietokannassa */ public $id = NULL;
	/** @var string $articleNo <p> Tunnus TecDocista */ public $articleNo = '[ArticleNo]';
	/** @var string $brandNo <p> Valmistajan tunnus TecDocista */ public $brandNo = '[BrandNo]';
	/** @var int $hankintapaikka_id <p> Hankintapaikan ID, meidän tietokannasta */ public $hankintapaikka_id = 0;
	/** @var string $tuotekoodi <p> Tuotteen koodi, TecDocista */ public $tuotekoodi = '[Tuotekoodi]';
	/** @var string $tilauskoodi <p> Koodi tilauskirjaa varten */ public $tilauskoodi = '[Tilauskoodi]';

	/** @var string $nimi <p> */ public $nimi = '[Tuotteen nimi]';
	/** @var string $valmistaja <p> */ public $valmistaja = '[Tuotteen valmistaja]';

	/** @var float $a_hinta <p> */ public $a_hinta = 0.00;
	/** @var float $a_hinta_ilman_alv <p> Veroton hinta */ public $a_hinta_ilman_alv = 0.00;
	/** @var float $ostohinta <p> Ylläpitoa varten NOT IMPLEMENTED */ public $ostohinta = 0.00;

	/** @var float $alv_prosentti <p> */ public $alv_prosentti = 0.00;
	/** @var float $alennus <p> Tuotteen alennusprosentti, jos olemassa */ public $yleinen_alennus = 0.00;
	/**
	 * <code>
	 * Array [
	 * 	kpl-määrä => [ kpl-määrä,
	 * 				   alennus-prosentti ], ...
	 * ]
	 * </code>
	 * @var array $maaraalennus_kpl_raja <p> Määräalennuksen kpl-rajat
	 */ public $maaraalennukset = array();
	/** @var int $kpl_maara <p> */ public $kpl_maara = 0;
	/** @var float $summa <p> */ public $summa = 0.00;

	/** @var string $hyllypaikka <p> */ public $hyllypaikka = '[Hyllypaikka]';

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
						hinta_ilman_alv AS a_hinta_ilman_alv, hyllypaikka
					FROM tuote
					LEFT JOIN ALV_kanta ON tuote.ALV_kanta = ALV_kanta.kanta
					LEFT JOIN tuote_erikoishinta ON tuote.id = tuote_erikoishinta.tuote_id
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
	 * @param int|null $id [optional]
	 */
	function hae_alennukset ( DByhteys $db, /*int*/$id = NULL ) {
		if ( is_null($id) ) {
			$id = $this->id;
		}

		$sql = "SELECT maaraalennus_kpl, maaraalennus_prosentti
				FROM tuote_erikoishinta
				WHERE tuote_id = ?
					AND tuote_erikoishinta.voimassaolopvm >= CURDATE()";
		$db->query( $sql, [$id] );
	}

	/**
	 * @param boolean $ilman_alv [optional] default=false <p> Tulostetaanko hinta ilman ALV:ta.
	 * @param boolean $ilman_euro [optional] default=false <p> Tulostetaanko hinta ilman €-merkkiä.
	 * @return string
	 */
	function a_hinta_toString ( /*bool*/ $ilman_alv = false, /*bool*/ $ilman_euro = false ) {
		$hinta = $ilman_alv ? $this->a_hinta_ilman_alv : $this->a_hinta;
		return number_format( (double)$hinta, 2, ',', '.' ) . ( $ilman_euro ? '' : ' &euro;' );
	}

	/**
	 * @param boolean $ilman_alv [optional] default=false <p> Tulostetaanko hinta ilman ALV:ta.
	 * @param boolean $ilman_euro [optional] default=false <p> Tulostetaanko hinta ilman €-merkkiä.
	 * @return string
	 */
	function summa_toString ( /*bool*/ $ilman_alv = false, /*bool*/ $ilman_euro = false ) {
		$hinta = $ilman_alv ? ($this->a_hinta_ilman_alv*$this->kpl_maara) : $this->summa;
		return number_format( (double)$hinta, 2, ',', '.' ) . ( $ilman_euro ? '' : ' &euro;' );
	}
}
