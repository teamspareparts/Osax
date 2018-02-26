<?php declare(strict_types=1);
// Setting error reporting on, just in case
error_reporting(E_ERROR); // Don't want to be surprised.
ini_set('display_errors', "1"); // Though pretty sure it doesn't matter through cronjob
// Autoloading classes
set_include_path(get_include_path().PATH_SEPARATOR.'luokat/');
spl_autoload_extensions('.class.php');
spl_autoload_register();
// Working directory, for cronjob. Mandatory.
chdir(__DIR__); // This breaks symlinks on Windows
// Just a random timelimit. Incase it get's stuck
set_time_limit(300); // 5min

$db = new DByhteys();

// Haetaan tilaukset, joiden tilaustuotteita ei ole vielä tilattu
$sql = "SELECT id, kayttaja_id
		FROM tilaus
		WHERE maksettu = 1
			AND tilaustuotteet_tilattu = 0
		LIMIT 1";
$tilaus = $db->query($sql, []);
if ( !$tilaus ) {
	return;
}

// Yritetään tilata Eoltaksen tilaustuotteet
$success = EoltasWebservice::orderFromEoltas( $db, $tilaus->id );
if ( $success ) {
	$sql = "UPDATE tilaus SET tilaustuotteet_tilattu = 1 WHERE id = ?";
	$db->query($sql, [$tilaus->id]);
}