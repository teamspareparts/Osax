<?php declare(strict_types=1);
require '_start.php'; global $db, $user, $cart;

if ( !$user->isAdmin() ) {
    header("Location:etusivu.php"); exit();
}

//tarkastetaan onko GET muuttujat sallittuja ja haetaan ostotilauskirjan tiedot
$ostotilauskirja_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ( !$otk = $db->query("SELECT * FROM ostotilauskirja WHERE id = ? LIMIT 1", [$ostotilauskirja_id]) ) {
	header("Location: yp_ostotilauskirja_hankintapaikka.php"); exit();
}

/**
 * Lasketaan halutun hankintapaikan keskimääräinen toimitusaika.
 * @param DByhteys $db
 * @param int $hankintapaikka_id
 * @return float
 */
function get_toimitusaika( DByhteys $db, int $hankintapaikka_id ) : float {
    $oletus_toimitusaika = 7; //Käytetään mikäli aikaisempia tilauksia ei ole
    //Toimitusaika (lasketaan kolmen viime lähetyksen keskiarvo)
    $sql = "	SELECT lahetetty, saapumispaiva 
		  		FROM ostotilauskirja_arkisto 
				WHERE hankintapaikka_id = ? AND saapumispaiva IS NOT NULL
				LIMIT 3";
    $ostotilauskirjan_aikaleimat = $db->query($sql, [$hankintapaikka_id], FETCH_ALL);
    $toimitusaika = $i = 0;
    foreach ( $ostotilauskirjan_aikaleimat as $aikaleimat ) {
        $toimitusaika += ceil((strtotime($aikaleimat->saapumispaiva) - strtotime($aikaleimat->lahetetty)) / (60 * 60 * 24));
        $i++;
    }
    if ( $toimitusaika ) {
        $toimitusaika = ceil($toimitusaika / $i); //keskiarvo
    } else {
        $toimitusaika = $oletus_toimitusaika; //default
    }
    return $toimitusaika;
}

/**
 * Luo select-valikon hankintapaikkaan liitetyistä tilauskirjoista.
 * @param DByhteys $db
 * @param int $hankintapaikka_id
 * @return String|null
 */
function get_ostotilauskirja_select_string( DByhteys $db, int $hankintapaikka_id ) {
	// Haetaan hankintapaikan ostotilauskirjat
	$sql = "SELECT * FROM ostotilauskirja WHERE hankintapaikka_id = ?";
	$otks = $db->query($sql, [$hankintapaikka_id], FETCH_ALL);
	if ( !$otks ) {
		return null;
	}

	// Build string
	$str = "<select name='uusi_tilauskirja'>";
	foreach ( $otks as $otk ) {
		$str .= "<option value={$otk->id}>{$otk->tunniste}</option>";
	}
	$str .= "</select>";
	return $str;
}

/**
 * Siirtää tiluastuotteen toiselle tilauskirjalle.
 * @param DByhteys $db
 * @param int $otk_id
 * @param int $uusi_otk_id
 * @param int $tuote_id
 * @return bool
 */
function siirra_tuote_toiselle_tilauskirjalle( DByhteys $db, int $otk_id, int $uusi_otk_id, int $tuote_id ) : bool {
	// Tarkastetaan onko tuotetta jo toisella tilauskirjalla
	$sql = "SELECT *
			FROM ostotilauskirja_tuote
			WHERE ostotilauskirja_id = ?
				AND tuote_id = ?
				AND tilaustuote = 1
				AND automaatti = 0";
	$tuote = $db->query($sql, [$uusi_otk_id, $tuote_id]);
	if ( $tuote ) {
		// Jos tuote jo tilauskirjalla päivitetään kpl määrä.
		$sql = "UPDATE ostotilauskirja_tuote otk_t1
				INNER JOIN (
					SELECT kpl, selite FROM ostotilauskirja_tuote
					WHERE ostotilauskirja_id = ? AND tuote_id = ?
						AND tilaustuote = 1 AND automaatti = 0
					) AS otk_t2
				SET otk_t1.kpl = otk_t1.kpl + otk_t2.kpl,
					otk_t1.selite = CONCAT(otk_t1.selite, otk_t2.selite)
				WHERE otk_t1.ostotilauskirja_id = ?
					AND otk_t1.tuote_id = ?
					AND otk_t1.tilaustuote = 1
					AND otk_t1.automaatti = 0";
		$db->query($sql, [$otk_id, $tuote_id, $uusi_otk_id, $tuote_id]);
		// Poista tuote alkuperäiseltä tilauskirjalta
		$sql = "DELETE FROM ostotilauskirja_tuote
				WHERE ostotilauskirja_id = ?
					AND tuote_id = ?
					AND tilaustuote = 1
					AND automaatti = 0";
		$result = $db->query($sql, [$otk_id, $tuote_id]);
	} else {
		// Jos tuotetta ei ole tilauskirjalla muutetaan vain tilauskirjan id
		$sql = "UPDATE ostotilauskirja_tuote
				SET ostotilauskirja_id = ?
				WHERE ostotilauskirja_id = ?
					AND tuote_id = ?
					AND tilaustuote = 1
					AND automaatti = 0";
		$result = $db->query($sql, [$uusi_otk_id, $otk_id, $tuote_id]);
	}
	return $result ? true : false;


}

/**
 * @param DByhteys $db
 * @param int $ostotilauskirja_id
 * @param array $tuotteet
 * @return bool
 */
function tallenna_ostotilauskirja( DByhteys $db, int $ostotilauskirja_id, array $tuotteet ) : bool {

	// Päivitetään kaikkien tilauskirjalla olevien tuotteiden kpl
	$values = [];
	$questionmarks = implode(',', array_fill(0, count($tuotteet), '(?, ?, ?, ?, ?)'));
	$sql = "INSERT INTO ostotilauskirja_tuote (ostotilauskirja_id, tuote_id, automaatti, tilaustuote, kpl)
	        	VALUES {$questionmarks}
	        ON DUPLICATE KEY
	        	UPDATE kpl = VALUES(kpl)";

	foreach ( $tuotteet as $tuote ) {
		array_push($values, $ostotilauskirja_id, $tuote['id'], $tuote['automaatti'],
			$tuote['tilaustuote'], $tuote['kpl']);
	}
	$result = $db->query($sql, $values);

	// Poistetaan tuotteet
	$sql = "DELETE FROM ostotilauskirja_tuote
			WHERE ostotilauskirja_id = ?
				AND tuote_id = ?
				AND automaatti = ?
				AND tilaustuote = ?";
	foreach ( $tuotteet as $tuote ) {
		if ( $tuote['kpl'] == 0 ) {
			$db->query($sql, [ $ostotilauskirja_id, $tuote['id'], $tuote['automaatti'], $tuote['tilaustuote'] ]);
		}
	}

	return $result ? true : false;
}


/**
 * Ostotilauskirjan lähetys
 * @param DByhteys $db
 * @param int $user_id
 * @param int $ostotilauskirja_id
 * @return int <p> Palauttaa 0 tai arkistoidun ostotilauskirjan id:n.
 */
function laheta_ostotilauskirja( DByhteys $db, int $user_id, int $ostotilauskirja_id ) : int {

    //Haetaan ostotilauskirjan tuotteet
    $sql = "SELECT ostotilauskirja_tuote.*, tuote.sisaanostohinta FROM ostotilauskirja_tuote
 			LEFT JOIN tuote
 			  ON ostotilauskirja_tuote.tuote_id = tuote.id
 			WHERE ostotilauskirja_id = ?";
    if( !$products = $db->query($sql, [$ostotilauskirja_id], FETCH_ALL) ) {
        return 0;
    }

    //Haetaan hankintapaikan keskimääräinen toimitusaika
	$sql = "SELECT hankintapaikka_id FROM ostotilauskirja WHERE id = ?";
    $hankintapaikka_id = $db->query($sql, [$ostotilauskirja_id])->hankintapaikka_id;
    $toimitusaika = get_toimitusaika($db, $hankintapaikka_id);

	//Lisätään ostotilauskirja arkistoon
	$sql = "INSERT INTO ostotilauskirja_arkisto ( hankintapaikka_id, tunniste, original_rahti, rahti,
 				oletettu_saapumispaiva, lahetetty, lahettaja, ostotilauskirja_id)
            SELECT hankintapaikka_id, tunniste, rahti, rahti, NOW() + INTERVAL ? DAY , NOW(), ?, id FROM ostotilauskirja
            WHERE id = ? ";
	if (!$db->query($sql, [$toimitusaika, $user_id, $ostotilauskirja_id])) {
	    return 0;
	}
	$uusi_otk_id = $db->query("SELECT LAST_INSERT_ID() AS last_id", []);

    //Lisätään ostotilauskirjan tuotteet arkistoon (kaikki kerralla)
    $sql_insert_values = [];
    $questionmarks = implode(',', array_fill(0, count($products), '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'));
	$sql = "INSERT INTO ostotilauskirja_tuote_arkisto (ostotilauskirja_id, tuote_id, automaatti, tilaustuote,
	        	original_kpl, kpl, selite, lisays_pvm, lisays_kayttaja_id, ostohinta)
	        VALUES {$questionmarks}";

	foreach ($products as $product) {
		array_push($sql_insert_values, $uusi_otk_id->last_id, $product->tuote_id, $product->automaatti,
			$product->tilaustuote, $product->kpl, $product->kpl, $product->selite, $product->lisays_pvm,
			$product->lisays_kayttaja_id, $product->sisaanostohinta);
	}
    $result = $db->query($sql, $sql_insert_values);
	if( !$result ) {
		return 0;
	}

    //Pävitetään seuraava lähetyspäivä ja saapumispäivä ostotilauskirjalle
    $sql = "UPDATE ostotilauskirja
	        SET oletettu_lahetyspaiva = now() + INTERVAL toimitusjakso WEEK,
	            oletettu_saapumispaiva = now() + INTERVAL toimitusjakso WEEK + INTERVAL ? DAY 
 			WHERE id = ?";
    $db->query($sql, [$toimitusaika, $ostotilauskirja_id]);

	//Tyhjennetään alkuperäinen ostotilauskirja
	$sql = "DELETE FROM ostotilauskirja_tuote WHERE ostotilauskirja_id = ?";
	if( !$db->query($sql, [$ostotilauskirja_id]) ) {
	    return 0;
    }

	return $uusi_otk_id->last_id;
}

/**
 * Järjestetään tuotteet artikkelinumeron mukaan.
 * @param array $products
 * @return array <p> Sama array sortattuna
 */
function sortProductsByName( array $products ) : array {
	//TODO: Sitten kun Janne on saanut päivitettyä kantaan tilauskoodit,
	//TODO: muutetaan vertailu artikkelinumerosta tilauskoodeihin.
	usort($products, function ($a, $b) {
		return ($a->articleNo > $b->articleNo);
	});
	return $products;
}

if ( isset($_POST['muokkaa']) ) {
	// Ei muuteta selitettä, jos automaation lisäämä tuote
	$_POST['selite'] = $_POST['automaatti'] ? "AUTOMAATTI" : $_POST['selite'];
    // Muokataan tilauskirjan tuotetta
	$values = [
		$_POST['kpl'],
		$user->id,
		$_POST['selite'],
		$ostotilauskirja_id,
		$_POST['id'],
		$_POST['automaatti'],
		$_POST['tilaustuote']
	];
	$sql = "UPDATE ostotilauskirja_tuote
			SET kpl = ?, lisays_kayttaja_id = ?, selite = ?
            WHERE ostotilauskirja_id = ? AND tuote_id = ? AND automaatti = ? AND tilaustuote = ?";
	$result1 = $db->query($sql, $values);
	// Päivitetään sisäänostohinta
	$sql = "UPDATE tuote SET sisaanostohinta = ? WHERE id = ?";
	$result2 = $db->query($sql, [$_POST['ostohinta'], $_POST['id']]);
    if ( $result1 && $result2) {
        $_SESSION["feedback"] = "<p class='success'>Muokaus onnistui.</p>";
    } else {
        $_SESSION["feedback"] = "<p class='error'>ERROR: Muokkauksessa tapahtui virhe!</p>";
    }
}
else if ( isset($_POST['muokkaa_kaikki']) ) {
	$tuotteet = isset($_POST['tuote']) ? $_POST['tuote'] : [];
	tallenna_ostotilauskirja($db, $ostotilauskirja_id, $tuotteet);
}
else if ( isset($_POST['poista']) ) {
	$sql = "DELETE FROM ostotilauskirja_tuote WHERE tuote_id = ? AND ostotilauskirja_id = ? AND automaatti = ? AND tilaustuote = ?";
    if ( $db->query($sql, [$_POST['id'], $ostotilauskirja_id, $_POST['automaatti'], $_POST['tilaustuote']]) ) {
        $_SESSION["feedback"] = "<p class='success'>Tuote poistettu ostotilauskirjalta.</p>";
    } else {
        $_SESSION["feedback"] = "<p class='error'>ERROR</p>";
    }
}
else if ( isset($_POST['poista_kaikki']) ) {
	if ( $db->query("DELETE FROM ostotilauskirja_tuote WHERE ostotilauskirja_id = ?", [$ostotilauskirja_id]) ) {
		$_SESSION["feedback"] = "<p class='success'>Tilauskirja tyhjennetty.</p>";
	} else {
		$_SESSION["feedback"] = "<p class='error'>Tilauskirja on jo tyhjä.</p>";
	}
}
else if ( isset($_POST['siirra']) ) {
	siirra_tuote_toiselle_tilauskirjalle($db, $otk->id, (int)$_POST['uusi_tilauskirja'], (int)$_POST['tuote_id']);
}
else if ( isset($_POST['laheta']) ) {
	$id = laheta_ostotilauskirja($db, (int)$user->id, $otk->id);
    if ( $id ) {
        $_SESSION["download"] = $id;
		header("Location: yp_ostotilauskirja_odottavat.php");
		exit();
    } else {
        $_SESSION["feedback"] = "<p class='error'>Ostotilauskirjaa ei voitu lähettää! Ostotilauskirja on tyhjä.</p>";
    }
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) ){
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}
$feedback = isset($_SESSION["feedback"]) ? $_SESSION["feedback"] : "";
unset($_SESSION["feedback"]);

// Haetaan ostotilauskirjalla olevat tuotteet
$sql = "SELECT t1.*, otk_t.*,
 			(t1.sisaanostohinta * otk_t.kpl) AS kokonaishinta,
			SUM(t2.varastosaldo) AS hyllyssa_vastaavia_tuotteita,
			
			(SELECT sum(kpl)
				FROM tilaus_tuote
				INNER JOIN tilaus ON tilaus_tuote.tilaus_id = tilaus.id
					AND tilaus.paivamaara > DATE_SUB(NOW(),INTERVAL 1 YEAR)
				WHERE tuote_id = t1.id AND tilaus.maksettu = 1)
			AS vuosimyynti_kpl,
				
			(SELECT sum(tt.kpl)
				FROM tuote t3
				INNER JOIN tilaus_tuote tt ON tt.tuote_id = t3.id
				INNER JOIN tilaus ON tt.tilaus_id = tilaus.id
					AND tilaus.paivamaara > DATE_SUB(NOW(),INTERVAL 1 YEAR)
				WHERE t3.hyllypaikka = t1.hyllypaikka AND tilaus.maksettu = 1)
			AS vuosimyynti_hylly_kpl
				
        FROM ostotilauskirja_tuote otk_t
        INNER JOIN tuote t1
        	ON otk_t.tuote_id = t1.id
        LEFT JOIN tuote t2
        	ON t1.hyllypaikka = t2.hyllypaikka AND t2.hyllypaikka <> ''
        		AND t1.id != t2.id AND t2.aktiivinen = 1
        WHERE ostotilauskirja_id = ?
        GROUP BY tuote_id, automaatti, tilaustuote";
$products = $db->query($sql, [$ostotilauskirja_id], FETCH_ALL);
$products = sortProductsByName($products);

// Haetaan tuotteiden yhteishinta ja kappalemäärä
$sql = "SELECT SUM(tuote.sisaanostohinta * kpl) AS tuotteet_hinta, SUM(kpl) AS tuotteet_kpl
        FROM ostotilauskirja_tuote 
        LEFT JOIN tuote
        	ON ostotilauskirja_tuote.tuote_id = tuote.id 
        WHERE ostotilauskirja_id = ?
        GROUP BY ostotilauskirja_id";
$yht = $db->query($sql, [$ostotilauskirja_id]);
$yht_hinta = $yht ? ($yht->tuotteet_hinta + $otk->rahti) : $otk->rahti;
$yht_kpl = $yht ? $yht->tuotteet_kpl : 0;

$tilauskirjat_tuotteen_siirtamista_varten = get_ostotilauskirja_select_string($db, $otk->hankintapaikka_id);
?>

<!DOCTYPE html>
<html lang="fi" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/jsmodal-light.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
    <script src="js/jsmodal-1.0d.min.js"></script>
    <title>Ostotilauskirjat</title>
</head>
<body>
<?php require 'header.php'?>
<main class="main_body_container">

	<!-- Otsikko ja painikkeet -->
	<div class="otsikko_container">
		<section class="takaisin">
			<a href="yp_ostotilauskirja.php?id=<?=$otk->hankintapaikka_id?>" class="nappi grey"><i class="material-icons">navigate_before</i>Takaisin</a>
		</section>
		<section class="otsikko">
			<span>Ostotilauskirja</span>
			<h1><?=$otk->tunniste?></h1>
		</section>
		<section class="napit">
			<button class="nappi" onclick="varmista_lahetys()">Lähetä</button>
		</section>
	</div>

	<!-- Alaotsikko ja painikkeet -->
	<div class="flex" style="align-items: center;">
		<section>
            <h4>Arvioitu saapumispäivä: <?=date("d.m.Y", strtotime($otk->oletettu_saapumispaiva))?></h4>
		</section>
		<section style="margin-left: auto;">
			<button class="nappi red" onclick="tyhjenna_ostotilauskirja()">Tyhjennä</button>
			<button class="nappi" onclick="tallenna_ostotilauskirja()">Tallenna</button>
		</section>
	</div>

    <?= $feedback?>

	<form action="" method="post" id="muokkaa_ostotilauskirja_kaikki">
	    <table style="min-width: 90%;">
	        <thead>
	        <tr><th>Tilauskoodi</th>
	            <th>Tuotenumero</th>
	            <th>Tuote</th>
	            <th class="number">KPL</th>
	            <th class="number">Varastosaldo</th>
		        <th class="number">Myynti kpl</th>
		        <th class="number">Hyllypaikan myynti</th>
	            <th class="number">Ostohinta</th>
	            <th class="number">Yhteensä</th>
	            <th>Selite</th>
		        <th></th><!-- Tehdassaldo -->
	            <th></th><!-- Varoitus -->
		        <th></th><!-- Input -->
		        <th></th><!-- Toiminnot -->
	        </tr>
	        </thead>
	        <tbody>
	        <!-- Rahtimaksu -->
	        <tr><td colspan="2"></td>
		        <td>Rahtimaksu</td>
		        <td colspan="4"></td>
		        <td class="number"><?=format_number($otk->rahti)?></td>
	            <td class="number"><?=format_number($otk->rahti)?></td>
		        <td colspan="5"></td></tr>
	        <!-- Tuotteet -->
	        <?php foreach ($products as $index=>$product) : ?>
	            <tr class="tuote"><td><?=$product->tilauskoodi?></td>
	                <td><?=$product->tuotekoodi?></td>
	                <td><?=$product->valmistaja?><br><?=$product->nimi?></td>
	                <td class="number"><?=format_number($product->kpl,0)?></td>
	                <td class="number"><?=format_number($product->varastosaldo,0)?></td>
		            <td class="number"><?=format_number($product->vuosimyynti_kpl,0)?></td>
		            <td class="number"><?=format_number($product->vuosimyynti_hylly_kpl,0)?></td>
	                <td class="number"><?=format_number($product->sisaanostohinta)?></td>
	                <td class="number"><?=format_number($product->kokonaishinta)?></td>
		            <td>
	                    <?php if ( $product->automaatti ) : ?>
	                        <span style="color: red"><?=$product->selite?></span>
	                    <?php elseif ( $product->tilaustuote ) : ?>
		                    <span style="color: green"><?=$product->selite?></span>
	                    <?php else : ?>
		                    <?=$product->selite?>
	                    <?php endif;?>
	                </td>
		            <td></td><!-- Tehdassaldo -->
		            <td class="number"><!-- Varoitus -->
			            <?php if ( $product->hyllyssa_vastaavia_tuotteita ) : ?>
				            <span title="Hyllyssä <?=$product->hyllypaikka?> vastaavia tuotteita" style="color: rebeccapurple">
					            <i class="material-icons">warning</i>
					            <?=$product->hyllyssa_vastaavia_tuotteita?></span>
		                <?php endif; ?>
		            </td>
		            <td><!-- Input -->
			            <input type="number" value="<?=$product->kpl?>" name="tuote[<?=$index?>][kpl]" class="kpl" min="0">
			            <input type="hidden" value="<?=$product->id?>" name="tuote[<?=$index?>][id]">
			            <input type="hidden" value="<?=$product->automaatti?>" name="tuote[<?=$index?>][automaatti]">
			            <input type="hidden" value="<?=$product->tilaustuote?>" name="tuote[<?=$index?>][tilaustuote]">
		            </td>
	                <td class="toiminnot"><!-- Toiminnot -->
	                    <button type="button" class="nappi" onclick="avaa_modal_muokkaa_tuote(<?=$product->id?>,
	                            '<?=$product->tuotekoodi?>', <?=$product->kpl?>, <?=$product->sisaanostohinta?>,
	                            '<?=$product->selite?>', <?=$product->automaatti?>, <?=$product->tilaustuote?>)">
		                    Muokkaa</button>
	                    <button type="button" class="nappi red" onclick="poista_ostotilauskirjalta(
	                        <?=$product->id?>, <?=$product->automaatti?>, <?=$product->tilaustuote?>)">
		                    Poista</button>
		                <?php if ( $product->tilaustuote ) : ?>
		                    <button type="button" class="nappi" onclick="siirra(
		                        <?=$product->id?>, <?=$product->automaatti?>, <?=$product->tilaustuote?>,
		                        '<?=$product->tuotekoodi?>')">
		                        Siirrä</button>
		                <?php endif; ?>
	                </td>
	            </tr>
	        <?php endforeach;?>
	        <!-- Yhteensä -->
	        <tr class="border_top"><td>YHTEENSÄ</td>
		        <td colspan="2"></td>
	            <td class="number"><?= format_number($yht_kpl,0)?></td>
	            <td colspan="4"></td>
	            <td class="number"><?=format_number($yht_hinta)?></td>
	            <td colspan="5"></td>
	        </tr>
	        </tbody>
	    </table>
	</form>
</main>

<?php require 'footer.php'; ?>

<script type="text/javascript">


    /**
     * Modal tuotteen tietojen muokkaamiseen.
     * @param tuote_id
     * @param tuotenumero
     * @param kpl
     * @param ostohinta
     * @param selite
     * @param automaatti
     * @param tilaustuote
     */
    function avaa_modal_muokkaa_tuote( tuote_id, tuotenumero, kpl, ostohinta, selite, automaatti, tilaustuote ) {
        Modal.open( {
            content:  '\
				<h4>Muokkaa tuotteen tietoja ostotilauskirjalla.</h4>\
				<hr>\
				<br>\
				<form action="" method="post" name="muokkaa_ostotilauskirja_tuote" id="muokkaa_otk_tuote">\
					<label>Tuote</label>\
                    <h4 style="display: inline;">'+tuotenumero+'</h4>\
					<br><br>\
					<label>KPL</label>\
					<input name="kpl" type="number" value="'+kpl+'" class="kpl" title="Tilattavat kappaleet" min="1" required>\
					<br><br>\
					<label>Ostohinta (€)</label>\
					<input name="ostohinta" type="number" step="0.01" value="'+ostohinta.toFixed(2)+'" class="eur" title="Tuotteen ostohinta" required>\
					<br><br>\
					<label for="selite">Selite:</label><br> \
                    <textarea rows="3" cols="25" name="selite" form="muokkaa_otk_tuote" placeholder="Miksi lisäät tuotteen käsin?">'+selite+'</textarea>\
                    <br><br> \
					<input name="id" type="hidden" value="'+tuote_id+'">\
					<input name="automaatti" type="hidden" value="'+automaatti+'">\
					<input name="tilaustuote" type="hidden" value="'+tilaustuote+'">\
					<input class="nappi" type="submit" name="muokkaa" value="Muokkaa"> \
				</form>\
				',
            draggable: true
        });
    }

    /**
     * Siirretään tuote toiselle ostotilauskirjalle
     * @param tuote_id
     * @param automaatti
     * @param tilaustuote
     * @param tuotenumero
     */
    function siirra( tuote_id, automaatti, tilaustuote, tuotenumero ) {
        let tilauskirjat = <?=json_encode($tilauskirjat_tuotteen_siirtamista_varten)?>;
        Modal.open({
            content:  '\
				<h4>Siirrä tuote toiselle tilauskirjalle.</h4>\
				<hr>\
				<br>\
				<form action="" method="post" name="muokkaa_ostotilauskirja_tuote" id="muokkaa_otk_tuote">\
                    <label>Tuote:</label>\
                    <span style="font-weight: bold">'+tuotenumero+'</span>\
					<br><br>\
					<label>Tilauskirja:</label>\
	                '+tilauskirjat+'\
	                <br><br>\
					<input type="hidden" name="tuote_id" value="'+tuote_id+'">\
					<input type="submit" name="siirra" value="Siirrä" class="nappi"> \
				</form>\
				',
            draggable: true
        });
    }

    /**
     * Luodaan form tuotteen poistamista varten.
     * @param tuote_id
     * @param automaatti
     * @param tilaustuote
     */
	function poista_ostotilauskirjalta(tuote_id, automaatti, tilaustuote) {
        if( confirm("Haluatko varmasti poistaa tuotteen ostotilauskirjalta?") ) {
            //Rakennetaan form
            let form = document.createElement("form");
            form.setAttribute("method", "POST");
            form.setAttribute("action", "");

            //asetetaan $_POST["poista"]
            let field = document.createElement("input");
            field.setAttribute("type", "hidden");
            field.setAttribute("name", "poista");
            field.setAttribute("value", true);
            form.appendChild(field);

            //tuote_id
            field = document.createElement("input");
            field.setAttribute("type", "hidden");
            field.setAttribute("name", "id");
            field.setAttribute("value", tuote_id);
            form.appendChild(field);

            //automaatti
			field = document.createElement("input");
			field.setAttribute("type", "hidden");
			field.setAttribute("name", "automaatti");
			field.setAttribute("value", automaatti);
			form.appendChild(field);

			//tilaustuote
            field = document.createElement("input");
            field.setAttribute("type", "hidden");
            field.setAttribute("name", "tilaustuote");
            field.setAttribute("value", tilaustuote);
            form.appendChild(field);

            //form submit
            document.body.appendChild(form);
            form.submit();
        }
    }


    /**
     * Luodaan form tilauskirjan poistamista varten.
     */
	function tyhjenna_ostotilauskirja() {
		if( confirm("Haluatko varmasti tyhjentää ostotilauskirjan?") ) {
			//Rakennetaan form
			let form = document.createElement("form");
			form.setAttribute("method", "POST");
			form.setAttribute("action", "");

			//asetetaan $_POST["poista_kaikki"]
			let field = document.createElement("input");
			field.setAttribute("type", "hidden");
			field.setAttribute("name", "poista_kaikki");
			field.setAttribute("value", true);
			form.appendChild(field);

			//form submit
			document.body.appendChild(form);
			form.submit();
		}
	}

    /**
     * Lähetetään tuotteiden muokkaukseen käytettävä form.
     */
	function tallenna_ostotilauskirja() {
        let form = document.getElementById('muokkaa_ostotilauskirja_kaikki');

        //asetetaan $_POST["muokkaa_kaikki"]
        let field = document.createElement("input");
        field.setAttribute("type", "hidden");
        field.setAttribute("name", "muokkaa_kaikki");
        field.setAttribute("value", "true");
        form.appendChild(field);

        form.submit();
	}

    /**
     * Varmistetaa tilauskirjan lähetys ja luodaan form lähetystä varten.
     */
    function varmista_lahetys() {
        let vahvistus = confirm( "Haluatko varmasti lähettää ostotilauskirjan hankintapaikalle?");
        if ( vahvistus ) {
            let form = document.createElement("form");
            form.setAttribute("method", "POST");
            form.setAttribute("action", "");

            //asetetaan $_POST["laheta"]
            let field = document.createElement("input");
            field.setAttribute("type", "hidden");
            field.setAttribute("name", "laheta");
            field.setAttribute("value", "true");
            form.appendChild(field);

            //form submit
            document.body.appendChild(form);
            form.submit();
        }
    }

    /**
     * Haetaan tehdassaldot Eoltaksen tuotteille.
     */
    function hae_eoltas_tehdassaldo() {
	    let tuotteet = document.getElementsByClassName("tuote");
	    for (let i = 0; i < tuotteet.length; i++) {
            let hankintapaikka_id = tuotteet[i].cells[1].innerText.substr(0,3);
	        let articleNo = tuotteet[i].cells[1].innerText.slice(4);
            let brandName = tuotteet[i].cells[2].innerText.split("\n")[0];
	        let kpl = +tuotteet[i].cells[3].innerText;
            $.post(
                "ajax_requests.php",
                {   eoltas_tehdassaldo: true,
                    hankintapaikka_id: hankintapaikka_id,
                    articleNo: articleNo,
                    brandName: brandName },
                function( data ) {
                    if ( data ) {
                        let varoitus_kuvake = "";
                        let tehdassaldo = +data;
                        // Valitaan varoitus ikoni
                        if ( tehdassaldo === 0 ) {
                            varoitus_kuvake = "<i class='material-icons' style='color:red;' " +
	                            "title='Tehdassaldo nolla (0).'>highlight_off</i>";
                        } else if ( (tehdassaldo - kpl) < 0 ) {
                            varoitus_kuvake = "<i class='material-icons' style='color:goldenrod;' " +
                                "title='Tehdassaldo " + tehdassaldo + " kpl'>highlight_off</i>";
                        } else {
                            varoitus_kuvake = "<i class='material-icons' style='color:green;' " +
	                            "title='Tehdassaldo " + tehdassaldo + " kpl'>check_circle</i>";
                        }
                        tuotteet[i].cells[10].innerHTML += varoitus_kuvake;
                    }
                });
	    }
    }

    $(document).ready(function(){
        hae_eoltas_tehdassaldo();
    });

</script>
</body>
</html>
