<?php

function commons_get_categorys_list($db)
{
	global $config;
	
	$categorys = array();
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t commons_get_categorys_list()");
	}
	
	$sql = "SELECT `commons_gemeinde`, `online` FROM `" . $config['dbprefix'] . "commons_commonscat` WHERE `online` >= 2 ORDER BY `online` DESC";
	
	$res = $db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$categorys[] = $row['commons_gemeinde'];
	}
	
	$res->close();
	
	return $categorys;
}

function commons_get_feature_alias_cat($db, $url, $base_category, $api_url, $feature)
{
	global $config;

	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t commons_get_feature_alias_cat()");
	}
	
	// read data
	$user_agent = "WikiDaheim/0.0.0 (wikidaheim.at)";
	ini_set('user_agent', $user_agent);
	$str = @file_get_contents($url);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t commons_get_feature_alias_cat: \t ".$url);
	}
	
	if($str === FALSE)
	{
		return "";
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
		
		// loop to get subcat
		foreach($xml->query->categorymembers->cm as $fotos)
		{
			$attribute = "type";
			$type = (string)$fotos->attributes()->$attribute;
			$attribute = "title";
			$title = (string)$fotos->attributes()->$attribute;
			
			if($type == "subcat")
			{
				$online = 0;
				$title = $db->real_escape_string($title);
				$sql = "SELECT `online` FROM `" . $config['dbprefix'] . "commons_feature` WHERE `feature` LIKE '$feature' AND `commons_string` LIKE '$title'";
				$res = $db->query($sql);
				if($config['log'] > 2)
				{
					append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
				}
				
				$sql = "INSERT INTO `" . $config['dbprefix'] . "commons_feature` (`feature`, `commons_string`, `data_update`, `online`) VALUES ('$feature','$title',CURRENT_TIMESTAMP,2) ";
				while($row = $res->fetch_array(MYSQLI_ASSOC))
				{
					$sql = "UPDATE `" . $config['dbprefix'] . "commons_feature` SET `data_update`=CURRENT_TIMESTAMP, `online`=2 WHERE `feature` LIKE '$feature' AND `commons_string` LIKE '$title' ";
					$online = $row['online'];
				}
				$res->close();
				
				if($online != 2)
				{
					$db->query($sql);
					if($config['log'] > 2)
					{
						append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
					}
					
					$url = $api_url . '?action=query&list=categorymembers&format=xml&cmtitle=' . urlencode($title) . '&cmlimit=max&cmprop=title|type&continue';
					
					$continue = commons_get_feature_alias_cat($db, $url, $base_category, $api_url, $feature);
		
					while($continue != "") // loop while api gives data
					{
						if($continue == "connection error")
						{
							// log error
							if($config['log'] > 0)
							{
								append_file("log/cron.txt","\n".date(DATE_RFC822)."\t error \t connection error \t commons_get_cat()");
							}
							return "ERROR";
						}
						$continue = commons_get_feature_alias_cat($db, $url."=-||&cmcontinue=".$continue, $base_category, $api_url, $feature);
					} // end api loop
				}
			}
			else if ($type == "file")
			{
				$file_online = 0;
				$title = $db->real_escape_string($title);
				$sql = "SELECT `online` FROM `" . $config['dbprefix'] . "commons_feature_photos` WHERE `feature` LIKE '$feature' AND `photo` LIKE '$title'";
				$res = $db->query($sql);
				if($config['log'] > 2)
				{
					append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
				}
				
				$sql = "INSERT INTO `" . $config['dbprefix'] . "commons_feature_photos` (`feature`, `photo`, `data_update`, `online`) VALUES ('$feature','$title',CURRENT_TIMESTAMP,2) ";
				while($row = $res->fetch_array(MYSQLI_ASSOC))
				{
					$sql = "UPDATE `" . $config['dbprefix'] . "commons_feature_photos` SET `data_update`=CURRENT_TIMESTAMP, `online`=2 WHERE `feature` LIKE '$feature' AND `photo` LIKE '$title' ";
					$file_online = $row['online'];
				}
				$res->close();
				
				if($file_online != 2)
				{
					$db->query($sql);
					if($config['log'] > 2)
					{
						append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
					}
				}
			}
		}
	}
	
	return $continue;
}

function commons_get_feature_cat($db, $api_url)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t commons_get_feature_cat()");
	}
	
	
	$sql = "SELECT `feature` FROM `" . $config['dbprefix'] . "commons_photos_features` WHERE `online` = 1 OR `online` = 3";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$features = array();
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{ 
		$features[] = $row['feature'];
	}
	$res->close();
	
	foreach($features as $feature)
	{
		$sql = "UPDATE `" . $config['dbprefix'] . "commons_feature` SET `online`=1 WHERE `feature` LIKE '".$feature."'";
		$db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
	
		$sql = "UPDATE `" . $config['dbprefix'] . "commons_feature_photos` SET `online`=1 WHERE `feature` LIKE '".$feature."'";
		$db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
		
		
		$sql = "SELECT `alias` FROM `" . $config['dbprefix'] . "commons_photos_features_alias` WHERE `feature` LIKE '".$feature."'";
		$res = $db->query($sql);
	
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
		
		while($row = $res->fetch_array(MYSQLI_ASSOC))
		{
			$category = $row['alias'];
			$url = $api_url . '?action=query&list=categorymembers&format=xml&cmtitle=' . urlencode($category) . '&cmlimit=max&cmprop=title|type&continue';
			
			$continue = commons_get_feature_alias_cat($db, $url, $category, $api_url, $feature);
			while($continue != "") // loop while api gives data
			{
				if($continue == "connection error")
				{
					// log error
					if($config['log'] > 0)
					{
						append_file("log/cron.txt","\n".date(DATE_RFC822)."\t error \t connection error \t list_get_main()");
					}
				}
				else
				{
					$continue = commons_get_feature_alias_cat($db, $url."=-||&eicontinue=".$continue, $category, $api_url, $feature);
				}
			} // api loop
		}
	
		$res->close();
		
		// set online for feature
		$sql = "SELECT `online` FROM `" . $config['dbprefix'] . "commons_photos_features` WHERE `feature` LIKE '".$feature."'";
		$res = $db->query($sql);
	
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
		
		$row = $res->fetch_array(MYSQLI_ASSOC);
		$online = $row['online'];
		
		if($online == 1)
		{
			$sql = "UPDATE `" . $config['dbprefix'] . "commons_photos_features` SET `online` = 2 WHERE `feature` LIKE '".$feature."'";
		}
		else
		{
			$sql = "UPDATE `" . $config['dbprefix'] . "commons_photos_features` SET `online` = 4 WHERE `feature` LIKE '".$feature."'";
		}
		
		$db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
	}
	
	$sql = "DELETE FROM `" . $config['dbprefix'] . "commons_feature` WHERE `online` = 1";
	$db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$sql = "DELETE FROM `" . $config['dbprefix'] . "commons_feature_photos` WHERE `online` = 1";
	$db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
}

function commons_get_feature_cat_db($db)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t commons_get_feature_cat_db()");
	}
	
	$feature_alias = array();
	
	$sql = "SELECT `feature` FROM `" . $config['dbprefix'] . "commons_photos_features` WHERE `online` >= 1";
	$res = $db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$feature_alias[] = $row['feature'];
	}
	
	$res->close();
	
	return $feature_alias;
}

function commons_feature_exists($db, $features, $commons_gemeinde)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t commons_feature_exists()");
	}
	
	$sql = "DELETE FROM `" . $config['dbprefix'] . "commons_gemeide_feature` WHERE `commons_gemeinde` LIKE '$commons_gemeinde'";
	$db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	
	foreach($features as $feature_name)
	{
		
		$sql = "UPDATE `" . $config['dbprefix'] . "commons_commonscat` SET `$feature_name`='0' WHERE `commons_gemeinde`='$commons_gemeinde' AND `$feature_name` = 1";
		$db->query($sql);

		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
		
		$features_exists = 0;
		$max = 3;
		while($max >= 0)
		{
			$max--;
			$sql = "SELECT `gemeinde`.`commons_feature` FROM (SELECT `commons_feature` FROM `" . $config['dbprefix'] . "commons_photos` WHERE `commons_gemeinde` LIKE '$commons_gemeinde' AND `level` = '$max' AND `online` = 2 AND `commons_feature` NOT LIKE '$commons_gemeinde') AS `gemeinde` INNER JOIN (SELECT `commons_string` FROM `" . $config['dbprefix'] . "commons_feature` WHERE `feature` LIKE '$feature_name') AS `commons` ON `gemeinde`.`commons_feature` = `commons`.`commons_string`";
		
			$res = $db->query($sql);
			if($config['log'] > 2)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
			}
		
			while($row = $res->fetch_array(MYSQLI_ASSOC))
			{
				$category = $row['commons_feature'];
			
				$features_exists++;
			
				$sql = "UPDATE `" . $config['dbprefix'] . "commons_commonscat` SET `$feature_name`='1' WHERE `commons_gemeinde`='$commons_gemeinde'";
				$db->query($sql);
				if($config['log'] > 2)
				{
					append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
				}
				
				$sql = "INSERT INTO `" . $config['dbprefix'] . "commons_gemeide_feature` (`commons_gemeinde`, `feature`, `commons_feature`, `online`) VALUES ('$commons_gemeinde', '$feature_name', '$category', 2)";
				$db->query($sql);
				if($config['log'] > 2)
				{
					append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
				}
			}
			$res->close();
		
			if($features_exists != 0)
			{
				break;
			}
		}
	}
}

function commons_get_fotos($db, $url, $api_url, $category, $main_category, $features, $max = 4, $max_fotos = 6)
{
	$max--;
	global $config;

	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t commons_get_fotos()");
	}
	
	if($max == 3)
	{
		foreach($features as $feature)
		{
			$sql = "UPDATE `" . $config['dbprefix'] . "commons_commonscat` SET `$feature`='0' WHERE `commons_gemeinde`='$commons_gemeinde' AND `$feature` = 2";
			$db->query($sql);

			if($config['log'] > 2)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
			}
		}
	}
	
	// read data
	$user_agent = "WikiDaheim/0.0.0 (wikidaheim.at)";
	ini_set('user_agent', $user_agent);
	$str = @file_get_contents($url);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t commons_get_fotos: \t ".$url);
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
		
		// loop to get fotos
		foreach($xml->query->categorymembers->cm as $fotos)
		{
			$attribute = "type";
			$type = $db->real_escape_string((string)$fotos->attributes()->$attribute);
			$attribute = "title";
			$title = $db->real_escape_string((string)$fotos->attributes()->$attribute);
			
			if($type == "subcat")
			{	
				if($max <= 0)
				{
					// skip
				}
				else
				{
					// api
					$url = $api_url . '?action=query&list=categorymembers&format=xml&cmtitle=' . urlencode($title) . '&cmlimit=max&cmprop=title|type&continue';
		
					$continue = commons_get_fotos($db, $url, $api_url, $title, $main_category, $features, $max, 1);
		
					while($continue != "") // loop while api gives data
					{
						if($continue == "connection error")
						{
							// log error
							if($config['log'] > 0)
							{
								append_file("log/cron.txt","\n".date(DATE_RFC822)."\t error \t connection error \t commons_get_fotos()");
							}
							return "connection error";
						}
						// read data and save to db
						$continue = commons_get_fotos($db, $url."=-||&cmcontinue=".$continue, $api_url, $title, $main_category, $features, $max, 1);
					} // end api loop
				}
			}
			else if($type == "file")
			{
				if(
					($max == 3) ||
					(substr($title, -4, -1)===".og") ||
					(substr($title, -4)==".wav") ||
					(substr($title, -5)==".flac") ||
					(substr($title, -5)==".opus")
				)
				{
					$category = $db->real_escape_string($category);
					$main_category = $db->real_escape_string($main_category);
					$sql = "SELECT `feature` FROM `" . $config['dbprefix'] . "commons_feature_photos` WHERE `photo` LIKE '$title' AND `online` >= 1";

					$res = $db->query($sql);
					if($config['log'] > 2)
					{
						append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
					}
					
					$num_names = $res->num_rows;
					if ($num_names < 1)
					{
						$res->close();
					}
					else
					{
						$row = $res->fetch_array(MYSQLI_ASSOC);
						$feature = $row['feature'];
						$res->close();
						
						$sql = "SELECT `online` FROM `" . $config['dbprefix'] . "commons_photos` WHERE `name` LIKE '$title' AND `commons_gemeinde` LIKE '$main_category' AND `commons_feature` LIKE '$feature'";
						$res = $db->query($sql);
						if($config['log'] > 2)
						{
							append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
						}
				
						$num_names = $res->num_rows;
						$res->close();
						
						if ($num_names < 1)
						{
							$sql = "INSERT INTO `" . $config['dbprefix'] . "commons_photos` (`online`, `commons_gemeinde`, `commons_feature`, `name`, `data_update`, `level`) VALUES ( 2, '$main_category', '$feature', '$title', CURRENT_TIMESTAMP, $max)";
							$res = $db->query($sql);
							if($config['log'] > 2)
							{
								append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
							}
						}
						else if($num_names == 1)
						{
					
							$sql = "UPDATE `" . $config['dbprefix'] . "commons_photos` SET `online` = 2, `data_update` = CURRENT_TIMESTAMP, `level` = $max WHERE `name` LIKE '$title' AND `commons_gemeinde` LIKE '$main_category' AND `commons_feature` LIKE '$feature'";
							$res = $db->query($sql);
							if($config['log'] > 2)
							{
								append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
							}
						}
						else
						{
							if($config['log'] > 0)
							{
								append_file("log/cron.txt","\n".date(DATE_RFC822)."\t error \t duplicate: $value \t get_fotos()");
							}
					
							// try to fix that:
							$sql = "DELETE FROM `" . $config['dbprefix'] . "commons_photos` WHERE `name` LIKE '$title' AND `commons_gemeinde` LIKE '$main_category' AND `commons_feature` LIKE '$feature'";
							$db->query($sql);
							if($config['log'] > 2 )
							{
								append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
							}
							
							$sql = "INSERT INTO `" . $config['dbprefix'] . "commons_photos` (`online`, `commons_gemeinde`, `commons_feature`, `name`, `data_update`, `level`) VALUES ( 2, '$main_category', '$feature', '$title', CURRENT_TIMESTAMP, $max)";
							$res = $db->query($sql);
							if($config['log'] > 2)
							{
								append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
							}
						}
						
						$sql = "SELECT `$feature` FROM `" . $config['dbprefix'] . "commons_commonscat` WHERE `commons_gemeinde` LIKE '$main_category'";

						$res = $db->query($sql);
						if($config['log'] > 2)
						{
							append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
						}
					
						$row = $res->fetch_array(MYSQLI_ASSOC);
						$status = $row[$feature];
						$res->close();
						
						if($status == 0)
						{
							$sql = "UPDATE `" . $config['dbprefix'] . "commons_commonscat` SET `$feature`='2' WHERE `commons_gemeinde`='$main_category'";
							$db->query($sql);

							if($config['log'] > 2)
							{
								append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
							}
						}
					}
				}
				
				if($max_fotos > 0)
				{
					$max_fotos--;
					// new file?
					$category = $db->real_escape_string($category);
					$main_category = $db->real_escape_string($main_category);
					$sql = "SELECT `online` FROM `" . $config['dbprefix'] . "commons_photos` WHERE `name` LIKE '$title' AND `commons_gemeinde` LIKE '$main_category' AND `commons_feature` LIKE '$category'";

					$res = $db->query($sql);
					if($config['log'] > 2)
					{
						append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
					}
				
					$num_names = $res->num_rows;
					$res->close();
				
					if ($num_names < 1)
					{
						$sql = "INSERT INTO `" . $config['dbprefix'] . "commons_photos` (`online`, `commons_gemeinde`, `commons_feature`, `name`, `data_update`, `level`) VALUES ( 2, '$main_category', '$category', '$title', CURRENT_TIMESTAMP, $max)";
						$res = $db->query($sql);
						if($config['log'] > 2)
						{
							append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
						}
					}
					else if($num_names == 1)
					{
					
						$sql = "UPDATE `" . $config['dbprefix'] . "commons_photos` SET `online` = 2, `data_update` = CURRENT_TIMESTAMP, `level` = $max WHERE `name` LIKE '$title' AND `commons_gemeinde` LIKE '$main_category' AND `commons_feature` LIKE '$category'";
						$res = $db->query($sql);
						if($config['log'] > 2)
						{
							append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
						}
					}
					else
					{
						if($config['log'] > 0)
						{
							append_file("log/cron.txt","\n".date(DATE_RFC822)."\t error \t duplicate: $value \t get_fotos()");
						}
					
						// try to fix that
						$sql = "DELETE FROM `" . $config['dbprefix'] . "commons_photos` WHERE `name` LIKE '$title' AND `commons_gemeinde` LIKE '$main_category' AND `commons_feature` LIKE '$category'";
						$db->query($sql);
					
						if($config['log'] > 2 )
						{
							append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
						}
					
						$sql = "INSERT INTO `" . $config['dbprefix'] . "commons_photos` (`online`, `commons_gemeinde`, `commons_feature`, `name`, `data_update`, `level`) VALUES ( 2, '$main_category', '$category', '$title', CURRENT_TIMESTAMP, $max)";
						$res = $db->query($sql);
						if($config['log'] > 2)
						{
							append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
						}
					}
				}
			}
		} // end fotos
	} // connection ok
	
	return $continue;
}

function commons_get_main(&$db, $api_url)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t commons_get_main()");
	}
	
	$features = array();
	$categorys = commons_get_categorys_list($db);
	
	if(count($categorys) == 0)
	{
		commons_get_feature_cat($db, $api_url);
		
		$sql = "UPDATE `" . $config['dbprefix'] . "commons_photos` SET `online`='1' WHERE `online`='2'";
		$db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
		
		$sql = "UPDATE `" . $config['dbprefix'] . "commons_commonscat` SET `online`='2' WHERE `online`='1'";
		$db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
		
		$categorys = commons_get_categorys_list($db);
	}
	$features = commons_get_feature_cat_db($db);
	
	foreach($categorys as $category)
	{
		// api query
		$url = $api_url . '?action=query&list=categorymembers&format=xml&cmtitle=Category:' . urlencode($category) . '&cmlimit=max&cmprop=title|type&continue';
		
		$continue = commons_get_fotos($db, $url, $api_url, $category, $category, $features, 4);
		
		while($continue != "") // loop while api gives data
		{
			if($continue == "connection error")
			{
				// log error
				if($config['log'] > 0)
				{
					append_file("log/cron.txt","\n".date(DATE_RFC822)."\t error \t connection error \t commons_get_main()");
				}
				return "ERROR";
			}
			// read data and save to db
			$continue = commons_get_fotos($db, $url."=-||&cmcontinue=".$continue, $api_url, $category, $category, $features, 4);
		} // end api loop
		
		// clean up
		$sql = "DELETE FROM `" . $config['dbprefix'] . "commons_photos` WHERE `commons_gemeinde` LIKE '$category' AND `online`!='2' ";
		$db->query($sql);

		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
		
		// if category in $features
		commons_feature_exists($db, $features, $category);
		
		$sql = "UPDATE `" . $config['dbprefix'] . "commons_commonscat` SET `online`='1' WHERE `commons_gemeinde`='$category'";
		$db->query($sql);

		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
	}
	
	$sql = "UPDATE `" . $config['dbprefix'] . "commons_photos_features` SET `online` = 1 WHERE `online` = 2 OR `online` = 4";
	$db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$sql = "UPDATE `" . $config['dbprefix'] . "commons_feature` SET `online`=1 WHERE 1";
	$db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}

	$sql = "UPDATE `" . $config['dbprefix'] . "commons_feature_photos` SET `online`=1 WHERE 1";
	$db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
}

?>