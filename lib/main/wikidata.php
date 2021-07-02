<?php

function wikidata_get_article(&$db, $url)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t wikidata_get_article()");
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
	foreach($data["results"]["bindings"] as $element)
	{
		if(array_key_exists("_s", $element))
		{
			if(array_key_exists("value", $element["_s"]))
			{
				$wikidata_id = $db->real_escape_string($element["_s"]["value"]);
				
				$id = explode("entity/",$wikidata_id);
				$columns = "wikidata_id, online, data_update";
				$values = "'$id[1]', 3, CURRENT_TIMESTAMP";
			
				if(array_key_exists("_sLabel", $element))
				{
					if(array_key_exists("xml:lang", $element["_sLabel"]))
					{
						if(array_key_exists("value", $element["_sLabel"]))
						{
							$sLabel = $db->real_escape_string($element["_sLabel"]["value"]);
							$columns .= ", sLabel";
							$values .= ", '$sLabel'";
						}
					}
				}
				
				$sql = "INSERT INTO " . $config['dbprefix'] . "wikidata_external_data($columns) VALUES ($values)"; 
				$db->query($sql);
			
				if($config['log'] > 2)
				{
					append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
				}
			}
		}
	}
}

function wikidata_get_feature_query(&$db)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t wikidata_getfeature_query()");
	}
		
	$sql = "SELECT `query`,`feature` FROM `" . $config['dbprefix'] . "wikidata_external_category_features_query` WHERE 1";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$feature_query[$db->real_escape_string($row['feature'])] = $db->real_escape_string($row['query']);
	}

	$res->free();
	
	return $feature_query;
}

function wikidata_has_feature(&$db,$feature_query)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t wikidata_has_feature()");
	}
	
	foreach($feature_query as $feature => $api_query)
	{
		// api query
		$url = 'https://query.wikidata.org/sparql?format=json&query=' . $api_query;

		// read data
		$user_agent = $config['user_agent'];
		ini_set('user_agent', $user_agent);
	
		$content = @file_get_contents($url);
		if ($content === FALSE)
		{
			return "connection error"; // TODO !!!
		} 
		
		// save data
		$data = json_decode($content, true);
		
		foreach($data["results"]["bindings"] as $element)
		{
			if(array_key_exists("item", $element))
			{
				if(array_key_exists("value", $element["item"]))
				{
					$wikidata_id = $db->real_escape_string($element["item"]["value"]);
					$id = explode("entity/",$wikidata_id);
					
					$sql = "UPDATE `" . $config['dbprefix'] . "wikidata_external_data` SET `$feature`='1', `data_update` = CURRENT_TIMESTAMP WHERE `wikidata_id` LIKE '$id[1]'";
					$db->query($sql);
					if($config['log'] > 2)
					{
						append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
					}
				}
			}
		}
	}
}

function wikidata_base_get_main(&$db)
{ 
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t wikidata_base_get_main()");
	}
	
	$sql = "UPDATE `" . $config['dbprefix'] . "wikidata_external_data` SET `online`='1' WHERE `online`='2'";
	$db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$sql = "SELECT `query` FROM `" . $config['dbprefix'] . "wikidata_external_main` WHERE `online` = '1'";
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
		if(wikidata_get_article($db, $url) == "connection error")
		{
			if($config['log'] > 0)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t error \t connection error \t wikidata_get_main(".$url.")");
			}
			return "ERROR";
		}
	}
	
	// dublicates
	$sql = "SELECT `wikidata_id`, count(`wikidata_id`) AS `more`  FROM `" . $config['dbprefix'] . "wikidata_external_data` WHERE `online` = 3 GROUP BY `wikidata_id` HAVING COUNT(`wikidata_id`) > 1";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$wikidata_id = array();
	$more = array();
	$items = 0;
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$wikidata_id[] = $db->real_escape_string($row['wikidata_id']);
		$more[] = $db->real_escape_string($row['more']) - 1;
		$items++;
	}
	$res->free();
	
	$loop = 0;
	while($loop < $items)
	{
		$sql = "DELETE FROM `" . $config['dbprefix'] . "wikidata_external_data` WHERE `online`='3' AND `wikidata_id` = '".$wikidata_id[$loop]."' LIMIT ".$more[$loop];
		$db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
		$loop++;
	}
	
	$feature_query = wikidata_get_feature_query($db);
	wikidata_has_feature($db,$feature_query);
}

?>