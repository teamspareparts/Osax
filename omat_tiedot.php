<!DOCTYPE html>
<html lang="fi">
<head>
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="css/jsmodal-light.css">
	<meta charset="UTF-8">
	<title>Omat Tiedot</title>
</head>
<body>
<?php include("header.php");?>

<?php
	require 'tietokanta.php';
	//käydään hakemassa tietokannasta tiedot lomakkeen esitäyttöä varten
	$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die("Connection error:" . mysqli_connect_error());
	$tbl_name = 'kayttaja';
	
	//Jos ylläpitäjä muuttaa asiakkaan tietoja
	if (is_admin() && isset($_GET['muokkaa_asiakasta'])){
		$id = $_GET['id'];
		$query = "SELECT * FROM $tbl_name WHERE id='$id'";
		echo '<h1 class="otsikko">Muokkaa asiakasta</h1>';
	}
	else {
		$email = $_SESSION['email'];
		$query = "SELECT * FROM $tbl_name WHERE sahkoposti='$email'";
		echo '<h1 class="otsikko">Omat Tiedot</h1>';
	}

	$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
	$row = mysqli_fetch_assoc($result);
	$email = $row['sahkoposti'];
	$enimi = $row['etunimi'];
	$snimi = $row['sukunimi'];
	$puhelin = $row['puhelin'];
	$ynimi = $row['yritys'];
	$demo = $row['demo'];
	$voimassaolopvm = $row['voimassaolopvm'];
?>

<div id="lomake">
	<form action="" name="uusi_asiakas" method="post" accept-charset="utf-8">
		<fieldset><legend>Nykyiset tiedot</legend>
			<br>
			<label><span>Sähköposti</span></label>
			<p style="display: inline; font-size: 16px;">
				<?= $email; ?>
			</p>
			<input type="hidden" name="email" value="<?= $email;?>" />
			
			<?php if ($demo){
				echo '
			<br><br><label><span>Voimassa</span></label>
			<p style="display: inline; font-size: 16px;">' . 
			(new DateTime($voimassaolopvm))->format("d.m.Y H:i:s")
			. '</p>';
			}?>
			<br><br>
			<label><span>Etunimi</span></label>
			<input name="etunimi" type="text" pattern="[a-öA-Ö]{3,20}" value="<?= $enimi; ?>" title="Vain aakkosia.">
			<br><br>
			<label><span>Sukunimi</span></label>
			<input name="sukunimi" type="text" pattern="[a-öA-Ö]{3,20}" value="<?= $snimi; ?>" title="Vain aakkosia">
			<br><br>
			<label><span>Puhelin</span></label>
			<input name="puh" type="text" pattern=".{1,20}" value="<?= $puhelin; ?>">
			<br><br>
			<label><span>Yrityksen nimi</span></label>
			<input name="yritysnimi" type="text" pattern=".{1,50}" value="<?= $ynimi; ?>">
			<br><br><br>
			<label><span>Uusi salasana</span></label>
			<input name="new_password" type="password" pattern=".{6,}" title="Pituus min 6 merkkiä.">
			<br><br>
			<label><span>Vahvista salasana</span></label>
			<input name="confirm_new_password" type="password" pattern=".{6,}" title="Pituus min 6 merkkiä.">
			<br><br><br>

			<div id="submit">
				<input name="submit" value="Päivitä tiedot" type="submit">
			</div>
		</fieldset>

	</form><br><br>

	<?php
	if (isset($_SESSION['result'])){
		if($_SESSION['result'] == -1){
			echo "Sähköpostia ei löytynyt.";
		}
		elseif ($_SESSION['result'] == -2){
			echo "Salasanat eivät täsmää.";
		}
		else {
			echo "Tiedot päivitetty.";
		}
		unset($_SESSION['result']);
	}

	if (isset($_POST['etunimi'])) {
		$result = db_paivita_tiedot($_POST['email'], $_POST['etunimi'], $_POST['sukunimi'], $_POST['puh'], $_POST['yritysnimi'],
									$_POST['new_password'], $_POST['confirm_new_password']);
		$_SESSION['result'] = $result;
		header("Location: http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
		exit;
	}

	//result:
	//-1	salasanat ei täsmää
	//-2	käyttäjätunnusta ei olemassa
	//1		lisäys onnistui
	function db_paivita_tiedot($email, $asiakas_etunimi, $asiakas_sukunimi, $asiakas_puh, $asiakas_yritysnimi, $asiakas_uusi_salasana, $asiakas_varmista_uusi_salasana){
		$tbl_name="kayttaja";				// Taulun nimi
		$asiakas_sposti = $email;
		$hajautettu_uusi_salasana = password_hash($asiakas_uusi_salasana, PASSWORD_DEFAULT);

		//Palvelimeen liittyminen
		$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die("Connection error:" . mysqli_connect_error());

		//Tarkastetaan löytyykö käyttäjätunnusta
		$query = "SELECT * FROM $tbl_name WHERE sahkoposti='$asiakas_sposti'";
		$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
		$count = mysqli_num_rows($result);
		if($count != 1){
			return -1; //käyttäjänimeä ei löytynyt
		} else {
			if ($asiakas_uusi_salasana != $asiakas_varmista_uusi_salasana){
				return -2;	//salasanat ei täsmää
			}else {
				//päivitetään tietokantaan
				$query = "UPDATE $tbl_name SET etunimi='$asiakas_etunimi', sukunimi='$asiakas_sukunimi', puhelin='$asiakas_puh', yritys='$asiakas_yritysnimi'
				WHERE sahkoposti='$asiakas_sposti'";
				mysqli_query($connection, $query) or die(mysqli_error($connection));


				//päivitetään myös salasana, jos muutettu
				if ($asiakas_uusi_salasana != "" && $asiakas_varmista_uusi_salasana != ""){
					$query = "UPDATE $tbl_name SET salasana_hajautus='$hajautettu_uusi_salasana'
					WHERE sahkoposti='$asiakas_sposti'";
					mysqli_query($connection, $query) or die(mysqli_error($connection));
				}

				return 1;	//talletetaan tulos sessioniin
			}
		}
	}
	
	include 'omat_tiedot_osoitekirja.php'; //Sisältää kaiken toiminnallisuuden osoitekirjaa varten
?>
</div>

</body>
</html>
