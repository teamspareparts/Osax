<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
require 'apufunktiot.php';
if ( !$user->isAdmin() ) {
    header("Location:etusivu.php"); exit();
}

//tarkastetaan onko GET muuttujat sallittuja ja haetaan ostotilauskirjan tiedot
$ostotilauskirja_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$otk = $db->query("SELECT * FROM ostotilauskirja WHERE id = ? LIMIT 1", [$ostotilauskirja_id])) {
	header("Location: yp_ostotilauskirja_hankintapaikka.php"); exit();
}

/**
 * Lasketaan halutun hankintapaikan keskimääräinen toimitusaika
 * @param DByhteys $db
 * @param int $hankintapaikka_id
 * @return float|int
 */
function get_toimitusaika(DByhteys $db, /*int*/ $hankintapaikka_id) {
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
 * Ostotilauskirjan lähetys
 * @param DByhteys $db
 * @param User $user
 * @param $ostotilauskirja_id
 * @return bool Palauttaa false tai arkistoidun ostotilauskirjan id:n.
 */
function laheta_ostotilauskirja(DByhteys $db, User $user, $ostotilauskirja_id){

    //Haetaan ostotilauskirjan tuotteet
    $sql = "SELECT * FROM ostotilauskirja_tuote
 			LEFT JOIN tuote
 			  ON ostotilauskirja_tuote.tuote_id = tuote.id
 			WHERE ostotilauskirja_id = ?";
    if( !$products = $db->query($sql, [$ostotilauskirja_id], FETCH_ALL) ) {
        return false;
    }

    //Haetaan hankintapaikan keskimääräinen toimitusaika
    $hankintapaikka_id = $db->query("SELECT hankintapaikka_id FROM ostotilauskirja WHERE id = ?",
        [$ostotilauskirja_id])->hankintapaikka_id;

    $toimitusaika = get_toimitusaika($db, $hankintapaikka_id);


	//Lisätään ostotilauskirja arkistoon
	$sql = "INSERT INTO ostotilauskirja_arkisto ( hankintapaikka_id, tunniste, rahti, oletettu_saapumispaiva, lahetetty, lahettaja, ostotilauskirja_id)
            SELECT hankintapaikka_id, tunniste, rahti, NOW() + INTERVAL ? DAY , NOW(), ?, id FROM ostotilauskirja
            WHERE id = ? ";
	if (!$db->query($sql, [$toimitusaika, $user->id, $ostotilauskirja_id])) {
	    return false;
	}
	$uusi_otk_id = $db->query("SELECT LAST_INSERT_ID() AS last_id", []);


    //Lisätään ostotilauskirjan tuotteet arkistoon (kaikki kerralla)
    $sql_insert_values = [];
    $questionmarks = implode(',', array_fill(0, count($products), '(?, ?, ?, ?, ?, ?, ?, ?, ?)'));
	$sql = "INSERT INTO ostotilauskirja_tuote_arkisto (ostotilauskirja_id, tuote_id, automaatti,
	        original_kpl, kpl, selite, lisays_pvm, lisays_kayttaja_id, ostohinta) 
 								VALUES {$questionmarks}";

	foreach ($products as $product) {
		array_push($sql_insert_values, $uusi_otk_id->last_id, $product->id, $product->automaatti, $product->kpl, $product->kpl,
			$product->selite, $product->lisays_pvm, $product->lisays_kayttaja_id, $product->sisaanostohinta);
    }

    $result = $db->query($sql, $sql_insert_values);
	if( !$result ) {
		return false;
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
	    return false;
    }

	return $uusi_otk_id->last_id;
}

/** Järjestetään tuotteet artikkelinumeron mukaan
 * @param $catalog_products
 * @return array <p> Sama array sortattuna
 */
function sortProductsByName( $products ){
	usort($products, "cmpName");
	return $products;
}

//TODO: Sitten kun Janne on saanut päivitettyä kantaan tilauskoodit,
//TODO: muutetaan vertailu artikkelinumerosta tilauskoodeihin.
/** Vertailufunktio usortille.
 * @param $a
 * @param $b
 * @return bool
 */
function cmpName($a, $b) {
	return ($a->articleNo > $b->articleNo);
}


if ( isset($_POST['muokkaa']) ) {
    unset($_POST['muokkaa']);
    if ( $_POST['automaatti']) { //Ei muuteta selitettä, jos automaation lisäämä tuote
        $_POST['selite'] = "AUTOMAATTI";
    }
    $sql1 = "  UPDATE ostotilauskirja_tuote
              SET kpl = ?, lisays_kayttaja_id = ?, selite = ?
              WHERE ostotilauskirja_id = ? AND tuote_id = ? AND automaatti = ?";
    $sql2 = " UPDATE tuote SET sisaanostohinta = ? WHERE id = ?";
    if ( $db->query($sql1, [$_POST['kpl'], $user->id, $_POST['selite'], $ostotilauskirja_id, $_POST['id'], $_POST['automaatti']]) &&
        $db->query($sql2, [$_POST['ostohinta'], $_POST['id']]) ) {
        $_SESSION["feedback"] = "<p class='success'>Muokaus onnistui.</p>";
    } else {
        $_SESSION["feedback"] = "<p class='error'>ERROR: Muokkauksessa tapahtui virhe!</p>";
    }
}
else if( isset($_POST['poista']) ) {
    unset($_POST['poista']);
    if ( $db->query("DELETE FROM ostotilauskirja_tuote WHERE tuote_id = ? AND ostotilauskirja_id = ? AND automaatti = ? ",
                    [$_POST['id'], $ostotilauskirja_id, $_POST['automaatti']]) ) {
        $_SESSION["feedback"] = "<p class='success'>Tuote poistettu ostotilauskirjalta.</p>";
    } else {
        $_SESSION["feedback"] = "<p class='error'>ERROR</p>";
    }
}
else if( isset($_POST['poista_kaikki']) ) {
	if ( $db->query("DELETE FROM ostotilauskirja_tuote WHERE ostotilauskirja_id = ?", [$ostotilauskirja_id]) ) {
		$_SESSION["feedback"] = "<p class='success'>Tilauskirja tyhjennetty.</p>";
	} else {
		$_SESSION["feedback"] = "<p class='error'>Tilauskirja on jo tyhjä.</p>";
	}
}
else if( isset($_POST['laheta']) ) {
    unset($_POST['laheta']);
    if ( $id = laheta_ostotilauskirja($db, $user, $_POST['id']) ) {
        $_SESSION["download"] = $id;
		header("Location: yp_ostotilauskirja_odottavat.php");
		exit();
    } else {
        $_SESSION["feedback"] = "<p class='error'>Ostotilauskirjaa ei voitu lähettää! Ostotilauskirja on tyhjä.</p>";
    }
}


if ( !empty($_POST) ){
    header("Location: " . $_SERVER['REQUEST_URI']); //Estää formin uudelleenlähetyksen
    exit();
}
$feedback = isset($_SESSION["feedback"]) ? $_SESSION["feedback"] : "";
unset($_SESSION["feedback"]);


$sql = "  SELECT *, tuote.sisaanostohinta*ostotilauskirja_tuote.kpl AS kokonaishinta 
          FROM ostotilauskirja_tuote
          LEFT JOIN tuote
            ON ostotilauskirja_tuote.tuote_id = tuote.id 
          WHERE ostotilauskirja_id = ?
          GROUP BY tuote_id, automaatti";
$products = $db->query($sql, [$ostotilauskirja_id], FETCH_ALL);
$products = sortProductsByName($products);

$sql = "  SELECT SUM(tuote.sisaanostohinta * kpl) AS tuotteet_hinta, SUM(kpl) AS tuotteet_kpl
          FROM ostotilauskirja_tuote 
          LEFT JOIN tuote
            ON ostotilauskirja_tuote.tuote_id = tuote.id 
          WHERE ostotilauskirja_id = ?
          GROUP BY ostotilauskirja_id";
$yht = $db->query($sql, [$ostotilauskirja_id]);
$yht_hinta = $yht ? ($yht->tuotteet_hinta + $otk->rahti) : $otk->rahti;
$yht_kpl = $yht ? $yht->tuotteet_kpl : 0;
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
	<div class="otsikko_container">
		<section class="takaisin">
			<a href="yp_ostotilauskirja.php?id=<?=$otk->hankintapaikka_id?>" class="nappi grey">Takaisin</a>
		</section>
		<section class="otsikko">
			<span>Ostotilauskirja</span>
			<h1><?=$otk->tunniste?></h1>
		</section>
		<section class="napit">
			<button class="nappi" onclick="varmista_lahetys(<?=$otk->id?>)">Lähetä</button>
		</section>
	</div>

    <h4>Arvioitu saapumispäivä: <?=date("d.m.Y", strtotime($otk->oletettu_saapumispaiva))?></h4>

    <?= $feedback?>

    <table style="min-width: 90%;"><!-- Katalogissa saatavilla, tilattavissa olevat tuotteet (varastosaldo > 0) -->
        <thead>
        <tr><th>Tilauskoodi</th>
            <th>Tuotenumero</th>
            <th>Tuote</th>
            <th class="number">KPL</th>
            <th class="number">Varastosaldo</th>
            <th class="number">Ostohinta</th>
            <th class="number">Yhteensä</th>
            <th>Selite</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <!-- Rahtimaksu -->
        <tr><td></td><td></td><td>Rahtimaksu</td><td></td><td></td><td class="number"><?=format_euros($otk->rahti)?></td>
            <td class="number"><?=format_euros($otk->rahti)?></td><td></td><td></td></td></tr>
        <!-- Tuotteet -->
        <?php foreach ($products as $product) : ?>
            <tr><td><?=$product->tilauskoodi?></td>
                <td><?=$product->tuotekoodi?></td>
                <td><?=$product->valmistaja?><br><?=$product->nimi?></td>
                <td class="number"><?=format_integer($product->kpl)?></td>
                <td class="number"><?=$product->varastosaldo?></td>
                <td class="number"><?=format_euros($product->sisaanostohinta)?></td>
                <td class="number"><?=format_euros($product->kokonaishinta)?></td>
                <td>
                    <?php if ( $product->automaatti ) : ?>
                        <span style="color: red"><?=$product->selite?></span>
                    <?php else : ?>
				        <?=$product->selite?>
                    <?php endif;?>
                </td>
                <td class="toiminnot">
                    <button class="nappi" onclick="avaa_modal_muokkaa_tuote(<?=$product->id?>,
                            '<?=$product->tuotekoodi?>', <?=$product->kpl?>, <?=$product->sisaanostohinta?>,
                            '<?=$product->selite?>', <?=$product->automaatti?>)">Muokkaa</button>
                    <button class="nappi" onclick="poista_ostotilauskirjalta(
                    <?=$product->id?>, <?=$product->automaatti?>)">Poista</button>
                </td>
            </tr>
        <?php endforeach;?>
        <!-- Yhteensä -->
        <tr><td style="border-top: 1px solid black;">YHTEENSÄ</td><td colspan="2" style="border-top: 1px solid black"></td>
            <td class="number" style="border-top: 1px solid black"><?= format_integer($yht_kpl)?></td>
            <td style="border-top: 1px solid black"></td>
            <td class="number" style="border-top: 1px solid black"><?=format_euros($yht_hinta)?></td>
            <td colspan="3" style="border-top: 1px solid black"></td>
        </tr>


        </tbody>
    </table>

	<div id="painikkeet">
		<button class="nappi red" onclick="tyhjenna_ostotilauskirja()">Tyhjennä</button>
	</div>
</main>

<?php require 'footer.php'; ?>

<script type="text/javascript">


    function avaa_modal_muokkaa_tuote(tuote_id, tuotenumero, kpl, ostohinta, selite, automaatti){
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
					<input name="kpl" type="number" value="'+kpl+'" title="Tilattavat kappaleet" min="1" required>\
					<br><br>\
					<label>Ostohinta (€)</label>\
					<input name="ostohinta" type="number" step="0.01" value="'+ostohinta.toFixed(2)+'" title="Tuotteen ostohinta" required>\
					<br><br>\
					<label for="selite">Selite:</label><br> \
                    <textarea rows="3" cols="25" name="selite" form="muokkaa_otk_tuote" placeholder="Miksi lisäät tuotteen käsin?">'+selite+'</textarea>\
                    <br><br> \
					<input name="id" type="hidden" value="'+tuote_id+'">\
					<input name="automaatti" type="hidden" value="'+automaatti+'">\
					<input class="nappi" type="submit" name="muokkaa" value="Muokkaa"> \
				</form>\
				',
            draggable: true
        });
    }

    /**
     *
     * @param tuote_id
     * @param automaatti
     */
	function poista_ostotilauskirjalta(tuote_id, automaatti){
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

            //form submit
            document.body.appendChild(form);
            form.submit();
        }
    }

	function tyhjenna_ostotilauskirja(){
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

    function varmista_lahetys(ostotilauskirja_id){
        let vahvistus = confirm( "Haluatko varmasti lähettää ostotilauskirjan hankintapaikalle?");
        if ( vahvistus ) {
            let form = document.createElement("form");
            form.setAttribute("method", "POST");
            form.setAttribute("action", "");

            //asetetaan $_POST["laheta"]
            let field = document.createElement("input");
            field.setAttribute("type", "hidden");
            field.setAttribute("name", "laheta");
            field.setAttribute("value", true);
            form.appendChild(field);

            field = document.createElement("input");
            field.setAttribute("type", "hidden");
            field.setAttribute("name", "id");
            field.setAttribute("value", ostotilauskirja_id);
            form.appendChild(field);

            //form submit
            document.body.appendChild(form);
            form.submit();
        }
    }



    $(document).ready(function(){

        $('*[data-href]')
            .css('cursor', 'pointer')
            .click(function(){
                window.location = $(this).data('href');
                //return false;
            });
    });


</script>
</body>
</html>
