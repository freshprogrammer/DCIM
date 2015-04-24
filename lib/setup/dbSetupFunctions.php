<?php
	
	//table list according to the Db after the update - when loading from restore new tables will just be left blank and/or fail to truncate if missing
	$mainTables = array();
	$mainTables[]="dcim_badge";
	$mainTables[]="dcim_customer";
	$mainTables[]="dcim_device";
	$mainTables[]="dcim_deviceport";
	$mainTables[]="dcim_location";
	$mainTables[]="dcim_portconnection";
	$mainTables[]="dcim_portvlan";
	$mainTables[]="dcim_power";
	$mainTables[]="dcim_powerloc";
	$mainTables[]="dcim_room";
	$mainTables[]="dcim_site";
	$mainTables[]="dcim_vlan";
	$mainTables[]="dcim_user";

	$logTables = array();
	$logTables[]="dcimlog_badge";
	$logTables[]="dcimlog_customer";
	$logTables[]="dcimlog_device";
	$logTables[]="dcimlog_deviceport";
	$logTables[]="dcimlog_location";
	$logTables[]="dcimlog_portconnection";
	$logTables[]="dcimlog_portvlan";
	$logTables[]="dcimlog_power";
	$logTables[]="dcimlog_powerloc";
	$logTables[]="dcimlog_room";
	$logTables[]="dcimlog_site";
	$logTables[]="dcimlog_vlan";
	
	

	$restoreStructureSQLFile = "../../restoredata/structure.sql";
	$restoreDataSQLFile = "../../restoredata/demoData.sql";

	function BuildDB()
	{
		/* This will drop and recreate create the DB to current DB specs found in the documentation folder
		 */
		global $resultMessage;
		global $restoreStructureSQLFile;
		
		DropAllTables();
		
		ExecuteThisFile("RS",$restoreStructureSQLFile);
		
		$resultMessage[]= "BuildDB()-Sucsessfully created database structure";
	}
	
	function RestoreDBWithDemoData()
	{
		/* This will wipe/truncate all current data in the database and repopulate it all with demo data and fresh logs
		 */
		global $resultMessage;
		global $restoreDataSQLFile;

		TruncateAllTables();
		
		//NOTE: this is just restoring the data in the core tables and not the logs
		ExecuteThisFile("RD",$restoreDataSQLFile);
		
		ReCreateAllLogs();
		
		$resultMessage[]= "RestoreDBWithDemoData()-Sucsessfully populated database with demo data";
	}
	
	//returns 1 if DB is ready to update
	//returns 0 if DB is not ready
	//returns -1 if DB is already updated
	function IsDatabaseUpToDate_Update1($executePart1, $executePart2)
	{
		global $debugMessage;
		
		//assumes all changes happen at once - so if 1 is valid they all must be
		if($executePart1)
		{
			if(DoesTableExist("dcim_room"))
				return -1;//already up to day
			else
				return 1;// good to update
		}
		else if($executePart2)
		{
			//if(DoesTableExist("dcim_room"))
			//	return -1;//already up to day
			//else
				return 1;// good to update
		}
	}
	
	//this shoould mainly be for deletions and changes with db additions being done live and with code adjustments changing to to system gradualy over time.
	function RunDBUpdate_Update1($executePart1, $executePart2)
	{
		//paramaters should be mutialy exclusive, so only 1 is true at any time
		//$executePart1 = true;//safe db additions
		//$executePart2 = false; //unsafe db removals that will only work after code has been updated
		
		/* // "x" denotes code is ready
		 * This will Update the Database by doing the following in the main and log tables:
		 *x	Drop field dcim_deviceport.hno
		 *x	Change dcim_power panel,circuit, volts, amps to NOT NULL
		 *x	Change dcim_power.circuit from varchar(5) to tinyint(2)
		 *x	Rename dcim_power.cload to load - drop cload here - created and mirrored load prior
		 *x	increase location.name size from 10 to 50
		 * 	
		 *x	create dcim_room tables
		 *x	-create location.roomid
		 *x	-drop location.colo
		 *x	-drop location.siteid
		 * 
		 */

		//Part 1 cmds should be run as phase one as an opertunity to update code acordingly
		//after code is updated Part 2 can be run to finalize the data removing old rows and such
		
		global $resultMessage;
		global $debugMessage;
		
		$debugMessage[]= "RunDBUpdate_Update1()-Start";

		// Part 1 unsafe changes ///////////////////////////////////
		if($executePart1)
		{
			$debugMessage[]= "RunDBUpdate_Update1()-Part 1 - safe prep";
		
			$cmdm = "CREATE TABLE  `dcim_room` (
			 `roomid` INT( 8 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			 `siteid` INT( 8 ) NOT NULL ,
			 `name` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
			 `fullname` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
			 `custaccess` VARCHAR( 1 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'T',
			 `edituser` INT( 8 ) NOT NULL ,
			 `editdate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
			 `qauser` INT( 8 ) NOT NULL DEFAULT  '-1',
			 `qadate` DATETIME NOT NULL ,
			 INDEX (  `siteid` )
			 ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;";
			$cmdl = "CREATE TABLE  `dcimlog_room` (
			 `roomlogid` INT( 8 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			 `logtype` VARCHAR( 1 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'I'  ,
			 `roomid` INT( 8 ) NOT NULL  ,
			 `siteid` INT( 8 ) NOT NULL ,
			 `name` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
			 `fullname` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
			 `custaccess` VARCHAR( 1 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'T',
			 `edituser` INT( 8 ) NOT NULL ,
			 `editdate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
			 `qauser` INT( 8 ) NOT NULL DEFAULT  '-1',
			 `qadate` DATETIME NOT NULL ,
			 INDEX (  `roomid` ) ,
			 INDEX (  `siteid` )
			 ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;";
			ExecuteThis("UP1M",$cmdm);
			ExecuteThis("UP1L",$cmdl);
			
			ExecuteThis("UP2a","INSERT INTO  dcim_room (roomid,siteid,name,fullname,custaccess,edituser,editdate,qauser,qadate) VALUES (1,0,'MDF','Main Distribution Frame','F',0,CURRENT_TIMESTAMP , 0, NOW( ));");
			ExecuteThis("UP2b","INSERT INTO  dcim_room (roomid,siteid,name,fullname,custaccess,edituser,editdate,qauser,qadate) VALUES (2,0,'CA#1','Customer Area #1','T',0,CURRENT_TIMESTAMP , 0, NOW( ));");
			ExecuteThis("UP2c","INSERT INTO  dcim_room (roomid,siteid,name,fullname,custaccess,edituser,editdate,qauser,qadate) VALUES (3,0,'CA#2','Customer Area #2','T',0,CURRENT_TIMESTAMP , 0, NOW( ));");
			ExecuteThis("UP2d","INSERT INTO  dcim_room (roomid,siteid,name,fullname,custaccess,edituser,editdate,qauser,qadate) VALUES (4,0,'CA#3','Customer Area #3','T',0,CURRENT_TIMESTAMP , 0, NOW( ));");
			ExecuteThis("UP2e","INSERT INTO  dcim_room (roomid,siteid,name,fullname,custaccess,edituser,editdate,qauser,qadate) VALUES (5,0,'CA#4','Customer Area #4','T',0,CURRENT_TIMESTAMP , 0, NOW( ));");
			ExecuteThis("UP2f","INSERT INTO  dcim_room (roomid,siteid,name,fullname,custaccess,edituser,editdate,qauser,qadate) VALUES (6,0,'CA#5','Customer Area #5','T',0,CURRENT_TIMESTAMP , 0, NOW( ));");
			ExecuteThis("UP2g","INSERT INTO  dcim_room (roomid,siteid,name,fullname,custaccess,edituser,editdate,qauser,qadate) VALUES (7,0,'Unknown','Unknown/Missing','F',0,CURRENT_TIMESTAMP , 0, NOW( ));");
			 	
			ExecuteThis("UP3","INSERT INTO dcimlog_room SELECT NULL,'I' AS logtype,a.* FROM dcim_room AS a WHERE 1=1;");
			
			
			ExecuteThis("UP4M","ALTER TABLE `dcim_location`    ADD `roomid` INT( 8 ) NOT NULL AFTER  `locationid`;");
			ExecuteThis("UP4L","ALTER TABLE `dcimlog_location` ADD `roomid` INT( 8 ) NOT NULL AFTER  `locationid`;");
			ExecuteThis("UP5M","ALTER TABLE `dcim_location`    ADD INDEX ( `roomid` );");
			ExecuteThis("UP5L","ALTER TABLE `dcimlog_location` ADD INDEX ( `roomid` );");
			ExecuteThis("UP6M","UPDATE dcim_location    SET roomid= 1+CAST(colo AS UNSIGNED);");
			ExecuteThis("UP6L","UPDATE dcimlog_location SET roomid= 1+CAST(colo AS UNSIGNED);");
			ExecuteThis("UP7M","UPDATE dcim_location    SET roomid=7 WHERE colo='00';");
			ExecuteThis("UP7L","UPDATE dcimlog_location SET roomid=7 WHERE colo='00';");
			
			ExecuteThis("UP8M","ALTER TABLE `dcim_power`    ADD `load` DECIMAL( 4, 2 ) NOT NULL DEFAULT  '0' AFTER `status`;");
			ExecuteThis("UP8L","ALTER TABLE `dcimlog_power` ADD `load` DECIMAL( 4, 2 ) NOT NULL DEFAULT  '0' AFTER `status`;");
			ExecuteThis("UP9M","UPDATE dcim_power    AS p SET p.load=p.cload;");
			ExecuteThis("UP9L","UPDATE dcimlog_power AS p SET p.load=p.cload;");
			
	
			$resultMessage[]= "RunDBUpdate_Update1()-Part 1 complete";
		}
		
		// Part 2 unsafe changes /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		if($executePart2)
		{
			$debugMessage[]= "RunDBUpdate_Update1()-Part 2 - un safe DB changes";
		
			//drop dcim_deviceport.hno
			////code changes implemented and tested
			$cmdm = "ALTER TABLE `dcim_deviceport`    DROP `hno`";
			$cmdl = "ALTER TABLE `dcimlog_deviceport` DROP `hno`";
			ExecuteThis("U1M",$cmdm);
			ExecuteThis("U1L",$cmdl);
			
			//change dcim_power panel,circuit, volts, amps to NOT NULL
			//add volts default 120
			//add amp default 20
			//add load default 0
			////no code changes necisary
			$cmdm = "ALTER TABLE  `dcim_power` 		CHANGE  `panel` `panel` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
													CHANGE  `circuit` `circuit` VARCHAR( 5 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
													CHANGE  `volts` `volts` SMALLINT( 3 ) NOT NULL DEFAULT  '120',
													CHANGE  `amps` `amps` TINYINT( 2 ) NOT NULL DEFAULT  '20'";
			$cmdl = "ALTER TABLE  `dcimlog_power` 	CHANGE  `panel` `panel` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
													CHANGE  `circuit` `circuit` VARCHAR( 5 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
													CHANGE  `volts` `volts` SMALLINT( 3 ) NOT NULL DEFAULT  '120',
													CHANGE  `amps` `amps` TINYINT( 2 ) NOT NULL DEFAULT  '20'";
			ExecuteThis("U2M",$cmdm);
			ExecuteThis("U2L",$cmdl);
			
			//change vircuit from varchar(5) to tinyint(2) - reality dictates that this will be from 1-42
			////no code changes necisary - unless you wanna check some casting 
			$cmdm = "ALTER TABLE  `dcim_power`    CHANGE  `circuit`  `circuit` TINYINT( 2 ) NOT NULL";
			$cmdl = "ALTER TABLE  `dcimlog_power` CHANGE  `circuit`  `circuit` TINYINT( 2 ) NOT NULL";
			ExecuteThis("U4M",$cmdm);
			ExecuteThis("U4L",$cmdl);
			
			//drop dcim_power.cload - to be replaced by load
			////code changes implemented and tested
			$cmdm = "ALTER TABLE `dcim_power`    DROP `cload`";
			$cmdl = "ALTER TABLE `dcimlog_power` DROP `cload`";
			ExecuteThis("U6M",$cmdm);
			ExecuteThis("U6L",$cmdl);
			
			//increase location.name size from 10 to 50
			////no code changes necisary
			$cmdm = "ALTER TABLE  `dcim_location`    CHANGE  `name`  `name` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
			$cmdl = "ALTER TABLE  `dcimlog_location` CHANGE  `name`  `name` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
			ExecuteThis("U8M",$cmdm);
			ExecuteThis("U8L",$cmdl);
	
			$cmdm = "ALTER TABLE `dcim_location`    DROP `siteid`, DROP `colo`";
			$cmdl = "ALTER TABLE `dcimlog_location` DROP `siteid`, DROP `colo`";
			ExecuteThis("U9M",$cmdm);
			ExecuteThis("U9L",$cmdl);

			$resultMessage[]= "RunDBUpdate_Update1()-Part 2 complete";
		}
		//done
		
		$resultMessage[]= "RunDBUpdate_Update1()-Sucsessfully updated database.";
	}
	
	function ReCreateAllLogs()
	{
		global $resultMessage;
		
		//create insert log records for all data
		ExecuteThis("L2","INSERT INTO dcimlog_badge				SELECT NULL,'I' AS logtype,cur.* FROM dcim_badge			AS cur WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_customer			SELECT NULL,'I' AS logtype,cur.* FROM dcim_customer			AS cur WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_device			SELECT NULL,'I' AS logtype,cur.* FROM dcim_device			AS cur WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_deviceport		SELECT NULL,'I' AS logtype,cur.* FROM dcim_deviceport		AS cur WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_location			SELECT NULL,'I' AS logtype,cur.* FROM dcim_location			AS cur WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_portconnection	SELECT NULL,'I' AS logtype,cur.* FROM dcim_portconnection	AS cur WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_portvlan			SELECT NULL,'I' AS logtype,cur.* FROM dcim_portvlan			AS cur WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_power				SELECT NULL,'I' AS logtype,cur.* FROM dcim_power			AS cur WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_powerloc			SELECT NULL,'I' AS logtype,cur.* FROM dcim_powerloc			AS cur WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_room				SELECT NULL,'I' AS logtype,cur.* FROM dcim_room				AS cur WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_site				SELECT NULL,'I' AS logtype,cur.* FROM dcim_site				AS cur WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_vlan				SELECT NULL,'I' AS logtype,cur.* FROM dcim_vlan				AS cur WHERE 1=1");
		$resultMessage[]= "WipeAndReCreateAllLogs()-Sucsessfully re-created all log records.";
	}
	
	function DropAllTables()
	{
		/* This Drop all existing in all pre-existing and new tables
		 */
		global $resultMessage;
		global $mainTables;
		global $logTables;

		foreach($mainTables as $table)
		{
			ExecuteThis("D0","Drop TABLE $table");
		}
		foreach($logTables as $table)
		{
			ExecuteThis("D0","Drop TABLE $table");
		} 
		
		$resultMessage[]= "DropAllTables()-Sucsessfully Dropped all tables";
	}
	
	function TruncateAllTables()
	{
		/* This will wipe/truncate all current data in the database in all pre-existing and new tables
		 */
		global $resultMessage;
		global $mainTables;
		global $logTables;

		foreach($mainTables as $table)
		{
			ExecuteThis("D1","TRUNCATE TABLE $table");
		}
		foreach($logTables as $table)
		{
			ExecuteThis("D1","TRUNCATE TABLE $table");
		} 
		
		$resultMessage[]= "TruncateAllTables()-Sucsessfully Truncated all tables";
	}
	
	function ExecuteThisFile($debugTag,$fileName, $reportSucsess=false)
	{
		global $debugMessage;
		
		$debugMessage[]= "ExecuteThisFile($debugTag,$fileName,$reportSucsess)-Start";
		
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
		$companyNameNouns[]="Resorts       ";
		
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