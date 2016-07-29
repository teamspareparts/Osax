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
 *
 * @method __construct
 * @method raw_query
 * @method query
 * @method prepare_stmt
 * @method run_prepared_stmt
 * @method get_next_row
 * @method close_prepared_stmt
 * @method __destruct
 */
class DByhteys_luokka {

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
	 * 	_DEF_FETCH_M : Mitä PDO-haku palauttaa (arrayn, objektin, ...)<br>
	 * 	_EMUL_PREP : {@link https://phpdelusions.net/pdo#emulation}
	 * @var array
	 */
	protected $pdo_options = [		//PDO:n DB driver specific options
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false ];
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

	/**
	 * Tämä muuttujaa ei oikeastaan ole käytössä tällä hetkellä.
	 * See link for more info on return types:
	 *	{@link https://phpdelusions.net/pdo/fetch_modes}
	 * @var $returnType <p> possible return types for a PDO query fetch()-function
	 */
	protected $returnType = PDO::FETCH_ASSOC;

	/**
	 * Konstruktori.
	 *
	 * @param string $username
	 * @param string $password
	 * @param string $database
	 * @param string $host optional //TODO: 'localhost' ei saata toimia.
	 */
	public function __construct( /* string */ $username,  /* string */ $password,
			/* string */ $database, /* string */ $host = 'localhost' ) {
		define('FETCH_ALL', TRUE); // Tämä on hieman liioittelua minulta, myönnetään

		$this->pdo_dsn = "mysql:host={$host};dbname={$database};charset=utf8";
		$this->connection = new PDO(
				$this->pdo_dsn, $username, $password, $this->pdo_options );
	}

	/**
	 * Tällä voi suoraan suorittaa sql-haun, kuin mysqli_query($connection, $sql_query).
	 * Huom. Ei todellakaan turvallinen. Älä käytä, jos mitään käyttäjän syötteitä mukana.
	 * @param string $query
	 * @return array
	 */
	public function raw_query( $query ) {
		$db = $this->connection;
		$result = $db->query( $query );
		foreach ( $result as $the_answer ) {
			$results[] = $the_answer;
		}
		return $results;
	}

	/**
	 * Hakee tietokannasta yhden tai useamman rivin prepared stmt:ia käytttäen.
	 * Defaultina hakee yhden rivin. Jos tarvitset useamman, huom. kolmas parametri.
	 * @param string $query
	 * @param array $values optional, default=NULL<p>
	 * 		muuttujien tyypilla ei ole väliä. PDO muuttaa ne stringiksi,
	 * 		jotka sitten lähetetään tietokannalle.
	 * @param bool $fetch_All_Rows optional, default=FALSE<p>
	 * 		haetaanko kaikki rivit, vai vain yksi.
	 * @return array ( results | empty ) <p> assoc array, to be precise
	 */
	public function query( /* string */ $query, array $values = NULL,
			/* bool */ $fetch_All_Rows = FALSE ) {
		$db = $this->connection;

		$stmt = $db->prepare( $query );	// Valmistellaan query
		$stmt->execute( $values );		//Toteutetaan query varsinaisilla arvoilla

		if ($fetch_All_Rows) { // Jos arvo asetettu, niin haetaan kaikki saadut rivit
			$result = $stmt->fetchAll( $this->returnType );
			$stmt->closeCursor();

		} else { //Muuten haetaan vain ensimmäinen saatu rivi, ja palautetaan se.
			$result = $stmt->fetch( $this->returnType );
			$stmt->closeCursor();
		}

		return $result;
	}

	/**
	 * Valmistelee erillisen haun, jota voi sitten käyttää run_prep_stmt()-metodilla.
	 * @param string $query
	 * @return void
	 */
	public function prepare_stmt( /* string */ $query ) {
		$db = $this->connection;
		$this->prepared_stmt = $db->prepare( $query );
	}

	/**
	 * Suorittaa valmistellun sql-queryn (valmistelu prepare_stmt()-metodissa)
	 * @param array $values optional, default=NULL<p>
	 * 		queryyn upotettavat arvot
	 * @return void<p> käytä get_next_row()-metodia saadaksesi tuloksen
	 */
	public function run_prepared_stmt( array $values = NULL ) {
		$stmt = $this->prepared_stmt;
		$stmt->execute( $values );
	}

	/**
	 * Palauttaa seuraavan rivin viimeksi tehdystä hausta.
	 * Huom. ei toimi query()-metodin kanssa. Käytä vain prep.stmt -metodien kanssa.<br>
	 * Lisäksi, toisen haun tekeminen millä tahansa muulla metodilla nollaa tulokset.
	 * @return array( results|empty )
	 */
	public function get_next_row() {
		$stmt = $this->prepared_stmt;
		$results = $stmt->fetch( $this->returnType );
		return $results;
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
		$this->username = NULL;
		$this->password = NULL;
		$this->host = NULL;
		$this->database = NULL;
	}
}

/**
 * Sisältää esimerkkejä käytöstä käytännössä.
 * Lisäksi lopussa on pari trivia tietoa luokasta. <p>
 * (Please don't try running these. They won't work.)
 */
class examples_and_information {

	/**
	 * Miten asiat tehtiin ennen. Esimerkin vuoksi tässä.
	 */
	function example_0_before() {
		$mysqli_conn = mysqli_connect('host', 'username', 'password', 'tietokanta')
				or die('Error: ' . mysqli_connect_error());

		$value = ''; //Some user input
		$simple_query = "	SELECT	*
							FROM	table
							WHERE	column = {$value}; "; //Turvallisuusriski

		$result = mysqli_query($mysqli_conn, $simple_query) or die(mysqli_error($mysqli_conn));
		$result = mysqli_fetch_assoc($result);

		if ( $result ) {
			//Do stuff and things
		}
	}

	function example_1( $db_conn /* Älä välitä tästä, säästää yhden rivin koodia */ ) {
		$values_array = [ $user_input ]; // An array of some user inputs
		$query = "	SELECT	*
					FROM	table
					WHERE	column = ? "; //Huom. ei puolipistettä ";"

		$result = $db_conn->query( $query, $values_array );

		if ( $result ) {
			//Do stuff and things
		}

		/*
		 * Huom. Tällä tavalla haettuna metodi palauttaa tiedot muodossa:
			Array (
				[id] => 'foo'
				...
			)
		 */
	}

	function example_2( $db_conn ) {
		$values_array = [ $user_input ]; // An array of some user inputs
		$query = "	SELECT	*
					FROM	table
					WHERE	column = ?"; //Huom. ei puolipistettä ";"

		$results = $db_conn->query( $query, $values_array, FETCH_ALL /*Alias TRUE:lle*/ );

		foreach ( $results as $array ) {
			//Do stuff with the assoc array
		}

		/*
		 * Huom. Tällä tavalla haettuna metodi palauttaa tiedot muodossa:
			Array (
				[0] => Array (
			            [id] => 'foo'
			            ...
					)
				[1] => Array (
			            [id] => 'bar'
			            ...
					)
				...
			)
		 */
	}

	/**
	 * Complicated way, or at least more verbose
	 */
	function example_3( $db_conn ) {
		$values_array = [ $user_input ]; // An array of some user inputs
		$query = "	SELECT	*
					FROM	table
					WHERE	column = ?"; //Huom. ei puolipistettä ";"

		$db_conn->prepare_stmt( $query ); //Valmistellaan sql-haku

		$db_conn->run_prepared_stmt( $values_array ); //Ajetaan haku syötteillä


		while ( $result = $db_conn->get_next_row() ) {
			//Do stuff with the received assoc array
		}

		/* Jos haluat ajaa uudestaan saman haun, mutta eri arvoilla... */
		$db_conn->run_prepared_stmt( $different_values_array );
		//Ajetaan haku eri syötteillä, ja sen jälkeen sama while-/if-lause

		/*
		 * Huom. Tällä tavalla haettuna metodi palauttaa tiedot samalla tavalla kuin
		 *  ekassa esimerkissä. Jos odotat vain yhtä riviä, voit myös käyttää if-lausetta,
		 *  mutta sillä ei ole hirveästi väliä kumpaa käyttää.
		 */

		/*
		 * Luokassa on myös close_prepared_stmt()-metodi, mutta sitä ei oikeastaan tarvitse käyttää.
		 */
		$db_conn->close_prepared_stmt(); //Sulkee statementin
	}

	/**
	 * Some interesting trivia about the class, and it's functions.
	 *
	 * Jos luit linkin aivan tiedoston alussa PDO:sta, tämä toistaa
	 * aika paljon siitä.
	 */
	function example_4_interesting_trivia( $db_conn ) {

		/*
		 * Kaikki nämä seuraavat sql_haut toimivat:
		 */
		$query_no_user_input = "	SELECT	*
									FROM	table ";

		$query_with_user_input = "	SELECT	*
									FROM	table
									WHERE	column = {$value} ";
		$db_conn->query( $query_no_user_input ); //tai query( $query, NULL )
		$db_conn->query( $query_with_user_input ); //tai query( $query, NULL )

		$db_conn->prepare_stmt( $query_no_user_input );
		$db_conn->prepare_stmt( $query_with_user_input );
		$db_conn->run_prepared_stmt(); //tai run_prep_stmt( NULL )
		/*
		 * Tämän takia raw_query()-metodi on hieman turha.
		 */


		/*
		 * SQL-kyselyn voi tehdä myös nimetyilla placeholdereilla
		 */
		$query = "	SELECT	*
					FROM	table
					WHERE	column = :value ";
		/* ... missä tapauksessa $values -array pitää olla assoc array:  */
		$values_array = [ 'value' => $user_input ]; //Key samanniminen kuin placeholder
		/*
		 * Tässä tapauksessa niiden ei tarvitse olla samassa järjestyksessä.
		 * Nimettyjä ja kysymysmerkkejä ei voi käyttää samassa queryssa.
		 */


		/*
		 * Esimerkissä 2 käytin FETCH_ALL muuttujaa. Se on alias TRUE:lle.
		 * Define()-metodi siitä on konstruktorissa. Tämä kohta on hieman leikkimistä minulta, myönnetään
		 */
		FETCH_ALL === TRUE;


		/*
		 * Luokan muuttujissa on $returnType. Se ei ole käytössä parametrina missään, mutta teoreettisesti
		 *  sillä voisi palauttaa tuloksen hyvin monella eri tavalla. Jos ominaisuudelle on tarvetta, se ei
		 *  olisi vaikea lisätä. Funktioiden fetch()-metodit jo käyttävät parametrina $this->returnType:ia.
		 */
		$pdo_options = [ PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC ];
		$returnType = PDO::FETCH_ASSOC; // Nämä kaksi toistaa itseään, jos ihmettelit asiaa.
		// Niillä ei siis oikeastaan ole mitään funktionaalista eroa.


		/*
		 * Syy miksi käytän PDO:ta, enkä MySQLi:ta (jossa on myös prep. stmt), on seuraava rivi:
		 */
		//Prepare
		$db_conn->bindParam( $value_types, $value1, $value2 /*, ...*/ ); //Tämä funktio on PDO:ssa ja mySQLi:ssa
		//Execute
		/*
		 * Tällä tavalla tehtynä minun pitäisi selvittää, miten monta muuttujaa annetaan, koska tuo metodi
		 *  ei hyväksy parametrina arrayta.
		 * PDO:ssa execute()-metodi hyväksyy arrayn, jossa muuttujat. Kaikki annetut muuttujat muutetaan merkkijonoksi PHP puolella.
		 * Oletettavasti tietokanta sitten muuttaa ne tarvittaviin muotoihin takaisin.
		 *
		 * Plus lisäksi, siinä pitäisi joko antaa myös arvojen tyypit, tai selvittää luokassa,
		 *  mitä tyyppiä ne on. Monimutkaistaa tilannetta. Tämä on helpompaa
		 *
		 * (Minä oikeastaan juur luin läpi PHP-manuaalia, ja tämä ei oikeastaan ole 100 % totta.)
		 */
	}
}
//EOF