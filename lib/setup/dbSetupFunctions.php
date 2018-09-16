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
	$mainTables[]="dcim_powercircuit";
	$mainTables[]="dcim_powercircuitloc";
	$mainTables[]="dcim_powerpanel";
	$mainTables[]="dcim_powerups";
	$mainTables[]="dcim_room";
	$mainTables[]="dcim_site";
	$mainTables[]="dcim_vlan";
	$mainTables[]="dcim_user";
	$mainTables[]="dcim_config";

	$logTables = array();
	$logTables[]="dcimlog_badge";
	$logTables[]="dcimlog_customer";
	$logTables[]="dcimlog_device";
	$logTables[]="dcimlog_deviceport";
	$logTables[]="dcimlog_location";
	$logTables[]="dcimlog_portconnection";
	$logTables[]="dcimlog_portvlan";
	$logTables[]="dcimlog_powercircuit";
	$logTables[]="dcimlog_powercircuitloc";
	$logTables[]="dcimlog_powerpanel";
	$logTables[]="dcimlog_powerups";
	$logTables[]="dcimlog_room";
	$logTables[]="dcimlog_site";
	$logTables[]="dcimlog_vlan";
	
	function BuildDB($restoreStructureSQLFile)
	{
		/* This will drop and recreate create the DB to current DB specs found in the documentation folder
		 */
		global $resultMessage;
		
		DropAllTables();
		
		ExecuteThisFile("RS",$restoreStructureSQLFile);
		
		$resultMessage[]= "BuildDB()-Sucsessfully created database structure";
	}
	
	function RestoreDBWithDemoData($restoreDataSQLFile)
	{
		/* This will wipe/truncate all current data in the database and repopulate it all with demo data and fresh logs
		 */
		global $resultMessage;
		
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
			if(DoesFieldExist("dcim_device","altname") && DoesFieldExist("dcim_user","siteid"))//this may warn/error if field has already been added but wont break anything the rest will still function
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
	function RunDBUpdate_Update($executePart1, $executePart2)
	{
		//updates in sync with v1.3 - DB v3
		//paramaters should be mutually exclusive, so only 1 is true at any time
		//$executePart1 = true;//safe db additions
		//$executePart2 = false; //unsafe db removals that will only work after code has been updated
		
		//Part 1 cmds should be run as phase one as an opportunity to update code acordingly
		//after code is updated Part 2 can be run to finalize the data removing old rows and such
		
		global $resultMessage;
		global $debugMessage;
		
		$debugMessage[]= "RunDBUpdate_Update1()-Start";
		
		// Part 1 unsafe changes ///////////////////////////////////
		if($executePart1)
		{
			$debugMessage[]= "RunDBUpdate_Update1()-Part 1 - safe prep";
			$reportsucsess = false;
			
			//create config table
			$cmdm = "CREATE TABLE  `dcim_config` (
					`configid` INT( 8 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`appname` VARCHAR( 200 ) NOT NULL DEFAULT  'DCIM Demo',
					`pagetitle` VARCHAR( 200 ) NOT NULL DEFAULT  'DCIM',
					`versionnote` VARCHAR( 200 ) NOT NULL DEFAULT  'note',
					`cookiedurration` INT( 3 ) NOT NULL DEFAULT  '36',
					`cookiedurrationipad` INT( 3 ) NOT NULL DEFAULT  '2',
					`badgesEnabled` VARCHAR( 1 ) NOT NULL DEFAULT  'T',
					`subnetsEnabled` VARCHAR( 1 ) NOT NULL DEFAULT  'T',
					`qaenabled` VARCHAR( 1 ) NOT NULL DEFAULT  'T',
					`demoenvironment` VARCHAR( 1 ) NOT NULL DEFAULT  'F',
					`dbversion` VARCHAR( 10 ) NOT NULL DEFAULT  '1'
					) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci";
			ExecuteThis("UP3_M-1",$cmdm,$reportsucsess);
			
			//insert config data
			$cmdm = "INSERT INTO  `dcim_config` (
					`configid` ,
					`appname` ,
					`pagetitle` ,
					`versionnote` ,
					`cookiedurration` ,
					`cookiedurrationipad` ,
					`badgesEnabled` ,
					`subnetsEnabled` ,
					`qaenabled` ,
					`demoenvironment` ,
					`dbversion`)
					VALUES (NULL ,  'DCIM Demo',  'DCIM',  'note',  '36',  '2',  'T',  'T',  'T',  'F',  '3')";
			ExecuteThis("UP3_M-2",$cmdm,$reportsucsess);
			
			//update dcim_user file
			//- update password hash field to 60 chars
			//- Add siteid field
			$cmdm = "ALTER TABLE    `dcim_user` ADD  `siteid` INT( 8 ) NOT NULL AFTER  `userid`,
					CHANGE  `pass`  `pass` VARCHAR( 60 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
					ADD INDEX  `siteid` (  `siteid` )";
			ExecuteThis("UP3_M-3",$cmdm,$reportsucsess);
			
			//update dcim_device file
			//- Add altname field
			$cmdm = "ALTER TABLE  `dcim_device` ADD  `altname` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER  `name`";
			$cmdl = "ALTER TABLE  `dcimlog_device` ADD  `altname` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER  `name`";
			ExecuteThis("UP3_M-4",$cmdm,$reportsucsess);
			ExecuteThis("UP3_L-4",$cmdl,$reportsucsess);
			
			//update location
			//- add keyno field (20c)
			//- add allocation field (1c) - empty, internal, managed, colo
			//- add order field (1c) (t/f) - reversed for racks vs cabinets
			$cmdm = "ALTER TABLE  `dcim_location`    ADD  `keyno` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER  `orientation` ,
					ADD  `allocation` VARCHAR( 1 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'E' AFTER  `keyno` ,
					ADD  `order` VARCHAR( 1 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'S' AFTER  `allocation`";
			$cmdl = "ALTER TABLE  `dcimlog_location` ADD  `keyno` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER  `orientation` ,
					ADD  `allocation` VARCHAR( 1 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'E' AFTER  `keyno` ,
					ADD  `order` VARCHAR( 1 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'S' AFTER  `allocation`";
			ExecuteThis("UP3_M-5",$cmdm,$reportsucsess);
			ExecuteThis("UP3_L-5",$cmdl,$reportsucsess);
			
			////start power changes
			
			//Add dcim_powerpanel
			// - panelid, upsid, name, amps, circuits, roomid, xpos, ypos, width, depth, orientation, note
			$cmdm = "CREATE TABLE  `dcim_powerpanel` (
						`powerpanelid` INT( 8 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
						`powerupsid` INT( 8 ) NOT NULL ,
						`name` VARCHAR( 50 ) NOT NULL ,
						`amps` INT( 4 ) NOT NULL ,
						`circuits` INT( 3 ) NOT NULL DEFAULT  '0',
						`roomid` INT( 8 ) NOT NULL ,
						`xpos` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0.0',
						`ypos` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0.0',
						`width` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0.0',
						`depth` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0.0',
						`orientation` VARCHAR( 1 ) NOT NULL DEFAULT  'N',
						`note` TEXT NOT NULL ,
						`edituser` INT( 8 ) NOT NULL ,
						`editdate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
						`qauser` INT( 8 ) NOT NULL DEFAULT  '-1',
						`qadate` DATETIME NOT NULL ,
						INDEX (  `powerupsid` ),
						INDEX (  `roomid` ),
						INDEX (  `qauser` )
						) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci";
			$cmdl = "CREATE TABLE  `dcimlog_powerpanel` (
						`powerpanellogid` INT( 8 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
						`logtype` VARCHAR( 1 ) NOT NULL DEFAULT 'I',
						`powerpanelid` INT( 8 ) NOT NULL ,
						`powerupsid` INT( 8 ) NOT NULL ,
						`name` VARCHAR( 50 ) NOT NULL ,
						`amps` INT( 4 ) NOT NULL ,
						`circuits` INT( 3 ) NOT NULL DEFAULT  '0',
						`roomid` INT( 8 ) NOT NULL ,
						`xpos` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0.0',
						`ypos` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0.0',
						`width` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0.0',
						`depth` DECIMAL( 6, 2 ) NOT NULL DEFAULT  '0.0',
						`orientation` VARCHAR( 1 ) NOT NULL DEFAULT  'N',
						`note` TEXT NOT NULL ,
						`edituser` INT( 8 ) NOT NULL ,
						`editdate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
						`qauser` INT( 8 ) NOT NULL DEFAULT  '-1',
						`qadate` DATETIME NOT NULL ,
						INDEX (  `powerpanelid` ),
						INDEX (  `powerupsid` ),
						INDEX (  `roomid` ),
						INDEX (  `qauser` )
						) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci";
			ExecuteThis("UP3_M-6",$cmdm,$reportsucsess);
			ExecuteThis("UP3_L-6",$cmdl,$reportsucsess);
			
			//Add dcim_powerups
			//- upsid, name, volts, amps, note
			$cmdm = "CREATE TABLE  `dcim_powerups` (
					`powerupsid` INT( 8 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`name` VARCHAR( 32 ) NOT NULL ,
					`volts` INT( 5 ) NOT NULL ,
					`amps` INT( 5 ) NOT NULL ,
					`note` TEXT NOT NULL ,
					`edituser` INT( 8 ) NOT NULL ,
					`editdate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
					`qauser` INT( 8 ) NOT NULL DEFAULT  '-1',
					`qadate` DATETIME NOT NULL
					) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci";
			$cmdl = "CREATE TABLE  `dcimlog_powerups` (
					`powerupslogid` INT( 8 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`logtype` VARCHAR( 1 ) NOT NULL DEFAULT 'I',
					`powerupsid` INT( 8 ) NOT NULL ,
					`name` VARCHAR( 32 ) NOT NULL ,
					`volts` INT( 5 ) NOT NULL ,
					`amps` INT( 5 ) NOT NULL ,
					`note` TEXT NOT NULL ,
					`edituser` INT( 8 ) NOT NULL ,
					`editdate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
					`qauser` INT( 8 ) NOT NULL DEFAULT  '-1',
					`qadate` DATETIME NOT NULL
					) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci";
			ExecuteThis("UP3_M-7",$cmdm,$reportsucsess);
			ExecuteThis("UP3_L-7",$cmdl,$reportsucsess);
			
			//rename dcim_power to dcim_powercircuit
			$cmdm = "RENAME TABLE  `dcim_power`    TO  `dcim_powercircuit`";
			$cmdl = "RENAME TABLE  `dcimlog_power` TO  `dcimlog_powercircuit`";
			ExecuteThis("UP3_M-8",$cmdm,$reportsucsess);
			ExecuteThis("UP3_L-8",$cmdl,$reportsucsess);
			
			//rename dcim_powerloc to dcim_powercircuitloc
			$cmdm = "RENAME TABLE  `dcim_powerloc`    TO  `dcim_powercircuitloc`";
			$cmdl = "RENAME TABLE  `dcimlog_powerloc` TO  `dcimlog_powercircuitloc`";
			ExecuteThis("UP3_M-9",$cmdm,$reportsucsess);
			ExecuteThis("UP3_L-9",$cmdl,$reportsucsess);
			
			//rename powerid to powercircuitid in dcim_powercircuit
			//removed 'unsigned' - for some reason this was the only field that was tagged as such
			//add field powerpanelid
			$cmdm = "ALTER TABLE  `dcim_powercircuit`    CHANGE  `powerid`  `powercircuitid` SMALLINT( 8 ) NOT NULL AUTO_INCREMENT,
					ADD  `powerpanelid` INT( 8 ) NOT NULL AFTER  `powercircuitid`,
					CHANGE  `circuit`  `circuit` TINYINT( 3 ) NOT NULL";
			$cmdl = "ALTER TABLE  `dcimlog_powercircuit` CHANGE  `powerlogid`  `powercircuitlogid` SMALLINT( 8 ) NOT NULL AUTO_INCREMENT,
					CHANGE  `powerid`  `powercircuitid` SMALLINT( 8 ) NOT NULL,
					ADD  `powerpanelid` INT( 8 ) NOT NULL AFTER  `powercircuitid`,
					CHANGE  `circuit`  `circuit` TINYINT( 3 ) NOT NULL";
			ExecuteThis("UP3_M-10",$cmdm,$reportsucsess);
			ExecuteThis("UP3_L-10",$cmdl,$reportsucsess);
			
			//add indexes for new fields
			$cmdm = "ALTER TABLE  `dcim_powercircuit`    ADD INDEX (  `powerpanelid` )";
			$cmdl = "ALTER TABLE  `dcimlog_powercircuit` ADD INDEX (  `powerpanelid` )";
			ExecuteThis("UP3_M-11",$cmdm,$reportsucsess);
			ExecuteThis("UP3_L-11",$cmdl,$reportsucsess);
			
			//rename powerid to powercircuitid in dcim_powercircuitloc - just renaming the fields so we dont need to recreate the indexes
			$cmdm = "ALTER TABLE  `dcim_powercircuitloc`    CHANGE  `powerlocid`  `powercircuitlocid` INT( 8 ) NOT NULL AUTO_INCREMENT ,
					CHANGE  `powerid`  `powercircuitid` INT( 8 ) NOT NULL DEFAULT  '0'";
			$cmdl = "ALTER TABLE  `dcimlog_powercircuitloc` CHANGE  `powerloclogid`  `powercircuitloclogid` INT( 8 ) NOT NULL AUTO_INCREMENT,
					CHANGE  `powerlocid`  `powercircuitlocid` INT( 8 ) NOT NULL ,
					CHANGE  `powerid`  `powercircuitid` INT( 8 ) NOT NULL DEFAULT  '0'";
			ExecuteThis("UP3_M-12",$cmdm,$reportsucsess);
			ExecuteThis("UP3_L-12",$cmdl,$reportsucsess);
			
			
			$pullPanelRecsFromOldPowerCircuits = true;
			if($pullPanelRecsFromOldPowerCircuits)
			{
				//assumes valid legacy panel data
				//create parent records for panels
				
				$cmd = "INSERT INTO dcim_powerpanel (name,powerupsid,amps,circuits,roomid,note)
					SELECT DISTINCT panel, 0,125,42,-1,'Automated'
					FROM dcim_powercircuit";
				ExecuteThis("UP3_panelcreation-1",$cmd,$reportsucsess);
				
				$cmdm = "UPDATE dcim_powercircuit AS pc, dcim_powerpanel AS pp 
					SET pc.powerpanelid=pp.powerpanelid
					WHERE pc.panel=pp.name";
				//puposely referencing dcim_powerpanel and not dcimlog powerpanel - logs dont exist
				$cmdl = "UPDATE dcimlog_powercircuit AS pc, dcim_powerpanel AS pp 
					SET pc.powerpanelid=pp.powerpanelid
					WHERE pc.panel=pp.name";
				ExecuteThis("UP3_panelcreation-m-2",$cmdm,$reportsucsess);
				ExecuteThis("UP3_panelcreation-l-2",$cmdl,$reportsucsess);
				
				$cmdm = "ALTER TABLE `dcim_powercircuit` DROP `panel`";
				$cmdl = "ALTER TABLE `dcimlog_powercircuit` DROP `panel`";
				ExecuteThis("UP3_panelcreation-m-3",$cmdm,$reportsucsess);
				ExecuteThis("UP3_panelcreation-l-3",$cmdl,$reportsucsess);
				
				//clean up  powerpanel logs
				ExecuteThis("UP3_panelcreation-4","INSERT INTO dcimlog_powerpanel			SELECT NULL,'I' AS logtype,cur.* FROM dcim_powerpanel		AS cur WHERE 1=1",$reportsucsess);
			}
			
			$resultMessage[]= "RunDBUpdate_Update1()-Part 1 complete";
		}
		
		// Part 2 unsafe changes /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		if($executePart2)
		{
			$debugMessage[]= "RunDBUpdate_Update1()-Part 2 - un safe DB changes";
			/*
			$cmdm = "ALTER TABLE `dcim_location` DROP `size`;";
			$cmdl = "ALTER TABLE  `dcimlog_location` DROP `size`;";
			ExecuteThis("UP2_1M",$cmdm);
			ExecuteThis("UP2_1L",$cmdl);
			
			$cmdm = "ALTER TABLE `dcim_location` DROP `status`;";
			$cmdl = "ALTER TABLE  `dcimlog_location` DROP `status`;";
			ExecuteThis("UP2_2M",$cmdm);
			ExecuteThis("UP2_2L",$cmdl);
			*/
			
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
	
	function QAAllRecordsAsAdmin()
	{
		$adminUserID=0;

		ExecuteThis("Q1","UPDATE dcim_badge				SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_customer			SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_device			SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_deviceport		SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_location			SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_portconnection	SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_portvlan			SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_power				SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_powerloc			SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_room				SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_site				SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_vlan				SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		$resultMessage[]= "QAAllRecordsAsAdmin()-Sucsessfully QAed all outstanding records.";
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
			ExecuteThis("D0","Drop TABLE IF EXISTS $table");
		}
		foreach($logTables as $table)
		{
			ExecuteThis("D0","Drop TABLE IF EXISTS $table");
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
	
	function RestoreDemoCreds()
	{
		global $resultMessage;
		global $errorMessage;
		global $debugMessage;
		
		$errorMessage[] = "RestoreDemoCreds() - Stub - did nothing";
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