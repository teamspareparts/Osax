<?php declare(strict_types=1);
require "./_start.php";
require "./luokat/tilaus.class.php";
global $db, $user;

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php");
	exit();
}
if ( !isset($_POST["luo_raportti"]) ) {
	header("Location:etusivu.php");
	exit();
}

// Haetaan tilausnumerot
$sql = "SELECT id FROM tilaus
		WHERE tilaus.paivamaara > ?
			AND tilaus.paivamaara < ? + INTERVAL 1 DAY
			AND maksettu = 1";
$tilaukset = $db->query($sql, [$_POST["pvm_from"], $_POST["pvm_to"]], FETCH_ALL);

$laskut = [];
$alv_kannat = [];
$myynti_alvillinen = $myynti_alviton = 0;
$myynti_lasku = $myynti_lasku_alviton = 0;
$myynti_kortti = $myynti_kortti_alviton = 0;
$myynti_maarittelematon = $myynti_maarittelematon_alviton = 0;
foreach ( $tilaukset as $tilaus ) {
	// Haetaan tilauksen tiedot
	$lasku = new Tilaus($db, $tilaus->id);
	$laskut[] = $lasku;
	// Yhteensä
	$myynti_alvillinen += $lasku->hintatiedot[ 'summa_yhteensa' ];
	$myynti_alviton += round($lasku->hintatiedot[ 'alv_perus' ], 2);
	// Maksutapa yhteensä
	if ( $lasku->maksutapa == 0 ) { // paytrail
		$myynti_kortti += $lasku->hintatiedot[ 'summa_yhteensa' ];
		$myynti_kortti_alviton += round($lasku->hintatiedot[ 'alv_perus' ], 2);
	} elseif ( $lasku->maksutapa == 1 ) { // lasku
		$myynti_lasku += $lasku->hintatiedot[ 'summa_yhteensa' ];
		$myynti_lasku_alviton += round($lasku->hintatiedot[ 'alv_perus' ], 2);
	} else {
		$myynti_maarittelematon += $lasku->hintatiedot[ 'summa_yhteensa' ];
		$myynti_maarittelematon_alviton += round($lasku->hintatiedot[ 'alv_perus' ], 2);
	}

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
$raportti =
	"Myyntiraportti aikaväliltä ".date('d.m.Y', strtotime($_POST["pvm_from"])) .
		" - " . date('d.m.Y', strtotime($_POST["pvm_to"])) . "\r\n\r\n" .
	"Myynti yhteensä ". format_number( $myynti_alvillinen ) ." sis alv\r\n" .
	"Myynti yhteensä ". format_number( $myynti_alviton ) ." alv 0%\r\n" .
	"Tapahtumamäärä ". count($tilaukset) . " kpl\r\n" .
	"\r\n";
// ALV erottelu
$raportti .= "ALV erottelu myynnistä\r\n";
foreach ($alv_kannat as $alv_kanta) {
	$raportti .=
		"Kanta\t {$alv_kanta[ 'kanta' ]}\r\n".
		"Perus\t ". format_number($alv_kanta[ 'perus' ]). "\r\n".
		"Maara\t ". format_number($alv_kanta[ 'maara' ]). "\r\n\r\n";
}
// Myyntitapa erottelu
$raportti .=
	"Maksutapaerottelu myynnistä\r\n" .
	"Lasku\r\n" .
	format_number( $myynti_lasku ) . " sis alv\r\n" .
	format_number( $myynti_lasku_alviton ) . " alv 0%\r\n\r\n" .
	"Paytrail\r\n" .
	format_number( $myynti_kortti ) . " sis alv\r\n" .
	format_number( $myynti_kortti_alviton ) . " alv 0%\r\n\r\n";
if ( $myynti_maarittelematon ) {
	$raportti .= "Maarittelematon maksutapa\r\n" .
		format_number($myynti_maarittelematon) . " sis alv\r\n" .
		format_number($myynti_maarittelematon_alviton) . " alv 0%";
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
