<?php
/**
 * Luokka Tietokannan yhteyden käsittelyä varten PDO:n avulla.
 *
 * Link for more info on PDO: {@link https://phpdelusions.net/pdo}<br>
 * Link to PHP-manual on PDO: {@link https://secure.php.net/manual/en/book.pdo.php}
 *
 * Tiedoston lopussa toinen luokka, jossa esimerkkejä käytöstä.
 * Siinä on myös joitain yksinkertaisia selityksiä, jotka on myös ekassa
 * ylhäällä olevassa linkissä.
 *
 * Käytän PDO:ta, koska se on yksinkertaisempaa käyttää prep. stmt:n kanssa.
 */
class DByhteys {

	/**
	 * PDO:n yhteyden luontia varten, sisältää tietokannan tiedot.
	 * 	"mysql:host={$host};dbname={$database};charset={$charset}"
	 * @var string
	 */
	protected $pdo_dsn = '';		//PDO:n yhdistämistä varten
	/**
	 * Optional options for the PDO connection, given at new PDO(...).
	 * ATTR_* : attribuutti<br>
	 * 	_ERRMODE : Miten PDO-yhteys toimii virhetilanteissa.<br>
	 * 	_DEF_FETCH_M : Mitä PDO-haku palauttaa defaultina (arrayn, objektin, ...)<br>
	 * 	_EMUL_PREP : {@link https://phpdelusions.net/pdo#emulation}
	 * @var array
	 */
	protected $pdo_options = [		//PDO:n DB driver specific options
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
			PDO::ATTR_EMULATE_PREPARES   => false,
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")];
	/**
	 * Säilyttää yhdeyten, jota kaikki metodit käyttävät
	 * @var PDO object
	 */
	protected $connection = NULL;	//PDO connection
	/**
	 * PDO statement, prepared statementien käyttöä varten.
	 * Tämä muuttuja on käytössä prepare_stmt(), run_prepared_stmt(),
	 *  get_next_row() ja close_prepared_stmt() metodien välillä.
	 * Raw_query() ja query() metodit käyttävät erillistä objektia.
	 * Mikä on kyllä hieman turhaa, nyt kun mietin asiaa. Look, tässä
	 *  luokassa on aika monta asiaa, joita voisi hieman hioa.
	 * @var PDOStatement object
	 */
	protected $prepared_stmt = NULL;//Tallennettu prepared statement

	const FETCH_ALL = TRUE;

	/**
	 * Konstruktori.
	 *
	 * @param string $username
	 * @param string $password
	 * @param string $database
	 * @param string $host [optional] //TODO: 'localhost' ei saata toimia.
	 */
	public function __construct( /*string*/ $username, /*string*/ $password,
			/*string*/ $database, /*string*/ $host = 'localhost' ) {

		define('FETCH_ALL', TRUE); // Tämä on hieman liioittelua minulta, myönnetään.
		$this->pdo_dsn = "mysql:host={$host};dbname={$database};charset=utf8";
		$this->connection = new PDO(
				$this->pdo_dsn, $username, $password, $this->pdo_options );
	}

	/**
	 * Hakee tietokannasta yhden tai useamman rivin prepared stmt:ia käytttäen.
	 * Defaultina hakee yhden rivin. Jos tarvitset useamman, huom. kolmas parametri.<p>
	 * Huom. Liian suurilla tuloksilla saattaa kaatua. Älä käytä FetchAll:ia jos odotat kymmeniä tuhansia tuloksia.<p>
	 * Ilman neljättä parametria palauttaa tuloksen geneerisenä objektina.
	 * @param string $query
	 * @param array $values [optional], default=NULL<p>
	 * 		muuttujien tyypilla ei ole väliä. PDO muuttaa ne stringiksi,
	 * 		jotka sitten lähetetään tietokannalle.
	 * @param bool $fetch_All_Rows [optional], default=FALSE<p>
	 * 		haetaanko kaikki rivit, vai vain yksi.
	 * @param int $returnType [optional], default = NULL <p>
	 * 		Missä muodossa haluat tiedot palautettavan.
	 * 		Huom. vaatii oikean muotoilun, esim. PDO::FETCH_ASSOC, ilman lainausmerkkejä.<br>
	 * 		Mahdolliset (tärkeimmät) arvot:
	 * 		<ul>	<li>PDO::FETCH_ASSOC)</li>
	 * 				<li>PDO::FETCH_NUM</li>
	 * 				<li>PDO::FETCH_BOTH</li>
	 * 				<li>PDO::FETCH_OBJ (huom. default jo valmiiksi</li>
	 * 				<li>PDO::FETCH_LAZY</li>
	 * 		</ul>
	 * @return array|boolean|stdClass <p> Palauttaa arrayn, jos esim. SELECT.<br>
	 * 		Palauttaa boolean, jos esim. INSERT tai DELETE.<br>Palauttaa objektin, jos haetaan vain yksi,
	 * 		ja palautusmuotona on PDO::FETCH_OBJ. //TODO: Update doc
	 */
	public function query( /*string*/ $query, array $values = NULL,
			/*bool*/ $fetch_All_Rows = FALSE, /*int*/ $returnType = NULL ) {
		$db = $this->connection;

		$q_type = substr( ltrim($query), 0, 6 ); // Kaikki haku-tyypit ovat 6 merkkiä pitkiä. Todella käytännöllistä.

		$stmt = $db->prepare( $query );		// Valmistellaan query
		$result = $stmt->execute( $values );//Toteutetaan query varsinaisilla arvoilla

		if ( $q_type === "SELECT" ) { //Jos select, haetaan array
			if ( $fetch_All_Rows ) { // Jos arvo asetettu, niin haetaan kaikki saadut rivit
				$result = $stmt->fetchAll( $returnType );
				$stmt->closeCursor();

			} else { //Muuten haetaan vain ensimmäinen saatu rivi, ja palautetaan se.
				$result = $stmt->fetch( $returnType );
				$stmt->closeCursor();
			}
		} //Jos ei, palautetaan boolean execute()-metodilta

		return $result;
	}

	/**
	 * Valmistelee erillisen haun, jota voi sitten käyttää run_prep_stmt()-metodilla.
	 * @param string $query
	 */
	public function prepare_stmt( /* string */ $query ) {
		$this->prepared_stmt = $this->connection->prepare( $query );
	}

	/**
	 * Suorittaa valmistellun sql-queryn (valmistelu prepare_stmt()-metodissa).
	 * Hae tulos get_next_row()-metodilla.
	 * @param array $values [optional], default=NULL<p>
	 *        queryyn upotettavat arvot
	 * @return bool
	 */
	public function run_prepared_stmt( array $values = NULL ) {
		return $this->prepared_stmt->execute( $values );
	}

	/**
	 * Palauttaa seuraavan rivin viimeksi tehdystä hausta.
	 * Huom. ei toimi query()-metodin kanssa. Käytä vain prep.stmt -metodien kanssa.<br>
	 * Lisäksi, toisen haun tekeminen millä tahansa muulla metodilla nollaa tulokset.
	 * Palauttaa tulokset objektina, jos ei palautustyyppiä.
	 * @param int $returnType [optional], default = NULL <p>
	 * 		Missä muodossa haluat tiedot palautettavan.
	 * 		Huom. vaatii oikean muotoilun, esim. PDO::FETCH_ASSOC, ilman lainausmerkkejä.<br>
	 * 		Mahdolliset arvot:
	 * 		<ul>	<li>PDO::FETCH_ASSOC</li>
	 * 				<li>PDO::FETCH_NUM</li>
	 * 				<li>PDO::FETCH_BOTH</li>
	 * 				<li>PDO::FETCH_OBJ (huom. default jo valmiiksi)</li>
	 * 				<li>PDO::FETCH_LAZY</li>
	 * 		</ul>
	 * Niitä on muitakin, mutta nuo on tärkeimmät.
	 * @return stdClass
	 */
	public function get_next_row ( /* mixed */ $returnType = NULL ) {
		return $this->prepared_stmt->fetch( $returnType );
	}

	/**
	 * Metodilla voi muuttaa missä muodossa kaikki luokan sql-haut palautetaan.
	 *
	 * Mahdolliset tyypit:
	 * <ul>
	 * 	<li>'enum' : an array indexed by column number as returned in your result set, starting at 0 </li>
	 * 	<li>'object' : returns an object with variables corresponding to the column names </li>
	 * 	<li>'key_pair' : only use with two columns. Same principle as assoc array, but first column is key. </li>
	 * </ul>
	 * Jos metodi kutsutaan ilman parametria, tai parametria ei tunnisteta, niin:
	 * <ul><li>returnType = Assoc array</li></ul>
	 * @param string $type [optional], default = NULL <p> haluttu tyyppi
	 */
	public function setReturnType( /* string */ $type = NULL ) {
		switch ( $type ) {
			case 'enum':
				$this->connection->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_NUM );
				break;
			case 'both':
				$this->connection->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_BOTH );
				break;
			case 'assoc':
				$this->connection->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC );
				break;
			case 'key_pair':
				$this->connection->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_KEY_PAIR );
				break;
			default:
				$this->connection->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ );
				break;
		}
	}

	/**
	 * Sulkee valmistellun PDOstatementin.
	 * Enimmäkseen käytössä vain __destructorissa, mutta jos joku haluaa varmistaa.
	 */
	public function close_prepared_stmt() {
		if ( $this->prepared_stmt ) {
			$this->prepared_stmt->closeCursor();
			$this->prepared_stmt = NULL;
		}
	}

	/**
	 * Hävittää kaikki jäljet objektista.
	 * Tätä metodia ei ole tarkoitus kutsua ohjelman ajon aikana.
	 */
	function __destruct() {
		$this->close_prepared_stmt();
		$this->connection = NULL;
	}

	/**
	 * Palauttaa PDO-yhteyden manuaalia käyttöä varten.
	 * @return PDO connection
	 */
	public function getConnection () {
		return $this->connection;
	}
}

/**
 * Lopuksi joitain esimerkkejä, plus lopussa
 *  on pari trivia tietoa luokasta.
 */

/*******
  Esimerkki 1 (simple_query_with_single_result):
$query = "	SELECT	*
			FROM	table
			WHERE	column = ? "; //Huom. ei puolipistettä ";"
$values_array = [ 'user_input' ];
$result = $db_conn->query( $query, $values_array );
if ( $result ) {
	//Do stuff and things
}

Huom. Tällä tavalla haettuna metodi palauttaa tiedot objekti-muodossa:
	foo->column_name
*******/


/********
  Esimerkki 2 (simple_query_with_multiple_results):
$query = "	SELECT	*
			FROM	table
			WHERE	column = ? "; //Huom. ei puolipistettä ";"
$values_array = [ 'user_input' ]; // An array of some user inputs

$results = $db_conn->query( $query, $values_array, FETCH_ALL (Alias TRUE:ll) );

foreach ( $results as $array ) {
	//Do stuff with the assoc array
}


Huom. Tällä tavalla haettuna metodi palauttaa tiedot (assoc array) muodossa:
	foo->column_name1
	foo->column_name2

******/

/*****
  Example 3 (more_verbose_version):
$values_array = [ 'user_input' ]; // An array of some user inputs
$query = "	SELECT	*
			FROM	table
			WHERE	column = ?"; //Huom. ei puolipistettä ";"

$db_conn->prepare_stmt( $query ); //Valmistellaan sql-haku

$db_conn->run_prepared_stmt( $values_array ); //Ajetaan haku syötteillä
$result = $db_conn->get_next_row()
while ( $result ) {
	//Do stuff with the received assoc array
	$result = $db_conn->get_next_row()
}

// Jos haluat ajaa saman haun, mutta eri arvoilla...
$db_conn->run_prepared_stmt( ['different_input'] );
//Ajetaan haku eri syötteillä, ja sen jälkeen sama while-/if-lause

Huom. Tällä tavalla haettuna metodi palauttaa tiedot samalla tavalla kuin
ekassa esimerkissä. Jos odotat vain yhtä riviä, voit myös käyttää if-lausetta,
mutta sillä ei ole hirveästi väliä kumpaa käyttää.
******/

/*******
  Example 4 (tietoa eri palautustyypeistä):
Ei esimerkkejä, koska testaillessani, suurin osa niistä
on oikeastaan aika tylsiä. Pari on näyttää täysin samalta,
paitsi jos tietokanta on suunniteltu oudosti.

// Jos haluat valita tietyn tietyn palautustyypin...
$db_conn->query( $query, $values, [FETCH_ALL || NULL], PDO::FETCH_ASSOC );
// Tai...
$db_conn->setReturnType( 'object' ); // Vaihtaa koko luokan palautustyypin. Pysyvä muutos.
$db_conn->query( $query, $values ); // Palauttaa nyt objektina

// Jos haluat vaihtaa takaisin defaultiin...
$db_conn->setReturnType( ['' || NULL || 'assoc'] );
Haluaisin huomauttaa, että tuo syntaksi ei todellakaan ole mikään
standardi. Minä keksin sen tyhjästä juuri äsken.


 * FETCH_LAZY: ei voi käyttää fetchAll():in kanssa.
 *
 * FECTH_KEY_PAIR: vaatii tasan kaksi valittua kolumnia.
 * 		Ensimmäinen kolumni on avain, toinen arvo.
 *
 * FETCH_UNIQUE: sama idea kuin FETCH_ASSOC fetchAll():in kanssa.
 * 		Valitsee ensimmäisen kolumin arvon, ja tekee siitä kyseisen arrayn avaimen.
 * 		Ei palauta ensimmäistä kolumnia. Jos duplikaatteja, palauttaa viimeisen.
 * 		Ei voi käyttää fetch():in kanssa (single result, that is)
 *
 * OBJ on aika ilmiselvä. ENUM kanssa.
 * NAMED palauttaa saman kuin ASSOC, mutta jos duplikaatti kolumneja, tekee
 *  niistä oman arrayn (inside the first array).
 * BOTH vaan palauttaa koko arrayn tuplana. Mitä hyötyä siitä on?
 *
 * Suurin osa näistä on aika esoteerisia käyttötarkoitukseltaan.
 * [later comment from me:] Edes minä en enää muista mitä nää kaikki oli.
 * 		Miksi edes vaivauduin kirjoittamaan tämän?

******/

/*******
  Example 5: Some Interesting trivia bout the class
 * Jos luit linkin aivan tiedoston alussa PDO:sta, tämä toistaa
 * aika paljon siitä.

//
// Kaikki nämä seuraavat sql_haut toimivat:
//
$query_no_user_input = "SELECT	*
						FROM	table ";

$query_with_user_input = "	SELECT	*
							FROM	table
							WHERE	column = {$value} ";
$db_conn->query( $query_no_user_input ); //tai query( $query, NULL )
$db_conn->query( $query_with_user_input ); //tai query( $query, NULL )

$db_conn->prepare_stmt( $query_no_user_input );
$db_conn->prepare_stmt( $query_with_user_input );
$db_conn->run_prepared_stmt(); //tai run_prep_stmt( NULL )

 * Tätä tyyliä ei tietenkään suositella, jos mukana user inputteja.
 * Mutta jos jonkin prep stmt:n kanssa on ongelmia, niin voit vain pistää koko
 * jutun tuolla tavalla.



//
// SQL-kyselyn voi tehdä myös nimetyilla placeholdereilla
//
$query = "	SELECT	*
			FROM	table
			WHERE	column = :value ";
// ... missä tapauksessa $values-array pitää olla assoc array:
$values_array = [ 'value' => 'user_input' ]; //Key samanniminen kuin placeholder
$db_conn->query( $query, $values_array );

 * Tässä tapauksessa niiden ei tarvitse olla samassa järjestyksessä.
 * Nimettyjä ja kysymysmerkkejä ei voi käyttää samassa queryssa.



 * Esimerkissä 2 käytin FETCH_ALL muuttujaa. Se on alias TRUE:lle.
 * Define()-metodi siitä on tiedoston alussa. Tämä kohta on hieman leikkimistä minulta, myönnetään.
FETCH_ALL === TRUE;


//
// Syy miksi käytän PDO:ta, enkä MySQLi:ta (jossa on myös prep. stmt), on seuraava rivi:
//
(Prepare_stmt)
$db_conn->bindParam( $value_types, $value1, $value2, ... ); //Tämä funktio on PDO:ssa ja mySQLi:ssa
(Execute)

 * Tällä tavalla tehtynä minun pitäisi selvittää, miten monta muuttujaa annetaan, koska tuo metodi
 *  ei hyväksy parametrina arrayta.
 * PDO:ssa execute()-metodi hyväksyy arrayn, jossa muuttujat. Kaikki annetut muuttujat muutetaan
 * merkkijonoksi PHP puolella.
 * Oletettavasti tietokanta sitten muuttaa ne tarvittaviin muotoihin takaisin.
 *
 * Plus lisäksi, siinä pitäisi joko antaa myös arvojen tyypit, tai selvittää luokassa,
 *  mitä tyyppiä ne on. Monimutkaistaa tilannetta. Tämä on helpompaa
 *
 * (Minä oikeastaan juur luin läpi PHP-manuaalia, ja tämä ei oikeastaan ole 100 % totta. But close enough.)
*****/
//EOF
