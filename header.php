<div class="header_container">
    <section class="header_top">
        <div id="head_logo">
            <img src="img/osax_logo.jpg" align="left" alt="No pics, plz">
        </div>

        <div id="head_info">
            Tervetuloa takaisin, <?= $user->kokoNimi() ?><br>
            Kirjautuneena: <?= $user->sahkoposti ?>
        </div>

        <div id="head_cart">
            <a href='ostoskori.php' class="flex_row">
                <div style="margin:auto 5px;">
                    <i class="material-icons">shopping_cart</i>
                </div>
                <div>
                    Ostoskori<br>
                    Tuotteita: <span id="head_cart_tuotteet"><?= $cart->montako_tuotetta ?></span>
                    (Kpl:<span id="head_cart_kpl"><?= $cart->montako_tuotetta_kpl_maara_yhteensa ?></span>)
                </div>
            </a>
        </div>
    </section>

    <section id="navigationbar">
        <ul>
            <li><a href='etusivu.php'><span style="padding: 15px 20px;"><i class="material-icons">home</i></span></a></li>
            <li><a href='tuotehaku.php'><span>Tuotehaku</span></a></li>
            <?php if ( $user->isAdmin() ) : ?>
                <li><a href='yp_yritykset.php'><span>Yritykset</span></a></li>
                <li><a href='yp_tuotteet.php'><span>Tuotteet</span></a></li>
                <li><a href='yp_tilaukset.php'><span>Tilaukset</span></a></li>

                <li class="dropdown"><span>Muut<i id="dropdown_icon" class="material-icons">arrow_drop_down</i></span>
                    <ul class="dropdown-content">
                        <li><a href="yp_ostotilauskirja_hankintapaikka.php"><span>Tilauskirjat</span></a></li>
                        <li><a href="yp_hallitse_eula.php"><span>EULA</span></a></li>
                        <li><a href="yp_hankintapyynnot.php"><span>Hankintapyynnöt</span></a></li>
                        <li><a href="yp_muokkaa_alv.php"><span>ALV-muokkaus</span></a></li>
                        <li><a href="yp_luo_hinnastotiedosto.php"><span>Lataa hinnastot</span></a></li>
                        <li><a href='toimittajat.php'><span>Toimittajat</span></a></li>
                        <li><a href='omat_tiedot.php'><span>Omat tiedot</span></a></li>
                    </ul>
                </li>
			<?php else : ?>
                <li><a href='omat_tiedot.php'><span>Omat tiedot</span></a></li>
                <li><a href='tilaushistoria.php'><span>Tilaushistoria</span></a></li>
			<?php endif; ?>
            <li class="last"><a href="logout.php?redir=5"><span>Kirjaudu ulos</span></a></li>
        </ul>
    </section>
</div>


<script type="text/javascript">
    $(".dropdown").click(function () {
        var dropdown_icon = $("#dropdown_icon");
        if ( dropdown_icon.text() === "arrow_drop_down" ){
            dropdown_icon.text("arrow_drop_up");
            $(".dropdown-content").show();

        } else {
            dropdown_icon.text("arrow_drop_down");
            $(".dropdown-content").hide();
        }
    });
</script>
