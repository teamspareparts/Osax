<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>ALV-testi</title>
	<?php require 'tietokanta.php';?>
</head>
<body>

<?php
$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME)
				or die("Connection error:" . mysqli_connect_error());
				
function hae_kaikki_ALV_tasot_ja_tulosta() {
	global $connection;
	$sql_query = "
			SELECT	*
			FROM	ALV_taso";
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	
	$result->fetch_assoc();   //En halua ensimmaista tulosta, joka on (0, 0.00).
	
	while ($row = $result->fetch_assoc()) {
        printf (
			"<label>ALV-taso %s:</label><input type=\"text\" name=\"kentta[]\" value=\"%s\"><br>", 
			$row['taso'], $row['prosentti']);
    }
	$row_count = mysqli_num_rows($result);   //Tarvitaan lomakkeen numerointia varten.
	$data = array("i" => $row_count);
}
?>

<script>
var i = <?= json_encode($data) ?>;

function lisaa_uusi_ALV() {
	var newdiv = document.createElement('div');
	newdiv.innerHTML = "<label>ALV-taso " + i + ":</label>" 
		+ "<input type=\"text\" name=\"kentta[]\" placeholder=\"0,00\"><br>";
	document.getElementById('alv_form_container').appendChild(newdiv);
	i++;
}
</script>


<div>Muokkaa ALV-tasoja</div>
<br>

<form action=alv-test.php name=testilomake method=post>
	<div id=alv_form_container>
		<?php hae_kaikki_ALV_tasot_ja_tulosta() ?>
	</div>
	<input type=button name=add_New_ALV_button value="+ uusi ALV-taso" onclick=lisaa_uusi_ALV()><br>
	<br>
	<input type=submit name=post value="Tallenna muutokset">
</form>



<?php

if ( !empty($_POST["post"]) ) {
	$array = $_POST["kentta"];
	
	foreach ($array as $data) {
		echo $data . "<br>";
	}
}
?>

</body>
</html>