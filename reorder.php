<?php
include "pmms.php";

if (!(isset($_GET["room"]) && isset($_GET["id"]) && isset($_GET["direction"]))) {
	die();
}

session_start();

$room = $_GET["room"];
$queue_id = $_GET["id"];
$direction = $_GET["direction"];

$conn = create_db_connection();

if (can_control_room($conn, session_id(), $room)) {
	reorder_queue($conn, $room, $queue_id, $direction);
}

$conn->close();
?>
