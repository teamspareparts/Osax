<?php declare(strict_types=1);
session_start();
session_destroy();
header( "location:index.php?redir=" . ($_GET['redir'] ?? "5") );
exit();
