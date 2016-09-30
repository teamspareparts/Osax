<?php
require '_start.php';
require 'tecdoc.php';
if (!is_admin()) {
	header("Location:etusivu.php");
	exit();
}
/**
 * Hakee kaikkien tietokannasta löytyvien valmistajien valmistajien id:t
 * ja hinnastojen sisäänajopäivämäärät.
 * @return array|bool|stdClass
 */
function hae_hinnaston_sisaanajo_pvm( DByhteys $db, /*int*/ $brandId){
	$query = "SELECT MAX(hinnaston_sisaanajo_pvm) as suurin_pvm FROM valmistajan_hankintapaikka WHERE brandId = ?";
	return $db->query($query, [$brandId], NULL, PDO::FETCH_OBJ);
}

function tulosta_brandit(DByhteys $db, array $brands){
	//Tulostetaan "laatikot", jotka sisältävät kuvan, nimen ja hinnaston sisäänajopäivämäärän
	foreach ($brands as $brand) {
		$pvm = hae_hinnaston_sisaanajo_pvm( $db, $brand->brandId );
		$logo_src = TECDOC_THUMB_URL . $brand->brandLogoID . "/";
		echo '<div class="floating-box clickable"  data-brandId="'.$brand->brandId.'"><div class="line"><img src="'.$logo_src.'" style="vertical-align:middle; padding-right:10px;" /><span>'. $brand->brandName .'</span></div>';
		if ($pvm->suurin_pvm) {
			$date = new DateTime($pvm->suurin_pvm);
			echo "Päivitetty: " . $date->format('d.m.Y');
		}
		echo "</div>";
	}
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
//$valmistajat = hae_hinnaston_sisaanajo_pvm();
?>


<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
	<title>Toimittajat</title>
</head>
<body>
<?php require 'header.php'; ?>
<h1 class="otsikko">Toimittajat</h1><br>
<div class="container">
<?= tulosta_brandit($db, $brands)?>

</div>



<script type="text/javascript">
$(document).ready(function(){

	//Submit form
	$('.clickable').click(function(){
		var brandId = $(this).attr('data-brandId');
		var brandName = $(this).attr('data-brandName');
		var valmistajaId = $(this).attr('data-valmistajaId');

		var form = document.createElement("form");
		form.setAttribute("method", "GET");
		form.setAttribute("action", "toimittajan_hallinta.php");

		//brandId	(Tecdocista saatava)
		var field = document.createElement("input");
		field.setAttribute("type", "hidden");
		field.setAttribute("name", "brandId");
		field.setAttribute("value", brandId);
		form.appendChild(field);


		//form submit
		document.body.appendChild(form);
		form.submit();
	});
	
	$('.clickable').css('cursor', 'pointer');
});

</script>
</body>
</html>
