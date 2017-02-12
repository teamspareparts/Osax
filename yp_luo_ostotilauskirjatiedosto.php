<?php
require "_start.php"; global $db, $user;
require "tecdoc.php";

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php"); exit();
}

//tarkastetaan onko GET muuttujat sallittuja ja haetaan ostotilauskirjan tiedot
$ostotilauskirja_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$otk = $db->query("SELECT * FROM ostotilauskirja_arkisto WHERE id = ? LIMIT 1", [$ostotilauskirja_id])) {
	header("Location: etusivu.php"); exit();
}


$sql = "  SELECT tuote.tilauskoodi, tuote.articleNo, tuote.valmistaja, ostotilauskirja_tuote_arkisto.kpl
  		  FROM ostotilauskirja_tuote_arkisto
          LEFT JOIN tuote
            ON ostotilauskirja_tuote_arkisto.tuote_id = tuote.id 
          WHERE ostotilauskirja_id = ?
          GROUP BY tuote_id";
$tuotteet = $db->query($sql, [$ostotilauskirja_id], FETCH_ALL);



$name = $otk->hankintapaikka_id."-".$otk->tunniste."-".$otk->lahetetty.".csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename='. $name);
header('Pragma: no-cache');
header("Expires: 0");

$outstream = fopen("php://output", "w");

fwrite($outstream, "Tuotenumero;Valmistaja;KPL\r\n");

foreach ($tuotteet as $tuote) {
	$row = 	$tuote->articleNo . ";" .
			$tuote->valmistaja . ";" .
			$tuote->kpl . ";" .
			"\r\n";
	fwrite($outstream, $row);
}

fclose($outstream);
exit();