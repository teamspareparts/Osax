<?php
require "_start.php"; global $db, $user;

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php"); exit();
}
if ( !isset($_POST["luo_raportti"]) ) {
	header("Location:etusivu.php"); exit();
}


$sql = "	SELECT tilaus.laskunro, tilaus.paivamaara, tilaus.pysyva_rahtimaksu, tilaus.kayttaja_id, yritys.nimi AS yritys,
	 		SUM( tilaus_tuote.kpl * 
			    (tilaus_tuote.pysyva_hinta * (1+tilaus_tuote.pysyva_alv) * (1-tilaus_tuote.pysyva_alennus)) )
			    AS summa_alvillinen,
			SUM( tilaus_tuote.kpl * 
			    (tilaus_tuote.pysyva_hinta * (1-tilaus_tuote.pysyva_alennus)) )
			    AS summa_alviton
			FROM tilaus
			LEFT JOIN tilaus_tuote
				ON tilaus.id = tilaus_tuote.tilaus_id
			LEFT JOIN kayttaja
				ON tilaus.kayttaja_id = kayttaja.id
			LEFT JOIN yritys
				ON kayttaja.yritys_id = yritys.id
			WHERE tilaus.paivamaara > ? AND tilaus.paivamaara < ? + INTERVAL 1 DAY AND maksettu = 1
			GROUP BY tilaus.id";
$tilaukset = $db->query($sql, [$_POST["pvm_from"], $_POST["pvm_to"]], FETCH_ALL);

$raportti = chr(0xEF) . chr(0xBB) . chr(0xBF); //BOM
$raportti .= "Myyntitapahtumat ".date('d.m.Y', strtotime($_POST["pvm_from"])) .
		" - " . date('d.m.Y', strtotime($_POST["pvm_to"])) . "\r\n\r\n" .
		"Tapahtumamäärä ". count($tilaukset) ." kpl\r\n" .
		"\r\n" .
		"Myyntipvm, Lasku nro, Asiakas, Summa ALV, Summa ALV 0, maksutapa\r\n";
foreach ($tilaukset as $tilaus) {
	$tilauspvm = date('d.m.Y', strtotime($tilaus->paivamaara));
	$laskunro = isset($tilaus->laskunro) ? $tilaus->laskunro : "NULL";
	$rahtimaksu_alvillinen = $tilaus->pysyva_rahtimaksu;
	$rahtimaksu_alviton = $tilaus->pysyva_rahtimaksu / (1 + 0.24); //ALV 24%
	$summa_alvillinen = number_format( round($tilaus->summa_alvillinen + $rahtimaksu_alvillinen, 2), 2, ".", "")."€";
	$summa_alviton = number_format( round($tilaus->summa_alviton + $rahtimaksu_alviton, 2), 2, ".", "")."€";
	$maksutapa = "Määrittelemätön maksutapa";
	$raportti .=    "{$tilauspvm}, {$laskunro}, {$tilaus->yritys}, ".
					"{$summa_alvillinen}, {$summa_alviton}, {$maksutapa}\r\n";

}



/** Ladataan tiedosto suoraan selaimeen */

$datetime = date("d-m-Y H-i-s");
$name = "Myyntitapahtumalistaus-{$datetime}.txt";
header('Content-Type: text');
header('Content-Disposition: attachment; filename='. $name);
header('Pragma: no-cache');
header("Expires: 0");

$outstream = fopen("php://output", "w");

fwrite($outstream, $raportti);



fclose($outstream);
exit();