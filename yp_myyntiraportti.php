<?php
require '_start.php'; global $db, $user, $cart;

//Vain ylläpitäjälle
if ( !$user->isAdmin() ) { header("Location:etusivu.php"); exit(); }

?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<title>Raportit</title>
</head>
<body>
<?php include("header.php");?>
<main class="main_body_container">
    <section>
        <h1 class="otsikko">Myyntiraportti</h1>
        <div id="painikkeet">
            <a class="nappi grey" href="yp_raportit.php">Takaisin</a>
        </div>
    </section>

	<div class="feedback success" hidden>Odota kunnes raportti valmistuu!</div>
	<fieldset><legend>Raportin rajaukset</legend>
		<form action="yp_luo_myyntiraportti.php" method="post" id="myyntiraportti">

			<!-- Päivämäärän valinta -->
			<label for="pvm_from">From: </label>
			<input type="date" name="pvm_from" id="pvm_from" required>
			<br><br>
			<label for="pvm_to">To: </label>
			<input type="date" name="pvm_to" id="pvm_to" value="<?=date("Y-m-d")?>" required>
			<br><br>
			<input name="luo_raportti" type="submit" value="Lataa raportti">

		</form>
	</fieldset>


</main>
<script>
	$(document).ready(function(){
		$("#myyntiraportti").on("submit", function(e) {
			$(".feedback").show().fadeOut(5000);

		});
	});
</script>
</body>
</html>

