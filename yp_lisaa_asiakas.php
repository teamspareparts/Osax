<?php
require '_start.php'; global $db, $user, $cart, $yritys;

if ( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
} elseif ( !empty($_GET['yritys_id']) ) {
	$yritys_id = 0;
}

if (isset($_POST['spoksti'])){
	$demo = !empty($_POST['demo_user']) ? '1' : '0';			// Onko demokäyttäjä?
	$paivat = !empty($_POST['paivat']) ? $_POST['paivat'] : '0';// Demokäyttäjän käyttöaika

	$result = db_lisaa_asiakas($_POST, $demo, $paivat);
	if($result == -1){
		echo "Sähköposti varattu.";
	}
	elseif ($result == -2){
		echo "Salasanat eivät täsmää.";
	}
	else{
		header("Location:yp_asiakkaat.php?yritys_id=".$_GET['yritys_id']);
	}
}

//return:
//-2	salasanat ei täsmää
//-1	käyttäjätunnus on jo olemassa
//1		lisäys onnistui
//2		kayttaja aktivoitu uudelleen
function db_lisaa_aksiakas($variables, $demo, $paivat){
	$asiakas_hajautettu_salasana = password_hash($asiakas_salasana, PASSWORD_DEFAULT);


	//Tarkastetaan, että salasana ja vahvistussalasana ovat samat.
	if ($asiakas_salasana != $asiakas_varmista_salasana){
		return -2;	//salasanat ei täsmää
	}else {
		//Palvelimeen liittyminen
		$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die("Connection error:" . mysqli_connect_error());
		$tbl_name = 'kayttaja';
		//Tarkastetaan onko samannimistä käyttäjätunnusta
		$query = "SELECT * FROM $tbl_name WHERE sahkoposti='$asiakas_sposti';";
		$result = mysqli_query($connection, $query);
		$count = mysqli_num_rows($result);
		$row = mysqli_fetch_assoc($result);



		if($count != 0 && $row["aktiivinen"] == 1) {
			return -1; //käyttäjänimi varattu
		}
		elseif ($count != 0 && $row["aktiivinen"] == 0){
			$query = "UPDATE $tbl_name 
				  					SET yritys_id=$yritys_id, aktiivinen=1, etunimi='$asiakas_etunimi', sukunimi='$asiakas_sukunimi',puhelin='$asiakas_puh',
			  						salasana_hajautus='$asiakas_hajautettu_salasana', salasana_vaihdettu=NOW(), demo='$demo', voimassaolopvm=NOW()+INTERVAL '$paivat' DAY, salasana_uusittava=1
		  							WHERE sahkoposti='$asiakas_sposti'";
			$result = mysqli_query($connection, $query) or die("Error:" . mysqli_error($connection));
			return 2;	//kayttaja aktivoitu
		}
		else {
			//lisätään tietokantaan
			$query = "	INSERT INTO $tbl_name (yritys_id, salasana_hajautus, salasana_vaihdettu, etunimi, sukunimi, sahkoposti, puhelin, demo, voimassaolopvm, salasana_uusittava)
		      					VALUES ('$yritys_id', '$asiakas_hajautettu_salasana', NOW(), '$asiakas_etunimi', '$asiakas_sukunimi', '$asiakas_sposti', '$asiakas_puh', '$demo', NOW()+INTERVAL '$paivat' DAY, 1)";
			$result = mysqli_query($connection, $query) or die("Error:" . mysqli_error($connection));;
			return 1;	//kaikki ok
		}
	}
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<title>Lisää asiakas</title>
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container lomake">
	<form action="" name="uusi_asiakas" method="post" accept-charset="utf-8">
		<fieldset><legend>Uuden käyttäjän tiedot</legend>
			<br>
			<label class="required" for="sposti"> Sähköposti </label>
			<input id="sposti" name="sposti" type="email" pattern=".{1,255}" required>
			<br><br>
			<label for="enimi"> Etunimi </label>
			<input id="enimi" name="etunimi" type="text" pattern="[a-zA-Z]{3,20}">
			<br><br>
			<label for="snimi"> Sukunimi </label>
			<input id="snimi" name="sukunimi" type="text" pattern="[a-zA-Z]{3,20}">
			<br><br>
			<label for="puh"> Puhelin </label>
			<input id="puh" name="puh" type="text" pattern=".{1,20}">
			<br><br>
			<label for="ynimi"> Yrityksen nimi </label>
			<input id="ynimi" name="yritysnimi" type="text" pattern=".{1,50}">
			<br><br>
			<label for="ss" class="required"> Salasana </label>
			<input id="ss" name="password" type="password" pattern=".{6,}"
				   title="Pituus min 6 merkkiä." required>
			<br><br>
			<label for="vahv_ss" class="required"> Vahvista salasana </label>
			<input id="vahv_ss" name="confirm_password" type="password" pattern=".{6,}"
				   title="Pituus min 6 merkkiä." required><br>
			<span id="check"></span>
			<br><br><br>
			<label for="demo"> Testiasiakas </label>
			<input name="demo_user" type="checkbox" title="Asiakas aktiivinen vain määräajan." id="demo">
			
			<span id="inner_label" class="">Päivät:</span>
			<input name="paivat" type="number" value="7" class="" min="1" maxlength="4" id="paivat"
				   title="Kuinka monta päivää aktiivinen">

			<input name="yritys_id" type="hidden" value="<?=$_GET['yritys_id']?>" />
			<br><br>
			<span class="small_note"> <span style="color:red;">*</span> = pakollinen kenttä</span>
			<br>

			<div class="center">
				<input class="nappi" name="submit" value="Lisää asiakas" type="submit" id="asiakas_submit">
			</div>
		</fieldset>
	</form><br><br>
</main>
	
<script type="text/javascript">
	$(document).ready(function(){
		/** Muuttujien alustusta */
		var pwSubmit = $('#asiakas_submit'); // Salasanan pituuden ja vahvistuksen tarkistusta varten
		var newPassword = $('#ss'); // Ditto
		var pwCheck = $('#check'); // Ditto

		/** Demo-valinnan alustusta */
		$("#paivat").addClass('disabled');		// Otetaan pvm-input pois käytöstä aluksi
		$("#inner_label").addClass('disabled');	//Ditto

		/** Testiasiakas-valinta
			Onko päivät-valinta disabled? */
		$("#demo").change(function(){
			if ( this.checked ) {
				$("#paivat").removeClass('disabled');
				$("#inner_label").removeClass('disabled');
			} else {
				$("#paivat").addClass('disabled');
				$("#inner_label").addClass('disabled');
			}
		});



		/** Salasanojen tarkastus reaaliajassa */
		$('#ss, #vahv_ss').on('keyup', function () {
			pwSubmit.prop('disabled', true).addClass('disabled');
			if ( newPassword.val().length >= 8 ) {
				if ( newPassword.val() === $('#vahv_ss').val() ) {
					pwCheck.html('<i class="material-icons">done</i>Salasana OK.').css('color', 'green');
					pwSubmit.prop('disabled', false);
				} else {
					pwCheck.html('<i class="material-icons">warning</i>Salasanat eivät täsmää').css('color', 'red');
				}
			} else {
				pwCheck.html('<i class="material-icons">warning</i>Salasanan min. pituus on 8 merkkiä.')
					.css('color', 'red');
			}
		});
	});
</script>
</body>
</html>
