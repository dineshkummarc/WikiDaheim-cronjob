<?php

// get categories from db
function list_get_articles_list($db, $source)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t list_get_articles_list()");
	}
	
	$sql = "SELECT `article` FROM `" . $config['dbprefix'] . $source . "_list` WHERE `online` >= 1";
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

function list_get_feature_alias_list($db, $source, $features)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t list_get_feature_alias_list()");
	}
	
	$features_alias[] = array();
	
	foreach($features as $feature)
	{
		$feature_alias[$feature][$feature] = "";
		
		$sql = "SELECT `alias` FROM `" . $config['dbprefix'] . $source . "_list_features_alias` WHERE `feature` LIKE '".$feature."'";
		$res = $db->query($sql);
	
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
		
		while($row = $res->fetch_array(MYSQLI_ASSOC))
		{
			
			$feature_alias[$feature][strtolower(str_replace(" ","", $db->real_escape_string($row['alias'])))] = "";
		}
	
		$res->close();
	}
	
	return $feature_alias;
}

function list_get_feature_list($db, $source)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t list_get_feature_list()");
	}
	
	$sql = "SELECT `feature` FROM `" . $config['dbprefix'] . $source . "_list_features` WHERE `online` >= 1";
	$res = $db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$features = array();
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{ 
		$features[] = strtolower(str_replace(" ","", $db->real_escape_string($row['feature'])));
	}
	
	$res->close();
	
	return list_get_feature_alias_list($db, $source, $features);
}

function list_get_id($db, $source)
{
	global $config;

	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t list_get_id()");
	}
	
	$sql = "SELECT `data` FROM `" . $config['dbprefix'] . $source . "_config` WHERE `key` LIKE 'main' AND `type` LIKE 'id'";
	$res = $db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$id = array();

	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$id[$db->real_escape_string($row['data'])] = "";
	}
	$res->close();
	
	return $id;
}

function list_get_image_requested($db, $source)
{
	global $config;

	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t list_get_image_requested()");
	}
	
	$sql = "SELECT `data` FROM `" . $config['dbprefix'] . $source . "_config` WHERE `key` LIKE 'body' AND `type` LIKE 'image_requested'";
	$res = $db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$image_requested = array();

	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$image_requested[$db->real_escape_string($row['data'])] = "";
	}
	$res->close();
	
	return $image_requested;
}

function wiki_remove_link_and_html($data)
{
	preg_match_all('/\[\[(.*?)\]\]/',$data,$erg);

	$loop_count = count($erg[0]);
	$loop = 0;
	while($loop < $loop_count)
	{
		$url = explode("|",$erg[1][$loop]);
		if(count($url)>1)
		{
			$data = str_replace($erg[0][$loop], " ". $url[1] . " ", $data);
		}
		else
		{
			$data = str_replace($erg[0][$loop], " " . $erg[1][$loop] . " ", $data);
		}
		$loop++;
	}
	
	$data = html_entity_decode($data);
	
	return $data;
}

function wiki_to_dbhtml($data)
{	
	// links
	preg_match_all('/\[\[(.*?)\]\]/',$data,$erg);
	
	$loop_count = count($erg[0]);
	$loop = 0;
	while($loop < $loop_count)
	{
		$url = explode("|",$erg[1][$loop]);
		if(count($url)>1)
		{
			$data = str_replace($erg[0][$loop], "<a href=\"https://de.wikipedia.org/wiki/" . $url[0] . "\" target=\"_blank\">" . $url[1] . "</a>", $data);
		}
		else
		{
			$data = str_replace($erg[0][$loop], "<a href=\"https://de.wikipedia.org/wiki/" . $erg[1][$loop] . "\" target=\"_blank\">" . $erg[1][$loop] . "</a>", $data);
		}
		$loop++;
	}
	
	// []
	preg_match_all('/\[(.*?)\]/',$data,$erg);

	$loop_count = count($erg[0]);
	$loop = 0;
	while($loop < $loop_count)
	{
		$data = str_replace($erg[0][$loop], " ", $data);
		$loop++;
	}
	
	// &amp; -> &
	$data = str_replace("&amp;","&",$data);
	
	// remove {{foo|bar}}
	$data = preg_replace("/\{\{(.*)\}\}/Uis", "", $data);
	
	// }}
	$pos1 = stripos($data, "}}");
	if ($pos1 !== false)
	{
		$data = substr($data,0,$pos1);
	}
	
	$pos1 = stripos($data, "{{");
	if ($pos1 !== false)
	{
		$data = substr($data,0,$pos1);
	}
	
	// spaces
	$data = trim($data);
	
	return $data;
}

function str_to_data($data)
{
	$data = preg_replace("/&lt;(.*)&gt;/Uis","",$data);
	return $data;
}

function list_get_article($db, $source, $url, $article, $features)
{
	global $config;

	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t list_get_article()");
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
		
		// foot
		$sql = "SELECT `data` FROM `" . $config['dbprefix'] . $source . "_config` WHERE `key` LIKE 'foot' AND `type` LIKE 'main'";
		$res = $db->query($sql);
		
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
		
		$row = $res->fetch_array(MYSQLI_ASSOC);
		$split = $row['data'];
		$res->close();
		
		
		$str = str_ireplace($split,$split,$str);
		
		$data_array = explode($split, $str);
		$str = $data_array[0];
		
		
		// head
		$sql = "SELECT `data` FROM `" . $config['dbprefix'] . $source . "_config` WHERE `key` LIKE 'head' AND `type` LIKE 'main'";
		$res = $db->query($sql);
		
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
		
		$row = $res->fetch_array(MYSQLI_ASSOC);
		$split = $row['data'];
		$res->close();
		
		$str = str_ireplace($split,$split,$str);
		$data_array = explode($split, $str);
		if (count($data_array) > 1)
		{
			$str = $data_array[1];
		}
		else
		{
			// TODO ? catch redirect
			return;
		}
		
		// head feature
		$split = '}}';
		$data_array = explode($split, $str);
		$head = $data_array[0];

		$head = explode("|",$head);
		$eelements = count($head);
		$ei = 1;
		
		$sql = "SELECT `data` FROM `" . $config['dbprefix'] . $source . "_config` WHERE `key` LIKE 'head' AND `type` LIKE 'feature'";
		$res = $db->query($sql);
		
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
		
		$head_feature = array();
	
		while($row = $res->fetch_array(MYSQLI_ASSOC))
		{
			$head_feature[strtolower(str_replace(" ","", $db->real_escape_string($row['data'])))] = "";
		}
	
		$res->close();
		
		
		$has_gemeinde = 0;
		$head_gemeinde = "";
		while($ei < $eelements)
		{
			$edata = explode("=",str_replace(array("\\n"), '', $head[$ei]));
			$ei++;
			
			$key = strtolower(str_replace(" ","",$edata[0]));
			if (array_key_exists($key, $head_feature))
			{
				if($key=='gemeinde')
				{
					$has_gemeinde++;
					preg_match_all('/\[\[(.*?)\]\]/',$edata[1],$erg);
					$loop_count = count($erg[0]);
					$loop = 0;
					while($loop < $loop_count)
					{
						$url = explode("|",$erg[1][$loop]);
						$edata[1] = str_replace($erg[0][$loop], $url[0], $edata[1]);
						$loop++;
					}
					
					// exception $head_gemeinde == Wien | Linz
					if(strpos($article, 'Wien/') !== false)
					{
						if(strpos($article, 'Wien/Alsergrund') !== false){$head_gemeinde = array("Alsergrund");}
						else if(strpos($article, 'Wien/Brigittenau') !== false){$head_gemeinde = array("Brigittenau");}
						else if(strpos($article, 'Wien/Döbling') !== false){$head_gemeinde = array("Döbling");}
						else if(strpos($article, 'Wien/Donaustadt') !== false){$head_gemeinde = array("Donaustadt");}
						else if(strpos($article, 'Wien/Favoriten') !== false){$head_gemeinde = array("Favoriten");}
						else if(strpos($article, 'Wien/Floridsdorf') !== false){$head_gemeinde = array("Floridsdorf");}
						else if(strpos($article, 'Wien/Hernals') !== false){$head_gemeinde = array("Hernals");}
						else if(strpos($article, 'Wien/Hietzing') !== false){$head_gemeinde = array("Hietzing");}
						else if(strpos($article, 'Wien/Innere Stadt') !== false){$head_gemeinde = array("Innere Stadt");}
						else if(strpos($article, 'Wien/Josefstadt') !== false){$head_gemeinde = array("Josefstadt");}
						else if(strpos($article, 'Wien/Landstraße') !== false){$head_gemeinde = array("Landstraße");}
						else if(strpos($article, 'Wien/Leopoldstadt') !== false){$head_gemeinde = array("Leopoldstadt");}
						else if(strpos($article, 'Wien/Liesing') !== false){$head_gemeinde = array("Liesing");}
						else if(strpos($article, 'Wien/Margareten') !== false){$head_gemeinde = array("Margareten");}
						else if(strpos($article, 'Wien/Mariahilf') !== false){$head_gemeinde = array("Mariahilf");}
						else if(strpos($article, 'Wien/Meidling') !== false){$head_gemeinde = array("Meidling");}
						else if(strpos($article, 'Wien/Neubau') !== false){$head_gemeinde = array("Neubau");}
						else if(strpos($article, 'Wien/Ottakring') !== false){$head_gemeinde = array("Ottakring");}
						else if(strpos($article, 'Wien/Penzing') !== false){$head_gemeinde = array("Penzing");}
						else if(strpos($article, 'Wien/Rudolfsheim-Fünfhaus') !== false){$head_gemeinde = array("Rudolfsheim-Fünfhaus");}
						else if(strpos($article, 'Wien/Simmering') !== false){$head_gemeinde = array("Simmering");}
						else if(strpos($article, 'Wien/Währing') !== false){$head_gemeinde = array("Währing");}
						else if(strpos($article, 'Wien/Wieden') !== false){$head_gemeinde = array("Wieden");}
						else {$gemeinde = array("Wien");}
					}
					else if(strpos($article, 'Linz') !== false)
					{
						$head_gemeinde = array("Linz");
					}
					else
					{
						// multiple gemeinde
						$head_gemeinde = explode(",",$edata[1]);
					}
					
					// multiple gemeinde
					$edata[1] = $head_gemeinde[0];
				}
				if(str_replace(" ","",$edata[1]) != "")
				{
					$head_feature[$key] = $db->real_escape_string( wiki_to_dbhtml($edata[1]) );
				}
			}
		}
		
		
		// body
		$sql = "SELECT `data` FROM `" . $config['dbprefix'] . $source . "_config` WHERE `key` LIKE 'body' AND `type` LIKE 'main'";
		$res = $db->query($sql);
		
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
		
		$row = $res->fetch_array(MYSQLI_ASSOC);
		$split = $row['data'];
		$res->close();
		
		$str = str_ireplace($split,$split,$str);
		
		// clean string
		$str = str_to_data($str);
		
		$data_array = explode($split, $str);
		$elements = count($data_array);
		$i = 1;
		
		
		$image_requested = list_get_image_requested($db, $source);
		$id = list_get_id($db, $source);
		
		
		while($i < $elements)
		{
			$data = $data_array[$i];
			$i++;
			
			$data = explode("\\n|",$data);
			
			$eelements = count($data);
			$ei = 1;
			
			$sql_feature = "";
			$sql_value = "";
			$gemeinde = $head_gemeinde;
			$sql_feature_image_requested = "";
			$sql_value_image_requested = "";
			
			
			while($ei < $eelements)
			{
				$edata = explode("=",str_replace(array("\\n"), '', $data[$ei]));
				$ei++;
				
				$key = strtolower(str_replace(" ","",$edata[0]));
				
				if(isset($edata[1]))
				{
					if(str_replace(" ","",$edata[1]) != "")
					{
						foreach($features as $features_key => $feature)
						{	
							foreach($feature as $test_feature => $value)
							{
								if ($key == $test_feature)
								{
									if(($features_key=='name')|| ($features_key=='adresse'))
									{
										$edata[1] = wiki_remove_link_and_html($edata[1]);
									}
								
									if($features_key=='gemeinde')
									{
										$has_gemeinde++;
										preg_match_all('/\[\[(.*?)\]\]/',$edata[1],$erg);
										$loop_count = count($erg[0]);
										$loop = 0;
										while($loop < $loop_count)
										{
											$url = explode("|",$erg[1][$loop]);
											$edata[1] = str_replace($erg[0][$loop], $url[0], $edata[1]);
											$loop++;
										}
									
										// exception $gemeinde == Wien | Linz
										if(strpos($article, 'Wien/') !== false)
										{
											if(strpos($article, 'Wien/Alsergrund') !== false){$gemeinde = array("Alsergrund");}
											else if(strpos($article, 'Wien/Brigittenau') !== false){$gemeinde = array("Brigittenau");}
											else if(strpos($article, 'Wien/Döbling') !== false){$gemeinde = array("Döbling");}
											else if(strpos($article, 'Wien/Donaustadt') !== false){$gemeinde = array("Donaustadt");}
											else if(strpos($article, 'Wien/Favoriten') !== false){$gemeinde = array("Favoriten");}
											else if(strpos($article, 'Wien/Floridsdorf') !== false){$gemeinde = array("Floridsdorf");}
											else if(strpos($article, 'Wien/Hernals') !== false){$gemeinde = array("Hernals");}
											else if(strpos($article, 'Wien/Hietzing') !== false){$gemeinde = array("Hietzing");}
											else if(strpos($article, 'Wien/Innere Stadt') !== false){$gemeinde = array("Innere Stadt");}
											else if(strpos($article, 'Wien/Josefstadt') !== false){$gemeinde = array("Josefstadt");}
											else if(strpos($article, 'Wien/Landstraße') !== false){$gemeinde = array("Landstraße");}
											else if(strpos($article, 'Wien/Leopoldstadt') !== false){$gemeinde = array("Leopoldstadt");}
											else if(strpos($article, 'Wien/Liesing') !== false){$gemeinde = array("Liesing");}
											else if(strpos($article, 'Wien/Margareten') !== false){$gemeinde = array("Margareten");}
											else if(strpos($article, 'Wien/Mariahilf') !== false){$gemeinde = array("Mariahilf");}
											else if(strpos($article, 'Wien/Meidling') !== false){$gemeinde = array("Meidling");}
											else if(strpos($article, 'Wien/Neubau') !== false){$gemeinde = array("Neubau");}
											else if(strpos($article, 'Wien/Ottakring') !== false){$gemeinde = array("Ottakring");}
											else if(strpos($article, 'Wien/Penzing') !== false){$gemeinde = array("Penzing");}
											else if(strpos($article, 'Wien/Rudolfsheim-Fünfhaus') !== false){$gemeinde = array("Rudolfsheim-Fünfhaus");}
											else if(strpos($article, 'Wien/Simmering') !== false){$gemeinde = array("Simmering");}
											else if(strpos($article, 'Wien/Währing') !== false){$gemeinde = array("Währing");}
											else if(strpos($article, 'Wien/Wieden') !== false){$gemeinde = array("Wieden");}
											else {$gemeinde = array("Wien");}
										}
										else if(strpos($article, 'Linz') !== false)
										{
											$gemeinde = array("Linz");
										}
										else
										{
											// multiple gemeinde
											$gemeinde = explode(",",$edata[1]);
										}
										$edata[1] = $gemeinde[0];
									
									}
									// normal
									$sql_feature .= "`" . $features_key . "`, ";
									$sql_value .= "'" . $db->real_escape_string( wiki_to_dbhtml($edata[1]) ) . "', ";
									break 2;
								}
							}
						}
						if (array_key_exists($key, $image_requested))
						{
							$sql_feature_image_requested .= "`" . $key . "`, ";
							$sql_value_image_requested .= "'" . $db->real_escape_string( wiki_to_dbhtml($edata[1]) ) . "', ";
						}
						else if (array_key_exists($key, $id))
						{
							$id[$key] = $db->real_escape_string( wiki_to_dbhtml($edata[1]) );
						}
					}
				}
			}
			
			if($has_gemeinde == 0)
			{
				// exception $gemeinde == Wien | Linz
				if(strpos($article, 'Wien/') !== false)
				{
					if(strpos($article, 'Wien/Alsergrund') !== false){$gemeinde = array("Alsergrund");}
					else if(strpos($article, 'Wien/Brigittenau') !== false){$gemeinde = array("Brigittenau");}
					else if(strpos($article, 'Wien/Döbling') !== false){$gemeinde = array("Döbling");}
					else if(strpos($article, 'Wien/Donaustadt') !== false){$gemeinde = array("Donaustadt");}
					else if(strpos($article, 'Wien/Favoriten') !== false){$gemeinde = array("Favoriten");}
					else if(strpos($article, 'Wien/Floridsdorf') !== false){$gemeinde = array("Floridsdorf");}
					else if(strpos($article, 'Wien/Hernals') !== false){$gemeinde = array("Hernals");}
					else if(strpos($article, 'Wien/Hietzing') !== false){$gemeinde = array("Hietzing");}
					else if(strpos($article, 'Wien/Innere Stadt') !== false){$gemeinde = array("Innere Stadt");}
					else if(strpos($article, 'Wien/Josefstadt') !== false){$gemeinde = array("Josefstadt");}
					else if(strpos($article, 'Wien/Landstraße') !== false){$gemeinde = array("Landstraße");}
					else if(strpos($article, 'Wien/Leopoldstadt') !== false){$gemeinde = array("Leopoldstadt");}
					else if(strpos($article, 'Wien/Liesing') !== false){$gemeinde = array("Liesing");}
					else if(strpos($article, 'Wien/Margareten') !== false){$gemeinde = array("Margareten");}
					else if(strpos($article, 'Wien/Mariahilf') !== false){$gemeinde = array("Mariahilf");}
					else if(strpos($article, 'Wien/Meidling') !== false){$gemeinde = array("Meidling");}
					else if(strpos($article, 'Wien/Neubau') !== false){$gemeinde = array("Neubau");}
					else if(strpos($article, 'Wien/Ottakring') !== false){$gemeinde = array("Ottakring");}
					else if(strpos($article, 'Wien/Penzing') !== false){$gemeinde = array("Penzing");}
					else if(strpos($article, 'Wien/Rudolfsheim-Fünfhaus') !== false){$gemeinde = array("Rudolfsheim-Fünfhaus");}
					else if(strpos($article, 'Wien/Simmering') !== false){$gemeinde = array("Simmering");}
					else if(strpos($article, 'Wien/Währing') !== false){$gemeinde = array("Währing");}
					else if(strpos($article, 'Wien/Wieden') !== false){$gemeinde = array("Wieden");}
					else {$gemeinde = array("Wien");}
				}
				else if(strpos($article, 'Linz') !== false)
				{
					$gemeinde = array("Linz");
				}
				
				$sql_feature .= "`gemeinde`, ";
				$sql_value .= "'" . $gemeinde[0] . "', ";
			}
			
			// save
			$key = array_keys($id);
			if(($id[$key[0]] != "")||($key[0] == "no ID"))
			{
				if($key[0] != "no ID")
				{
					$sql = "DELETE FROM `" . $config['dbprefix'] . $source . "_list_data` WHERE `" . $key[0] . "` LIKE '" . $id[$key[0]]. "'";
					$db->query($sql);
					
					if($config['log'] > 2)
					{
						append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
					}
				}
				
				$sql = "INSERT INTO `" . $config['dbprefix'] . $source . "_list_data` (";

				foreach($head_feature as $element => $value)
				{
					if ($value != "")
					{
						$sql_feature .= "`" . $element . "`, ";
						$sql_value .= "'" . $value . "', ";
					}
				}
				
				$sql .= $sql_feature;
				if($key[0] != "no ID")
				{
					$sql .= "`$key[0]`, ";
				}
				$sql .= "`article`, `online`, `data_update`) VALUES (";
				$sql .= $sql_value;
				if($key[0] != "no ID")
				{
					$sql .= "'" . $id[$key[0]] . "', ";
				}
				$sql .= "'".$article."', '2', CURRENT_TIMESTAMP)";
				
				$db->query($sql);
	
				if($config['log'] > 2)
				{
					append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
				}
				
				// multiple gemeinde
				if(count($gemeinde) > 1)
				{
					for ($gi= 1; $gi < count($gemeinde); $gi++)
					{
						$sql_gemeinde = str_replace("'".$db->real_escape_string( trim($gemeinde[0]) )."'","'".$db->real_escape_string( trim($gemeinde[$gi]) )."'",$sql);
						$db->query($sql_gemeinde);
						
						if($config['log'] > 2)
						{
							append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql_gemeinde);
						}
					}
				}
				
				// bilderwunsch
				if($sql_value_image_requested != "")
				{
					if($key[0] != "no ID")
					{
						$sql = "DELETE FROM `" . $config['dbprefix'] . $source . "_image_requested` WHERE `id` LIKE '" . $id[$key[0]]. "'";
						$db->query($sql);
				
						if($config['log'] > 2)
						{
							append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
						}
					}
			
					$sql = "INSERT INTO `" . $config['dbprefix'] . $source . "_image_requested` (";
			
					$sql .= "`info`, ";
					if($key[0] != "no ID")
					{
						$sql .= "`id`, ";
					}
					$sql .= "`gemeinde`, `online`, `data_update`) VALUES (";
					$sql .= $sql_value_image_requested;
					if($key[0] != "no ID")
					{
						$sql .= "'" . $id[$key[0]] . "', ";
					}
					$sql .= "'".$db->real_escape_string( trim($gemeinde[0]) )."', '2', CURRENT_TIMESTAMP)";
			
					$db->query($sql);

					if($config['log'] > 2)
					{
						append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
					}
				
					// multiple gemeinde
					if(count($gemeinde) > 1)
					{
						for ($gi= 1; $gi < count($gemeinde); $gi++)
						{
							$sql_gemeinde = str_replace("'".$db->real_escape_string( trim($gemeinde[0]) )."'","'".$db->real_escape_string( trim($gemeinde[$gi]) )."'",$sql);
							$db->query($sql_gemeinde);
						
							if($config['log'] > 2)
							{
								append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql_gemeinde);
							}
						}
					}
				}
			}
		}
	} // get data
}

function list_get_main($db, $source)
{
	global $config;
	
	if($config['log'] > 1)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t debug \t called: \t list_get_main()");
	}
	
	/* WARNING:
	 * list with "no ID" for objects are offline while they are updated and removed objects are NOT kept with online = 0 in the database
	 */
	$id = list_get_id($db, $source);
	$key = array_keys($id);
	if($key[0] == "no ID")
	{
		$sql = "DELETE FROM `" . $config['dbprefix'] . $source . "_list_data` WHERE `online`='1' OR `online`='2'";
	}
	else
	{
		$sql = "UPDATE `" . $config['dbprefix'] . $source . "_list_data` SET `online`='1' WHERE `online`='2'";
	}
	
	$db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	// bilderwunsch
	if($key[0] == "no ID")
	{
		$sql = "DELETE FROM `" . $config['dbprefix'] . $source . "_image_requested` WHERE `online`='1' OR `online`='2'";
	}
	else
	{
		$sql = "UPDATE `" . $config['dbprefix'] . $source . "_image_requested` SET `online`='1' WHERE `online`='2'";
	}
	
	$db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$articles = list_get_articles_list($db, $source);
	$features = list_get_feature_list($db, $source);
	
	
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
		if(list_get_article($db, $source, $url, $article, $features) == "connection error")
		{
			if($config['log'] > 0)
			{
				append_file("log/cron.txt","\n".date(DATE_RFC822)."\t error \t connection error \t wiki_get_main()");
			}
			return "ERROR";
		}
	}
	
	// set online to 0
	$sql = "UPDATE `" . $config['dbprefix'] . $source . "_list_data` SET `online`='0' WHERE `online`='1'";
	$db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$sql = "UPDATE `" . $config['dbprefix'] . $source . "_image_requested` SET `online`='0' WHERE `online`='1'";
	$db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$sql = "UPDATE `" . $config['dbprefix'] . $source . "_list` SET `online`='2' WHERE `online`='3'";
	$db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}

	$sql = "UPDATE `" . $config['dbprefix'] . $source . "_list_features` SET `online`='1' WHERE `online`='3' OR `online`='2'";
	$db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
}

?>