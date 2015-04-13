<?php 
	//See RunScript() to see actual patch notes and other details
	
	set_include_path('../'); 
	
	include 'config.php';
	include 'genericFunctions.php';
	include 'helperFunctions.php';
	include 'functions.php';
	include 'setup/dbSetupFunctions.php';
	
	SQLIConnect();
	SessionSetup();
	
	//globals
	$SCRIPTID_BUILD_DATABASE = 1;
	$SCRIPTID_CREATE_DEMO_DATA = 2;
	$SCRIPTID_BUILD_DB_WITH_DEMO_DATA = 3;
	$SCRIPTID_DB_UPDATE_1 = 11;
	$resultMessage = array();
	$errorMessage = array();
	$debugMessage = array();
	$date = new DateTime();
	$timeStamp = $date->format('Y-m-d H:i:s');
	
?>
<head>
<title>DCIM DB update control</title>
<link rel="icon" type="image/x-icon" href="../../images/favicon.ico">
<link rel="stylesheet" href="../css/default.css">
</head>
<font size=5><b>DCIM Database update control</b></font><BR>
<?php
	
	//simple action selection form
	echo "<form action='' method='post'>
	Script to run:
	<select name='scriptid'>
		<option value='0'									>No Action</option>
		<option value='0'									>-</option>
		<option value='$SCRIPTID_BUILD_DATABASE'			>Clear database and build new empty database</option>
		<option value='$SCRIPTID_CREATE_DEMO_DATA'			>Reset all data in database with demo snapshot</option>
		<option value='$SCRIPTID_BUILD_DB_WITH_DEMO_DATA'	>Clear database and re-populate with demo data</option>
		<option value='0'									>-</option>
		<option value='$SCRIPTID_DB_UPDATE_1'				>Update database with latest update</option>
	</select>
	<input type='submit' value='Run'>
	<input type='hidden' name='page_instance_id' value='".end($_SESSION['page_instance_ids'])."'>
</form>";
	
	$resultMessage[] = "Results - $timeStamp";
	$errorMessage[] = "Errors";
	$debugMessage[] = "Debug Messages - $timeStamp";
	
	//$dbScriptID = $SCRIPTID_BUILD_DB_WITH_DEMO_DATA;
	$dbScriptID = (int)GetInput("scriptid",true,false);
	
	// End Definitions - Start Processing ---------------------------------------------------------------------------------------------
	
	$validAction = false;// with be set true bellow when checks pass
	$dbStatus = 0;
	$commited = false;
	$validSession = false;
	
	$debugMessage[]= "-Start - testing permisions and db status";
	if(!$dbScriptID==0)
	{
		if(!isset($demoSiteEnabled) || !$demoSiteEnabled)
		{//this is not a demo server - anthing other that an update will screw with the core data or structure and is not allowed
			$errorMessage[]="ZZ-Fail1";
			if($dbScriptID==$SCRIPTID_DB_UPDATE_1)
			{
				$errorMessage[]="ZZ-Fail2";
				$validAction = true;
			}
		}
		else
			$validAction = true;//demo server - go wild
	}
	
	if($validAction)
		$validSession = IsValidSession();
	if($validSession)
		$dbStatus = TestDBReadiness($dbScriptID);
	if($dbStatus==1)
		$commited = TestUserCommitment($dbScriptID);
	if($commited)
	{//must have passed all checks
		RunScript($dbScriptID);
	}
	else
	{
		if($dbScriptID==0)
			$errorMessage[]= "No action selected";
		else if(!$validAction)
			$errorMessage[]= "Cannot wipe data or structure on live production servers. Aborted. DemoServer='$demoSiteEnabled' scriptID=$dbScriptID";
		else if(!$validSession)
			$errorMessage[]= "Invalid session. Preveted run on refresh. Re-submit form to run again";
		else if($dbStatus==0)
			$errorMessage[]="Database failed readiness check. Aborted";
		else if($dbStatus==-1)
			$errorMessage[]="Database Has already been updated. Aborted.";
		else if(!$commited)
			$errorMessage[]="User not commited. Aborted.";
	}
	
	$debugMessageString  = implode("<BR>\n",$debugMessage);
	$errorMessageString  = implode("<BR>\n",$errorMessage);
	$resultMessageString = implode("<BR>\n",$resultMessage);
	if(strlen($debugMessageString) > 0) echo "<!-- DEBUG MESSAGE  -->\n<div id='debugMessage'  class='debugMessage'>$debugMessageString</div>\n";
	if(strlen($errorMessageString) > 0) echo "<!-- ERROR MESSAGE  -->\n<div id='errorMessage'  class='errorMessage'>$errorMessageString</div>\n";
	if(strlen($resultMessageString) > 0)echo "<!-- RESULT MESSAGE -->\n<div id='resultMessage' class='resultMessage'>$resultMessageString</div>\n";
	
	
	//END PAGE - Begin local Functions - All actual processing functions are in the refferenced file
	
	//returns 1 if DB is ready to update
	//returns 0 if DB is not ready
	//returns -1 if DB is already updated
	function TestDBReadiness($dbScriptID)
	{
		global $SCRIPTID_BUILD_DATABASE;
		global $SCRIPTID_CREATE_DEMO_DATA;
		global $SCRIPTID_BUILD_DB_WITH_DEMO_DATA;
		global $SCRIPTID_DB_UPDATE_1;
		
		if($dbScriptID==$SCRIPTID_DB_UPDATE_1)
		{
			return IsDatabaseUpToDate_Update1();
		}
		return 1;
	}

	//returns true if user has proven commitment
	//this could test against a fixed admin password or something - cant refference DB since that will be wiped here
	function TestUserCommitment($dbScriptID)
	{
		global $SCRIPTID_BUILD_DATABASE;
		global $SCRIPTID_CREATE_DEMO_DATA;
		global $SCRIPTID_BUILD_DB_WITH_DEMO_DATA;
		global $SCRIPTID_DB_UPDATE_1;
		
		//TODO create the core of this function
		//$validationCode = (int)GetInput("code");
		// == date('j');//day of the month
		return true;
	}
	
	//idealy you will never being doing more than 1 update at a time and the build DB will build to current specs. If you want a script for 
	function RunScript($dbScriptID)
	{
		global $SCRIPTID_BUILD_DATABASE;
		global $SCRIPTID_CREATE_DEMO_DATA;
		global $SCRIPTID_BUILD_DB_WITH_DEMO_DATA;
		global $SCRIPTID_DB_UPDATE_1;
		global $errorMessage;
		
		switch($dbScriptID)
		{
			case $SCRIPTID_BUILD_DATABASE:
				echo "Processing Rebuild...";
				BuildDB();
				echo "<BR>Done";
				break;
			case $SCRIPTID_CREATE_DEMO_DATA:
				echo "Populating Database...";
				RestoreDBWithDemoData();
				echo "<BR>Done";
				break;
			case $SCRIPTID_BUILD_DB_WITH_DEMO_DATA:
				echo "Processing Rebuild...";
				BuildDB();
				echo "<BR>Populating Database...";
				RestoreDBWithDemoData();
				echo "<BR>Done";
				break;
			case $SCRIPTID_DB_UPDATE_1:
				echo "Processing Update...";
				RunDBUpdate_Update1();
				echo "<BR>Done";
				break;
			default:
				$errorMessage[]= "RunScript($dbScriptID)-Invalid script ID";
				break;
		}
	}
?>