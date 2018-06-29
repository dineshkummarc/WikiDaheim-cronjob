<?php

function list_get_main_list(&$db, $source)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t list_get_articles_list()");
	}
	
	$sql = "SELECT `category` FROM `" . $config['dbprefix'] . $source . "_main` WHERE `online` >= 1";
	$res = $db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$articles = array();
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$articles[] = $db->real_escape_string($row['category']);
	}
	
	$res->free();
	
	return $articles;
}

function list_get_articles(&$db, $source, $url, $article)
{
	global $config;

	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t list_get_articles()");
	}
	
	// read data
	$user_agent = $config['user_agent'];
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
		$str = str_replace("query-continue","continue",$str);
		$xml = new SimpleXMLElement($str);

		// get continue value
		$continue = "";
		if(isset($xml->continue))
		{
			foreach($xml->continue->attributes() as $element => $value)
			{
				if($element == "eicontinue")
				{
	    			$continue = $value;
				}
			}
		} // end continue value
		
		// loop to get townships
		foreach($xml->query->embeddedin->ei as $list)
		{
			foreach($list->attributes() as $element => $value)
			{
				if($element == "title")
				{
					$value = $db->real_escape_string($value);
					
					$sql = "SELECT `online` FROM `" . $config['dbprefix'] . $source . "_list` WHERE `article` = '$value'";
					$res = $db->query($sql);
					
					if($config['log'] > 2)
					{
						append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
					}
					
					$num_names = $res->num_rows;
					if ($num_names < 1) // new
					{
						$res->free();
						
						$sql = "INSERT INTO " . $config['dbprefix'] . $source . "_list(article, online, data_update) VALUES ('$value', 3, CURRENT_TIMESTAMP)"; 
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
						
						$res->free();
						
						$sql = "UPDATE `" . $config['dbprefix'] . $source . "_list` SET online='$online', data_update=CURRENT_TIMESTAMP WHERE `article` LIKE '$value'";
						$db->query($sql);
						
						if($config['log'] > 2)
						{
							append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
						}
					} // end in db
					else // duplicate
					{
						$res->free();
						
						if($config['log'] > 0)
						{
							append_file("log/cron.txt","\n".date(DATE_RFC822)."\t error \t duplicate: $value \t get_townships()");
						}
						
						$sql = "DELETE FROM `" . $config['dbprefix'] . $source . "_list` WHERE `article` LIKE '$value'";
						$db->query($sql);
						
						if($config['log'] > 2)
						{
							append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
						}
						
						$sql = "INSERT INTO " . $config['dbprefix'] . $source . "_list(article, online, data_update) VALUES ('$value', 3, CURRENT_TIMESTAMP)"; 
						$db->query($sql);
						
						if($config['log'] > 2)
						{
							append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
						}
					} // end duplicate
					
				} // end title
			} // end attributes
		} // end get townships
	} // end get data
	
	return $continue;
}

// exclude lists & categotys
function list_exclude_list(&$db, $source)
{
	global $config;

	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t list_exclude_list()");
	}
	
	$sql = "SELECT `data` FROM `" . $config['dbprefix'] . $source . "_config` WHERE `key` LIKE 'source' AND `type` LIKE 'exclude'";
	$res = $db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$exclude = array();
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$exclude[] = $db->real_escape_string($row['data']);
	}
	
	$res->free();
	
	foreach($exclude as $value)
	{
		$sql = "UPDATE `" . $config['dbprefix'] . $source . "_list` SET online='0' WHERE `article` LIKE '$value'";
		$db->query($sql);
		
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
	}
}

// include lists & categotys
function list_include_list(&$db, $source)
{
	global $config;

	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t list_include_list()");
	}
	
	$sql = "SELECT `data` FROM `" . $config['dbprefix'] . $source . "_config` WHERE `key` LIKE 'source' AND `type` LIKE 'include'";
	$res = $db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$include = array();
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$include[] = $db->real_escape_string($row['data']);
	}
	
	$res->free();
	
	foreach($include as $value)
	{
		$sql = "UPDATE `" . $config['dbprefix'] . $source . "_list` SET online='0' WHERE `article` NOT LIKE '$value'";
		$db->query($sql);
		
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
	}
}

function list_base_get_main(&$db, $source)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t list_get_main()");
	}
	
	$sql = "UPDATE `" . $config['dbprefix'] . $source . "_list` SET `online`='1' WHERE `online`='2'";
	$db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$articles = list_get_main_list($db, $source);
	
	
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
		
		$url = $api_url . '?action=query&list=embeddedin&format=xml&einamespace=0|4&eititle=' . urlencode($article) ."&continue";
		
		// read data and save to db
		$continue = list_get_articles($db, $source, $url, $article);
		while($continue != "") // loop while api gives data
		{
			if($continue == "connection error")
			{
				// log error
				if($config['log'] > 0)
				{
					append_file("log/cron.txt","\n".date(DATE_RFC822)."\t error \t connection error \t list_get_main()");
				}
				return "ERROR";
			}
			// read data and save to db
			$continue = list_get_articles($db, $source, $url."=-||&eicontinue=".$continue, $article);
		} // api loop
	}
	
	list_exclude_list($db, $source);
	list_include_list($db, $source);
	
	// set online to 0
	$sql = "UPDATE `" . $config['dbprefix'] . $source . "_list` SET `online`='0' WHERE `online`='1'";
	$db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
}

?>