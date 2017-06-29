<?php
require "_start.php"; global $db, $user;

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php"); exit();
}
if ( !isset($_POST["luo_raportti"]) ) {
	header("Location:etusivu.php"); exit();
}

// Alustetaan POST ja GET muuttujat
$brand = isset($_POST["brand"]) ? $_POST["brand"] : 0;
$hankintapaikka = isset($_POST["hankintapaikka"]) ? $_POST["hankintapaikka"] : 0;
$myyntitiedot = isset($_POST["myyntitiedot"]) ? true : false;
$sort = null;
switch ($_POST["sort"]) {
	case "sort_tuotekoodi":
		$sort = "articleNo";
		break;
	case "sort_hyllypaikka":
		$sort = "hyllypaikka";
		break;
	default:
		$sort = "articleNo";
		break;
}

// Alustetaan muuttujat
$tuotteet = [];
$raportti = "";

$sql = "SELECT tuote.id, tuote.hyllypaikka, tuote.tuotekoodi, tuote.sisaanostohinta, 
 			tuote.nimi, tuote.varastosaldo,
 			IFNULL( SUM(tilaus_tuote.kpl), 0 ) AS yhteensa_kpl,
 			ROUND( tuote.hinta_ilman_ALV, 2 ) AS hinta_ilman_ALV,
 			IFNULL( ROUND( SUM( tilaus_tuote.pysyva_hinta * (1-tilaus_tuote.pysyva_alennus) * tilaus_tuote.kpl ), 2 ), 0 ) AS yhteensa_summa,
 			IFNULL( ROUND( AVG( tilaus_tuote.pysyva_hinta * (1-tilaus_tuote.pysyva_alennus) ), 2 ), 0 ) AS keskimyyntihinta,
 			ROUND( 100*((tuote.hinta_ilman_ALV - tuote.sisaanostohinta) / tuote.hinta_ilman_ALV), 0 ) AS kate
 		FROM tuote
		LEFT JOIN tilaus_tuote
			ON tuote.id = tilaus_tuote.tuote_id
		WHERE varastosaldo > 0
			AND (brandNo = ? OR ? = 0)
			AND (hankintapaikka_id = ? OR ? = 0)
		GROUP BY tuote.id
		ORDER BY {$sort}";
$tuotteet = $db->query($sql, [$brand, $brand, $hankintapaikka, $hankintapaikka], FETCH_ALL);

// Luodaan raportti
$raportti .= chr(0xEF).chr(0xBB).chr(0xBF); //UTF-8 BOM
// Otsikkorivi
if ( $myyntitiedot ) {
	$raportti .= "Hyllypaikka;Tuotekoodi;Nimi;Ostohinta (alv 0%);Varastosaldo;".
		"Ovh ALV0 (€);Myyty KPL;Myyty yht (€);Myyntikeskihinta (€);Kate%\r\n";
} else {
	$raportti .= "Hyllypaikka;Tuotekoodi;Nimi;Ostohinta (alv 0%);Varastosaldo\r\n";
}
foreach ( $tuotteet as $tuote ) {
	$tuote->sisaanostohinta = str_replace(".", ",", $tuote->sisaanostohinta);
	$tuote->hinta_ilman_ALV = str_replace(".", ",", $tuote->hinta_ilman_ALV);
	$tuote->keskimyyntihinta = str_replace(".", ",", $tuote->keskimyyntihinta);
	$tuote->yhteensa_summa = str_replace(".", ",", $tuote->yhteensa_summa);
	if ( $myyntitiedot ) {
		$raportti .= "'{$tuote->hyllypaikka}';{$tuote->tuotekoodi};" .
			"{$tuote->nimi};{$tuote->sisaanostohinta};{$tuote->varastosaldo};" .
			"{$tuote->hinta_ilman_ALV};{$tuote->yhteensa_kpl};{$tuote->yhteensa_summa};" .
			"{$tuote->keskimyyntihinta};{$tuote->kate}%\r\n";
	} else {
		$raportti .= "'{$tuote->hyllypaikka}';{$tuote->tuotekoodi};" .
			"{$tuote->nimi};{$tuote->sisaanostohinta};{$tuote->varastosaldo}\r\n";
	}
}

// Ladataan tiedosto suoraan selaimeen
$datetime = date("d-m-Y h-i-s");
$name = "Varastolistausraportti-{$datetime}.csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename='. $name);
header('Pragma: no-cache');
header("Expires: 0");
$outstream = fopen("php://output", "w");
fwrite($outstream, $raportti);
fclose($outstream);
exit();