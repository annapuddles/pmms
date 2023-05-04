<?php
include "pmms.php";

$conn = create_db_connection();

$order_by = "sort_title";

if (isset($_GET["sort"])) {
	switch ($_GET["sort"]) {
		case "new":
			$order_by = "id DESC";
			break;
	}
}

if (isset($_GET["query"])) {
	$query = implode("* ", explode(" ", $_GET["query"])) . "*";

	if (isset($_GET["series"])) {
		$stmt = $conn->prepare("SELECT id, url, title, cover, MATCH (sort_title, keywords) AGAINST (? IN BOOLEAN MODE) AS relevance, LENGTH(sort_title) AS length FROM catalog WHERE series = ? AND MATCH (sort_title, keywords) AGAINST (? IN BOOLEAN MODE) AND hidden = FALSE ORDER BY relevance DESC, length, $order_by");
		$stmt->bind_param("sis", $query, $_GET["series"], $query);
	} else {
		if (isset($_GET["category"])) {
			$stmt = $conn->prepare("SELECT id, url, title, cover, MATCH (sort_title, keywords) AGAINST (? IN BOOLEAN MODE) AS relevance, LENGTH(sort_title) AS length FROM catalog WHERE category = ? AND MATCH (sort_title, keywords) AGAINST (? IN BOOLEAN MODE) AND hidden = FALSE ORDER BY relevance DESC, length, $order_by");
			$stmt->bind_param("sss", $query, $_GET["category"], $query);
		} else {
			$stmt = $conn->prepare("SELECT id, url, title, cover, MATCH (sort_title, keywords) AGAINST (? IN BOOLEAN MODE) AS relevance, LENGTH(sort_title) AS length FROM catalog WHERE MATCH (sort_title, keywords) AGAINST (? IN BOOLEAN MODE) AND hidden = FALSE ORDER BY relevance DESC, length, $order_by");
			$stmt->bind_param("ss", $query, $query);
		}
	}
} else {
	if (isset($_GET["series"])) {
		$stmt = $conn->prepare("SELECT id, url, title, cover FROM catalog WHERE series = ? AND hidden = FALSE ORDER BY $order_by");
		$stmt->bind_param("i", $_GET["series"]);
	} else {
		if (isset($_GET["category"])) {
			if (isset($_GET["genre"])) {
				$stmt = $conn->prepare("SELECT id, url, title, cover FROM catalog_with_genre WHERE series IS NULL AND category = ? AND genre = ? AND hidden = FALSE ORDER BY $order_by");
				$stmt->bind_param("ss", $_GET["category"], $_GET["genre"]);
			} else {
				$stmt = $conn->prepare("SELECT id, url, title, cover FROM catalog WHERE series IS NULL AND category = ? AND hidden = FALSE ORDER BY $order_by");
				$stmt->bind_param("s", $_GET["category"]);
			}
		} else {
			if (isset($_GET["genre"])) {
				$stmt = $conn->prepare("SELECT id, url, title, cover FROM catalog_with_genre WHERE series IS NULL AND genre = ? AND hidden = FALSE ORDER BY $order_by");
				$stmt->bind_param("s", $_GET["genre"]);
			} else {
				$stmt = $conn->prepare("SELECT id, url, title, cover FROM catalog WHERE series IS NULL AND hidden = FALSE ORDER BY $order_by");
			}
		}
	}
}

$stmt->execute();

$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
	$data[] = [
		"id" => $row["id"],
		"url" => $row["url"],
		"title" => $row["title"],
		"cover" => $row["cover"]
	];
}

$stmt->close();
$conn->close();

header("Content-type: application/json");
echo json_encode($data);
?>
