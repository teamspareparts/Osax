<?php
require "_start.php"; global $db, $user;

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php"); exit();
}
if ( !isset($_POST["luo_raportti"]) ) {
	header("Location:etusivu.php"); exit();
}

// Alustetaan muuttujat
$brand = isset($_POST["brand"]) ? $_POST["brand"] : 0;
$hankintapaikka = isset($_POST["hankintapaikka"]) ? $_POST["hankintapaikka"] : 0;
$asiakas = isset($_POST["asiakas"]) ? $_POST["asiakas"] : 0;
$pvm_from = isset($_POST["pvm_from"]) ? $_POST["pvm_from"] : '1970-01-01';
$pvm_to = isset($_POST["pvm_to"]) ? $_POST["pvm_to"] : date('Y-m-d');
$sort = null;
switch ($_POST["sort"]) {
	case "sort_tuotekoodi":
		$sort = "articleNo";
		break;
	case "sort_brandi":
		$sort = "brandNo";
		break;
	case "sort_myyty_kpl":
		$sort = "yhteensa_kpl";
		break;
	case "sort_myyty_summa":
		$sort = "yhteensa_summa";
		break;
	default:
		$sort = "articleNo";
		break;
}

$tuotteet = [];

// Haetaan tuotteet annetuilla rajauksilla
$sql = "SELECT tuote.id, tuote.brandNo, tuote.hankintapaikka_id, tuote.tuotekoodi,
 			tuote.nimi, SUM(tilaus_tuote.kpl) AS yhteensa_kpl, 
 			ROUND( tuote.hinta_ilman_ALV, 2 ) AS hinta_ilman_ALV,
 			ROUND( SUM( tilaus_tuote.pysyva_hinta * (1-tilaus_tuote.pysyva_alennus) * tilaus_tuote.kpl ), 2 ) AS yhteensa_summa,
 			ROUND( AVG( tilaus_tuote.pysyva_hinta * (1-tilaus_tuote.pysyva_alennus) ), 2 ) AS keskimyyntihinta,
 			ROUND( 100*((tuote.hinta_ilman_ALV - tuote.sisaanostohinta) / tuote.hinta_ilman_ALV), 0 ) AS kate
 		FROM tilaus
		LEFT JOIN tilaus_tuote
			ON tilaus.id = tilaus_tuote.tilaus_id
		LEFT JOIN tuote
			ON tilaus_tuote.tuote_id = tuote.id
		WHERE tilaus.paivamaara > ? 
			AND tilaus.paivamaara < ? + INTERVAL 1 DAY
			AND tilaus.maksettu = 1
			AND (tuote.brandNo = ? OR 0 = ?)
			AND (tuote.hankintapaikka_id = ? OR 0 = ?)
			AND (tilaus.kayttaja_id = ? OR 0 = ?)
		GROUP BY tuote.id
		ORDER BY {$sort} DESC ";
$tuotteet = $db->query($sql, [$pvm_from, $pvm_to, $brand, $brand, $hankintapaikka, $hankintapaikka, $asiakas, $asiakas], FETCH_ALL);

// Luodaan raportin sisältö
$raportti = "Brändi;Hankintapaikka;Tuotekoodi;Nimi;KPL Ovh ALV0 (€);" .
			"Myyty KPL;Myyty yht (€);Myyntikeskihinta (€);Kate%\r\n";

$yhteensa_myynti = 0;
$yhteensa_kpl = 0;
foreach ($tuotteet as $tuote) {
	$yhteensa_kpl += $tuote->yhteensa_kpl;
	$yhteensa_myynti += $tuote->yhteensa_summa;
	// Vaihdetaan desimaalipisteet desimaalipilkuiksi
	$tuote->hinta_ilman_ALV = str_replace(".", ",", $tuote->hinta_ilman_ALV);
	$tuote->yhteensa_summa = str_replace(".", ",", $tuote->yhteensa_summa);
	$tuote->keskimyyntihinta = str_replace(".", ",", $tuote->keskimyyntihinta);
	$raportti .=    "{$tuote->brandNo};{$tuote->hankintapaikka_id};{$tuote->tuotekoodi};" .
					"{$tuote->nimi};{$tuote->hinta_ilman_ALV};{$tuote->yhteensa_kpl};" .
					"{$tuote->yhteensa_summa};{$tuote->keskimyyntihinta};{$tuote->kate}%" .
					"\r\n";
}
$raportti .= "\r\nYHTEENSÄ\r\n{$yhteensa_myynti} €\r\n{$yhteensa_kpl} kpl";
// Muutetaan koodaus windows-1252 muotoon
$raportti = mb_convert_encoding($raportti, "windows-1252", "UTF-8");

// Ladataan tiedosto suoraan selaimeen
$datetime = date("d-m-Y h-i-s");
$name = "Tuotekohtainen myyntiraportti {$datetime}.csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename='. $name);
header('Pragma: no-cache');
header("Expires: 0");

$outstream = fopen("php://output", "w");
fwrite($outstream, $raportti);

fclose($outstream);
exit();