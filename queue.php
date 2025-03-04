<?php
include "pmms.php";

$room = $_GET["room"];
$limit = get_param("limit");

$conn = create_db_connection();

if (isset($limit)) {
	$stmt = $conn->prepare("SELECT queue.id AS id, queue.url AS url, queue.title AS title FROM room, queue WHERE room.id = queue.room_id AND room.room_key = ? LIMIT ?");
	$stmt->bind_param("si", $room, $limit);
} else {
	$stmt = $conn->prepare("SELECT queue.id AS id, queue.url AS url, queue.title AS title FROM room, queue WHERE room.id = queue.room_id AND room.room_key = ?");
	$stmt->bind_param("s", $room);
}
$stmt->execute();

$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
	$data[] = [
		"id" => $row["id"],
		"url" => $row["url"],
		"title" => $row["title"]
	];
}

$stmt->close();

$conn->close();

header("Content-type: application/json");
echo json_encode($data);

?>
