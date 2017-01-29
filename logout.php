<?php
	session_start();
	session_destroy();
	header( "location:index.php?redir=" . ($_GET['redir'] ? $_GET['redir'] : "5") );
	exit();
