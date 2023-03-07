<?php
include "pmms.php";

$conn = create_db_connection();

header("Content-type: application/json");

if (isset($_GET["url"]) && is_url_allowed($conn, $_GET["url"])) {
	echo 'true';
} else {
	echo 'false';
}

$conn->close();
?>
