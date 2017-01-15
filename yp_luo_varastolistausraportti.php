<?php
require "_start.php"; global $db, $user;

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php"); exit();
}
if ( !isset($_POST["luo_raportti"]) ) {
	header("Location:etusivu.php"); exit();
}


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

$tuotteet = [];
if ( $brand > 0 && $hankintapaikka > 0 ) {
	$sql = "SELECT * FROM tuote WHERE brandNo = ? AND hankintapaikka_id = ? ORDER BY {$sort}";
	$tuotteet = $db->query($sql, [$brand, $hankintapaikka], FETCH_ALL);
}
elseif ( $brand = 0 && $hankintapaikka > 0 ) {
	$sql = "SELECT * FROM tuote WHERE hankintapaikka_id = ? ORDER BY {$sort}";
	$tuotteet = $db->query($sql, [$hankintapaikka], FETCH_ALL);
}
elseif ( $brand > 0 && $hankintapaikka = 0 ) {
	$sql = "SELECT * FROM tuote WHERE brandNo = ? ORDER BY {$sort}";
	$tuotteet = $db->query($sql, [$brand], FETCH_ALL);
}
else {
	$sql = "SELECT * FROM tuote ORDER BY {$sort}";
	$tuotteet = $db->query($sql, [], FETCH_ALL);
}


/** Ladataan tiedosto suoraan selaimeen */
$datetime = date("d-m-Y h-i-s");
$name = "Varastolistausraportti-{$datetime}.csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename='. $name);
header('Pragma: no-cache');
header("Expires: 0");

$outstream = fopen("php://output", "w");
fwrite($outstream, chr(0xEF).chr(0xBB).chr(0xBF)); //UTF-8 BOM  --ehkä turha -SL
fwrite($outstream, "Hyllypaikka;Tilauskoodi;Nimi;Ostohinta (alv 0%);Varastosaldo\r\n");

foreach ($tuotteet as $tuote) {
	$row = "$tuote->hyllypaikka" . ";" .
		"$tuote->tilauskoodi" . ";" .
		"$tuote->nimi" . ";" .
		"$tuote->sisaanostohinta" . ";" .
		"$tuote->varastosaldo" .
		"\r\n";
	fwrite($outstream, $row);
}

fclose($outstream);
exit();