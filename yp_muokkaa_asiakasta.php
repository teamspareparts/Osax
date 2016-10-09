<!DOCTYPE html>
<html lang="fi">
<head>
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="css/jsmodal-light.css">
	<meta charset="UTF-8">
	<title></title>
</head>
<body>
<?php include("header.php");?>

<?php
	require 'tietokanta.php';
	if (!is_admin() || !isset($_GET['id'])) {
		header("Location:etusivu.php");
		exit();
	}
	
	//käydään hakemassa tietokannasta tiedot lomakkeen esitäyttöä varten
	global $connection;
	$tbl_name = 'kayttaja';
	$id = $_GET['id'];
	
	$query = "SELECT * FROM $tbl_name WHERE id='$id'";
	$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
	$row = mysqli_fetch_assoc($result);
	$id = $row['id'];
	$email = $row['sahkoposti'];
	$enimi = $row['etunimi'];
	$snimi = $row['sukunimi'];
	$puhelin = $row['puhelin'];
	$demo = $row['demo'];
	$voimassaolopvm = $row['voimassaolopvm'];
	$rahtimaksu = $row['rahtimaksu'];
	$ilmainen_toimitus = $row['ilmainen_toimitus_summa_raja'];
?>

<h1 class="otsikko">Muokkaa asiakasta</h1>
<div id="lomake">
	<form action="#" name="asiakkaan_tiedot" method="post" accept-charset="utf-8">
		<fieldset><legend>Asiakkaan tiedot</legend>
			<br>
			<label><span>Sähköposti</span></label>
			<p style="display: inline; font-size: 16px;">
				<?= $email; ?>
			</p>
			<input type="hidden" name="email" value="<?= $email;?>" />
			
			<?php
			//Jos asiakkaan käyttäjätunnukselle asetettu aikaraja.
			if ($demo){
				echo '
				<br><br><label><span>Voimassa</span></label>
				<p style="display: inline; font-size: 16px;">' . 
				(new DateTime($voimassaolopvm))->format("d.m.Y H:i:s")
				. '</p>';
			}
			?>
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
			<br>
			<div id="submit">
				<input name="submit" value="Päivitä tiedot" type="submit">
			</div>
		</fieldset>
	</form><br><br>
	
	<form action="#" name="resetoi_salasana" method="post">
		<fieldset><legend>Salasanan vaihto</legend>
			<label><span>Nollaa salasana:</span></label>
				<input name="reset_password" value="Resetoi salasana" type="submit">
				<input name="id" value="<?= $id?>" type="hidden"/>
		</fieldset>
	</form><br><br>

	<?php 
	
	if (isset($_SESSION['result'])){
		if($_SESSION['result'] == -1){
			echo "<p>Sähköpostia ei löytynyt.</p>";
		}
		elseif ($_SESSION['result'] == 1) {
			echo "<p>Tiedot päivitetty.</p>";
		}
		elseif ($_SESSION['result'] == 2) {
			echo "
				<h4>Salasana nollattu.</h4>
				<p>Asiakas joutuu vaihtamaan salasanansa sisäänkirjautuessaan.";
		}
		unset($_SESSION['result']);
	}
	
	elseif (isset($_POST['etunimi'])) {
		$result = db_paivita_tiedot($_POST['email'], $_POST['etunimi'], $_POST['sukunimi'], $_POST['puh'], $_POST['yritysnimi']);
		$_SESSION['result'] = $result;
		//Ladataan sivu uudelleen, jotta kenttien tiedot päivittyvät
		header("Location: http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
		exit;
	}
	elseif (isset($_POST['reset_password'])) {
		$result = pakota_salasanan_vaihto($_POST['id']);
		$_SESSION['result'] = $result;
		//Ladataan sivu uudelleen, jotta kenttien tiedot päivittyvät
		header("Location: http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
		exit;
	}

	//result:
	//-1	salasanat ei täsmää
	//-2	käyttäjätunnusta ei olemassa
	//1		lisäys onnistui
	function db_paivita_tiedot($email, $asiakas_etunimi, $asiakas_sukunimi, $asiakas_puh, $asiakas_yritysnimi){
		global $connection;
		$tbl_name="kayttaja";				// Taulun nimi
		$asiakas_sposti = $email;


		//Tarkastetaan löytyykö käyttäjätunnusta
		$query = "SELECT id FROM $tbl_name WHERE sahkoposti='$asiakas_sposti'";
		$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
		$count = mysqli_num_rows($result);
		if($count != 1){
			return -1; //käyttäjänimeä ei löytynyt
		}else {
			//päivitetään tietokantaan
			$query = "UPDATE $tbl_name SET etunimi='$asiakas_etunimi', sukunimi='$asiakas_sukunimi', puhelin='$asiakas_puh'
			WHERE sahkoposti='$asiakas_sposti'";
			mysqli_query($connection, $query) or die(mysqli_error($connection));

			return 1;	//talletetaan tulos sessioniin
		}
	}
	
	function pakota_salasanan_vaihto($id){
		global $connection;
		$query = "UPDATE kayttaja SET salasana_uusittava=1 WHERE id=$id";
		mysqli_query($connection, $query) or die(mysqli_error($connection));
		return 2;
	}

?>
</div>

</body>
</html>

