<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<title>Tuotteet</title>
</head>
<body>
<?php 	
include 'header.php';
if (!is_admin()) {
	header("Location:tuotehaku.php");
	exit();
}
?>
<h1>Lisää tuotteita tiedostosta</h1>
<p>Tällä sivulla voi valita tiedoston, josta kaikki tuotteet lisätään sivuston valikoimaan...</p>

<form action="lue_tiedostosta.php" method="post" enctype="multipart/form-data">
	Luettava tiedosto: <input type="file" name="file" accept=".csv"/>
	<input type="submit" name="submit" value="Submit" />
</form>

<?php 
if(isset($_FILES['file']['name'])) {
	//Jos ei virheitä...
	if(!$_FILES['file']['error']) {
		
		$kahva = fopen($_FILES['file']['tmp_name'], 'r');
		$sisalto = fgetcsv($kahva);
		
		/************************
		 * 
		 * 
		 * 
		 * Tähän tiedostosta luku
		 * 
		 * 
		 * 
		 * 
		 **************************/
		
	}
	//Jos virhe...
	else
	{
		echo $_FILES['file']['error'];
	}
}


?>

<?php include 'footer.php';?>
</body>
</html>
