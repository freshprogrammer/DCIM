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
		
		TruncateTables(true,true);
		
		//NOTE: this is just restoring the data in the core tables and not the logs
		ExecuteThisFile("RD",$restoreDataSQLFile);
		
		WipeAndReCreateAllLogs();
		
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
			if(DoesFieldExist("dcim_room","xpos"))
				return -1;//already up to date
			else
				return 1;// good to update
		}
		else if($executePart2)
		{
			//if part 1 up to date
			if(IsDatabaseUpToDate_Update1(true,false)==-1)
			{
				// do legacy fields still exist
				if(DoesFieldExist("dcim_location","colo"))
					return 1;// good to update
				else
					return -1;//already up to date
			}
			else
				return 0;//not executed part 1 yet
		}
		return 0;//shouldn't be able to get here - should be caught above
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
			
			//add new files to room, site and location tables for site layout values
			$cmdm = "ALTER TABLE  `dcim_room`
					ADD  `xpos` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0' AFTER  `custaccess` ,
					ADD  `ypos` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0' AFTER  `xpos` ,
					ADD  `width` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0' AFTER  `ypos` ,
					ADD  `depth` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0' AFTER  `width` ,
					ADD  `orientation` VARCHAR( 1 ) NOT NULL DEFAULT  'N' AFTER  `depth`";
			$cmdl = "ALTER TABLE  `dcimlog_room`
					ADD  `xpos` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0' AFTER  `custaccess` ,
					ADD  `ypos` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0' AFTER  `xpos` ,
					ADD  `width` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0' AFTER  `ypos` ,
					ADD  `depth` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0' AFTER  `width` ,
					ADD  `orientation` VARCHAR( 1 ) NOT NULL DEFAULT  'N' AFTER  `depth`";
			ExecuteThis("UP1_1M",$cmdm);
			ExecuteThis("UP1_1L",$cmdl);
			
			$cmdm = "ALTER TABLE  `dcim_site`
					ADD  `width` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0' AFTER  `fullname` ,
					ADD  `depth` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0' AFTER  `width` ;";
			$cmdl = "ALTER TABLE  `dcimlog_site`
					ADD  `width` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0' AFTER  `fullname` ,
					ADD  `depth` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0' AFTER  `width` ;";
			ExecuteThis("UP1_2M",$cmdm);
			ExecuteThis("UP1_2L",$cmdl);
			
			$cmdm = "ALTER TABLE  `dcim_location`
					ADD  `xpos` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0' AFTER  `status` ,
					ADD  `ypos` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0' AFTER  `xpos` ,
					ADD  `width` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0' AFTER  `ypos` ,
					ADD  `depth` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0' AFTER  `width` ,
					ADD  `orientation` VARCHAR( 1 ) NOT NULL DEFAULT  'N' AFTER  `depth` ,
					ADD  `altname` VARCHAR( 50 ) NOT NULL DEFAULT  '' AFTER  `name` ,
					ADD  `note` TEXT NOT NULL DEFAULT '' AFTER  `visible`";
			$cmdl = "ALTER TABLE  `dcimlog_location`
					ADD  `xpos` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0' AFTER  `status` ,
					ADD  `ypos` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0' AFTER  `xpos` ,
					ADD  `width` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0' AFTER  `ypos` ,
					ADD  `depth` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0' AFTER  `width` ,
					ADD  `orientation` VARCHAR( 1 ) NOT NULL DEFAULT  'N' AFTER  `depth` ,
					ADD  `altname` VARCHAR( 50 ) NOT NULL DEFAULT  '' AFTER  `name` ,
					ADD  `note` TEXT NOT NULL DEFAULT '' AFTER  `visible`";
			ExecuteThis("UP1_3M",$cmdm);
			ExecuteThis("UP1_3L",$cmdl);
			
			$resultMessage[]= "RunDBUpdate_Update1()-Part 1 complete";
		}
		
		// Part 2 unsafe changes /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		if($executePart2)
		{
			$debugMessage[]= "RunDBUpdate_Update1()-Part 2 - un safe DB changes";
			
			$cmdm = "ALTER TABLE `dcim_location` DROP `size`;";
			$cmdl = "ALTER TABLE  `dcimlog_location` DROP `size`;";
			ExecuteThis("UP2_1M",$cmdm);
			ExecuteThis("UP2_1L",$cmdl);
			
			$cmdm = "ALTER TABLE `dcim_location` DROP `status`;";
			$cmdl = "ALTER TABLE  `dcimlog_location` DROP `status`;";
			ExecuteThis("UP2_2M",$cmdm);
			ExecuteThis("UP2_2L",$cmdl);
			
			
			$resultMessage[]= "RunDBUpdate_Update1()-Part 2 complete";
		}
		//done
		
		$resultMessage[]= "RunDBUpdate_Update1()-Sucsessfully updated database.";
	}
	
	function WipeAndReCreateAllLogs()
	{
		global $resultMessage;
		
		TruncateTables(false,true);
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
	
	function TruncateTables($wipeMainTables,$wipeLogTables)
	{
		/* This will wipe/truncate all current data in the database in all pre-existing and new tables
		 */
		global $resultMessage;
		global $mainTables;
		global $logTables;

		if($wipeMainTables)
		{
			foreach($mainTables as $table)
			{
				ExecuteThis("D1","TRUNCATE TABLE $table");
			}
		}
		if($wipeLogTables)
		{
			foreach($logTables as $table)
			{
				ExecuteThis("D1","TRUNCATE TABLE $table");
			}
		}
		
		$resultMessage[]= "TruncateTables($mainTables,$logTables)-Sucsessfully truncated tables";
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
			$errorMessage[] = "ExecuteThis()-Prepare failed:($debugTag,$query) (" . $mysqli->errno . ") - E:" . $mysqli->error;
			return false;
		}
		else
		{
			if(!$stmt->execute())
			{
				$errorMessage[] = "ExecuteThis()-Error executing($debugTag): $query. - E:" . $mysqli->error;
				return false;
			}
			else 
			{
				if($reportSucsess)
					$resultMessage[] = "ExecuteThis()-Sucsessfully executed($debugTag): $query. - E:" . $mysqli->error;
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
		$companyNameNouns[]="Boating       ";
		
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