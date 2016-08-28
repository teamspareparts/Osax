<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
	<title>Toimittajat</title>
</head>
<body>
<?php 	
require 'header.php';
require 'tietokanta.php';
require 'tecdoc.php';
if (!is_admin()) {
	header("Location:etusivu.php");
	exit();
}?>
<h1 class="otsikko">Toimittajat</h1><br>
<div class="container">

<?php
function hae_hinnaston_sisaanajo_pvm(){
	global $db;
	$query = "SELECT brandId, sisaanajo_pvm FROM valmistajan_hinnaston_sisaanajo";
	return $db->query($query, [], FETCH_ALL, PDO::FETCH_OBJ);
}

function cmp($a, $b) {
	return strcmp($a->brandName, $b->brandName);
}

$brands = getAmBrands();
usort($brands, "cmp");
$paivamaarat = hae_hinnaston_sisaanajo_pvm();

foreach ($brands as $brand) {
	$logo_src = TECDOC_THUMB_URL . $brand->brandLogoID . "/";
	echo '<div class="floating-box clickable" data-brandId="'.$brand->brandId.'" data-brandName="'.$brand->brandName.'"><div class="line"><img src="'.$logo_src.'" style="vertical-align:middle; padding-right:10px;" /><span>'. $brand->brandName .'</span></div>';
	foreach ($paivamaarat as $pvm) {
		if ( $pvm->brandId == $brand->brandId ) {
			$date = new DateTime($pvm->sisaanajo_pvm);
			echo "Päivitetty: " . $date->format('d.m.Y');
		}
	}
	echo "</div>";
}

?>



<script type="text/javascript">
$(document).ready(function(){
	
	/*$('.clickable').click(function(){
		var brandId = $(this).closest('tr').attr('data-val');
		var brandName = $(this).closest('tr').children("td:nth-child(2)").text();

		//luodaan form
		var form = document.createElement("form");
		form.setAttribute("method", "GET");
		form.setAttribute("action", "toimittajan_hallinta.php");

		//brandId
		var field1 = document.createElement("input");
        field1.setAttribute("type", "hidden");
        field1.setAttribute("name", "brandId");
        field1.setAttribute("value", brandId);
        form.appendChild(field1);

        //brandName
        var field2 = document.createElement("input");
        field2.setAttribute("type", "hidden");
        field2.setAttribute("name", "brandName");
        field2.setAttribute("value", brandName);
        form.appendChild(field2);

		//form submit
		document.body.appendChild(form);
	    form.submit();

	    //-> Toimittajan hallintaan
	});*/

	$('.clickable').click(function(){
		var brandId = $(this).attr('data-brandId');
		var brandName = $(this).attr('data-brandName');

		var form = document.createElement("form");
		form.setAttribute("method", "GET");
		form.setAttribute("action", "toimittajan_hallinta.php");

		//brandId
		var field1 = document.createElement("input");
		field1.setAttribute("type", "hidden");
		field1.setAttribute("name", "brandId");
		field1.setAttribute("value", brandId);
		form.appendChild(field1);

		//brandName
		var field2 = document.createElement("input");
		field2.setAttribute("type", "hidden");
		field2.setAttribute("name", "brandName");
		field2.setAttribute("value", brandName);
		form.appendChild(field2);

		//form submit
		document.body.appendChild(form);
		form.submit();
	});
	
	$('.clickable').css('cursor', 'pointer');
});

</script>
</div>
</body>
</html>
