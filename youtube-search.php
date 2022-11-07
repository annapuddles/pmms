<?php
include "pmms.php";

if (isset($_GET["query"])) {
	$videos = get_youtube_search_results($_GET["query"]);
} else {
	$videos = get_youtube_search_results();
}

echo json_encode($videos);
?>
