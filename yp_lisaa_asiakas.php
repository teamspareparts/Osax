<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<title>Asiakkaat</title>
</head>
<body>
<?php include("header.php");?>
<h1 class="otsikko">Lisää asiakas</h1>
<br><br>
<div id="lomake">
	<form action="yp_lisaa_asiakas.php" name="uusi_asiakas" method="post" accept-charset="utf-8">
		<fieldset><legend>Uuden käyttäjän tiedot</legend>
			<br>
			<label><span>Sähköposti<span class="required">*</span></span></label>
			<input name="sposti" type="email" pattern=".{1,255}" required="required">
			<br><br>
			<label><span>Etunimi</span></label>
			<input name="etunimi" type="text" pattern="[a-zA-Z]{3,20}">
			<br><br>
			<label><span>Sukunimi</span></label>
			<input name="sukunimi" type="text" pattern="[a-zA-Z]{3,20}">
			<br><br>
			<label><span>Puhelin</span></label>
			<input name="puh" type="text" pattern=".{1,20}">
			<br><br>
			<label><span>Yrityksen nimi</span></label>
			<input name="yritysnimi" type="text" pattern=".{1,50}">
			<br><br>
			<label><span>Salasana<span class="required">*</span></span></label>
			<input name="password" type="password" pattern=".{6,}" title="Pituus min 6 merkkiä." required="required">
			<br><br>
			<label><span>Vahvista salasana<span class="required">*</span></span></label>
			<input name="confirm_password" type="password" pattern=".{6,}" title="Pituus min 6 merkkiä." required="required">
			<br><br><br>

			<div id="submit">
				<input name="submit" value="Lisää asiakas" type="submit">
			</div>
		</fieldset>

	</form><br><br>

	<?php	
	if (!is_admin()) {
		header("Location:tuotehaku.php");
		exit();
	}
	require 'tietokanta.php';

		if (isset($_POST['sposti'])){
			$result = db_lisaa_asiakas($_POST['etunimi'], $_POST['sukunimi'], $_POST['sposti'], $_POST['puh'],
										$_POST['yritysnimi'], $_POST['password'], $_POST['confirm_password']);
			if($result == -1){
				echo "Sähköposti varattu.";
			}
			elseif ($result == -2){
				echo "Salasanat eivät täsmää.";
			}
			elseif ($result == 2) {
				echo "Käyttäjä aktivoitu.";
			}
			else {
				echo "Lisäys onnistui.";
			}
		}

		//return:
		//-1	salasanat ei täsmää
		//-2	käyttäjätunnus on jo olemassa
		//1		lisäys onnistui
		//2		kayttaja aktivoitu uudelleen
		function db_lisaa_asiakas($asiakas_etunimi, $asiakas_sukunimi, $asiakas_sposti,
				$asiakas_puh, $asiakas_yritysnimi, $asiakas_salasana, $asiakas_varmista_salasana){

					$asiakas_hajautettu_salasana = password_hash($asiakas_salasana, PASSWORD_DEFAULT);

					//Tarkastetaan, että salsana ja vahvistussalasana ovat samat.
					if ($asiakas_salasana != $asiakas_varmista_salasana){
						return -2;	//salasanat ei täsmää
					}else {


						//Palvelimeen liittyminen
						$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die("Connection error:" . mysqli_connect_error());
						$tbl_name = 'kayttaja';

						//Tarkastetaan onko samannimistä käyttäjätunnusta
						$query = "SELECT * FROM $tbl_name WHERE sahkoposti='$asiakas_sposti'";
						$result = mysqli_query($connection, $query);
						$count = mysqli_num_rows($result);
						$row = mysqli_fetch_assoc($result);
						if($count != 0 && $row["aktiivinen"] == 1) {
							return -1; //talletetaan tulos sessioniin: käyttäjänimi varattu
						}
						elseif ($count != 0 && $row["aktiivinen"] == 0){
							$query = "UPDATE $tbl_name 
										SET aktiivinen=1, etunimi='$asiakas_etunimi', sukunimi='$asiakas_sukunimi', yritys='$asiakas_yritysnimi',
											puhelin='$asiakas_puh', salasana_hajautus='$asiakas_hajautettu_salasana'
										WHERE sahkoposti='$asiakas_sposti'";
							$result = mysqli_query($connection, $query);
							return 2;
						}
						else {
							//lisätään tietokantaan
							$query = "INSERT INTO $tbl_name (salasana_hajautus, etunimi, sukunimi, yritys, sahkoposti, puhelin)
							VALUES ('$asiakas_hajautettu_salasana', '$asiakas_etunimi', '$asiakas_sukunimi', '$asiakas_yritysnimi', '$asiakas_sposti', '$asiakas_puh')";
							$result = mysqli_query($connection, $query);
							return 1;	//talletetaan tulos sessioniin
						}
					}
		}
	?>
	</div>
</body>
</html>
