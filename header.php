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

    <section class="navigationbar">
        <ul>
            <li><a href='etusivu.php' style="padding-left:16px; padding-right: 0;">
                <i class="material-icons">home</i></a></li>
            <li><a href='tuotehaku.php'>Tuotehaku</a></li>
            <?php if ( $user->isAdmin() ) : ?>
                <li><a href='yp_yritykset.php'>Yritykset</a></li>
                <li><a href='yp_tuotteet.php'>Tuotteet</a></li>
                <li><a href='yp_tilaukset.php'>Tilaukset</a></li>

                <li class="dropdown"><a href="javascript:void(0)">Muut<i id="dropdown_icon" class="material-icons">arrow_drop_down</i></a>
                    <ul class="dropdown-content">
                        <li><a href="yp_ostotilauskirja_odottavat.php">Varastoon saapuminen</a></li>
                        <li><a href="yp_ostotilauskirja_hankintapaikka.php">Tilauskirjat</a></li>
                        <li><a href="yp_hallitse_eula.php">EULA</a></li>
                        <li><a href="yp_hankintapyynnot.php">Hankintapyynnöt</a></li>
                        <li><a href="yp_muokkaa_alv.php">ALV-muokkaus</a></li>
                        <li><a href='toimittajat.php'>Toimittajat</a></li>
                        <li><a href='yp_raportit.php'>Raportit</a></li>
                        <li><a href='omat_tiedot.php'>Omat tiedot</a></li>
                    </ul>
                </li>
			<?php else : ?>
                <li><a href='omat_tiedot.php'>Omat tiedot</a></li>
                <li><a href='tilaushistoria.php'>Tilaushistoria</a></li>
                <li><a href="yp_luo_hinnastotiedosto.php">Lataa Hinnasto</a></li>
			<?php endif; ?>
            <li class="last"><a href="logout.php?redir=5">Kirjaudu ulos</a></li>
        </ul>
    </section>
</div>


<script type="text/javascript">
    //navbar active link
    let pgurl = window.location.href.substr(window.location.href
            .lastIndexOf("/")+1).split('?')[0];
	//Tarkastetaan alasivut
	switch(pgurl) {
		case "yp_muokkaa_yritysta.php":
		case "yp_lisaa_yritys.php":
		case "yp_asiakkaat.php":
		case "yp_muokkaa_asiakasta.php":
		case "yp_lisaa_asiakas.php":
			pgurl = "yp_yritykset.php";
			break;
		case "yp_tilaushistoria.php":
		case "tilaus_info.php":
			pgurl = "yp_tilaukset.php";
			break;
		case "toimittajan_hallinta.php":
		case "yp_lisaa_tuotteita.php":
		case "yp_valikoima.php":
			pgurl = "toimittajat.php";
			break;
		case "yp_ostotilauskirja.php":
		case "yp_ostotilauskirja_tuote.php":
			pgurl = "yp_ostotilauskirja_hankintapaikka.php";
			break;
		case "yp_ostotilauskirja_tuote_odottavat.php":
			pgurl = "yp_ostotilauskirja_odottavat.php";
			break;
		case "yp_varastolistausraportti.php":
        case "yp_myyntiraportti.php":
			pgurl = "yp_raportit.php";
			break;
	}

    $(".navigationbar a").each(function(){
        if ( $(this).attr("href") == pgurl ) {
            $(this).addClass("active");
            //Jos dropdpdown valikko, myös "MUUT"-painike active
			if ($("ul li ul li").has(this).length) {
				$(".dropdown > a").addClass("active");
			}
        }
    });

    //dropdown icon toiminnallisuus
    $(".dropdown a").click(function () {
        const dropdown_icon = $("#dropdown_icon");
        if ( dropdown_icon.text() === "arrow_drop_down" ){
            dropdown_icon.text("arrow_drop_up");
            $(".dropdown-content").show();
        } else {
            dropdown_icon.text("arrow_drop_down");
            $(".dropdown-content").hide();
        }
    });

</script>
