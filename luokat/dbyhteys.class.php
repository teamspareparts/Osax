<?php declare(strict_types=1); //
/**
 * Luokka Tietokannan yhteyden käsittelyä varten PDO:n avulla.
 *
 * Link for more info on PDO: {@link https://phpdelusions.net/pdo}<br>
 * Link to PHP-manual on PDO: {@link https://secure.php.net/manual/en/book.pdo.php}
 */
class DByhteys {

	/**
	 * PDO:n yhteyden luontia varten, sisältää tietokannan tiedot.<br>
	 *    "mysql:host={$host};dbname={$database};charset={$charset}"
	 * @var string
	 */
	protected $pdo_dsn = '';
	/**
	 * Optional options for the PDO connection, given at new PDO(...).
	 * ATTR_* : attribuutti<br>
	 *    _ERRMODE : Miten PDO-yhteys toimii virhetilanteissa.<br>
	 *    _DEF_FETCH_M : Mitä PDO-haku palauttaa defaultina (arrayn, objektin, ...)<br>
	 *    _EMUL_PREP : {@link https://phpdelusions.net/pdo#emulation}
	 * @var array
	 */
	protected $pdo_options = [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
		PDO::ATTR_EMULATE_PREPARES => false,
		PDO::MYSQL_ATTR_FOUND_ROWS => true ];
	/**
	 * Säilyttää yhdeyten, jota kaikki metodit käyttävät
	 * @var PDO object
	 */
	protected $connection = null;
	/**
	 * PDO statement, prepared statementien käyttöä varten.
	 * Tämä muuttuja on käytössä prepare_stmt(), run_prepared_stmt(),
	 *  get_next_row() ja close_prepared_stmt() metodien välillä.
	 * Qquery() metodit käyttävät erillistä objektia.
	 * @var PDOStatement object
	 */
	protected $prepared_stmt = null;

	const FETCH_ALL = true;

	/**
	 * Konstruktori.
	 * Lukee tarvittavat tiedot suoraan config.ini -tiedostosta.
	 * @param array $config       [optional] <p> Kenttien järjestys: <br>
	 *                            Jos enum: host, name, user, pass (sama kuin config.ini)<br>
	 *                            Jos assoc: ei väliä, mutta nimet pitää olla samat kuin config.ini.
	 * @param string $iniFileName [optional], default = "./config/config.ini.php" <p>
	 *                            Muuta, jos käytät tiedostoa jossain muussa kansiossa kuin ./root.
	 */
	public function __construct( array $config = null, string $iniFileName = './config/config.ini.php' ) {
		define( 'FETCH_ALL', true );
		if ( $config === null ) {
			$config = parse_ini_file( $iniFileName );
		}
		elseif ( isset( $config[ 0 ] ) ) {
			$config = [ 'host' => $config[ 0 ], 'name' => $config[ 1 ], 'user' => $config[ 2 ], 'pass' => $config[ 3 ] ];
		}
		$this->pdo_dsn = "mysql:host={$config[ 'host' ]};dbname={$config[ 'name' ]};charset=utf8";
		$this->connection = new PDO( $this->pdo_dsn, $config[ 'user' ], $config[ 'pass' ], $this->pdo_options );
	}

	/**
	 * Suorittaa SQl-koodin prepared stmt:ia käytttäen. Palauttaa haetut rivit (SELECT),
	 * tai muutettujen rivien määrän muussa tapauksessa. <br>
	 * Defaultina palauttaa yhden rivin. Jos tarvitset useamman, huom. kolmas parametri.<p><p>
	 * Huom. Liian suurilla tuloksilla saattaa kaatua. Älä käytä FetchAll:ia jos odotat kymmeniä tuhansia tuloksia.<p>
	 * Ilman neljättä parametria palauttaa tuloksen geneerisenä objektina.
	 *
	 * @param string $query
	 * @param array  $values         [optional], default = null <p>
	 *                               Muuttujien tyypilla ei ole väliä. PDO muuttaa ne stringiksi, jotka sitten
	 *                               lähetetään tietokannalle.
	 * @param bool   $fetchAllRows   [optional], default = false <p>
	 *                               Haetaanko kaikki rivit, vai vain yksi.
	 * @param int    $returnType     [optional], default = null <p>
	 *                               Missä muodossa haluat tiedot palautettavan. Käyttää PDO-luokan
	 *                               PDO::FETCH_* constant-muuttujia. <br> Default on PDO::FETCH_OBJ.
	 * @param string $className      [optional] <p> Jos haluat jonkin tietyn luokan olion. <p>
	 *                               Huom: $returnType ei tarvitse olla määritelty.<p>
	 *                               Huom: haun muuttujien nimet pitää olla samat kuin luokan muuttujat.
	 *
	 * @return array|int|bool|stdClass <p> Palauttaa stdClass[], jos SELECT ja FETCH_ALL==true.
	 *                               Palauttaa stdClass-objektin, jos haetaan vain yksi.<br>
	 *                               Palauttaa <code>$stmt->rowCount</code> (muutettujen rivien määrä), jos esim.
	 *                               INSERT tai DELETE.<br>
	 */
	public function query( string $query, array $values = [], bool $fetchAllRows = false,
						   int $returnType = null, string $className = '' ) {
		// Katsotaan mikä hakutyyppi kyseessä, jotta voidaan palauttaa hyödyllinen vastaus tyypin mukaan.
		$q_type = substr( ltrim( $query ), 0, 6 ); // Kaikki haku-tyypit ovat 6 merkkiä pitkiä. Todella käytännöllistä.

		$stmt = $this->connection->prepare( $query );    // Valmistellaan query
		$stmt->execute( $values ); //Toteutetaan query varsinaisilla arvoilla

		if ( $q_type === "SELECT" ) {
			if ( $fetchAllRows ) {
				if ( empty( $className ) ) {
					return $stmt->fetchAll( $returnType ?? PDO::FETCH_OBJ );
				}
				else { // Palautetaan tietyn luokan olioina
					return $stmt->fetchAll( PDO::FETCH_CLASS, $className );
				}
			}
			else { // Haetaan vain yksi rivi
				if ( empty( $className ) ) {
					return $stmt->fetch( $returnType ?? PDO::FETCH_LAZY );
				}
				else { // Palautetaan tietyn luokan oliona.
					return $stmt->fetchObject( $className );
				}
			}
		}
		else { // Palautetaan muutettujen rivien määrän.
			return $stmt->rowCount();
		}
	}

	/**
	 * Valmistelee erillisen haun, jota voi sitten käyttää {@see run_prep_stmt()}-metodilla.
	 * @param string $query
	 */
	public function prepare_stmt( string $query ) {
		$this->prepared_stmt = $this->connection->prepare( $query );
	}

	/**
	 * Suorittaa valmistellun sql-queryn (valmistelu {@see prepare_stmt()}-metodissa).
	 * Hae tulos {@see get_next_row()}-metodilla.
	 * @param array $values [optional], default=NULL<p>
	 *                      queryyn upotettavat arvot
	 * @return bool
	 */
	public function run_prepared_stmt( array $values = [] ) : bool {
		return $this->prepared_stmt->execute( $values );
	}

	/**
	 * Palauttaa seuraavan rivin viimeksi tehdystä hausta.
	 * Huom. ei toimi query()-metodin kanssa. Käytä vain prep.stmt -metodien kanssa.<br>
	 * Lisäksi, toisen haun tekeminen millä tahansa muulla metodilla nollaa tulokset.
	 * Palauttaa tulokset objektina, jos ei palautustyyppiä.
	 * @param int    $returnType [optional] <p> Missä muodossa haluat tiedot palautettavan. Default on PDO::FETCH_OBJ.
	 * @param string $className  [optional] <p> Jos haluat jonkin tietyn luokan olion. <p>
	 *                           Huom: $returnType ei tarvitse olla määritely.<p>
	 *                           Huom: haun muuttujien nimet pitää olla samat kuin luokan muuttujat.
	 * @return mixed|stdClass
	 */
	public function get_next_row( int $returnType = null, string $className = '' ) {
		return (empty( $className ))
			? $this->prepared_stmt->fetch( $returnType ?? PDO::FETCH_LAZY )
			: $this->prepared_stmt->fetchObject( $className ) ;
	}

	/**
	 * Palauttaa PDO-yhteyden manuaalia käyttöä varten.
	 * @return PDO connection
	 */
	public function getConnection () : PDO {
		return $this->connection;
	}
}
