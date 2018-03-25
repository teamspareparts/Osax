<?php declare(strict_types=1);
require '_start.php'; global $db, $user, $cart;

//Vain ylläpitäjälle
if ( !$user->isAdmin() ) {
	header("Location:etusivu.php");
	exit();
}

?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="https://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<script src="https://code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
	<script src="./js/datepicker-fi.js"></script>
	<title>Raportit</title>
</head>
<body>
<?php include("header.php");?>
<main class="main_body_container">

	<!-- Otsikko ja painikkeet -->
	<div class="otsikko_container">
		<section class="takaisin">
			<a class="nappi grey" href="yp_raportit.php"><i class="material-icons">navigate_before</i>Takaisin</a>
		</section>
		<section class="otsikko">
			<h1>Myyntitapahtumalistaus</h1>
		</section>
		<section class="napit">
		</section>
	</div>

	<div class="feedback"></div>

	<fieldset><legend>Raportin rajaukset</legend>
		<form action="yp_luo_myyntitapahtumalistaus.php" method="post" id="myyntitapahtumalistaus">

			<!-- Päivämäärän valinta -->
			<label for="pvm_from">From: </label>
			<input type="text" name="pvm_from" id="pvm_from" class="datepicker" required>
			<br><br>
			<label for="pvm_to">To: </label>
			<input type="text" name="pvm_to" id="pvm_to" class="datepicker" value="<?=date("Y-m-d")?>" required>
			<br><br>
			<input name="luo_raportti" type="submit" value="Lataa raportti">

		</form>
	</fieldset>

</main>

<?php require 'footer.php'; ?>

<script>
    $(document).ready(function(){
        $("#myyntitapahtumalistaus").on("submit", function(e) {
            $(".feedback").append("<p class='success'>Odota kunnes raportti valmistuu!</p>").fadeOut(5000);
        });
        $('.datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            maxDate: '+0d',
        }).keydown(function(e){
	        e.preventDefault();
        });
    });
</script>
</body>
</html>


