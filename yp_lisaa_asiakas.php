<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<title>Asiakkaat</title>
</head>
<body>
<?php include("header_yllapito.php");?>
<div id="lisaa_asiakas">
	<h1 class="otsikko">Lisää asiakas</h1>
	<br><br>
	<div id="asiakas_tiedot">
		<form action="db_lisaa_asiakas.php" name="uusi_asiakas" method="post" accept-charset="utf-8">
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
	
	//result:
	//-1	salasanat ei täsmää
	//-2	käyttäjätunnus jo olemassa
	//1		lisäys onnistui
	
	
		if (isset($_SESSION['result'])){
			if($_SESSION['result'] == -1){
				echo "Sähköposti varattu.";
			}
			elseif ($_SESSION['result'] == -2){
				echo "Salasanat eivät täsmää.";
			}
			else {
				echo "Lisäys onnistui.";
			}
			unset($_SESSION['result']);
		}
	?>
	</div>
</div>
</body>
</html>