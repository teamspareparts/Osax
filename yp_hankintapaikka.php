<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
if ( !$user->isAdmin() ) {
	header("Location:etusivu.php");
	exit();
}

/**
 * @param DByhteys $db
 * @param $hankintapaikka_id
 * @param $brand_id
 * @return array|int|stdClass
 */
function poista_linkitys( DByhteys $db, /*int*/ $hankintapaikka_id, /*int*/ $brand_id){
	//Poistetaan linkitykset hankintapaikan ja yrityksen välillä.
	//TODO: deactivate products
	$sql = "UPDATE tuote SET tuote.aktiivinen = 0 WHERE hankintapaikka_id = ? AND brandNo = ?";
	$db->query($sql, [$hankintapaikka_id, $brand_id]);
	$sql = "DELETE FROM brandin_linkitys WHERE hankintapaikka_id = ? AND brandi_id = ? ";
	return $db->query($sql, [$hankintapaikka_id, $brand_id]);
}

// GET-parametri
$hankintapaikka_id = isset($_GET['hankintapaikka_id']) ? $_GET['hankintapaikka_id'] : null;

// Hankintapaikan tiedot
$hankintapaikka = $db->query("SELECT *, LPAD(id, 3, '0') AS hankintapaikka_id FROM hankintapaikka WHERE id = ?", [$hankintapaikka_id]);

// Tarkistetaan GET-parametrien oikeellisuus
if ( !$hankintapaikka ) {
	header("Location:yp_hankintapaikat.php");
	exit();
}

// Haetaan linkitetyt brändit
$sql = "SELECT * FROM brandin_linkitys
		LEFT JOIN brandi
			ON brandin_linkitys.brandi_id = brandi.id
		WHERE brandin_linkitys.hankintapaikka_id = ?";
$linkitetyt_brandit = $db->query($sql, [$hankintapaikka_id], FETCH_ALL);


if ( isset($_POST['poista_linkitys']) ) {
	poista_linkitys($db, $_POST['hankintapaikka_id'], $_POST['brand_id']);
}
elseif ( isset($_POST['xxx']) ) {

}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if (!empty($_POST)) {
	header("Location: " . $_SERVER['REQUEST_URI']); //Estää formin uudelleenlähetyksen
	exit();
}
$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : "";
unset($_SESSION["feedback"]);

?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/jsmodal-light.css">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<script src="js/jsmodal-1.0d.min.js"></script>
	<title>Ostotilauskirjat</title>
</head>
<body>
	<?php require 'header.php'; ?>
	<main class="main_body_container">

		<div class="otsikko_container">
			<section class="takaisin">
				<a href="yp_hankintapaikka.php" class="nappi grey"><i class="material-icons">navigate_before</i>Takaisin</a>
			</section>
			<section class="otsikko">
				<span><?=$hankintapaikka->hankintapaikka_id?>&nbsp;&nbsp;</span>
				<h1><?=$hankintapaikka->nimi?></h1>
			</section>
			<section class="napit">
				<a href="yp_hankintapaikka_linkitys.php?hankintapaikka_id=<?=$hankintapaikka->id?>" class="nappi">Linkitä brändi</a>
				<a href="yp_lisaa_tuotteita.php?hankintapaikka=<?=$hankintapaikka->id?>" class="nappi" >Lisää TecDoc tuotteita</a>
				<a href="yp_lisaa_omia_tuotteita.php?hankintapaikka=<?=$hankintapaikka->id?>" class="nappi" >Perusta omia tuotteita</a>
			</section>
		</div>

		<!-- Yhteystiedot -->
		<table style="float: left; padding-right: 50px;">
			<thead>
				<tr><th colspan='2' class='text-center'>Yhteystiedot</th></tr>
			</thead>
			<tbody>
				<tr><td>ID</td><td><?= $hankintapaikka->hankintapaikka_id?></td></tr>
				<tr><td>Yritys</td><td><?= $hankintapaikka->nimi?></td></tr>
				<tr><td>Osoite</td><td><?= $hankintapaikka->katuosoite?><br><?= $hankintapaikka->postinumero, " ", $hankintapaikka->kaupunki?></td></tr>
				<tr><td>Maa</td><td><?= $hankintapaikka->maa?></td></tr>
				<tr><td>Puh</td><td><?= $hankintapaikka->puhelin?></td></tr>
				<tr><td>Fax</td><td><?= $hankintapaikka->fax?></td></tr>
				<tr><td>URL</td><td><?= $hankintapaikka->www_url?></td></tr>
				<tr><td>Tilaustapa</td><td><?= $hankintapaikka->tilaustapa?></td></tr>
				<tr><th colspan='2' class='text-center'>Yhteyshenkilö</th></tr>
				<tr><td>Nimi</td><td><?= $hankintapaikka->yhteyshenkilo_nimi?></td></tr>
				<tr><td>Puh</td><td><?= $hankintapaikka->yhteyshenkilo_puhelin?></td></tr>
				<tr><td>Email</td><td><?= $hankintapaikka->yhteyshenkilo_email?></td></tr>
			</tbody>
		</table>

		<!-- Listaus linkitetyistä brändeistä -->
		<table>
			<thead>
				<tr><th colspan="5" class="center">Linkitetyt brändit</th></tr>
			</thead>
			<tbody>
			<?php foreach ($linkitetyt_brandit as $brand) : ?>
				<tr><td><img src="<?=$brand->url?>"></td>
					<td><?=mb_strtoupper($brand->nimi)?></td>
					<td><?=$brand->brandi_kaytetty_id?></td>
					<td><?=isset($brand->hinnaston_sisaanajo_pvm) ? date('j.n.Y', strtotime($brand->hinnaston_sisaanajo_pvm)) : ''?></td>
					<td><button class="nappi red" onclick="poista_linkitys(<?=$hankintapaikka->id?>, <?=$brand->id?>)">
							Poista linkitys</button>
						<a href="yp_valikoima.php?brand=<?=$brand->id?>&hankintapaikka=<?=$hankintapaikka->id?>" class="nappi">
							Valikoima</a>
					</td></tr>
			<?php endforeach;?>
			</tbody>
		</table>


	</main>
	
<?php require 'footer.php'; ?>

</body>
</html>

<script>
    /**
     * Luo piilotetun formin, jota tarvitaan linkityksen poistamiseen
     */
    function poista_linkitys (hankintapaikka_id, brand_id) {
        let c = confirm("Haluatko varmasti poistaa linkityksen brändiin?\r\n" +
	                    "Toiminto deaktivoi kyseisen hankintapaikan brändin tuotteet.");
        if (c === false) {
            e.preventDefault();
            return false;
        }
        let form = document.createElement("form");
        form.setAttribute("method", "POST");
        form.setAttribute("action", "");
        form.setAttribute("name", "poista_linkitys");


        //POST["poista_linkitys"]
        let field = document.createElement("input");
        field.setAttribute("type", "hidden");
        field.setAttribute("name", "poista_linkitys");
        field.setAttribute("value", true);
        form.appendChild(field);

        //POST["hankintapaikka_id"]
        field = document.createElement("input");
        field.setAttribute("type", "hidden");
        field.setAttribute("name", "hankintapaikka_id");
        field.setAttribute("value", hankintapaikka_id);
        form.appendChild(field);

        //POST["hankintapaikka_id"]
        field = document.createElement("input");
        field.setAttribute("type", "hidden");
        field.setAttribute("name", "brand_id");
        field.setAttribute("value", brand_id);
        form.appendChild(field);

        document.body.appendChild(form);
        form.submit();
    }
</script>
