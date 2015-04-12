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

$restoreStructureSQLFile = "../../restoredata/structure.sql";
$restoreDataSQLFile = "../../restoredata/demoData.sql";

	function BuildDB()
	{
		/* This will create the DB to current DB specs found in the documentation folder
		 */
		global $resultMessage;
		global $restoreStructureSQLFile;
		
		ExecuteThisFile("B",$restoreStructureSQLFile);
		
		$resultMessage[]= "BuildDB()-Sucsessfully created database structure";
	}
	
	function RestoreDBWithDemoData()
	{
		/* This will wipe/truncate all current data in the database and repopulate it all with demo data
		 */
		global $resultMessage;
		global $restoreDataSQLFile;

		ExecuteThis("D1","TRUNCATE TABLE dcim_badge");
		ExecuteThis("D1","TRUNCATE TABLE dcim_customer");
		ExecuteThis("D1","TRUNCATE TABLE dcim_device");
		ExecuteThis("D1","TRUNCATE TABLE dcim_deviceport");
		ExecuteThis("D1","TRUNCATE TABLE dcim_location");
		ExecuteThis("D1","TRUNCATE TABLE dcim_portconnection");
		ExecuteThis("D1","TRUNCATE TABLE dcim_portvlan");
		ExecuteThis("D1","TRUNCATE TABLE dcim_power");
		ExecuteThis("D1","TRUNCATE TABLE dcim_powerloc");
		ExecuteThis("D1","TRUNCATE TABLE dcim_site");
		ExecuteThis("D1","TRUNCATE TABLE dcim_user");
		ExecuteThis("D1","TRUNCATE TABLE dcim_vlan");
		
		//NOTE: this is just restoreing the core tables and not the logs
		ExecuteThisFile("D2",$restoreDataSQLFile);
		
		WipeAndReCreateAllLogs();
		
		$resultMessage[]= "RestoreDBWithDemoData()-Sucsessfully populated database with demo data";
	}
	
	//returns 1 if DB is ready to update
	//returns 0 if DB is not ready
	//returns -1 if DB is already updated
	function IsDatabaseUpToDate_Update1()
	{
		global $debugMessage;
		
		//assumes all changes happen at once - so if 1 is valid they all must be
		if(DoesTableExist("dcim_room"))
			return -1;
		else
			return 1;
	}
	
	//this shoould mainly be for deletions and changes with db additions being done live and with code adjustments changing to to system gradualy over time.
	function RunDBUpdate_Update1()
	{
		/* // "x" denotes code is ready
		 * This will Update the Database by doing the following in the main and log tables:
		 *x	Drop field dcim_deviceport.hno
		 *x	Change dcim_power panel,circuit, volts, amps to NOT NULL
		 *x	Change dcim_power.circuit from varchar(5) to tinyint(2)
		 * 	Rename dcim_power.cload to load - drop cload here - created and mirrored load prior
		 * 	increase location.name size from 10 to 50
		 * 	
		 * 	create dcim_room tables
		 * 	-create location.roomid
		 * 	-drop location.colo
		 * 	-drop location.siteid
		 * 
		 * //manual
		 * 
		 * 
		 */
		global $resultMessage;
		global $debugMessage;
		
		$debugMessage[]= "RunDBUpdate_Update1()-Start";
		
		$execute = false;
		
		//this stuff will be done manualy before hand - the update is cleaning this stuff up
		/*
		 CREATE TABLE  `dcim_room` (
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
		 ) ENGINE = MYISAM;
		
		 CREATE TABLE  `dcimlog_room` (
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
		 ) ENGINE = MYISAM;
		
		 INSERT INTO  dcim_room (roomid,siteid,name,fullname,custaccess,edituser,editdate,qauser,qadate) VALUES (1,0,'MDF','Main Distribution Frame','F',0,CURRENT_TIMESTAMP , 0, NOW( ));
		 INSERT INTO  dcim_room (roomid,siteid,name,fullname,custaccess,edituser,editdate,qauser,qadate) VALUES (2,0,'CA#1','Customer Area #1','T',0,CURRENT_TIMESTAMP , 0, NOW( ));
		 INSERT INTO  dcim_room (roomid,siteid,name,fullname,custaccess,edituser,editdate,qauser,qadate) VALUES (3,0,'CA#2','Customer Area #2','T',0,CURRENT_TIMESTAMP , 0, NOW( ));
		 INSERT INTO  dcim_room (roomid,siteid,name,fullname,custaccess,edituser,editdate,qauser,qadate) VALUES (4,0,'CA#3','Customer Area #3','T',0,CURRENT_TIMESTAMP , 0, NOW( ));
		 INSERT INTO  dcim_room (roomid,siteid,name,fullname,custaccess,edituser,editdate,qauser,qadate) VALUES (5,0,'CA#4','Customer Area #4','T',0,CURRENT_TIMESTAMP , 0, NOW( ));
		 INSERT INTO  dcim_room (roomid,siteid,name,fullname,custaccess,edituser,editdate,qauser,qadate) VALUES (6,0,'CA#5','Customer Area #5','T',0,CURRENT_TIMESTAMP , 0, NOW( ));
		 INSERT INTO  dcim_room (roomid,siteid,name,fullname,custaccess,edituser,editdate,qauser,qadate) VALUES (7,0,'Unknown','Unknown/Missing','F',0,CURRENT_TIMESTAMP , 0, NOW( ));
		 	
		 INSERT INTO dcimlog_room SELECT NULL,'I' AS logtype,a.* FROM dcim_room AS a WHERE 1=1;
		
		
		 ALTER TABLE  `dcim_location`    ADD  `roomid` INT( 8 ) NOT NULL AFTER  `locationid` ;
		 ALTER TABLE  `dcim_location`    ADD INDEX (  `roomid` ) ;
		 ALTER TABLE  `dcimlog_location` ADD  `roomid` INT( 8 ) NOT NULL AFTER  `locationid` ;
		 ALTER TABLE  `dcimlog_location` ADD INDEX (  `roomid` ) ;
		 UPDATE dcim_location    SET roomid= 1+CAST(colo AS UNSIGNED);
		 UPDATE dcimlog_location SET roomid= 1+CAST(colo AS UNSIGNED);
		 UPDATE dcim_location    SET roomid=7 where roomid=1;
		 UPDATE dcimlog_location SET roomid=7 where roomid=1;
		
		 ALTER TABLE  `dcim_power`    ADD  `load` DECIMAL( 4, 2 ) NOT NULL DEFAULT  '0' AFTER  `status` ;
		 ALTER TABLE  `dcimlog_power` ADD  `load` DECIMAL( 4, 2 ) NOT NULL DEFAULT  '0' AFTER  `status` ;
		 UPDATE dcim_power    AS p SET p.load=p.cload;
		 UPDATE dcimlog_power AS p SET p.load=p.cload;
		
		 */
		
		
		//drop dcim_deviceport.hno
		////code changes implemented and tested
		$cmdm = "ALTER TABLE `dcim_deviceport`    DROP `hno`";
		$cmdl = "ALTER TABLE `dcimlog_deviceport` DROP `hno`";
		if($execute)ExecuteThis("U1M",$cmdm);
		if($execute)ExecuteThis("U1L",$cmdl);
		
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
		if($execute)ExecuteThis("U2M",$cmdm);
		if($execute)ExecuteThis("U2L",$cmdl);
		
		//change vircuit from varchar(5) to tinyint(2) - reality dictates that this will be from 1-42
		////no code changes necisary - unless you wanna check some casting 
		$cmdm = "ALTER TABLE  `dcim_power`    CHANGE  `circuit`  `circuit` TINYINT( 2 ) NOT NULL";
		$cmdl = "ALTER TABLE  `dcimlog_power` CHANGE  `circuit`  `circuit` TINYINT( 2 ) NOT NULL";
		if($execute)ExecuteThis("U3M",$cmdm);
		if($execute)ExecuteThis("U3L",$cmdl);
		
		//drop dcim_power.cload - to be replaced by load
		////code changes implemented and tested
		$cmdm = "ALTER TABLE `dcim_power`    DROP `cload`";
		$cmdl = "ALTER TABLE `dcimlog_power` DROP `cload`";
		if($execute)ExecuteThis("U1M",$cmdm);
		if($execute)ExecuteThis("U1L",$cmdl);
		
		//increase location.name size from 10 to 50
		////no code changes necisary
		$cmdm = "ALTER TABLE  `dcim_location`    CHANGE  `name`  `name` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
		$cmdl = "ALTER TABLE  `dcimlog_location` CHANGE  `name`  `name` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
		if($execute)ExecuteThis("U4M",$cmdm);
		if($execute)ExecuteThis("U4L",$cmdl);
		
		
		//done
		
		$resultMessage[]= "RunDBUpdate_Update1()-Sucsessfully updated database.";
	}
	
	function WipeAndReCreateAllLogs()
	{
		global $resultMessage;
		
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
		ExecuteThis("L1","TRUNCATE TABLE dcimlog_site");
		ExecuteThis("L1","TRUNCATE TABLE dcimlog_vlan");
		//create insert log records for all data
		ExecuteThis("L2","INSERT INTO dcimlog_badge				SELECT NULL,'I' AS logtype,a.* FROM dcim_badge			AS a WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_customer			SELECT NULL,'I' AS logtype,a.* FROM dcim_customer		AS a WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_device			SELECT NULL,'I' AS logtype,a.* FROM dcim_device			AS a WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_deviceport		SELECT NULL,'I' AS logtype,a.* FROM dcim_deviceport		AS a WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_location			SELECT NULL,'I' AS logtype,a.* FROM dcim_location		AS a WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_portconnection	SELECT NULL,'I' AS logtype,a.* FROM dcim_portconnection	AS a WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_portvlan			SELECT NULL,'I' AS logtype,a.* FROM dcim_portvlan		AS a WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_power				SELECT NULL,'I' AS logtype,a.* FROM dcim_power			AS a WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_powerloc			SELECT NULL,'I' AS logtype,a.* FROM dcim_powerloc		AS a WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_site				SELECT NULL,'I' AS logtype,a.* FROM dcim_site			AS a WHERE 1=1");
		ExecuteThis("L2","INSERT INTO dcimlog_vlan				SELECT NULL,'I' AS logtype,a.* FROM dcim_vlan			AS a WHERE 1=1");
		$resultMessage[]= "WipeAndReCreateAllLogs()-Sucsessfully re-created all log records.";
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