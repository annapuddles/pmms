<?php
include "pmms.php";

$conn = create_db_connection();

if (isset($_GET["family"])) {
	$catalog_table = "family_catalog";
	$catalog_with_genre_table = "family_catalog_with_genre";
} else {
	$catalog_table = "catalog";
	$catalog_with_genre_table = "catalog_with_genre";
}

if (isset($_GET["order"])) {
	switch ($_GET["order"]) {
		case "latest":
			$order = "id DESC";
			break;
		default:
			$order = "sort_title";
			break;
	}
} else {
	$order = "sort_title";
}

if (isset($_GET["query"])) {
	$query = implode("* ", explode(" ", $_GET["query"])) . "*";

	if (isset($_GET["series"])) {
		$stmt = $conn->prepare("SELECT id, url, title, cover, MATCH (sort_title, keywords) AGAINST (? IN BOOLEAN MODE) AS relevance, LENGTH(sort_title) AS length FROM $catalog_table WHERE series = ? AND MATCH (sort_title, keywords) AGAINST (? IN BOOLEAN MODE) AND hidden = FALSE ORDER BY relevance DESC, length, sort_title");
		$stmt->bind_param("sis", $query, $_GET["series"], $query);
	} else {
		if (isset($_GET["category"])) {
			$stmt = $conn->prepare("SELECT id, url, title, cover, MATCH (sort_title, keywords) AGAINST (? IN BOOLEAN MODE) AS relevance, LENGTH(sort_title) AS length FROM $catalog_table WHERE category = ? AND MATCH (sort_title, keywords) AGAINST (? IN BOOLEAN MODE) AND hidden = FALSE ORDER BY relevance DESC, length, sort_title");
			$stmt->bind_param("sss", $query, $_GET["category"], $query);
		} else {
			$stmt = $conn->prepare("SELECT id, url, title, cover, MATCH (sort_title, keywords) AGAINST (? IN BOOLEAN MODE) AS relevance, LENGTH(sort_title) AS length FROM $catalog_table WHERE MATCH (sort_title, keywords) AGAINST (? IN BOOLEAN MODE) AND hidden = FALSE ORDER BY relevance DESC, length, sort_title");
			$stmt->bind_param("ss", $query, $query);
		}
	}
} else {
	if (isset($_GET["series"])) {
		$stmt = $conn->prepare("SELECT id, url, title, cover FROM $catalog_table WHERE series = ? AND hidden = FALSE ORDER BY $order");
		$stmt->bind_param("i", $_GET["series"]);
	} else {
		if (isset($_GET["category"])) {
			if (isset($_GET["genre"])) {
				$stmt = $conn->prepare("SELECT id, url, title, cover FROM $catalog_with_genre_table WHERE series IS NULL AND category = ? AND genre = ? AND hidden = FALSE ORDER BY $order");
				$stmt->bind_param("ss", $_GET["category"], $_GET["genre"]);
			} else {
				$stmt = $conn->prepare("SELECT id, url, title, cover FROM $catalog_table WHERE series IS NULL AND category = ? AND hidden = FALSE ORDER BY $order");
				$stmt->bind_param("s", $_GET["category"]);
			}
		} else {
			if (isset($_GET["genre"])) {
				$stmt = $conn->prepare("SELECT id, url, title, cover FROM $catalog_with_genre_table WHERE series IS NULL AND genre = ? AND hidden = FALSE ORDER BY $order");
				$stmt->bind_param("s", $_GET["genre"]);
			} else {
				$stmt = $conn->prepare("SELECT id, url, title, cover FROM $catalog_table WHERE series IS NULL AND hidden = FALSE ORDER BY $order");
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
