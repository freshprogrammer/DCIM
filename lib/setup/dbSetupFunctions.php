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
			if(DoesFieldExist("dcim_room","xpos") && DoesFieldExist("dcim_room","layer"))//this may warn/error if xpos has already been added but wont break anything the rest will still function
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
				if(DoesFieldExist("dcim_location","size"))
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
		//paramaters should be mutually exclusive, so only 1 is true at any time
		//$executePart1 = true;//safe db additions
		//$executePart2 = false; //unsafe db removals that will only work after code has been updated
		
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
			
			//added later so its seperate
			$cmdm = "ALTER TABLE `dcim_room` ADD `layer` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `orientation`";
			$cmdl = "ALTER TABLE `dcimlog_room` ADD `layer` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `orientation`";
			ExecuteThis("UP1_4M",$cmdm);
			ExecuteThis("UP1_4L",$cmdl);
			
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
		$companyNameNouns[]="American      ";
		$companyNameNouns[]="Applications  ";
		$companyNameNouns[]="Apps          ";
		$companyNameNouns[]="Baking        ";
		$companyNameNouns[]="Bit           ";
		$companyNameNouns[]="Bits          ";
		$companyNameNouns[]="Boating       ";
		$companyNameNouns[]="Bubbles       ";
		$companyNameNouns[]="Burgers       ";
		$companyNameNouns[]="Byte          ";
		$companyNameNouns[]="Bytes         ";
		$companyNameNouns[]="Carbs         ";
		$companyNameNouns[]="Cars          ";
		$companyNameNouns[]="Coding        ";
		$companyNameNouns[]="Comms         ";
		$companyNameNouns[]="Communications";
		$companyNameNouns[]="Computer      ";
		$companyNameNouns[]="Computers     ";
		$companyNameNouns[]="Computing     ";
		$companyNameNouns[]="Connections   ";
		$companyNameNouns[]="Consulting    ";
		$companyNameNouns[]="Corp          ";
		$companyNameNouns[]="Credits       ";
		$companyNameNouns[]="Data          ";
		$companyNameNouns[]="Devices       ";
		$companyNameNouns[]="Drones        ";
		$companyNameNouns[]="Dynamics      ";
		$companyNameNouns[]="Firearms      ";
		$companyNameNouns[]="Fireworks     ";
		$companyNameNouns[]="Food          ";
		$companyNameNouns[]="Friends       ";
		$companyNameNouns[]="Genisis       ";
		$companyNameNouns[]="Guns          ";
		$companyNameNouns[]="Gyms          ";
		$companyNameNouns[]="Hosting       ";
		$companyNameNouns[]="Interactive   ";
		$companyNameNouns[]="Labs          ";
		$companyNameNouns[]="Loads         ";
		$companyNameNouns[]="Otomization   ";
		$companyNameNouns[]="Portals       ";
		$companyNameNouns[]="Reloading     ";
		$companyNameNouns[]="Resorts       ";
		$companyNameNouns[]="Revolution    ";
		$companyNameNouns[]="Robotics      ";
		$companyNameNouns[]="SEO           ";
		$companyNameNouns[]="Search        ";
		$companyNameNouns[]="Software      ";
		$companyNameNouns[]="Solutions     ";
		$companyNameNouns[]="Sorting       ";
		$companyNameNouns[]="Systems       ";
		$companyNameNouns[]="TV            ";
		$companyNameNouns[]="Tech          ";
		$companyNameNouns[]="Technology    ";
		
		$adjetives = array();
		$adjetives[]="Arial          ";
		$adjetives[]="Base           ";
		$adjetives[]="Baseline       ";
		$adjetives[]="Best           ";
		$adjetives[]="Better         ";
		$adjetives[]="Blue           ";
		$adjetives[]="Budget         ";
		$adjetives[]="Centrailized   ";
		$adjetives[]="Cherry         ";
		$adjetives[]="Chocolate      ";
		$adjetives[]="Core           ";
		$adjetives[]="Cornerstone    ";
		$adjetives[]="Creative       ";
		$adjetives[]="Data           ";
		$adjetives[]="E              ";
		$adjetives[]="Energy         ";
		$adjetives[]="Global         ";
		$adjetives[]="Good           ";
		$adjetives[]="Great          ";
		$adjetives[]="Green          ";
		$adjetives[]="Happy          ";
		$adjetives[]="Hyper          ";
		$adjetives[]="Important      ";
		$adjetives[]="Internet       ";
		$adjetives[]="Intuitive      ";
		$adjetives[]="Lead           ";
		$adjetives[]="Learning       ";
		$adjetives[]="Local          ";
		$adjetives[]="Loud           ";
		$adjetives[]="Main           ";
		$adjetives[]="Mega           ";
		$adjetives[]="Neon           ";
		$adjetives[]="Net            ";
		$adjetives[]="New            ";
		$adjetives[]="One            ";
		$adjetives[]="Online         ";
		$adjetives[]="Optical        ";
		$adjetives[]="Original       ";
		$adjetives[]="Pacific        ";
		$adjetives[]="Pivotal        ";
		$adjetives[]="Planet         ";
		$adjetives[]="Platinum       ";
		$adjetives[]="Productive     ";
		$adjetives[]="Progressive    ";
		$adjetives[]="Red            ";
		$adjetives[]="Revolutionary  ";
		$adjetives[]="Sad            ";
		$adjetives[]="Techy          ";
		$adjetives[]="Test           ";
		$adjetives[]="The Best       ";
		$adjetives[]="Top            ";
		$adjetives[]="Vanila         ";
		$adjetives[]="White          ";
		$adjetives[]="Wireless       ";
		return trim($adjetives[array_rand($adjetives)]) . " " . trim($companyNameNouns[array_rand($companyNameNouns)]);
	}
	
	function GenRandomPersonName()
	{
		$firstNames = array();
		$firstNames[]="Adam       ";
		$firstNames[]="Alex       ";
		$firstNames[]="Anthony    ";
		$firstNames[]="Arthur     ";
		$firstNames[]="Bill       ";
		$firstNames[]="Bob        ";
		$firstNames[]="Brandon    ";
		$firstNames[]="Brannon    ";
		$firstNames[]="Brian      ";
		$firstNames[]="Brittney   ";
		$firstNames[]="Bruce      ";
		$firstNames[]="Bryan      ";
		$firstNames[]="Burnie     ";
		$firstNames[]="Chris      ";
		$firstNames[]="Christopher";
		$firstNames[]="Clint      ";
		$firstNames[]="Craig      ";
		$firstNames[]="Danielle   ";
		$firstNames[]="Derrick    ";
		$firstNames[]="Donald     ";
		$firstNames[]="Doug       ";
		$firstNames[]="Eric       ";
		$firstNames[]="EriK       ";
		$firstNames[]="Gavin      ";
		$firstNames[]="Greg       ";
		$firstNames[]="Hank       ";
		$firstNames[]="Heidi      ";
		$firstNames[]="Helen      ";
		$firstNames[]="Homer      ";
		$firstNames[]="Ivan       ";
		$firstNames[]="Jack       ";
		$firstNames[]="James      ";
		$firstNames[]="Jason      ";
		$firstNames[]="Jennifer   ";
		$firstNames[]="Jeremy     ";
		$firstNames[]="Jerry      ";
		$firstNames[]="Jesse      ";
		$firstNames[]="Jim        ";
		$firstNames[]="Joe        ";
		$firstNames[]="John       ";
		$firstNames[]="Joseph     ";
		$firstNames[]="Josh       ";
		$firstNames[]="Joshua     ";
		$firstNames[]="Julie      ";
		$firstNames[]="Justin     ";
		$firstNames[]="Kelly      ";
		$firstNames[]="Kim        ";
		$firstNames[]="Kyle       ";
		$firstNames[]="Lenard     ";
		$firstNames[]="Leo        ";
		$firstNames[]="Lori       ";
		$firstNames[]="Luck       ";
		$firstNames[]="Mario      ";
		$firstNames[]="Matt       ";
		$firstNames[]="Michael    ";
		$firstNames[]="Mike       ";
		$firstNames[]="Monty      ";
		$firstNames[]="Pablo      ";
		$firstNames[]="Paul       ";
		$firstNames[]="Pete       ";
		$firstNames[]="Peter      ";
		$firstNames[]="Ray        ";
		$firstNames[]="Rich       ";
		$firstNames[]="Richard    ";
		$firstNames[]="Rick       ";
		$firstNames[]="Robert     ";
		$firstNames[]="Roman      ";
		$firstNames[]="Sam        ";
		$firstNames[]="Scott      ";
		$firstNames[]="Sean       ";
		$firstNames[]="Shawn      ";
		$firstNames[]="Stan       ";
		$firstNames[]="Thommas    ";
		$firstNames[]="Tiffani    ";
		$firstNames[]="Tim        ";
		$firstNames[]="Timmy      ";
		$firstNames[]="Tom        ";
		
		$lastNames = array();
		$lastNames[]="Adams      ";
		$lastNames[]="Alexander  ";
		$lastNames[]="Baker      ";
		$lastNames[]="Barnes     ";
		$lastNames[]="Bell       ";
		$lastNames[]="Black      ";
		$lastNames[]="Brown      ";
		$lastNames[]="Burns      ";
		$lastNames[]="Campbel    ";
		$lastNames[]="Carter     ";
		$lastNames[]="Chang      ";
		$lastNames[]="Ching      ";
		$lastNames[]="Clark      ";
		$lastNames[]="Clinton    ";
		$lastNames[]="Cooper     ";
		$lastNames[]="Cox        ";
		$lastNames[]="Davis      ";
		$lastNames[]="Garcia     ";
		$lastNames[]="Gonsolez   ";
		$lastNames[]="Gray       ";
		$lastNames[]="Green      ";
		$lastNames[]="Hall       ";
		$lastNames[]="Hernadez   ";
		$lastNames[]="Hill       ";
		$lastNames[]="Hitler     ";
		$lastNames[]="Jackson    ";
		$lastNames[]="Jenkins    ";
		$lastNames[]="Jones      ";
		$lastNames[]="Kenedy     ";
		$lastNames[]="King       ";
		$lastNames[]="Lopez      ";
		$lastNames[]="Mitchel    ";
		$lastNames[]="Nelson     ";
		$lastNames[]="Omm        ";
		$lastNames[]="Ownens     ";
		$lastNames[]="Perez      ";
		$lastNames[]="Phillips   ";
		$lastNames[]="Pinkerton  ";
		$lastNames[]="Ramzey     ";
		$lastNames[]="Reed       ";
		$lastNames[]="Roberts    ";
		$lastNames[]="Rodgers    ";
		$lastNames[]="Ross       ";
		$lastNames[]="Ryu        ";
		$lastNames[]="Simpson    ";
		$lastNames[]="Smith      ";
		$lastNames[]="Trump      ";
		$lastNames[]="Turners    ";
		$lastNames[]="Walker     ";
		$lastNames[]="Warren     ";
		$lastNames[]="White      ";
		$lastNames[]="Whittman   ";
		$lastNames[]="Williams   ";
		$lastNames[]="Wood       ";
		$lastNames[]="Young      ";
		$lastNames[]="Yun        ";
		
		return trim($firstNames[array_rand($firstNames)]) . " " . trim($lastNames[array_rand($lastNames)]);
	}
?>