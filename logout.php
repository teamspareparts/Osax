<?php
	session_start();
	session_destroy();
	header("location:index.php?redir=" . $_GET['redir'])
?>