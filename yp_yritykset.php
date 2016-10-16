<?php
require '_start.php'; global $db, $user, $cart;
if ( !$user->isAdmin() ) {
    header("Location:tuotehaku.php"); exit();
}

/** Yrityksen, ja sen asiakkaiden, deaktivointi */
if ( !empty($_POST['ids']) ) {
	foreach ($_POST['ids'] as $yritys_id) {
		$query = "UPDATE yritys SET aktiivinen = 0 WHERE id = ?";
		$db->query($query, [$yritys_id]);
		$query = "UPDATE kayttaja SET aktiivinen = 0 WHERE yritys_id = ?";
		$db->query($query, [$yritys_id]);
	}
	$_SESSION['feedback'] = "<p class='success'>Yritys (ja sen asiakkaat) deaktivoitu</p>";
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) ) { //Estetään formin uudelleenlähetyksen
    header("Location: " . $_SERVER['REQUEST_URI']); exit();
} else {
    $feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : "";
    unset($_SESSION["feedback"]);
}

$yritykset = $db->query( "SELECT * FROM yritys", NULL, FETCH_ALL ); //TODO: Voisi olla tehokkaampi
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
	<title>Yritykset</title>
    <link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
</head>
<body>
<?php include 'header.php'; ?>
<div id=asiakas>
    <h1 class="otsikko">Asiakasyritykset</h1>
    <div id="painikkeet">
        <a href="yp_lisaa_yritys.php"><span class="nappi">Lisää uusi Yritys</span></a>
    </div>
    <br><br><br>


    <div id="lista">

        <form action="yp_yritykset.php" method="post">
            <table class="asiakas_lista">
                <tr><th>Yritys</th><th>Y-tunnus</th><th>Osoite</th><th>Maa</th><th class="smaller_cell">Poista</th><th class=smaller_cell></th></tr>

                <?php
                //listataan kaikki tietokannasta löytyvät yritykset
                foreach ($yritykset as $y) :
                    if ($y->aktiivinen == 1) : ?>

                        <tr data-val="<?= $y->id ?>">
                            <td class="cell"><?= $y->nimi ?></td>
                            <td class="cell"><?= $y->y_tunnus ?></td>
                            <td class="cell"><?= $y->katuosoite . '<br>' . $y->postinumero . ' ' . $y->postitoimipaikka ?></td>
                            <td class="cell"><?= $y->maa ?></td>
                            <td class="smaller_cell">
                                <input type="checkbox" name="ids[]" value="<?= $y->id ?>" />
                            </td>
                            <td class="smaller_cell"><a href="yp_muokkaa_yritysta.php?id=<?= $y->id ?>"><span class="nappi">Muokkaa</span></a></td>
                        </tr>
                <?php endif; endforeach;?>
            </table>
                <br>
                <div id=submit>
                    <input type="submit" value="Poista valitut Yritykset">
                </div>
        </form>
    </div>

</div>

<script type="text/javascript">
    $(document).ready(function(){

        //painettaessa taulun riviä ohjataan asiakkaan tilaushistoriaan
        $('.cell').click(function(){
            $('tr').click(function(){
                var id = $(this).attr('data-val');
                window.document.location = 'yp_asiakkaat.php?yritys_id='+id;
            });
        })
        .css('cursor', 'pointer');
    });

</script>

</body>
</html>
