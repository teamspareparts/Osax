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
    unset($_POST['lisaa']);
    $sql = "  INSERT IGNORE INTO ostotilauskirja 
              (tunniste, oletettu_saapumispaiva, rahti, hankintapaikka_id)
              VALUES ( ?, ?, ?, ? )";
    if ( $db->query($sql, array_values($_POST)) ) {
        $_SESSION["feedback"] = "<p class='success'>Uusi ostotilauskirja lisätty.</p>";
    } else {
        $_SESSION["feedback"] = "<p class='error'>Ostotilauskirjan tunniste varattu.</p>";
    }
}
/** Ostotilauskirjan muokkaus */
else if ( isset($_POST['muokkaa']) ) {
    unset($_POST['muokkaa']);
    $sql = "  UPDATE ostotilauskirja
              SET oletettu_saapumispaiva = ?, rahti = ?
              WHERE id = ?";
    if ( $db->query($sql, array_values($_POST)) ) {
        $_SESSION["feedback"] = "<p class='success'>Muokaus onnistui.</p>";
    }
}
/** Ostotilauskirjan poistaminen */
else if( isset($_POST['poista']) ) {
    unset($_POST['poista']);
    if ( $db->query("DELETE FROM ostotilauskirja WHERE id = ?", array_values($_POST)) &&
		 $db->query("DELETE FROM ostotilauskirja_tuote WHERE ostotilauskirja_id = ?", array_values($_POST)) ) {
        $_SESSION["feedback"] = "<p class='success'>Ostotilauskirja poistettu.</p>";
    } else {
        $_SESSION["feedback"] = "<p class='error'>ERROR</p>";
    }
}

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
            <a class="nappi grey" href="yp_ostotilauskirja_hankintapaikka.php">Takaisin</a>
            <button class="nappi" type="button" onClick="avaa_modal_uusi_ostotilauskirja('<?=$hankintapaikka_id?>')">Uusi ostotilauskirja</button>
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
                                    '<?= date("Y-m-d", strtotime($otk->oletettu_saapumispaiva))?>',
                                    '<?= $otk->rahti?>', '<?= $otk->id?>')">
                                    Muokkaa</a>
                        <a class="nappi" href='javascript:void(0)'
                           onclick="poista_ostotilauskirja('<?= $otk->id?>')">Poista</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <form
    <?php else : ?>
        <p>Ei ostotilaukirjoja.</p>
    <?php endif; ?>







</main>




<script type="text/javascript">

    function avaa_modal_uusi_ostotilauskirja( ostokirjatilaus_id ) {
        let date = new Date().toISOString().slice(0,10);
        Modal.open({
            content: '\
            <h4>Anna uuden ostotilauskirjan tiedot.</h4>\
            <br><br>\
                <form action="" method="post" name="uusi_ostotilauskirja">\
					<label><span>Tunniste</span></label>\
					<input name="tunniste" type="text" placeholder="Ostotilauskirjan nimi" pattern=".{3,}" required>\
					<br><br>\
					<label><span>Saapumispäivä</span></label>\
					<input name="saapumispvm" type="date" value="'+date+'" title="Arvioitu saapumispäivä" min="'+date+'" required>\
					<br><br>\
					<label><span>Rahtimaksu (€)</span></label>\
					<input name="rahti" type="number" step="0.01" value="200.00" title="Rahtimaksu">\
					<br><br>\
					<input name="ostokirjatilaus_id" type="hidden" value="'+ostokirjatilaus_id+'">\
					<input class="nappi" type="submit" name="lisaa" value="Tallenna" id="lisaa_ostotilauskirja"> \
				</form>\
				',
            draggable: true
        });
    }

    function avaa_modal_muokkaa_ostotilauskirja(tunniste, saapumispvm, rahti, ostokirjatilaus_id){
        let date = new Date().toISOString().slice(0,10);
        Modal.open( {
            content:  '\
				<h4>Muokkaa ostitilauskirjan tietoja.</h4>\
				<hr>\
				<br>\
				<form action="" method="post" name="muokkaa_hankintapaikka">\
					<label><span>Tunniste</span></label>\
                    <h4 style="display: inline;">'+tunniste+'</h4>\
					<br><br>\
					<label><span>Saapumispäivä</span></label>\
					<input name="saapumispvm" type="date" value="'+saapumispvm+'" title="Arvioitu saapumispäivä" min="'+date+'" required>\
					<br><br>\
					<label><span>Rahtimaksu (€)</span></label>\
					<input name="rahti" type="number" step="0.01" value="'+rahti+'" title="Rahtimaksu">\
					<br><br>\
					<input name="ostokirjatilaus_id" type="hidden" value="'+ostokirjatilaus_id+'">\
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
            field.setAttribute("value", true);
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
    });


</script>
</body>
</html>
