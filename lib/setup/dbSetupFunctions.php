<?php
/*
 * There are 23 tables (11*2 + users)
 * dcim_badge
 * dcim_customer
 * dcim_device
 * dcim_deviceport
 * dcim_location
 * dcim_portconnection
 * dcim_portvlan
 * dcim_power
 * dcim_powerloc
 * dcim_site
 * dcim_vlan
 * 
 * dcim_user
 * 
 * dcimlog_badge
 * dcimlog_customer
 * dcimlog_device
 * dcimlog_deviceport
 * dcimlog_location
 * dcimlog_portconnection
 * dcimlog_portvlan
 * dcimlog_power
 * dcimlog_powerloc
 * dcimlog_site
 * dcimlog_vlan
 */
	function BuildDB()
	{
		/* This will create the DB to current DB specs found in the documentation folder
		 */
		global $resultMessage;
		global $errorMessage;
		global $debugMessage;
		
		$debugMessage[]= "BuildDB()-Start";
		
		ExecuteThisFile("B","../restoredata/structure.sql");
		
		$resultMessage[]= "BuildDB()-Sucsessfully created database structure";
	}
	
	function RestoreDBWithDemoData()
	{
		/* This will wipe/truncate all current data in the database and repopulate it all with demo data
		 */
		global $resultMessage;
		global $errorMessage;
		global $debugMessage;
		
		$debugMessage[]= "RestoreDBWithDemoData()-Start";

		ExecuteThis("D1","TRUNCATE TABLE dcim_badge");
		ExecuteThis("D1","TRUNCATE TABLE dcim_customer");
		ExecuteThis("D1","TRUNCATE TABLE dcim_device");
		ExecuteThis("D1","TRUNCATE TABLE dcim_deviceport");
		ExecuteThis("D1","TRUNCATE TABLE dcim_location");
		ExecuteThis("D1","TRUNCATE TABLE dcim_portconnection");
		ExecuteThis("D1","TRUNCATE TABLE dcim_portvlan");
		ExecuteThis("D1","TRUNCATE TABLE dcim_power");
		ExecuteThis("D1","TRUNCATE TABLE dcim_powerloc");
		ExecuteThis("D1","TRUNCATE TABLE dcim_vlan");
		ExecuteThis("D1","TRUNCATE TABLE dcim_user");
		
		ExecuteThisFile("D2","../restoredata/demoData.sql");
		
		WipeAndReCreateAllLogs();
		
		$resultMessage[]= "BuildDB()-Sucsessfully populated database with demo data";
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
		
		$debugMessage[]= "RunDBUpdate1()-Start";
		
		//SELECT siteid, CONCAT("CA",CAST(colo AS UNSIGNED)) AS roomname, colo, COUNT(*) AS count FROM dcimlog_location GROUP BY colo
		
		//ALTER TABLE `dcim_deviceport` DROP `hno`;
		
		$resultMessage[]= "BuildDB()-Sucsessfully updated database.";
	}
	
	function WipeAndReCreateAllLogs()
	{
		global $resultMessage;
		global $errorMessage;
		global $debugMessage;
		
		$debugMessage[]= "WipeAndReCreateAllLogs()-Start";
		//wipe all log records
		ExecuteThis("L1","TRUNCATE TABLE dcimlog_badge");
		ExecuteThis("L1","TRUNCATE TABLE dcimlog_customer");
		ExecuteThis("L1","TRUNCATE TABLE dcimlog_device");
		ExecuteThis("L1","TRUNCATE TABLE dcimlog_deviceport");
		ExecuteThis("L1","TRUNCATE TABLE dcimlog_location");
		ExecuteThis("L1","TRUNCATE TABLE dcimlog_portconnection");
		ExecuteThis("L1","TRUNCATE TABLE dcimlog_portvlan");
		ExecuteThis("L1","TRUNCATE TABLE dcimlog_power");
		ExecuteThis("L1","TRUNCATE TABLE dcimlog_powerloc");
		ExecuteThis("L1","TRUNCATE TABLE dcimlog_vlan");
		//create insert log records for all data
		ExecuteThis("L2","INSERT INTO dcimlog_badge			SELECT NULL,'I' AS logtype,a.* FROM dcim_badge			AS a WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_customer		SELECT NULL,'I' AS logtype,a.* FROM dcim_customer		AS a WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_device			SELECT NULL,'I' AS logtype,a.* FROM dcim_device			AS a WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_deviceport		SELECT NULL,'I' AS logtype,a.* FROM dcim_deviceport		AS a WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_location		SELECT NULL,'I' AS logtype,a.* FROM dcim_location		AS a WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_portconnection	SELECT NULL,'I' AS logtype,a.* FROM dcim_portconnection	AS a WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_portvlan		SELECT NULL,'I' AS logtype,a.* FROM dcim_portvlan		AS a WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_power			SELECT NULL,'I' AS logtype,a.* FROM dcim_power			AS a WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_powerloc		SELECT NULL,'I' AS logtype,a.* FROM dcim_powerloc		AS a WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_vlan			SELECT NULL,'I' AS logtype,a.* FROM dcim_vlan			AS a WHERE 1=1");
		$resultMessage[]= "WipeAndReCreateAllLogs()-Sucsessfully re-created all log records.";
	}
	
	function ExecuteThisFile($debugTag,$fileName, $reportSucsess=false)
	{
		$cmds = SQLFileToCmdArray($fileName);
		foreach ($cmds as $cmd)
		{
			ExecuteThis($debugTag,$cmd,$reportSucsess);
		}
	}
	
	function ExecuteThis($debugTag,$query, $reportSucsess=false)
	{
		global $mysqli;
		global $resultMessage;
		global $errorMessage;
		global $debugMessage;
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "ExecuteThis()-Prepare failed:($debugTag,$query) (" . $mysqli->errno . ") " . $mysqli->error;
			return false;
		}
		else
		{
			if(!$stmt->execute())
			{
				$errorMessage[] = "ExecuteThis()-Error executing($debugTag): $query.";
				return false;
			}
			else 
			{
				if($reportSucsess)
					$resultMessage[] = "ExecuteThis()-Sucsessfully executed($debugTag): $query.";
			}
			$stmt->close();
			return true;
		}
	}
	
	function GenRandomCompanyName()
	{
		$companyNameNouns = array();
		$companyNameNouns[]="Hosting       ";
		$companyNameNouns[]="Apps          ";
		$companyNameNouns[]="Cars          ";
		$companyNameNouns[]="Search        ";
		$companyNameNouns[]="Tech          ";
		$companyNameNouns[]="Technology    ";
		$companyNameNouns[]="SEO           ";
		$companyNameNouns[]="Computing     ";
		$companyNameNouns[]="Revolution    ";
		$companyNameNouns[]="Labs          ";
		$companyNameNouns[]="Data          ";
		$companyNameNouns[]="Baking        ";
		$companyNameNouns[]="Corp          ";
		$companyNameNouns[]="Solutions     ";
		$companyNameNouns[]="Computers     ";
		$companyNameNouns[]="Devices       ";
		$companyNameNouns[]="Connections   ";
		$companyNameNouns[]="Consulting    ";
		$companyNameNouns[]="Applications  ";
		$companyNameNouns[]="Computers     ";
		$companyNameNouns[]="Computer      ";
		$companyNameNouns[]="American      ";
		$companyNameNouns[]="Bit           ";
		$companyNameNouns[]="Byte          ";
		$companyNameNouns[]="Bits          ";
		$companyNameNouns[]="Bytes         ";
		$companyNameNouns[]="Systems       ";
		$companyNameNouns[]="Software      ";
		$companyNameNouns[]="Coding        ";
		$companyNameNouns[]="Solutions     ";
		$companyNameNouns[]="Communications";
		$companyNameNouns[]="Comms         ";
		$companyNameNouns[]="Gyms          ";
		$companyNameNouns[]="Genisis       ";
		$companyNameNouns[]="Interactive   ";
		$companyNameNouns[]="Credits       ";
		$companyNameNouns[]="TV            ";
		$companyNameNouns[]="Dynamics      ";
		$companyNameNouns[]="Drones        ";
		$companyNameNouns[]="Carbs         ";
		$companyNameNouns[]="Bubbles       ";
		$companyNameNouns[]="Portals       ";
		$companyNameNouns[]="Friends       ";
		
		$adjetives = array();
		$adjetives[]="Mega           ";
		$adjetives[]="Red            ";
		$adjetives[]="Green          ";
		$adjetives[]="Blue           ";
		$adjetives[]="Great          ";
		$adjetives[]="Best           ";
		$adjetives[]="Better         ";
		$adjetives[]="Good           ";
		$adjetives[]="The Best       ";
		$adjetives[]="Learning       ";
		$adjetives[]="Core           ";
		$adjetives[]="Main           ";
		$adjetives[]="Important      ";
		$adjetives[]="Happy          ";
		$adjetives[]="Sad            ";
		$adjetives[]="Productive     ";
		$adjetives[]="Progressive    ";
		$adjetives[]="White          ";
		$adjetives[]="Top            ";
		$adjetives[]="New            ";
		$adjetives[]="Revolutionary  ";
		$adjetives[]="Base           ";
		$adjetives[]="Net            ";
		$adjetives[]="Internet       ";
		$adjetives[]="Global         ";
		$adjetives[]="Local          ";
		$adjetives[]="Wireless       ";
		$adjetives[]="Techy          ";
		$adjetives[]="Centrailized   ";
		$adjetives[]="Creative       ";
		$adjetives[]="Cornerstone    ";
		$adjetives[]="Data           ";
		$adjetives[]="Online         ";
		$adjetives[]="Pivotal        ";
		$adjetives[]="E              ";
		$adjetives[]="Hyper          ";
		$adjetives[]="Platinum       ";
		$adjetives[]="Arial          ";
		$adjetives[]="Optical        ";
		$adjetives[]="Budget         ";
		$adjetives[]="Baseline       ";
		$adjetives[]="Intuitive      ";
		$adjetives[]="Original       ";
		$adjetives[]="Energy         ";
		$adjetives[]="Lead           ";
		$adjetives[]="Neon           ";
		$adjetives[]="One            ";
		$adjetives[]="Planet         ";
		$adjetives[]="Pacific        ";
		$adjetives[]="Test           ";
		return trim($adjetives[array_rand($adjetives)]) . " " . trim($companyNameNouns[array_rand($companyNameNouns)]);
	}
	
	function GenRandomPersonName()
	{
		$firstNames = array();
		$firstNames[]="Mike       ";
		$firstNames[]="Michael    ";
		$firstNames[]="Anthony    ";
		$firstNames[]="Kim        ";
		$firstNames[]="Joe        ";
		$firstNames[]="Bob        ";
		$firstNames[]="Robert     ";
		$firstNames[]="Jim        ";
		$firstNames[]="Bruce      ";
		$firstNames[]="Brandon    ";
		$firstNames[]="Brannon    ";
		$firstNames[]="Roman      ";
		$firstNames[]="Scott      ";
		$firstNames[]="Bill       ";
		$firstNames[]="James      ";
		$firstNames[]="Adam       ";
		$firstNames[]="Jason      ";
		$firstNames[]="Chris      ";
		$firstNames[]="Tom        ";
		$firstNames[]="Thommas    ";
		$firstNames[]="Craig      ";
		$firstNames[]="Rick       ";
		$firstNames[]="John       ";
		$firstNames[]="Richard    ";
		$firstNames[]="Rich       ";
		$firstNames[]="Jerry      ";
		$firstNames[]="Bryan      ";
		$firstNames[]="Brian      ";
		$firstNames[]="Josh       ";
		$firstNames[]="Joshua     ";
		$firstNames[]="Lori       ";
		$firstNames[]="Jennifer   ";
		$firstNames[]="Julie      ";
		$firstNames[]="Kelly      ";
		$firstNames[]="Tiffani    ";
		$firstNames[]="Heidi      ";
		$firstNames[]="Eric       ";
		$firstNames[]="Paul       ";
		$firstNames[]="Joseph     ";
		$firstNames[]="Greg       ";
		$firstNames[]="Alex       ";
		$firstNames[]="Mario      ";
		$firstNames[]="Ivan       ";
		$firstNames[]="Justin     ";
		$firstNames[]="Clint      ";
		$firstNames[]="Sam        ";
		$firstNames[]="Hank       ";
		$firstNames[]="Homer      ";
		$firstNames[]="Christopher";
		$firstNames[]="Stan       ";
		$firstNames[]="Timmy      ";
		$firstNames[]="Tim        ";
		$firstNames[]="Kyle       ";
		$firstNames[]="Peter      ";
		$firstNames[]="Pete       ";
		$firstNames[]="Matt       ";
		$firstNames[]="Ray        ";
		$firstNames[]="Jack       ";
		$firstNames[]="Jeremy     ";
		$firstNames[]="Jesse      ";
		$firstNames[]="Arthur     ";
		$firstNames[]="Pablo      ";
		$firstNames[]="Sean       ";
		$firstNames[]="Shawn      ";
		$firstNames[]="Helen      ";
		$firstNames[]="Leo        ";
		$firstNames[]="Lenard     ";
		
		$lastNames = array();
		$lastNames[]="Smith      ";
		$lastNames[]="Jones      ";
		$lastNames[]="Ryu        ";
		$lastNames[]="Yun        ";
		$lastNames[]="Ching      ";
		$lastNames[]="Roberts    ";
		$lastNames[]="Chang      ";
		$lastNames[]="Black      ";
		$lastNames[]="Hernadez   ";
		$lastNames[]="Davis      ";
		$lastNames[]="Brown      ";
		$lastNames[]="Williams   ";
		$lastNames[]="King       ";
		$lastNames[]="Lopez      ";
		$lastNames[]="Hill       ";
		$lastNames[]="Simpson    ";
		$lastNames[]="Adams      ";
		$lastNames[]="Baker      ";
		$lastNames[]="Green      ";
		$lastNames[]="Garcia     ";
		$lastNames[]="Clark      ";
		$lastNames[]="Perez      ";
		$lastNames[]="Turners    ";
		$lastNames[]="Nelson     ";
		$lastNames[]="Carter     ";
		$lastNames[]="Mitchel    ";
		$lastNames[]="Gonsolez   ";
		$lastNames[]="Young      ";
		$lastNames[]="Hall       ";
		$lastNames[]="Walker     ";
		$lastNames[]="Jackson    ";
		$lastNames[]="White      ";
		$lastNames[]="Whittman   ";
		$lastNames[]="Ramzey     ";
		$lastNames[]="Cox        ";
		$lastNames[]="Gray       ";
		$lastNames[]="Wood       ";
		$lastNames[]="Pinkerton  ";
		$lastNames[]="Barnes     ";
		$lastNames[]="Ross       ";
		$lastNames[]="Cooper     ";
		$lastNames[]="Reed       ";
		$lastNames[]="Rodgers    ";
		$lastNames[]="Bell       ";
		$lastNames[]="Phillips   ";
		$lastNames[]="Campbel    ";
		$lastNames[]="Alexander  ";
		$lastNames[]="Jenkins    ";
		$lastNames[]="Ownens     ";
		$lastNames[]="Warren     ";
		$lastNames[]="Kenedy     ";
		$lastNames[]="Hitler     ";
		
		return trim($firstNames[array_rand($firstNames)]) . " " . trim($lastNames[array_rand($lastNames)]);
	}
?>