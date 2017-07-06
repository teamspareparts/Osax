<?php
require '_start.php'; global $db, $user, $cart;

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php"); exit();
}

if ( isset($_POST['lisaa_uusi']) ) {
	$_POST['summary'] = trim( $_POST['summary'] );
	$_POST['details'] = trim( $_POST['details'] );
	$db->query( "INSERT INTO etusivu_uutinen (otsikko, tyyppi, summary, details, loppu_pvm) VALUES (?,?,?,?,?)",
		[$_POST['headline'], $_POST['type'], $_POST['summary'], $_POST['details'], $_POST['loppu']] );
	header("location:etusivu.php");
	exit;
}
elseif ( isset($_POST['muokkaa']) ) {
	$_POST['summary'] = trim( $_POST['summary'] );
	$_POST['details'] = trim( $_POST['details'] );
	$db->query( "UPDATE etusivu_uutinen SET otsikko = ?, summary= ?, details = ?, loppu_pvm = ? WHERE id = ?",
				[$_POST['headline'], $_POST['summary'], $_POST['details'], $_POST['loppu'], $_POST['id']] );
	header("location:etusivu.php");
	exit;
}
elseif ( isset($_POST['poista']) ) {
	$db->query( "UPDATE etusivu_uutinen SET aktiivinen = 0 WHERE id = ?", [ $_POST['id'] ] );
	header("location:etusivu.php");
	exit;
}

if ( !empty($_GET['id']) ) {
	$sql = "SELECT id, tyyppi, otsikko, summary, details, pvm, loppu_pvm
			FROM etusivu_uutinen WHERE id = ? LIMIT 1";
	$row = $db->query( $sql, [ $_GET['id'] ] );
}

// Loppu pvm:n valmistelu, niin ei tarvitse sekoittaa HTML:ää.
$today = date('Y-m-d');
$future = date('Y-m-d',strtotime('+6 months'));
$current = (!empty($row)) ? date( 'Y-m-d', strtotime( $row->loppu_pvm ) )
	: date('Y-m-d',strtotime('+14 days')) ;

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) || !empty($_FILES) ) { //Estetään formin uudelleenlähetyksen
	header("Location: " . $_SERVER['REQUEST_URI']);
	exit();
} else {
	$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : "";
	unset($_SESSION["feedback"]);
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Lisää uutinen</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="css/styles.css">
	<script src="./js/nodep-date-input-polyfill.dist.js" async></script>
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container">

	<?= $feedback ?>
	<p><a href="etusivu.php" class="nappi grey">Palaa takaisin</a></p>

	<fieldset>
		<legend><?= !empty($row->id) ? "Muokkaa uutista" : "Lisää uusi uutinen/mainos etusivulle" ?></legend>
		<form class="flex_column fp_content_form" method="post">
			<label> Otsikko </label>
			<input type="text" name="headline" id="otsikko" title="Tekstin otsikko" placeholder="TEKSTIN OTSIKKO"
				   maxlength="50" value="<?= !empty($row) ? $row->otsikko : '' ?>" required>

			<label> Tekstin sijainti. Jos jokin kolumni on tyhjä, sitä ei näytetä ollenkaan.
				(Muokkauksessa ei voi vaihtaa sijaintia tällä hetkellä.) </label>
			<select title="Tekstin sisällön sijainti" name="type" required <?= !empty($row) ? 'disabled' : '' ?>>
				<option disabled selected>--- Valitse tekstin sijainti ---</option>
				<option value="0">Vasen kolumni</option>
				<option value="1">Keskimmäinen kolumni</option>
				<option value="2">Oikea kolumni</option>
			</select>

			<label> Summary. Näytetään käyttäjälle aina. <span style="font-weight: normal;">Hyväksyy HTML-koodia. Max. pituus 500 merkkiä.<span></label>
			<textarea name="summary" maxlength="500" placeholder="SUMMARY. Tekstin tiivistelmä."
			          rows="5" title="Tekstin sisältö. Hyväksyy HTML:ää."
			          required><?= !empty($row) ? $row->summary : '' ?></textarea>

			<label> Details. Piilotettu summary-osion alle; näkyviin painamalla nuolta.
				Jätä tyhjäksi, jos et halua nuolta. <span style="font-weight: normal;">Hyväksyy HTML-koodia. Max. pituus 500 merkkiä.<span></label>
			<textarea name="details" maxlength="10000" placeholder="DETAILS. Tarkempaa lisätietoa." rows="10"
					  title="Tekstin sisältö. Hyväksyy HTML:ää."
			><?= !empty($row) ? $row->details : '' ?></textarea>

			<label> Loppu pvm. Milloin uutinen poistuu etusivulta. </label>
			<input type="date" name="loppu" title="Valitse pvm, jolloin uutinen loppuu (häviää etusivulta)."
			       min="<?=$today?>" max="<?=$future?>" value="<?=$current?>" required>

			<?php if (empty($row)) : ?>
				<input type="submit" name="lisaa_uusi" value="Lisää uusi teksti etusivulle" class="nappi">
			<?php else : ?>
				<input type="hidden" name="id" value="<?=$row->id?>">
				<input type="submit" name="muokkaa" value="Tallenna muokkaukset" class="nappi"><br>
				<input type="submit" name="poista" value="Poista uutinen" class="nappi red">
			<?php endif; ?>

		</form>
	</fieldset>

</main>

<?php require 'footer.php'; ?>

</body>
</html>
