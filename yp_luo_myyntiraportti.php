<?php
require "_start.php"; global $db, $user;

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php"); exit();
}
if ( !isset($_POST["luo_raportti"]) ) {
	header("Location:etusivu.php"); exit();
}

//TODO: Hae myös myynti maksutavan mukaan kortti/verkkomaksu --SL 21.5

//TODO: Varmista että rahtimaksun alvin pyöristys on tehty samalla tavalla kuin tilausta tehdessä -- SL 21.5

/** Haetaan kokonaismyynti annetulla aikavälillä */
$sql = "	SELECT maksutapa,
			ROUND( tilaus.pysyva_rahtimaksu / ( 1 + 0.24 ) + 
				SUM( tilaus_tuote.pysyva_hinta * tilaus_tuote.kpl * (1-tilaus_tuote.pysyva_alennus)), 2)
				AS myynti_alviton,
	 		tilaus.pysyva_rahtimaksu +
	 			SUM( ROUND( ((tilaus_tuote.pysyva_hinta * (1+tilaus_tuote.pysyva_alv) * tilaus_tuote.kpl) *
	 			(1-tilaus_tuote.pysyva_alennus)), 2) )
	 			AS myynti_alvillinen
			FROM tilaus
			LEFT JOIN tilaus_tuote
				ON tilaus.id = tilaus_tuote.tilaus_id
			WHERE tilaus.paivamaara > ? AND tilaus.paivamaara < ? + INTERVAL 1 DAY AND maksettu = 1
			GROUP BY tilaus.id";
$tilaukset = $db->query($sql, [$_POST["pvm_from"], $_POST["pvm_to"]], FETCH_ALL);

/** Haetaan myynti luokiteltuna ALV-ryhmiin */
$sql = "	SELECT 	pysyva_alv, SUM( pysyva_hinta * kpl * pysyva_alv ) AS myynti_alviton
			FROM tilaus
			LEFT JOIN tilaus_tuote
				ON tilaus.id = tilaus_tuote.tilaus_id
			WHERE tilaus.paivamaara > ? AND tilaus.paivamaara < ? + INTERVAL 1 DAY AND maksettu = 1
			GROUP BY tilaus_tuote.pysyva_alv";
$myynti_by_alv = $db->query($sql, [$_POST["pvm_from"], $_POST["pvm_to"]], FETCH_ALL);

/** Lasketaan rahtimaksujen alv (24%) */
$sql = "    SELECT IFNULL( SUM( (0.24 * tilaus.pysyva_rahtimaksu) / (1 + 0.24)), 0 ) AS alv
    		FROM tilaus
    		WHERE tilaus.paivamaara > ? AND tilaus.paivamaara < ? + INTERVAL 1 DAY AND maksettu = 1";
$rahtimaksu_alv = $db->query($sql, [$_POST["pvm_from"], $_POST["pvm_to"]])->alv;


$myynti_alvillinen = 0;
$myynti_alviton = 0;
foreach ( $tilaukset as $tilaus ) {
	$myynti_alvillinen += $tilaus->myynti_alvillinen;
	$myynti_alviton += $tilaus->myynti_alviton;
}


/** Ladataan tiedosto suoraan selaimeen */
$datetime = date("d-m-Y h-i-s");
$name = "Myyntiraportti-{$datetime}.txt";
header('Content-Type: text');
header('Content-Disposition: attachment; filename='. $name);
header('Pragma: no-cache');
header("Expires: 0");

$outstream = fopen("php://output", "w");
$raportti = "Myyntiraportti aikaväliltä ".date('d.m.Y', strtotime($_POST["pvm_from"])) .
				" - " . date('d.m.Y', strtotime($_POST["pvm_to"])) . "\r\n\r\n" .
			"Myynti yhteensä ". number_format( $myynti_alvillinen, 2, ",", " " ) ." € sis alv\r\n" .
			"Myynti yhteensä ". number_format( $myynti_alviton, 2, ",", " " ) ." € alv 0%\r\n" .
			"Tapahtumamäärä ". count($tilaukset) . " kpl\r\n" .
			"\r\n" .
			"ALV erottelu myynnistä\r\n";
foreach ($myynti_by_alv as $alv) {
    $myynnin_alv = round($alv->myynti_alviton, 2);
    // Lasketaan rahtimaksujen alvit mukaan, jos ALV-kanta 24%
    if ( $alv->pysyva_alv == 0.24 ) {
    	$myynnin_alv += $rahtimaksu_alv;
    }
	$raportti .= "ALV ".($alv->pysyva_alv*100)."%\t ".
					number_format( $myynnin_alv, 2, ",", " " ) ." €\r\n";

}
fwrite($outstream, $raportti);



fclose($outstream);
exit();