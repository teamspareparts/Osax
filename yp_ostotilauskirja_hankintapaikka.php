<?php
require '_start.php'; global $db, $user, $cart;

if ( !$user->isAdmin() ) {
    header("Location:etusivu.php"); exit();
}

function hae_aktiiviset_hankintapaikat( DByhteys $db ) {
    $sql = "SELECT LPAD(hankintapaikka.id,3,'0') AS id, hankintapaikka.nimi, GROUP_CONCAT(valmistajan_hankintapaikka.brandName) AS brandit
            FROM hankintapaikka
            RIGHT JOIN valmistajan_hankintapaikka
              ON hankintapaikka.id = valmistajan_hankintapaikka.hankintapaikka_id
            GROUP BY hankintapaikka.id";
    return $db->query($sql, [], FETCH_ALL);
}


//Haetaan kaikki hankintapaikat, joihin linkitetty valmistaja
$hankintapaikat = hae_aktiiviset_hankintapaikat($db);

?>


<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
    <title>Ostotilauskirjat</title>
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container">
    <section>
        <h1 class="otsikko">Ostotilauskirjat</h1>
        <h4>Valitse hankintapaikka:</h4>
    </section>
        <?php if ( $hankintapaikat ) : ?>
        <table>
            <thead>
            <tr><th>ID</th>
                <th>Nimi</th>
                <th>Brandit</th>
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
                        <?php foreach ($hp->brandit as $b) : ?>
                            <span><?= $b?></span><br>
                        <?php endforeach;?>
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
