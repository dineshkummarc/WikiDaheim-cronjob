<?php

function wikidata_get_data(&$db, $url, $wikidata_id)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t wikidata_get_data()");
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
	if(isset($data["entities"]["$wikidata_id"]["claims"]["P625"][0]["mainsnak"]["datavalue"]["value"]["latitude"]))
	{
		if(isset($data["entities"]["$wikidata_id"]["claims"]["P625"][0]["mainsnak"]["datavalue"]["value"]["longitude"]))
		{
			$latitude = $db->real_escape_string($data["entities"]["$wikidata_id"]["claims"]["P625"][0]["mainsnak"]["datavalue"]["value"]["latitude"]);
			$longitude = $db->real_escape_string($data["entities"]["$wikidata_id"]["claims"]["P625"][0]["mainsnak"]["datavalue"]["value"]["longitude"]);
			$sql = "UPDATE `" . $config['dbprefix'] . "wikidata_data` SET latitude='".$latitude."', `longitude`='".$longitude."', `online`=4, `data_update` = CURRENT_TIMESTAMP WHERE `wikidata_id` LIKE '$wikidata_id' AND `online` LIKE '3'";
			//echo $sql;
			$db->query($sql);

			if($config['log'] > 2)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
			}
		}
		else
		{
			$sql = "UPDATE `" . $config['dbprefix'] . "wikidata_data` SET `online`=5 WHERE `wikidata_id` LIKE '$wikidata_id' AND `online` LIKE '3'";
			//echo $sql;
			$db->query($sql);

			if($config['log'] > 2)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
			}
		}
	}
	else
	{
		$sql = "UPDATE `" . $config['dbprefix'] . "wikidata_data` SET `online`=5 WHERE `wikidata_id` LIKE '$wikidata_id' AND `online` LIKE '3'";
		//echo $sql;
		$db->query($sql);

		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
	}
}

function wikidata_get_main(&$db)
{
	global $config;
	
	$sql = "SELECT `wikidata_id` FROM `" . $config['dbprefix'] . "wikidata_data` WHERE `online` = '3'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$wikidata_ids = array();
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$wikidata_ids[] = $db->real_escape_string($row['wikidata_id']);
	}
	$res->free();
	
	foreach($wikidata_ids as $wikidata_id)
	{
		// api query
		$url = 'https://www.wikidata.org/wiki/Special:EntityData/' . $wikidata_id . '.json';
		
		// read data and save to db
		if(wikidata_get_data($db, $url, $wikidata_id) == "connection error")
		{
			if($config['log'] > 0)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t error \t connection error \t wikidata_get_main(".$url.")");
			}
			return "ERROR";
		}
	}
	
	// delete old data
	$sql = "DELETE FROM `" . $config['dbprefix'] . "wikidata_data` WHERE `online`='1'";
	$db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$sql = "DELETE FROM `" . $config['dbprefix'] . "wikidata_data` WHERE `online`='5'";
	$db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	// show new data
	$sql = "UPDATE `" . $config['dbprefix'] . "wikidata_data` SET `online`='2' WHERE `online`='4'";
	$db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
}

?>