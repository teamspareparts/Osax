<?php
require_once 'luokat/db_yhteys_luokka.class.php';
//
// Tietokannan asetukset
//

define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'tuoteluettelo_database');

//Objektin luonti uutta yhteyttä varten
$db = new DByhteys( DB_USERNAME, DB_PASSWORD, DB_NAME, DB_HOST );

$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME)
				or die('Tietokantayhteyttä ei voitu muodostaa: ' . mysqli_connect_error());
