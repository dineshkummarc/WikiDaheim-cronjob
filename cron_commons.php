<?php
// startpoint for cronjob
require_once "config/config.php"; // config stuff
require_once "lib/lib.php"; // file functions
require_once "lib/commons/base.php"; // get commons data

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
	// $api_url
	$sql = "SELECT `data` FROM `" . $config['dbprefix'] . "source_config` WHERE `key` LIKE 'api_url' AND `wiki` LIKE 'commons'";
	$res = $db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/cron.txt","\n".date(DATE_RFC822)."\t para \t sql: \t ".$sql);
	}
	
	$row = $res->fetch_array(MYSQLI_ASSOC);
	$api_url = $row['data'];
	$res->close();
	
	
	// stop if active
	$sql = "SELECT UNIX_TIMESTAMP(`data_update`) AS `lastedit` FROM `" . $config['dbprefix'] . "commons_photos` ORDER BY `data_update` DESC LIMIT 0 , 1";
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
	$res->close();
	$now = (int) time() - 60;
	
	if($lastedit == 0)
	{
		$features = array();
		$features = commons_get_feature_cat($db, $api_url); // lib/commons/base.php
	}
	else if($lastedit >= $now)
	{
		echo "stop - no Internal Server Error";
		return;
	}
	
	$sql = "SELECT UNIX_TIMESTAMP(`data_update`) AS `lastedit` FROM `" . $config['dbprefix'] . "commons_feature_photos` ORDER BY `data_update` DESC LIMIT 0 , 1";
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
	$res->close();
	$now = (int) time() - 60;
	if($lastedit >= $now)
	{
		echo "stop - no Internal Server Error";
		return;
	}
	
	// get commons data for all lists & articles
	if(commons_get_main($db, $api_url) == "ERROR") // lib/commons/base.php
	{
		$db->close();
		echo "ERROR";
		return;
	}
	
	$db->close();
} // $db

echo "commons done";

?>