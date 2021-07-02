<?php
// startpoint for cronjob
require_once "config/config.php"; // config stuff
require_once "lib/lib.php"; // file functions
require_once "lib/main/wikidata.php"; // get main wikidata
require_once "lib/wiki/wikidata.php"; // get wikidata data


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
	// stop if active
	$sql = "SELECT UNIX_TIMESTAMP(`data_update`) AS `lastedit` FROM `" . $config['dbprefix'] . "wikidata_external_data` ORDER BY `data_update` DESC LIMIT 0 , 1";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$lastedit = 0;
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$lastedit = (int) $row['lastedit'];
	}
	$res->free();
	$now = (int) time() - (60*10);
	
	if($lastedit >= $now)
	{
		echo "stop - no Internal Server Error";
		return;
	}
	else
	{
		$sql = "SELECT count(`online`) AS `todo` FROM `" . $config['dbprefix'] . "wikidata_external_data` WHERE `online` = 3 GROUP BY `online`";
		$res = $db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
		}
		$todo = 0;
		while($row = $res->fetch_array(MYSQLI_ASSOC))
		{
			$todo = (int) $row['todo'];
		}
		$res->free();
		if($todo > 0)
		{
			if(wikidata_get_main($db) == "ERROR") // lib/wiki/wikidata.php
			{
				$db->close();
				return;
			}
		}
		else
		{
			if(wikidata_base_get_main($db) == "ERROR") // lib/main/wikidata.php
			{
				$db->close();
				return;
			}
			if(wikidata_get_main($db) == "ERROR") // lib/wiki/wikidata.php
			{
				$db->close();
				return;
			}
		}
		
	}
	
	$db->close();
}

echo "done - no Internal Server Error";

?>