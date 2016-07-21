<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/login_styles.css">
	<style type="text/css">
		.class #id tag {}
	    
	    #eula_body {
	    	display: flex;
	    	flex-direction: column;
		    margin: auto;
		    width: 90vw;
		    height: 100%;
			overflow: hidden;
	    }
	    
	    #headlines_container {
	    	display: flex;
	    	justify-content: space-around;
	    }
	    #headlines_text, #headlines_logo {
	    	margin: auto;
	    }
		
		
		#eula_textbox_container {
			width: 99%;
	    }
		#eula_scrollable_textbox {
		    overflow-y: scroll;
		    resize: vertical;
			width: 100%;
		    height: 200px;
	    }
	    
	    #eula_napit_container {
	    	display: flex;
	    	flex-direction: row;
	    	justify-content: space-around;
	    }
	    #hyvaksy_eula, #hylkaa_eula {
	    	flex: 1 1 auto;
	    	text-align: center;
	    }
	    .nappi, button, input {
		    padding: 8px 15px;
		    border-radius: 4px;
		    border: solid 1px;
		    transition-duration: 0.2s;
			font-weight: 700;
			font-size: 15px;
			text-transform: uppercase;
			font-family: 'Open Sans', sans-serif;
	    }
	    #hyvaksy_nappi { background-color: lightgreen; }
	    #hylkaa_nappi { background-color: lightpink; }
	</style>
	<title>Käyttöoikeussopimus</title>
</head>
<body>

<?php
require 'tietokanta.php';

session_start();

if ( empty($_SESSION['email']) ) {
	header('Location: index.php?redir=5');
	exit;
}

$user_id = $_SESSION['id'];

if ( !empty($_POST['vahvista_eula']) ) {
	$sql_query = "	UPDATE	kayttaja
					SET		vahvista_eula = '0'
					WHERE	id = '$user_id';";
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
}

$txt_tiedosto = __DIR__.'/eula.txt';
$eula_txt = file_get_contents( $txt_tiedosto, false, NULL, 0 );

?>


<div id="eula_body">
	<div id="headlines_container">
		<div id="headlines_text">
			<h1>Käyttöoikeussopimus</h1>
			<h4>Ole hyvä ja hyväksy käyttöoikeussopimus ennen sivuston käyttöä.</h4>
			<span> (Tällä sivulla ei ole enää mitään Admin-hallintaa) </span>
		</div>
		<div id="headlines_logo">
			<h1>LOGO</h1>
		</div>
	</div>
	<div id="eula_textbox_container">
		<textarea id="eula_scrollable_textbox" ReadOnly Multiline>
			<?php echo $eula_txt; ?>
		</textarea><br>
	</div>
	<div id="eula_napit_container" style>
		<span id="hyvaksy_eula"><button class="nappi" id="hyvaksy_nappi" onClick="hyvaksy_eula();">Hyväksy</button></span>
		<span id="hylkaa_eula"><button class="nappi" id="hylkaa_nappi" onClick="hylkaa_eula();">Hylkää</button><br></span>
	</div>
</div>


<form style="display:none;" id="vahvista_eula_form" action="#" method=post>
	<input type=hidden name="vahvista_eula" value="<?= $user_id ?>"> 
	<?php /** Tämä user_id ei täytä muuta virkaa kuin arvon täytteenä, 
			* jotta postaus menisi empty()-tarkistuksesta läpi. Siten se ei ole riski turvallisuudelle. */?>
</form>

<script>
function hyvaksy_eula () {
	var form_ID = "vahvista_eula_form";
	var vahvistus = confirm( "Oletko varma, että haluat vahvistaa EULA:n?\n"
							+ "Tätä toimintoa ei voi perua jälkeenpäin!\n"
							+ "Jos painat OK, yhtikäs mitään ei muutu, ja maailma jatkuu pyörimistään.\n"
							+ "(Huom. Hylkää-napilla olisi ollut aivan sama lopputulos.)");
	if ( vahvistus == true ) {
		document.getElementById(form_ID).submit();
	} else {
		return false;
	}
}

function hylkaa_eula () {
	var vahvistus = alert( "Mitä oikein odotit tapahtuvan?\n"
							+ "Ole hyvä, ja lopeta sivuston käyttö välittömästi.\n"
							+ "Sen jälkeen, ilmoita ylläpitäjälle halustasi sulkea tilisi.\n"
							+ "Kiitos yhteistyöstäsi.");
	window.location="./logout.php?redir=10";
}
</script>

</body>
</html>