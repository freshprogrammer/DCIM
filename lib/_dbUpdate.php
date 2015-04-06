<head>
<title>DCIM DB Update</title>
<link rel="icon" type="image/x-icon" href="../images/favicon.ico">
<link rel="stylesheet" href="css/default.css">
</head>
<?php 
	/* 	This will Update the Database by doing the following in the main and log tables:
	 * 		Drop field dcim_deviceport.hno
	 * 		Change dcim_power.circuit from varchar(5) to tinyint(2)
	 * 		Rename dcim_power.cload to load
	 * 		Change dcim_power panel,circuit, volts, amps, cload to NOT NULL
	 */

	//set_include_path('.'); 
	
	include 'config.php';
	include 'genericFunctions.php';
	include 'helperFunctions.php';
	include 'functions.php';
	
	SQLIConnect();
	
	//globals
	$resultMessage = array();
	$errorMessage = array();
	$debugMessage = array();
	
	$resultMessage[] = "initialized";
	$errorMessage[] = "initialized";
	$debugMessage[] = "initialized";
	

	$debugMessage[]= "main()-Start Processing";
	$dbStatus = TestDBReadiness();
	$commited = TestUserCommitment();
	if($dbStatus==1 && $commited)
	{
		DoUpdate();
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
	
	//returns 1 if DB is ready for update
	//returns 0 if DB is not ready
	//returns -1 if DB is already updated
	function TestDBReadiness()
	{
		global $resultMessage;
		global $errorMessage;
		global $debugMessage;
		
		$debugMessage[]= "TestDBReadiness()-Start Processing";
		return 0;
	}

	//returns true if user has proven commitment
	function TestUserCommitment()
	{
		global $resultMessage;
		global $errorMessage;
		global $debugMessage;
		
		$debugMessage[]= "TestUserCommitment()-Start Processing";
		return false;
	}
	
	function DoUpdate()
	{
		global $resultMessage;
		global $errorMessage;
		global $debugMessage;
		
		$debugMessage[]= "DoUpdate()-Start Processing";
		//SELECT siteid, CONCAT("CA",CAST(colo AS UNSIGNED)) AS roomname, colo, COUNT(*) AS count FROM dcimlog_location GROUP BY colo
		
		//ALTER TABLE `dcim_deviceport` DROP `hno`;
	}
	
?>