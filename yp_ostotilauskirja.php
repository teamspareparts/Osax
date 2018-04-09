<?php declare(strict_types=1);
require '_start.php'; global $db, $user, $cart;

if ( !$user->isAdmin() ) {
    header("Location:etusivu.php"); exit();
}

// Tarkastetaan onko GET muuttuja sallittu ja haetaan hankintapaikan tiedot
$hankintapaikka_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$hankintapaikka = $db->query("SELECT * FROM hankintapaikka WHERE id = ?", [$hankintapaikka_id]);
if ( !$hankintapaikka ) {
    header("Location: yp_ostotilauskirja_hankintapaikka.php"); exit();
}

/** Ostotilauskirjan lisäys */
if ( isset($_POST['lisaa']) ) {
    $toimitusjakso = isset($_POST['toimitusjakso']) ? $_POST['toimitusjakso'] : 0;
	$arr = [
		$_POST['tunniste'],
		$_POST['lahetyspvm'],
		$_POST['saapumispvm'],
		$_POST['rahti'],
		$toimitusjakso,
		$_POST['hankintapaikka_id'],
	];
    //Tarkastetaan onko hankintapaikalla jo toistuva tilauskirja
	$sql = "SELECT id FROM ostotilauskirja WHERE toimitusjakso > 0 AND hankintapaikka_id = ?";
    if ( $db->query($sql, [$hankintapaikka_id]) && $toimitusjakso != 0 ) { //Vain yksi toistuva ostotilauskirja
        $_SESSION["feedback"] = "<p class='error'>Hankintapaikalla voi olla vain yksi aktiivinen tilauskirja.</p>";
    }
    else {
		$sql = "INSERT IGNORE INTO ostotilauskirja 
                	(tunniste, oletettu_lahetyspaiva, oletettu_saapumispaiva,
                	rahti, toimitusjakso, hankintapaikka_id)
                VALUES ( ?, ?, ?, ?, ?, ? )";
		if ( $db->query($sql, $arr) ) {
			$_SESSION["feedback"] = "<p class='success'>Uusi ostotilauskirja lisätty.</p>";
		} else {
			$_SESSION["feedback"] = "<p class='error'>Ostotilauskirjan tunniste varattu.</p>";
		}
	}
}
/** Ostotilauskirjan muokkaus */
else if ( isset($_POST['muokkaa']) ) {
	$arr = [
		$_POST['lahetyspvm'],
		$_POST['saapumispvm'],
		$_POST['rahti'],
		$_POST['toimitusjakso'],
		$_POST['ostotilauskirja_id'],
	];
    $sql = "UPDATE ostotilauskirja
            SET oletettu_lahetyspaiva = ?, oletettu_saapumispaiva = ?, rahti = ?, toimitusjakso = ?
            WHERE id = ?";
    if ( $db->query($sql, $arr) ) {
        $_SESSION["feedback"] = "<p class='success'>Muokkaus onnistui.</p>";
    }
    //Merkataan, että tuotteiden riittävyys on laskettava uudelleen
    $sql = "UPDATE tuote
            SET paivitettava = 1
            WHERE id IN (SELECT tuote_id FROM ostotilauskirja_tuote WHERE ostotilauskirja_id = ?)";
    $db->query($sql, [$_POST["ostotilauskirja_id"]]);
}
/** Ostotilauskirjan poistaminen */
else if( isset($_POST['poista']) ) {
	$sql = "DELETE FROM ostotilauskirja_tuote WHERE ostotilauskirja_id = ?";
	$db->query($sql, [$_POST['ostotilauskirja_id']]);
    if ( $db->query("DELETE FROM ostotilauskirja WHERE id = ?", [$_POST['ostotilauskirja_id']]) ) {
        $_SESSION["feedback"] = "<p class='success'>Ostotilauskirja poistettu.</p>";
    } else {
        $_SESSION["feedback"] = "<p class='error'>ERROR</p>";
    }
}

$feedback = check_feedback_POST();

// Haetaan ostotilauskirjat
$sql = "SELECT *, ostotilauskirja.id AS id, SUM(kpl*tuote.sisaanostohinta) AS hinta,
			COUNT(ostotilauskirja_tuote.tuote_id) AS kpl
		FROM ostotilauskirja
 		LEFT JOIN ostotilauskirja_tuote
 			ON ostotilauskirja.id = ostotilauskirja_tuote.ostotilauskirja_id
 		LEFT JOIN tuote
 		    ON ostotilauskirja_tuote.tuote_id = tuote.id
 		WHERE ostotilauskirja.hankintapaikka_id = ?
 		GROUP BY ostotilauskirja.id";
$ostotilauskirjat = $db->query($sql, [$hankintapaikka_id], FETCH_ALL);

$sql = "SELECT otk_a.id, tunniste, lahetetty, DATE_FORMAT(lahetetty, '%d.%m.%Y') AS lahetettyHieno, saapumispaiva,
				DATE_FORMAT(saapumispaiva, '%d.%m.%Y') AS saapumispaivaHieno, rahti,
  				
  				(SELECT IFNULL(SUM(otk_t_a.kpl*otk_t_a.ostohinta),0) FROM ostotilauskirja_tuote_arkisto otk_t_a
	  				WHERE otk_t_a.ostotilauskirja_id = otk_a.id)
			    AS hinta,
			    (SELECT COUNT(otk_t_a.tuote_id) FROM ostotilauskirja_tuote_arkisto otk_t_a 
			     	WHERE otk_t_a.ostotilauskirja_id = otk_a.id)
			    AS kpl
		FROM ostotilauskirja_arkisto otk_a
		WHERE otk_a.hankintapaikka_id = ? AND hyvaksytty = 1 
		ORDER BY saapumispaiva";
/** @var \Ostotilauskirja[] $otk_historia */
$otk_historia = $db->query( $sql, [$hankintapaikka_id],
                            FETCH_ALL, null, "Ostotilauskirja" );
?>

<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="css/jsmodal-light.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
<link rel="stylesheet" href="https://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
<script src="js/jsmodal-1.0d.min.js"></script>
<title>Ostotilauskirjat</title>
</head>
<body>
<?php require 'header.php'?>
<main class="main_body_container">

	<div class="otsikko_container">
		<section class="takaisin">
			<a href="yp_ostotilauskirja_hankintapaikka.php" class="nappi grey"><i class="material-icons">navigate_before</i>Takaisin</a>
		</section>
		<section class="otsikko">
			<span>Ostotilauskirja</span>
			<h1><?=$hankintapaikka->id?> - <?=$hankintapaikka->nimi?></h1>
		</section>
		<section class="napit">
			<button class="nappi" type="button" onclick="avaa_modal_uusi_ostotilauskirja('<?=$hankintapaikka_id?>')">
				Uusi ostotilauskirja</button>
		</section>
	</div>

    <section>
        <h4>Valitse ostotilauskirja:</h4>
    </section>

    <?= $feedback?>

    <?php if ( $ostotilauskirjat ) : ?>
        <table style="margin: auto; width: 90%;">
            <thead>
            <tr><th colspan="8" class="center" style="background-color:#1d7ae2;">OSTOTILAUSKIRJAT:</th></tr>
            <tr><th>Tunniste</th>
                <th>Toimitusväli</th>
                <th>Lähetyspäivä</th>
                <th>Saapumispäivä</th>
                <th class="number">Tuotteet</th>
                <th class="number">Hinta</th>
                <th class="number">Rahti</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ( $ostotilauskirjat as $otk ) : ?>
                <tr data-id="<?=$otk->id?>">
                    <td data-href="yp_ostotilauskirja_tuote.php?id=<?=$otk->id?>">
                        <?= $otk->tunniste?></td>
                    <td data-href="yp_ostotilauskirja_tuote.php?id=<?=$otk->id?>">
                        <?php if (!$otk->toimitusjakso) : ?>
                            ERIKOISTILAUS
                        <?php else : ?>
						    <?= $otk->toimitusjakso?> viikkoa
                        <?php endif;?>
                    </td>
                    <td data-href="yp_ostotilauskirja_tuote.php?id=<?=$otk->id?>">
						<?= date("d.m.Y", strtotime($otk->oletettu_lahetyspaiva))?></td>
                    <td data-href="yp_ostotilauskirja_tuote.php?id=<?=$otk->id?>">
                        <?= date("d.m.Y", strtotime($otk->oletettu_saapumispaiva))?></td>
                    <td class="number" data-href="yp_ostotilauskirja_tuote.php?id=<?=$otk->id?>">
                        <?= format_number($otk->kpl,0)?></td>
                    <td class="number" data-href="yp_ostotilauskirja_tuote.php?id=<?=$otk->id?>">
                        <?= format_number($otk->hinta)?></td>
                    <td class="number" data-href="yp_ostotilauskirja_tuote.php?id=<?=$otk->id?>">
                        <?= format_number($otk->rahti)?></td>
                    <td class="toiminnot">
                        <a class="nappi" href='javascript:void(0)'
                           onclick="avaa_modal_muokkaa_ostotilauskirja('<?=$otk->tunniste?>',
                                   '<?= date("Y-m-d", strtotime($otk->oletettu_lahetyspaiva))?>',
                                    '<?= date("Y-m-d", strtotime($otk->oletettu_saapumispaiva))?>',
                                    '<?= $otk->rahti?>', '<?= $otk->toimitusjakso?>', '<?= $otk->id?>')">
                                    Muokkaa</a>
                        <a class="nappi red" href='javascript:void(0)'
                           onclick="poista_ostotilauskirja('<?= $otk->id?>')">Poista</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p>Ei ostotilaukirjoja.</p>
    <?php endif; ?>

	<br><hr>

	<h2>Historia:</h2>
	<?php if ( $otk_historia ) : ?>
		<table id="otk_hist" style="width: 90%; margin: auto;">
			<thead>
			<tr><th colspan="7" class="center" style="background-color:#1d7ae2;">HISTORIA:</th></tr>
			<tr><th>Tunniste</th>
				<th>Lähetyspäivä</th>
				<th>Saapumispäivä</th>
				<th class="number">Tuotteet</th>
				<th class="number">Hinta</th>
				<th class="number">Rahti</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $otk_historia as $otk ) : ?>
				<tr data-id="<?=$otk->id?>" data-href="yp_ostotilauskirja_historia_tuotteet.php?id=<?=$otk->id?>">
					<td><?= $otk->tunniste ?></td>
					<td><?= $otk->lahetettyHieno ?></td>
					<td><?= $otk->saapumispaivaHieno ?></td>
					<td class="number"><?= format_number($otk->kpl, 0) ?></td>
					<td class="number"><?= format_number($otk->hinta) ?></td>
					<td class="number"><?= format_number($otk->rahti) ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p>Hankintapaikalla ei ole yhtään OTK:ta historiassa.</p>
	<?php endif; ?>
</main>

<?php require 'footer.php'; ?>

<script type="text/javascript">

	/**
	 * Modal ostotilauskirjan lisäämiseen.
	 * @param hankintapaikka_id
	 */
    function avaa_modal_uusi_ostotilauskirja( hankintapaikka_id ) {
        let date = new Date().toISOString().slice(0,10);
        Modal.open({
            content: '\
            <h4>Anna uuden ostotilauskirjan tiedot.</h4>\
            <br><br>\
                <form action="" method="post" name="uusi_ostotilauskirja">\
					<label>Tunniste</label>\
					<input name="tunniste" type="text" placeholder="Ostotilauskirjan nimi" pattern=".{3,}" required>\
					<br><br>\
					<label>Lähetyspäivä</label>\
					<input name="lahetyspvm" type="text" class="datepicker" value="'+date+'" title="Arvioitu saapumispäivä" required>\
					<br><br>\
					<label>Saapumispäivä</label>\
					<input name="saapumispvm" type="text" class="datepicker" value="'+date+'" title="Arvioitu saapumispäivä" required>\
					<br><br>\
					<label>Rahtimaksu (€)</label>\
					<input name="rahti" type="number" step="0.01" value="200.00" title="Rahtimaksu" required>\
					<br><br>\
					<label>Tilauksen tyyppi</label>\
                    <input type="radio" name="tyyppi" value="vakiotilaus" checked> Toistuva \
                    <input type="radio" name="tyyppi" value="erikoistilaus"> Erikoistilaus \
					<br><br>\
					<div id="toimitusjakso_div">\
					    <label>Tilausväli (vko)</label>\
					    <input name="toimitusjakso" id="toimitusjakso" type="number" step="1" min="1" placeholder="6" title="Tilausväli viikkoina" required>\
					    <br><br>\
					</div>\
					<input name="hankintapaikka_id" type="hidden" value="'+hankintapaikka_id+'">\
					<input class="nappi" type="submit" name="lisaa" value="Tallenna" id="lisaa_ostotilauskirja"> \
				</form>\
				',
            draggable: true
        });
    }

    /**
     * Modal tilauskirjan tietojen muokkaamiseen.
     * @param tunniste
     * @param lahetyspvm
     * @param saapumispvm
     * @param rahti
     * @param tilausjakso
     * @param ostotilauskirja_id
     */
    function avaa_modal_muokkaa_ostotilauskirja( tunniste, lahetyspvm, saapumispvm, rahti, tilausjakso, ostotilauskirja_id ) {
        let tilausjakso_string;
        if ( +tilausjakso ) {
            tilausjakso_string = '<input type="number" name="toimitusjakso" step="1" value="'+tilausjakso+'" min="1" placeholder="6" title="Tilausväli viikkoina" required>';
        } else {
            tilausjakso_string = '<input type=hidden name="toimitusjakso" step="1" value="'+tilausjakso+'">';
        	tilausjakso_string += "ERIKOISTILAUS";
        }
        Modal.open( {
            content:  '\
				<h4>Muokkaa ostitilauskirjan tietoja.</h4>\
				<hr>\
				<br>\
				<form action="" method="post" name="muokkaa_hankintapaikka">\
					<label>Tunniste</label>\
                    <h4 style="display: inline;">'+tunniste+'</h4>\
					<br><br>\
					<label>Lähetyspäivä</label>\
					<input name="lahetyspvm" type="text" class="datepicker" value="'+lahetyspvm+'" title="Arvioitu lähetyspäivä" required>\
					<br><br>\
                    <label>Saapumispäivä</label>\
					<input name="saapumispvm" type="text" class="datepicker" value="'+saapumispvm+'" title="Arvioitu saapumispäivä" required>\
					<br><br>\
					<label>Rahtimaksu (€)</label>\
					<input name="rahti" type="number" step="0.01" value="'+rahti+'" title="Rahtimaksu">\
					<br><br>\
					<label>Tilausväli (vko)</label>\
                    '+tilausjakso_string+'\
	                <br><br>\
					<input name="ostotilauskirja_id" type="hidden" value="'+ostotilauskirja_id+'">\
					<input class="nappi" type="submit" name="muokkaa" value="Muokkaa"> \
				</form>\
				',
            draggable: true
        });
    }

    /**
     * Tilauskirjan poistamista varten
     * @param ostotilauskirja_id
     */
    function poista_ostotilauskirja( ostotilauskirja_id ) {
    	let form, field;
        if ( confirm("Haluatko varmasti poistaa kyseisen ostotilauskirjan?") ) {
            //Rakennetaan form
            form = document.createElement("form");
            form.setAttribute("method", "POST");
            form.setAttribute("action", "");

            //asetetaan $_POST["poista"]
	        field = document.createElement("input");
            field.setAttribute("type", "hidden");
            field.setAttribute("name", "poista");
            field.setAttribute("value", "true");
            form.appendChild(field);

            field = document.createElement("input");
            field.setAttribute("type", "hidden");
            field.setAttribute("name", "ostotilauskirja_id");
            field.setAttribute("value", ostotilauskirja_id);
            form.appendChild(field);

            //form submit
            document.body.appendChild(form);
            form.submit();
        }
    }

    $(document).ready(function(){

        $('*[data-href]')
            .css('cursor', 'pointer')
            .click(function(){
                window.location = $(this).data('href');
                return false;
            });

	    // Ostotilauskirjan lisäys -modalin toiminta
		$(document.body).on('change', 'input[name="tyyppi"]:radio', function() {
			let toimitusjakso_div = $("#toimitusjakso_div");
			let toimitusjakso_input = $("#toimitusjakso");
			if (this.value === 'vakiotilaus') {
				toimitusjakso_input.prop('required', true);
				toimitusjakso_div.show();
			}
			else if (this.value === 'erikoistilaus') {
				toimitusjakso_input.prop('required', false);
				toimitusjakso_div.hide();
			}
		})
        .on('focus', ".datepicker", function () {
            $(this).datepicker({
            	dateFormat: 'yy-mm-dd',
				minDate: new Date(),
            })
				.keydown(function(e){
					e.preventDefault();
				});
		});

    });
</script>
</body>
</html>
