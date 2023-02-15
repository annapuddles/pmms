<?php
include "pmms.php";

$room = $_GET["room"];

$conn = create_db_connection();

$stmt = $conn->prepare("SELECT captions_name, captions_url FROM room JOIN captions ON room.url = captions.url WHERE room_key = ? ORDER BY captions_name");
$stmt->bind_param("s", $room);
$stmt->execute();

$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
	$data[] = [
		"name" => $row["captions_name"],
		"url" => $row["captions_url"]
	];
}

$stmt->close();

$conn->close();

header("Content-type: application/json");
echo json_encode($data);

?>
