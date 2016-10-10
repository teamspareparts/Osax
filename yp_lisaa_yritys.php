<?php
require '_start.php'; global $db, $user, $cart, $yritys;

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
    header("Location:tuotehaku.php"); exit();
}

/** Lisätään yrityksen tiedot tietokantaan */
if (isset($_POST['nimi'])) {
	// Tarkistetaan, että halutulla tiedoilla ei ole jo aktivoitua yritystä.
	$sql = "SELECT id FROM yritys WHERE (y_tunnus=? OR nimi=?) AND aktiivinen=1 LIMIT 1";
	$row = $db->query( $sql, [$_POST['y_tunnus'],$_POST['nimi']] );

	if ( !$row ) {
		unset($_POST['submit']); //Poistetaan turha array-index. Rikkoo SQL-haun.
		$sql = "INSERT INTO yritys 
					( nimi, y_tunnus, sahkoposti, puhelin, katuosoite, postinumero, postitoimipaikka, maa )
				VALUES ( ?, ?, ?, ?, ?, ?, ?, ? )
				ON DUPLICATE KEY UPDATE 
					nimi=VALUES(nimi), y_tunnus=VALUES(y_tunnus), sahkoposti=VALUES(sahkoposti), 
					puhelin=VALUES(puhelin), katuosoite=VALUES(katuosoite), postinumero=VALUES(postinumero), 
					postitoimipaikka=VALUES(postitoimipaikka), maa=VALUES(maa), aktiivinen='1' ";

		if ( $db->query($sql, $_POST) ) {
			$db->query( "INSERT INTO ostoskori (yritys_id) SELECT id FROM yritys WHERE nimi = ?", [$yritys_nimi]);
			header("Location:yp_yritykset.php?feedback=success"); exit;
		}
	} else {
		$feedback = "<p class='error'>Kyseisellä Y-tunnuksella tai nimellä on jo aktivoitu yritys. ID: {$row->id}</p>";
	}
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
	<?= !empty($feedback) ? $feedback : '' ?>
	<a class="nappi" href="yp_yritykset.php" style="color:#000; background-color:#c5c5c5; border-color:#000;">
		Takaisin</a><br><br>
	<form action="" name="uusi_asiakas" method="post" accept-charset="utf-8">
		<fieldset><legend>Uuden asiakasyrityksen tiedot</legend>
			<br>
			<label for="yritys" class="required"> Yritys </label>
			<input id="yritys" name="nimi" type="text" pattern=".{3,40}" placeholder="Yritys Oy" required>
			<br><br>
			<label for="ytunnus" class="required" > Y-tunnus </label>
			<input id="ytunnus" name="y_tunnus" type="text" pattern=".{9}" placeholder="1234567-8" required>
			<br><br>
			<label for="email"> Sähköposti </label>
			<input id="email" name="email" type="text" pattern=".{3,250}" placeholder="osoite@osoite">
			<br><br>
			<label for="puh"> Puhelin </label>
			<input id="puh" name="puh" type="tel" placeholder="050 123 4567"
				   pattern="((\+|00)?\d{3,5}|)((\s|-)?\d){3,10}">
			<br><br>
			<label for="addr"> Katuosoite </label>
			<input id="addr" name="osoite" type="text" pattern=".{1,50}" placeholder="Katuosoite 1">
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
				<input class="nappi" name="submit" value="Lisää yritys" type="submit">
			</div>
		</fieldset>
	</form><br><br>
</main>
</body>
</html>
