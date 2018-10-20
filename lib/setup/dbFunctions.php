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
		global $resultMessage;
		global $debugMessage;
		global $mainTables;
		global $logTables;
		global $config_demoSiteEnabled;
		
		//updates in sync with v1.3 - DB v3
		//paramaters should be mutually exclusive, so only 1 is true at any time
		//$executePart1 = true;//safe db additions
		//$executePart2 = false; //unsafe db removals that will only work after code has been updated
		
		//Part 1 cmds should be run as phase one as an opportunity to update code acordingly
		//after code is updated Part 2 can be run to finalize the data removing old rows and such
		
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
			//- add order field (1c) (t/f) - reversed for cabinets vs racks
			$cmdm = "ALTER TABLE  `dcim_location`    ADD  `keyno` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER  `orientation` ,
					ADD  `allocation` VARCHAR( 1 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'E' AFTER  `keyno` ,
					ADD  `order` VARCHAR( 1 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'R' AFTER  `allocation`";
			$cmdl = "ALTER TABLE  `dcimlog_location` ADD  `keyno` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER  `orientation` ,
					ADD  `allocation` VARCHAR( 1 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'E' AFTER  `keyno` ,
					ADD  `order` VARCHAR( 1 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'R' AFTER  `allocation`";
			ExecuteThis("UP3_M-5",$cmdm,$reportsucsess);
			ExecuteThis("UP3_L-5",$cmdl,$reportsucsess);
			
			////start power changes
			
			//Add dcim_powerpanel
			// - panelid, upsid, name, amps, circuits, roomid, xpos, ypos, width, depth, orientation, note
			$cmdm = "CREATE TABLE  `dcim_powerpanel` (
						`powerpanelid` INT( 8 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
						`powerupsid` INT( 8 ) NOT NULL ,
						`roomid` INT( 8 ) NOT NULL ,
						`name` VARCHAR( 50 ) NOT NULL ,
						`amps` INT( 4 ) NOT NULL ,
						`circuits` INT( 3 ) NOT NULL DEFAULT  '0',
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
						`roomid` INT( 8 ) NOT NULL ,
						`name` VARCHAR( 50 ) NOT NULL ,
						`amps` INT( 4 ) NOT NULL ,
						`circuits` INT( 3 ) NOT NULL DEFAULT  '0',
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
					`siteid` INT( 8 ) NOT NULL ,
					`name` VARCHAR( 32 ) NOT NULL ,
					`volts` INT( 5 ) NOT NULL ,
					`amps` INT( 5 ) NOT NULL ,
					`note` TEXT NOT NULL ,
					`edituser` INT( 8 ) NOT NULL ,
					`editdate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
					`qauser` INT( 8 ) NOT NULL DEFAULT  '-1',
					`qadate` DATETIME NOT NULL,
					INDEX (  `siteid` ),
					INDEX (  `qauser` )
					) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci";
			$cmdl = "CREATE TABLE  `dcimlog_powerups` (
					`powerupslogid` INT( 8 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`logtype` VARCHAR( 1 ) NOT NULL DEFAULT 'I',
					`powerupsid` INT( 8 ) NOT NULL ,
					`siteid` INT( 8 ) NOT NULL ,
					`name` VARCHAR( 32 ) NOT NULL ,
					`volts` INT( 5 ) NOT NULL ,
					`amps` INT( 5 ) NOT NULL ,
					`note` TEXT NOT NULL ,
					`edituser` INT( 8 ) NOT NULL ,
					`editdate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
					`qauser` INT( 8 ) NOT NULL DEFAULT  '-1',
					`qadate` DATETIME NOT NULL,
					INDEX (  `powerupsid` ),
					INDEX (  `siteid` ),
					INDEX (  `qauser` )
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
					SELECT DISTINCT pc.panel, 0,225,42,r.roomid,'Automated'
						FROM dcim_powercircuit AS pc
						LEFT JOIN dcim_powercircuitloc AS pcl ON pc.powercircuitid=pcl.powercircuitid
						LEFT JOIN dcim_location AS l ON pcl.locationid=l.locationid
						LEFT JOIN dcim_room AS r ON l.roomid=r.roomid";
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
			}
			
			$createDemoPowerUPSs = $config_demoSiteEnabled;
			if($createDemoPowerUPSs)
			{
				$cmd = "INSERT INTO dcim_powerups (powerupsid,siteid,name,volts,amps,note) VALUES (1,0,'UPS-1',480,500,'Automated')";
				ExecuteThis("UP3_upscreation-1",$cmd,$reportsucsess);
				
				$cmd = "INSERT INTO dcim_powerups (powerupsid,siteid,name,volts,amps,note) VALUES (2,0,'UPS-2',480,500,'Automated')";
				ExecuteThis("UP3_upscreation-2",$cmd,$reportsucsess);
				
				ExecuteThis("UP3_upscreation-3","INSERT INTO dcimlog_powerups			SELECT NULL,'I' AS logtype,cur.* FROM dcim_powerups		AS cur WHERE 1=1",$reportsucsess);
				
				//set all panels in these rooms to UPS-2
				$cmd = "UPDATE dcim_powerups AS pu, dcim_room AS r, dcim_powerpanel AS pp
					SET pp.powerupsid=2
					WHERE FIND_IN_SET(r.roomid,'4,5,7,10,12,13') 
						AND r.roomid=pp.roomid";
				ExecuteThis("UP3_upscreation-4",$cmd,$reportsucsess);
				
				//set all other panels to UPS-1
				$cmd = "UPDATE dcim_powerpanel AS pp
					SET pp.powerupsid=1
					WHERE pp.powerupsid!=2";
				ExecuteThis("UP3_upscreation-5",$cmd,$reportsucsess);
			}
			
			if($pullPanelRecsFromOldPowerCircuits || $createDemoPowerUPSs)
			{
				//clean up  powerpanel logs
				ExecuteThis("UP3_panel-logs","INSERT INTO dcimlog_powerpanel			SELECT NULL,'I' AS logtype,cur.* FROM dcim_powerpanel		AS cur WHERE 1=1",$reportsucsess);
			}
			
			//update indexes
			ExecuteThis("UP3_updateIndexes_01","ALTER TABLE    `dcim_room` ADD INDEX (`qauser`)",$reportsucsess);
			ExecuteThis("UP3_updateIndexes_02","ALTER TABLE `dcimlog_room` ADD INDEX (`qauser`)",$reportsucsess);
			ExecuteThis("UP3_updateIndexes_03","ALTER TABLE    `dcim_customer` ADD INDEX (`cno`)",$reportsucsess);
			ExecuteThis("UP3_updateIndexes_04","ALTER TABLE `dcimlog_customer` ADD INDEX (`cno`)",$reportsucsess);
			ExecuteThis("UP3_updateIndexes_05","ALTER TABLE    `dcim_device` ADD INDEX (`locationid`), ADD INDEX (`name`), ADD INDEX (`altname`)",$reportsucsess);
			ExecuteThis("UP3_updateIndexes_06","ALTER TABLE `dcimlog_device` ADD INDEX (`locationid`), ADD INDEX (`name`), ADD INDEX (`altname`)",$reportsucsess);
			
			//update engine
			ExecuteThis("UP3_UpdateEngine_0","SET default_storage_engine=INNODB;",$reportsucsess);
			foreach($mainTables as $table)
			{
				ExecuteThis("UP3_UpdateEngine_$table","ALTER TABLE  `$table` ENGINE=INNODB");
			}
			foreach($logTables as $table)
			{
				ExecuteThis("UP3_UpdateEngine_$table","ALTER TABLE  `$table` ENGINE=INNODB");
			}
			
			$resultMessage[]= "RunDBUpdate_Update1()-Part 1 complete";
			
			
			$resultMessage[]= "RunDBUpdate_Update1()-Part 1 Still need to create and sync power panels to ups records. Also need to create 208v3p power records (3 each)";
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
		ExecuteThis("L2","INSERT INTO dcimlog_powercircuit		SELECT NULL,'I' AS logtype,cur.* FROM dcim_powercircuit		AS cur WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_powercircuitloc	SELECT NULL,'I' AS logtype,cur.* FROM dcim_powercircuitloc	AS cur WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_powerpanel		SELECT NULL,'I' AS logtype,cur.* FROM dcim_powerpanel		AS cur WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_powerups			SELECT NULL,'I' AS logtype,cur.* FROM dcim_powerups			AS cur WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_room				SELECT NULL,'I' AS logtype,cur.* FROM dcim_room				AS cur WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_site				SELECT NULL,'I' AS logtype,cur.* FROM dcim_site				AS cur WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_vlan				SELECT NULL,'I' AS logtype,cur.* FROM dcim_vlan				AS cur WHERE 1=1");
		$resultMessage[]= "WipeAndReCreateAllLogs()-Sucsessfully re-created all log records.";
	}
	
	function QAAllRecordsAsAdmin()
	{
		global $adminUserID;

		ExecuteThis("Q1","UPDATE dcim_badge				SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_customer			SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_device			SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_deviceport		SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_location			SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_portconnection	SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_portvlan			SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_powercircuit		SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_powercircuitloc	SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_powerpanel		SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
		ExecuteThis("Q1","UPDATE dcim_powerups			SET qauser=$adminUserID, qadate=CURRENT_TIMESTAMP WHERE qauser=-1");
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
		$u1 = "INSERT INTO `dcim_user` VALUES(0, 0, 'Admin', 'Administrator', '973012e8bea45a61b6538239e3de145d', 'dcim@freshprogramming.com', 'AM', 'the1AdminPass', '9', 0, '2018-09-19 07:24:33', 0, '2016-04-22 00:00:00');";
		$u1a= "UPDATE `dcim_user` SET userid=0 WHERE 1;";//cannot insert as 0 for some reason - using 0 just create a next autoincrement
		$u2 = "INSERT INTO `dcim_user` VALUES(1, 0, 'writer', 'Test Writer', 'd280a422f43b4b704334a4012c4b0f46', '', 'TW', 'testPass', '8', 0, '2016-01-02 06:17:06', 0, '2016-04-22 00:00:00');";
		$u3 = "INSERT INTO `dcim_user` VALUES(2, 0, 'reader', 'Test Reader', 'd280a422f43b4b704334a4012c4b0f46', '', 'TR', 'testPass', '1', 0, '2016-01-02 06:18:39', 0, '2016-04-22 00:00:00');";
		$u4 = "INSERT INTO `dcim_user` VALUES(3, 0, 'blocked', 'Test Blocked', 'cfb50a3e928aa308917fad97a1d84cc9', '', 'TB', 'blocked123', '0', 0, '2014-07-21 00:41:24', 0, '2016-04-22 00:00:00');";
		
		ExecuteThis("ResetDemoCreds-0","TRUNCATE TABLE dcim_user");
		ExecuteThis("ResetDemoCreds-1",$u1,true);
		ExecuteThis("ResetDemoCreds-1a",$u1a,true);
		ExecuteThis("ResetDemoCreds-2",$u2,true);
		ExecuteThis("ResetDemoCreds-3",$u3,true);
		ExecuteThis("ResetDemoCreds-4",$u4,true);
		$resultMessage[] = "Reset Creds to demo state. Can be seen <a href='https://github.com/freshprogrammer/DCIM/blob/master/documentation/creds.md'>here</a>.";
	}
	
	class DBLogRecord
	{
		public $table;
		public $key;
		public $editUser;
		public $editDate;
	}
	
	function CreateMissingInsertLogRecords()
	{
		global $userID;
		global $mysqli;
		global $resultMessage;
		global $errorMessage;
		
		//get list of missing log records - coppied from Check_RecordsMisingInsertLog()
		$query = "SELECT cur.* FROM (
	  SELECT 'site' AS `table`, s.siteid AS id,			NULL AS parent, NULL AS parentid, 	sl.siteid AS l_id, 		sl.logtype, s.edituser, s.editdate		FROM dcim_site AS s					LEFT JOIN dcimlog_site AS sl				ON s.siteid = sl.siteid								AND sl.logtype='I'
UNION SELECT 'badge', b.badgeid,						'customer', b.hno, 					bl.badgeid,				bl.logtype, b.edituser, b.editdate		FROM dcim_badge AS b				LEFT JOIN dcimlog_badge AS bl				ON b.badgeid = bl.badgeid							AND bl.logtype='I'
UNION SELECT 'customer', c.hno,							NULL, NULL,							cl.hno,					cl.logtype, c.edituser, c.editdate		FROM dcim_customer AS c				LEFT JOIN dcimlog_customer AS cl			ON c.hno = cl.hno									AND cl.logtype='I'
UNION SELECT 'device', d.deviceid,						'customer', d.hno, 					dl.deviceid,			dl.logtype, d.edituser, d.editdate		FROM dcim_device AS d				LEFT JOIN dcimlog_device AS dl				ON d.deviceid = dl.deviceid							AND dl.logtype='I'
UNION SELECT 'deviceport', dp.deviceportid,				'device', dp.deviceid,				dpl.deviceportid,		dpl.logtype,dp.edituser,dp.editdate		FROM dcim_deviceport AS dp			LEFT JOIN dcimlog_deviceport AS dpl			ON dp.deviceportid = dpl.deviceportid				AND dpl.logtype='I'
UNION SELECT 'location', l.locationid,					'room', l.roomid, 					ll.locationid,			ll.logtype, l.edituser, l.editdate		FROM dcim_location AS l				LEFT JOIN dcimlog_location AS ll			ON l.locationid = ll.locationid						AND ll.logtype='I'
UNION SELECT 'portconnection', pc.portconnectionid,		'deviceport', pc.childportid,		pcl.portconnectionid,	pcl.logtype,pc.edituser,pc.editdate		FROM dcim_portconnection AS pc		LEFT JOIN dcimlog_portconnection AS pcl		ON pc.portconnectionid = pcl.portconnectionid		AND pcl.logtype='I'
UNION SELECT 'portvlan', pv.portvlanid,					'deviceport', pv.deviceportid,		pvl.portvlanid,			pvl.logtype,pv.edituser,pv.editdate		FROM dcim_portvlan AS pv			LEFT JOIN dcimlog_portvlan AS pvl			ON pv.portvlanid = pvl.portvlanid					AND pvl.logtype='I'
UNION SELECT 'powercircuit', pc.powercircuitid,			'powerpanel', pc.powerpanelid, 		pcl.powercircuitid,		pcl.logtype,pc.edituser,pc.editdate		FROM dcim_powercircuit AS pc		LEFT JOIN dcimlog_powercircuit AS pcl		ON pc.powercircuitid = pcl.powercircuitid			AND pcl.logtype='I'
UNION SELECT 'powercircuitloc', pcl.powercircuitlocid,	'powercircuit', pcl.powercircuitid,	pcll.powercircuitlocid,	pcll.logtype,pcl.edituser,pcl.editdate	FROM dcim_powercircuitloc AS pcl	LEFT JOIN dcimlog_powercircuitloc AS pcll	ON pcl.powercircuitlocid = pcll.powercircuitlocid	AND pcll.logtype='I'
UNION SELECT 'powerpanel', pp.powerpanelid,				'powerups', pp.powerupsid, 			ppl.powerpanelid,		ppl.logtype, pp.edituser, pp.editdate	FROM dcim_powerpanel AS pp			LEFT JOIN dcimlog_powerpanel AS ppl			ON pp.powerpanelid = ppl.powerpanelid				AND ppl.logtype='I'
UNION SELECT 'powerups', pu.powerupsid,					'site', pu.siteid, 					pul.powerupsid,			pul.logtype, pu.edituser, pu.editdate	FROM dcim_powerups AS pu			LEFT JOIN dcimlog_powerups AS pul			ON pu.powerupsid = pul.powerupsid					AND pul.logtype='I'
UNION SELECT 'room', r.roomid,							'site', r.siteid, 					rl.roomid,				rl.logtype, r.edituser, r.editdate		FROM dcim_room AS r					LEFT JOIN dcimlog_room AS rl				ON r.roomid = rl.roomid								AND rl.logtype='I'
UNION SELECT 'vlan', v.vlanid,							'portvlan', v.vlan, 				vl.vlanid,				vl.logtype, v.edituser, v.editdate		FROM dcim_vlan AS v					LEFT JOIN dcimlog_vlan AS vl				ON v.vlanid = vl.vlanid								AND vl.logtype='I'
					) AS cur
					WHERE cur.l_id IS NULL
					ORDER BY 1, 2";
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->execute())
		{
			$errorMessage[] = "CreateMissingInsertLogRecords()-Prepare failed:(" . $mysqli->errno . ") - E:" . $mysqli->error;
			return false;
		}
		else
		{
			
			$stmt->store_result();
			$stmt->bind_result($table, $id, $parentTable, $parentID, $logID, $logType, $editUserID, $editDate);
			$count = $stmt->num_rows;
			
			$recs = array();
			while ($stmt->fetch())
			{
				$r = new DBLogRecord();
				$r->table = $table;
				$r->key = $id;
				$r->editUser = $editUserID;
				$r->editDate = $editDate;
				$recs[]=$r;
			}
			
			//processing done after finished looping through results 
			$affectedCount = 0;
			foreach($recs as $r)
			{
				$userID = $r->editUser;
				LogDBChange("dcim_".$r->table,$r->key,"I");
				$affectedCount++;
				$resultMessage[]= "Sucsessfully created log record for #".$r->key." in dcimlog_".$r->table.".";
			}
			
			$resultMessage[]= "Sucsessfully created $affectedCount missing insert records tables";
		}
	}
	
	function CorrectOutOfDateLogs()
	{//code code coppied from Check_RecordLogOutOfSync_Table
		global $mysqli;
		global $errorMessage;
		global $resultMessage;
		global $userID;
		global $adminUserID;
		
		$userID = $adminUserID;//admin
		
		$tables = array();
		$tables[]="dcim_badge";
		$tables[]="dcim_customer";
		$tables[]="dcim_device";
		$tables[]="dcim_deviceport";
		$tables[]="dcim_portconnection";
		$tables[]="dcim_location";
		$tables[]="dcim_portvlan";
		$tables[]="dcim_powerpanel";
		$tables[]="dcim_powerups";
		$tables[]="dcim_powercircuit";
		$tables[]="dcim_powercircuitloc";
		$tables[]="dcim_room";
		$tables[]="dcim_site";
		$tables[]="dcim_vlan";
		
		foreach ($tables as $table)
		{
			$affectedCount = 0;
			echo "<BR>Correcting out of date logs by updating unsyncronized records in $table";
			$logTable = GetLogTable($table);
			$keyfield = GetKeyField($table);
			$fields = GetTableFieldsFromDocs($table,true);
			
			//remove First and last 4 fields - edit info and keyfield
			array_shift($fields);
			array_pop($fields);
			array_pop($fields);
			array_pop($fields);
			array_pop($fields);
			
			$fieldSeperator = "-";
			$fieldConcat = "CONCAT(`".implode("`,'$fieldSeperator',`",$fields)."`)";
			
			//build sql
			$query= "SELECT a.$keyfield,
			(SELECT $fieldConcat FROM    $table WHERE $keyfield=a.$keyfield) AS cur,
			(SELECT $fieldConcat FROM $logTable WHERE $keyfield=a.$keyfield ORDER BY editdate DESC LIMIT 1) AS log
			FROM $table AS a
			HAVING cur!=log
			ORDER BY $keyfield";
			
			if (!($stmt = $mysqli->prepare($query)))
			{
				$errorMessage[] = "Prepare failed: CorrectOutOfDateLogs() - (" . $mysqli->errno . ") " . $mysqli->error;
			}
			else
			{
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($key, $cur, $log);
				$count = $stmt->num_rows;
				
				if($count>0)
				{
					//list result data
					$keys = array();
					while ($stmt->fetch())
					{
						$keys[]=$key;
					}
					
					//dont seperate to avoid $stmt conflicts
					foreach($keys as $key)
					{
						LogDBChange($table,$key,"U","",true);
						$affectedCount++;
						$resultMessage[]= "Sucsessfully created log record for #$key in $table log.";
					}
				}
				else
				{
					//nothing to do - all good
				}
			}
			$resultMessage[]= "Sucsessfully created $affectedCount missing update records in $table";
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