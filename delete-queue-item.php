<?php
include "pmms.php";

session_start();

$conn = create_db_connection();

if (isset($_GET["id"]) && can_control_room($conn, session_id(), $room)) {
	$stmt = $conn->prepare("DELETE FROM queue WHERE id = ?");
	$stmt->bind_param("i", $_GET["id"]);
	$stmt->execute();
	$stmt->close();
}

$conn->close();

?>
