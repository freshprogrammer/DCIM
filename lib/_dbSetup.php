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
	include '_dbSetupFunctions.php';
	
	SQLIConnect();
	
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
	
	$resultMessage[] = "<b>Results - $timeStamp</b>";
	$errorMessage[] = "<b>Errors</b>";
	$debugMessage[] = "<b>Debug Messages - $timeStamp</b>";
	
	//$dbScriptID = $SCRIPTID_BUILD_DB_WITH_DEMO_DATA;
	$dbScriptID = (int)GetInput("scriptid",true,false);
	
	// End Definitions - Start Processing ---------------------------------------------------------------------------------------------
	
	$validAction = false;// with be set true bellow when checks pass
	$dbStatus = 0;
	$commited = false;
	$debugMessage[]= "-Start - testing permisions and db status";
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
	
	if($validAction)
		$dbStatus = TestDBReadiness($dbScriptID);
	if($validAction && $dbStatus==1)
		$commited = TestUserCommitment($dbScriptID);
	if($commited)
	{
		RunScript($dbScriptID);
	}
	else
	{
		if(!$validAction)
			$errorMessage[]= "Cannot wipe data or structure on live production servers. Aborted. DemoServer='$demoSiteEnabled' scriptID=$dbScriptID";
		else if($dbStatus==0)
			$errorMessage[]="Database failed readiness check. Aborted";
		else if($dbStatus==-1)
			$errorMessage[]="Database Has already been updated. Aborted.";
		else if(!$commited)
			$errorMessage[]="User not commited. Aborted.";
	}
	
	//simple validation form

	echo "<form action='' method='post'>
	ScriptID to run:<input type='number' name='scriptid'></form>";
	
	
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
		global $resultMessage;
		global $errorMessage;
		global $debugMessage;
		
		$debugMessage[]= "TestDBReadiness($dbScriptID)-Start";
		
		//TODO create the core of this function
		return 1;
	}

	//must have 
	//returns true if user has proven commitment
	function TestUserCommitment($dbScriptID)
	{
		global $SCRIPTID_BUILD_DATABASE;
		global $SCRIPTID_CREATE_DEMO_DATA;
		global $SCRIPTID_BUILD_DB_WITH_DEMO_DATA;
		global $SCRIPTID_DB_UPDATE_1;
		global $resultMessage;
		global $errorMessage;
		global $debugMessage;
		
		$debugMessage[]= "TestUserCommitment($dbScriptID)-Start";

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
		global $resultMessage;
		global $errorMessage;
		global $debugMessage;
		
		$debugMessage[]= "RunScript($dbScriptID)-Start";
		
		switch($dbScriptID)
		{
			case $SCRIPTID_BUILD_DATABASE:
				echo "Processing..";
				BuildDB();
				break;
			case $SCRIPTID_CREATE_DEMO_DATA:
				echo "Processing..";
				RestoreDBWithDemoData();
				break;
			case $SCRIPTID_BUILD_DB_WITH_DEMO_DATA:
				echo "Processing..";
				BuildDB();
				RestoreDBWithDemoData();
				break;
			case $SCRIPTID_DB_UPDATE_1:
				echo "Processing..";
				RunDBUpdate1();
				break;
			default:
				$errorMessage[]= "RunScript($dbScriptID)-Invalid script ID";
				break;
		}
	}
?>