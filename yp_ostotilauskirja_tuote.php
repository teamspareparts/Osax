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
 * Ostotilauskirjan lähetys
 * @param DByhteys $db
 * @param User $user
 * @param $ostotilauskirja_id
 * @return bool Palauttaa false tai arkistoidun ostotilauskirjan id:n.
 */
function laheta_ostotilauskirja(DByhteys $db, User $user, $ostotilauskirja_id){
	//Lisätään osotilauskirja arkistoon
	$sql = "INSERT INTO ostotilauskirja_arkisto ( hankintapaikka_id, tunniste, rahti, oletettu_saapumispaiva, lahetetty, lahettaja)
            SELECT hankintapaikka_id, tunniste, rahti, oletettu_saapumispaiva, NOW(), ? FROM ostotilauskirja
            WHERE id = ? ";
	if (!$db->query($sql, [$user->id, $ostotilauskirja_id])) return false;
	$uusi_otk_id = $db->query("SELECT LAST_INSERT_ID() AS last_id", []);


	//Lisätään ostotilauskirjan tuotteet arkistoon
	$sql = "SELECT * FROM ostotilauskirja_tuote
 			LEFT JOIN tuote
 			 ON ostotilauskirja_tuote.tuote_id = tuote.id
 			WHERE ostotilauskirja_id = ?";
	if( !$products = $db->query($sql, [$ostotilauskirja_id], FETCH_ALL) ) return false;
	foreach ($products as $product) {
		$result = $db->query("	INSERT INTO ostotilauskirja_tuote_arkisto (ostotilauskirja_id, tuote_id, kpl, 
										lisays_tapa, lisays_pvm, lisays_kayttaja_id, ostohinta) 
 								VALUES(?, ?, ?, ?, ?, ?, ?)",
			[$uusi_otk_id->last_id, $product->id, $product->kpl, $product->lisays_tapa,
			$product->lisays_pvm, $product->lisays_kayttaja_id,
			$product->sisaanostohinta]);
		if( !$result ) return false;
    }

	//Tyhjennetään alkuperäinen ostotilauskirja
	//$sql = "DELETE FROM ostotilauskirja_tuote WHERE ostotilauskirja_id = ?";
	//if( !$db->query($sql, [$ostotilauskirja_id]) ) return false;

	return $uusi_otk_id->last_id;
}


if ( isset($_POST['muokkaa']) ) {
    unset($_POST['muokkaa']);
    $sql1 = "  UPDATE ostotilauskirja_tuote
              SET kpl = ?
              WHERE ostotilauskirja_id = ? AND tuote_id = ?";
    $sql2 = " UPDATE tuote SET sisaanostohinta = ? WHERE id = ?";
    if ( $db->query($sql1, [$_POST['kpl'], $ostotilauskirja_id, $_POST['id']] ) &&
        $db->query($sql2, [$_POST['ostohinta'], $_POST['id']])) {
        $_SESSION["feedback"] = "<p class='success'>Muokaus onnistui.</p>";
    } else {
        $_SESSION["feedback"] = "<p class='error'>ERROR: Muokkauksessa tapahtui virhe!</p>";
    }
}
else if( isset($_POST['poista']) ) {
    unset($_POST['poista']);
    if ( $db->query("DELETE FROM ostotilauskirja_tuote WHERE tuote_id = ? AND ostotilauskirja_id = ?",
                    [$_POST['id'], $ostotilauskirja_id]) ) {
        $_SESSION["feedback"] = "<p class='success'>Tuote poistettu ostotilauskirjalta.</p>";
    } else {
        $_SESSION["feedback"] = "<p class='error'>ERROR</p>";
    }
}
else if( isset($_POST['laheta']) ) {
    unset($_POST['laheta']);
    if ( $id = laheta_ostotilauskirja($db, $user, $_POST['id']) ) {
        $_SESSION["download"] = $id;
		header("Location: yp_ostotilauskirja_odottavat.php");
		exit();
    } else {
        $_SESSION["feedback"] = "<p class='error'>ERROR. Ostotilauskirjaa ei jostain syystä voitu lähettää.</p>";
    }
}


if ( !empty($_POST) ){
    header("Location: " . $_SERVER['REQUEST_URI']); //Estää formin uudelleenlähetyksen
    exit();
}
$feedback = isset($_SESSION["feedback"]) ? $_SESSION["feedback"] : "";
unset($_SESSION["feedback"]);


$sql = "  SELECT *, tuote.sisaanostohinta*ostotilauskirja_tuote.kpl AS kokonaishinta FROM ostotilauskirja_tuote
          LEFT JOIN tuote
            ON ostotilauskirja_tuote.tuote_id = tuote.id 
          WHERE ostotilauskirja_id = ?
          GROUP BY tuote_id";
$products = $db->query($sql, [$ostotilauskirja_id], FETCH_ALL);

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
    <section>
        <h1 class="otsikko">Ostotilauskirja</h1>
        <div id="painikkeet">
            <a class="nappi grey" href="yp_ostotilauskirja.php?id=<?=$otk->hankintapaikka_id?>">Takaisin</a>
            <button class="nappi" onclick="varmista_lahetys(<?=$otk->id?>)">Lähetä</button>

        </div>
        <h3><?=$otk->tunniste?><br><span style="font-size: small;">Arvioitu saapumispäivä: <?=date("d.m.Y", strtotime($otk->oletettu_saapumispaiva))?></span></h3>
    </section>

    <?= $feedback?>

        <table style="min-width: 90%;"><!-- Katalogissa saatavilla, tilattavissa olevat tuotteet (varastosaldo > 0) -->
            <thead>
            <tr><th>Tilauskoodi</th>
                <th>Tuotenumero</th>
                <th>Tuote</th>
                <th class="number">KPL</th>
                <th class="number">Ostohinta</th>
                <th class="number">Yhteensä</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <!-- Rahtimaksu -->
            <tr><td></td><td></td><td>Rahtimaksu</td><td class="number"></td><td class="number"><?=format_euros($otk->rahti)?></td>
                <td class="number"><?=format_euros($otk->rahti)?></td><td></td></tr>
            <!-- Tuotteet -->
            <?php foreach ($products as $product) : ?>
                <tr><td><?=$product->tilauskoodi?></td>
                    <td><?=$product->tuotekoodi?></td>
                    <td><?=$product->valmistaja?><br><?=$product->nimi?></td>
                    <td class="number"><?=format_integer($product->kpl)?></td>
                    <td class="number"><?=format_euros($product->sisaanostohinta)?></td>
                    <td class="number"><?=format_euros($product->kokonaishinta)?></td>
                    <td class="toiminnot">
                        <button class="nappi" onclick="avaa_modal_muokkaa_tuote(<?=$product->id?>,
                            '<?=$product->tuotekoodi?>', <?=$product->kpl?>, <?=$product->sisaanostohinta?>)">Muokkaa</button>
                        <button class="nappi" onclick="poista_ostotilauskirjalta('<?=$product->id?>')">Poista</button>
                    </td>
                </tr>
            <?php endforeach;?>
            <!-- Yhteensä -->
            <tr><td style="border-top: 1px solid black;">YHTEENSÄ</td><td style="border-top: 1px solid black"></td><td style="border-top: 1px solid black"></td>
                <td class="number" style="border-top: 1px solid black"><?= format_integer($yht_kpl)?></td>
                <td style="border-top: 1px solid black"></td>
                <td class="number" style="border-top: 1px solid black"><?=format_euros($yht_hinta)?></td>
                <td style="border-top: 1px solid black"></td>
            </tr>


            </tbody>
        </table>
</main>




<script type="text/javascript">


    /**
     *
     * @param tuote_id
     * @param tuotenumero
     * @param kpl
     * @param ostohinta
     */
    function avaa_modal_muokkaa_tuote(tuote_id, tuotenumero, kpl, ostohinta){
        Modal.open( {
            content:  '\
				<h4>Muokkaa tuotteen tietoja ostotilauskirjalla.</h4>\
				<hr>\
				<br>\
				<form action="" method="post" name="muokkaa_hankintapaikka">\
					<label><span>Tuote</span></label>\
                    <h4 style="display: inline;">'+tuotenumero+'</h4>\
					<br><br>\
					<label><span>KPL</span></label>\
					<input name="kpl" type="number" value="'+kpl+'" title="Tilattavat kappaleet" min="1" required>\
					<br><br>\
					<label><span>Ostohinta (€)</span></label>\
					<input name="ostohinta" type="number" step="0.01" value="'+ostohinta.toFixed(2)+'" title="Tuotteen ostohinta" required>\
					<br><br>\
					<input name="id" type="hidden" value="'+tuote_id+'">\
					<input class="nappi" type="submit" name="muokkaa" value="Muokkaa"> \
				</form>\
				',
            draggable: true
        });
    }

    /**
     *
     * @param tuote_id
     */
    function poista_ostotilauskirjalta(tuote_id){
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

            field = document.createElement("input");
            field.setAttribute("type", "hidden");
            field.setAttribute("name", "id");
            field.setAttribute("value", tuote_id);
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
                return false;
            });
    });


</script>
</body>
</html>
