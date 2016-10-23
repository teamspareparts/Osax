<?php
/** */class Laskutiedot {
	public $tilaus_pvm = '[Tilauksen päivämäärä]';
	public $tilaus_nro = '[Tilauksen numero]';
	public $laskun_nro = '[Laskun numero]';
	public $maksutapa = '[Maksutapa]';
	public $toimitustapa = '[Toimitustapa]';

	public $asiakkaan_id = '[User ID]'; public $asiakas = NULL; //Object
	public $yrityksen_id = '[Yrit ID]'; public $yritys = NULL; //Object

	public $toimitusosoite = NULL; //Object
	public $db = NULL; //Object
	/**@var TuoteL[] */public $tuotteet = array();
	public $hintatiedot = array(
			'alv_kannat' => array(),// Yksittäisten alv-kantojen tietoja varten, kuten perus ja määrä alempana
			'alv_perus' => 0, 		// Summa yhteensä josta alv lasketaan
			'alv_maara' => 0, 		// yhteenlaskettu ALV-maara
			'tuotteet_yht' => 0,	// Yhteenlaskettu summa kaikista tuotteista
			'lisaveloitukset' => 0,	// Esim. rahtimaksu
			'summa_yhteensa' => 0,	// Kaikki maksut yhteenlaskettu. Lopullinen asiakkaan maksama summa.
	);

	/** @param DByhteys $db
	 * @param $tilaus_id */
	function __construct( DByhteys $db, /*int*/ $tilaus_id = NULL ) {
		$this->tilaus_nro = !empty($tilaus_id) ? $tilaus_id : '[Til nro]';
		$this->db = $db;
		$this->asiakas = new Asiakas();
		$this->yritys = new YritysL();
		$this->toimitusosoite = new Toimitusosoite();
		$this->tuotteet[] = new TuoteL();
		$this->haeTilauksenTiedot( $this->tilaus_nro );
	}

	/** @param $tilaus_nro */
	function haeTilauksenTiedot( $tilaus_nro = NULL ) {
		$sql = "SELECT kayttaja_id, paivamaara, pysyva_rahtimaksu FROM tilaus WHERE id = ? LIMIT 1";
		$this->tilaus_nro = !empty($tilaus_nro) ? $tilaus_nro : $this->tilaus_nro;
		$row = $this->db->query( $sql, [$this->tilaus_nro] );
		if ( $row ) {
			$this->asiakkaan_id = $row->kayttaja_id;
			$this->tilaus_pvm = $row->paivamaara;
			$this->toimitustapa = 'Rahti, 14 päivän toimitus';
			$this->hintatiedot['lisaveloitukset'] += $row->pysyva_rahtimaksu;
			$this->haeAsiakas();
			$this->haeYritys();
			$this->haeToimitusosoite();
			$this->haeTuotteet();
		}
	}

	/** */function haeAsiakas() {
		$sql = "SELECT id, puhelin, sahkoposti, yritys_id, CONCAT(etunimi, ' ', sukunimi) AS koko_nimi
				FROM kayttaja WHERE	id = ? LIMIT 1";
		$row = $this->db->query( $sql, [$this->asiakkaan_id] );
		if ( $row ) {
			$this->asiakas->id = $row->id;
			$this->asiakas->koko_nimi = $row->koko_nimi;
			$this->asiakas->sahkoposti = $row->sahkoposti;
			$this->asiakas->puhelin = $row->puhelin;
			$this->asiakas->yritys = $row->yritys_id;
		}
	}

	/** */function haeYritys() {
		$sql = "SELECT id, puhelin, sahkoposti, nimi, katuosoite, postinumero, postitoimipaikka, y_tunnus
				FROM yritys WHERE id = ? LIMIT 1";
		$row = $this->db->query( $sql, [$this->asiakas->yritys] );
		if ( $row ) {
			$this->yritys->id = $row->id;
			$this->yritys->yritysnimi = $row->nimi;
			$this->yritys->sahkoposti = $row->sahkoposti;
			$this->yritys->puhelin = $row->puhelin;
			$this->yritys->y_tunnus = $row->y_tunnus;
			$this->yritys->katuosoite = $row->katuosoite;
			$this->yritys->postinumero = $row->postinumero;
			$this->yritys->postitoimipaikka = $row->postitoimipaikka;
		}
	}

	/** */function haeToimitusosoite() {
		$sql = "SELECT CONCAT(pysyva_etunimi, ' ', pysyva_sukunimi) AS koko_nimi, pysyva_puhelin, 
					pysyva_sahkoposti, pysyva_katuosoite, pysyva_postinumero, pysyva_postitoimipaikka, pysyva_yritys
				FROM tilaus_toimitusosoite WHERE tilaus_id = ?";
		$row = $this->db->query( $sql, [$this->tilaus_nro] );
		if ( $row ) {
			$this->toimitusosoite->koko_nimi = $row->koko_nimi;
			$this->toimitusosoite->sahkoposti = $row->pysyva_sahkoposti;
			$this->toimitusosoite->puhelin = $row->pysyva_puhelin;
			$this->toimitusosoite->katuosoite = $row->pysyva_katuosoite;
			$this->toimitusosoite->postinumero = $row->pysyva_postinumero;
			$this->toimitusosoite->postitoimipaikka = $row->pysyva_postitoimipaikka;
		}
	}

	/** */function haeTuotteet() {
		$this->tuotteet = array();
		$sql = "SELECT tuote.id, tuote.articleNo, tuote.brandNo, tilaus_tuote.kpl, tilaus_tuote.pysyva_hinta, 
					tilaus_tuote.pysyva_alv, tilaus_tuote.pysyva_alennus,
					((tilaus_tuote.pysyva_hinta * (1+tilaus_tuote.pysyva_alv)) * (1-tilaus_tuote.pysyva_alennus))
						AS maksettu_hinta
				FROM tilaus_tuote LEFT JOIN tuote ON tuote.id = tilaus_tuote.tuote_id 
				WHERE tilaus_tuote.tilaus_id = ?";
		$this->db->prepare_stmt($sql);
		$this->db->run_prepared_stmt([$this->tilaus_nro]);
		$row = $this->db->get_next_row();
		while ( $row ) {
			$tuote = new TuoteL();
			$tuote->id = $row->id;
			$tuote->tuotekoodi = $row->articleNo;
//			$tuote->tuotenimi = $row->articleNo;
//			$tuote->valmistaja = $row->brandNo;
			$tuote->a_hinta = round($row->maksettu_hinta, 2);
			$tuote->a_hinta_ilman_alv = $row->pysyva_hinta * (1 - $row->pysyva_alennus);
			$tuote->alv_prosentti = (int)((float)$row->pysyva_alv * 100); // 0.24 => 24
			$tuote->alennus = (float)$row->pysyva_alennus * 100; // 0.24 => 24
			$tuote->kpl_maara = $row->kpl;
			$tuote->summa = ($row->maksettu_hinta * $row->kpl);
			$this->tuotteet[] = $tuote;

			if ( !array_key_exists($tuote->alv_prosentti, $this->hintatiedot['alv_kannat']) ) {
				$this->hintatiedot['alv_kannat'][$tuote->alv_prosentti]['kanta'] = $tuote->alv_prosentti;
				$this->hintatiedot['alv_kannat'][$tuote->alv_prosentti]['perus'] = 0;
				$this->hintatiedot['alv_kannat'][$tuote->alv_prosentti]['maara'] = 0;
			}
			$this->hintatiedot['alv_kannat'][$tuote->alv_prosentti]['perus'] +=
				$tuote->a_hinta_ilman_alv * $tuote->kpl_maara;
			$this->hintatiedot['alv_kannat'][$tuote->alv_prosentti]['maara'] +=
				($tuote->a_hinta - $tuote->a_hinta_ilman_alv) * $tuote->kpl_maara;

			$this->hintatiedot['alv_perus'] += $tuote->a_hinta_ilman_alv * $tuote->kpl_maara;
			$this->hintatiedot['alv_maara'] += ($tuote->a_hinta - $tuote->a_hinta_ilman_alv) * $tuote->kpl_maara;
			$this->hintatiedot['tuotteet_yht'] += $tuote->summa;

			$row = $this->db->get_next_row();
		}
		$this->hintatiedot['summa_yhteensa'] =
			$this->hintatiedot['tuotteet_yht'] + $this->hintatiedot['lisaveloitukset'];
	}


	/** @param $number
	 * @return string */
	function float_toString ( /*float*/$number ) {
		return number_format ( (double)$number, 2, ',', '.' );
	}
}

/** */class Asiakas {
	public $id = '[ID]';
	public $koko_nimi = '[Koko nimi]';
	public $sahkoposti = '[Sähköposti]';
	public $puhelin = '[Puhelin]';
	public $yritys = '[Asiakkaan yritys]';

	/** */ function __toString () {
		return "<p>
			Asiakkaan tiedot:<br>
			Nimi: {$this->koko_nimi}<br>
			Sähköposti: {$this->sahkoposti}<br>
			Puhelin: {$this->puhelin}<br>
			Yritys: {$this->yritys}<br>
			</p>";
	}
}

/** */class YritysL {
	public $id = '[ID]';
	public $yritysnimi = '[Yrityksen nimi]';
	public $sahkoposti = '[Sähköposti]';
	public $puhelin = '[Puhelin]';
	public $y_tunnus = '[Y-tunnus]';
	public $katuosoite = '[Katuosoite]';
	public $postinumero = '[Postinumero]';
	public $postitoimipaikka = '[Postitoimipaikka]';

	/** */function __toString () {
		return "<p>
			Yrityksen tiedot:<br>
			Nimi: {$this->yritysnimi}<br>
			Sähköposti: {$this->sahkoposti}<br>
			Puhelin: {$this->puhelin}<br>
			Yritys: {$this->y_tunnus}<br>
			Katuosoite: {$this->katuosoite}<br>
			Postinumero: {$this->postinumero}<br>
			Postitoimipaikka: {$this->postitoimipaikka}<br>
			</p>";
	}
}

/** */class Toimitusosoite {
	public $koko_nimi = '[Koko nimi]';
	public $sahkoposti = '[Sähköposti]';
	public $puhelin = '[Puhelin]';
	public $katuosoite = '[Katuosoite]';
	public $postinumero = '[Postinumero]';
	public $postitoimipaikka = '[Postitoimipaikka]';

	/** */function __toString () {
		return "<p>
			Toimitusosoitteen tiedot:<br>
			Nimi: {$this->koko_nimi}<br>
			Sähköposti: {$this->sahkoposti}<br>
			Puhelin: {$this->puhelin}<br>
			Katuosoite: {$this->katuosoite}<br>
			Postinumero: {$this->postinumero}<br>
			Postitoimipaikka: {$this->postitoimipaikka}<br>
			</p>";
	}
}

/** */class TuoteL {
	public $id = '[ID]';
	public $tuotekoodi = '[Tuotekoodi]';
	public $tuotenimi = '[Tuotteen nimi]';
	public $valmistaja = '[Tuotteen valmistaja]';
	public $a_hinta = '[a-hinta]';
	public $a_hinta_ilman_alv = '[a-hinta ilman ALV]';
	public $alv_prosentti = '[ALV]';
	public $alennus = '[Alennus]';
	public $kpl_maara = '[KPL-määrä]';
	public $summa = '[Tuotteiden summa]';

	/** */function a_hinta_toString () {
		return number_format ( (double)$this->a_hinta, 2, ',', '.' );
	}

	/** */function summa_toString () {
		return number_format ( (double)$this->summa, 2, ',', '.' );
	}
}
