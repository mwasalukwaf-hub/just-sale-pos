<?php
// server_files/logout.php
session_start();
session_destroy();
header("Location: login");
exit;
