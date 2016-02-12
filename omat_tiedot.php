<!DOCTYPE html>
<html lang="fi">
<head>
	<link rel="stylesheet" href="css/styles.css">
	<meta charset="UTF-8">
	<title>Omat Tiedot</title>
</head>
<body>
<?php include("header_asiakas.php");?>

<h1 class="otsikko">Omat Tiedot</h1>

<!------------------------------------------------------------
	Esitäytetään lomake $_SESSION muuttujaan talletetuilla 
	tiedoilla. (Talletettu kirjautumisen yhteydessä.)
	Näin vältetään turhat tietokantahaut.
------------------------------------------------------------->

<div id="lomake">
		<form action="db_paivita_tiedot.php" name="uusi_asiakas" method="post" accept-charset="utf-8">
			<fieldset><legend>Nykyiset tiedot</legend>
				<br>
				<label><span>Sähköposti</span></label>
				<p style="display: inline; color: blue; font-size: 16px;">
					<?php echo $_SESSION['user']; ?>
				</p>
				<br><br>
				<label><span>Etunimi</span></label>
				<input name="etunimi" type="text" pattern="[a-zA-Z]{3,20}" value="<?php echo $_SESSION['etunimi']; ?>" title="Vain aakkosia.">
				<br><br>
				<label><span>Sukunimi</span></label>
				<input name="sukunimi" type="text" pattern="[a-zA-Z]{3,20}" value="<?php echo $_SESSION['sukunimi']; ?>" title="Vain aakkosia">
				<br><br>
				<label><span>Puhelin</span></label>
				<input name="puh" type="text" pattern=".{1,20}" value="<?php echo $_SESSION['puhelin']; ?>">
				<br><br>
				<label><span>Yrityksen nimi</span></label>
				<input name="yritysnimi" type="text" pattern=".{1,50}" value="<?php echo $_SESSION['yritysnimi']; ?>">
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
	
		//result:
		//-1	salasanat ei täsmää
		//-2	käyttäjätunnus jo olemassa
		//1		lisäys onnistui
		
		
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
		?>
	</div>
	
</body>
</html>

