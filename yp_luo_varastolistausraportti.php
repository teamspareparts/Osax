<?php
require "_start.php"; global $db, $user;

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php"); exit();
}
if ( !isset($_POST["luo_raportti"]) ) {
	header("Location:etusivu.php"); exit();
}

$sql = "";
$tuotteet = [];



$name = "xxxxxxxxxxx.csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename='. $name);
header('Pragma: no-cache');
header("Expires: 0");

$outstream = fopen("php://output", "w");

fwrite($outstream, "Hyllypaikka;Tilauskoodi;Nimi;Ostohinta (alv 0%);Varastosaldo\n");

foreach ($tuotteet as $tuote) {
	$row = "";
}

fclose($outstream);
exit();