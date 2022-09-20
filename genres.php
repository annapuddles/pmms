<?php
include "pmms.php";

$conn = create_db_connection();

$stmt = $conn->prepare("SELECT name FROM genre ORDER BY name");
$stmt->execute();

$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
	$data[] = $row["name"];
}

$stmt->close();

$conn->close();

header("Content-type: application/json");
echo json_encode($data);
?>
