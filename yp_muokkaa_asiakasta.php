<?php
require '_start.php'; global $db, $user, $cart;
if ( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
}

/** Asiakkaan muokkaus */
if ( !empty($_POST['muokkaa_asiakas']) ) {
	$sql = "UPDATE kayttaja SET etunimi = ?, sukunimi = ?, puhelin = ? WHERE id = ?";
	unset($_POST['muokkaa_asiakas']); //Turha indeksi, poistetaan.
	$db->query( $sql, array_values($_POST) );
	$_SESSION['feedback'] = "<p class='success'>Asiakkaan tiedot päivitetty.</p>";

/** Asiakkaan salasanan vaihto. Pakottaa asiakkaan vaihtamaan salansanan seuraavalla kirjautumisella. */
} elseif ( !empty($_POST['reset_password'])) {
	$sql = "UPDATE kayttaja SET salasana_uusittava = 1 WHERE id = ?";
	$db->query( $sql, array_values($_POST) );
	$_SESSION['feedback'] = "<p class='success'>Salasana nollattu.<br>Salasanan vaihtaminen 
				pakotettu seuraavalla kirjautumiskerralla</p>";
}

/** Asiakkaan muuttaminen pysyväksi */
elseif ( isset($_POST['demo_away']) ) {
	$sql = "UPDATE kayttaja SET demo = 0 WHERE id = ?";
	$db->query( $sql, array_values($_POST) );
	$_SESSION['feedback'] = "<p class='success'>Asiakkaan tili on nyt pysyvä.</p>";
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) ) { //Estetään formin uudelleenlähetyksen
	header("Location: " . $_SERVER['REQUEST_URI']); exit();
} else {
	$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : "";
	unset($_SESSION["feedback"]);
}

$asiakas = new User( $db, (!empty($_GET['id']) ? $_GET['id'] : NULL) );
if ( !$asiakas->isValid() || !$asiakas->aktiivinen) {
	header("Location:yp_asiakkaat.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title> Asiakkaan muokkaus </title>
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
</head>
<body>
<?php include("header.php"); ?>
<main class="main_body_container lomake">
	<?= $feedback ?>
	<a class="nappi grey" href="yp_asiakkaat.php?yritys_id=<?=$asiakas->yritys_id?>">Takaisin</a><br><br>
	<form action="#" name="asiakkaan_tiedot" method="post" accept-charset="utf-8">
		<fieldset><legend>Asiakkaan tiedot</legend>
			<br>
			<label for="email">Sähköposti</label>
			<span> <?=$asiakas->sahkoposti?> </span>

			<?php if ($asiakas->demo) : ?>
				<br><br>
				<label>Voimassa</label>
				<span><?=(new DateTime($asiakas->voimassaolopvm))->format("d.m.Y H:i:s") ?></span>
			<?php endif; ?>
			<br><br>
			<label for="enimi">Etunimi</label>
			<input id="enimi" name="etunimi" type="text" pattern="[a-öA-Ö]{3,20}"
				   value="<?=$asiakas->etunimi?>" title="Vain aakkosia.">
			<br><br>
			<label for="snimi">Sukunimi</label>
			<input id="snimi" name="sukunimi" type="text" pattern="[a-öA-Ö]{3,20}"
				   value="<?=$asiakas->sukunimi?>" title="Vain aakkosia">
			<br><br>
			<label for="puh">Puhelin</label>
			<input id="puh" name="puh" type="tel" value="<?=$asiakas->puhelin?>"
				   pattern="((\+|00)?\d{3,5}|)((\s|-)?\d){3,10}">
			<br><br>
			<br><input name="id" type="hidden" value="<?=$asiakas->id?>">
			<div class="center">
				<input class="nappi" name="muokkaa_asiakas" value="Päivitä tiedot" type="submit">
			</div>
		</fieldset>
	</form><br><br>

	<form action="#" name="resetoi_salasana" method="post">
		<fieldset class="center"><legend>Salasanan vaihto</legend>
			<label style="float:none;">Nollaa salasana:
				<input name="reset_password" value="<?= $asiakas->id ?>" type="hidden">
				<input class="nappi" value="Resetoi salasana" type="submit">
			</label>
		</fieldset>
	</form><br><br>

	<?php if ($asiakas->demo) : ?>
	<form action="#" name="muuta_demoaika" method="post">
		<fieldset class="center"><legend> Demoajan muuttaminen </legend>
			<label style="float:none;">Tee tilistä pysyvä:
				<input name="demo_away" value="<?= $asiakas->id ?>" type="hidden">
				<input class="nappi" value="Poista demorajoitus" type="submit">
			</label>
			<br><br>
            <label style="float:none;">Muuta demoaikaa:</label><br>
				<p style="font-weight:normal;">
				//TODO, if needed. <br>Se vaatisi käyttäjältä päivämäärän kysymistä,<br>
				and I have no interest in jumping into that rabbit hole.</p>
		</fieldset>
	</form><br><br>
	<?php endif;?>
</main>

<?php require 'footer.php'; ?>

</body>
</html>

