<?php
require '_start.php'; global $db, $user, $cart, $yritys;

$yritys = new Yritys( $db, (!empty($_GET['id']) ? (int)$_GET['id'] : null));
if ( !$user->isAdmin() || !$yritys->isValid() ) {
    header("Location:etusivu.php"); exit();
}

/** Yrityksen tietojen muokkaus */
if (isset($_POST['muokkaa_yritysta'])) {
	unset($_POST['muokkaa_yritysta']);
	$sql = "UPDATE yritys 
			SET sahkoposti = ?, puhelin = ?, katuosoite = ?, postinumero = ?, postitoimipaikka = ?, maa = ?
			WHERE nimi = ? AND y_tunnus = ?";
	$db->query( $query, array_values( $_POST ) );
    $_SESSION["feedback"] = "<p class='success'>Tietojen päivittäminen onnistui.</p>";
}

/** Yrityksen rahtimaksun muokkaus */
elseif (isset($_POST['muokkaa_rahtimaksu'])) {
	unset( $_POST['muokkaa_rahtimaksu'] );
	$sql = "UPDATE yritys SET rahtimaksu = ?, ilmainen_toimitus_summa_raja = ? WHERE id = ?";
	$db->query( $sql, array_values($_POST) );
    $_SESSION['feedback'] = "<p class='success'>Rahtimaksu ja ilmaisen toimituksen raja päivitetty.</p>";
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) ) { //Estetään formin uudelleenlähetyksen
	header("Location: " . $_SERVER['REQUEST_URI']); exit();
} else {
	$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : "";
	unset($_SESSION["feedback"]);
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
    <title>Yritykset</title>
</head>
<body>
<?php require 'header.php'?>
<main class="main_body_container lomake">
    <?= $feedback ?>
    <a class="nappi" href="yp_yritykset.php" style="color:#000; background-color:#c5c5c5; border-color:#000;">
        Takaisin</a><br><br>
    <form name="muokkaa_yritysta" method="post" accept-charset="utf-8">
        <fieldset><legend>Muokkaa yrityksen tietoja</legend>
            <label> Yritys </label><p style="display:inline; font-size:16px; font-weight:bold;"><?= $yritys->nimi?></p>
            <br><br>
            <label> Y-tunnus </label><p style="display:inline; font-size:16px; font-weight:bold;">
				<?= $yritys->y_tunnus?></p>
            <br><br>
            <label for="email"> Sähköposti </label>
            <input id="email" name="email" type="email" value="<?= $yritys->sahkoposti?>">
            <br><br>
            <label for="puh"> Puhelin </label>
            <input id="puh" name="puh" type="tel" value="<?= $yritys->puhelin?>"
                   pattern="((\+|00)?\d{3,5}|)((\s|-)?\d){3,10}">
            <br><br>
            <label for="addr"> Katuosoite </label>
            <input id="addr" name="osoite" type="text" pattern=".{1,50}" value="<?= $yritys->katuosoite?>">
            <br><br>
            <label for="postnum"> Postinumero </label>
            <input id="postnum" name="postinumero" type="text" value="<?= $yritys->postinumero?>">
            <br><br>
            <label for="postplace"> Postitoimipaikka</label>
            <input id="postplace" name="postitoimipaikka" type="text" value="<?= $yritys->postitoimipaikka?>">
            <br><br>
            <label for="maa"> Maa </label>
            <input id="maa" name="maa" type="text" value="<?= $yritys->maa?>">
            <input name="nimi" type="hidden" value="<?= $yritys->nimi?>">
            <input name="y_tunnus" type="hidden" value="<?= $yritys->y_tunnus?>">
            <br><br>
            <div class="center">
                <input class="nappi" name="muokkaa_yritysta" value="Muokkaa yritystä" type="submit">
            </div>
        </fieldset>
    </form>
    <br><br>
    <form method="post">
        <fieldset><legend>Yrityksen rahtimaksu</legend>
            <span>Kumpikin arvo euroina (€). <br> Nollan kohdalla ilmainen toimitus aina.</span>
            <br><br>
            <label> Rahtimaksu: </label>
            <input name="rahtimaksu" type="number" step="0.01" min="0" max="100000" value="<?= $yritys->rahtimaksu ?>"
				   title="Anna käyttäjäkohtainen rahtimaksu euroina (€).">
            <br>
            <label> Ilmaisen toimitus: </label>
            <input name="ilmainen_toimitus" type="number" step="0.01" min="0" max="100000"
				   value="<?= $yritys->ilm_toim_sum_raja ?>" title="Ilmaisen toimituksen raja euroina (€).">
            <br>
            <div class="center">
				<input name="id" value="<?=$yritys->id?>" type="hidden">
                <input name="muokkaa_rahtimaksu" value="Muokkaa rahtimaksua" type="submit" class="nappi">
            </div>
        </fieldset>
    </form>
</main>
</body>
</html>
