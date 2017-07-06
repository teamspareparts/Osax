<?php
require '_start.php'; global $db, $user, $cart;

$yritys = new Yritys( $db, (!empty($_GET['id']) ? (int)$_GET['id'] : null));
if ( !$user->isAdmin() || !$yritys->isValid() ) {
    header("Location:etusivu.php"); exit();
}

/** Yrityksen tietojen muokkaus */
if ( !empty($_POST['muokkaa_tietoja'])) {
	$sql = "UPDATE yritys 
			SET sahkoposti = ?, puhelin = ?, katuosoite = ?, postinumero = ?, postitoimipaikka = ?, maa = ?
			WHERE id = ? LIMIT 1";
	$result = $db->query( $sql, array_values($_POST) );
	if ($result) {
		$_SESSION["feedback"] = "<p class='success'>Tietojen päivittäminen onnistui.</p>";
	} else {
		$_SESSION['feedback'] = "<p class='error'>Tietojen päivittäminen epäonnistui</p>";
	}
}

/** Yrityksen rahtimaksun muokkaus */
elseif ( !empty($_POST['rahtimaksu'])) {
	$sql = "UPDATE yritys SET rahtimaksu = ?, ilmainen_toimitus_summa_raja = ? WHERE id = ? LIMIT 1";
	$result = $db->query( $sql, array_values($_POST) );
	if ($result) {
		$_SESSION['feedback'] = "<p class='success'>Rahtimaksu ja ilmaisen toimituksen raja päivitetty.</p>";
	} else {
		$_SESSION['feedback'] = "<p class='error'>Rahtimaksun parametrien muuttaminen epäonnistui</p>";
	}
}

/** Yrityksen alennuksen lisääminen/muokkaaminen */
elseif ( !empty($_POST['muokkaa_alennus']) ) {
	$_POST['yleinen_alennus'] = (int)$_POST['yleinen_alennus'] / 100; // 10 % --> 0.10;
	$sql = "UPDATE yritys SET alennus_prosentti = ? WHERE id = ? LIMIT 1";
	$result = $db->query( $sql, array_values($_POST) );
	if ($result) {
		$_SESSION['feedback'] = "<p class='success'>Yleinen alennus ". $_POST['yleinen_alennus']*100 ." % asetettu </p>";
	} else {
		$_SESSION['feedback'] = "<p class='error'>Alennuksen muuttaminen epäonnistui</p>";
	}
}

/** Yrityksen maksutavan */
elseif ( isset($_POST['maksutapa']) ) {
	$sql = "UPDATE yritys SET maksutapa = ? WHERE id = ? LIMIT 1";
	$result = $db->query( $sql, array_values($_POST) );
	if ($result) {
		$_SESSION['feedback'] = "<p class='success'>Yrityksen maksutapaa päivitetty</p>";
	} else {
		$_SESSION['feedback'] = "<p class='error'>Maksutavan muuttaminen epäonnistui</p>";
	}
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
	<title>Yritykset</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="css/styles.css">
	<style>
		label.special_snowflake {
			width: 120px;
			float: left;
			font-weight: bold;
			white-space: nowrap;
			padding: 3pt;
		}
	</style>
</head>
<body>
<?php require 'header.php'?>
<main class="main_body_container lomake">
    <?= $feedback ?>
    <a class="nappi grey" href="yp_yritykset.php">Takaisin</a><br><br>
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
                   pattern="((\+|00)?\d{3,5}|)((\s|-)?\d){10}">
            <br><br>
            <label for="addr"> Katuosoite </label>
            <input id="addr" name="osoite" type="text" maxlength="50" value="<?= $yritys->katuosoite?>">
            <br><br>
            <label for="postnum"> Postinumero </label>
            <input id="postnum" name="postinumero" type="text" value="<?= $yritys->postinumero?>">
            <br><br>
            <label for="postplace"> Postitoimipaikka</label>
            <input id="postplace" name="postitoimipaikka" type="text" value="<?= $yritys->postitoimipaikka?>">
            <br><br>
            <label for="maa"> Maa </label>
            <input id="maa" name="maa" type="text" value="<?= $yritys->maa?>">
            <br><br>
            <div class="center">
	            <input type="hidden" name="muokkaa_tietoja" value="<?= $yritys->id?>">
                <input class="nappi" value="Muokkaa yritystä" type="submit">
            </div>
        </fieldset>
    </form>
    <br><br>
	<form name="muuta_tuotaalennus" method="post">
		<fieldset class="center"><legend>Yrityksen alennus</legend>
			<span>Yleinen alennus, koskee kaikkia tuotteita.</span>
			<br><br>
			<label> Yleinen alennus: </label>
			<input type="number" name="yleinen_alennus" min="0" max="100"
			       value="<?= $yritys->yleinen_alennus * 100 ?>" title="Anna alennus kokonaislukuna"> %
			<br><br>
			<div class="center">
				<input type="hidden" name="muokkaa_alennus" value="<?= $yritys->id ?>">
				<input type="submit" value="Muokkaa alennusta" class="nappi">
			</div>
		</fieldset>
	</form>
	<br><br>
    <form name="muuta_rahtimaksu" method="post">
        <fieldset><legend>Yrityksen rahtimaksu</legend>
            <span>Kumpikin arvo euroina (€). <br> Jos nolla, ilmainen toimitus aina.</span>
            <br><br>
            <label> Rahtimaksu: </label>
            <input name="rahtimaksu" type="number" step="0.01" min="0" max="100000" value="<?= $yritys->rahtimaksu ?>"
				   title="Anna käyttäjäkohtainen rahtimaksu euroina (€)."> €
            <br>
            <label> Ilmaisen toimituksen raja: </label>
            <input name="ilmainen_toimitus" type="number" step="0.01" min="0" max="100000"
				   value="<?= $yritys->ilm_toim_sum_raja ?>" title="Ilmaisen toimituksen raja euroina (€)."> €
            <br>
            <div class="center">
				<input name="id" value="<?=$yritys->id?>" type="hidden">
                <input value="Muokkaa rahtimaksua" type="submit" class="nappi">
            </div>
        </fieldset>
    </form>
	<br><br>
	<form name="muuta_maksutapa" method="post">
		<fieldset><legend>Maksutavan valinta</legend>
			<span>Käyttäjä voi silti valita maksaa Paytraililla.<br>
			<br>
			<label for="mt" class="special_snowflake"> Maksutapa: </label>
			<select name="maksutapa" id="mt">
				<option selected disabled>Valitse maksutapa (nykyinen arvo: <?= $yritys->maksutapa ?>)</option>
				<option value="0">0: Vain Paytrail</option>
				<option value="1">1: Paytrail + Lasku</option>
			</select>
			<br><br>
			<div class="center">
				<input name="id" value="<?=$yritys->id?>" type="hidden">
				<input type="submit" value="Muokkaa maksutapaa" class="nappi">
			</div>
		</fieldset>
	</form>
</main>

<?php require 'footer.php'; ?>

</body>
</html>
