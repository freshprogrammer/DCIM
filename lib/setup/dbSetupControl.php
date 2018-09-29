<?php
	//See RunScript() to see actual patch notes and other details
	
	set_include_path('../'); 
	
	require_once 'DCIMCustomFunctions.php';
	require_once 'config.php';
	require_once 'customFunctions.php';
	require_once 'genericFunctions.php';
	require_once 'helperFunctions.php';
	require_once 'functions.php';
	require_once 'setup/dbSetupFunctions.php';
	
	SQLIConnect_Admin();
	SessionSetup();
	
	//globals
	global $appName;
	global $pageTitle;
	//rebuilds
	$SCRIPTID_BUILD_DATABASE = 1;
	$SCRIPTID_CREATE_DEMO_DATA = 2;
	$SCRIPTID_BUILD_DB_WITH_DEMO_DATA = 3;
	$SCRIPTID_RESET_DEMO_CREDS = 4;
	//updates
	$SCRIPTID_DB_UPDATEv3_1 = 11;
	$SCRIPTID_DB_UPDATEv3_2 = 12;
	//batches
	$SCRIPTID_CREATE_POPULATE_UPDATE = 21;
	//simple procedures
	$SCRIPTID_RECREATE_ALL_LOGS = 101;
	//Data correction functions - Mass QA, ect
	$SCRIPTID_QA_ALL_RECORDS = 201;
	
	//scripts that can be run on live (non demo) environment
	$liveEnvironmentScripts = array();
	$liveEnvironmentScripts []= $SCRIPTID_DB_UPDATEv3_1;
	$liveEnvironmentScripts []= $SCRIPTID_DB_UPDATEv3_2;
	$liveEnvironmentScripts []= $SCRIPTID_QA_ALL_RECORDS;
	
	$resultMessage = array();
	$errorMessage = array();
	$debugMessage = array();
	$timeStamp = date("Y-m-d H:i:s");
	
	
	$restoreStructureSQLFile = "../../restoredata/structure.sql";
	$restoreDataSQLFile = "../../restoredata/demoData.sql";
	
?>
<head>
<title><?php echo $pageTitle;?> - DB update control</title>
<link rel="icon" type="image/x-icon" href="../../images/favicon.ico">
<link rel="stylesheet" href="../css/default.css">
<script type='text/javascript'>
function ConfirmIntent()
{
	var selectValue = document.getElementById('scriptidselect').value;
	//only prompt on values that do things. - Could algo give custom messges here
	if(<?php echo "selectValue==$SCRIPTID_BUILD_DATABASE || selectValue==$SCRIPTID_CREATE_DEMO_DATA || selectValue==$SCRIPTID_BUILD_DB_WITH_DEMO_DATA || selectValue==$SCRIPTID_DB_UPDATEv3_1 || selectValue==$SCRIPTID_DB_UPDATEv3_2 || selectValue==$SCRIPTID_CREATE_POPULATE_UPDATE"; ?>)
	{
		var confirmed = false;
		var confirm = prompt("Are you sure you want to run this script that will masively change the DB? Enter 'YES' to confirm.", "");
		
		if (confirm != null) 
		{
			if(confirm=="YES")
				confirmed = true;
			else
				alert("Failed Authentication. You must be not be sure.");
		}
		return confirmed;
	}
	else
		return true;
}
</script>
</head>
<font size=5><b><?php echo $appName;?> Database update control</b></font><?php
 	echo " on PHP v".phpversion()."<BR>\n";
	
	//simple action selection form
	echo "<form action='' method='post' onsubmit='return ConfirmIntent()'>
	Script to run:
	<select id='scriptidselect' name='scriptid'>
		<option value='0'									>No Action</option>
		<option value='0'									>-</option>
		<option value='$SCRIPTID_BUILD_DATABASE'			>Clear database and build new empty database</option>
		<option value='$SCRIPTID_CREATE_DEMO_DATA'			>Reset all data in database with demo snapshot</option>
		<option value='$SCRIPTID_BUILD_DB_WITH_DEMO_DATA'	>Clear database and re-populate with demo data</option>
		<option value='$SCRIPTID_RESET_DEMO_CREDS'			>Reset Demo Credentials</option>
		<option value='0'									>-</option>
		<option value='$SCRIPTID_DB_UPDATEv3_1'				>Update database to DBv3 (part 1)</option>
		<option value='$SCRIPTID_DB_UPDATEv3_2'				>Update database to DBv3 (part 2)</option>
		<option value='0'									>-</option>
		<option value='$SCRIPTID_CREATE_POPULATE_UPDATE'	>Rebuild & re-populate & fully update DB (If restore data is not up to date)</option>
		<option value='0'									>-</option>
		<option value='$SCRIPTID_RECREATE_ALL_LOGS'			>Wipe and recreate log records as of now.</option>
		<option value='0'									>-</option>
		<option value='$SCRIPTID_QA_ALL_RECORDS'			>QA all outstanding records as Admin.</option>
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
	
	$validAction = false;
	$dbStatus = 0;
	$commited = false;
	$validSession = false;
	
	$debugMessage[]= "-Start - testing permisions and db status";
	if(!$dbScriptID==0)
	{
		if(!isset($demoSiteEnabled) || !$demoSiteEnabled)
		{//this is not a demo server - anthing other that an update will screw with the core data or structure and is not allowed
			
			if(in_array($dbScriptID,$liveEnvironmentScripts))
			{
				$validAction = true;
			}
		}
		else
			$validAction = true;//demo server - go wild
	}
	
	if($validAction)
		$validSession = IsValidSession();
	if($validSession)
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
		else if(!$commited)
			$errorMessage[]="User not commited. Aborted.";
	}
	
	$debugMessageString  = implode("<BR>\n",$debugMessage);
	$errorMessageString  = implode("<BR>\n",$errorMessage);
	$resultMessageString = implode("<BR>\n",$resultMessage);
	if(strlen($debugMessageString) > 0) echo "<!-- DEBUG MESSAGE  -->\n<div id='debugMessage'  class='debugMessage'>$debugMessageString</div>\n";
	if(strlen($errorMessageString) > 0) echo "<!-- ERROR MESSAGE  -->\n<div id='errorMessage'  class='errorMessage'>$errorMessageString</div>\n";
	if(strlen($resultMessageString) > 0)echo "<!-- RESULT MESSAGE -->\n<div id='resultMessage' class='resultMessage'>$resultMessageString</div>\n";
	
	//show db structure documentation on page for reffereance
	echo "<BR>";
	echo "Description of the live DB<BR>";
	echo CreateTableRowCountTable();
	echo DescribeDBInTables();
	
	//END PAGE - Begin local Functions - All actual processing functions are in the refferenced file
	
	//returns 1 if DB is ready to update
	//returns 0 if DB is not ready
	//returns -1 if DB is already updated
	function TestDBReadiness($dbScriptID)
	{
		global $SCRIPTID_DB_UPDATEv3_1;
		global $SCRIPTID_DB_UPDATEv3_2;
		global $SCRIPTID_CREATE_POPULATE_UPDATE;
		
		if($dbScriptID==$SCRIPTID_DB_UPDATEv3_1 || $dbScriptID==$SCRIPTID_DB_UPDATEv3_2 || $dbScriptID==$SCRIPTID_CREATE_POPULATE_UPDATE)
		{
			if($dbScriptID==$SCRIPTID_DB_UPDATEv3_1)
				return IsDatabaseUpToDate_Update1(true,false);
			else if($dbScriptID==$SCRIPTID_DB_UPDATEv3_2)
					return IsDatabaseUpToDate_Update1(false,true);
			else if($dbScriptID==$SCRIPTID_CREATE_POPULATE_UPDATE)
				return IsDatabaseUpToDate_Update1(true,true);
		}
		return 1;
	}

	//returns true if user has proven commitment
	//this could test against a fixed admin password or something - cant refference DB since that will be wiped here
	function TestUserCommitment($dbScriptID)
	{
		//This will be validated in JS
		return true;
	}
	
	//idealy you will never being doing more than 1 update at a time and the build DB will build to current specs. If you want a script for 
	function RunScript($dbScriptID)
	{
		global $SCRIPTID_BUILD_DATABASE;
		global $SCRIPTID_CREATE_DEMO_DATA;
		global $SCRIPTID_BUILD_DB_WITH_DEMO_DATA;
		global $SCRIPTID_RESET_DEMO_CREDS;
		global $SCRIPTID_DB_UPDATEv3_1;
		global $SCRIPTID_DB_UPDATEv3_2;
		global $SCRIPTID_CREATE_POPULATE_UPDATE;
		global $SCRIPTID_RECREATE_ALL_LOGS;
		global $SCRIPTID_QA_ALL_RECORDS;
		global $errorMessage;
		global $restoreStructureSQLFile;
		global $restoreDataSQLFile;
		
		switch($dbScriptID)
		{
			case $SCRIPTID_BUILD_DATABASE:
				echo "Processing Rebuild...";
				BuildDB($restoreStructureSQLFile);
				echo "<BR>Done";
				break;
			case $SCRIPTID_CREATE_DEMO_DATA:
				echo "Populating Database...";
				RestoreDBWithDemoData($restoreDataSQLFile);
				echo "<BR>Done";
				break;
			case $SCRIPTID_BUILD_DB_WITH_DEMO_DATA:
				echo "Processing Rebuild...";
				BuildDB($restoreStructureSQLFile);
				echo "<BR>Populating Database...";
				RestoreDBWithDemoData($restoreDataSQLFile);
				echo "<BR>Done";
				break;
			case $SCRIPTID_RESET_DEMO_CREDS:
				echo "<BR>Restoring default demo credentials...";
				RestoreDemoCreds();
				echo "<BR>Done";
				break;
			case $SCRIPTID_DB_UPDATEv3_1:
			case $SCRIPTID_DB_UPDATEv3_2:
				echo "processing DBv3 Update...";
				
				$dbStatus = TestDBReadiness($dbScriptID);
				if($dbStatus==1)
					RunDBUpdate_Update($dbScriptID==$SCRIPTID_DB_UPDATEv3_1,$dbScriptID==$SCRIPTID_DB_UPDATEv3_2);
				else if(dbStatus==0)
					$errorMessage[]="Database failed readiness check. Update Aborted";
				else if($dbStatus==-1)
					$errorMessage[]="Database Has already been updated tp DBv3. Update Aborted.";
				
				echo "<BR>Done";
				break;
			case $SCRIPTID_CREATE_POPULATE_UPDATE:
				echo "Processing Rebuild...";
				BuildDB($restoreStructureSQLFile);
				echo "<BR>Populating Database...";
				RestoreDBWithDemoData($restoreDataSQLFile);
				
				$dbStatus = TestDBReadiness($dbScriptID);
				if($dbStatus==1)
				{
					echo "<BR>Processing Update (DBv3) Part 1...";
					RunDBUpdate_Update(true,false);
					echo "<BR>Processing Update (DBv3) Part 2...";
					RunDBUpdate_Update(false,true);
				}
				else if($dbStatus==0)
					$errorMessage[]="Database failed readiness check. Update Aborted";
				else if($dbStatus==-1)
					$errorMessage[]="Database Has already been updated. Update Aborted.";
					
				echo "<BR>Done";
				break;
			case $SCRIPTID_RECREATE_ALL_LOGS:
				echo "Rebuilding log records";
				WipeAndReCreateAllLogs();
				echo "<BR>Done";
				break;
			case $SCRIPTID_QA_ALL_RECORDS:
				echo "QAing all records as Admin";
				QAAllRecordsAsAdmin();
				echo "<BR>Done";
				break;
			default:
				$errorMessage[]= "RunScript($dbScriptID)-Invalid script ID";
				break;
		}
	}
?>