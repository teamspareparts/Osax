<?php
/**
 * Luokka Tietokannan yhteyden käsittelyä varten PDO:n avulla.
 *
 * Link for more info on PDO: https://phpdelusions.net/pdo
 *
 * Ajattelin, lisäksi pistää tämän luokan jälkeen toisen luokan,
 * 	jossa on esimerkkejä käytöstä.
 *
 * @name DByhteys_luokka
 * @author jjarv
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
	 * ATTR_* : attribuutti
	 * 		ERRMODE : Miten PDO-yhteys toimii virhetilanteissa.
	 * 		DEF_FETCH_M : Mitä PDO-haku palauttaa (arrayn, objektin, ...)
	 * 		EMUL_PREP : https://phpdelusions.net/pdo#emulation
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
	 *	https://phpdelusions.net/pdo/fetch_modes
	 * @var $returnType; possible return types for a PDO query fetch()-function
	 */
	protected $returnType = PDO::FETCH_ASSOC;

	/**
	 * Konstruktori. Oletan, että tiedät mikä se on.
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
	 *
	 * @param string $query
	 * @param array $values optional;
	 * 		muuttujien tyypilla ei ole väliä. PDO muuttaa ne stringiksi,
	 * 		jotka sitten lähetetään tietokannalle.
	 * @param bool $fetch_All_Rows optional;
	 * 		haetaanko kaikki rivit, vai vain yksi.
	 * @return array(results|empty); assoc array, to be precise
	 */
	public function query( /* string */ $query, array $values = NULL,
			/* bool */ $fetch_All_Rows = false ) {
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
	 * @param array $values optional; queryyn upotettavat arvot
	 * @return void; käytä get_next_row()-metodia saadaksesi tuloksen
	 */
	public function run_prepared_stmt( array $values = NULL ) {
		$stmt = $this->prepared_stmt;
		$stmt->execute( $values );
	}

	/**
	 * Palauttaa seuraavan rivin viimeksi tehdystä hausta.
	 * Huom. ei toimi query()-metodin kanssa. Toisen haun tekeminen nollaa tulokset.
	 * @return array( results|empty ); assoc array
	 */
	public function get_next_row() {
		$stmt = $this->prepared_stmt;
		$results = $stmt->fetch( $this->returnType );
		return $results;
	}

	/**
	 * Sulkee valmistellun PDOstatementin.
	 * Enimmäkseen käytössä vain __destructorissa
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
 * Coming later...
 */
class examples_and_information {
	var $select = "	SELECT	*
					FROM	table
					WHERE	column = value;";

	var $delete = "	DELETE
					FROM	table
					WHERE	column = value;";
	var $insert = "	INSERT
					INTO	table
					VALUES	(value1, value2, ...);";
	var $update = "	UPDATE	table
					SET		column1 = value1, column2 = value2, ...
					WHERE	column = value;";
	var $insert_into = "
					INSERT INTO table2
						(column2(s))
					SELECT column1(s)
					FROM table1
					WHERE column1 = value;";
}
//EOF