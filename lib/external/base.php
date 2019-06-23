<?php

function request_get_articles_list(&$db, $source)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t list_get_articles_list()");
	}
	
	$sql = "SELECT `gemeinde`,`latitude`,`longitude`,`distance` FROM `" . $config['dbprefix'] . "gemeinde_geo` WHERE 1";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$articles = array();
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$articles[ $db->real_escape_string($row['gemeinde']) ]['gemeinde'] = $db->real_escape_string($row['gemeinde']);
		$articles[ $db->real_escape_string($row['gemeinde']) ]['latitude'] = $db->real_escape_string($row['latitude']);
		$articles[ $db->real_escape_string($row['gemeinde']) ]['longitude'] = $db->real_escape_string($row['longitude']);
		$articles[ $db->real_escape_string($row['gemeinde']) ]['distance'] = $db->real_escape_string($row['distance']);
	}
	
	$res->free();
	
	return $articles;
}

function request_get_article(&$db, $source, $url, $gemeinde, $features)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t request_get_article()");
	}
	
	// read data
	$user_agent = $config['user_agent'];
	ini_set('user_agent', $user_agent);
	
	$content = @file_get_contents($url);
	if ($content === FALSE)
	{
		return "connection error";
	}
	
	// delete old data
	$sql = "DELETE FROM `" . $config['dbprefix'] . $source . "_external_data` WHERE `gemeinde`='".$gemeinde."'";

	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	if ($db->query($sql) !== TRUE)
	{
		return "connection error";
	}
	
	// save data
	$data_array = explode("\n",$content);

	for($lines = 1; $lines < count($data_array); $lines++)
	{
		if($data_array[$lines] != "")
		{
			$data = explode(";",$data_array[$lines]);
			$latitude = $db->real_escape_string($data[1]);
			$longitude = $db->real_escape_string($data[2]);
			$article = "https://de.wikipedia.org/wiki/".$db->real_escape_string($data[3]);
			$name = str_replace("_"," ",$db->real_escape_string($data[3]));
			$description = str_replace("_"," ",$db->real_escape_string($data[4]));
			
			$sql = "INSERT INTO `" . $config['dbprefix'] . $source . "_external_data` ("
				. "`latitude`, `longitude`, `description`, `gemeinde`, `article`, `name`, `online`, `data_update`) VALUES ("
				. "'".$latitude."','".$longitude."','".$description."','".$gemeinde."','".$article."', '".$name."', '2', CURRENT_TIMESTAMP)";
			
			if($config['log'] > 2)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
			}
			if ($db->query($sql) !== TRUE)
			{
				return "connection error";
			}
		}
	}
}

function request_get_main(&$db, $source)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t request_get_main()");
	}
	
	$sql = "UPDATE `" . $config['dbprefix'] . $source . "_external_data` SET `online`='1' WHERE `online`='2'";

	$db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$articles = request_get_articles_list($db, $source);
	
	$sql = "SELECT `data` FROM `" . $config['dbprefix'] . "source_config` WHERE `key` LIKE 'api_url' AND `wiki` LIKE '" . $source . "'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$row = $res->fetch_array(MYSQLI_ASSOC);
	$api_url = $row['data'];
	$res->free();
	
	foreach($articles as $article)
	{
		// api query
		$url = $api_url . '?lat=' . urlencode($article['latitude']) . '&lon=' . urlencode($article['longitude']) . '&distance=' . urlencode($article['distance']);
		
		// read data and save to db
		if(request_get_article($db, $source, $url, $article['gemeinde'], $features) == "connection error")
		{
			if($config['log'] > 0)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t error \t connection error \t wiki_get_main(".$url.")");
			}
			return "ERROR";
		}
	}
	
	$sql = "UPDATE `" . $config['dbprefix'] . $source . "_external_data` SET `online`='0' WHERE `online`='1'";
	$db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
}

?>