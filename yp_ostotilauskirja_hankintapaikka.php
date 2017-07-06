<?php
require '_start.php'; global $db, $user, $cart;
require 'apufunktiot.php';

if ( !$user->isAdmin() ) {
    header("Location:etusivu.php"); exit();
}

/**
 * Hakee kaikki aktiiviset hankintapaikat
 * @param DByhteys $db
 * @return array|int|stdClass
 */
function hae_aktiiviset_hankintapaikat( DByhteys $db ) {
	$sql = "SELECT LPAD(hankintapaikka.id,3,'0') AS id, hankintapaikka.nimi, GROUP_CONCAT(brandi.nimi) AS brandit
            FROM hankintapaikka
            INNER JOIN brandin_linkitys
              ON hankintapaikka.id = brandin_linkitys.hankintapaikka_id
            INNER JOIN brandi
            	ON brandin_linkitys.brandi_id = brandi.id
            GROUP BY hankintapaikka.id";
	return $db->query($sql, [], FETCH_ALL);
}

/**
 * Hakee hankintapaikkkojen ostotilauskirjat
 * @param DByhteys $db
 * @param array $hankintapaikat
 */
function hae_hankintapaikkojen_ostotilauskirjat( DByhteys $db, array $hankintapaikat ) {
	$sql = "SELECT *, ostotilauskirja.id AS id, IFNULL(SUM(kpl*tuote.sisaanostohinta),0) AS hinta, COUNT(ostotilauskirja_tuote.tuote_id) AS kpl FROM ostotilauskirja
 		LEFT JOIN ostotilauskirja_tuote
 			ON ostotilauskirja.id = ostotilauskirja_tuote.ostotilauskirja_id
 		LEFT JOIN tuote
 		    ON ostotilauskirja_tuote.tuote_id = tuote.id
 		WHERE ostotilauskirja.hankintapaikka_id = ?
 		GROUP BY ostotilauskirja.id";
	foreach ($hankintapaikat as $hp) {
		$hp->ostotilauskirjat = $db->query($sql, [$hp->id], FETCH_ALL);
	}
}


//Haetaan kaikki hankintapaikat, joihin linkitetty valmistaja
$hankintapaikat = hae_aktiiviset_hankintapaikat($db);
hae_hankintapaikkojen_ostotilauskirjat($db, $hankintapaikat);

?>


<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
    <title>Ostotilauskirjat</title>
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container">

	<div class="otsikko_container">
		<section class="takaisin">
		</section>
		<section class="otsikko">
			<h1>Ostotilauskirjat</h1>
			<span>&nbsp;&nbsp; Valitse alla olevalta listalta hankintapaikka</span>
		</section>
		<section class="napit">
		</section>
	</div>

    <section>
        <h2>Valitse hankintapaikka:</h2>
    </section>
    <?php if ( $hankintapaikat ) : ?>
    <table>
        <thead>
        <tr><th>ID</th>
            <th>Nimi</th>
            <th>Brandit</th>
			<th>Tilauskirjat</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ( $hankintapaikat as $hp ) :
            $hp->brandit = explode(",", $hp->brandit)
            ?>
            <tr data-href="yp_ostotilauskirja.php?id=<?= intval($hp->id)?>">
                <td><?= $hp->id?></td>
                <td><?= $hp->nimi?></td>
                <td>
                    <?php foreach ($hp->brandit as $brand) : ?>
                        <span><?= $brand?></span><br>
                    <?php endforeach;?>
                </td>
				<td>
					<?php foreach ($hp->ostotilauskirjat as $otk) : ?>
						<?= $otk->tunniste?> - <?= $otk->kpl?> - <?= format_euros($otk->hinta)?><br>
					<?php endforeach; ?>
				</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else : ?>
        <p>Ei hankintapaikkoja. Käy luomassa uusia hankintapaikkoja
        toimittajat -sivulla!</p>
    <?php endif; ?>
</main>

<?php require 'footer.php'; ?>

<script type="text/javascript">
    $(document).ready(function(){

        $('*[data-href]')
            .css('cursor', 'pointer')
            .click(function(){
                window.location = $(this).data('href');
                return false;
            });
    });

</script>
</body>
</html>
