<?php

function wikidata_get_place_list(&$db)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t wikidata_get_place_list()");
	}
	
	$sql = "SELECT `wikidata`, `gemeinde` FROM `" . $config['dbprefix'] . "gemeinde_geo`";
	$res = $db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$place_list[] = array();
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$wikidata = $db->real_escape_string($row['wikidata']);
		$place_list[$wikidata] = $db->real_escape_string($row['gemeinde']);
	}
	
	$res->free();
	
	return $place_list;
}


function wikidata_get_data(&$db, $url, $wikidata_id, $place_list)
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
	
	$insert = "";
	$insert_value = "";
	$latitude = $longitude = array();
	
	if(isset($data["entities"]["$wikidata_id"]["claims"]["P625"]))
	{
		foreach($data["entities"]["$wikidata_id"]["claims"]["P625"] as $coordinate)
		{
			if(isset($coordinate["mainsnak"]["datavalue"]["value"]["latitude"]))
			{
				array_push($latitude, $coordinate["mainsnak"]["datavalue"]["value"]["latitude"]);
			}
			if(isset($coordinate["mainsnak"]["datavalue"]["value"]["longitude"]))
			{
				array_push($longitude, $coordinate["mainsnak"]["datavalue"]["value"]["longitude"]);
			}
		}
	}
	else if(isset($data["entities"]["$wikidata_id"]["claims"]["P119"]["0"]["qualifiers"]["P625"]))
	{
		foreach($data["entities"]["$wikidata_id"]["claims"]["P119"]["0"]["qualifiers"]["P625"] as $coordinate)
		{
			if(isset($coordinate["datavalue"]["value"]["latitude"]))
			{
				array_push($latitude, $coordinate["datavalue"]["value"]["latitude"]);
			}
			if(isset($coordinate["datavalue"]["value"]["longitude"]))
			{
				array_push($longitude, $coordinate["datavalue"]["value"]["longitude"]);
			}
		}
	}
	
	if( (count($latitude) == 0) || (count($longitude) == 0) )
	{
		$sql = "UPDATE `" . $config['dbprefix'] . "wikidata_external_data` SET `online`=5, `data_update` = CURRENT_TIMESTAMP WHERE `wikidata_id` LIKE '$wikidata_id' AND `online` LIKE '3'";
		$db->query($sql);

		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
	}
	else
	{
		if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["dewiki"]["url"]))
		{
			$article = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["dewiki"]["url"]);
			if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["dewiki"]["title"]))
			{
				$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["dewiki"]["title"]);
				$insert .= ", `article`, `sLabel`";
				$insert_value .= ", '" . $article . "', '" . $sLabel . "'";
			}
		}
		else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["dewikivoyage"]["url"]))
		{
			$article = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["dewikivoyage"]["url"]);
			if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["dewikivoyage"]["title"]))
			{
				$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["dewikivoyage"]["title"]);
				$insert .= ", `article`, `sLabel`";
				$insert_value .= ", '" . $article . "', '" . $sLabel . "'";
			}
		}
		else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["commonswiki"]["url"]))
		{
			$article = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["commonswiki"]["url"]);
			if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["commonswiki"]["title"]))
			{
				$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["commonswiki"]["title"]);
				$insert .= ", `article`, `sLabel`";
				$insert_value .= ", '" . $article . "', '" . $sLabel . "'";
			}
		}
		
		else if(isset($data["entities"]["$wikidata_id"]["labels"]["de"]["value"]))
		{
			$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
			$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["labels"]["de"]["value"]);
			$insert .= ", `article`, `sLabel`";
			$insert_value .= ", '" . $article . "', '" . $sLabel . "'";
		}
		else if(isset($data["entities"]["$wikidata_id"]["labels"]["en"]["value"]))
		{
			$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
			$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["labels"]["en"]["value"]);
			$insert .= ", `article`, `sLabel`";
			$insert_value .= ", '" . $article . "', '" . $sLabel . "'";
		}
		// no de/en lable
		else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["enwiki"]["title"]))
		{
			$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
			$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["enwiki"]["title"]);
			$insert .= ", `article`, `sLabel`";
			$insert_value .= ", '" . $article . "', '" . $sLabel . "'";
		}
		else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["huwiki"]["title"]))
		{
			$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
			$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["huwiki"]["title"]);
			$insert .= ", `article`, `sLabel`";
			$insert_value .= ", '" . $article . "', '" . $sLabel . "'";
		}
		else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["slwiki"]["title"]))
		{
			$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
			$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["slwiki"]["title"]);
			$insert .= ", `article`, `sLabel`";
			$insert_value .= ", '" . $article . "', '" . $sLabel . "'";
		}
		else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["plwiki"]["title"]))
		{
			$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
			$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["plwiki"]["title"]);
			$insert .= ", `article`, `sLabel`";
			$insert_value .= ", '" . $article . "', '" . $sLabel . "'";
		}
		else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["itwiki"]["title"]))
		{
			$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
			$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["itwiki"]["title"]);
			$insert .= ", `article`, `sLabel`";
			$insert_value .= ", '" . $article . "', '" . $sLabel . "'";
		}
		else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["frwiki"]["title"]))
		{
			$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
			$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["frwiki"]["title"]);
			$insert .= ", `article`, `sLabel`";
			$insert_value .= ", '" . $article . "', '" . $sLabel . "'";
		}
		else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["nlwiki"]["title"]))
		{
			$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
			$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["nlwiki"]["title"]);
			$insert .= ", `article`, `sLabel`";
			$insert_value .= ", '" . $article . "', '" . $sLabel . "'";
		}
		else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["ukwiki"]["title"]))
		{
			$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
			$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["ukwiki"]["title"]);
			$insert .= ", `article`, `sLabel`";
			$insert_value .= ", '" . $article . "', '" . $sLabel . "'";
		}
		else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["ruwiki"]["title"]))
		{
			$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
			$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["ruwiki"]["title"]);
			$insert .= ", `article`, `sLabel`";
			$insert_value .= ", '" . $article . "', '" . $sLabel . "'";
		}
		
		/*else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["svwiki"]["title"]))
		{
			$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
			$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["svwiki"]["title"]);
			$insert .= ", `article`='" . $article . "', `sLabel`='" . $sLabel . "'";
		}
		else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["cebwiki"]["url"]))
		{
			$article = $data["entities"]["$wikidata_id"]["sitelinks"]["cebwiki"]["url"];
			if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["cebwiki"]["title"]))
			{
				$sLabel = $data["entities"]["$wikidata_id"]["sitelinks"]["cebwiki"]["title"];
				$insert .= ", `article`='" . $article . "', `sLabel`='" . $sLabel . "'";
			}
		}*/
		
		if(isset($data["entities"]["$wikidata_id"]["descriptions"]["de"]["value"]))
		{
			$description = $db->real_escape_string($data["entities"]["$wikidata_id"]["descriptions"]["de"]["value"]);
			$insert .= ", `description`";
			$insert_value .= ", '" . $description . "'";
		}
			
		// special ceb, sv
		$online = 4;
		$items = sizeof($data["entities"]["$wikidata_id"]["sitelinks"]);
		if($items==1)
		{
			if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["cebwiki"]["url"]))
			{
				$online = 5;
			}
			else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["svwiki"]["url"]))
			{
				$online = 5;
			}
		}
		else if($items==2)
		{
			if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["cebwiki"]["url"]))
			{
				if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["svwiki"]["url"]))
				{
					$online = 5;
				}
			}
		}
		$insert .= ", `online`";
		$insert_value .= ", '" . $online . "'";
		

		// P131
		$place = "";
		$loop_P131 = 0;
		while(isset($data["entities"]["$wikidata_id"]["claims"]["P131"][$loop_P131]["mainsnak"]["datavalue"]["value"]["id"]))
		{
			$place = $db->real_escape_string($data["entities"]["$wikidata_id"]["claims"]["P131"][$loop_P131]["mainsnak"]["datavalue"]["value"]["id"]);
			if(array_key_exists($place, $place_list))
			{
				$insert .= ", `place`";
				$insert_value .= ", '" . $place_list[$place] . "'";
				break;
			}
			$loop_P131++;
		}
		
		// P18
		$abb = "";
		if(isset($data["entities"]["$wikidata_id"]["claims"]["P18"][0]["mainsnak"]["datavalue"]["value"]))
		{
			$foto = $db->real_escape_string($data["entities"]["$wikidata_id"]["claims"]["P18"][0]["mainsnak"]["datavalue"]["value"]);
			$insert .= ", `foto`";
			$insert_value .= ", '" . $foto . "'";
		}
		
		// P373
		$commonscat = "";
		if(isset($data["entities"]["$wikidata_id"]["claims"]["P373"][0]["mainsnak"]["datavalue"]["value"]))
		{
			$commonscat = $db->real_escape_string($data["entities"]["$wikidata_id"]["claims"]["P373"][0]["mainsnak"]["datavalue"]["value"]);
			$insert .= ", `commonscat`";
			$insert_value .= ", '" . $commonscat . "'";
		}
		
		// P4219
		$tkk = "";
		if(isset($data["entities"]["$wikidata_id"]["claims"]["P4219"][0]["mainsnak"]["datavalue"]["value"]))
		{
			$tkk = $db->real_escape_string($data["entities"]["$wikidata_id"]["claims"]["P4219"][0]["mainsnak"]["datavalue"]["value"]);
			$insert .= ", `tkk`";
			$insert_value .= ", '" . $tkk . "'";
		}
		
		// Save Data
		$sql = "DELETE FROM `" . $config['dbprefix'] . "wikidata_external_data` WHERE `wikidata_id` LIKE '$wikidata_id' AND `online` LIKE '3'";
		$db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
				
		for ($loopco = 0; $loopco < count($latitude); $loopco++)
		{
			$insert_add = ", `latitude`";
			$insert_value_add = ", '" .  $latitude[$loopco] . "'";
			
			$insert_add .= ", `longitude`";
			$insert_value_add .= ", '" .  $longitude[$loopco] . "'";
			
			$insert_add .= ", `data_update`";
			$insert_value_add .= ", CURRENT_TIMESTAMP";
			
			$sql = "INSERT INTO `" . $config['dbprefix'] . "wikidata_external_data` (`wikidata_id` " . $insert . $insert_add . ") VALUES ('$wikidata_id'" . $insert_value . $insert_value_add .  ")";
	 		$db->query($sql); 
	 		if($config['log'] > 2)
	 		{
	 			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	 		}
		}
	}
}

function wikidata_include_article(&$db, $url)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t wikidata_include_article()");
	}
	
	// read data
	$user_agent = $config['user_agent'];
	ini_set('user_agent', $user_agent);
	
	$content = @file_get_contents($url);
	if ($content === FALSE)
	{
		return "connection error";
	}
	
	// include article
	$data = json_decode($content, true);
	foreach($data["results"]["bindings"] as $element)
	{
		if(array_key_exists("item", $element))
		{
			if(array_key_exists("value", $element["item"]))
			{
				$wikidata_id = $db->real_escape_string($element["item"]["value"]);
				
				$id = explode("entity/",$wikidata_id);
				$wikidata_id = $id[1];
				
				$sql = "UPDATE `" . $config['dbprefix'] . "wikidata_external_data` SET `online` = 4, `data_update` = CURRENT_TIMESTAMP WHERE `wikidata_id` LIKE '$wikidata_id' AND `online` LIKE '5'";
				
				$db->query($sql);
			
				if($config['log'] > 2)
				{
					append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
				}
			}
		}
	}
}

function wikidata_get_main(&$db)
{
	global $config;
	
	$place_list = array();
	$place_list = wikidata_get_place_list($db);
	
	$sql = "SELECT `wikidata_id` FROM `" . $config['dbprefix'] . "wikidata_external_data` WHERE `online` = '3'";
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
		if(wikidata_get_data($db, $url, $wikidata_id, $place_list) == "connection error")
		{
			if($config['log'] > 0)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t error \t connection error \t wikidata_get_main(".$url.")");
			}
			return "ERROR";
		}
	}
	
	// include
	$sql = "SELECT `query` FROM `" . $config['dbprefix'] . "wikidata_external_include` WHERE `online` = '1'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}

	$api_querys = array();
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$api_querys[] = $db->real_escape_string($row['query']);
	}
	$res->free();

	foreach($api_querys as $api_query)
	{
		// api query
		$url = 'https://query.wikidata.org/sparql?format=json&query=' . $api_query;
	
		// read data and save to db
		if(wikidata_include_article($db, $url) == "connection error")
		{
			if($config['log'] > 0)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t error \t connection error \t wikidata_get_main(".$url.")");
			}
			return "ERROR";
		}
	}
	
	
	// delete stuff
	$sql = "DELETE FROM `" . $config['dbprefix'] . "wikidata_external_data` WHERE `online`='5'";
	$db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	
	$sql = "SELECT count(`wikidata_id`) AS `new` FROM `" . $config['dbprefix'] . "wikidata_external_data` WHERE `online` = 4";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	$row = $res->fetch_array(MYSQLI_ASSOC);
	$new = $db->real_escape_string($row['new']);
	$res->free();
	
		// delete old data
		$sql = "DELETE FROM `" . $config['dbprefix'] . "wikidata_external_data` WHERE `online`='1'";
		$db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
	
		// show new data
		$sql = "UPDATE `" . $config['dbprefix'] . "wikidata_external_data` SET `online`='2' WHERE `online`='4'";
		$db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
}

?>