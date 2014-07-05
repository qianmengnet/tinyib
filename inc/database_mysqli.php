<?php
if (!defined('TINYIB_BOARD')) {
	die('');
}

if (!function_exists('mysqli_connect')) {
	fancyDie("MySQL library is not installed");
}

$link = @mysqli_connect(TINYIB_DBHOST, TINYIB_DBUSERNAME, TINYIB_DBPASSWORD);
if (!$link) {
	fancyDie("Could not connect to database: " . ((is_object($link)) ? mysqli_error($link) : (($link_error = mysqli_connect_error()) ? $link_error : '(unknown error)')));
}
$db_selected = @mysqli_query($link, "USE " . constant('TINYIB_DBNAME'));
if (!$db_selected) {
	fancyDie("Could not select database: " . ((is_object($link)) ? mysqli_error($link) : (($link_error = mysqli_connect_error()) ? $link_error : '(unknown error')));
}

// Create the posts table if it does not exist
if (mysqli_num_rows(mysqli_query($link, "SHOW TABLES LIKE '" . TINYIB_DBPOSTS . "'")) == 0) {
	mysqli_query($link, "CREATE TABLE `" . TINYIB_DBPOSTS . "` (
		`id` mediumint(7) unsigned NOT NULL auto_increment,
		`parent` mediumint(7) unsigned NOT NULL,
		`timestamp` int(20) NOT NULL,
		`bumped` int(20) NOT NULL,
		`ip` varchar(15) NOT NULL,
		`name` varchar(75) NOT NULL,
		`tripcode` varchar(10) NOT NULL,
		`email` varchar(75) NOT NULL,
		`nameblock` varchar(255) NOT NULL,
		`subject` varchar(75) NOT NULL,
		`message` text NOT NULL,
		`password` varchar(255) NOT NULL,
		`file` varchar(75) NOT NULL,
		`file_hex` varchar(75) NOT NULL,
		`file_original` varchar(255) NOT NULL,
		`file_size` int(20) unsigned NOT NULL default '0',
		`file_size_formatted` varchar(75) NOT NULL,
		`image_width` smallint(5) unsigned NOT NULL default '0',
		`image_height` smallint(5) unsigned NOT NULL default '0',
		`thumb` varchar(255) NOT NULL,
		`thumb_width` smallint(5) unsigned NOT NULL default '0',
		`thumb_height` smallint(5) unsigned NOT NULL default '0',
		PRIMARY KEY	(`id`),
		KEY `parent` (`parent`),
		KEY `bumped` (`bumped`)
	) ENGINE=MyISAM");
}

// Create the bans table if it does not exist
if (mysqli_num_rows(mysqli_query($link, "SHOW TABLES LIKE '" . TINYIB_DBBANS . "'")) == 0) {
	mysqli_query($link, "CREATE TABLE `" . TINYIB_DBBANS . "` (
		`id` mediumint(7) unsigned NOT NULL auto_increment,
		`ip` varchar(15) NOT NULL,
		`timestamp` int(20) NOT NULL,
		`expire` int(20) NOT NULL,
		`reason` text NOT NULL,
		PRIMARY KEY	(`id`),
		KEY `ip` (`ip`)
	) ENGINE=MyISAM");
}

# Post Functions
function uniquePosts() {
	global $link;
	$row = mysqli_fetch_row(mysqli_query($link, "SELECT COUNT(DISTINCT(`ip`)) FROM " . TINYIB_DBPOSTS));
	return $row[0];
}

function postByID($id) {
	global $link;
	$result = mysqli_query($link, "SELECT * FROM `" . TINYIB_DBPOSTS . "` WHERE `id` = '" . mysqli_real_escape_string($link, $id) . "' LIMIT 1");
	if ($result) {
		while ($post = mysqli_fetch_assoc($result)) {
			return $post;
		}
	}
}

function threadExistsByID($id) {
	global $link;
	return mysqli_result(mysqli_query($link, "SELECT COUNT(*) FROM `" . TINYIB_DBPOSTS . "` WHERE `id` = '" . mysqli_real_escape_string($link, $id) . "' AND `parent` = 0 LIMIT 1"), 0, 0) > 0;
}

function insertPost($post) {
	global $link;
	mysqli_query($link, "INSERT INTO `" . TINYIB_DBPOSTS . "` (`parent`, `timestamp`, `bumped`, `ip`, `name`, `tripcode`, `email`, `nameblock`, `subject`, `message`, `password`, `file`, `file_hex`, `file_original`, `file_size`, `file_size_formatted`, `image_width`, `image_height`, `thumb`, `thumb_width`, `thumb_height`) VALUES (" . $post['parent'] . ", " . time() . ", " . time() . ", '" . $_SERVER['REMOTE_ADDR'] . "', '" . mysqli_real_escape_string($link, $post['name']) . "', '" . mysqli_real_escape_string($link, $post['tripcode']) . "',	'" . mysqli_real_escape_string($link, $post['email']) . "',	'" . mysqli_real_escape_string($link, $post['nameblock']) . "', '" . mysqli_real_escape_string($link, $post['subject']) . "', '" . mysqli_real_escape_string($link, $post['message']) . "', '" . mysqli_real_escape_string($link, $post['password']) . "', '" . $post['file'] . "', '" . $post['file_hex'] . "', '" . mysqli_real_escape_string($link, $post['file_original']) . "', " . $post['file_size'] . ", '" . $post['file_size_formatted'] . "', " . $post['image_width'] . ", " . $post['image_height'] . ", '" . $post['thumb'] . "', " . $post['thumb_width'] . ", " . $post['thumb_height'] . ")");
	return mysqli_insert_id($link);
}

function bumpThreadByID($id) {
	global $link;
	mysqli_query($link, "UPDATE `" . TINYIB_DBPOSTS . "` SET `bumped` = " . time() . " WHERE `id` = " . $id . " LIMIT 1");
}

function countThreads() {
	global $link;
	return mysqli_result(mysqli_query($link, "SELECT COUNT(*) FROM `" . TINYIB_DBPOSTS . "` WHERE `parent` = 0"), 0, 0);
}

function allThreads() {
	global $link;
	$threads = array();
	$result = mysqli_query($link, "SELECT * FROM `" . TINYIB_DBPOSTS . "` WHERE `parent` = 0 ORDER BY `bumped` DESC");
	if ($result) {
		while ($thread = mysqli_fetch_assoc($result)) {
			$threads[] = $thread;
		}
	}
	return $threads;
}

function numRepliesToThreadByID($id) {
	global $link;
	return mysqli_result(mysqli_query($link, "SELECT COUNT(*) FROM `" . TINYIB_DBPOSTS . "` WHERE `parent` = " . $id), 0, 0);
}

function postsInThreadByID($id) {
	global $link;
	$posts = array();
	$result = mysqli_query($link, "SELECT * FROM `" . TINYIB_DBPOSTS . "` WHERE `id` = " . $id . " OR `parent` = " . $id . " ORDER BY `id` ASC");
	if ($result) {
		while ($post = mysqli_fetch_assoc($result)) {
			$posts[] = $post;
		}
	}
	return $posts;
}

function postsByHex($hex) {
	global $link;
	$posts = array();
	$result = mysqli_query($link, "SELECT `id`, `parent` FROM `" . TINYIB_DBPOSTS . "` WHERE `file_hex` = '" . mysqli_real_escape_string($link, $hex) . "' LIMIT 1");
	if ($result) {
		while ($post = mysqli_fetch_assoc($result)) {
			$posts[] = $post;
		}
	}
	return $posts;
}

function latestPosts() {
	global $link;
	$posts = array();
	$result = mysqli_query($link, "SELECT * FROM `" . TINYIB_DBPOSTS . "` ORDER BY `timestamp` DESC LIMIT 10");
	if ($result) {
		while ($post = mysqli_fetch_assoc($result)) {
			$posts[] = $post;
		}
	}
	return $posts;
}

function deletePostByID($id) {
	global $link;
	$posts = postsInThreadByID($id);
	foreach ($posts as $post) {
		if ($post['id'] != $id) {
			deletePostImages($post);
			mysqli_query($link, "DELETE FROM `" . TINYIB_DBPOSTS . "` WHERE `id` = " . $post['id'] . " LIMIT 1");
		} else {
			$thispost = $post;
		}
	}
	if (isset($thispost)) {
		if ($thispost['parent'] == TINYIB_NEWTHREAD) {
			@unlink('res/' . $thispost['id'] . '.html');
		}
		deletePostImages($thispost);
		mysqli_query($link, "DELETE FROM `" . TINYIB_DBPOSTS . "` WHERE `id` = " . $thispost['id'] . " LIMIT 1");
	}
}

function trimThreads() {
	global $link;
	if (TINYIB_MAXTHREADS > 0) {
		$result = mysqli_query($link, "SELECT `id` FROM `" . TINYIB_DBPOSTS . "` WHERE `parent` = 0 ORDER BY `bumped` DESC LIMIT " . TINYIB_MAXTHREADS . ", 10");
		if ($result) {
			while ($post = mysqli_fetch_assoc($result)) {
				deletePostByID($post['id']);
			}
		}
	}
}

function lastPostByIP() {
	global $link;
	$replies = mysqli_query($link, "SELECT * FROM `" . TINYIB_DBPOSTS . "` WHERE `ip` = '" . $_SERVER['REMOTE_ADDR'] . "' ORDER BY `id` DESC LIMIT 1");
	if ($replies) {
		while ($post = mysqli_fetch_assoc($replies)) {
			return $post;
		}
	}
}

# Ban Functions
function banByID($id) {
	global $link;
	$result = mysqli_query($link, "SELECT * FROM `" . TINYIB_DBBANS . "` WHERE `id` = '" . mysqli_real_escape_string($link, $id) . "' LIMIT 1");
	if ($result) {
		while ($ban = mysqli_fetch_assoc($result)) {
			return $ban;
		}
	}
}

function banByIP($ip) {
	global $link;
	$result = mysqli_query($link, "SELECT * FROM `" . TINYIB_DBBANS . "` WHERE `ip` = '" . mysqli_real_escape_string($link, $ip) . "' LIMIT 1");
	if ($result) {
		while ($ban = mysqli_fetch_assoc($result)) {
			return $ban;
		}
	}
}

function allBans() {
	global $link;
	$bans = array();
	$result = mysqli_query($link, "SELECT * FROM `" . TINYIB_DBBANS . "` ORDER BY `timestamp` DESC");
	if ($result) {
		while ($ban = mysqli_fetch_assoc($result)) {
			$bans[] = $ban;
		}
	}
	return $bans;
}

function insertBan($ban) {
	global $link;
	mysqli_query($link, "INSERT INTO `" . TINYIB_DBBANS . "` (`ip`, `timestamp`, `expire`, `reason`) VALUES ('" . mysqli_real_escape_string($link, $ban['ip']) . "')");
	return mysqli_insert_id($link);
}

function clearExpiredBans() {
	global $link;
	$result = mysqli_query($link, "SELECT * FROM `" . TINYIB_DBBANS . "` WHERE `expire` > 0 AND `expire` <= " . time());
	if ($result) {
		while ($ban = mysqli_fetch_assoc($result)) {
			mysqli_query($link, "DELETE FROM `" . TINYIB_DBBANS . "` WHERE `id` = " . $ban['id'] . " LIMIT 1");
		}
	}
}

function deleteBanByID($id) {
	global $link;
	mysqli_query($link, "DELETE FROM `" . TINYIB_DBBANS . "` WHERE `id` = " . mysqli_real_escape_string($link, $id) . " LIMIT 1");
}

function mysqli_result($res, $row, $field = 0) {
	$res->data_seek($row);
	$datarow = $res->fetch_array();
	return $datarow[$field];
}