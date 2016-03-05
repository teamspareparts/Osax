<?php
	session_start();
	session_destroy();
	header("location:login.php?redir=6")
?>