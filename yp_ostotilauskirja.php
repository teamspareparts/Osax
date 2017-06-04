<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
require 'apufunktiot.php';
if ( !$user->isAdmin() ) {
    header("Location:etusivu.php"); exit();
}

//tarkastetaan onko GET muuttuja sallittu ja haetaan hankintapaikan tiedot
$hankintapaikka_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$hp = $db->query("SELECT * FROM hankintapaikka WHERE id = ? LIMIT 1", [$hankintapaikka_id])) {
    header("Location: yp_ostotilauskirja_hankintapaikka.php"); exit();
}

/** Ostotilauskirjan lisäys */
if ( isset($_POST['lisaa']) ) {
    $toimitusjakso = isset($_POST["toimitusjakso"]) ? $_POST["toimitusjakso"] : 0;
	$arr = [
		$_POST["tunniste"],
		$_POST["lahetyspvm"],
		$_POST["saapumispvm"],
		$_POST["rahti"],
		$toimitusjakso,
		$_POST["hankintapaikka_id"],
	];
    //Tarkastetaan onko hankintapaikalla jo toistuva tilauskirja
	$sql = "SELECT  id FROM ostotilauskirja 
            WHERE   toimitusjakso > 0 AND hankintapaikka_id = ?  
            LIMIT   1";
	$result = $db->query($sql, [$hankintapaikka_id]);
    if ( $toimitusjakso && $result) { //Vain yksi toistuva ostotilauskirja
        $_SESSION["feedback"] = "<p class='error'>Hankintapaikalla voi olla vain 
                                    yksi aktiivinen tilauskirja.</p>";
    }
    else {
		$sql = "INSERT IGNORE INTO ostotilauskirja 
                (tunniste, oletettu_lahetyspaiva, oletettu_saapumispaiva,
                rahti, toimitusjakso, hankintapaikka_id)
                VALUES ( ?, ?, ?, ?, ?, ? )";
		if ($db->query($sql, $arr)) {
			$_SESSION["feedback"] = "<p class='success'>Uusi ostotilauskirja lisätty.</p>";
		} else {
			$_SESSION["feedback"] = "<p class='error'>Ostotilauskirjan tunniste varattu.</p>";
		}
	}
}

/** Ostotilauskirjan muokkaus */
else if ( isset($_POST['muokkaa']) ) {
	$toimitusjakso = isset($_POST["toimitusjakso"]) ? $_POST["toimitusjakso"] : 0;
	$arr = [
		$_POST["lahetyspvm"],
		$_POST["saapumispvm"],
		$_POST["rahti"],
		$toimitusjakso,
		$_POST["ostotilauskirja_id"],
	];
    $sql = "  UPDATE ostotilauskirja
              SET oletettu_lahetyspaiva = ?, oletettu_saapumispaiva = ?, rahti = ?, toimitusjakso = ?
              WHERE id = ?";
    if ( $db->query($sql, $arr) ) {
        $_SESSION["feedback"] = "<p class='success'>Muokaus onnistui.</p>";
    }
    //Merkataan, että tuotteiden riittävyys on laskettava uudelleen
    $sql = "    UPDATE tuote
                SET paivitettava = 1
                WHERE id IN (SELECT tuote_id FROM ostotilauskirja_tuote WHERE ostotilauskirja_id = ?)";
    $db->query($sql, [$_POST["ostotilauskirja_id"]]);
}

/** Ostotilauskirjan poistaminen */
else if( isset($_POST['poista']) ) {
    unset($_POST['poista']);
	$db->query("DELETE FROM ostotilauskirja_tuote WHERE ostotilauskirja_id = ?", array_values($_POST));
    if ( $db->query("DELETE FROM ostotilauskirja WHERE id = ?", array_values($_POST)) ) {
        $_SESSION["feedback"] = "<p class='success'>Ostotilauskirja poistettu.</p>";
    } else {
        $_SESSION["feedback"] = "<p class='error'>ERROR</p>";
    }
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) ){
    header("Location: " . $_SERVER['REQUEST_URI']); //Estää formin uudelleenlähetyksen
    exit();
}
$feedback = isset($_SESSION["feedback"]) ? $_SESSION["feedback"] : "";
unset($_SESSION["feedback"]);




//haetaan ostotilauskirjat
$sql = "SELECT *, ostotilauskirja.id AS id, SUM(kpl*tuote.sisaanostohinta) AS hinta, COUNT(ostotilauskirja_tuote.tuote_id) AS kpl FROM ostotilauskirja
 		LEFT JOIN ostotilauskirja_tuote
 			ON ostotilauskirja.id = ostotilauskirja_tuote.ostotilauskirja_id
 		LEFT JOIN tuote
 		    ON ostotilauskirja_tuote.tuote_id = tuote.id
 		WHERE ostotilauskirja.hankintapaikka_id = ?
 		GROUP BY ostotilauskirja.id";
$ostotilauskirjat = $db->query($sql, [$hankintapaikka_id], FETCH_ALL);


?>



<!DOCTYPE html>
<html lang="fi" xmlns="http://www.w3.org/1999/html">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="css/jsmodal-light.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
<link rel="stylesheet" href="https://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
<script src="js/jsmodal-1.0d.min.js"></script>
<title>Ostotilauskirjat</title>
</head>
<body>
<?php require 'header.php'?>
<main class="main_body_container">
    <section>
        <h1 class="otsikko">Ostotilauskirja</h1>
        <div id="painikkeet">
            <a class="nappi grey" href="yp_ostotilauskirja_hankintapaikka.php">Takaisin</a>
            <button class="nappi" type="button" onClick="avaa_modal_uusi_ostotilauskirja('<?=$hankintapaikka_id?>')">
                Uusi ostotilauskirja</button>
        </div>
    </section>
    <section>
        <h2><?=$hp->id?> - <?=$hp->nimi?></h2>
        <h4>Valitse ostotilauskirja:</h4>
    </section>

    <?= $feedback?>

    <?php if ( $ostotilauskirjat ) : ?>
        <table>
            <thead>
            <tr><th>Tunniste</th>
                <th>Toimitusväli</th>
                <th>Lähetyspäivä</th>
                <th>Saapumispäivä</th>
                <th>Tuotteet</th>
                <th>Hinta</th>
                <th>Rahti</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ( $ostotilauskirjat as $otk ) : ?>
                <tr>
                    <td data-href="yp_ostotilauskirja_tuote.php?id=<?=$otk->id?>">
                        <?= $otk->tunniste?></td>
                    <td data-href="yp_ostotilauskirja_tuote.php?id=<?=$otk->id?>">
                        <?php if (!$otk->toimitusjakso) : ?>
                            ERIKOISTILAUS
                        <?php else : ?>
						    <?= $otk->toimitusjakso?> viikkoa
                        <?php endif;?>
                    </td>
                    <td data-href="yp_ostotilauskirja_tuote.php?id=<?=$otk->id?>">
						<?= date("d.m.Y", strtotime($otk->oletettu_lahetyspaiva))?></td>
                    <td data-href="yp_ostotilauskirja_tuote.php?id=<?=$otk->id?>">
                        <?= date("d.m.Y", strtotime($otk->oletettu_saapumispaiva))?></td>
                    <td data-href="yp_ostotilauskirja_tuote.php?id=<?=$otk->id?>">
                        <?= format_integer($otk->kpl)?></td>
                    <td data-href="yp_ostotilauskirja_tuote.php?id=<?=$otk->id?>">
                        <?= format_euros($otk->hinta)?></td>
                    <td data-href="yp_ostotilauskirja_tuote.php?id=<?=$otk->id?>">
                        <?= format_euros($otk->rahti)?></td>
                    <td class="toiminnot">
                        <a class="nappi" href='javascript:void(0)'
                           onclick="avaa_modal_muokkaa_ostotilauskirja('<?=$otk->tunniste?>',
                                   '<?= date("Y-m-d", strtotime($otk->oletettu_lahetyspaiva))?>',
                                    '<?= date("Y-m-d", strtotime($otk->oletettu_saapumispaiva))?>',
                                    '<?= $otk->rahti?>', '<?= $otk->toimitusjakso?>', '<?= $otk->id?>')">
                                    Muokkaa</a>
                        <a class="nappi" href='javascript:void(0)'
                           onclick="poista_ostotilauskirja('<?= $otk->id?>')">Poista</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p>Ei ostotilaukirjoja.</p>
    <?php endif; ?>


</main>




<script type="text/javascript">

    function avaa_modal_uusi_ostotilauskirja( hankintapaikka_id ) {
        let date = new Date().toISOString().slice(0,10);
        Modal.open({
            content: '\
            <h4>Anna uuden ostotilauskirjan tiedot.</h4>\
            <br><br>\
                <form action="" method="post" name="uusi_ostotilauskirja">\
					<label>Tunniste</label>\
					<input name="tunniste" type="text" placeholder="Ostotilauskirjan nimi" pattern=".{3,}" required>\
					<br><br>\
					<label>Lähetyspäivä</label>\
					<input name="lahetyspvm" type="text" class="datepicker" value="'+date+'" title="Arvioitu saapumispäivä" required>\
					<br><br>\
					<label>Saapumispäivä</label>\
					<input name="saapumispvm" type="text" class="datepicker" value="'+date+'" title="Arvioitu saapumispäivä" required>\
					<br><br>\
					<label>Rahtimaksu (€)</label>\
					<input name="rahti" type="number" step="0.01" value="200.00" title="Rahtimaksu" required>\
					<br><br>\
					<label>Tilauksen tyyppi</label>\
                    <input type="radio" name="tyyppi" value="vakiotilaus" checked> Toistuva \
                    <input type="radio" name="tyyppi" value="erikoistilaus"> Erikoistilaus \
					<br><br>\
					<div id="toimitusjakso_div">\
					    <label>Tilausväli (vko)</label>\
					    <input name="toimitusjakso" id="toimitusjakso" type="number" step="1" min="1" placeholder="6" title="Tilausväli viikkoina" required>\
					    <br><br>\
					</div>\
					<input name="hankintapaikka_id" type="hidden" value="'+hankintapaikka_id+'">\
					<input class="nappi" type="submit" name="lisaa" value="Tallenna" id="lisaa_ostotilauskirja"> \
				</form>\
				',
            draggable: true
        });
    }

    function avaa_modal_muokkaa_ostotilauskirja(tunniste, lahetyspvm, saapumispvm, rahti, tilausjakso, ostotilauskirja_id){
        if (tilausjakso !== 0) {
            tilausjakso = '<input name="toimitusjakso" type="number" step="1" value="'+tilausjakso+'" min="1" placeholder="6" title="Tilausväli viikkoina" required>';
        } else {
        	tilausjakso = "ERIKOISTILAUS";
        }
        Modal.open( {
            content:  '\
				<h4>Muokkaa ostitilauskirjan tietoja.</h4>\
				<hr>\
				<br>\
				<form action="" method="post" name="muokkaa_hankintapaikka">\
					<label>Tunniste</label>\
                    <h4 style="display: inline;">'+tunniste+'</h4>\
					<br><br>\
					<label>Lähetyspäivä</label>\
					<input name="lahetyspvm" type="text" class="datepicker" value="'+lahetyspvm+'" title="Arvioitu lähetyspäivä" required>\
					<br><br>\
                    <label>Saapumispäivä</label>\
					<input name="saapumispvm" type="text" class="datepicker" value="'+saapumispvm+'" title="Arvioitu saapumispäivä" required>\
					<br><br>\
					<label>Rahtimaksu (€)</label>\
					<input name="rahti" type="number" step="0.01" value="'+rahti+'" title="Rahtimaksu">\
					<br><br>\
					<label>Tilausväli (vko)</label>\
                    '+ tilausjakso +'\
	                <br><br>\
					<input name="ostotilauskirja_id" type="hidden" value="'+ostotilauskirja_id+'">\
					<input class="nappi" type="submit" name="muokkaa" value="Muokkaa"> \
				</form>\
				',
            draggable: true
        });
    }

    function poista_ostotilauskirja(ostotilauskirja_id){
        if( confirm("Haluatko varmasti poistaa kyseisen ostotilauskirjan?") ) {
            //Rakennetaan form
            let form = document.createElement("form");
            form.setAttribute("method", "POST");
            form.setAttribute("action", "");

            //asetetaan $_POST["poista"]
            let field = document.createElement("input");
            field.setAttribute("type", "hidden");
            field.setAttribute("name", "poista");
            field.setAttribute("value", "true");
            form.appendChild(field);

            field = document.createElement("input");
            field.setAttribute("type", "hidden");
            field.setAttribute("name", "ostotilauskirja_id");
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

        /** Ostotilauskirjan lisäys -modalin toiminta */

		$(document.body).on('change', 'input[name="tyyppi"]:radio', function() {
			let toimitusjakso_div = $("#toimitusjakso_div");
			let toimitusjakso_input = $("#toimitusjakso");
			if (this.value === 'vakiotilaus') {
				toimitusjakso_input.prop('required', true);
				toimitusjakso_div.show();
			}
			else if (this.value === 'erikoistilaus') {
				toimitusjakso_input.prop('required', false);
				toimitusjakso_div.hide();
			}
		})
        .on('focus', ".datepicker", function () {
            $(this).datepicker({
            	dateFormat: 'yy-mm-dd',
				minDate: new Date(),
            })
				.keydown(function(e){
					e.preventDefault();
				});
		});
    });



</script>
</body>
</html>