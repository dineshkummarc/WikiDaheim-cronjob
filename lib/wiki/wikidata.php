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
			
			$insert = "`latitude`=".$latitude.", `longitude`=".$longitude;
			
			if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["dewiki"]["url"]))
			{
				$article = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["dewiki"]["url"]);
				if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["dewiki"]["title"]))
				{
					$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["dewiki"]["title"]);
					$insert .= ", `article`='" . $article . "', `sLabel`='" . $sLabel . "'";
				}
			}
			else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["dewikivoyage"]["url"]))
			{
				$article = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["dewikivoyage"]["url"]);
				if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["dewikivoyage"]["title"]))
				{
					$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["dewikivoyage"]["title"]);
					$insert .= ", `article`='" . $article . "', `sLabel`='" . $sLabel . "'";
				}
			}
			else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["commonswiki"]["url"]))
			{
				$article = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["commonswiki"]["url"]);
				if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["commonswiki"]["title"]))
				{
					$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["commonswiki"]["title"]);
					$insert .= ", `article`='" . $article . "', `sLabel`='" . $sLabel . "'";
				}
			}
			
			if(isset($data["entities"]["$wikidata_id"]["labels"]["de"]["value"]))
			{
				$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
				$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["labels"]["de"]["value"]);
				$insert .= ", `article`='" . $article . "', `sLabel`='" . $sLabel . "'";
			}
			else if(isset($data["entities"]["$wikidata_id"]["labels"]["en"]["value"]))
			{
				$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
				$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["labels"]["en"]["value"]);
				$insert .= ", `article`='" . $article . "', `sLabel`='" . $sLabel . "'";
			}
			// no de/en lable
			else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["enwiki"]["title"]))
			{
				$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
				$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["enwiki"]["title"]);
				$insert .= ", `article`='" . $article . "', `sLabel`='" . $sLabel . "'";
			}
			else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["huwiki"]["title"]))
			{
				$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
				$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["huwiki"]["title"]);
				$insert .= ", `article`='" . $article . "', `sLabel`='" . $sLabel . "'";
			}
			else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["slwiki"]["title"]))
			{
				$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
				$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["slwiki"]["title"]);
				$insert .= ", `article`='" . $article . "', `sLabel`='" . $sLabel . "'";
			}
			else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["plwiki"]["title"]))
			{
				$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
				$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["plwiki"]["title"]);
				$insert .= ", `article`='" . $article . "', `sLabel`='" . $sLabel . "'";
			}
			else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["itwiki"]["title"]))
			{
				$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
				$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["itwiki"]["title"]);
				$insert .= ", `article`='" . $article . "', `sLabel`='" . $sLabel . "'";
			}
			else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["frwiki"]["title"]))
			{
				$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
				$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["frwiki"]["title"]);
				$insert .= ", `article`='" . $article . "', `sLabel`='" . $sLabel . "'";
			}
			else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["nlwiki"]["title"]))
			{
				$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
				$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["nlwiki"]["title"]);
				$insert .= ", `article`='" . $article . "', `sLabel`='" . $sLabel . "'";
			}
			else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["ukwiki"]["title"]))
			{
				$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
				$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["ukwiki"]["title"]);
				$insert .= ", `article`='" . $article . "', `sLabel`='" . $sLabel . "'";
			}
			else if(isset($data["entities"]["$wikidata_id"]["sitelinks"]["ruwiki"]["title"]))
			{
				$article = "https://www.wikidata.org/wiki/" . $wikidata_id;
				$sLabel = $db->real_escape_string($data["entities"]["$wikidata_id"]["sitelinks"]["ruwiki"]["title"]);
				$insert .= ", `article`='" . $article . "', `sLabel`='" . $sLabel . "'";
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
				$insert .= ", `description`='" . $description . "'";
			}
			
			
			// special ceb, sv and no item
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
/*			else if($items==0)
			{
				$online = 5;
			}*/
			$insert .= ", `online`= " . $online;
			
			$sql = "UPDATE `" . $config['dbprefix'] . "wikidata_external_data` SET " . $insert . ", `data_update` = CURRENT_TIMESTAMP WHERE `wikidata_id` LIKE '$wikidata_id' AND `online` LIKE '3'";

			$db->query($sql);

			if($config['log'] > 2)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
			}
		}
		else
		{
			$sql = "UPDATE `" . $config['dbprefix'] . "wikidata_external_data` SET `online`=5 WHERE `wikidata_id` LIKE '$wikidata_id' AND `online` LIKE '3'";
			$db->query($sql);

			if($config['log'] > 2)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
			}
		}
	}
	else
	{
		$sql = "UPDATE `" . $config['dbprefix'] . "wikidata_external_data` SET `online`=5 WHERE `wikidata_id` LIKE '$wikidata_id' AND `online` LIKE '3'";
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
	$sql = "DELETE FROM `" . $config['dbprefix'] . "wikidata_external_data` WHERE `online`='1'";
	$db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$sql = "DELETE FROM `" . $config['dbprefix'] . "wikidata_external_data` WHERE `online`='5'";
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