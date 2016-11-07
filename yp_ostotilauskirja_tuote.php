<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
require 'apufunktiot.php';
if ( !$user->isAdmin() ) {
    header("Location:etusivu.php"); exit();
}

//tarkastetaan onko GET muuttujat sallittuja ja haetaan hankintapaikan tiedot
$ostotilauskirja_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$otk = $db->query("SELECT * FROM ostotilauskirja WHERE id = ? LIMIT 1", [$ostotilauskirja_id])) {
    header("Location: yp_ostotilauskirja_hankintapaikka.php"); exit();
}


/******************************************************************************
 *
 *
 *                          KESKENERÄINEN SIVU!!
 *
 *
 ******************************************************************************/


else if ( isset($_POST['muokkaa']) ) {
    unset($_POST['muokkaa']);
    //TODO: Muokkaa kpl ostotilauskirja_tuotteelle ja ostohinta tuotteelle
    $sql = "  UPDATE ostotilauskirja
              SET oletettu_saapumispaiva = ?, rahti = ?
              WHERE ostotilauskirja_id = ?";
    if ( $db->query($sql, array_values($_POST)) ) {
        $_SESSION["feedback"] = "<p class='success'>Muokaus onnistui.</p>";
    } else {
        $_SESSION["feedback"] = "<p class='error'>ERROR: Muokkauksessa tapahtui virhe!</p>";
    }
}
else if( isset($_POST['poista']) ) {
    unset($_POST['poista']);
    if ( $db->query("DELETE FROM ostotilauskirja_tuote WHERE tuote_id = ?", [$_POST['id']]) ) {
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


$sql = "  SELECT *, SUM(tuote.sisaanostohinta * kpl) AS tuotteet_hinta FROM ostotilauskirja_tuote
          LEFT JOIN tuote
            ON ostotilauskirja_tuote.tuote_id = tuote.id 
          WHERE ostotilauskirja_id = ?
          GROUP BY ostotilauskirja_id";
$products = $db->query($sql, [$ostotilauskirja_id], FETCH_ALL);
if( $products ) get_basic_product_info($products);
$yht_hinta = !empty($products) ? ($products[0]->tuotteet_hinta + $otk->rahti) : $otk->rahti;

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
            <a class="nappi grey" href="yp_ostotilauskirja.php?id=<?=$otk->hankintapaikka_id?>">Takaisin</a>
        </div>
        <h3><?=$otk->tunniste?></h3>
    </section>

    <?= $feedback?>

        <table style="min-width: 90%;"><!-- Katalogissa saatavilla, tilattavissa olevat tuotteet (varastosaldo > 0) -->
            <thead>
            <tr><th>Tuotenumero</th>
                <th>Tuote</th>
                <th class="number">KPL</th>
                <th class="number">Ostohinta</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <!-- Rahtimaksu -->
            <tr><td></td><td>Rahtimaksu</td><td class="number">1</td><td class="number"><?=format_euros($otk->rahti)?></td><td></td></tr>
            <!-- Tuotteet -->
            <?php foreach ($products as $product) : ?>
                <tr>
                    <td><?=$product->tuotekoodi?></td>
                    <td><?=$product->brandName?><br><?=$product->articleName?></td>
                    <td class="number"><?=format_integer($product->kpl)?></td>
                    <td class="number"><?=format_euros($product->sisaanostohinta)?></td>
                    <td class="toiminnot">
                        <button class="nappi" onclick="avaa_modal_muokkaa_tuote(<?=$product->id?>,
                            '<?=$product->tuotekoodi?>', <?=$product->kpl?>, <?=$product->sisaanostohinta?>)">Muokkaa</button>
                        <button class="nappi" onclick="poista_ostotilauskirjalta('<?=$product->id?>')">Poista</button>
                    </td>
                </tr>
            <?php endforeach;?>
            <!-- Yhteensä -->
            <tr><td style="border-top: 1px solid black;">YHTEENSÄ</td><td style="border-top: 1px solid black"></td>
                <td class="number" style="border-top: 1px solid black">1</td>
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
					<input name="kpl" type="number" value="'+kpl+'" title="Tilattavat kappaleet" min="1" required />\
					<br><br>\
					<label><span>Ostohinta (€)</span></label>\
					<input name="ostohinta" type="number" step="0.01" value="'+ostohinta.toFixed(2)+'" title="Tuotteen ostohinta" required/>\
					<br><br>\
					<input name="id" type="hidden" value="'+tuote_id+'">\
					<input class="nappi" type="submit" name="muokkaa" value="Muokkaa" /> \
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
            field.setAttribute("name", "id");
            field.setAttribute("value", tuote_id);
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