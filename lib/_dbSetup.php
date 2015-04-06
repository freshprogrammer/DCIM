<head>
<title>DCIM DB Update</title>
<link rel="icon" type="image/x-icon" href="../images/favicon.ico">
<link rel="stylesheet" href="css/default.css">
</head>
<?php 
	//See RunScript() to see actual patch notes and other details
	
	//set_include_path('.'); 
	
	include 'config.php';
	include 'genericFunctions.php';
	include 'helperFunctions.php';
	include 'functions.php';
	
	SQLIConnect();
	
	//globals
	$SCRIPTID_BUILD_DATABASE = 0;
	$SCRIPTID_CREATE_DEMO_DATA = 1;
	$SCRIPTID_BUILD_DB_WITH_DEMO_DATA = 2;
	$SCRIPTID_DB_UPDATE_1 = 11;
	$resultMessage = array();
	$errorMessage = array();
	$debugMessage = array();
	
	$resultMessage[] = "<b>Results</b>";
	$errorMessage[] = "<b>Errors</b>";
	$debugMessage[] = "<b>Debug</b>";

	$dbScriptID = 0;
	
	$debugMessage[]= "-Start Processing";
	$dbStatus = TestDBReadiness($dbScriptID);
	$commited = TestUserCommitment($dbScriptID);
	if($dbStatus==1 && $commited)
	{
		RunScript($dbScriptID);
	}
	else
	{
		if($dbStatus==0)
		{
			$errorMessage[]="Database failed readiness check. Aborted";
		}
		else if($dbStatus==-1)
		{
			$errorMessage[]="Database Has already been updated. Aborted.";
		}
		else if(!$commited)
		{
			$errorMessage[]="User not commited. Aborted.";
		}
	}

	$debugMessageString  = implode("<BR>\n",$debugMessage);
	$errorMessageString  = implode("<BR>\n",$errorMessage);
	$resultMessageString = implode("<BR>\n",$resultMessage);
	if(strlen($debugMessageString) > 0) echo "<!-- DEBUG MESSAGE  -->\n<div id='debugMessage'  class='debugMessage'>$debugMessageString</div>\n";
	if(strlen($errorMessageString) > 0) echo "<!-- ERROR MESSAGE  -->\n<div id='errorMessage'  class='errorMessage'>$errorMessageString</div>\n";
	if(strlen($resultMessageString) > 0)echo "<!-- RESULT MESSAGE -->\n<div id='resultMessage' class='resultMessage'>$resultMessageString</div>\n";
	
	
	
	//END PAGE - Begin Functions////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	//returns 1 if DB is ready to update
	//returns 0 if DB is not ready
	//returns -1 if DB is already updated
	function TestDBReadiness($dbScriptID)
	{
		global $resultMessage;
		global $errorMessage;
		global $debugMessage;
		global $demoSiteEnabled;
		
		$debugMessage[]= "TestDBReadiness($dbScriptID)-Start Processing";
		
		if(isset($demoSiteEnabled) && $demoSiteEnabled)
			return 0;
		return 0;
	}

	//returns true if user has proven commitment
	function TestUserCommitment($dbScriptID)
	{
		global $resultMessage;
		global $errorMessage;
		global $debugMessage;
		
		$debugMessage[]= "TestUserCommitment($dbScriptID)-Start Processing";
		return false;
	}
	
	function RunScript($dbScriptID)
	{
		global $resultMessage;
		global $errorMessage;
		global $debugMessage;
		
		$debugMessage[]= "RunScript($dbScriptID)-Start Processing";
		
		switch($dbScriptID)
		{
			case $SCRIPTID_BUILD_DATABASE:
				BuildDB();
				break;
			case $SCRIPTID_CREATE_DEMO_DATA:
				break;
			case $SCRIPTID_BUILD_DB_WITH_DEMO_DATA:
				break;
			case $SCRIPTID_DB_UPDATE_1:
				RunDBUpdate1();
				break;
		}
	}
	
	function BuildDB()
	{
		/* This will create the DB to current DB specs found in the documentation folder
		 */
		global $resultMessage;
		global $errorMessage;
		global $debugMessage;
		global $demoSiteEnabled;
		
		if(isset($demoSiteEnabled) && $demoSiteEnabled)
		{
			//drop tables just in case
		}
	}
	
	function RestoreDBWithDemoData()
	{
		/* This will wipe/truncate all current data in the database and repopulate it all with demo data
		 */
		global $resultMessage;
		global $errorMessage;
		global $debugMessage;
		global $demoSiteEnabled;
		
		if(isset($demoSiteEnabled) && $demoSiteEnabled)
		{
			
		}
		else 
		{
			$errorMessage[]="Cannot run RestoreDBWithDemoData() while Demo Site is not enabled in config";
		}
	}
	
	function RunDBUpdate1()
	{
		/* This will Update the Database by doing the following in the main and log tables:
		 * 	Drop field dcim_deviceport.hno
		 * 	Change dcim_power.circuit from varchar(5) to tinyint(2)
		 * 	Rename dcim_power.cload to load
		 * 	Change dcim_power panel,circuit, volts, amps, cload to NOT NULL
		 */
		global $resultMessage;
		global $errorMessage;
		global $debugMessage;
		
		//SELECT siteid, CONCAT("CA",CAST(colo AS UNSIGNED)) AS roomname, colo, COUNT(*) AS count FROM dcimlog_location GROUP BY colo
		
		//ALTER TABLE `dcim_deviceport` DROP `hno`;
	}
	
?>