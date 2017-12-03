<?php declare(strict_types=1);
require "./_start.php";
require "./luokat/laskutiedot.class.php";
global $db, $user;

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php");
	exit();
}
if ( !isset($_POST["luo_raportti"]) ) {
	header("Location:etusivu.php");
	exit();
}

//TODO: Hae myös myynti maksutavan mukaan kortti/verkkomaksu --SL 21.5

// Haetaan tilausnumerot
$sql = "SELECT id FROM tilaus
		WHERE tilaus.paivamaara > ?
			AND tilaus.paivamaara < ? + INTERVAL 1 DAY
			AND maksettu = 1";
$tilaukset = $db->query($sql, [$_POST["pvm_from"], $_POST["pvm_to"]], FETCH_ALL);

$laskut = [];
$alv_kannat = [];
$myynti_alvillinen = 0;
$myynti_alviton = 0;
foreach ( $tilaukset as $tilaus ) {
	// Luodaan Laskutiedot-olio väärällä userilla, koska tarvitaan vain tilausten summia
	$lasku = new Laskutiedot($db, $tilaus->id, $user);
	$laskut[] = $lasku;
	// Yhteensä
	$myynti_alvillinen += $lasku->hintatiedot[ 'summa_yhteensa' ];
	$myynti_alviton += round($lasku->hintatiedot[ 'alv_perus' ], 2);
	/**
	 * ALV-tiedot säilytetään arrayssa, jossa on kolme arvoa:
	 *  kanta, esim. 24 (%);
	 *  perus, eli summa josta ALV lasketaan; ja
	 *  määrä, eli lasketun ALV:n määrä.
	 */
	// Tarkistetaan, että tuotteen ALV-kanta on listalla.
	foreach ( $lasku->hintatiedot[ 'alv_kannat' ] as $kanta=>$alv ) {
		// Lisätään alv-kanta listaan, jos se ei jo ole siellä
		if ( !array_key_exists($kanta, $alv_kannat) ) {
			$alv_kannat[ $kanta ][ 'kanta' ] = "{$kanta} %";
			$alv_kannat[ $kanta ][ 'perus' ] = round($alv[ 'perus' ], 2);
			$alv_kannat[ $kanta ][ 'maara' ] = round($alv[ 'maara' ], 2);
		} else {
			$alv_kannat[ $kanta ][ 'perus' ] += round($alv[ 'perus' ], 2);
			$alv_kannat[ $kanta ][ 'maara' ] += round($alv[ 'maara' ], 2);
		}
	}
}

// Luodaan raportti
$raportti = "Myyntiraportti aikaväliltä ".date('d.m.Y', strtotime($_POST["pvm_from"])) .
				" - " . date('d.m.Y', strtotime($_POST["pvm_to"])) . "\r\n\r\n" .
			"Myynti yhteensä ". format_number( $myynti_alvillinen ) ." sis alv\r\n" .
			"Myynti yhteensä ". format_number( $myynti_alviton ) ." alv 0%\r\n" .
			"Tapahtumamäärä ". count($tilaukset) . " kpl\r\n" .
			"\r\n" .
			"ALV erottelu myynnistä\r\n";
foreach ($alv_kannat as $alv_kanta) {
	$raportti .=    "Kanta\t {$alv_kanta[ 'kanta' ]} \r\n".
					"Perus\t ". format_number($alv_kanta[ 'perus' ]). "\r\n".
					"Maara\t ". format_number($alv_kanta[ 'maara' ]). "\r\n\r\n";
}

// Ladataan tiedosto suoraan selaimeen
$datetime = date("d-m-Y h-i-s");
$name = "Myyntiraportti-{$datetime}.txt";
header('Content-Type: text');
header('Content-Disposition: attachment; filename='. $name);
header('Pragma: no-cache');
header("Expires: 0");
$outstream = fopen("php://output", "w");
fwrite($outstream, $raportti);
fclose($outstream);
exit();
