<!DOCTYPE html>
<html lang="fi">
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
    <title>Yritykset</title>
</head>
<body>
<?php
require 'header.php';
require 'tietokanta.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!is_admin() || !$id) {
    header("Location:yp_yritykset.php");
    exit();
}

//Päivitetäänkö yrityksen tiedot
$result = null;
if (isset($_POST['email'])) {
    $result = db_muokkaa_yritysta($_POST['nimi'], $_POST['y_tunnus'], $_POST['email'], $_POST['puh'],
        $_POST['osoite'], $_POST['postinumero'], $_POST['postitoimipaikka'], $_POST['maa']);
}


//Haetaan yrityksen tiedot
$query = "	SELECT *
			FROM yritys 
			WHERE id= ? ";

$yritys = $db->query( $query, [$id], FETCH_ALL, PDO::FETCH_OBJ );
if (count($yritys) == 1) {
    $yritys = $yritys[0];
}
else{   //JOS id:llä ei löydy tietokannasta ohjataan takaisin
    header("Location:yp_yritykset.php");
    exit();
}
?>

<h1 class="otsikko">Muokkaa yritystä</h1>
<br><br>
<div id="lomake">
    <form action="" name="muokkaa_yritysta" method="post" accept-charset="utf-8">
        <fieldset><legend>Muokkaa yrityksen tietoja</legend>
            <br>
            <label><span>Yritys</span></label>
            <p style="display: inline; font-size: 16px; font-weight: bold"><?= $yritys->nimi?></p>
            <br><br>
            <label><span>Y-tunnus</span></label>
            <p style="display: inline; font-size: 16px; font-weight: bold"><?= $yritys->y_tunnus?></p>
            <br><br>
            <label><span>Sähköposti</span></label>
            <input name="email" type="email" value="<?= $yritys->sahkoposti?>" />
            <br><br>
            <label><span>Puhelin</span></label>
            <input name="puh" type="text" pattern=".{4,20}" value="<?= $yritys->puhelin?>" />
            <br><br>
            <label><span>Katuosoite</span></label>
            <input name="osoite" type="text" pattern=".{1,50}" value="<?= $yritys->katuosoite?>" />
            <br><br>
            <label><span>Postinumero</span></label>
            <input name="postinumero" type="text" value="<?= $yritys->postinumero?>" />
            <br><br>
            <label><span>Postitoimipaikka</span></label>
            <input name="postitoimipaikka" type="text" value="<?= $yritys->postitoimipaikka?>" />
            <br><br>
            <label><span>Maa</span></label>
            <input name="maa" type="text" value="<?= $yritys->maa?>" />
            <input name="nimi" type="hidden" value="<?= $yritys->nimi?>" />
            <input name="y_tunnus" type="hidden" value="<?= $yritys->y_tunnus?>" />

            <br><br><br>

            <div id="submit">
                <input name="submit" value="Tallenna" type="submit">
            </div>
        </fieldset>

    </form><br><br>

    <?php

    if ($result) {
        echo "Tiedot päivitetty.";
    }



    function db_muokkaa_yritysta($yritys_nimi, $y_tunnus, $email,
                             $puh, $osoite, $postinumero, $postitoimipaikka, $maa){

        global $db;
        $tbl_name = 'yritys';

        //Tarkastetaan onko samannimistä käyttäjätunnusta
        $query = "UPDATE $tbl_name 
                  SET sahkoposti= ?, puhelin = ?, katuosoite = ?, 
                      postinumero = ?, postitoimipaikka= ?, maa = ?
                  WHERE y_tunnus= ? AND nimi= ?";
        return $db->query($query, [$email, $puh, $osoite, $postinumero, $postitoimipaikka, $maa, $y_tunnus, $yritys_nimi]);


    }
    ?>
</div>
</body>
</html>
