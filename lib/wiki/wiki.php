<?php

function township_get_articles_list($db, $source)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t township_get_articles_list()");
	}
	
	$sql = "SELECT `article` FROM `" . $config['dbprefix'] . $source . "_township` WHERE `online` >= 2";
	$res = $db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$articles = array();
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$articles[] = $db->real_escape_string($row['article']);
	}
	
	$res->close();
	
	return $articles;
}

function township_get_feature_alias_list($db, $source, $features)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t township_get_feature_alias_list()");
	}
	
	$features_alias[] = array();
	
	foreach($features as $feature)
	{
		$feature_alias[$feature][$feature] = "";
		
		$sql = "SELECT `alias` FROM `" . $config['dbprefix'] . $source . "_township_features_alias` WHERE `feature` LIKE '".$feature."'";
		$res = $db->query($sql);
	
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
		
		while($row = $res->fetch_array(MYSQLI_ASSOC))
		{
			$feature_alias[$feature][$db->real_escape_string($row['alias'])] = "";
		}
	
		$res->close();
	}
	
	return $feature_alias;
}

function township_get_feature_list($db, $source)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t township_get_feature_list()");
	}
	
	$sql = "SELECT `feature` FROM `" . $config['dbprefix'] . $source . "_township_features` WHERE `online` >= 1";
	$res = $db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$features = array();
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$features[] = $db->real_escape_string($row['feature']);
	}
	
	$res->close();
	
	return township_get_feature_alias_list($db, $source, $features);
}

function township_has_feature_test($str, $test_feature)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t township_has_feature_test()");
	}
	
	$str = str_replace(array("\\n"," "),"",$str);
	$str = preg_replace("/<!--(.*)-->/Uis", "", $str); // remove ><((((°> comments
	if (strpos($str, "=".str_replace(" ", "", $test_feature)."=") === false)
	{
		return 0;
	}
	
	return 1;
}

function township_has_feature($str, $feature)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t township_has_feature()");
	}
	
	foreach($feature as $test_feature => $value)
	{
		if (township_has_feature_test($str, $test_feature) != 0)
		{
			return 1;
		}
	}
	
	return 0;
}

function township_get_municipalityid($db, $str, $article)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t township_get_municipalityid()");
	}
	
	$gemeindekennzahl = "";
	$str = str_replace(" ", "", $str);
	$str = str_replace("\\n", "", $str);
	if (strpos($str, "|Gemeindekennzahl=") !== false)
	{
		$start = strpos($str, "|Gemeindekennzahl=");
		$stop = strlen($str);
		$gemeindekennzahl = substr($str, $start + 18, $stop);
		$gemeindekennzahl = strstr($gemeindekennzahl, '}}', true);
		if (strpos($gemeindekennzahl, "|") !== false)
		{
			$gemeindekennzahl = strstr($gemeindekennzahl, '|', true);
		}
	}
	
	// TODO Wien???
	
	if ($gemeindekennzahl != "")
	{
		$gemeindekennzahl = $db->real_escape_string($gemeindekennzahl);
		
		$sql = "UPDATE `" . $config['dbprefix'] . "gemeinde_geo` SET gemeindekennzahl='$gemeindekennzahl' WHERE `gemeinde` LIKE '$article'";
		$db->query($sql);
		
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
	}
}

function township_get_commonscat($db, $str, $article)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t township_get_commonscat()");
	}
	
	$str = str_replace("{{commonscat", "{{Commonscat", $str);
	$str = str_replace("{{commons", "{{Commons", $str);

	$str = str_replace("|3=S", "", $str);
	$str = str_replace("|}}", "}}", $str);

	$commonscat = "";
	if (strpos($str, "{{Commonscat}}") === false)
	{
		if (strpos($str, "{{Commons}}") !== false)
		{
			$commonscat = $article;
		}
		else if (strpos($str, "{{Commons|") !== false)
		{
			$start = strpos($str, "{{Commons|");
			$stop = strlen($str);
			$commonscat = substr($str, $start + 10, $stop);
			$commonscat = strstr($commonscat, '}}', true);
			if (strpos($commonscat, "|") !== false)
			{
				$commonscat = strstr($commonscat, '|', true);
			}
		}
		else if (strpos($str, "{{Commonscat|") !== false)
		{
			$start = strpos($str, "{{Commonscat|");
			$stop = strlen($str);
			$commonscat = substr($str, $start + 13, $stop);
			$commonscat = strstr($commonscat, '}}', true);
			if (strpos($commonscat, "|") !== false)
			{
				$commonscat = strstr($commonscat, '|', true);
			}
		}
		else if (strpos($str, "{{Schwesterprojekte") !== false)
		{
			$start = strpos($str, "{{Schwesterprojekte");
			$stop = strlen($str);
			$commonscat = substr($str, $start, $stop);
			$commonscat = strstr($commonscat, '}}', true);
			
			$start = strpos($commonscat, "commonscat=");
			$stop = strlen($commonscat);
			$commonscat = substr($commonscat, $start + 11, $stop);
			$commonscat = strstr($commonscat, '|', true);
			
			$commonscat = trim($commonscat);
		}
	}
	else
	{
		$commonscat = $article;
	}
	
	switch($commonscat)
	{
		case "Dellach im Gailtal":
			$commonscat = "Dellach (Gailtal)";
			break;
		case "Ebenthal":
			$commonscat = "Ebenthal in Kärnten";
			break;
		case "Hermagor-Pressegger See":
			$commonscat = "Hermagor";
			break;
		case "Innere Stadt (Wien)":
			$commonscat = "Innere Stadt, Vienna";
			break;
		case "Malta, Kärnten":
			$commonscat = "Malta, Carinthia";
			break;
		case "Maria Rain":
			$commonscat = "Maria Rain, Kärnten";
			break;
		case "Moosburg, Kärnten":
			$commonscat = "Moosburg (Carinthia)";
			break;
		case "Ried (Zillertal)":
			$commonscat = "Ried im Zillertal";
			break;
		case "Sankt Urban, Kärnten":
			$commonscat = "Sankt Urban, Carinthia";
			break;
		case "Schiefling am See":
			$commonscat = "Schiefling am Wörthersee";
			break;
		case "St. Veit an der Glan":
			$commonscat = "Sankt Veit an der Glan";
			break;
		case "Velden am Wörthersee":
			$commonscat = "Velden am Wörther See";
			break;
		case "Weißensee, Kärnten":
			$commonscat = "Weissensee (municipality in Carinthia)";
			break;
		case "Wolfsberg, Kärnten":
			$commonscat = "Wolfsberg";
			break;
	}
	
	
	if ($commonscat != "")
	{
		$commonscat = $db->real_escape_string($commonscat);
		
		$sql = "SELECT `online` FROM `" . $config['dbprefix'] . "commons_commonscat` WHERE `commons_gemeinde` LIKE '$commonscat'";
		$res = $db->query($sql);
		
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
		
		$num_names = $res->num_rows;
		if ($num_names < 1) // new
		{
			$res->close();
			
			$sql = "INSERT INTO " . $config['dbprefix'] . "commons_commonscat(commons_gemeinde, online, data_update) VALUES ('$commonscat', 3, CURRENT_TIMESTAMP)";
			$db->query($sql);
			
			if($config['log'] > 2)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
			}
		} // new
		else if($num_names == 1) // in db
		{
			$row = $res->fetch_array(MYSQLI_ASSOC);
			
			$online = 2;
			if(($row['online'] == 0) || ($row['online'] == 3))
			{
				$online = 3;
			}
			else if($row['online'] == 1)
			{
				$online = 1;
			}
			$res->close();
			
			$sql = "UPDATE `" . $config['dbprefix'] . "commons_commonscat` SET online='$online', data_update=CURRENT_TIMESTAMP WHERE `commons_gemeinde` LIKE '$commonscat'";
			$db->query($sql);
			
			if($config['log'] > 2)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
			}
		} // in db
		else // fix duplicates
		{
			$res->close();
			
			$sql = "DELETE FROM `" . $config['dbprefix'] . "commons_commonscat` WHERE `commons_gemeinde` LIKE '$commonscat'";
			
			$db->query($sql);
			
			if($config['log'] > 2)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
			}
			
			$sql = "INSERT INTO " . $config['dbprefix'] . "commons_commonscat(commons_gemeinde, online, data_update) VALUES ('$commonscat', 3, CURRENT_TIMESTAMP)";
			$db->query($sql);
			
			if($config['log'] > 2)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
			}
		} // fix duplicates
	} // commonscat
	
	return $commonscat;
}

function township_get_wikidata($db, $source, $url, $article)
{
	global $config;

	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t township_get_wikidata()");
	}
	
	// read data
	$user_agent = "WikiDaheim/0.0.0 (wikidaheim.at)";
	ini_set('user_agent', $user_agent);
	$str = @file_get_contents($url);

	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t file_get_contents: \t ".$url);
	}
	
	if($str === FALSE)
	{
		return "connection error";
	}
	else
	{
		$xml = new SimpleXMLElement($str);
		foreach($xml->query->pages->page->pageprops->attributes() as $element => $value)
		{
			if($element == "wikibase_item")
			{
				$sql = "UPDATE `" . $config['dbprefix'] . "gemeinde_geo` SET `wikidata` = '$value' WHERE `gemeinde` LIKE '$article'";
				$db->query($sql);
				if($config['log'] > 2)
				{
					append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
				}
			}
		}
	}
}

function township_get_article($db, $source, $url, $article, $features)
{
	global $config;

	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t township_get_article()");
	}
	
	// read data
	$user_agent = "WikiDaheim/0.0.0 (wikidaheim.at)";
	ini_set('user_agent', $user_agent);
	$str = @file_get_contents($url);

	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t file_get_contents: \t ".$url);
	}
	
	if($str === FALSE)
	{
		return "connection error";
	}
	else
	{
		$str = $db->real_escape_string($str);

		$commonscat = township_get_commonscat($db, $str, $article);
		township_get_municipalityid($db, $str, $article);
		
		$sql = "SELECT `online` FROM `" . $config['dbprefix'] . $source . "_township_data` WHERE `article` LIKE '$article'";
		$res = $db->query($sql);
		
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
		
		$num_names = $res->num_rows;
		if ($num_names < 1) // new
		{
			$res->close();
			
			$sql = "INSERT INTO " . $config['dbprefix'] . $source . "_township_data(article, online, commonscat) VALUES ('$article', 3, '$commonscat')"; 
			$db->query($sql);
			
			if($config['log'] > 2)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
			}
		} // end new
		else if($num_names == 1) // in db
		{
			$row = $res->fetch_array(MYSQLI_ASSOC);
			
			$online = 2;
			if($row['online'] == 0)
			{
				$online = 3;
			}
			$res->close();
						
			$sql = "UPDATE `" . $config['dbprefix'] . $source . "_township_data` SET online='$online', commonscat = '$commonscat' WHERE `article` LIKE '$article'";
			$db->query($sql);
						
			if($config['log'] > 2)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
			}
		} // end in db
		else // duplicate
		{
			$res->close();
			
			if($config['log'] > 0)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t error \t duplicate: $article \t township_get_article()");
			}
			
			$sql = "DELETE FROM `" . $config['dbprefix'] . $source . "_township_data` WHERE `article` LIKE '$article'";
			$db->query($sql);
			
			if($config['log'] > 2)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
			}
			
			$sql = "INSERT INTO " . $config['dbprefix'] . $source . "_township_data(article, online, commonscat) VALUES ('$article', 3, '$commonscat')"; 
			$db->query($sql);
			
			if($config['log'] > 2)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
			}
		} // duplicate
		
		// analyse data		
		$feature_info = "";
		foreach($features as $key => $feature)
		{
			$feature_info .= umlaute($key) . " = '" . township_has_feature($str, $feature) . "', ";
		}
		
		$sql = "UPDATE `" . $config['dbprefix'] . $source . "_township_data` SET ".$feature_info." online='2', data_update=CURRENT_TIMESTAMP WHERE `article` LIKE '$article'";
		$db->query($sql);
					
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
	} // get data
}


function township_get_main($db, $source)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t township_get_main()");
	}
	
	$sql = "UPDATE `" . $config['dbprefix'] . $source . "_township_data` SET `online`='1' WHERE `online`='2'";
	$db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$sql = "UPDATE `" . $config['dbprefix'] . "commons_commonscat` SET `online`='1' WHERE `online`='2'";
	$db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	
	$articles = township_get_articles_list($db, $source);
	$features = township_get_feature_list($db, $source);
	
	
	$sql = "SELECT `data` FROM `" . $config['dbprefix'] . "source_config` WHERE `key` LIKE 'api_url' AND `wiki` LIKE '" . $source . "'";
	$res = $db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$row = $res->fetch_array(MYSQLI_ASSOC);
	$api_url = $row['data'];
	$res->close();
	
	foreach($articles as $article)
	{
		// api query
		$url = $api_url . '?format=xml&action=query&prop=revisions&rvprop=content&titles=' . urlencode($article);
		
		// read data and save to db
		if(township_get_article($db, $source, $url, $article, $features) == "connection error")
		{
			if($config['log'] > 0)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t error \t connection error \t wiki_get_main()");
			}
			return "ERROR";
		}
		
		// wikidata
		$sql = "SELECT `wikidata` FROM `" . $config['dbprefix'] . "gemeinde_geo` WHERE `gemeinde` LIKE '$article'";
		$res = $db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
		while($row = $res->fetch_array(MYSQLI_ASSOC))
		{
			if($row['wikidata'] == "")
			{
				$url = $api_url . "?action=query&format=xml&prop=pageprops&ppprop=wikibase_item&redirects=1&titles=" . urlencode($article);
				if(township_get_wikidata($db,$source,$url,$article) == "connection error")
				{
					if($config['log'] > 0)
					{
						append_file("log/cron.txt","\n".date(DATE_RFC822)."\t error \t connection error \t wiki_get_main()");
					}
					return "ERROR";
				}
			}
		}
		
		$sql = "UPDATE `" . $config['dbprefix'] . $source . "_township` SET online='1' WHERE `article` LIKE '$article'";
		$db->query($sql);
					
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
	}
	
	// set online to 0
	$sql = "UPDATE `" . $config['dbprefix'] . $source . "_township_data` SET `online`='0' WHERE `online`='1'";
	$db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}

	$sql = "UPDATE `" . $config['dbprefix'] . $source . "_township_features` SET `online`='1' WHERE `online`='3' OR `online`='2'";
	$db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$sql = "UPDATE `" . $config['dbprefix']. "commons_commonscat` SET `online` = 0 WHERE `data_update` <= DATE_SUB(NOW(),INTERVAL 12 HOUR)";
	$db->query($sql);
			
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
}

?>