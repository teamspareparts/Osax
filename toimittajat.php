<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
if ( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
}
/**
 * Hakee kaikkien tietokannasta löytyvien valmistajien valmistajien id:t
 * ja hinnastojen sisäänajopäivämäärät.
 * @return array|bool|stdClass
 */
function hae_hinnaston_sisaanajo_pvm( DByhteys $db, /*int*/ $brandId){
	$query = "SELECT MAX(hinnaston_sisaanajo_pvm) as suurin_pvm FROM valmistajan_hankintapaikka WHERE brandId = ?";
	return $db->query($query, [$brandId]);
}

/**
 * Tulostaa valmistajat HTML-muodossa
 * @param DByhteys $db
 * @param array $brands
 * @return string
 */
function tulosta_brandit(DByhteys $db, array $brands){
    $taulukko = "";
	//Tulostetaan "laatikot", jotka sisältävät kuvan, nimen ja hinnaston sisäänajopäivämäärän
	foreach ($brands as $brand) {
		$pvm = hae_hinnaston_sisaanajo_pvm( $db, $brand->brandId );
		$logo_src = TECDOC_THUMB_URL . $brand->brandLogoID . "/";
		$taulukko .= "<div class=\"floating-box clickable\"  data-brandId=\"{$brand->brandId}\"><div class=\"line\"><img src=\"{$logo_src}\" style=\"vertical-align:middle; padding-right:10px;\"><span>{$brand->brandName}</span></div>";
		if ($pvm->suurin_pvm) {
			$date = new DateTime($pvm->suurin_pvm);
			$taulukko .= "Päivitetty: {$date->format('d.m.Y')}";
		}
		$taulukko .= "</div>";
	}
	return $taulukko;
}

/**
 * Sorttaus algoritmi merkkijonoille.
 * @param $a
 * @param $b
 * @return int
 */
function cmp($a, $b) {
	return strcmp($a->brandName, $b->brandName);
}


//Haetaan kaikki valmistajat
$brands = getAmBrands();
//Järjestetään aakkosten mukaan
usort($brands, "cmp");
?>


<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
    <title>Toimittajat</title>
</head>
<body>
<?php require 'header.php'; ?>
<h1 class="otsikko">Valmistajat</h1><br>
<div class="container">
    <?= tulosta_brandit($db, $brands);?>
</div>



<script type="text/javascript">

$(document).ready(function(){
	$('.clickable').click(function(){
		let brandId = $(this).attr('data-brandId');
		window.document.location = 'toimittajan_hallinta.php?brandId='+brandId;
	})
	.css('cursor', 'pointer');
});

</script>
</body>
</html>
