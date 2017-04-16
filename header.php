<noscript>
    <meta http-equiv="refresh" content="0; url=index.php">
</noscript>

<!-- Tiedoston latausta varten -->
<form id="download_hinnasto" method="post" action="download.php">
    <input type="hidden" name="filepath" value="hinnasto/hinnasto.txt">
</form>

<header class="header_container">
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
                <i class="material-icons" style="margin-top: -3px;">home</i></a></li>
            <li><a href='tuotehaku.php'>Tuotehaku</a></li>
            <?php if ( $user->isAdmin() ) : ?>
                <li><a href='yp_yritykset.php'>Yritykset</a></li>
                <li><a href='yp_tuotteet.php'>Tuotteet</a></li>
                <li><a href='yp_tilaukset.php'>Tilaukset</a></li>

                <li><a id="dropdown_link" href="javascript:void(0)">Muut
                        <i id="dropdown_icon" class="material-icons" style="font-size: 18px;">arrow_drop_down</i></a>
                    <ul class="dropdown-content" id="navbar_dropdown-content">
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
                <li><a href='#' onclick="document.getElementById('download_hinnasto').submit()">Lataa hinnasto</a></li>
			<?php endif; ?>
            <li class="last"><a href="logout.php?redir=5">Kirjaudu ulos</a></li>
        </ul>
    </section>
</header>


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

    let links = document.getElementsByClassName("navigationbar")[0].getElementsByTagName("a");
    for ( i = 0; i < links.length; i++ ) {
        if ( links[i].getAttribute("href") === pgurl ) {
            links[i].className += "active";
            //Jos dropdpdown valikko, myös "MUUT"-painike active
            if ( links[i].parentElement.parentElement.className === "dropdown-content") {
                document.getElementById("dropdown_link").className += "active";
            }
        }
    }

    //dropdown icon toiminnallisuus
    document.getElementById("dropdown_link").onclick = function () {
        const dropdown_icon = document.getElementById("dropdown_icon");
        const dropdown_content = document.getElementById("navbar_dropdown-content");
        if ( dropdown_icon.innerHTML === "arrow_drop_down" ){
            dropdown_icon.innerHTML = "arrow_drop_up";
            dropdown_content.style.display = 'block';
        } else {
            dropdown_icon.innerHTML = "arrow_drop_down";
            dropdown_content.style.display = 'none';
        }
    };


</script>
