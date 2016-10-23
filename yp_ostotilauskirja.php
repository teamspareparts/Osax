<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
require 'apufunktiot.php';
if ( !$user->isAdmin() ) {
    header("Location:etusivu.php"); exit();
}

//tarkastetaan onko tultu toimittajat sivulta
$hankintapaikka_id = isset($_GET['id']) ? $_GET['id'] : null;
//TODO: Tarkasta onko id OK

$ostotilauskirjat = $db->query("SELECT * FROM ostotilauskirja WHERE hankintapaikka_id = ?", [$hankintapaikka_id], FETCH_ALL);

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
else if ( isset($_POST['muokkaa']) ) {
    unset($_POST['muokkaa']);
    $sql = "  UPDATE ostotilauskirja
              SET oletettu_saapumispaiva = ?, rahti = ?
              WHERE ostotilauskirja_id = ?";
    if ( $db->query($sql, array_values($_POST)) ) {
        $_SESSION["feedback"] = "<p class='success'>Muokaus onnistui.</p>";
    }
}
else if( isset($_POST['poista']) ) {
    unset($_POST['poista']);
    if ( $db->query("DELETE FROM ostotilauskirja WHERE ostotilauskirja_id = ?", array_values($_POST)) ) {
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


?>



<!DOCTYPE html>
<html lang="fi" xmlns="http://www.w3.org/1999/html">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="css/jsmodal-light.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
<script src="js/jsmodal-1.0d.min.js"></script>
<title>Ostotilauskirjat</title>
</head>
<body>
<?php require 'header.php'?>
<main class="main_body_container">
    <section>
        <h1 class="otsikko">Ostotilauskirja</h1>
        <div id="painikkeet">
            <input class="nappi" type="button" value="Uusi ostotilauskirja" onClick="avaa_modal_uusi_ostotilauskirja('<?=$hankintapaikka_id?>')">
        </div>
    </section>
    <?= $feedback?>

    <?php if ( $ostotilauskirjat ) : ?>
        <table>
            <thead>
            <tr><th>Tunniste</th>
                <th>Saapumispäivä</th>
                <th>Rahti</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ( $ostotilauskirjat as $otk ) : ?>
                <tr data-href="">
                    <td><?= $otk->tunniste?></td>
                    <td><?= date("d.m.Y", strtotime($otk->oletettu_saapumispaiva))?></td>
                    <td><?= format_euros($otk->rahti)?></td>
                    <td class="toiminnot">
                        <a class="nappi" href='javascript:void(0)'
                           onclick="avaa_modal_muokkaa_ostotilauskirja('<?=$otk->tunniste?>',
                                    '<?= date("Y-m-d", strtotime($otk->oletettu_saapumispaiva))?>',
                                    '<?= $otk->rahti?>', '<?= $otk->ostotilauskirja_id?>')">
                                    Muokkaa</a>
                        <a class="nappi" href='javascript:void(0)'
                           onclick="poista_ostotilauskirja('<?= $otk->ostotilauskirja_id?>')">Poista</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <form
    <?php else : ?>
        <p>Ei tehtyjä tilauksia.</p>
    <?php endif; ?>







</main>




<script type="text/javascript">

    function avaa_modal_uusi_ostotilauskirja( ostokirjatilaus_id ) {
        var date = new Date().toISOString().slice(0,10);
        Modal.open({
            content: '\
            <h4>Anna uuden ostotilauskirjan tiedot.</h4>\
            <br><br>\
                <form action="" method="post" name="uusi_ostotilauskirja">\
					<label><span>Tunniste</span></label>\
					<input name="tunniste" type="text" placeholder="Ostotilauskirjan nimi" pattern=".{3,}" required />\
					<br><br>\
					<label><span>Saapumispäivä</span></label>\
					<input name="saapumispvm" type="date" value="'+date+'" title="Arvioitu saapumispäivä" min="'+date+'" required />\
					<br><br>\
					<label><span>Rahtimaksu (€)</span></label>\
					<input name="rahti" type="number" step="0.01" value="200.00" title="Rahtimaksu" />\
					<br><br>\
					<input name="ostokirjatilaus_id" type="hidden" value="'+ostokirjatilaus_id+'">\
					<input class="nappi" type="submit" name="lisaa" value="Tallenna" id="lisaa_ostotilauskirja" /> \
				</form>\
				',
            draggable: true
        });
    }

    function avaa_modal_muokkaa_ostotilauskirja(tunniste, saapumispvm, rahti, ostokirjatilaus_id){
        var date = new Date().toISOString().slice(0,10);
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
					<input name="saapumispvm" type="date" value="'+saapumispvm+'" title="Arvioitu saapumispäivä" min="'+date+'" required />\
					<br><br>\
					<label><span>Rahtimaksu (€)</span></label>\
					<input name="rahti" type="number" step="0.01" value="'+rahti+'" title="Rahtimaksu" />\
					<br><br>\
					<input name="ostokirjatilaus_id" type="hidden" value="'+ostokirjatilaus_id+'">\
					<input class="nappi" type="submit" name="muokkaa" value="Muokkaa" /> \
				</form>\
				',
            draggable: true
        });
    }

    function poista_ostotilauskirja(ostotilauskirja_id){
        if( confirm("Haluatko varmasti poistaa kyseisen ostotilauskirjan?") ) {
            //Rakennetaan form
            var form = document.createElement("form");
            form.setAttribute("method", "POST");
            form.setAttribute("action", "");

            //asetetaan $_POST["poista"]
            var field = document.createElement("input");
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


</script>
</body>
</html>