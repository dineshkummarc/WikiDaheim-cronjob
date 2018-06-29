<?php
// startpoint for cronjob
require_once "config/config.php"; // config stuff
require_once "lib/lib.php"; // file functions
require_once "lib/main/wiki.php"; // get main category
require_once "lib/wiki/wiki.php"; // get wiki data
require_once "lib/main/list.php"; // get main category
require_once "lib/wiki/list.php"; // get lists data

function get_source(&$db,$recursion=true)
{
	global $config;
	$sources = array();
	$sql = "SELECT `data`, `type` FROM `" . $config['dbprefix'] . "config` WHERE `online` >= 2 AND `key` LIKE 'source'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	$i = 0;
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$i++;
		$sources[$i]['data'] = $row['data'];
		$sources[$i]['type'] = $row['type'];
		break;
	}
	$res->free();
	
	if (($i == 0) && $recursion)
	{
		$sql = "UPDATE `" . $config['dbprefix'] . "config` SET `online`='2' WHERE `online`='1' AND `key` LIKE 'source'";
		$db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
		$sources = get_source($db,false);
	}
	
	return $sources;
}

// mysql
$db = new mysqli($config['dbhost'], $config['dbuser'], $config['dbpassword'], $config['dbname']);

if ($db->connect_error)
{
	// log error
	if($config['log'] > 0)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)." \t error \t db connect_error \t main()");
	}
}
else
{
	// update Database
	$sources = array();
	$sources = get_source($db);
	
	foreach($sources as $source)
	{
		switch($source['type'])
		{
			case "township":
			
				if(township_base_get_main($db, $source['data']) == "ERROR") // lib/main/wiki.php
				{
					$db->close();
					return;
				}
				
				if(township_get_main($db, $source['data']) == "ERROR") // lib/wiki/wiki.php
				{
					$db->close();
					return;
				}
				
				$sql = "UPDATE `" . $config['dbprefix'] . "config` SET `online`='1' WHERE `data`='" . $source['data'] . "' AND `type`='" . $source['type'] . "'";
				$db->query($sql);
				if($config['log'] > 2)
				{
					append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
				}
				break;
				
			case "list":
				if(list_base_get_main($db, $source['data']) == "ERROR") // lib/main/list.php
				{
					$db->close();
					return;
				}
				
				if(list_get_main($db, $source['data']) == "ERROR") // lib/wiki/list.php
				{
					$db->close();
					return;
				}
				
				$sql = "UPDATE `" . $config['dbprefix'] . "config` SET `online`='1' WHERE `data`='" . $source['data'] . "' AND `type`='" . $source['type'] . "'";
				$db->query($sql);
	
				if($config['log'] > 2)
				{
					append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
				}
				break;
				
			case "external":
				$sql = "UPDATE `" . $config['dbprefix'] . "config` SET `online`='1' WHERE `data`='" . $source['data'] . "' AND `type`='" . $source['type'] . "'";
				$db->query($sql);
	
				if($config['log'] > 2)
				{
					append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
				}
				break;
			
			case "request":
				$sql = "UPDATE `" . $config['dbprefix'] . "config` SET `online`='1' WHERE `data`='" . $source['data'] . "' AND `type`='" . $source['type'] . "'";
				$db->query($sql);
	
				if($config['log'] > 2)
				{
					append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
				}
				break;
				
			default:
				if($config['log'] > 0)
				{
					append_file("log/cron.txt","\n".date(DATE_RFC822)." \t error \t unknown type: ".$source['type']." \t main()");
				}
				break;
		}
	}
	
	$db->close();
}

echo "list done - no Internal Server Error";

?>