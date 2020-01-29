<?php

function buildThreadsJSON() {
	$output = '';

	$threads = allThreads();
	$pages = ceil(count($threads) / TINYIB_THREADSPERPAGE) - 1;

	foreach ($threads as $thread) {
		$replies = postsInThreadByID($thread['id']);

		$output .= $thread['id'] . "\n";
	}

	return $output;
}