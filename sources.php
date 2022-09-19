<?php
include "pmms.php";

$room = $_GET["room"];

$conn = create_db_connection();

$stmt = $conn->prepare("SELECT source_name FROM room JOIN source ON room.url = source.url WHERE room_key = ? ORDER BY source_name");
$stmt->bind_param("s", $room);
$stmt->execute();

$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
	$data[] = $row["source_name"];
}

$stmt->close();

$conn->close();

header("Content-type: application/json");
echo json_encode($data);

?>
