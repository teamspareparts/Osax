<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<title>Asiakkaat</title>
</head>
<body>
<?php include("header_yllapito.php");?>
<div id=asiakas>
	<h1 class="otsikko">Asiakkaat</h1>
	<div id="painikkeet">
		<a href="yp_lisaa_asiakas.php"><span class="lisaa_asiakas_painike">Lisää uusi asiakas</span></a>
	</div>
	<br><br><br>
</div>
	
	<div id="asiakas_lista">
	
		<form action="db_poista_asiakas.php" method="post">
		<fieldset class="asiakas_info">
			<p><span class="etunimi">Etunimi</span><span class="sukunimi">Sukunimi</span><span class="puhelin">Puhelin</span><span class="yritys">Yritys</span><span class="sposti">Sähköposti</span><span>Poista</span></p>
		</fieldset>
		
			<?php 
			
				$host = "localhost";				// Host
				$username = "root";					// Käyttäjänimi
				$password="";						// Salasana
				$db_name="tuoteluettelo_database";	// Tietokannan nimi
				$tbl_name="kayttaja";				// Taulun nimi
			
				$connection = mysqli_connect($host, $username, $password, $db_name) or die("Connection error:" . mysqli_connect_error());
				
				$query = "SELECT * FROM $tbl_name";
				$result = mysqli_query($connection, $query);
				
				
				//listataan kaikki tietokannasta löytyvät asiakkaat ja luodaan
				//niihin linkit, jotka vievät asiakkaan tilaushistoriaan.
				//Linkin mukana välitetään asiakkaan tietokanta id.
				while($row = mysqli_fetch_assoc($result)){
					if ($row["yllapitaja"] != 1){	//listataan vain asiakkaat
						echo '<fieldset>';
						echo '<a href="yp_asiakkaat.php?id=' . $row["id"] . '"><span class="etunimi">' . $row["etunimi"] . 
							'</span><span class="sukunimi">' . $row["sukunimi"] .
							'</span><span class="puhelin">' . $row["puhelin"] .
							'</span><span class="yritys">' . $row["yritys"] .
							'</span><span class="sposti">' . $row["sahkoposti"] . '</span><a>';
						echo '<input type="checkbox" name="ids[]" value="' . $row["id"] . '"><br>';
						echo '</fieldset>';
					}
				}
				
				
				mysqli_close($connection);
				
			?>
			<br>
			<div id=submit>
				<input type="submit" value="Poista valitut asiakkaat">
			</div>
		</form>		
	</div>
</body>
</html>