<?php declare(strict_types=1);
require '_start.php'; global $db, $user, $cart;

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header( "Location:tuotehaku.php" );
	exit();
}

/**
 * Uuden yrityksen tietojen lisäys
 */
if ( !empty( $_POST[ 'nimi' ] ) ) {
	// Tarkistetaan, että halutulla tiedoilla ei ole jo aktivoitua yritystä.
	$sql = "SELECT id FROM yritys WHERE (y_tunnus=? OR nimi=?) AND aktiivinen=1 LIMIT 1";
	$row = $db->query( $sql, [ $_POST[ 'y_tunnus' ], $_POST[ 'nimi' ] ] );

	if ( !$row ) {
		$sql = "INSERT INTO yritys 
					( nimi, y_tunnus, sahkoposti, puhelin, katuosoite, postinumero, postitoimipaikka, maa )
				VALUES ( ?, ?, ?, ?, ?, ?, ?, ? )
				ON DUPLICATE KEY UPDATE 
					nimi=VALUES(nimi), y_tunnus=VALUES(y_tunnus), sahkoposti=VALUES(sahkoposti), 
					puhelin=VALUES(puhelin), katuosoite=VALUES(katuosoite), postinumero=VALUES(postinumero), 
					postitoimipaikka=VALUES(postitoimipaikka), maa=VALUES(maa), aktiivinen='1' ";

		if ( $db->query( $sql, array_values( $_POST ) ) ) {
			$db->query( "INSERT INTO ostoskori (yritys_id) SELECT id FROM yritys WHERE nimi = ? AND y_tunnus = ?",
						[ $_POST[ 'nimi' ], $_POST[ 'y_tunnus' ] ] );
			header( "Location:yp_yritykset.php?feedback=success" );
			exit;
		}
	}
	else {
		$_SESSION['feedback'] = "<p class='error'>Kyseisellä Y-tunnuksella tai nimellä on jo aktivoitu yritys.
			<br>ID: {$row->id}</p>";
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
    <meta charset="UTF-8">
	<title>Yritykset</title>
    <link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container lomake">
	<div class="otsikko_container">
		<section class="takaisin">
			<a href="yp_yritykset.php" class="nappi grey">
				<i class="material-icons">navigate_before</i>Takaisin</a><br><br>
		</section>
		<section class="otsikko">
			<h1>Uusi yritys</h1>
		</section>
		<section class="napit">
		</section>
	</div>
	<?= !empty($feedback) ? $feedback : '' ?>
	<form action="" name="uusi_asiakas" method="post" accept-charset="utf-8">
		<fieldset><legend>Uuden asiakasyrityksen tiedot</legend>
			<br>
			<label for="yritys" class="required"> Yritys </label>
			<input id="yritys" name="nimi" type="text" pattern=".{3,40}" placeholder="Yritys Oy" required>
			<br><br>
			<label for="ytunnus" class="required" > Y-tunnus </label>
			<input id="ytunnus" name="y_tunnus" type="text" pattern="(\d{7})[-][\d]" placeholder="1234567-8" required>
			<br><br>
			<label for="email"> Sähköposti </label>
			<input id="email" name="email" type="text" maxlength="200" placeholder="osoite@osoite">
			<br><br>
			<label for="puh"> Puhelin </label>
			<input id="puh" name="puh" type="tel" placeholder="050 123 4567"
				   pattern="((\+|00)?\d{3,5}|)((\s|-)?\d){3,10}">
			<br><br>
			<label for="addr"> Katuosoite </label>
			<input id="addr" name="osoite" type="text" maxlength="50" placeholder="Katuosoite 1">
			<br><br>
			<label for="pnum"> Postinumero </label>
			<input id="pnum" name="postinumero" type="text" placeholder="10100">
			<br><br>
			<label for="ptoimipaikka"> Postitoimipaikka </label>
			<input id="ptoimipaikka" name="postitoimipaikka" type="text" placeholder="HELSINKI">
			<br><br>
			<label for="maa"> Maa </label>
			<input id="maa" name="maa" type="text" placeholder="FI" >
			<br><br>
			<span class="small_note"> <span style="color:red;">*</span> = pakollinen kenttä</span>
			<br>
			<div class="center">
				<input class="nappi" value="Lisää yritys" type="submit">
			</div>
		</fieldset>
	</form><br><br>
</main>

<?php require 'footer.php'; ?>

</body>
</html>
