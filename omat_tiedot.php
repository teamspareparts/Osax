<!DOCTYPE html>
<html lang="fi">
<head>
	<link rel="stylesheet" href="css/styles.css">
	<meta charset="UTF-8">
	<title>Omat Tiedot</title>
</head>
<body>
<?php include("header.php");?>

<h1 class="otsikko">Omat Tiedot</h1>

<?php
	require 'tietokanta.php';
	//käydään hakemassa tietokannasta tiedot lomakkeen esitäyttöä varten
	$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die("Connection error:" . mysqli_connect_error());
	$tbl_name = 'kayttaja';

	$email = $_SESSION['email'];
	$query = "SELECT * FROM $tbl_name WHERE sahkoposti='$email'";
	$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
	$row = mysqli_fetch_assoc($result);
	$enimi = $row['etunimi'];
	$snimi = $row['sukunimi'];
	$puhelin = $row['puhelin'];
	$ynimi = $row['yritys'];
?>

<div id="lomake">
	<form action="omat_tiedot.php" name="uusi_asiakas" method="post" accept-charset="utf-8">
		<fieldset><legend>Nykyiset tiedot</legend>
			<br>
			<label><span>Sähköposti</span></label>
			<p style="display: inline; font-size: 16px;">
				<?= $_SESSION['email']; ?>
			</p>
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
		$result = db_paivita_tiedot($_POST['etunimi'], $_POST['sukunimi'], $_POST['puh'], $_POST['yritysnimi'],
									$_POST['new_password'], $_POST['confirm_new_password']);
		$_SESSION['result'] = $result;
		header("Location:omat_tiedot.php");
		exit;
	}

	//result:
	//-1	salasanat ei täsmää
	//-2	käyttäjätunnusta ei olemassa
	//1		lisäys onnistui
	function db_paivita_tiedot($asiakas_etunimi, $asiakas_sukunimi, $asiakas_puh, $asiakas_yritysnimi, $asiakas_uusi_salasana, $asiakas_varmista_uusi_salasana){
		$tbl_name="kayttaja";				// Taulun nimi
		$asiakas_sposti = $_SESSION['email'];
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

	function hae_kaikki_toimitusosoitteet_ja_tulosta($kayttaja_id) {
		global $connection;
		$sql_query = "
				SELECT	*
				FROM	toimitusosoite
				WHERE	kayttaja_id = '$kayttaja_id'";
		$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
		$i = 1;
		while ($row = $result->fetch_assoc()) {
			/*HTML-tulostus*/?>
			<p> Osoite <?= $i ?><br>
			<br>
			<label><span>Sähköposti</span></label> <?= $row['sahkoposti']?><br>
			<label><span>Puhelin</span></label><?= $row['puhelin']?><br>
			<label><span>Yritys</span></label><?= $row['yritys']?><br>
			<label><span>Katuosoite</span></label><?= $row['katuosoite']?><br>
			<label><span>Postinumero</span></label><?= $row['postinumero']?><br>
			<label><span>Postitoimipaikka</span></label><?= $row['postitoimipaikka']?><br><br>
			
			<form action="_toimitusosoite-test.php" name="testilomake" method="post">
				<input type=hidden name=muokkaa value="<?= $i ?>">
				<input type=submit value="Muokkaa">
			</form> 
			
			</p><hr>
			<?php $i++;
		}
	}?>
	<fieldset>
		<Legend>Osoitekirja</legend>
		<?= hae_kaikki_toimitusosoitteet_ja_tulosta($_SESSION['id']); ?>
		<input type=submit value="Lisää uusi osoite">
	</fieldset>
</div>

</body>
</html>
