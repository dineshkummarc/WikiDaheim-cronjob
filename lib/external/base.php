<?php

function bilderwunsch_get_article($db, $source, $url)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t bilderwunsch_get_article()");
	}
	
	// read data
	$user_agent = $config['user_agent'];
	ini_set('user_agent', $user_agent);
	
	$content = @file_get_contents($url);
	if ($content === FALSE)
	{
		return "connection error";
	}
	
	// save data
	$data = json_decode($content, true);

	foreach ($data as $bw)
	{
		$article = $db->real_escape_string(space($bw["title"]));
		$latitude = round($db->real_escape_string($bw["lat"]),10);
		$longitude = round($db->real_escape_string($bw["lon"]),10);
		$description = $db->real_escape_string(space($bw["description"]));
		
		// only in at
		if(($latitude<=49)&($latitude>=46.3))
		{
			if(($longitude<=17.2)&($longitude>=9.5))
			{
				if($description=="")
				{
					$description = $article;
				}
				$sql = "INSERT INTO " . $config['dbprefix'] . $source . "_external_data(article,latitude,longitude,description,online,data_update) VALUES ('$article',$latitude,$longitude,'$description',4,CURRENT_TIMESTAMP)";
				$db->query($sql);
				if($config['log'] > 2)
				{
					append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
				}
			}
		}
	}
}

function bilderwunsch_get_main(&$db, $source)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t bilderwunsch_get_main()");
	}
	
	$sql = "UPDATE `" . $config['dbprefix'] . $source . "_external_data` SET `online`='1' WHERE `online`='2'";

	$db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$sql = "SELECT `data` FROM `" . $config['dbprefix'] . "source_config` WHERE `key` LIKE 'api_url' AND `wiki` LIKE '" . $source . "'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$row = $res->fetch_array(MYSQLI_ASSOC);
	$api_url = $row['data'];
	$res->free();
	
	// read data and save to db
	if(bilderwunsch_get_article($db, $source, $api_url) == "connection error")
	{
		if($config['log'] > 0)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t error \t connection error \t bilderwunsch_get_main(".$url.")");
		}
		return "ERROR";
	}
	
	// delete old data
	$sql = "DELETE FROM `" . $config['dbprefix'] . $source . "_external_data` WHERE `online`='1'";
	$db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	// show new data
	$sql = "UPDATE `" . $config['dbprefix'] . $source . "_external_data` SET `online`='2' WHERE `online`='4'";
	$db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
}

?>