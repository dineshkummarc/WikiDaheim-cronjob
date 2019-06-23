<?php
// startpoint for cronjob
require_once "config/config.php"; // config stuff
require_once "lib/lib.php"; // file functions
require_once "lib/external/base.php"; // file functions


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
	$source = "bilderwunsch";
	if(bilderwunsch_get_main($db, $source) == "ERROR") // lib/external/base.php
	{
		$db->close();
		echo "ERROR";
		return;
	}
	
	$db->close();
}

echo "request done";

?>