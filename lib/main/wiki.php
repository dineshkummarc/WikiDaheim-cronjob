<?php

// get categories from db
function township_get_main_list(&$db, $source)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t township_get_main_list()");
	}
	
	$sql = "SELECT `category` FROM `" . $config['dbprefix'] . $source . "_main` WHERE `online` >= 1";
	$res = $db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$categorys = array();
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$categorys[] = $db->real_escape_string($row['category']);
	}
	
	$res->free();
	
	return $categorys;
}

// get data from wiki and save in db
function township_get_townships(&$db, $source, $url, $townships)
{
	global $config;

	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t township_get_townships()");
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
				if($element == "cmcontinue")
				{
	    			$continue = $value;
				}
			}
		} // end continue value
		
		// loop to get townships
		foreach($xml->query->categorymembers->cm as $township)
		{
			foreach($township->attributes() as $element => $value)
			{
				if($element == "title")
				{
					$value = $db->real_escape_string($value);
					
					$sql = "SELECT `online` FROM `" . $config['dbprefix'] . $source . "_township` WHERE `article` = '$value'";
					$res = $db->query($sql);
					
					if($config['log'] > 2)
					{
						append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
					}
					
					$num_names = $res->num_rows;
					if ($num_names < 1) // new
					{
						$res->free();
						
						$sql = "INSERT INTO " . $config['dbprefix'] . $source . "_township(article, online, data_update) VALUES ('$value', 3, CURRENT_TIMESTAMP)"; 
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
						
						$sql = "UPDATE `" . $config['dbprefix'] . $source . "_township` SET online='$online', data_update=CURRENT_TIMESTAMP WHERE `article` LIKE '$value'";
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
							append_file("log/cron.txt","\n".date(DATE_RFC822)."\t error \t duplicate: $value \t township_get_townships()");
						}
						
						$sql = "DELETE FROM `" . $config['dbprefix'] . $source . "_township` WHERE `article` LIKE '$value'";
						$db->query($sql);
						
						if($config['log'] > 2)
						{
							append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
						}
						
						$sql = "INSERT INTO " . $config['dbprefix'] . $source . "_township(article, online, data_update) VALUES ('$value', 3, CURRENT_TIMESTAMP)"; 
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
function township_exclude_townships(&$db, $source)
{
	global $config;

	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t township_exclude_townships()");
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
		$sql = "UPDATE `" . $config['dbprefix'] . $source . "_township` SET online='0' WHERE `article` LIKE '$value'";
		$db->query($sql);
		
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
	}
}

// include lists & categotys
function township_include_townships(&$db, $source)
{
	global $config;

	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t township_include_townships()");
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
		$sql = "UPDATE `" . $config['dbprefix'] . $source . "_township` SET online='0' WHERE `article` NOT LIKE '$value'";
		$db->query($sql);
		
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
	}
}

// get article adresses from wp
function township_base_get_main(&$db, $source)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t base_get_main()");
	}
	
	// return if open towns
	$todo = 0;
	$sql = "SELECT `online`, count(*) AS `todo` FROM `" . $config['dbprefix'] . $source . "_township` WHERE `online` = 2 GROUP BY `online`";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$todo = $db->real_escape_string($row['todo']);
	}
	$res->free();
	if($todo != 0)
	{
		return;
	}
	
	
	$sql = "UPDATE `" . $config['dbprefix'] . $source . "_main` SET `online`='1' WHERE `online`='2'";
	$db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$main = township_get_main_list($db, $source);
	
	
	$sql = "UPDATE `" . $config['dbprefix'] . $source . "_township` SET `online`='1' WHERE `online`='2'";
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
	
	foreach($main as $townships)
	{
		// api query
		$url = $api_url . '?action=query&list=categorymembers&format=xml&cmtitle=' . urlencode($townships) . '&cmprop=title&continue';
		
		// read data and save to db
		$continue = township_get_townships($db, $source, $url, $townships);
		while($continue != "") // loop while api gives data
		{
			if($continue == "connection error")
			{
				// log error
				if($config['log'] > 0)
				{
					append_file("log/cron.txt","\n".date(DATE_RFC822)."\t error \t connection error \t base_get_main()");
				}
				return "ERROR";
			}
			// read data and save to db
			$continue = township_get_townships($db, $source, $url."=-||&cmcontinue=".$continue, $townships);
		} // api loop
	} // townships loop
	
	township_include_townships($db, $source);
	township_exclude_townships($db, $source);
	
	// set online to 0 for townships that are no longer in this category
	$sql = "UPDATE `" . $config['dbprefix'] . $source . "_township` SET `online`='0' WHERE `online`='1'";
	$db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
}

?>