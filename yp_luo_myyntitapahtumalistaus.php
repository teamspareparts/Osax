<?php
require "_start.php";
global $db, $user;

if (!$user->isAdmin()) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php");
	exit();
}
if (!isset($_POST["luo_raportti"])) {
	header("Location:etusivu.php");
	exit();
}

/**
 * Luo tapahtumalistausraportin sisällön
 * @param DByhteys $db
 * @param $pvm_from <p>
 * @param $pvm_to <p>
 * @return string <p> Raportin sisältö.
 */
function luo_tapahtumalistaus(DByhteys $db, /*string*/
                              $pvm_from, /*string*/
                              $pvm_to)
{
	$sum_alviton_lasku = 0;
	$sum_alviton_paytrail = 0;
	$sum_alviton_maarittelematon = 0;
	$sum_alvillinen_lasku = 0;
	$sum_alvillinen_paytrail = 0;
	$sum_alvillinen_maarittelematon = 0;

	//Haetaan tilaukset
	$sql = "SELECT tilaus.laskunro, tilaus.paivamaara, 
			tilaus.kayttaja_id, yritys.nimi AS yritys,
			IFNULL(tilaus.maksutapa, -1) AS maksutapa,
			tilaus.pysyva_rahtimaksu +
	 			SUM( ROUND( tilaus_tuote.kpl * 
			    (tilaus_tuote.pysyva_hinta * (1+tilaus_tuote.pysyva_alv) * (1-tilaus_tuote.pysyva_alennus)), 2) )
			    AS summa_alvillinen,
			tilaus.pysyva_rahtimaksu / ( 1 + 0.24 ) +
				SUM( ROUND( tilaus_tuote.kpl * 
			    (tilaus_tuote.pysyva_hinta * (1-tilaus_tuote.pysyva_alennus)), 2 ) )
			    AS summa_alviton
			FROM tilaus
			LEFT JOIN tilaus_tuote
				ON tilaus.id = tilaus_tuote.tilaus_id
			LEFT JOIN kayttaja
				ON tilaus.kayttaja_id = kayttaja.id
			LEFT JOIN yritys
				ON kayttaja.yritys_id = yritys.id
			WHERE tilaus.paivamaara > ? AND tilaus.paivamaara < ? + INTERVAL 1 DAY AND maksettu = 1
			GROUP BY tilaus.id
			ORDER BY tilaus.paivamaara";
	$tilaukset = $db->query($sql, [$pvm_from, $pvm_to], FETCH_ALL);

	//Luodaan raportti
	$raportti = chr(0xEF) . chr(0xBB) . chr(0xBF); //BOM
	$raportti .= "Myyntitapahtumat " . date('d.m.Y', strtotime($_POST["pvm_from"])) .
		" - " . date('d.m.Y', strtotime($_POST["pvm_to"])) . "\r\n\r\n" .
		"Tapahtumamäärä " . count($tilaukset) . " kpl\r\n" .
		"\r\n" .
		"Myyntipvm, Lasku nro, Asiakas, Summa ALV, Summa ALV 0, maksutapa\r\n";
	foreach ($tilaukset as $tilaus) {
		$tilaus->tilauspvm = date('d.m.Y', strtotime($tilaus->paivamaara));
		$tilaus->laskunro = isset($tilaus->laskunro) ? $tilaus->laskunro : "NULL";
		$tilaus->summa_alvillinen_string = number_format($tilaus->summa_alvillinen, 2, ".", "") . "€";
		$tilaus->summa_alviton_string = number_format($tilaus->summa_alviton, 2, ".", "") . "€";
		//Määritellään maksutapa ja lasketaan tilausten yhteisarvoa
		$tilaus->maksutapa_string = null;
		switch ($tilaus->maksutapa) {
			case -1:
				$tilaus->maksutapa_string = "Määrittelemätön";
				$sum_alviton_maarittelematon = round($sum_alviton_maarittelematon + $tilaus->summa_alviton, 2);
				$sum_alvillinen_maarittelematon = round($sum_alvillinen_maarittelematon + $tilaus->summa_alvillinen, 2);
				break;
			case 0:
				$tilaus->maksutapa_string = "Paytrail";
				$sum_alviton_paytrail = round($sum_alviton_paytrail + $tilaus->summa_alviton, 2);
				$sum_alvillinen_paytrail = round($sum_alvillinen_paytrail + $tilaus->summa_alvillinen, 2);
				break;
			case 1:
				$tilaus->maksutapa_string = "Lasku";
				$sum_alviton_lasku = round($sum_alviton_lasku + $tilaus->summa_alviton, 2);
				$sum_alvillinen_lasku = round($sum_alvillinen_lasku + $tilaus->summa_alvillinen, 2);
				break;
		}

		$raportti .= "{$tilaus->tilauspvm}, {$tilaus->laskunro}, {$tilaus->yritys}, " .
			"{$tilaus->summa_alvillinen_string}, {$tilaus->summa_alviton_string}, {$tilaus->maksutapa_string}\r\n";
	}

	//Yhteensä
	$yhteensa_alvillinen_string = number_format($sum_alvillinen_maarittelematon + $sum_alvillinen_paytrail + $sum_alvillinen_lasku, 2, ".", "");
	$yhteensa_alviton_string = number_format($sum_alviton_maarittelematon + $sum_alviton_paytrail + $sum_alviton_lasku, 2, ".", "");
	$raportti .= "\r\nYHTEENSÄ\r\n" .
		"{$yhteensa_alviton_string}€ ALV 0%\r\n" .
		"{$yhteensa_alvillinen_string}€ sis. ALV\r\n";

	//Tilausket eroteltuna maksutavan mukaan
	$raportti .= "\r\n\r\nTILAUKSET EROTELTUNA MAKSUTAVAN MUKAAN\r\n";
	usort($tilaukset, function ($a, $b) { //Sortataan maksutavan mukaan
		return $a->maksutapa < $b->maksutapa;
	});
	$edellinen_maksutapa = -100;
	foreach ($tilaukset as $tilaus) {
		//Jos maksutapa vaihtuu, tehdään erottelu
		if ($tilaus->maksutapa != $edellinen_maksutapa) {
			$edellinen_maksutapa = $tilaus->maksutapa;
			$raportti .= str_repeat("-", 80) . "\r\n";
		}
		$raportti .= "{$tilaus->tilauspvm}, {$tilaus->laskunro}, {$tilaus->yritys}, " .
			"{$tilaus->summa_alvillinen_string}, {$tilaus->summa_alviton_string}, {$tilaus->maksutapa_string}\r\n";
	}
	$raportti .= str_repeat("-", 80) . "\r\n\r\n";

	//Yhteensä, järjestettynä maksutavan mukaan
	$sum_alviton_lasku_string = number_format($sum_alviton_lasku, 2, ".", "");
	$sum_alviton_paytrail_string = number_format($sum_alviton_paytrail, 2, ".", "");
	$sum_alviton_maarittelematon_string = number_format($sum_alviton_maarittelematon, 2, ".", "");
	$sum_alvillinen_lasku_string = number_format($sum_alvillinen_lasku, 2, ".", "");
	$sum_alvillinen_paytrail_string = number_format($sum_alvillinen_paytrail, 2, ".", "");
	$sum_alvillinen_maarittelematon_string = number_format($sum_alvillinen_maarittelematon, 2, ".", "");
	$raportti .= "YHTEENSÄ - MÄÄRITTELEMÄTÖN\r\n" .
		"{$sum_alviton_maarittelematon_string}€ ALV 0%\r\n" .
		"{$sum_alvillinen_maarittelematon_string}€ sis. ALV\r\n\r\n" .
		"YHTEENSÄ - PAYTRAIL\r\n" .
		"{$sum_alviton_paytrail_string}€ ALV 0%\r\n" .
		"{$sum_alvillinen_paytrail_string}€ sis. ALV\r\n\r\n" .
		"YHTEENSÄ - LASKU\r\n" .
		"{$sum_alviton_lasku_string}€ ALV 0%\r\n" .
		"{$sum_alvillinen_lasku_string}€ sis. ALV\r\n\r\n";

	return $raportti;
}

$raportti = luo_tapahtumalistaus($db, $_POST["pvm_from"], $_POST["pvm_to"]);


/** Ladataan tiedosto suoraan selaimeen */

$datetime = date("d-m-Y H-i-s");
$name = "Myyntitapahtumalistaus-{$datetime}.txt";
header('Content-Type: text');
header('Content-Disposition: attachment; filename=' . $name);
header('Pragma: no-cache');
header("Expires: 0");

$outstream = fopen("php://output", "w");

fwrite($outstream, $raportti);

fclose($outstream);
exit();