<?php
require '_start.php'; global $db, $user, $cart, $yritys;

//Päivitetäänkö omat tiedot
$huomautus = null;
if (isset($_POST['uudet_tiedot'])){
	$huomautus = db_paivita_tiedot($_POST['email'], $_POST['etunimi'], $_POST['sukunimi'], $_POST['puh']);
}
elseif (isset($_POST['new_password'])) {
	$huomautus = vaihda_salasana($_SESSION['id'], $_POST['new_password'], $_POST['confirm_new_password']);
}



//käydään hakemassa tietokannasta tiedot lomakkeen esitäyttöä varten

$email = $_SESSION['email'];
$query = "SELECT * FROM kayttaja WHERE sahkoposti= ? ";
$tiedot = $db->query($query, [$email], FETCH_ALL, PDO::FETCH_OBJ)[0];
$email = $tiedot->sahkoposti;
$enimi = $tiedot->etunimi;
$snimi = $tiedot->sukunimi;
$puhelin = $tiedot->puhelin;
$demo = $tiedot->demo;
$voimassaolopvm = $tiedot->voimassaolopvm;

$tbl_name = 'yritys';
$yritys_id = $tiedot->yritys_id;
$query = "SELECT * FROM $tbl_name WHERE id = ? ";
$yritys = $db->query($query, [$yritys_id], FETCH_ALL, PDO::FETCH_OBJ);
if (count($yritys) == 1) {
	$yritys = $yritys[0];
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="css/jsmodal-light.css">
	<meta charset="UTF-8">
	<title>Omat Tiedot</title>
</head>
<body>
<?php require("header.php"); ?>

<h1 class="otsikko">Omat Tiedot</h1>
<div id="lomake">
	<form action="" name="asiakkaan_tiedot" method="post" accept-charset="utf-8">
		<fieldset><legend>Nykyiset tiedot</legend>
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
				<input type="hidden" name="uudet_tiedot">
				<input name="submit" value="Päivitä tiedot" type="submit">
			</div>
		</fieldset>
	</form><br>

	<br>
	<form action="" name="uusi_salasana" method="post" accept-charset="utf-8">
	<fieldset><legend>Vaihda salasana</legend>	
		<label><span>Uusi salasana</span></label>
		<input name="new_password" type="password" pattern=".{6,}" title="Pituus min 6 merkkiä.">
		<br><br>
		<label><span>Vahvista salasana</span></label>
		<input name="confirm_new_password" type="password" pattern=".{6,}" title="Pituus min 6 merkkiä.">
		<br><br><br>
		<div id="submit">
			<input name="submit" value="Vaihda salasana" type="submit">
		</div>
	</fieldset>
	</form>
	<br><br>
    <?php include 'omat_tiedot_osoitekirja.php'; //Sisältää kaiken toiminnallisuuden osoitekirjaa varten ?>
    <br><br>
    <?php if ($yritys) : ?>
    <fieldset style="display:inline-block; text-align:left;"><legend>Yritys</legend>
        <label><span>Nimi</span></label><?=$yritys->nimi?><br>
        <label><span>Sähköposti</span></label><?=$yritys->sahkoposti?><br>
        <label><span>Puhelin</span></label><?=$yritys->puhelin?><br>
        <label><span>Katuosoite</span></label><?=$yritys->katuosoite?><br>
        <label><span>Postinumero</span></label><?=$yritys->postinumero?><br>
        <label><span>Postitoimipaikka</span></label><?=$yritys->postitoimipaikka?><br>
        <label><span>Maa</span></label><?=$yritys->maa?><br>
    </fieldset>
    <?php endif; ?>


	<?php
    switch ($huomautus){
        case -2:
			echo "<p>Salasanat eivät täsmää.</p>";
            break;
        case -1:
            echo "OK";
            break;
        case 1:
			echo "<p>Tiedot päivitetty.</p>";
            break;
        case 2:
            echo "<p>Salasana vaihdettu.</p>";
            break;
        default :
            continue;
	}
	
	//result:
	//-1	Salasanat eivät täsmää
	//2		Salasana vaihdettu
	function vaihda_salasana($id, $asiakas_uusi_salasana, $asiakas_varmista_uusi_salasana){
		global $db;
		$tbl_name="kayttaja";				// Taulun nimi
		$hajautettu_uusi_salasana = password_hash($asiakas_uusi_salasana, PASSWORD_DEFAULT);
		
		if ($asiakas_uusi_salasana != $asiakas_varmista_uusi_salasana){
			return -2; // salasanat eivät täsmää
		}
		else {
			if ($asiakas_uusi_salasana != "" && $asiakas_varmista_uusi_salasana != ""){
				$query = "UPDATE $tbl_name SET salasana_hajautus= ?
				WHERE id= ? ";
                if ($db->query($query, [$hajautettu_uusi_salasana, $id])) return 2;
			}
		}
		return -1;
	}
	

	//result:
	//1		tiedot päivitetty
	function db_paivita_tiedot($email, $asiakas_etunimi, $asiakas_sukunimi, $asiakas_puh){
		$tbl_name="kayttaja";				// Taulun nimi
		$asiakas_sposti = $email;
        global $db;

        //päivitetään tietokantaan
        $query = "
            UPDATE $tbl_name 
            SET etunimi= ? , sukunimi= ? , puhelin= ?
  		    WHERE sahkoposti= ? ";
        if ($db->query($query, [$asiakas_etunimi, $asiakas_sukunimi, $asiakas_puh, $asiakas_sposti])){
            return 1;
        }
        return -1;


	}

?>


</div>

</body>
</html>
