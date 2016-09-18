<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
    <title>Yritykset</title>
</head>
<body>
<?php
require 'header.php';
require 'tietokanta.php';
if (!is_admin()) {
    header("Location:tuotehaku.php");
    exit();
}

?>
<h1 class="otsikko">Lisää yritys</h1>
<br><br>
<div id="lomake">
    <form action="yp_lisaa_yritys.php" name="uusi_yritys" method="post" accept-charset="utf-8">
        <fieldset><legend>Uuden yrityksen tiedot</legend>
            <br>
            <label><span>Yritys<span class="required">*</span></span></label>
            <input name="nimi" type="text" pattern="{3,40}" placeholder="Yritys Oy" required="required" />
            <br><br>
            <label><span>Y-tunnus<span class="required">*</span></span></label>
            <input name="y_tunnus" type="text" pattern=".{9}" placeholder="1234567-8" required="required" />
            <br><br>
            <label><span>Sähköposti</span></label>
            <input name="email" type="email" placeholder="email@email.com" />
            <br><br>
            <label><span>Puhelin</span></label>
            <input name="puh" type="text" pattern=".{4,20}" placeholder="050 123 4567" />
            <br><br>
            <label><span>Katuosoite</span></label>
            <input name="osoite" type="text" pattern=".{1,50}" placeholder="Katuosoite 1" />
            <br><br>
            <label><span>Postinumero</span></label>
            <input name="postinumero" type="text" placeholder="10100" />
            <br><br>
            <label><span>Postitoimipaikka</span></label>
            <input name="postitoimipaikka" type="text" placeholder="HELSINKI" />
            <br><br>
            <label><span>Maa</span></label>
            <input name="maa" type="text" placeholder="FI" >

            <br><br><br>

            <div id="submit">
                <input name="submit" value="Lisää Yritys" type="submit">
            </div>
        </fieldset>

    </form><br><br>

    <?php

    if (isset($_POST['nimi'])) {
        $result = db_lisaa_yritys($_POST['nimi'], $_POST['y_tunnus'], $_POST['email'], $_POST['puh'],
            $_POST['osoite'], $_POST['postinumero'], $_POST['postitoimipaikka'], $_POST['maa']);
        if ($result == -1) {
            echo "Yritys on jo olemassa.";
        } elseif ($result == 2) {
            echo "Yritys aktivoitu.";
        } else {
            echo "Yritys lisätty.";
        }
    }


    //return:
    //-1	yritys on jo olemassa
    //1		lisäys onnistui
    //2		yritys aktivoitu uudelleen
    function db_lisaa_yritys($yritys_nimi, $y_tunnus, $email,
                              $puh, $osoite, $postinumero, $postitoimipaikka, $maa){


        global $db;
        $tbl_name = 'yritys';

        //Tarkastetaan onko samannimistä yritystä
        $query = "SELECT * FROM $tbl_name WHERE y_tunnus= ? OR nimi= ? ";
        $result = $db->query($query, [$y_tunnus, $yritys_nimi], FETCH_ALL, PDO::FETCH_OBJ);

        if (count($result) == 0){
            //lisätään tietokantaan
            $query = "INSERT INTO $tbl_name (nimi, y_tunnus, sahkoposti, puhelin, katuosoite, postinumero, postitoimipaikka, maa)
		  				VALUES ( ?, ?, ?, ?, ?, ?, ?, ?)";
            $result = $db->query($query, [$yritys_nimi, $y_tunnus, $email, $puh, $osoite, $postinumero, $postitoimipaikka, $maa]);
            return 1;	//yritys lisätty
        }
        elseif (count($result) == 1) {
            if ($result[0]->aktiivinen == 1) {
                return -1;  //duplikaatti
            }
            else {
                $query = "UPDATE  $tbl_name 
		  				  SET     aktiivinen=1, nimi= ?, y_tunnus=?, sahkoposti=? , puhelin=? ,
	  						      katuosoite=? , postinumero=? , postitoimipaikka=? , maa=? 
  						  WHERE   y_tunnus=? OR nimi=? ";
                $result = $db->query( $query, [$yritys_nimi, $y_tunnus, $email, $puh, $osoite, $postinumero, $postitoimipaikka, $maa, $y_tunnus, $yritys_nimi]);
                return 2;	//yritys aktivoitu
            }
        }
        else {
            //JOS tietokannassa on duplikaatteja...
            echo "ERROR";
        }
	//Luodaan yritykselle ostoskori
	$db->query( "INSERT INTO ostoskori (yritys_id) SELECT id FROM yritys WHERE nimi = ?", [$yritys_nimi]);

    }
    ?>
</div>
</body>
</html>
