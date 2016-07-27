<?php
class DByhteys_luokka {
	protected $username = '';		//DB käyttäjänimi
	protected $password = '';		//DB salasana

	protected $pdo_dsn = '';		//PDO:n yhdistämistä varten
	protected $pdo_options = [		//PDO:n DB driver specific options
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false ];

	protected $connection = NULL;	//PDO connection (or MySQLi)
	protected $prepared_stmt = NULL;//Tallennettu prepared statement

	/**
	 * See link for more info on return types:
	 *	https://phpdelusions.net/pdo/fetch_modes
	 * @var $returnType; possible return types for a PDO query fetch()-function
	 */
	protected $returnType = PDO::FETCH_ASSOC;

	public function __construct( $username, $password, $database, $host = 'localhost' ) {
		mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
		$this->username = $username;
		$this->password = $password;
		$this->pdo_dsn = "mysql:host={$host};dbname={$database};charset=utf8";
		$this->connection = new PDO(
				$this->pdo_dsn, $username, $password, $this->pdo_options );
	}

	public function raw_query( $query ) {
		$db = $this->connection;
		$result = $db->query( $query );
		foreach ( $result as $the_answer ) {
			$results[] = $the_answer;
		}
		return $results;
	}

	public function query( $query, $values = NULL ) {
		$db = $this->connection;

		$stmt = $db->prepare( $query );

		$stmt->execute( $values );

		$result = $stmt->fetch( $this->returnType );

		$stmt->closeCursor();
		return $results;
	}

	public function prepare_stmt( $query ) {
		$db = $this->connection;
		$this->prepared_stmt = $db->prepare( $query );
	}

	public function run_prepared_stmt( $values = NULL ) {
		$stmt = $this->prepared_stmt;
		$stmt->execute( $values );
	}

	public function get_next_row() {
		$stmt = $this->prepared_stmt;
		$results = $stmt->fetch( $this->returnType );
		return $results;
	}

	public function close_prepared_stmt() {
		if ( $this->prepared_stmt ) {
			$this->prepared_stmt->closeCursor();
			$this->prepared_stmt = NULL;
		}
	}

	public function close_connection() {
		$this->close_prepared_stmt();
		if ( $this->connection ) {
			$this->connection = NULL;
		}
	}

	function __destruct() {
		$this->close_connection();
		$this->username = NULL;
		$this->password = NULL;
		$this->host = NULL;
		$this->database = NULL;
	}
}

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