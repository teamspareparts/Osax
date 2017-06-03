<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
if ( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
}

/**
 * Päivitetään tecdocista löytyvät brändit omaan tietokantaan.
 * @param DByhteys $db
 * @return array|int|stdClass
 */
function paivita_tecdocin_brandit_kantaan( DByhteys $db ){
	$brands = getAmBrands();
	$placeholders = [];
	foreach ( $brands as $brand ) {
	    $placeholders[] = $brand->brandId;
		$placeholders[] = $brand->brandName;
		$placeholders[] = TECDOC_THUMB_URL . $brand->brandLogoID . "/";
    }
	$questionmarks = implode(',', array_fill( 0, count($brands), '( ?, ?, ? )'));
	$sql = "INSERT INTO brandi (id, nimi, url)
            VALUES {$questionmarks}
            ON DUPLICATE KEY
            UPDATE id = VALUES(id), nimi = VALUES(nimi), 
              url = VALUES(url)";
	return $db->query($sql, $placeholders);
}


/**
 * Lisätään oma brändi.
 * @param DByhteys $db
 * @param $values
 * @return array|int|stdClass
 */
function lisaa_brandi( DByhteys $db, /*string*/$nimi, /*string*/$kuva_url ) {
	// Lasketaan oma (tecdoc) id. Omien brändien id:t lähtee 100 000:sta.
	$sql = "SELECT COUNT(id) AS count FROM brandi WHERE oma_brandi IS TRUE";
	$kpl = $db->query($sql, [])->count;
	$vapaa_id = 100000 + $kpl;

    $sql = "INSERT INTO brandi (id, nimi, url, oma_brandi)
            VALUES( ?, ?, ?, 1 )
            ON DUPLICATE KEY
            UPDATE nimi = VALUES(nimi), url = VALUES(url), aktiivinen = 1";
	return $db->query($sql, [$vapaa_id, $nimi, $kuva_url]);
}

//Haetaan kaikki brändit
$sql = "SELECT brandi.*, MAX(hinnaston_sisaanajo_pvm) AS hinnaston_pvm FROM brandi 
		LEFT JOIN brandin_linkitys
			ON brandi.id = brandin_linkitys.brandi_id
		WHERE aktiivinen = 1
		GROUP BY brandi.id
		ORDER BY nimi ASC";
$brands = $db->query($sql, [], FETCH_ALL);

if ( isset($_POST['paivita']) ) {
    paivita_tecdocin_brandit_kantaan( $db );
}
elseif ( isset($_POST['lisaa']) ) {
	lisaa_brandi( $db, $_POST['nimi'], $_POST['kuva_url'] );
}
if ( !empty($_POST) || !empty($_FILES) ) { //Estetään formin uudelleenlähetyksen
	header("Location: " . $_SERVER['REQUEST_URI']);
	exit();
}
$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : '';
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
    <title>Toimittajat</title>
</head>
<body>

<!-- Form tecdocin tietojen päivittämiseen -->
<form action="" method="post" id="paivita_tecdoc">
    <input type="hidden" name="paivita">
</form>

<?php require 'header.php'; ?>
<!-- Otsikko ja napit -->
<section>
    <h1 class="otsikko">Brändit</h1>
    <div id="painikkeet">
        <button class="nappi" onClick="document.getElementById('paivita_tecdoc').submit();">Päivitä TecDoc</button>
        <!-- <button class="nappi" onClick="avaa_modal_uusi_brandi();">Lisää oma brändi</button> -->
    </div>
</section><br>

<!-- Brändien listaus -->
<div class="container">
    <?php foreach ($brands as $brand) : ?>
	    <!-- Brändille oma box -->
        <div class="floating-box clickable"  data-brandId="<?=$brand->id?>">
	        <!-- Brändin nimi ja kuva -->
            <div class="line">
                <img src="<?=$brand->url?>" style="vertical-align:middle; padding-right:10px;">
                <span><?=mb_strtoupper($brand->nimi)?></span>
            </div>
	        <!-- Viimeisin hinnastonpäivitys -->
            <?php if ( !empty($brand->hinnaston_pvm) ) : ?>
                <span>Päivitetty: <?=date('d.m.Y',strtotime($brand->hinnaston_pvm))?></span>
            <?php endif;?>
        </div>
    <?php endforeach;?>
</div>



<script type="text/javascript">

    /**
     * Modal oman brändin lisäämiseen (ei tecdocissa)
     */
    function avaa_modal_uusi_brandi(){
        Modal.open( {
            content:  `
				<h4>Anna uuden brändin tiedot.</h4>
				<hr><br>
				<form action="" method="post" name="uusi_hankintapaikka" id="uusi_hankintapaikka">
					<label class="required">Brändin nimi</label>
					<input name="nimi" type="text" placeholder="BOSCH" title="Brändin nimi" required>
					<br><br>
					<label>Kuvan URL (valinnainen)</label>
					<input name="kuva_url" type="text" placeholder="url.com/photos/12345" title="Max 100 merkkiä." pattern=".{3,100}">
					<br><br>
					<input class="nappi" type="submit" name="lisaa" value="Tallenna">
				</form>
				`,
            draggable: true
        } );
    }

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
