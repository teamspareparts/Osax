<?php declare(strict_types=1);
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
if ( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
}

/**
 * Hakee kaikki hankintapaikat.
 * @param DByhteys $db
 * @return array <p> Palauttaa hankintapaikkojen nimet, jos löytyi. Muuten false.
 */
function hae_kaikki_hankintapaikat( DByhteys $db ) : array{
	$sql = "SELECT id, nimi, LPAD(`id`,3,'0') AS hankintapaikka_id FROM hankintapaikka";
	return $db->query($sql, [], FETCH_ALL);
}

/**
 * Poistaa linkityksen valmistajan ja hankintapaikan väliltä.
 * @param DByhteys $db
 * @param int $hankintapaikka_id
 * @param int $brandId
 * @return bool
 */
function poista_linkitys( DByhteys $db, int $hankintapaikka_id, int $brand_id) : bool {
	// Deaktivoidaan tuotteet
	$sql = "UPDATE tuote SET aktiivinen = 0 WHERE hankintapaikka_id = ? AND brandNo = ?";
	$result1 = $db->query($sql, [$hankintapaikka_id, $brand_id]);
	// Poistetaan linkitykset hankintapaikan ja yrityksen välillä.
	$sql = "DELETE FROM brandin_linkitys WHERE hankintapaikka_id = ? AND brandi_id = ? ";
	$result2 = $db->query($sql, [$hankintapaikka_id, $brand_id]);
	if ( !$result1 || !$result2 ) {
		return false;
	}
	return true;
}

/**
 * Deaktivoi brändin ja brändin tuotteet sekä poistaa kaikki linkitykset.
 * @param DByhteys $db
 * @param int $brandi_id
 * @return bool
 */
function poista_brandi( DByhteys $db, int $brandi_id ) : bool {
    $sql = "DELETE FROM brandin_linkitys WHERE brandi_id = ?";
    $result1 = $db->query($sql, [$brandi_id]);
    $sql = "UPDATE tuote SET aktiivinen = 0 WHERE brandNo = ?";
	$result2 = $db->query($sql, [$brandi_id]);
    $sql = "UPDATE brandi SET aktiivinen = 0 WHERE id = ?";
    $result3 =  $db->query($sql, [$brandi_id]);
	if ( !$result1 || !$result2 || !$result3 ) {
		return false;
	}
	return true;
}

/**
 * Muokkaa brändin tietoja.
 * @param DByhteys $db
 * @param int $brand_id
 * @param string $nimi
 * @param string $url
 * @return bool
 */
function muokkaa_brandi( DByhteys $db, int $brand_id, string $nimi, string $url ) : bool {
	$sql = "UPDATE brandi SET nimi = ?, url = ? WHERE id = ?";
	$result = $db->query($sql, [$nimi, $url, $brand_id]);
	if ( !$result ) {
		return false;
	}
	return true;
}

/**
 * Hakee hankintapaikat
 * @param DByhteys $db
 * @param int $brand_id
 * @return array
 */
function hae_hankintapaikat( DByhteys $db, int $brand_id) : array {

	//tarkastetaan onko valmistajaan linkitetty hankintapaikka
	$sql = "	SELECT *, LPAD(`id`,3,'0') AS hankintapaikka_id FROM brandin_linkitys
 				JOIN hankintapaikka
 					ON brandin_linkitys.hankintapaikka_id = hankintapaikka.id
 				WHERE brandin_linkitys.brandi_id = ? ";
	return $db->query($sql, [$brand_id], FETCH_ALL);
}

// GET-parametri
$brand_id = isset($_GET['brandId']) ? (int)$_GET['brandId'] : null;

// Tarkastetaan GET-parametrin oikeellisuus
$brand = $db->query("SELECT * FROM brandi WHERE id = ? AND aktiivinen = 1 LIMIT 1", [$brand_id]);
if ( !$brand ) {
	header("Location:yp_toimittajat.php"); exit();
}

// Haetaan brändin yhteystiedot ja logon URL
$brand_address = getAmbrandAddress($brand->id);
$brand_address = !empty($brand_address) ? $brand_address[0] : null;
$hankintapaikat = hae_hankintapaikat($db, $brand->id);
$brand_logo_src = !empty($brand_address) ? TECDOC_THUMB_URL . $brand_address->logoDocId . "/" : "";

// Haetaan kaikki hankintapaikat valmiiksi hankintapaikka -modalia varten varten
$kaikki_hankintapaikat = hae_kaikki_hankintapaikat( $db );

if ( isset($_POST['poista_linkitys']) ) {
	poista_linkitys($db, (int)$_POST['hankintapaikka_id'], (int)$_POST['brand_id']);
}
elseif (isset($_POST['muokkaa'])) {
    muokkaa_brandi($db, (int)$_POST['brand_id'], $_POST['nimi'], $_POST['url']);
}
elseif (isset($_POST['poista'])) {
    poista_brandi($db, (int)$_POST['brand_id']);
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
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/jsmodal-light.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
    <script src="js/jsmodal-1.0d.min.js"></script>
    <title>Toimittajat</title>
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container">

	<div class="otsikko_container">
		<section class="takaisin">
			<a href="yp_toimittajat.php" class="nappi grey"><i class="material-icons">navigate_before</i>Takaisin</a>
		</section>
		<section class="otsikko">
			<img src="<?=$brand_logo_src?>" style="vertical-align: middle; padding-right: 20px; display:inline-block;">
			<h1 style="display:inline-block; vertical-align:middle;"><?= $brand->nimi?></h1>
		</section>
		<section class="napit">
			<?php if ( $brand->oma_brandi ) : ?>
				<button class="nappi" onClick="avaa_modal_muokkaa_brandi(<?=$brand->id?>, '<?=$brand->nimi?>','<?=$brand->url?>')">
					Muokkaa brändiä</button>
				<button class="nappi red" onClick="poista_brandi(<?=$brand->id?>)">
					Poista brändi</button>
			<?php endif;?>
		</section>
	</div>

	<?=$feedback?>

    <!-- Brändin yhteystiedot -->
    <?php if ( !empty($brand_address) ) : ?>
        <table class="inline-block" style="padding-right: 80pt; vertical-align: top;">
            <thead>
            <tr><th colspan='2' class='text-center'>Yhteystiedot</th></tr>
            </thead>
            <tbody>
            <tr><td>Yritys</td><td><?=$brand_address->name?></td></tr>
            <tr><td>Osoite</td><td><?=$brand_address->street?><br><?=$brand_address->zip?> <?=strtoupper($brand_address->city)?></td></tr>
            <tr><td>Puh</td><td><?=$brand_address->phone?></td></tr>
            <?php if (isset($brand_address->fax)) : ?>
                <tr><td>Fax</td><td><?$brand_address->fax?></td></tr>
            <?php endif; if(isset($brand_address->email)) : ?>
                <tr><td>Email</td><td><?=$brand_address->email?></td></tr>
            <?php endif; ?>
            <tr><td>URL</td><td><?=$brand_address->wwwURL?></td></tr>
            <tr><td colspan="2">&nbsp;</td></tr>
            <tr><td>TecDoc ID</td><td><?=$brand->id?></td></tr>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Hankintapaikkojen yhteystiedot -->
    <?php foreach( $hankintapaikat as $i=>$hankintapaikka ) : ?>
        <table class="inline-block" style="padding-right: 30pt; vertical-align: top">
            <tr><th colspan='2' class='text-center'>Hankintapaikka <?=++$i?></th></tr>
            <tr><td>ID</td><td><?= $hankintapaikka->id?></td></tr>
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
            <tr>
                <td colspan="2">
                    <button onclick="poista_linkitys(<?=$hankintapaikka->id?>, <?=$brand->id?>)" class="nappi red">
                        Poista linkitys</button>
            </tr>
            <tr>
                <td colspan="2">
                    <a href="yp_valikoima.php?brand=<?=$brand->id?>&hankintapaikka=<?=intval($hankintapaikka->id)?>" class="nappi">Valikoima</a></td>
            </tr>
        </table>
	<?php endforeach; ?>

</main>

<?php require 'footer.php'; ?>

<script>
	//
	// Avataan modal, jossa voi täyttää uuden toimittajan yhteystiedot
	// tai valita jo olemassa olevista.
	//
	function avaa_modal_linkitys(brand_id){
		Modal.open( {
			content:  `
				<div>
				<h4>Valitse linkitettävä hankintapaikka listasta.</h4>
				<hr><br><br>
				<form action="" method="post" id="valitse_hankintapaikka">
                    <label>Hankintapaikat</label>
                    <select name="hankintapaikka" id="hankintapaikka">
                        <option value="0">-- Hankintapaikka --</option>
                    </select>
                    <br><br>
                    <input class="nappi" type="submit" name="lisaa_linkitys" value="Valitse">
                    <input type="hidden" name="brand_id" value="`+brand_id+`">
                </form>
				</div>
				`,
			draggable: true
		} );

        let hankintapaikka_lista, hankintapaikka, i;
        let hankintapaikat = [];
        hankintapaikat = <?php echo json_encode($kaikki_hankintapaikat);?>;
        //Täytetään Select-Option
        hankintapaikka_lista = document.getElementById("hankintapaikka");
        for (i = 0; i < hankintapaikat.length; i++) {
            hankintapaikka = new Option(hankintapaikat[i].hankintapaikka_id+" - "+hankintapaikat[i].nimi, hankintapaikat[i].id);
            hankintapaikka_lista.options.add(hankintapaikka);
        }

	}

	/**
     * Modal bräändin tietojen muokkaamiseen
     */
	function avaa_modal_muokkaa_brandi(brand_id, nimi, url){
        Modal.open({
            content: `
                <h4>Muokkaa brändin `+nimi+` tietoja.</h4>
			    <br><br>
                <form action="" method="post" name="muokkaa_brandi">
					<label class="required">Brändin nimi</label>
				    <input type="text" name="nimi" placeholder="BOSCH" value="`+nimi+`" required>
				    <br><br>
				    <label>Kuvan URL (valinnainen)</label>
				    <input type="text" name="url" value="`+url+`" placeholder="url.com/photos/12345" title="Max 100 merkkiä." pattern=".{3,100}">
				    <br><br>
				    <input type="submit" name="muokkaa" value="Muokkaa" class="nappi">
				    <input type="hidden" name="brand_id" value="`+brand_id+`">
			    </form>
            `,
            draggable: true,
        });
    }

    /**
     * Luo piilotetun formin, jota tarvitaan linkityksen poistamiseen
     */
	function poista_linkitys (hankintapaikka_id, brand_id) {
        let c = confirm("Haluatko varmasti poistaa hankintapaikan kyseiseltä brändiltä?\r\n" +
	                    "Tämä toiminto deaktivoi kaikki tämän brändin tuotteet kyseiseltä hankintapaikalta.");
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

    /**
     * Luo piilotetun formin, jota tarvitaan brändin poistamiseen
     */
    function poista_brandi (brand_id) {
        let c = confirm("Haluatko varmasti poistaa brändin?\n\n" +
	        "Tämä toiminto deaktivoi kaikki kyseisen brändin tuotteet.");
        if (c === false) {
            e.preventDefault();
            return false;
        }
        let form = document.createElement("form");
        form.setAttribute("method", "POST");
        form.setAttribute("action", "");
        form.setAttribute("name", "poista_linkitys");


        //POST["poista"]
        let field = document.createElement("input");
        field.setAttribute("type", "hidden");
        field.setAttribute("name", "poista");
        field.setAttribute("value", true);
        form.appendChild(field);

        //POST["brand_id"]
        field = document.createElement("input");
        field.setAttribute("type", "hidden");
        field.setAttribute("name", "brand_id");
        field.setAttribute("value", brand_id);
        form.appendChild(field);

        document.body.appendChild(form);
        form.submit();
    }

    $(document).ready(function() {
        $(document.body)

            .on('submit', '#valitse_hankintapaikka', function(e){
				//Estetään valitsemasta hankintapaikaksi labelia
                let hankintapaikka = document.getElementById("hankintapaikka");
                let id = parseInt(hankintapaikka.options[hankintapaikka.selectedIndex].value);
                if (id === 0) {
                    e.preventDefault();
                    return false;
                }
            });
    });
	
</script>
</body>
</html>






