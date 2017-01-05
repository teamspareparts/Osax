<?php
/** */
class Laskutiedot {
	public $tilaus_pvm = '[Tilauksen päivämäärä]';
	public $tilaus_nro = '[Tilauksen numero]';
	public $laskun_nro = '[Laskun numero]';
	public $maksutapa = '[Maksutapa]';
	public $toimitustapa = '[Toimitustapa]';

	/** @var User */public $asiakas = NULL;
	/** @var Yritys */public $yritys = NULL;
	/** @var Yritys */public $osax = NULL;

	public $toimitusosoite = NULL; //Object
	public $db = NULL; //Object
	/**@var Tuote[] */public $tuotteet = array();
	public $hintatiedot = array(
		'alv_kannat' => array(),// Yksittäisten alv-kantojen tietoja varten, kuten perus ja määrä alempana
		'alv_perus' => 0.00, 		// Summa yhteensä josta alv lasketaan
		'alv_maara' => 0.00, 		// yhteenlaskettu ALV-maara
		'tuotteet_yht' => 0.00,		// Yhteenlaskettu summa kaikista tuotteista
		'lisaveloitukset' => 0.00,	// Esim. rahtimaksu
		'summa_yhteensa' => 0.00,	// Kaikki maksut yhteenlaskettu. Lopullinen asiakkaan maksama summa.
	);

	/**
	 * @param DByhteys $db
	 * @param int $tilaus_id
	 * @param User $user
	 * @param Yritys $yritys
	 */
	function __construct( DByhteys $db, /*int*/ $tilaus_id = NULL, User $user, Yritys $yritys ) {
		$this->tilaus_nro = !empty($tilaus_id) ? $tilaus_id : '[Til nro]';
		$this->db = $db;
		$this->asiakas = $user;
		$this->yritys = $yritys;
		$this->osax = new Yritys( $db, 1 );
		$this->toimitusosoite = new Toimitusosoite();
		$this->haeTilauksenTiedot( $this->tilaus_nro );
	}

	/** @param $tilaus_nro */
	function haeTilauksenTiedot( $tilaus_nro = NULL ) {
		$sql = "SELECT paivamaara, pysyva_rahtimaksu FROM tilaus WHERE id = ? LIMIT 1";
		$this->tilaus_nro = !empty($tilaus_nro) ? $tilaus_nro : $this->tilaus_nro;
		$row = $this->db->query( $sql, [$this->tilaus_nro] );
		if ( $row ) {
			$this->tilaus_pvm = $row->paivamaara;
			$this->toimitustapa = 'Rahti, 14 päivän toimitus';
			$this->hintatiedot['lisaveloitukset'] += $row->pysyva_rahtimaksu;
			$this->haeToimitusosoite();
			$this->haeTuotteet();
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
		$sql = "SELECT tuote.id, tuote.tuotekoodi, tilaus_tuote.tuotteen_nimi, tilaus_tuote.valmistaja, tilaus_tuote.kpl, 
					tilaus_tuote.pysyva_hinta, tilaus_tuote.pysyva_alv, tilaus_tuote.pysyva_alennus,
					((tilaus_tuote.pysyva_hinta * (1+tilaus_tuote.pysyva_alv)) * (1-tilaus_tuote.pysyva_alennus))
						AS maksettu_hinta
				FROM tilaus_tuote LEFT JOIN tuote ON tuote.id = tilaus_tuote.tuote_id 
				WHERE tilaus_tuote.tilaus_id = ?";
		$this->db->prepare_stmt($sql);
		$this->db->run_prepared_stmt([$this->tilaus_nro]);
		while ( $row = $this->db->get_next_row() ) {
			$tuote = new Tuote();
			$tuote->id = $row->id;
			$tuote->tuotekoodi = $row->tuotekoodi;
			$tuote->nimi = $row->tuotteen_nimi;
			$tuote->valmistaja = $row->valmistaja;
			$tuote->a_hinta = round($row->maksettu_hinta, 2);
			$tuote->a_hinta_ilman_alv = $row->pysyva_hinta * (1 - $row->pysyva_alennus);
			$tuote->alv_prosentti = (int)((float)$row->pysyva_alv * 100); // 0.24 => 24
			$tuote->alennus = (int)((float)$row->pysyva_alennus * 100); // 0.24 => 24
			$tuote->kpl_maara = $row->kpl;
			$tuote->summa = ($row->maksettu_hinta * $row->kpl);
			$this->tuotteet[] = $tuote;

			// Tarkistetaan, että tuotteen ALV-kanta on listalla
			if ( !array_key_exists($tuote->alv_prosentti, $this->hintatiedot['alv_kannat']) ) {
				$this->hintatiedot['alv_kannat'][$tuote->alv_prosentti]['kanta'] = $tuote->alv_prosentti;
				$this->hintatiedot['alv_kannat'][$tuote->alv_prosentti]['perus'] = 0;
				$this->hintatiedot['alv_kannat'][$tuote->alv_prosentti]['maara'] = 0;
			}
			// Lisätään ALV-tiedot arrayhin. Ensin yksittäisen ALV-kannan tiedot...
			$this->hintatiedot['alv_kannat'][$tuote->alv_prosentti]['perus'] +=
				$tuote->a_hinta_ilman_alv * $tuote->kpl_maara;
			$this->hintatiedot['alv_kannat'][$tuote->alv_prosentti]['maara'] +=
				($tuote->a_hinta - $tuote->a_hinta_ilman_alv) * $tuote->kpl_maara;
			// ... ja sitten ALV-kannat yhteensä. En tiedä onko tämä tarpeellista, mutta se nyt on siinä.
			$this->hintatiedot['alv_perus'] += $tuote->a_hinta_ilman_alv * $tuote->kpl_maara;
			$this->hintatiedot['alv_maara'] += ($tuote->a_hinta - $tuote->a_hinta_ilman_alv) * $tuote->kpl_maara;
			$this->hintatiedot['tuotteet_yht'] += $tuote->summa;
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
