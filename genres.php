<?php
include "pmms.php";

$conn = create_db_connection();

if (isset($_GET["family"])) {
	$catalog_with_genre_table = "family_catalog_with_genre";
} else {
	$catalog_with_genre_table = "catalog_with_genre";
}

if (isset($_GET["category"])) {
	$stmt = $conn->prepare("SELECT DISTINCT genre AS name FROM $catalog_with_genre_table WHERE series IS NULL AND category = ? ORDER BY name");
	$stmt->bind_param("s", $_GET["category"]);
} else {
	$stmt = $conn->prepare("SELECT DISTINCT genre AS name FROM $catalog_with_genre_table WHERE series IS NULL ORDER BY name");
}
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
