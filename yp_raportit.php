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
    <style>
        .floating-box span { color: #2A4E77; font-weight: bold; }
    </style>
</head>
<body>
<?php include("header.php");?>
<main class="main_body_container">
    <h1>Raportit</h1>

    <div class="floating-box clickable line" data-href="yp_varastolistausraportti.php"><span>Varastolistausraportti</span></div>
    <div class="floating-box clickable line" data-href="yp_myyntiraportti.php"><span>Myyntiraportti</span></div>
    <div class="floating-box clickable line" data-href="yp_luo_hinnastotiedosto.php"><span>Lataa hinnastot</span></div>
</main>
<script>
	$(document).ready(function(){

        $('.clickable').click(function(){
			window.document.location = $(this).data('href');
        }).css('cursor', 'pointer');

	});
</script>
</body>
</html>
