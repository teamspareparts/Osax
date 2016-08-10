<?php
/** * */
class Laskutiedot {
	public $tilaus_pvm = '[Tilauksen päivämäärä]';
	public $tilaus_nro = '[Tilauksen numero]';
	public $laskun_nro = '[Laskun numero]';

	public $maksutapa = '[Maksutapa]';
	public $toimitustapa = '[Toimitustapa]';

	protected $asiakkaan_id = '[Asiakkaan ID]';
	/** @var Asiakas object */
	protected $asiakas = NULL;

	protected $yrityksen_id = '[Yrityksen ID]';
	protected $yritys = NULL;

	protected $toimitusosoite = NULL;

	protected $db = NULL;

	protected $tuotteet = array();

	protected $hintatiedot = array(
			'alv_perus' => 0, 		// Summa yhteensä josta alv lasketaan
			'alv_maara' => 0, 		// yhteenlaskettu ALV-maara
			'tuotteet_yht' => 0,	// Yhteenlaskettu summa kaikista tuotteista
			'lisaveloitukset' => 0,	// Esim. rahtimaksu
			'summa_yhteensa' => 0,	// Kaikki maksut yhteenlaskettu. Lopullinen asiakkaan maksama summa.
	);

	/**
	 * Laskutiedot constructor.
	 * @param DByhteys $db
	 * @param int $tilaus_id [optional] default=NULL
	 */
	public function __construct( DByhteys $db, /*int*/ $tilaus_id = NULL ) {
		$this->tilaus_nro = isset($tilaus_id) ? $tilaus_id : '[Tilauksen numero]';
		$this->db = $db;
		$this->asiakas = new Asiakas();
		$this->yritys = new Yritys();
		$this->toimitusosoite = new Toimitusosoite();
		$this->tuotteet[] = new Tuote();
	}

	/**
	 * @param int $tilaus_nro [optional] default=NULL
	 */
	public function haeTilauksenTiedot( $tilaus_nro = NULL ) {
		$query = "	SELECT	kayttaja_id, paivamaara, pysyva_rahtimaksu
					FROM	tilaus
					WHERE	id = ? ";

		$this->tilaus_nro = isset($tilaus_nro) ? $tilaus_nro : $this->tilaus_nro;
		$row = $this->db->query( $query, [$this->tilaus_nro] );
		if ( $row ) {
			$this->asiakkaan_id = $row['kayttaja_id'];
			$this->tilaus_pvm = $row['paivamaara'];
			$this->toimitustapa = 'Rahti, 14 päivän toimitus';
			$this->hintatiedot['lisaveloitukset'] += $row['pysyva_rahtimaksu'];

			$this->haeAsiakas();
			$this->haeYritys();
			$this->haeToimitusosoite();
			$this->haeTuotteet();
			$this->laskeHintatiedot();
		}
	}

	protected function haeAsiakas() {
		$query = "	SELECT	id, puhelin, sahkoposti, yritys, CONCAT(etunimi, ' ', sukunimi) AS koko_nimi
					FROM	kayttaja
					WHERE	id = :id ";
		$row = $this->db->query( $query, ['id' => $this->asiakkaan_id] );

		$this->asiakas->id = $row['id'];
		$this->asiakas->koko_nimi = $row['koko_nimi'];
		$this->asiakas->sahkoposti = $row['sahkoposti'];
		$this->asiakas->puhelin = $row['puhelin'];
		$this->asiakas->yritys = $row['yritys'];
	}

	protected function haeYritys() {
		$query = "	SELECT	id, puhelin, sahkoposti, nimi, katuosoite, postinumero, postitoimipaikka, y_tunnus
					FROM	yritys
					WHERE	nimi = :name ";
		$row = $this->db->query( $query, ['name' => $this->asiakas->yritys] );

		$this->yritys->id = $row['id'];
		$this->yritys->yritysnimi = $row['nimi'];
		$this->yritys->sahkoposti = $row['sahkoposti'];
		$this->yritys->puhelin = $row['puhelin'];
		$this->yritys->y_tunnus = $row['y_tunnus'];
		$this->yritys->katuosoite = $row['katuosoite'];
		$this->yritys->postinumero = $row['postinumero'];
		$this->yritys->postitoimipaikka = $row['postitoimipaikka'];
	}

	protected function haeToimitusosoite() {
		$query = "	SELECT	CONCAT(pysyva_etunimi, ' ', pysyva_sukunimi) AS koko_nimi, pysyva_puhelin, 
						pysyva_sahkoposti, pysyva_katuosoite, pysyva_postinumero, pysyva_postitoimipaikka, pysyva_yritys
					FROM	tilaus_toimitusosoite
					WHERE	tilaus_id = :id ";
		$row = $this->db->query( $query, ['id' => $this->tilaus_nro] );

		$this->toimitusosoite->koko_nimi = $row['koko_nimi'];
		$this->toimitusosoite->sahkoposti = $row['pysyva_sahkoposti'];
		$this->toimitusosoite->puhelin = $row['pysyva_puhelin'];
		$this->toimitusosoite->katuosoite = $row['pysyva_katuosoite'];
		$this->toimitusosoite->postinumero = $row['pysyva_postinumero'];
		$this->toimitusosoite->postitoimipaikka = $row['pysyva_postitoimipaikka'];
	}

	/**  */
	protected function haeTuotteet() {
		$this->tuotteet = array();
		$query = "	SELECT tuote.id, tuote.articleNo, tuote.brandNo, 
						tilaus_tuote.kpl, tilaus_tuote.pysyva_hinta, tilaus_tuote.pysyva_alv, 
						tilaus_tuote.pysyva_alennus,
						( (tilaus_tuote.pysyva_hinta * (1 + tilaus_tuote.pysyva_alv)) 
							* (1 - tilaus_tuote.pysyva_alennus) )
							AS maksettu_hinta
					FROM tilaus_tuote
					LEFT JOIN tuote
						ON tuote.id = tilaus_tuote.tuote_id
					WHERE tilaus_tuote.tilaus_id = :order_id ";
		$this->db->prepare_stmt($query);
		$this->db->run_prepared_stmt(['order_id' => $this->tilaus_nro]);
		$row = $this->db->get_next_row();
		while ( $row ) {
			$tuote = new Tuote();
			$tuote->id = $row['id'];
			$tuote->tuotekoodi = $row['articleNo'];
//			$tuote->tuotenimi = $row['articleNo'];
//			$tuote->valmistaja = $row['brandNo'];
			$tuote->a_hinta = round($row['maksettu_hinta'], 2);
			$tuote->a_hinta_ilman_alv = $row['pysyva_hinta'] * (1 - $row['pysyva_alennus']);
			$tuote->alv_prosentti = (float)$row['pysyva_alv'] * 100;
			$tuote->alennus = (float)$row['pysyva_alennus'] * 100;
			$tuote->kpl_maara = $row['kpl'];
			$tuote->summa = ($row['maksettu_hinta'] * $row['kpl']);
			$this->tuotteet[] = $tuote;
			$row = $this->db->get_next_row();
		}
	}

	protected function laskeHintatiedot() {
		$this->hintatiedot['alv_perus'] = 0;
		$this->hintatiedot['alv_maara'] = 0;
		$this->hintatiedot['tuotteet_yht'] = 0;
		$this->hintatiedot['summa_yhteensa'] = 0;

		foreach ( $this->tuotteet as $tuote ) {
			$this->hintatiedot['alv_perus'] += $tuote->a_hinta_ilman_alv * $tuote->kpl_maara;
			$this->hintatiedot['alv_maara'] += ($tuote->a_hinta - $tuote->a_hinta_ilman_alv) * $tuote->kpl_maara;
			$this->hintatiedot['tuotteet_yht'] += $tuote->summa;
		}

		$this->hintatiedot['summa_yhteensa'] = $this->hintatiedot['tuotteet_yht'] +
												$this->hintatiedot['lisaveloitukset'];
	}

	/**  */
	public function tulostaLasku () {
		$lasku = "";

		$lasku .= "
			<p>
			Tilauksen numero: {$this->tilaus_nro}<br>
			Laskun numero: {$this->laskun_nro}<br>
			Asiakkasnumero: {$this->asiakkaan_id}<br>
			Tilaus tehty: {$this->tilaus_pvm}<br>
			
			Maksutapa: {$this->maksutapa}<br>
			Toimitustapa: {$this->toimitustapa}<br>
			</p>";

		$lasku .= (string)$this->asiakas;

		$lasku .= (string)$this->yritys;

		$lasku .= (string)$this->toimitusosoite;

		foreach ( $this->tuotteet as $tuote ) {
			$lasku .= (string)$tuote;
		}

		$lasku .= "
			<p>
			Hintatiedot:<br>
			ALV-perus: {$this->hintatiedot['alv_perus']} (Summa, josta ALV lasketaan) (Nämä voisi vielä jaotella eri ALV-tasoihin, jos sille on tarvetta)<br>
			ALV-määrä: {$this->hintatiedot['alv_maara']} (Yhteenlaskettu ALV:n määrä)<br>
			Tuotteet yhteensä: {$this->hintatiedot['tuotteet_yht']}<br>
			Lisäveloitukset: {$this->hintatiedot['lisaveloitukset']} (Esim. rahtimaksu)<br>
			Loppusumma yhteensä: {$this->hintatiedot['summa_yhteensa']}<br>
			</p>";

		$lasku .= "
			<p>
			Maksutiedot:
			[Tosin jos kaikki maksavat suoraan, niin näitä ei varmaan sitten tarvita?]";

		return $lasku;
	}

	/**
	 * @return Asiakas
	 */
	public function getAsiakas () {
		return $this->asiakas;
	}

	/**
	 * @return null|Yritys
	 */
	public function getYritys () {
		return $this->yritys;
	}

	/**
	 * @return null|Toimitusosoite
	 */
	public function getToimitusosoite () {
		return $this->toimitusosoite;
	}

	/**
	 * @return array
	 */
	public function getTuotteet () {
		return $this->tuotteet;
	}

	/**
	 * @return array
	 */
	public function getHintatiedot () {
		return $this->hintatiedot;
	}
}

/**
 * Tilauksen tehneen asiakkaan tiedot.
 */
class Asiakas {
	public $id = '[ID]';
	public $koko_nimi = '[Koko nimi]';
	public $sahkoposti = '[Sähköposti]';
	public $puhelin = '[Puhelin]';
	public $yritys = '[Asiakkaan yritys]';

	/**  */
	public function __toString () {
		$string = "
			<p>
			Asiakkaan tiedot:<br>
			Nimi: {$this->koko_nimi}<br>
			Sähköposti: {$this->sahkoposti}<br>
			Puhelin: {$this->puhelin}<br>
			Yritys: {$this->yritys}<br>
			</p>";
		return $string;
	}
}

/**
 * Tilauksen tehneen yrityksen tiedot.
 */
class Yritys {
	public $id = '[ID]';
	public $yritysnimi = '[Yrityksen nimi]';
	public $sahkoposti = '[Sähköposti]';
	public $puhelin = '[Puhelin]';
	public $y_tunnus = '[Y-tunnus]';
	public $katuosoite = '[Katuosoite]';
	public $postinumero = '[Postinumero]';
	public $postitoimipaikka = '[Postitoimipaikka]';

	/**  */
	public function __toString () {
		$string = "
			<p>
			Yrityksen tiedot:<br>
			Nimi: {$this->yritysnimi}<br>
			Sähköposti: {$this->sahkoposti}<br>
			Puhelin: {$this->puhelin}<br>
			Yritys: {$this->y_tunnus}<br>
			Katuosoite: {$this->katuosoite}<br>
			Postinumero: {$this->postinumero}<br>
			Postitoimipaikka: {$this->postitoimipaikka}<br>
			</p>";
		return $string;
	}
}

/**
 * Tilauksen tehneen asiakkaan valitsema toimitusosoite
 */
class Toimitusosoite {
	public $koko_nimi = '[Koko nimi]';
	public $sahkoposti = '[Sähköposti]';
	public $puhelin = '[Puhelin]';
	public $katuosoite = '[Katuosoite]';
	public $postinumero = '[Postinumero]';
	public $postitoimipaikka = '[Postitoimipaikka]';

	/**  */
	public function __toString () {
		$string = "
			<p>
			Toimitusosoitteen tiedot:<br>
			Nimi: {$this->koko_nimi}<br>
			Sähköposti: {$this->sahkoposti}<br>
			Puhelin: {$this->puhelin}<br>
			Katuosoite: {$this->katuosoite}<br>
			Postinumero: {$this->postinumero}<br>
			Postitoimipaikka: {$this->postitoimipaikka}<br>
			</p>";
		return $string;
	}
}

/**
 * Tilattu tuote, ja sen tiedot.
 */
class Tuote {
	public $id = '[ID]';
	public $tuotekoodi = '[Tuotekoodi]';
	public $tuotenimi = '[Tuotteen nimi]';
	public $valmistaja = '[Tuotteen valmistaja]';
	public $a_hinta = '[a-hinta]';
	public $a_hinta_ilman_alv = '[a-hinta ilman ALV]';
	public $alv_prosentti = '[ALV-prosentti]';
	public $alennus = '[ALV-prosentti]';
	public $kpl_maara = '[KPL-määrä]';
	public $summa = '[Tuotteiden summa]';

	/**  */
	public function __toString () {
		$string = "
			<p>
			Tuote: {$this->tuotekoodi} | {$this->tuotenimi} | {$this->valmistaja}<br>
			Hintatiedot: {$this->a_hinta} | {$this->a_hinta_ilman_alv} | {$this->alv_prosentti} | 
				{$this->kpl_maara} | {$this->summa}<br>
			</p>";
		return $string;
	}
}
