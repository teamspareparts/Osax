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

//Alustetaan muuttujat
$tuotteet = [];
$raportti = "";

$sql = "SELECT *
		FROM tuote
		WHERE varastosaldo > 0
			AND (brandNo = ? OR ? = 0)
			AND (hankintapaikka_id = ? OR ? = 0)
		ORDER BY {$sort}";
$tuotteet = $db->query($sql, [$brand, $brand, $hankintapaikka, $hankintapaikka], FETCH_ALL);

// Luodaan raportti
$raportti .= chr(0xEF).chr(0xBB).chr(0xBF); //UTF-8 BOM
$raportti .= "Hyllypaikka;Tuotekoodi;Nimi;Ostohinta (alv 0%);Varastosaldo\r\n";
foreach ( $tuotteet as $tuote ) {
	$tuote->sisaanostohinta = str_replace(".", ",", $tuote->sisaanostohinta);
	$raportti .= "'{$tuote->hyllypaikka}';{$tuote->tuotekoodi};" .
		"{$tuote->nimi};{$tuote->sisaanostohinta};{$tuote->varastosaldo}\r\n";
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