<?php
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
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="css/styles.css">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<title>Raportit</title>
    <style>
        .floating-box span { color: #2A4E77; font-weight: bold; }
    </style>
</head>
<body>

<!-- Tiedoston latausta varten -->
<form id="download_hinnasto_yp" method="post" action="download.php">
    <input type="hidden" name="filepath" value="hinnasto/hinnasto.txt">
</form>

<?php include("header.php");?>
<main class="main_body_container">
    <h1>Raportit</h1>

    <div class="floating-box clickable line" data-href="yp_raportti_varastolistaus.php">
	    <span>Varastolistausraportti</span></div>
    <div class="floating-box clickable line" data-href="yp_raportti_myyntiraportti.php"><span>Myyntiraportti</span></div>
    <div class="floating-box clickable line" data-href="yp_raportti_myyntitapahtumalistaus.php">
	    <span>Myyntitapahtumalistaus</span></div>
	<div class="floating-box clickable line" data-href="yp_raportti_tuotekohtainen_myynti.php">
		<span>Tuotekohtainen myyntiraportti</span></div>
    <div onclick="document.getElementById('download_hinnasto_yp').submit()" class="floating-box clickable line">
	    <span>Lataa hinnasto <i class="material-icons">file_download</i></span>
    </div>
</main>
<script>
	$(document).ready(function(){
        $('.clickable').click(function(){
            if ( $(this).data('href') ) {
                window.document.location = $(this).data('href');
            }
        }).css('cursor', 'pointer');
	});
</script>
</body>
</html>
