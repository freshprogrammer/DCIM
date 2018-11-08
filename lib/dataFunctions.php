<?php
	/* These functions process form actions and similar - dont mess with html*/
	
	function LoadConfigVariables()
	{
		global $mysqli;
		global $errorMessage;
		
		$query = "SELECT dbversion, appname, pagetitle, versionnote, cookiedurration, cookiedurrationipad, badgesenabled, subnetsenabled, qaenabled, demoenvironment
					FROM dcim_config
					LIMIT 1";
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->execute())
			$errorMessage[]="LoadConfigVariables() Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		else
		{
			$stmt->store_result();
			$stmt->bind_result($db_config_dbVersion, $db_config_appName, $db_config_pageTitle, $db_config_versionNote, $db_config_loginCookieDurration, $db_config_loginCookieDurrationOniPad, $db_config_badgesEnabled, $db_config_subnetsEnabled, $db_config_qaEnabled, $db_config_demoSiteEnabled);
			$count = $stmt->num_rows;
			
			if($count!=1)
				$errorMessage[]="LoadConfigVariables() Failed to find any config data - using defaults";
			else
			{
				$stmt->fetch();
				
				global $config_dbVersion;
				if($config_dbVersion != $db_config_dbVersion)
					$errorMessage[]="LoadConfigVariables() - Code DB version($config_dbVersion) doesn't match the dcim_config.dbversion($db_config_dbVersion). Please contact your administrator to correct this issue.";
				else
				{
					global $config_demoSiteEnabled;
					$config_demoSiteEnabled= ($db_config_demoSiteEnabled=="T");
					
					global $config_appName;
					$config_appName = $db_config_appName;
					global $config_pageTitle;
					$config_pageTitle = $db_config_pageTitle;
					global $config_versionNote;
					$config_versionNote = $db_config_versionNote;
					
					global $config_loginCookieDurration;
					$config_loginCookieDurration = $db_config_loginCookieDurration*60*60;//hours
					global $config_loginCookieDurrationOniPad;
					$config_loginCookieDurrationOniPad = $db_config_loginCookieDurrationOniPad*60*60;//hours
					
					global $config_badgesEnabled;
					$config_badgesEnabled = ($db_config_badgesEnabled=="T");
					global $config_subnetsEnabled;
					$config_subnetsEnabled = ($db_config_subnetsEnabled=="T");
					global $config_qaEnabled;
					$config_qaEnabled = ($db_config_qaEnabled=="T");
				}
			}
		}
	}
	
	function UpdatePermissionLevel()
	{
		global $mysqli;
		global $user;
		global $userID;
		global $userSiteID;
		global $resultMessage;
		global $errorMessage;
		global $config_userPasswordSalt;
		
		//check cookie
		if(isset($_COOKIE["dcim_user"]))
			$user = $_COOKIE["dcim_user"];
		
		if(isset($_COOKIE["dcim_password"]))
			$password = $_COOKIE["dcim_password"];
		
		if(!isset($user) || !isset($password))
		{
			//no cookies -check for fresh login form info
			$user = GetInput("logInUserName");
			$password = GetInput("logInPassword");
			//should change to password_hash($pass,PASSWORD_BCRYPT) & password_verify($pass,$hash)
			$password = md5($password.$config_userPasswordSalt);
		}
		
		//Validate User Authentication against DB - always
		$hasRights = false;
		if(isset($user) && strlen($user) > 0)
		{
			$query = "SELECT userid, username, pass, name, initials, note, permission, siteid
				FROM dcim_user
				WHERE username=?";
			if (!($stmt = $mysqli->prepare($query)))
			{
				$errorMessage[] = "Prepare failed (UpdatePermissionLevel()): (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			}
			else
			{
				$stmt->bind_Param('s', $user);
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($uID, $uName, $dbPass, $uFullName, $uInitials, $note, $permission, $siteID);
				$count = $stmt->num_rows;
				
				if($count>0)
				{
					$stmt->fetch();
					$userID = $uID;
					$userSiteID = $siteID;
					$user = $uName; //use name from DB - match case
					if(strcmp($password,$dbPass)==0)
					{
						if(ValidReadPermission($permission))//see DescribeUserPermissionLevel($p) for permission details
						{
							$hasRights = true;
						}
						else
						{
							$errorMessage[] = "Valid credentials but no permission to view this content.";
						}
						UpdateUserLastActivity($userID);
					}
					else
					{
						$errorMessage[] = "Incorrect password for \"".MakeHTMLSafe($user)."\".";// - ($password) vs ($dbPass)";
					}
				}
				else
				{
					$errorMessage[] = "User \"".MakeHTMLSafe($user)."\" not found.";
				}
			}//prepare didn't fail
		}
		
		if($hasRights)
		{
			UpdateLoginCookies($user,$password);
		}
		else
		{
			ClearUserLogin();
		}
		
		if($hasRights)
			return $permission;
		else
			return -1; //invalid within the 1 char val
	}
	
	function UpdateLoginCookies($user, $password)
	{
		global $config_loginCookieDurration;
		//update cookie info
		setcookie("dcim_user", $user, time()+$config_loginCookieDurration);
		//this should be the MD5 of the password so it is never stored on the local machie as original
		setcookie("dcim_password", $password, time()+$config_loginCookieDurration);
		
		//update the current var - seesion cookie in browser doesn't actualy update till the page is reloaded
		$_COOKIE["dcim_user"] = $user;
		$_COOKIE["dcim_password"] = $password;
	}
	
	function ProcessFormActions()
	{
		global $_SESSION;
		global $resultMessage;
		global $errorMessage;
		global $debugMessage;
		global $redirectAroundFormResubmitWarning;
		
		//theoreticly its possible to change cookie data and log in as someone else and process data at the same time... meh - probably just invalidate form data on fresh login
		
		$redirectPage = "";
		$action = GetInput("action");
		
		if(strlen($action)!=0 && !IsValidSession())
		{
			$errorMessage[] = "Page Reload. No action taken.";
			return;
		}
		
		$tookAction = false;
		
		if($action==="Badge_Edit" || $action==="Badge_Add" || $action==="Badge_Delete")
		{
			$redirectPage = ProcessBadgeAction($action);
			$tookAction = true;
		}
		else if($action==="Customer_Edit" || $action==="Customer_Add")
		{
			$redirectPage = ProcessCustomerAction($action);
			$tookAction = true;
		}
		else if($action==="Device_Edit" || $action==="Device_Add")
		{
			$redirectPage = ProcessDeviceAction($action);
			$tookAction = true;
		}
		else if($action==="Connection_Edit" || $action==="Connection_Add" || $action==="Connection_Delete")
		{
			$redirectPage = ProcessConnectionAction($action);
			$tookAction = true;
		}
		else if($action==="DevicePort_Edit" || $action==="DevicePort_Add" || $action==="DevicePort_Delete")
		{
			$redirectPage = ProcessDevicePortAction($action);
			$tookAction = true;
		}
		else if($action==="Subnet_Add" || $action==="Subnet_Edit" || $action==="Subnet_Delete")
		{
			$redirectPage = ProcessSubnetAction($action);
			$tookAction = true;
		}
		else if($action==="UserPassword_Update")
		{
			$redirectPage = ProcessUserPasswordUpdate();
			$tookAction = true;
		}
		else if($action==="PowerCircuit_Add" || $action==="PowerCircuit_Edit" || $action==="PowerCircuit_Delete")
		{
			$redirectPage = ProcessPowerCircuitAction($action);
			$tookAction = true;
		}
		else if($action==="PowerPanel_Add" || $action==="PowerPanel_Edit" || $action==="PowerPanel_Delete")
		{
			$redirectPage = ProcessPowerPanelAction($action);
			$tookAction = true;
		}
		else if($action==="Location_Add" || $action==="Location_Edit" || $action==="Location_Delete")
		{
			$redirectPage = ProcessLocationAction($action);
			$tookAction = true;
		}
		else if($action==="QA_Record")
		{
			$redirectPage = ProcessQAAction($action);
			$tookAction = true;
		}
		else if($action==="PowerAudit_PanelUpdate")
		{
			$redirectPage = ProcessPowerAuditPanelUpdate($action);
			$tookAction = true;
		}
		else if(strlen($action))
		{
			$errorMessage[] = "Unknown Form action sent ($action).";
			$tookAction = true;
		}

		if($tookAction && $redirectAroundFormResubmitWarning)
		{
			$_SESSION['resultMessage'] = $resultMessage;
			$_SESSION['errorMessage'] = $errorMessage;
			$_SESSION['debugMessage'] = $debugMessage;
			
			if(strlen($redirectPage)>2)
				header('location:'.$redirectPage);
			else
				header('location:'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
			exit;
		}
	}
	
	function ProcessPowerAuditPanelUpdate($action)
	{
		global $mysqli;
		global $userID;
		global $errorMessage;
		global $resultMessage;
		
		$valid = false;
		$powerCircuitIDs = array();
		$loads = array();
		$stati = array();
		$circuitsPerPanel = 42;//TODO this should be looking up the circuit count from dcim_powerpanel
		
		for($circuit=1; $circuit<=$circuitsPerPanel; $circuit++)
		{
			$powerCircuitID = GetInput("c".$circuit."powercircuitid");
			$load = GetInput("c".$circuit."load");
			$status = GetInput("c".$circuit."status");
			
			if(strlen($powerCircuitID)>0 && strlen($load)>0)
			{
				$powerCircuitIDs[] = $powerCircuitID;
				$loads[] = $load;
				if(!isset($status) || strlen($status)==0)
					$status = "D";
				$stati[] = $status;
			}
		}
		
		$inputCount = count($powerCircuitIDs);
		$valid = $inputCount>0;
		if($valid)
		{
			$query = "UPDATE dcim_powercircuit AS pc
					SET pc.load=?, pc.status=?
					WHERE pc.powercircuitid=?
					LIMIT 1";
			
			if (!($stmt = $mysqli->prepare($query)))
				$errorMessage[] = "Prepare failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error.".";
			else
			{
				$stmt->bind_Param('dsi', $writeLoad, $writeStatus, $writePowerCircuitID);

				$goodCount = 0;
				$badCount = 0;
				for($i=0; $i<$inputCount; $i++)
				{
					$writePowerCircuitID = $powerCircuitIDs[$i];
					$writeLoad = $loads[$i];
					$writeStatus = $stati[$i];
					$badCount++;
						
					if (!$stmt->execute())//execute
						//failed (errorNo-error)
						$errorMessage[] = "Failed to execute panel power circuit update($writePowerCircuitID,$writeLoad,$writeStatus) (" . $stmt->errno . "-" . $stmt->error . ").";
					else
					{
						$badCount--;
						$goodCount++;
						
						$affectedCount = $stmt->affected_rows;
						if($affectedCount==1)
						{
							//dif load
						}
						else
						{
							//load not changed
							//$errorMessage[] = "Successfully updated power record (PowerCircuitID:$writePowerCircuitID Load:$writeLoad), but affected $affectedCount rows.";
						}
						
						//these moved out because i dont care if the values are the saem durring an audit - (probably just still 0)
						$resultMessage[] = "Successfully updated power circuit (PowerCircuitID:$writePowerCircuitID Load:$writeLoad Status:$writeStatus).";
						UpdateRecordEditUser("dcim_powercircuit","powercircuitid",$writePowerCircuitID);//assume this is a full power audit so log it even if the data hasn't changed
						LogDBChange("dcim_powercircuit",$writePowerCircuitID,"U");
					}
				}
				$resultMessage[] = "Power Audit Panel - Updated $inputCount Records. ($goodCount Updates,$badCount Failures)";
			}
		}
		$poweraPanelID = GetInput("powerpanelid");
		$redirectPage = "./?powerpanelid=$poweraPanelID";
		return $redirectPage;
	}
	
	function ProcessQAAction($action)
	{
		$valid = true;
		
		//$add = $action==="Badge_Add";
		
		$table = GetInput("table");
		$id = GetInput("id");
		
		if($valid)$valid = ValidQARootTable($table);
		if($valid)$valid = ValidGenericID($id, "Unique Record ID");
		
		if($valid)
		{
			QARecord($table,$id);
		}
	}
	
	function LogDBChange($table, $ukey, $action, $filter="", $setCurrentDateAndUser=false)
	{
		//TODO this should be tested and probably adjusted to accomodate multiple updates where using filter instead of ukey and multiple records can be found (mostly for deleing linking recrods like powerloc deletion)
		// ^ also worth noting that thst there is no front end way to link double cross link power records so not super important
		global $mysqli;
		global $userID;
		global $errorMessage;
		global $resultMessage;
		
		$keyFieldName = GetKeyField($table);
		$logTable = GetLogTable($table);
		
		$keyLookupTable = $table;
		if((strlen($filter)>0) && $action=="D")//its already been deleted - look up in log table with filter for keys
			$keyLookupTable = $logTable;
		
		//run filter and re run with keys
		if(strlen($filter)>0)
		{
			$keys = GetKeysFromFilter($keyLookupTable,$filter,$keyFieldName);
			foreach ($keys as $ukey)
				LogDBChange($table, $ukey, $action);
			return;
		}
		
		//disable for D while testing U
		if($action=="Q")
		{
			$errorMessage[] = "LogDBChange($table, $ukey, <B>$action</B>,$filter) Disabled while in development.";
			return;
		}
		
		if($table=="dcim_user")return;// no log table for the users table
		if($keyFieldName==false ||$logTable==false)
		{
			$errorMessage[] = "LogDBChange($table, $ukey, $action,<B>$filter</B>) Failed to look up keyname, or logname.";
			return;// failed to lookup necisarrys
		}
		
		//deletions could copy from same - obviously not tested
		/*
http://stackoverflow.com/questions/4039748/in-mysql-can-i-copy-one-row-to-insert-into-the-same-table

CREATE TEMPORARY TABLE tmptable_1 SELECT * FROM $table WHERE $keyFieldName = $ukey ORDER BY badgelogid DESC LIMIT 1;
UPDATE tmptable_1 SET $keyFieldName = NULL, logtype='D';
INSERT INTO $table SELECT * FROM tmptable_1;
DROP TEMPORARY TABLE IF EXISTS tmptable_1;
		 */
		
		if($action=="D")
		{
			//copy last log record as final deleted record - this will leave one more record to QA
			$logKeyField = GetKeyField($logTable);
			$query1 = "CREATE TEMPORARY TABLE tmptable_1 SELECT * FROM $logTable WHERE $keyFieldName = $ukey ORDER BY $logKeyField DESC LIMIT 1;";
			//^LIMIT 1 to only copy most recent log record
			$query2 = "UPDATE tmptable_1 SET $logKeyField = NULL, logtype='$action', edituser=$userID, editdate=CURRENT_TIMESTAMP, qauser=-1, qadate='';";
			$query3 = "INSERT INTO $logTable SELECT * FROM tmptable_1;";
			$query4 = "DROP TEMPORARY TABLE IF EXISTS tmptable_1;";
			
			//$query1
			if (!($stmt1 = $mysqli->prepare($query1)))
				$errorMessage[] = "Prepare failed: LogDBChange ($table, $ukey, $action)-1 (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				if (!$stmt1->execute())//execute 
					$errorMessage[] = "Failed to execute LogDBChange ($table, $ukey, $action)-1 (" . $stmt1->errno . "-" . $stmt1->error . ").";
				else 
				{
					if($stmt1->affected_rows<1)
						$errorMessage[] = "LogDBChange($table, $ukey, $action) Success(q1), but affected <1 row.";
				}
			}
			//$query2
			if (!($stmt2 = $mysqli->prepare($query2)))
				$errorMessage[] = "Prepare failed: LogDBChange ($table, $ukey, $action)-2 (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				if (!$stmt2->execute())//execute 
					$errorMessage[] = "Failed to execute LogDBChange ($table, $ukey, $action)-2 (" . $stmt2->errno . "-" . $stmt2->error . ").";
				else 
				{
					if($stmt2->affected_rows<1)
						$errorMessage[] = "LogDBChange($table, $ukey, $action) Success(q2), but affected <1 row.";
				}
			}
			//$query3
			if (!($stmt3 = $mysqli->prepare($query3)))
				$errorMessage[] = "Prepare failed: LogDBChange ($table, $ukey, $action)-3 (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				if (!$stmt3->execute())//execute 
					$errorMessage[] = "Failed to execute LogDBChange ($table, $ukey, $action)-3 (" . $stmt3->errno . "-" . $stmt3->error . ").";
				else 
				{
					if($stmt3->affected_rows<1)
						$errorMessage[] = "LogDBChange($table, $ukey, $action) Success(q3), but affected <1 row.";
				}
			}
			//$query4
			if (!($stmt4 = $mysqli->prepare($query4)))
				$errorMessage[] = "Prepare failed: LogDBChange ($table, $ukey, $action)-4 (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				if (!$stmt4->execute())//execute 
					$errorMessage[] = "Failed to execute LogDBChange ($table, $ukey, $action)-4 (" . $stmt4->errno . "-" . $stmt4->error . ").";
				else
				{
					//do nothing here - record count will always be 0
					//if($stmt4->affected_rows<1)
					//	$errorMessage[] = "LogDBChange($table, $ukey, $action) Success(q4), but affected <1 row.";
				}
			}
		}
		else
		{
			$query = "INSERT INTO $logTable SELECT NULL,'$action',cur.* FROM $table AS cur WHERE $keyFieldName='$ukey'";	
			
			if (!($stmt = $mysqli->prepare($query)))
				$errorMessage[] = "Prepare failed: (LogDBChange ($table, $ukey, $action)) (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				if (!$stmt->execute())//execute 
					//failed (errorNo-error)
					$errorMessage[] = "Failed to execute LogDBChange ($table, $ukey, $action) (" . $stmt->errno . "-" . $stmt->error . ").";
				else 
				{
					$affectedCount = $stmt->affected_rows;
					if($affectedCount==1)
					{
						//$resultMessage[] = "Successfully Logged change ($table, $ukey, $action).";
					}
					else
					{
						$errorMessage[] = "LogDBChange($table, $ukey, $action) Success, but affected $affectedCount rows.";
					}
				}	
			}
			
			if($setCurrentDateAndUser)
			{
				$logKeyField = GetKeyField($logTable);
				$query = "UPDATE $logTable SET edituser=$userID, editdate=CURRENT_TIMESTAMP , qauser=-1, qadate=NULL WHERE $keyFieldName='$ukey' ORDER BY $logKeyField DESC LIMIT 1";
				
				if (!($stmt = $mysqli->prepare($query)))
					$errorMessage[] = "Prepare failed: (LogDBChange-2 ($table, $ukey, $action)) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{
					if (!$stmt->execute())//execute
						//failed (errorNo-error)
						$errorMessage[] = "Failed to execute LogDBChange ($table, $ukey, $action) (" . $stmt->errno . "-" . $stmt->error . ").";
					else
					{
						$affectedCount = $stmt->affected_rows;
						if($affectedCount==1)
						{
							//$resultMessage[] = "Successfully Logged change ($table, $ukey, $action).";
						}
						else
						{
							$errorMessage[] = "LogDBChange($table, $ukey, $action) Success, but affected $affectedCount rows.";
						}
					}
				}
			}
		}
	}
	
	function QARecord($table, $ukey, $keyField="", $liveRecord=true)
	{
		global $mysqli;
		global $userID;
		global $errorMessage;
		global $resultMessage;
		
		$affectedCount = 0;
		$recDescrip = GetTableRecordDescription($table);
	
		if(strlen($keyField)==0)
			$keyField = GetKeyField($table);
			
		if(!$recDescrip || !$keyField)
		{
			$errorMessage[] = "QARecord($table, $ukey, $keyField, $liveRecord) - Invalid table.";
			return; 
		}
		
		//update QA user and date
		if($liveRecord)
			$query = "UPDATE $table SET qauser=?, qadate=CURRENT_TIMESTAMP WHERE $keyField=$ukey LIMIT 1";
		else	
		{
			$logKeyField = GetKeyField($table);
			$query = "UPDATE $table SET qauser=?, qadate=CURRENT_TIMESTAMP WHERE $keyField=$ukey ORDER BY $logKeyField DESC LIMIT 1";
		}
		
		if (!($stmt = $mysqli->prepare($query)))
			$errorMessage[] = "Prepare failed: QARecord($table, $ukey, $keyField, $liveRecord) (" . $mysqli->errno . ") " . $mysqli->error;
		else
		{
			$stmt->bind_Param('i', $userID);
			
			if (!$stmt->execute())
				$errorMessage[] = "Failed to execute QARecord($table, $ukey, $keyField, $liveRecord) (" . $stmt->errno . "-" . $stmt->error . ").";
			else 
			{
				$affectedCount = $stmt->affected_rows;
				if($affectedCount==1)
				{
					if($liveRecord)
						$resultMessage[] = "Successfully QAed $recDescrip.";
					if($liveRecord)
						QARecord(GetLogTable($table),$ukey,$keyField,false);
				}
				else
					$errorMessage[] = "QARecord($table, $ukey, $keyField, $liveRecord) Success, but affected $affectedCount rows.";
			}	
		}
		return $affectedCount;
	}
	
	function ProcessBadgeAction($action)
	{
		global $mysqli;
		global $userID;
		global $errorMessage;
		global $resultMessage;
		
		$totalAffectedCount = 0;
		$valid = true;

		$add = $action==="Badge_Add";
		$delete = $action==="Badge_Delete";
		
		$badgeID = GetInput("badgeid");
		$badgeNo = GetInput("badgeno");
		$hNo = GetInput("hno");
		$name = GetInput("name");
		$issue = GetInput("issue");
		$status = GetInput("status");
		$pastStatus = GetInput("paststatus");
		
		if(!$add)
		{
			//if edit then validate badgeID, otherwise a new one will be generated on insert
			if($valid)$valid = ValidBadgeID($badgeID);
		}
		
		if(!$delete)
		{
			if($valid)$valid = ValidBadgeNo($badgeNo);
			if($valid)$valid = ValidHNo($hNo);
			if($valid)$valid = ValidBadgeName($name);
			if($valid)$valid = ValidBadgeIssue($issue);
			if($valid)$valid = ValidBadgeStatus($status);
			
			//make sure customer exists
			if($valid)$valid = ValidRecord("hno","Hosting #",$hNo,"dcim_customer",true);
		}
		
		//maybe check for existing badges with this badgeNo and warn
		
		if($valid && $delete)
		{
			//validate badge exists and status is R or D
			$valid = false;
			$passedDBChecks = false;
			$query = "SELECT badgeid, status FROM dcim_badge WHERE badgeid=?";
			
			if (!($stmt = $mysqli->prepare($query)))
			{
				$errorMessage[] = "Prepare failed: ($action-2) (" . $mysqli->errno . ") " . $mysqli->error;
			}
			else
			{
				$stmt->bind_Param('i', $badgeID);		
				$stmt->execute();
				$stmt->store_result();
				$count = $stmt->num_rows;
				
				if($count==1)
				{
					$stmt->bind_result($foundBadgeID,$foundStatus);
					$stmt->fetch();
					
					$passedDBChecks = ($foundStatus=="R" || $foundStatus=="D");
					
					if(!$passedDBChecks)
						$errorMessage[] = "Error: Badge is in invalid status to be deleted.";
				}
				else if($count>1)
					$errorMessage[] = "Error: Multiple badges with ID#$badgeID found.";
				else if($count>1)
					$errorMessage[] = "Error: Badge with ID#$badgeID not found.";
			}
			$valid = $passedDBChecks;
		}
		
		//do actions
		if($valid)
		{
			$updateAdditionalFields = "";
			$enroll = false;
			if($status==="E")
			{
				$enroll = true;
				$status="A";
			}
			
			if($add)
			{
				$handDate = "'0000-00-00'";
				if($enroll)
					$handDate = "CURDATE()";
					
				$query = "INSERT INTO dcim_badge 
					(hno,name,badgeno,status,issue,hand,edituser,editdate) 
					VALUES(?,?,?,?,?,".$handDate.",?,CURRENT_TIMESTAMP)";
					
				if (!($stmt = $mysqli->prepare($query)))
					$errorMessage[] = "Prepare failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{//					   hnbsiu
					$stmt->bind_Param('issssi', $hNo, $name, $badgeNo, $status, $issue, $userID);
					
					if (!$stmt->execute())//execute 
						//failed (errorNo-error)
						$errorMessage[] = "Failed to execute badge add (" . $stmt->errno . "-" . $stmt->error . ").";
					else 
					{
						$affectedCount = $stmt->affected_rows;
						$totalAffectedCount += $affectedCount;
						if($affectedCount==1)
						{
							$resultMessage[] = "Successfully added badge (H".$hNo.",".$name.").";
							LogDBChange("dcim_badge",-1,"I","hno='$hNo' AND name='$name' AND badgeno='$badgeNo'");
						}
						else
						{
							$errorMessage[] = "Success, but affected $affectedCount rows.";
						}
					}	
				}
			}
			else if($delete)
			{
				if(CustomFunctions::UserHasBadgeDeletePermission())
				{
					$query = "DELETE FROM dcim_badge WHERE badgeid=? LIMIT 1";
				
					if (!($stmt = $mysqli->prepare($query)))
						$errorMessage[] = "Process Badge Delete Prepare failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error;
					else
					{
						$stmt->bind_Param('i', $badgeID);
				
						if (!$stmt->execute())//execute
							$errorMessage[] = "Failed to execute Badge Delete '".UserHasAdminPermission()."' (" . $stmt->errno . "-" . $stmt->error . ")";
						else
						{
							$affectedCount = $stmt->affected_rows;
							$totalAffectedCount += $affectedCount;
							if($affectedCount==1)
							{
								$resultMessage[] = "Successfully Deleted Badge.";
								LogDBChange("dcim_badge",$badgeID,"D");
							}
							else
								$errorMessage[] = "Success, but affected $affectedCount rows.";
						}
					}
				}
				else
				{
					$errorMessage[] = "Your do not have permission to delete badges";
				}
			}
			else
			{
				if($pastStatus==="A" && $status==="R")
				{
					//changeing status to returned
					$updateAdditionalFields = $updateAdditionalFields.", returned=CURDATE()"	;
				}
				else if($enroll)
					$updateAdditionalFields = $updateAdditionalFields.", hand=CURDATE()";
				
				
				$query = "UPDATE dcim_badge 
					SET name=?, badgeno=?, status=?, issue=?".$updateAdditionalFields." 
					WHERE badgeid=? 
					LIMIT 1";
		
				if (!($stmt = $mysqli->prepare($query)))
					$errorMessage[] = "Prepare failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error.".";
				else
				{
					$stmt->bind_Param('ssssi', $name, $badgeNo, $status, $issue, $badgeID);	
					
					if (!$stmt->execute())//execute 
						//failed (errorNo-error)
						$errorMessage[] = "Failed to execute badge edit (" . $stmt->errno . "-" . $stmt->error . ").";
					else
					{
						$affectedCount = $stmt->affected_rows;
						$totalAffectedCount += $affectedCount;
						if($affectedCount==1)
						{
							$resultMessage[] = "Successfully edited badge (H".$hNo.",".$name."). $totalAffectedCount records updated.";
							UpdateRecordEditUser("dcim_badge","badgeid",$badgeID);//do this seperate to distinquish actual record changes from identical updates
							LogDBChange("dcim_badge",$badgeID,"U");
						}
						else
						{
							$resultMessage[] = "Success, but affected $affectedCount rows.";
						}
					}
				}
			}
		}
	}
	
	class PowerCircuit
	{
		public $id;
		public $circuit;
		public $volts;
		public $amps;
		public $status;
		public $load;
	}
	
	function Get3PhasePowerLookup($powerPanelID, $inputCircuitID)
	{
		global $mysqli;
		global $errorMessage;
		//pull list of all circuits for panel
		//return an array of the 3 PowerCircuits for this 3 phase power circuit in circuit order
		$query = "SELECT pc.powercircuitid, pc.circuit, pc.volts, pc.amps, pc.status, pc.load, pp.circuits, pp.powerpanelid, pp.name
			FROM dcim_powerpanel AS pp
				LEFT JOIN dcim_powercircuit AS pc ON pc.powerpanelid=pp.powerpanelid
			WHERE pc.powerpanelid=? AND pc.volts=308
			ORDER BY pc.circuit%2=0, pc.circuit";
		
		$found = false;
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $powerPanelID) || !$stmt->execute())
			$errorMessage[] = "Prepare failed: ($action-a2) (" . $mysqli->errno . ") " . $mysqli->error;
		else
		{
			$stmt->store_result();
			$count = $stmt->num_rows;
			if($count>=1)
			{
				$stmt->bind_result($powerCircuitID, $circuit, $volts, $amps, $status, $load, $circuits, $powerPanelID, $panelName);
				$phaseNo = 1;//1-3
				$result = array();
				while ($stmt->fetch())
				{
					if($powerCircuitID==$inputCircuitID)	$found=true;
					
					$c = new PowerCircuit();
					$c->id = $powerCircuitID;
					$c->circuit = $circuit;
					$c->volts = $volts;
					$c->amps = $amps;
					$c->status = $status;
					$c->load = $load;
					
					$result[] = $c;
					
					$phaseNo++;
					if($phaseNo==4)
					{
						$phaseNo=1;
						if($found)
						{
							$validSet = ($result[0]->circuit==$result[1]->circuit+2 && $result[0]->circuit==$result[2]->circuit+4);//ensure that all 3 circuits are sequential (1,3,5)
							if(!$validSet)//found but invalid
							{
								$errorMessage[] = "Warning! Found record but not part of valid 3 phase set on this panel. Contact your Admin to correct this.($powerPanelID)(Circuits:".$result[0]->circuit.",".$result[1]->circuit.",".$result[2]->circuit.").";
								$found = false;//return null
							}
							break;//stop and return result
						}
						else $result = array();//try again
					}
				}
				if($phaseNo!=1)//found !%3 circuits
					$errorMessage[] = "Warning! Get3PhaseMasterLookup() Failed to locate correct number of 208v3p power citrcuits on this panel($powerPanelID)($phaseNo).";
			}
			else
			{//no 3 phase power found - this function should only be called if this is a 208v3p circuit. This shouldn't happen
				$errorMessage[] = "Warning! Unexpected number of 208v3p power circuits on this panel ($panelName). Please alert you admin to correct this issue.";
			}
		}
		if($found) return $result;
		else return null;
	}
	
	function ProcessPowerCircuitAction($action)
	{
		global $mysqli;
		global $userID;
		global $errorMessage;
		global $resultMessage;
		
		$add = $action==="PowerCircuit_Add";
		$delete = $action==="PowerCircuit_Delete";
		
		$powerCircuitID = GetInput("powercircuitid");
		$powerPanelID = GetInput("powerpanelid");
		$locationID = GetInput("locationid");
		$circuit= GetInput("circuit");
		$volts = GetInput("volts");
		$amps = GetInput("amps");
		$status = GetInput("status");
		$load = GetInput("load");
		
		if(!isset($status) || strlen($status)==0)
			$status = "D";
		
		$totalAffectedCount = 0;
		$valid = true;
		
		if($valid)$valid = ValidPowerCircuitVolts($volts);
		if($valid)$valid = ValidPowerCircuitAmps($amps);
		if($valid)$valid = ValidPowerCircuitStatus($status);
		if($valid)$valid = ValidPowerCircuitLoad($load, $amps);
		
		$isDoubleCircuit = (int)$volts == 208;
		$isTrippleCircuit = (int)$volts == 308;
		$updateAll = false;//need to update all 3 in 3 phase
		
		//DB CHECKS
		//check valid IDs in tables 
		if($valid && $add && $locationID!=-1)$valid = ValidRecord("locationid","Location ID",$locationID,"dcim_location",true);
		
		if($valid)
		{//validate panel and look up info
			$valid = false;
			$query = "SELECT pp.powerpanelid, pp.name, pp.circuits
				FROM dcim_powerpanel AS pp
				WHERE pp.powerpanelid=?";
			
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $powerPanelID) || !$stmt->execute())
				$errorMessage[] = "Prepare 0 failed: ProcessPowerCircuitAction($action) (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				$stmt->store_result();
				$count = $stmt->num_rows;
				
				if($count==1)
				{
					$valid=true;
					$stmt->bind_result($powerPanelID, $powerPanelName, $panelCircuits);
					$stmt->fetch();
				}
				else if($count==0)
					$errorMessage[] = "Power Panel #$powerPanelID not found";
				else
					$errorMessage[] = "Found more than 1 Power Panel with ID#$powerPanelID";
			}
		}
		
		if(!$add && $valid)
		{//make sure this record exists and lookup old info for comparison
			$valid = false;
			$query = "SELECT pc.powercircuitid, pc.circuit, pc.volts, pc.amps, pc.load, pc. status
				FROM dcim_powercircuit AS pc
				WHERE pc.powercircuitid=?";
			
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $powerCircuitID) || !$stmt->execute())
			{
				$errorMessage[] = "Prepare 1 failed: ProcessPowerCircuitAction($action) (" . $mysqli->errno . ") " . $mysqli->error;
			}
			else 
			{
				$stmt->store_result();
				$count = $stmt->num_rows;
				
				if($count==1)
				{
					$valid=true;
					$stmt->bind_result($powerCircuitID,$oldCircuit, $oldVolts, $oldAmps, $oldLoad, $oldStatus);
					$stmt->fetch();
				}
				else if($count==0)
					$errorMessage[] = "Circuit #$powerCircuitID not found";
				else
					$errorMessage[] = "Found more than 1 circuit with ID#$powerCircuitID";
			}
		}
		
		if($valid)$valid = ValidPowerCircuitNo($circuit, $panelCircuits);
		if($valid && $add && ($isDoubleCircuit || $isTrippleCircuit))$valid = ValidPowerCircuitNo($circuit+2, $panelCircuits);
		if($valid && $add && $isTrippleCircuit)$valid = ValidPowerCircuitNo($circuit+4, $panelCircuits);
		
		//check for existing panel circuit combo
		if($add && $valid)
		{
			$valid = false;
			$passedDBChecks = false;
			//this could be optomised by filtering inner selects by panel and/or range of circuit
			$filter = "";
			if($isDoubleCircuit)
				$filter = "csr.powerpanelid=? AND (csr.circuit=? OR csr.circuit=?)";
			else if($isTrippleCircuit)
				$filter = "csr.powerpanelid=? AND (csr.circuit=? OR csr.circuit=? OR csr.circuit=?)";
			else
				$filter = "csr.powerpanelid=? AND csr.circuit=?";
			
			$query = "SELECT pp.name, csr.* FROM (
					SELECT powerpanelid,powercircuitid,circuit,volts,amps, '' AS reserved
						FROM dcim_powercircuit
					UNION 
						SELECT powerpanelid,powercircuitid,IF(volts=208,circuit+2,NULL) AS cir,volts,amps, 'T'
						FROM dcim_powercircuit HAVING NOT(cir IS NULL)
						) AS csr
					LEFT JOIN dcim_powerpanel AS pp ON pp.powerpanelid=csr.powerpanelid
				WHERE $filter
				ORDER BY circuit";
			
			if (!($stmt = $mysqli->prepare($query)))
				$errorMessage[] = "ProcessPowerCircuitAction() Prepare 2 failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error.".";
			else
			{
				$failed = false;
				if($isDoubleCircuit)
				{
					$secondCircuit = 2+(int)$circuit;
					$failed = !$stmt->bind_Param('iss', $powerPanelID, $circuit, $secondCircuit);
					if($failed)
						$errorMessage[] = "ProcessPowerCircuitAction() Bind 2b2 failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error.".";
				}
				else if($isTrippleCircuit)
				{
					$secondCircuit = 2+(int)$circuit;
					$thirdCircuit = 4+(int)$circuit;
					$failed = !$stmt->bind_Param('isss', $powerPanelID, $circuit, $secondCircuit,$thirdCircuit);
					if($failed)
						$errorMessage[] = "ProcessPowerCircuitAction() Bind 2b3 failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error.".";
				}
				else 
				{
					$failed = !$stmt->bind_Param('is', $powerPanelID, $circuit);
					if($failed)
						$errorMessage[] = "ProcessPowerCircuitAction() Bind 2b1 failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error.".";
				}	
				if (!$failed && !$stmt->execute())//execute
					$errorMessage[] = "ProcessPowerCircuitAction() Execute 2c failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error.".";
				else
				{
					$stmt->store_result();
					$count = $stmt->num_rows;
					
					if($count==0)
						$passedDBChecks = true;
					else
					{
						$stmt->bind_result($ppName, $ppID,$k, $c, $v, $a, $r);
						$stmt->fetch();
						
						$errorMessage[] = "Existing panel Circuit conflict found (Power Panel:$ppName(#$ppID), Circuit#$c) ID#$k. Cannot create duplicate.";
					}
				}
			}
			$valid=$passedDBChecks;
		}
		
		if($valid && $isTrippleCircuit && !$add)
		{
			//look up 2083p power circuits
			$lookupResult = Get3PhasePowerLookup($powerPanelID,$powerCircuitID);
			if($lookupResult==null)$valid = false;
			if($valid)
			{
				list($c1,$c2,$c3) = $lookupResult;
				$debugMessage[]="ProcessPowerCircuitAction() tripple: ($c1->id,$c2->id,$c3->id) - powerPanelID = $powerPanelID";
				//changes that should be pushed to all 3 records
				if($c1->status!=$status || $c2->status!=$status || $c3->status!=$status)$updateAll = true;
				if($c1->amps!=$amps|| $c2->amps!=$amps|| $c3->amps!=$amps)$updateAll = true;
			}
		}
		
		if($valid)
		{//push changes to DB
			if($add)
			{
				$totalAffectedCount += ProcessPowerCircuitAction_Add($powerPanelID, $circuit, $volts, $amps, $status, $load, $locationID);
				
				if($volts==308)
				{//add additional records for 208v3p power - should have already been validated
					$totalAffectedCount += ProcessPowerCircuitAction_Add($powerPanelID, $circuit+2, $volts, $amps, $status, $load, $locationID);
					$totalAffectedCount += ProcessPowerCircuitAction_Add($powerPanelID, $circuit+4, $volts, $amps, $status, $load, $locationID);
				}
				$resultMessage[] = "$totalAffectedCount total records created.";
			}
			else if($delete)
			{
				if($isTrippleCircuit)
				{
					$totalAffectedCount += ProcessPowerCircuitAction_Delete($c1->id, $powerPanelName, $c1->circuit);
					$totalAffectedCount += ProcessPowerCircuitAction_Delete($c2->id, $powerPanelName, $c2->circuit);
					$totalAffectedCount += ProcessPowerCircuitAction_Delete($c3->id, $powerPanelName, $c3->circuit);
				}
				else
				{
					$totalAffectedCount += ProcessPowerCircuitAction_Delete($powerCircuitID, $powerPanelName, $circuit);
				}
				$resultMessage[] = "$totalAffectedCount total records Deleted.";
			}
			else
			{//update
				if($updateAll)
				{
					$totalAffectedCount += ProcessPowerCircuitAction_Update($c1->id, $amps, $status, $load, $c1->circuit, $powerPanelName);
					$totalAffectedCount += ProcessPowerCircuitAction_Update($c2->id, $amps, $status, $load, $c2->circuit, $powerPanelName);
					$totalAffectedCount += ProcessPowerCircuitAction_Update($c3->id, $amps, $status, $load, $c3->circuit, $powerPanelName);
				}
				else
				{
					$totalAffectedCount += ProcessPowerCircuitAction_Update($powerCircuitID, $amps, $status, $load, $circuit, $powerPanelName);
				}
				$resultMessage[] = "$totalAffectedCount total records Updated.";
			}
		}
	}
	
	function ProcessPowerCircuitAction_Add($powerPanelID, $circuit, $volts, $amps, $status, $load, $locationID)
	{//just does the Add - should be validated in ProcessPowerCircuitAction() above
		global $mysqli;
		global $userID;
		global $errorMessage;
		global $resultMessage;
		
		$totalAffectedCount = 0;
		
		$query = "INSERT INTO dcim_powercircuit
					(powerpanelid,circuit,volts,amps,status,`load`,edituser,editdate)
					VALUES(?,?,?,?,?,?,?,CURRENT_TIMESTAMP)";
		
		//															   pcvaslu
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('isssssi', $powerPanelID, $circuit, $volts, $amps, $status, $load, $userID) || !$stmt->execute())
			$errorMessage[] = "ProcessPowerCircuitAction_Add - Prepare failed: (a1) (" . $mysqli->errno . ") " . $mysqli->error;
		else
		{
			$affectedCount = $stmt->affected_rows;
			$totalAffectedCount += $affectedCount;
			if($affectedCount==1)
			{
				LogDBChange("dcim_powercircuit",-1,"I","powerpanelid='$powerPanelID' AND circuit='$circuit'");
				$resultMessage[] = "Successfully added power circuit (Panel#:".$powerPanelID." Circuit#".$circuit.").";
			}
			else
				$errorMessage[] = "Power circuit added successfully, but affected $affectedCount rows.";
			
			//look up inserted id
			$query = "SELECT pc.powercircuitid, pp.name
				FROM dcim_powercircuit AS pc
					LEFT JOIN dcim_powerpanel AS pp ON pp.powerpanelid=pc.powerpanelid
				WHERE pc.powerpanelid=? AND pc.circuit=?";
			
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('is', $powerPanelID, $circuit) || !$stmt->execute())
				$errorMessage[] = "ProcessPowerCircuitAction_Add - Prepare failed: (a2) (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				$stmt->store_result();
				$count = $stmt->num_rows;
				if($count==1)
				{
					$stmt->bind_result($powerCircuitID, $powerPanelName);
					$stmt->fetch();
					//update result message with the more usefull panel name
					array_pop($resultMessage);
					$resultMessage[]= "Successfully added power circuit (Panel:".$powerPanelName." Circuit#".$circuit.").";
				}
				
				if($count==1 && $locationID!=-1)
				{//create power circuit loc record
					//sucsessfull Insert - insert circuit-location link record
					$query = "INSERT INTO dcim_powercircuitloc
					(powercircuitid,locationid,edituser,editdate)
					VALUES(?,?,?,CURRENT_TIMESTAMP)";
					
					//															   plu
					if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('iii', $powerCircuitID, $locationID, $userID) || !$stmt->execute())
						$errorMessage[] = "ProcessPowerCircuitAction_Add - Prepare failed: (a3) (" . $mysqli->errno . ") " . $mysqli->error;
						else
						{
							$affectedCount = $stmt->affected_rows;
							$totalAffectedCount += $affectedCount;
							
							if($affectedCount==1)
							{
								LogDBChange("dcim_powercircuitloc",-1,"I","powercircuitid=$powerCircuitID AND locationid=$locationID");
								$resultMessage[] = "Successfully added power circuit location link (powerCircuitID:".$powerCircuitID.",locationID:".$locationID.").";
							}
							else
								$errorMessage[] = "Power circuit location link added successfully, but affected $affectedCount rows.";
						}
				}
				else
				{
					if($count!=1)//only report error if circuit was not found, otherwist locationwas deleberately skipped
						$errorMessage[] = "Failed to locate inserted record. Power (if created) is not linked to Location. PowerID:$powerPanelID Circuit:$circuit";
				}
			}
		}
		return $totalAffectedCount;
	}
	
	function ProcessPowerCircuitAction_Delete($powerCircuitID, $powerPanelName, $circuit)
	{
		global $mysqli;
		global $errorMessage;
		global $resultMessage;
		
		$totalAffectedCount = 0;
		//delete where powerCircuitID=? in power and powerloc
		$query = "DELETE FROM  dcim_powercircuit
					WHERE powercircuitid=?
					LIMIT 1";
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $powerCircuitID) || !$stmt->execute())
			$errorMessage[] = "ProcessPowerCircuitAction_Delete() - Prepare failed: (d1) (" . $mysqli->errno . ") " . $mysqli->error;
		else
		{
			$affectedCount = $stmt->affected_rows;
			$totalAffectedCount += $affectedCount;
			if($affectedCount==1)
			{
				LogDBChange("dcim_powercircuit",$powerCircuitID,"D");
				$resultMessage[] = "Successfully deleted power circuit (Panel:$powerPanelName Circuit#$circuit).";
				
				//delete link - dont limit to 1 because this 1 power record could be linked to multiple locations
				$query = "DELETE FROM  dcim_powercircuitloc
						WHERE powercircuitid=?";
				
				if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $powerCircuitID) || !$stmt->execute())
					$errorMessage[] = "ProcessPowerCircuitAction_Delete() - Prepare failed: (d2) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{
					$affectedCount = $stmt->affected_rows;
					$totalAffectedCount += $affectedCount;
					if($affectedCount>=1)
					{
						LogDBChange("dcim_powercircuitloc",-1,"D","powercircuitid='$powerCircuitID'");
						$resultMessage[] = "Successfully unlinked power circuit from location(Panel:$powerPanelName Circuit#$circuit). $affectedCount unlinked";
						
					}
					else
						$resultMessage[] = "Successfully unlinked power, but affected $affectedCount rows.";
				}
			}
			else
			{
				$errorMessage[] = "Successfully deleted power record, but affected $affectedCount rows.";
			}
		}
		return $totalAffectedCount;
	}
	
	function ProcessPowerCircuitAction_Update($powerCircuitID, $amps, $status, $load, $circuit, $powerPanelName)
	{
		global $mysqli;
		global $errorMessage;
		global $resultMessage;
		
		$totalAffectedCount = 0;
		$query = "UPDATE dcim_powercircuit AS pc
					SET pc.amps=?, pc.status=?, pc.load=?
					WHERE pc.powercircuitid=?
					LIMIT 1";
		
		if (!($stmt = $mysqli->prepare($query)))
			$errorMessage[] = "Prepare failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error.".";
		else
		{
			$stmt->bind_Param('sssi', $amps, $status, $load, $powerCircuitID);
			
			if (!$stmt->execute())//execute - failed (errorNo-error)
				$errorMessage[] = "Failed to execute power circuit edit (" . $stmt->errno . "-" . $stmt->error . ").";
			else
			{
				$affectedCount = $stmt->affected_rows;
				$totalAffectedCount += $affectedCount;
				if($affectedCount==1)
				{
					$resultMessage[] = "Successfully edited power circuit (Panel:$powerPanelName Circuit#$circuit). $totalAffectedCount records updated.";
					UpdateRecordEditUser("dcim_powercircuit","powercircuitid",$powerCircuitID);//do this seperate to distinquish actual record changes from identical updates (updates without changes)
					LogDBChange("dcim_powercircuit",$powerCircuitID,"U");
				}
				else
				{
					$errorMessage[] = "Successfully edited power record, but affected $affectedCount rows.";
				}
			}
		}
		return $totalAffectedCount;
	}
	
	function ProcessPowerPanelAction($action)
	{
		global $mysqli;
		global $userID;
		global $errorMessage;
		global $resultMessage;
		
		$valid = true;
		$totalAffectedCount = 0;
		$redirectPage = "";
		
		if(!CustomFunctions::UserHasPanelPermission())
		{
			$valid = false;
			$errorMessage[] = "Your do not have permission to edit power panels. Please contact your administrator.";
		}
		else
		{
			$add = $action==="PowerPanel_Add";
			$delete = $action==="PowerPanel_Delete";
			
			$powerPanelID = GetInput("powerpanelid");
			$roomID = GetInput("roomid");
			$upsID = GetInput("upsid");
			$name = trim(GetInput("name"));
			$amps = GetInput("amps");
			$circuits = GetInput("circuits");
			$orientation = GetInput("orientation");
			$xPos = GetInput("xpos");
			$yPos = GetInput("ypos");
			$width = GetInput("width");
			$depth = GetInput("depth");
			$notes = trim(GetInput("notes"));
		}
		
		if(!$delete)
		{
			if($valid)$valid = ValidPowerPanelName($name);
			if($valid)$valid = ValidPowerPanelAmps($amps);
			if($valid)$valid = ValidPowerPanelCircuits($circuits);
			if($valid)$valid = ValidPowerPanelOrientation($orientation);
			if($valid)$valid = ValidPowerPanelXPos($xPos);
			if($valid)$valid = ValidPowerPanelYPos($yPos);
			if($valid)$valid = ValidPowerPanelWidth($width);
			if($valid)$valid = ValidPowerPanelDepth($depth);
			if($valid)$valid = ValidNotes($notes);
			
			$safeName = MakeHTMLSafe($name);
		}
		
		//validate parent records exist
		if(!$add)
			if($valid)$valid = ValidRecord("powerpanelid","Power Panel ID",$powerPanelID,"dcim_powerpanel",true);
		//if($valid)$valid = ValidRecord("roomid","Room ID",$roomID,"dcim_room",true); //checked bellow
		
		//-1 upsid is valid - linked to no ups
		if($valid && $upsID!=-1)$valid = ValidRecord("powerupsid","UPS ID",$upsID,"dcim_powerups",true);
		
		//validate room id and look up some parent info / dimentions
		if(!$delete && $valid)
		{
			$passedDBChecks = false;//set false untill DB checks validate - if crash, following SQL shouln't execute
			$query = "SELECT r.roomid, r.name AS room, r.width, r.depth, s.siteid, s.name AS site
						FROM dcim_room AS r
							LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
						WHERE r.roomid=?;";
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $roomID) || !$stmt->execute())
				$errorMessage[] = "ProcessPowerPanelAction() Prepare failed: ($action-1) (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				$stmt->store_result();
				$passedDBChecks = $stmt->num_rows==1;
				
				if($passedDBChecks)
				{//flip xpos to interior of room if negative
					$stmt->bind_result($roomID, $roomName, $parentWidth, $parentDepth, $siteID, $siteName);
					$stmt->fetch();
					if($xPos<0)
						$xPos = $parentWidth+$xPos;
					if($yPos<0)
						$yPos = $parentDepth+$yPos;
					$safeSiteName = MakeHTMLSafe($siteName);
					$safeRoomName = MakeHTMLSafe($roomName);
				}
				else
					$errorMessage[] = "Error: Room ID#$roomID not found.";
			}
			$valid = $passedDBChecks;
		}
		
		//validate that this is not a duplicate panel name within this site
		if(!$delete && $valid)
		{
			$passedDBChecks = false;//set false untill DB checks validate - if crash, following SQL shouln't execute
			$query = "SELECT pp.powerpanelid, pp.name, s.name AS site
				FROM dcim_room AS r1 
					LEFT JOIN dcim_site AS s ON s.siteid=r1.siteid
					LEFT JOIN dcim_room AS r2 ON r2.siteid = r1.siteid
					LEFT JOIN dcim_powerpanel AS pp ON pp.roomid=r2.roomid
				WHERE r1.roomid=? AND pp.powerpanelid!=? AND pp.name=?";
				
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('iis', $roomID,$powerPanelID,$name) || !$stmt->execute())
				$errorMessage[] = "ProcessPowerPanelAction() Prepare failed: ($action-2) (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				$stmt->store_result();
				$passedDBChecks = $stmt->num_rows==0;
				
				if(!$passedDBChecks)
				{//build error msg
					$stmt->bind_result($foundPanelID, $foundName, $siteName);
					$stmt->fetch();
					$errorMessage[] = "Error: Existing power panel in ".MakeHTMLSafe($siteName)." named '".MakeHTMLSafe($foundName)."' found, ID:$foundPanelID.";
				}
			}
			$valid = $passedDBChecks;
		}
		
		//validate This is a lone PowerPanel record with no circuits linked to it so it can be deleted
		if($delete && $valid)
		{
			$passedDBChecks = false;//set false untill DB checks validate - if crash, following SQL shouln't execute
			$query = "SELECT pc.powercircuitid, CONCAT(pp.name,' CRK#',pc.circuit)
						FROM dcim_powerpanel AS pp
							RIGHT JOIN dcim_powercircuit AS pc ON pc.powerpanelid=pp.powerpanelid
						WHERE pp.powerpanelid=?";
			
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $powerPanelID) || !$stmt->execute())
				$errorMessage[] = "ProcessPowerPanelAction() Prepare failed: ($action-2) (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				$stmt->store_result();
				$passedDBChecks = $stmt->num_rows==0;
				
				if(!$passedDBChecks)
				{//build error msg
					$errorMessage[] = "Error: Cannot delete this Power Panel because there are ".($stmt->num_rows)." power circuits linked to it.";
				}
			}
			$valid = $passedDBChecks;
		}
		
		//do actions
		if($valid)
		{
			if($add)
			{
				$query = "INSERT INTO dcim_powerpanel
					(powerupsid, roomid,name,amps,circuits,xpos,ypos,width,depth,orientation,note,edituser,editdate)
					VALUES(   ?,      ?,   ?,   ?,       ?,   ?,   ?,    ?,    ?,          ?,   ?,       ?,CURRENT_TIMESTAMP)";
				//                                                             urnacxywdonu
				if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('iisiiddddssi', $upsID,$roomID,$name,$amps,$circuits,$xPos,$yPos,$width,$depth,$orientation,$notes,$userID))
					$errorMessage[] = "Prepare failed: ($action-3) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{
					if (!$stmt->execute())
						$errorMessage[] = "Failed to execute power panel add (" . $stmt->errno . "-" . $stmt->error . ").";
					else
					{
						$affectedCount = $stmt->affected_rows;
						$totalAffectedCount += $affectedCount;
						if($affectedCount==1)
						{
							$resultMessage[] = "Successfully added power panel '".$safeName."' to $safeSiteName $safeRoomName";
							LogDBChange("dcim_powerpanel",-1,"I","roomid=$roomID AND name='$name'");
						}
						else
							$errorMessage[] = "Success, but affected $affectedCount rows.";
					}
				}
			}
			else if($delete)
			{
				$query = "DELETE FROM dcim_powerpanel WHERE powerpanelid=? LIMIT 1";
				
				if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $powerPanelID) || !$stmt->execute())
					$errorMessage[] = "Process Power Panel Delete Prepare failed: ($action-4) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{
					$affectedCount = $stmt->affected_rows;
					$totalAffectedCount += $affectedCount;
					if($affectedCount==1)
					{
						$resultMessage[] = "Successfully Deleted Power Panel#$powerPanelID '".$safeName."' from $safeSiteName $safeRoomName";
						LogDBChange("dcim_powerpanel",$powerPanelID,"D");
						$redirectPage = "./?roomid=$roomID";
					}
					else
						$errorMessage[] = "Success, but affected $affectedCount rows.";
				}
			}
			else
			{
				$query = "UPDATE dcim_powerpanel
					SET powerupsid=?,roomid=?,name=?,amps=?,circuits=?,xpos=?,ypos=?,width=?,depth=?,orientation=?,note=?
					WHERE powerpanelid=?
					LIMIT 1";
				//                                                             urnacxywdonk - k is key
				if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('iisiiddddssi', $upsID, $roomID,$name,$amps,$circuits,$xPos,$yPos,$width,$depth,$orientation,$notes, $powerPanelID))
					$errorMessage[] = "Process Power Panel Update Prepare failed: ($action-5) (" . $mysqli->errno . ") " . $mysqli->error.".";
				else
				{
					if (!$stmt->execute())//execute
						$errorMessage[] = "Failed to execute power panel edit (" . $stmt->errno . "-" . $stmt->error . ").";
					else
					{
						$affectedCount = $stmt->affected_rows;
						$totalAffectedCount += $affectedCount;
						if($affectedCount==1)
						{
							$resultMessage[] = "Successfully edited power panel '".$safeName."' in $safeSiteName $safeRoomName. $totalAffectedCount records updated.";
							UpdateRecordEditUser("dcim_powerpanel","powerpanelid",$powerPanelID);//do this seperate to distinquish actual record changes from identical updates
							LogDBChange("dcim_powerpanel",$powerPanelID,"U");
						}
						else
							$resultMessage[] = "Success, but affected $affectedCount rows.";
					}
				}
			}
		}
		return $redirectPage;
	}
	
	function ProcessLocationAction($action)
	{
		global $mysqli;
		global $userID;
		global $errorMessage;
		global $resultMessage;
		
		$valid = true;
		$totalAffectedCount = 0;
		$redirectPage = "";
		
		if(!CustomFunctions::UserHasLocationPermission())
		{
			$valid = false;
			$errorMessage[] = "You do not have permission to edit locations. Please contact your administrator.";
		}
		else
		{
			$add = $action==="Location_Add";
			$delete = $action==="Location_Delete";
			
			$locationID = GetInput("locationid");
			$roomID = GetInput("roomid");
			$name = trim(GetInput("name"));
			$altName = trim(GetInput("altname"));
			$type = GetInput("type");
			$units = GetInput("units");
			$orientation = GetInput("orientation");
			$keyno = GetInput("keyno");
			$allocation = GetInput("allocation");
			$order = GetInput("order");
			$xPos = GetInput("xpos");
			$yPos = GetInput("ypos");
			$width = GetInput("width");
			$depth = GetInput("depth");
			$notes = trim(GetInput("notes"));
		}
		
		if(!$delete)
		{
			if($valid)$valid = ValidLocationName($name);
			if($valid)$valid = ValidLocationAltName($altName);
			if($valid)$valid = ValidLocationType($type);
			if($valid)$valid = ValidLocationUnits($units);
			if($valid)$valid = ValidLocationOrientation($orientation);
			if($valid)$valid = ValidLocationKeyno($keyno);
			if($valid)$valid = ValidLocationAllocation($allocation);
			if($valid)$valid = ValidLocationOrder($order);
			if($valid)$valid = ValidLocationXPos($xPos);
			if($valid)$valid = ValidLocationYPos($yPos);
			if($valid)$valid = ValidLocationWidth($width);
			if($valid)$valid = ValidLocationDepth($depth);
			if($valid)$valid = ValidNotes($notes);
		}
		
		//validate parent records exist
		if(!$add)
			if($valid)$valid = ValidLocation($locationID, true);
		if($valid)$valid = ValidRoom($roomID, true);
		
		//validate room id and look up parent dimensions
		if(!$delete && $valid)
		{
			$passedDBChecks = false;//set false untill DB checks validate - if crash, following SQL shouln't execute
			$query = "SELECT r.roomid, r.width, r.depth
						FROM dcim_room AS r
						WHERE r.roomid=?;";
				
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $roomID) || !$stmt->execute())
				$errorMessage[] = "ProcessLocationAction() Prepare failed: ($action-1) (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				$stmt->store_result();
				$passedDBChecks = $stmt->num_rows==1;
				
				if($passedDBChecks)
				{//flip xpos to interior of room if negative
					$stmt->bind_result($roomID, $parentWidth, $parentDepth);
					$stmt->fetch();
					if($xPos<0)
						$xPos = $parentWidth+$xPos;
					if($yPos<0)
						$yPos = $parentDepth+$yPos;
				}
				else
					$errorMessage[] = "Error: Room ID#$roomID not found.";
			}
			$valid = $passedDBChecks;
		}
		
		//validate that this is not a duplicate name within this room
		if(!$delete && $valid)
		{
			$passedDBChecks = false;//set false untill DB checks validate - if crash, following SQL shouln't execute
			$query = "SELECT l.locationid, l.name, l.altname FROM dcim_location AS l WHERE l.roomid=? AND locationid!=? AND (l.name=? OR l.altname=?);";
				
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('iiss', $roomID,$locationID,$name,$name) || !$stmt->execute())
				$errorMessage[] = "ProcessLocationAction() Prepare failed: ($action-2) (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				$stmt->store_result();
				$passedDBChecks = $stmt->num_rows==0;
				
				if(!$passedDBChecks)
				{//build error msg
					$stmt->bind_result($foundDeviceID, $foundName, $foundAltName);
					$stmt->fetch();
					if(strlen($foundAltName)>0)
						$foundAltName = " ('".MakeHTMLSafe($foundAltName)."')";
					$errorMessage[] = "Error: Existing location named '".MakeHTMLSafe($foundName)."'$foundAltName found, ID:$foundDeviceID.";
				}
			}
			$valid = $passedDBChecks;
		}
		
		//validate This is a lone location record with no power or devices linked to it so it can be deleted
		if($delete && $valid)
		{
			$passedDBChecks = false;//set false untill DB checks validate - if crash, following SQL shouln't execute
			$query = "SELECT d.locationid, 'D' as recType, d.deviceid, d.name
							FROM dcim_device AS d 
							WHERE d.locationid=?
					UNION
						SELECT pcl.locationid, 'P' as recType, pcl.powercircuitid, CONCAT(pp.name,' CRK#',pc.circuit)
							FROM dcim_powercircuitloc AS pcl
							LEFT JOIN dcim_powercircuit AS pc ON pc.powercircuitid=pcl.powercircuitid
							LEFT JOIN dcim_powerpanel AS pp ON pc.powerpanelid=pp.powerpanelid
							WHERE pcl.locationid=?";
			
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('ii', $locationID,$locationID) || !$stmt->execute())
				$errorMessage[] = "ProcessLocationAction() Prepare failed: ($action-3) (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				$stmt->store_result();
				$passedDBChecks = $stmt->num_rows==0;
				
				if(!$passedDBChecks)
				{//build error msg
					$errorMessage[] = "Error: Cannot delete this location because there are ".($stmt->num_rows)." power/device records linked to it.";
				}
			}
			$valid = $passedDBChecks;
		}
		
		//do actions
		if($valid)
		{
			if($add)
			{
				$query = "INSERT INTO dcim_location
					(roomid,name,altname,type,units,xpos,ypos,width,depth,orientation,keyno,allocation,`order`,visible,note,edituser,editdate)
					VALUES(?,  ?,      ?,   ?,    ?,   ?,   ?,    ?,    ?,          ?,    ?,         ?,      ?,    'T',   ?,       ?,CURRENT_TIMESTAMP)";
				//                                                             rnatuxywdokaonu
				if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('isssiddddsssssi', $roomID,$name,$altName,$type,$units,$xPos,$yPos,$width,$depth,$orientation,$keyno,$allocation,$order,$notes,$userID))
					$errorMessage[] = "Prepare failed: ($action-4) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{
					if (!$stmt->execute())
						$errorMessage[] = "Failed to execute location add (" . $stmt->errno . "-" . $stmt->error . ").";
					else
					{
						$affectedCount = $stmt->affected_rows;
						$totalAffectedCount += $affectedCount;
						if($affectedCount==1)
						{
							$resultMessage[] = "Successfully added location '".$name."' to Room#$roomID.";
							LogDBChange("dcim_location",-1,"I","roomid=$roomID AND name='$name'");
						}
						else
							$errorMessage[] = "Success, but affected $affectedCount rows.";
					}
				}
			}
			else if($delete)
			{
				$query = "DELETE FROM dcim_location WHERE locationid=? LIMIT 1";
				
				if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $locationID) || !$stmt->execute())
					$errorMessage[] = "Process Location Delete Prepare failed: ($action-5) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{
					$affectedCount = $stmt->affected_rows;
					$totalAffectedCount += $affectedCount;
					if($affectedCount==1)
					{
						$resultMessage[] = "Successfully Deleted Location#$locationID '".$name."' from Room#$roomID..";
						LogDBChange("dcim_location",$locationID,"D");
						$redirectPage = "./?roomid=$roomID";
					}
					else
						$errorMessage[] = "Success, but affected $affectedCount rows.";
				}
			}
			else
			{
				$query = "UPDATE dcim_location AS l
					SET roomid=?,name=?,altname=?,type=?,units=?,xpos=?,ypos=?,width=?,depth=?,orientation=?,keyno=?,allocation=?,l.order=?,note=?
					WHERE locationid=?
					LIMIT 1";
				//                                                             rnatuxywdokaonk
				if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('isssiddddsssssi', $roomID,$name,$altName,$type,$units,$xPos,$yPos,$width,$depth,$orientation,$keyno,$allocation,$order,$notes, $locationID))
					$errorMessage[] = "Process Location Update Prepare failed: ($action-6) (" . $mysqli->errno . ") " . $mysqli->error.".";
				else
				{
					if (!$stmt->execute())//execute
						$errorMessage[] = "Failed to execute location edit (" . $stmt->errno . "-" . $stmt->error . ").";
					else
					{
						$affectedCount = $stmt->affected_rows;
						$totalAffectedCount += $affectedCount;
						if($affectedCount==1)
						{
							$resultMessage[] = "Successfully edited location '".$name."' in Room#$roomID.. $totalAffectedCount records updated.";
							UpdateRecordEditUser("dcim_location","locationid",$locationID);//do this seperate to distinquish actual record changes from identical updates
							LogDBChange("dcim_location",$locationID,"U");
						}
						else
							$resultMessage[] = "Success, but affected $affectedCount rows.";
					}
				}
			}
		}
		return $redirectPage;
	}
	
	function ProcessSubnetAction($action)
	{
		global $mysqli;
		global $userID;
		global $errorMessage;
		global $resultMessage;
		
		$add = $action==="Subnet_Add";
		$delete = $action==="Subnet_Delete";
		
		$vlanID = GetInput("vlanid");
		$devicePortID = GetInput("deviceportid");
		$portID = GetInput("portid");
		$vlanInput = GetInput("vlan");
		$subnet = GetInput("subnet");
		$mask= GetInput("mask");
		$gateway = GetInput("gateway");
		$first = GetInput("first");
		$last = GetInput("last");
		$note = GetInput("note");
		
		
		$totalAffectedCount = 0;
		$valid = true;
		
		if(!$add)
		{
			//if edit/delete then validate vlanID, otherwise a new one will be generated on insert
			if($valid)$valid = ValidGenericID($vlanID, "VLAN ID");
		}
		if($valid)$valid = ValidGenericID($portID, "Port ID");
		if($valid)$valid = ValidSubnet($subnet);
		if($valid)$valid = ValidSubnetMask($mask);
		if($valid)$valid = ValidSubnetGateway($gateway);
		if($valid)$valid = ValidSubnetFirstIP($first);
		if($valid)$valid = ValidSubnetLastIP($last);
		
		if($valid)
		{
			$genNewTempVLAN = strlen(trim($vlanInput))==0;
			if(!$genNewTempVLAN)$valid = ValidSubnetVLAN($vlanInput,$vlan,$istemp);
		}
		
		//DB CHECKS
		if(!$add && $valid)$valid = ValidRecord("vlanid", "VLAN ID", $vlanID, "dcim_vlan", true,"vlan",$vlan);
		if(!$delete && $valid)$valid = ValidRecord("deviceportid", "Device Port", $devicePortID, "dcim_deviceport",true);
		
		//TODO - really should be locking the table here to prevent possible parrelleling issues - multiple people could create.... maybe not if php interpreter only processes one call at a time...
		//get new VLAN  - (min-1)
		if($valid && $genNewTempVLAN)
		{
			$valid = false;
			$passedDBChecks = false;
			$query = "SELECT MIN(vlan) AS min FROM dcim_vlan";
				
			if (!($stmt = $mysqli->prepare($query))) 
				$errorMessage[] = "ValidRecord($keyField,$keyName,$key,$table,$shouldExist): Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
			else 
			{
				if (!$stmt->execute())//execute - //failed (errorNo-error) 
					$errorMessage[] = "Failed to execute ValidRecord($keyField,$keyName,$key,$table,$shouldExist) (" . $stmt->errno . "-" . $stmt->error . ").";
				else 
				{
					$stmt->store_result();
					$count = $stmt->num_rows;
				
					if($count==1)
					{
						//update input locationid
						$stmt->bind_result($minVLAN);
						$stmt->fetch();
						$vlan = $minVLAN-1;
						$istemp = true;
						$passedDBChecks = true;
					}
					else 
						$errorMessage[] = "Failed to Locate new Temp VLAN";
					
				}
			}
			$valid=$passedDBChecks;
		}
		
		//$errorMessage[] = "Dev info - valid=$valid";
		//vlan should be positive of negative here
		//push to DB
		if($valid)
		{
			$displayVLAN = FormatVLAN($vlan);
			if($add)
			{
				$query = "INSERT INTO dcim_vlan
					(vlan,subnet,mask,gateway,first,last,note,edituser,editdate) 
					VALUES(?,?,?,?,?,?,?,?,CURRENT_TIMESTAMP)";
					
				if (!($stmt = $mysqli->prepare($query)))
					$errorMessage[] = "Prepare failed: ($action-1) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{//					   vsmgflnu
					$stmt->bind_Param('sssssssi', $vlan,$subnet,$mask,$gateway,$first,$last,$note,$userID);
					
					if (!$stmt->execute())//execute 
						//failed (errorNo-error)
						$errorMessage[] = "Failed to execute VLAN add (" . $stmt->errno . "-" . $stmt->error . ").";
					else 
					{
						$affectedCount = $stmt->affected_rows;
						$totalAffectedCount += $affectedCount;
						LogDBChange("dcim_vlan",-1,"I","vlan='$vlan' AND subnet='$subnet'");
						if($affectedCount==1)
						{
							$resultMessage[] = "Successfully added VLAN ($displayVLAN).";
						}
						else
							$errorMessage[] = "VLAN added successfully, but affected $affectedCount rows.";
						
						//assume it inserted correctly
						//create vlan-port link
						//$resultMessage[] = "Sucsessfully found inserted power record ID#$powerCircuitID - dbID#$dbPowerCircuitID. - search for ($panel, $circuit)";
						
						//sucsessfull Insert - insert port-vlan link record
						$query = "INSERT INTO dcim_portvlan
							(deviceportid,vlan,edituser,editdate) 
							VALUES(?,?,?,CURRENT_TIMESTAMP)";
							
						if (!($stmt = $mysqli->prepare($query)))
							$errorMessage[] = "Prepare failed: ($action-2) (" . $mysqli->errno . ") " . $mysqli->error;
						else
						{//					   plu
							$stmt->bind_Param('iii', $devicePortID, $vlan, $userID);
							
							if (!$stmt->execute())//execute 
								//failed (errorNo-error)
								$errorMessage[] = "Failed to execute Port VLAN link add (" . $stmt->errno . "-" . $stmt->error . ").";
							else 
							{
								$affectedCount = $stmt->affected_rows;
								$totalAffectedCount += $affectedCount;
								
								if($affectedCount==1)
								{
									LogDBChange("dcim_portvlan",-1,"I","deviceportid=$devicePortID AND vlan=$vlan");
									$resultMessage[] = "Successfully added port VLAN link.";// (vlanID:".$vlanID.",devicePortID:".$devicePortID.").";
								}
								else
									$errorMessage[] = "Port VLAN link added successfully, but affected $affectedCount rows.";
							}
						}
						$resultMessage[] = "$totalAffectedCount total records created.";
					}	
				}
			}
			else if($delete)
			{
				//delete this subnet... look for for another subnet on this VLAN, if not found delete link  
				$query = "DELETE FROM  dcim_vlan
					WHERE vlanid=? 
					LIMIT 1";
		
				if (!($stmt = $mysqli->prepare($query)))
					$errorMessage[] = "Prepare failed: ($action-1) (" . $mysqli->errno . ") " . $mysqli->error.".";
				else
				{
					$stmt->bind_Param('i', $vlanID);	
					
					if (!$stmt->execute())//execute 
						//failed (errorNo-error)
						$errorMessage[] = "Failed to execute subnet delete (" . $stmt->errno . "-" . $stmt->error . ").";
					else
					{
						$affectedCount = $stmt->affected_rows;
						$totalAffectedCount += $affectedCount;
						if($affectedCount==1)
						{
							LogDBChange("dcim_vlan",$vlanID,"D");
							$resultMessage[] = "Successfully deleted subnet (vlan:$vlan subnet:$subnet).";
						
							$noMoreSubnets = ValidRecord("vlan", "VLAN", $vlan, "dcim_vlan", false,"",$x,false,true);
							
							if($noMoreSubnets)
							{
								//delete  that was the last subnet - delete port link(s)
								$query = "DELETE FROM  dcim_portvlan
									WHERE vlan=?";// dont limit
						
								if (!($stmt = $mysqli->prepare($query)))
									$errorMessage[] = "Prepare failed: ($action-2) (" . $mysqli->errno . ") " . $mysqli->error.".";
								else
								{
									$stmt->bind_Param('i', $vlan);	
									
									if (!$stmt->execute())//execute 
										//failed (errorNo-error)
										$errorMessage[] = "Failed to execute subnet port link deletion (" . $stmt->errno . "-" . $stmt->error . ").";
									else
									{
										$affectedCount = $stmt->affected_rows;
										$totalAffectedCount += $affectedCount;
										if($affectedCount>=1)
										{
											LogDBChange("dcim_portvlan",-1,"D","vlan='$vlan'");//XXX this is an odd, non key based call 
											$resultMessage[] = "Successfully un linked VLAN from $affectedCount port(s)(VLAN:$vlan).";
											
										}
										else
										{
											$errorMessage[] = "Successfully unlinked from ports, but affected $affectedCount rows.";
										}
									}
								}
							}
						}
						else
						{
							$errorMessage[] = "Successfully deleted power record, but affected $affectedCount rows.";
						}
					}
				}
			}
			else
			{
				$query = "UPDATE dcim_vlan
					SET vlan=?, subnet=?, gateway=?, first=?, last=? , note=? 
					WHERE vlanid=? 
					LIMIT 1";
		
				if (!($stmt = $mysqli->prepare($query)))
					$errorMessage[] = "Prepare failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error.".";
				else
				{//					vsgflnk
					$stmt->bind_Param('isssssi', $vlan, $subnet, $gateway, $first, $last, $note, $vlanID);	
					
					if (!$stmt->execute())//execute 
						//failed (errorNo-error)
						$errorMessage[] = "Failed to execute VLAN edit (" . $stmt->errno . "-" . $stmt->error . ").";
					else
					{
						$affectedCount = $stmt->affected_rows;
						$totalAffectedCount += $affectedCount;
						if($affectedCount==1)
						{
							$resultMessage[] = "Successfully edited VLAN ($displayVLAN). $totalAffectedCount records updated.";
							UpdateRecordEditUser("dcim_vlan","vlanid",$vlanID);//do this seperate to distinquish actual record changes from identical updates (updates without changes)
							LogDBChange("dcim_vlan",$vlanID,"U");
						}
						else
						{
							$errorMessage[] = "Successfully edited VLAN, but affected $affectedCount rows.";
						}
					}
				}
			}
		}
	}
	
	function ProcessCustomerAction($action)
	{
		global $mysqli;
		global $userID;
		global $errorMessage;
		global $resultMessage;
		
		$redirectPage = "";
		$totalAffectedCount = 0;
		$valid = true;
		
		$add = $action==="Customer_Add";
		
		$hNo = GetInput("hno");
		$cNo = GetInput("cno");
		$name = GetInput("name");
		$status = GetInput("status");;
		$notes = GetInput("notes");
		
		if($valid)$valid = ValidHNo($hNo);
		if($valid)$valid = ValidCNo($cNo);
		if($valid)$valid = ValidCustomerName($name);
		if($valid)$valid = ValidCustomerStatus($status);
		if($valid)$valid = ValidNotes($name);
		
		
		if($valid)
		{
			//set false untill DB checks validate - if crash, following SQL shouln't execute
			$passedDBChecks = false;
			//advanced checks
			if($add)
			{
				//check if customer (hno) already exists before insertion
				$query = "SELECT hno,cno FROM dcim_customer WHERE hno=?";
				
				if (!($stmt = $mysqli->prepare($query)))
				{
					$errorMessage[] = "Prepare failed: ($action-1) (" . $mysqli->errno . ") " . $mysqli->error;
				}
				else
				{
					$stmt->bind_Param('s', $hNo);		
					$stmt->execute();
					$stmt->store_result();
					$custCount = $stmt->num_rows;
					$passedDBChecks = $custCount==0;
					
					if(!$passedDBChecks)
					{
						//build error msg
						$stmt->bind_result($hNoDB,$cNoDB);
						$stmt->fetch();
						
						if(strcmp($hNo,$hNoDB)==0)
							$errorMessage[] = "Error: Existing Customer with H# found. input(H$hNo) found(<a href='./?host=$hNoDB'>H$hNoDB</a>).";
						else if(strcmp($cNo,$cNoDB)==0)
							$errorMessage[] = "Error: Existing Customer with C# found. input(C$cNo) found(<a href='./?host=$hNoDB'>C$cNoDB</a>).";
						else
							$errorMessage[] = "Unknown Error matching H# & C# input(H$hNo,C$cNo) found(<a href='./?host=$hNoDB'>H$hNoDB</a>,C$cNoDB).";
					}
					
				}
			}
			else
			{
				//no db checks for update - not changing hno or cno
				$passedDBChecks = true;
			}
			$valid = $passedDBChecks;
		}
		
		if($valid)
		{
			//push changes to DB
			if($add)
			{
				$query = "INSERT INTO dcim_customer 
					(hno,cno,name,note,status,edituser,editdate) 
					VALUES(?,?,?,?,?,?,CURRENT_TIMESTAMP)";
					
				if (!($stmt = $mysqli->prepare($query)))
					$errorMessage[] = "Prepare failed: ($action-2) (" . $mysqli->errno . ") " . $mysqli->error.".";
				else
				{//					   hcnnsu
					$stmt->bind_Param('iisssi', $hNo, $cNo, $name, $notes, $status, $userID);
					
					if (!$stmt->execute())//execute 
						//failed (errorNo-error)
						$errorMessage[] = "Failed to execute Customer add (" . $stmt->errno . "-" . $stmt->error . ").";
					else
					{
						$affectedCount = $stmt->affected_rows;
						$totalAffectedCount += $affectedCount;
						if($affectedCount==1)
						{
							$resultMessage[] = "Successfully added Customer (H".$hNo.",".$name.").";
							$redirectPage = "./?host=$hNo";
							LogDBChange("dcim_customer",$hNo,"I");
						}
						else
						{
							$resultMessage[] = "Success, but affected $affectedCount rows.";
						}
					}
				}
			}
			else
			{
				$query = "UPDATE dcim_customer 
					SET name=?, note=?, status=? 
					WHERE hno=? 
					LIMIT 1";
		
				if (!($stmt = $mysqli->prepare($query)))
					$errorMessage[] = "Prepare failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error.".";
				else
				{//					   nnsh
					$stmt->bind_Param('sssi', $name, $notes, $status, $hNo);
					
					if (!$stmt->execute())//execute 
						//failed (errorNo-error)
						$errorMessage[] = "Failed to execute Customer edit (" . $stmt->errno . "-" . $stmt->error . ").";
					else 
					{
						$affectedCount = $stmt->affected_rows;
						$totalAffectedCount += $affectedCount;
						if($affectedCount==1)
						{
							$resultMessage[] = "Successfully edited Customer (H".$hNo.",".$name."). $totalAffectedCount records updated.";
							UpdateRecordEditUser("dcim_customer","hno",$hNo);//do this seperate to distinquish actual record changes from identical updates
							LogDBChange("dcim_customer",$hNo,"U");
						}
						else
						{
							$resultMessage[] = "Success, but affected $affectedCount rows.";
						}
					}
				}
			}
		}//end valid SQL do - if not valid error should be set above
		return $redirectPage;
	}
	
	function ProcessDevicePortAction($action)
	{
		global $mysqli;
		global $userID;		
		global $errorMessage;
		global $resultMessage;
		
		$totalAffectedCount = 0;
		$valid = true;
		
		$add = $action==="DevicePort_Add";
		$delete = $action==="DevicePort_Delete";
		
		$deviceID = GetInput("deviceid");
		$devicePortID = GetInput("deviceportid");
		$pic = GetInput("pic");
		$portNo = GetInput("portno");
		$type = GetInput("type");
		$status = GetInput("status");
		$speed = GetInput("speed");
		$mac = strtolower(GetInput("mac"));
		$note = GetInput("note");
		
		if($valid && $add)$valid = ValidGenericID($deviceID, "Device ID");
		if($valid && !$add)$valid = ValidGenericID($devicePortID, "Device Port ID");
		if($valid)$valid = ValidDevicePortPic($pic);
		if($valid)$valid = ValidDevicePortPortNo($portNo);
		if($valid)$valid = ValidDevicePortType($type);
		if($valid)$valid = ValidDevicePortStatus($status);
		if($valid)$valid = ValidDevicePortSpeed($speed);
		if($valid)$valid = ValidDevicePortMac($mac);
		if($valid)$valid = ValidDevicePortNote($note);
		
		if($valid)
		{
			///validate against current data for add and edit
			if(!$delete)
			{
				//validate parent ID
				$valid = false;
				$passedDBChecks = false;
				$query = "SELECT deviceid,hno,name,altname,model,member FROM dcim_device WHERE deviceid=?";
				
				if (!($stmt = $mysqli->prepare($query)))
				{
					$errorMessage[] = "Prepare failed: ($action-1) (" . $mysqli->errno . ") " . $mysqli->error;
				}
				else
				{
					$stmt->bind_Param('i', $deviceID);		
					$stmt->execute();
					$stmt->store_result();
					$deviceCount = $stmt->num_rows;
					$passedDBChecks = $deviceCount==1;
					
					if(!$passedDBChecks)
					{
						//build error msg
						$errorMessage[] = "Error: Existing Device not found with ID:$deviceID.";
					}
					else
					{
						//found parent device - pull down necisary details from it
						$stmt->bind_result($deviceID, $hNo, $deviceName,$deviceAltName, $deviceModel, $deviceMember);
						$stmt->fetch();
						$deviceFullName = GetDeviceFullName($deviceName, $deviceModel, $deviceMember,$deviceAltName, true);
					}
				}
				$valid = $passedDBChecks;
			}
			
			if($valid && !$delete)
			{
				//validate unique deivce(+member)+pic+port combo
				$valid = false;
				$passedDBChecks = false;
				$query = "SELECT deviceportid FROM dcim_deviceport WHERE deviceid=? AND pic=? AND port=? AND NOT(deviceportid=?)";
				
				if (!($stmt = $mysqli->prepare($query)))
				{
					$errorMessage[] = "Prepare failed: ($action-2) (" . $mysqli->errno . ") " . $mysqli->error;
				}
				else
				{
					$stmt->bind_Param('iiii', $deviceID,$pic,$portNo, $devicePortID);		
					$stmt->execute();
					$stmt->store_result();
					$portMatchCount = $stmt->num_rows;
					$passedDBChecks = $portMatchCount==0;
					
					if(!$passedDBChecks)
					{
						//build error msg
						$stmt->bind_result($foundDevicePortID);
						$stmt->fetch();
						
						$errorMessage[] = "Error: Existing Device Port found. ID:$foundDevicePortID.";
					}
				}
				$valid = $passedDBChecks;
			}
				
			if($valid && $delete)
			{
				//make sure there are no existing connections
				$valid = false;
				$passedDBChecks = false;
				$query = "SELECT portconnectionid FROM dcim_portconnection WHERE childportid=? OR parentportid=?";
				
				if (!($stmt = $mysqli->prepare($query)))
				{
					$errorMessage[] = "Prepare failed: ($action-2.1) (" . $mysqli->errno . ") " . $mysqli->error;
				}
				else
				{
					$stmt->bind_Param('ii', $devicePortID, $devicePortID);		
					$stmt->execute();
					$stmt->store_result();
					$portMatchCount = $stmt->num_rows;
					$passedDBChecks = $portMatchCount==0;
					
					if(!$passedDBChecks)
					{
						//build error msg
						$stmt->bind_result($foundPortConnectionID);
						$stmt->fetch();
						
						$errorMessage[] = "Error: Existing Port Connection found. Delete Aborted. Port Connection ID:$foundPortConnectionID.";
					}
				}
				$valid = $passedDBChecks;
			}
				
			if($valid && $delete)
			{
				//make sure there are no existing VLAN links
				$valid = false;
				$passedDBChecks = false;
				$query = "SELECT portvlanid, vlan FROM dcim_portvlan WHERE deviceportid=?";
				
				if (!($stmt = $mysqli->prepare($query)))
				{
					$errorMessage[] = "Prepare failed: ($action-2.1) (" . $mysqli->errno . ") " . $mysqli->error;
				}
				else
				{
					$stmt->bind_Param('i', $devicePortID);		
					$stmt->execute();
					$stmt->store_result();
					$portMatchCount = $stmt->num_rows;
					$passedDBChecks = $portMatchCount==0;
					
					if(!$passedDBChecks)
					{
						//build error msg
						$stmt->bind_result($foundPortVLANID,$vlan);
						$stmt->fetch();
						
						$errorMessage[] = "Error: Existing VLAN still linked to port. Delete Aborted. VLAN:$vlan. ID:$foundPortVLANID";
					}
				}
				$valid = $passedDBChecks;
			}
		}
		
		if($valid)
		{
			//push changes to DB
			if($add)
			{
				if(CustomFunctions::UserHasPortAddEditPermission())
				{
					$query = "INSERT INTO dcim_deviceport 
						(deviceid,pic,port,type,mac,speed,note,status,edituser,editdate) 
						VALUES(?,?,?,?,?,?,?,?,?,CURRENT_TIMESTAMP)";
						
					if (!($stmt = $mysqli->prepare($query)))
						$errorMessage[] = "Process Device Port Insert Prepare failed: ($action-3) (" . $mysqli->errno . ") " . $mysqli->error;
					else
					{//					   dpptmsnsu
						$stmt->bind_Param('iiisssssi', $deviceID, $pic, $portNo, $type, $mac, $speed, $note, $status, $userID);
						
						if (!$stmt->execute())//execute 
							$errorMessage[] = "Failed to execute Device Port Insert '".UserHasAdminPermission()."' (" . $stmt->errno . "-" . $stmt->error . ")";
						else
						{							
							$affectedCount = $stmt->affected_rows;
							$totalAffectedCount += $affectedCount;
							if($affectedCount==1)
							{
								$portFullName = FormatPort($deviceMember, $deviceModel, $pic, $portNo, $type);
								$resultMessage[] = "Successfully Created Device Port ".MakeHTMLSafe($deviceFullName)." $portFullName";
								LogDBChange("dcim_deviceport",-1,"I","deviceid='$deviceID' AND pic='$pic' AND port='$portNo' AND type='$type'");
							}
							else 
								$errorMessage[] = "Success, but affected $affectedCount rows.";
						}
					}
				}
				else
				{
					$errorMessage[] = "Your do not have permission to add ports";
				}
			}
			else if($delete)
			{
				if(CustomFunctions::UserHasPortDeletePermission())
				{
					$query = "DELETE FROM dcim_deviceport WHERE deviceportid=? LIMIT 1";
						
					if (!($stmt = $mysqli->prepare($query)))
						$errorMessage[] = "Process Device Port Delete Prepare failed: ($action-3) (" . $mysqli->errno . ") " . $mysqli->error;
					else
					{//					   k
						$stmt->bind_Param('i', $devicePortID);
						
						if (!$stmt->execute())//execute 
							$errorMessage[] = "Failed to execute Device Port Delete '".UserHasAdminPermission()."' (" . $stmt->errno . "-" . $stmt->error . ")";
						else
						{							
							$affectedCount = $stmt->affected_rows;
							$totalAffectedCount += $affectedCount;
							if($affectedCount==1)
							{
								$resultMessage[] = "Successfully Deleted Port.";
								LogDBChange("dcim_deviceport",$devicePortID,"D"); 
							}
							else 
								$errorMessage[] = "Success, but affected $affectedCount rows.";
						}
					}
				}
				else
				{
					$errorMessage[] = "Your do not have permission to delete ports";
				}
			}
			else
			{
				//update port
				$cont = false;
				if(CustomFunctions::UserHasPortAddEditPermission())
				{
					$query = "UPDATE dcim_deviceport SET
							pic = ?,
							port = ?,
							type = ?,
							status = ?,
							speed = ?,
							mac = ?, 
							note = ?
						WHERE deviceportid=? LIMIT 1 ";
					
					if (!($stmt = $mysqli->prepare($query)))
						$errorMessage[] = "Process Device port Update Prepare failed: ($action-3) (" . $mysqli->errno . ") " . $mysqli->error;
					else
					{//					   pptssmnk
						$stmt->bind_Param('iisssssi', $pic, $portNo, $type, $status, $speed, $mac, $note, $devicePortID);
						$vals = "$pic, $portNo, $type, $status, $speed, $mac, $note, $devicePortID";
						$cont =true;
					}
				}
				else
				{
					//only update minnor fields
					$query = "UPDATE dcim_deviceport SET
							speed = ?,
							mac = ?, 
							note = ?
						WHERE deviceportid=? LIMIT 1 ";
					
					if (!($stmt = $mysqli->prepare($query)))
						$errorMessage[] = "Process Device port Update Prepare failed: ($action-3) (" . $mysqli->errno . ") " . $mysqli->error;
					else
					{//					   smnk
						$stmt->bind_Param('sssi', $speed, $mac, $note, $devicePortID);
						$vals = "$speed, $mac, $note, $devicePortID";
						$cont =true;
					}
				}
		
				if($cont)
				{
					if (!$stmt->execute())//execute 
						//failed (errorNo-error)
						$errorMessage[] = "Failed to execute Device Port edit '".UserHasAdminPermission()."' (" . $stmt->errno . "-" . $stmt->error . ")";
					else
					{							
						$affectedCount = $stmt->affected_rows;
						$totalAffectedCount += $affectedCount;
						if($affectedCount==1)
						{
							$portFullName = FormatPort($deviceMember, $deviceModel, $pic, $portNo, $type);
							$resultMessage[] = "Successfully edited Device Port ".MakeHTMLSafe($deviceFullName)." $portFullName. $totalAffectedCount records updated";
							UpdateRecordEditUser("dcim_deviceport","deviceportid",$devicePortID);//do this seperate to distinquish actual record changes from identical updates
							LogDBChange("dcim_deviceport",$devicePortID,"U");
						}
						else 
						{
							$errorMessage[] = "Success, but affected $affectedCount rows.";
						}
					}
				}
			}
		}
		return $totalAffectedCount;
	}
	
	function ProcessDeviceAction($action)
	{
		global $mysqli;
		global $userID;
		global $errorMessage;
		global $resultMessage;
		
		$totalAffectedCount = 0;
		$valid = true;
		
		$add = $action==="Device_Add";
		
		$deviceID = GetInput("deviceid");
		$hNo = GetInput("hno");
		$deviceName = GetInput("devicename");
		$deviceAltName = GetInput("devicealtname");
		$type = GetInput("type");
		$size = GetInput("size");
		$locationID = GetInput("locationid");
		$unit = GetInput("unit");
		$status = GetInput("status");
		$notes = GetInput("notes");
		$model = GetInput("model");
		$member = GetInput("member");
		$asset = GetInput("asset");
		$serial = GetInput("serial");
		
		if(!$add && $valid)$valid = ValidGenericID($deviceID,"Device ID");
		if($valid)$valid = ValidHNo($hNo);
		if($valid)$valid = ValidDeviceName($deviceName);
		if($valid)$valid = ValidDeviceAltName($deviceAltName);
		if($valid)$valid = ValidDeviceType($type);
		if($valid)$valid = ValidDeviceSize($size);
		//location tested bellow
		if($valid)$valid = ValidDeviceUnit($unit);
		if($valid)$valid = ValidDeviceStatus($status);
		if($valid)$valid = ValidNotes($notes);
		
		if($valid && $type=="S")
		{
			if($valid)$valid = ValidDeviceModel($model);
			if($valid)$valid = ValidDeviceMember($member);
			if($valid)$valid = ValidDeviceAsset($asset);
			if($valid)$valid = ValidDeviceSerial($serial);
		}
		else 
		{
			//These should match must match the definitions in BuildDeviceModelArrays()
			if($type=="F")$model = "Colo Cabinet";
			else if($type=="C")$model = "Colo Cage";
			else if($type=="H")$model = "Colo Half Cabinet";
			$member = 0;
			$asset = "";
			$serial = "";
		}
		
		
		// Check DB for propper existing data
		if($valid)$valid = ValidLocation($locationID,true); 
		if($valid)$valid = ValidCustomer($hNo,true); 
		
		//advanced checks
		if($valid)
		{
			//set false untill DB checks validate - if crash, following SQL shouln't execute
			$passedDBChecks = false;
			
			//check if Device (name) already exists before insertion
			$query = "SELECT deviceid, name, member FROM dcim_device WHERE name=? AND member=? AND NOT(deviceid=?)";
			
			if (!($stmt = $mysqli->prepare($query)))
			{
				//TODO handle errors better
				$errorMessage[] = "Prepare failed: ($action-1) (" . $mysqli->errno . ") " . $mysqli->error;
			}
			else
			{
				$stmt->bind_Param('sii', $deviceName,$member, $deviceID);		
				$stmt->execute();
				$stmt->store_result();
				$custCount = $stmt->num_rows;
				$passedDBChecks = $custCount==0;
				
				if(!$passedDBChecks)
				{
					//build error msg
					$stmt->bind_result($foundDeviceID, $foundName, $foundMember);
					$stmt->fetch();
					
					$errorMessage[] = "Error: Existing Device named \"".MakeHTMLSafe($foundName)." Member $foundMember\" ID:$foundDeviceID found.";
				}
			}
			$valid = $passedDBChecks;
		}
		
		if($valid)
		{
			//push changes to DB
			if($add)
			{
				$query = "INSERT INTO dcim_device
					(hno, locationid, name, altname, member, note, unit, type, size, status, asset, serial, model, edituser, editdate) 
					VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,CURRENT_TIMESTAMP)";
				
				//TODO handle errors better
				if (!($stmt = $mysqli->prepare($query)))
					$errorMessage[] = "Process Device Insert Prepare failed: ($action -2) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{//					   hlnamnutssasmu
					$stmt->bind_Param('iissisissssssi', $hNo, $locationID, $deviceName, $deviceAltName, $member, $notes, $unit, $type, $size, $status, $asset, $serial, $model, $userID);
					
					if (!$stmt->execute())//execute 
						//failed (errorNo-error)
						$errorMessage[] = "Failed to execute Device Add (" . $stmt->errno . "-" . $stmt->error . ")";
					else
					{
						$affectedCount = $stmt->affected_rows;
						$totalAffectedCount += $affectedCount;
						$addedPortCount = 0;
						
						if($affectedCount==1)
						{
							//worked, now look up that record to create ports for it
							LogDBChange("dcim_device",-1,"I","hno='$hNo' AND name='$deviceName' AND member='$member'");
							//get deviceID
							$query = "SELECT deviceid FROM dcim_device WHERE hno=? AND name=? ORDER BY deviceid DESC LIMIT 1";
							
							if (!($stmt = $mysqli->prepare($query))) 
							{
								$errorMessage[] = "Process Device Post-Insert Lookup Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
							}
							
							$stmt->bind_Param('is', $hNo, $deviceName);
							$stmt->execute();
							$stmt->store_result();
							$stmt->bind_result($deviceID);
							$count = $stmt->num_rows;
							
							if($count==1)
							{
								//found device i just created - shouldn't ever fail
								$stmt->fetch();
								
								//Create device ports
								$addedPortCount = CreateBlankPortsForDevice($deviceID, $model);
								
								if($addedPortCount<=0)
								{
									$errorMessage[] = "Failed to Create Ports. ($deviceID, ".MakeHTMLSafe($model).") ";
								}
							}
							else 
							{
								$errorMessage[] = "Failed to locate new device. Device ports have not been created";
							}
						}
						
						if($affectedCount==1)
						{
							$resultMessage[] = "Successfully added Device (H".$hNo.",".MakeHTMLSafe($deviceName)."). $totalAffectedCount records created.";
							if($addedPortCount>0)
								$resultMessage[] ="Successfully added $addedPortCount Ports for $deviceName.";
						}
						else 
						{
							$resultMessage[] = "Success, but affected $totalAffectedCount rows.";
						}
					}	
				}
			}
			else
			{
				$query = "UPDATE dcim_device SET
						locationid = ?,unit = ?, 
						altname = ?, 
						status = ?, 
						asset = ?,
						serial = ?,
						note = ?
						WHERE deviceid =? LIMIT 1 ";
				
				if (!($stmt = $mysqli->prepare($query)))
					$errorMessage[] = "Process Device Update Prepare failed: ($action-3) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{//					   luasasnd
					$stmt->bind_Param('iisssssi', $locationID, $unit, $deviceAltName, $status, $asset, $serial, $notes, $deviceID);
					
					if (!$stmt->execute())//execute 
						//failed (errorNo-error)
						$errorMessage[] = "Failed to execute Device edit (" . $stmt->errno . "-" . $stmt->error . ") ";
					else
					{							
						$affectedCount = $stmt->affected_rows;
						$totalAffectedCount += $affectedCount;
						if($affectedCount==1)
						{
							$resultMessage[] = "Successfully edited Device (H".$hNo.",".MakeHTMLSafe($deviceName)."). $totalAffectedCount records updated";
							UpdateRecordEditUser("dcim_device","deviceid",$deviceID);//do this seperate to distinquish actual record changes from identical updates
							LogDBChange("dcim_device",$deviceID,"U");
						}
						else 
						{
							$resultMessage[] = "Success, but affected $affectedCount rows.";
						}
					}
				}
			}
		}//end valid SQL do - if not valid error should be set above
	}
	
	function ProcessConnectionAction($action)
	{
		global $mysqli;
		global $userID;
		global $errorMessage;
		global $resultMessage;
		
		$totalAffectedCount = 0;
		$valid = true;
		
		$add = $action==="Connection_Add";
		$delete = $action==="Connection_Delete";
		
		$portConnectionID = GetInput("portconnectionid");
		$childDeviceID = GetInput("childdeviceid");
		$childPortID = GetInput("childportid");
		$parentDeviceID = GetInput("parentdeviceid");
		$parentPortID = GetInput("parentportid");
		$patches = GetInput("patches");
		$updatePorts = GetInput("updateports");
		
		
		if(!$add && $valid)
		{
			$valid = ValidGenericID($portConnectionID,"Port Connection ID");
			
			if($valid)
			{
				$validFlags = array('T','F');
				$valid = ValidFlag($updatePorts,"Update Ports Choice",$validFlags);
				$updatePorts = $updatePorts=="T";
			}
		}
		
		if(!$delete)
		{
			//if($valid)$valid = ValidGenericID($childDeviceID,"Child Device ID");
			if($valid)$valid = ValidGenericID($childPortID,"Child Port ID");
			//if($valid)$valid = ValidGenericID($parentDeviceID,"Parent Device ID");
			if($valid)$valid = ValidGenericID($parentPortID,"Parent Port ID");
			if($valid)$valid = ValidNotes($patches);
		
			if($valid)
			{
				$valid = $childPortID!=$parentPortID;
				if(!$valid)$errorMessage[] = "Child port cannot be the same as the parent port.";
			}
		}
		
		
		//advanced checks
		//check if port connection record exists
		$oldChildPortID = -1;
		$oldParentPortID = -1;
		if(!$add && $valid)
		{
			//set false untill DB checks validate - if crash, following SQL shouln't execute
			$passedDBChecks = false;
			
			//check if Device (name) already exists before insertion
			$query = "SELECT portconnectionid, childportid, parentportid FROM dcim_portconnection WHERE portconnectionid=?";
			
			if (!($stmt = $mysqli->prepare($query)))
			{
				$errorMessage[] = "Prepare failed: ($action-1) (" . $mysqli->errno . ") " . $mysqli->error;
			}
			else
			{
				$stmt->bind_Param('i', $portConnectionID);		
				$stmt->execute();
				$stmt->store_result();
				$count = $stmt->num_rows;
				$passedDBChecks = $count==1;
				
				if(!$passedDBChecks)
				{
					//build error msg
					$errorMessage[] = "Error: Failed to locate existing connection (ID:$portConnectionID).";
				}
				else 
				{
					$stmt->bind_result($portConnectionID, $oldCPortID, $oldPPortID);
					$stmt->fetch();
					
					$oldChildPortID = $oldCPortID;
					$oldParentPortID = $oldPPortID;
				}
			}
			$valid = $passedDBChecks;
		}
		//checkif both ports are valid
		if(!$delete && $valid)
		{
			//set false untill DB checks validate - if crash, following SQL shouln't execute
			$passedDBChecks = false;
			
			//check if Device (name) already exists before insertion
			$query = "SELECT * FROM dcim_deviceport WHERE deviceportid=? OR deviceportid=?";
			
			if (!($stmt = $mysqli->prepare($query)))
			{
				$errorMessage[] = "Prepare failed: ($action-2) (" . $mysqli->errno . ") " . $mysqli->error;
			}
			else
			{
				$stmt->bind_Param('ii', $childPortID, $parentPortID);		
				$stmt->execute();
				$stmt->store_result();
				$count = $stmt->num_rows;
				$passedDBChecks = $count==2;
				
				if(!$passedDBChecks)
				{
					//build error msg
					$errorMessage[] = "Error: Failed to locate one or both ports (IDs $childPortID & $parentPortID).";
				}
			}
			$valid = $passedDBChecks;
		}
		//check if eaither port already has connection
		if(!$delete && $valid)
		{
			//set false untill DB checks validate - if crash, following SQL shouln't execute
			$passedDBChecks = false;
			
			//check if Device (name) already exists before insertion
			$query = "SELECT portconnectionid FROM dcim_portconnection WHERE NOT(portconnectionid=?) AND (childportid=? OR childportid=? OR parentportid=? OR parentportid=?)";
			
			if (!($stmt = $mysqli->prepare($query)))
			{
				$errorMessage[] = "Prepare failed: ($action-3) (" . $mysqli->errno . ") " . $mysqli->error;
			}
			else
			{
				$stmt->bind_Param('iiiii', $portConnectionID,$childPortID, $parentPortID, $childPortID, $parentPortID);		
				$stmt->execute();
				$stmt->store_result();
				$count = $stmt->num_rows;
				$passedDBChecks = $count==0;
				
				if(!$passedDBChecks)
				{
					//build error msg
					$stmt->bind_result($foundPortConnectionID);
					$stmt->fetch();
					
					$errorMessage[] = "Error: Existing connection record found for one or both ports (ID:$foundPortConnectionID).";
				}
			}
			$valid = $passedDBChecks;
		}
		
		//$errorMessage[] = "valid was '$valid' portConnectionID=$portConnectionID add=$add";
		//$valid = false;$errorMessage[] = " Dev stop";
		
		if($valid)
		{
			//push changes to DB
			if($add)
			{
				$query = "INSERT INTO dcim_portconnection
					(childportid, parentportid, patches, edituser, editdate) 
					VALUES(?,?,?,?,CURRENT_TIMESTAMP)";
					
				if (!($stmt = $mysqli->prepare($query)))
					$errorMessage[] = "Process Connection Insert Prepare failed: ($action -2) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{//					   cppu
					$stmt->bind_Param('iisi', $childPortID, $parentPortID, $patches, $userID);
					
					if (!$stmt->execute())//execute 
						//failed (errorNo-error)
						$errorMessage[] = "Failed to execute Connection Add (" . $stmt->errno . "-" . $stmt->error . ")";
					else
					{
						$affectedCount = $stmt->affected_rows;
						$totalAffectedCount += $affectedCount;
						$updatedPortCount =0;
						
						if($affectedCount==1)
						{
							LogDBChange("dcim_portconnection",-1,"I","childportid='$childPortID' AND parentportid='$parentPortID'");
							//update child port
							if(UpdateDevicePortStatus($childPortID,'A'))
								$updatedPortCount++;
							//update parent port
							if(UpdateDevicePortStatus($parentPortID,'A'))
								$updatedPortCount++;
						}
						
						if($affectedCount==1)
						{
							$resultMessage[] = "Successfully added Connection record.";
							if($updatedPortCount>0)
							{
								if($updatedPortCount==2)
									$resultMessage[] ="Successfully updated both Ports";
								else
									$resultMessage[] ="Successfully updated $updatedPortCount Ports";
							}
						}
						else 
						{
							$resultMessage[] = "Success, but affected $totalAffectedCount rows.";
						}
					}	
				}
			}
			else if($delete)
			{
				$affectedCount = DeletePortConnection($portConnectionID, $oldChildPortID, $oldParentPortID);
			}
			else
			{
				$query = "UPDATE dcim_portconnection SET
						childportid = ?, parentportid = ?, patches=?
						WHERE portconnectionid =? LIMIT 1 ";
		
				if (!($stmt = $mysqli->prepare($query)))
					$errorMessage[] = "Process Device Update Prepare failed: ($action-3) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{//					   cppp
					$stmt->bind_Param('iisi', $childPortID, $parentPortID, $patches, $portConnectionID);
					
					if (!$stmt->execute())//execute 
						//failed (errorNo-error)
						$errorMessage[] = "Failed to execute Device edit (" . $stmt->errno . "-" . $stmt->error . ") ";
					else
					{							
						$affectedCount = $stmt->affected_rows;
						$totalAffectedCount += $affectedCount;
						$updatedPortCount = 0;
						if($affectedCount==1)
						{
							if($oldChildPortID!=$childPortID)
							{
								//update old child port
								if(UpdateDevicePortStatus($oldChildPortID,'D'))
									$updatedPortCount++;
								if($updatePorts)
									$updatedPortCount += TransferVLANs($oldChildPortID,$childPortID);
							}
							if($oldParentPortID!=$parentPortID)
							{
								//update old parent port
								if(UpdateDevicePortStatus($oldParentPortID,'D'))
									$updatedPortCount++;
								if($updatePorts)
									$updatedPortCount += TransferVLANs($oldParentPortID,$parentPortID);
							}
								
							//update child port
							if(UpdateDevicePortStatus($childPortID,'A'))
								$updatedPortCount++;
							//update parent port
							if(UpdateDevicePortStatus($parentPortID,'A'))
								$updatedPortCount++;
								
							UpdateRecordEditUser("dcim_portconnection","portconnectionid",$portConnectionID);//do this seperate to distinquish actual record changes from identical updates
							LogDBChange("dcim_portconnection",$portConnectionID,"U");
						}
							
						if($affectedCount==1)
						{
							$totalAffectedCount += $updatedPortCount;
							$resultMessage[] = "Successfully edited connection. $totalAffectedCount records updated";
						}
						else 
						{
							$resultMessage[] = "Success, but affected $affectedCount rows.";
						}
					}
				}
			}
		}//end valid SQL do - if not valid error should be set above
	}
	
	function TransferVLANs($oldPortID, $newPortID)
	{
		global $mysqli;
		global $errorMessage;
		global $resultMessage;
		
		$query = "UPDATE dcim_portvlan SET
				deviceportid = ?
				WHERE deviceportid =?";
		$affectedCount = 0;
		
		if (!($stmt = $mysqli->prepare($query)))
			$errorMessage[] = "Process VLAN Transfer Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
		else
		{//					   no
			$stmt->bind_Param('ii', $newPortID, $oldPortID);
			
			if (!$stmt->execute())//execute 
				//failed (errorNo-error)
				$errorMessage[] = "Failed to execute VLAN Transfer (" . $stmt->errno . "-" . $stmt->error . ") ";
			else
			{							
				$affectedCount = $stmt->affected_rows;
				if($affectedCount>=1)
				{
					$resultMessage[] = "Successfully Transfered $affectedCount VLAN(s)";
					UpdateRecordEditUser("dcim_portvlan","deviceportid",-1, "deviceportid=$newPortID");
					LogDBChange("dcim_portvlan",-1,"U","deviceportid=$newPortID");
				}
				else 
				{
					$resultMessage[] = "Success, but affected $affectedCount rows.";
				}
			}
		}
		return $affectedCount;
	}
	
	function DeletePortConnection($devicePortConnectionID, $chidPortID=-1, $parentPortID=-1)
	{
		$affectedCount = 0;
		//Delete record
		global $mysqli;
		global $errorMessage;
		global $resultMessage;
		
		$query = "DELETE FROM dcim_portconnection WHERE portconnectionid=?";
				
		if (!($stmt = $mysqli->prepare($query)))
			$errorMessage[] = "DeletePortConnection() Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
		else
		{
			$stmt->bind_Param('i', $devicePortConnectionID);
			if (!$stmt->execute())//execute 
				//failed (errorNo-error)
				$errorMessage[] = "Failed to Update devicePort Record($status, $speed, $note, $userID, $editDate, $devicePortID) (" . $stmt->errno . "-" . $stmt->error . ")";
			else
			{//Success
				$affectedCount = $stmt->affected_rows;
				if($affectedCount==1)
				{
					LogDBChange("dcim_portconnection",$devicePortConnectionID,"D");
					$resultMessage[] = "Successfully deleted $affectedCount port connection record.";
				}
				else
					$errorMessage[] = "Failed to locate port connection record($devicePortConnectionID) for deletion (" . $stmt->errno . "-" . $stmt->erro.".)";
			}	
		}
		
		if($affectedCount==1 && $chidPortID!=-1 && $parentPortID!=-1)
		{
			//update ports stati... ect
			if(UpdateDevicePortStatus($chidPortID,'D'))
				$affectedCount++;
			if(UpdateDevicePortStatus($parentPortID,'D'))
				$affectedCount++;
		}
		return $affectedCount;
	}
	
	function CreateBlankPortsForDevice($deviceID,$model)
	{
		global $mysqli;
		global $errorMessage;
		global $resultMessage;
		global $userID;
	
		$insertedRecords = 0;
		
		$device = GetDeviceFromModelName($model);
		$startPortNo = $device->startPort;
		$portCount = $device->portCount;
		
		if($device->coloDevice)
		{//dont mass create ports for colo spaces - they can be created manualy
			$portCount = 1;
		}
		
		//could utilize DB default values for PIC(0), TYPE(E) and STATUS(D)
		$query = "INSERT INTO dcim_deviceport
			(deviceid, port, edituser, editdate, pic, type, status) 
			VALUES(?,?,?,CURRENT_TIMESTAMP,0,'E','D')";
		
		if (!($stmt = $mysqli->prepare($query)))
			$errorMessage[] = "CreateBlankPortsForDevice() Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error."\n";
		else
		{
			$lastPort = $startPortNo + $portCount -1;
			$portNo = 0;
			$stmt->bind_Param('iii', $deviceID, $portNo, $userID);
			for ($portNo = $startPortNo; $portNo <= $lastPort; $portNo++)
			{
				if (!$stmt->execute())//execute
					//failed (errorNo-error)
					$errorMessage[] = "Failed to Add blank port($deviceID, $portNo, $userID) (" . $stmt->errno . "-" . $stmt->error . ")\n";
				else
				{
					//Success
					//$resultMessage[] = "Successfully added blank port ($hNo, $deviceID, $portNo, $userID)\n";
					$insertedRecords++;
					LogDBChange("dcim_deviceport",-1,"I","deviceid='$deviceID' AND pic='0' AND port='$portNo' AND type='E'");
				}	
			}
		}
		return $insertedRecords;
	}
	
	function UpdateRecordEditUser($table, $keyField, $key, $filter="")
	{
		global $mysqli;
		global $userID;
		global $resultMessage;
		global $errorMessage;
		
		//run filter and re run with keys
		if(strlen($filter)>0)
		{
			$keys = GetKeysFromFilter($table,$filter,$keyField);
			foreach ($keys as $ukey)
				UpdateRecordEditUser($table, $keyField, $ukey);
			return;
		}
		
		$updateQASQL = ", qauser=-1, qadate=''";
		if(!DoesTableHaveQAFields($table))$updateQASQL = "";
		
		$query = "UPDATE $table SET edituser=$userID, editdate=CURRENT_TIMESTAMP $updateQASQL
				WHERE  $keyField = $key LIMIT 1 ";
	
		if (!($stmt = $mysqli->prepare($query)))
			$errorMessage[] = "UpdateRecordEditUser: Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
		else
		{
			if (!$stmt->execute())//execute 
				//failed (errorNo-error)
				$errorMessage[] = "Failed to execute EditUser update in $table (" . $stmt->errno . "-" . $stmt->error . ") ";
			else
			{
				$affectedCount = $stmt->affected_rows;
				if($affectedCount==1)
				{
					//silent update
					//$resultMessage[] = "Sucsesfuly Updated Record Edit User($table, $key)";
					return true;
				}
				else 
				{
					$resultMessage[] = "Sucsesfuly Updated Record Edit User, but affected $affectedCount rows ($table, $key).";
				}
			}
		}
		return false;
	}
	
	function ProcessUserPasswordUpdate()
	{
		global $mysqli;
		global $userID;
		global $errorMessage;
		global $resultMessage;
		global $config_userPasswordSalt;
		
		$totalAffectedCount = 0;
		$valid = true;
		
		$userIDInput = GetInput("userpasid");//dont use global userID in case this user is changing password for another user
		$oldPasInput = GetInput("oldpas");
		$newpas1 = GetInput("newpas1");
		$newpas2 = GetInput("newpas2");
		
		if($valid)$valid = ValidGenericID($userIDInput, "User ID");
		if($valid)$valid = ValidString($oldPasInput, 'Old Password', 1);
		
		$editingSelf = $userIDInput==$userID;
		
		$validateAdminPas = false;
		if(!$editingSelf && UserHasAdminPermission())
			$validateAdminPas = true;
		
		//check entered password againt password in DB
		if($valid)
		{
			$valid = false;//false untill proven true, in case SQL fails
			$query = "SELECT userid, pass, username
				FROM dcim_user
				WHERE userid=?";
			if (!($stmt = $mysqli->prepare($query)))
			{
				$errorMessage[] = "ProcessUserPasswordUpdate Prepare 1 failed: (" . $mysqli->errno . ") " . $mysqli->error;
			}
			else 
			{	
				$stmt->bind_Param('s', $userIDInput);
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($userIDInput, $dbPass, $dbUname);
				$count = $stmt->num_rows;
				
				if($count>0)
				{
					$stmt->fetch();
					
					$oldPasInput = md5($oldPasInput.$config_userPasswordSalt);
					$pasToTest = "";
					if($validateAdminPas)
					{
						//validate against current admin password - admins validate thier own pass before achanging oothers
						if(isset($_COOKIE["dcim_password"]))
							$pasToTest = $_COOKIE["dcim_password"];
					}
					else 
					{
						//changing own password - validate against old pas in DB
						$pasToTest = $dbPass;
					}
					if(strcmp($oldPasInput,$pasToTest)==0)
						$valid = true;
					else
					{
						if($validateAdminPas)
							$errorMessage[] = "Incorrect administrator password";
						else
							$errorMessage[] = "Incorrect current password";
					}
				}
				else
					$errorMessage[] = "User ID \"".MakeHTMLSafe($userIDInput)."\" not found.";
			}//prepare didn't fail
		}
		
		if($valid)$valid = ValidUserPassword($newpas1);
		if($valid)
		{
			$valid = strcmp($newpas1,$newpas2)==0;
			if(!$valid)
				$errorMessage[] = "New passwords dont match";
		}
		
		
		if($valid)//push new password to DB
		{
			$newFinalPas = md5($newpas1.$config_userPasswordSalt);
			$query = "UPDATE dcim_user SET pass=?
				WHERE userid=? LIMIT 1 ";
				
			if (!($stmt = $mysqli->prepare($query)))
				$errorMessage[] = "ProcessUserPasswordUpdate Prepare 2 failed: (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{//					   pu
				$stmt->bind_Param('si', $newFinalPas, $userIDInput);
				
				if (!$stmt->execute())//execute 
					//failed (errorNo-error)
					$errorMessage[] = "Failed to User Password Update (" . $stmt->errno . "-" . $stmt->error . ")";
				else
				{
					$affectedCount = $stmt->affected_rows;
					
					if($affectedCount==1)
					{
						UpdateRecordEditUser("dcim_user","userid",$userID);//do this seperate to distinquish actual record changes from identical updates
						
						if($editingSelf)
						{
							//changing own password so update password cookie... so the user doesnt have to log back in
							UpdateLoginCookies($dbUname, $newFinalPas);
						}
						$resultMessage[] = "Successfully updated password for user $dbUname.";
						//LogDBChange("dcim_user",$userIDInput,"U");//This will do nothing cuz there is no user log file
					}
					else 
					{
						$resultMessage[] = "Success, but affected $totalAffectedCount rows.";
					}
				}	
			}
		}//end valid SQL do - if not valid error should be set above
	}
	
	function ChangeHNo($old, $new, $renameDevices)
	{
		global $mysqli;
		global $errorMessage;
		global $resultMessage;
		
		
		//maybe should test old here - not explicitly necisary but better to be safe
		$continueWithChange = (ValidHNo($new));
		
		if($continueWithChange)
		{
			//check for exising new hno conflict
			// - NOTE: this doesnt actualy check if the old HNO exists - not
			$query = "SELECT hno FROM dcim_customer WHERE hno=?";
			
			if (!($stmt = $mysqli->prepare($query)))
			{
				$errorMessage[] = "Prepare failed: ChangeHNo($new,$old)-1 (" . $mysqli->errno . ") " . $mysqli->error;
			}
			else
			{
				$stmt->bind_Param('s', $new);
				$stmt->execute();
				$stmt->store_result();
				$custCount = $stmt->num_rows;
				$continueWithChange = $custCount==0;
				
				if(!$continueWithChange)
				{
					//build error msg
					$stmt->bind_result($hNoDB);
					$stmt->fetch();
					
					$errorMessage[] = "Error: Existing Customer with H# found. input(H$new) found(H$hNoDB).";
				}
				$stmt->close();
			}
		}
		
		//proceed with changes
		if($continueWithChange)
		{
			//update to new in cust, badge, device, deviceport
			// - NOTE: the update of the root key value is changed in a seperate cmd to ensure all depencancies are changed first
			$query1 = "UPDATE dcim_customer AS c
						LEFT JOIN dcim_badge AS b ON c.hno=b.hno
						LEFT JOIN dcim_device AS d ON  c.hno=d.hno
					SET b.hno=?, d.hno=?
					WHERE c.hno=?";
			$query2 = "UPDATE dcim_customer AS c
					SET c.hno=?
					WHERE c.hno=?";
			
			if (!($stmt1 = $mysqli->prepare($query1)))
			{
				$continueWithChange = false;
				$errorMessage[] = "Prepare failed: ChangeHNo($new,$old)-2a (" . $mysqli->errno . ") " . $mysqli->error;
			}
			else if (!($stmt2 = $mysqli->prepare($query2)))
			{
				$continueWithChange = false;
				$errorMessage[] = "Prepare failed: ChangeHNo($new,$old)-2b (" . $mysqli->errno . ") " . $mysqli->error;
			}
			else
			{//						nno
				$stmt1->bind_Param('iii', $new,$new,$old);
				if (!$stmt1->execute())//execute
				{
					//failed (errorNo-error)
					$continueWithChange = false;
					$errorMessage[] = "Failed to change HNo ($new,$old) (" . $stmt1->errno . "-" . $stmt1->error . ") ";
				}
				else
				{
					$affectedCount = $stmt1->affected_rows;
					$resultMessage[] = "Sucsesfully changed HNo ($old -> $new) affecting $affectedCount detail rows.";
				}
				$stmt1->close();
				
				if($continueWithChange)
				{//						no
					$stmt2->bind_Param('ii', $new,$old);
					if (!$stmt2->execute())//execute
					{
						//failed (errorNo-error)
						$continueWithChange = false;
						$errorMessage[] = "Failed to change HNo ($new,$old) (" . $stmt2->errno . "-" . $stmt2->error . ") ";
					}
					else
					{
						$affectedCount = $stmt2->affected_rows;
						if($affectedCount!=1)
							$errorMessage[] = "Failed to change HNo ($new,$old) Went through but processed $affectedCount rows";
							else
								$resultMessage[] = "Sucsesfully changed HNo ($old -> $new) affecting $affectedCount core rows.";
					}
					$stmt2->close();
				}
			}
			
			
			if($continueWithChange && $renameDevices)
			{
				//update device names to match
				$query = "UPDATE dcim_device AS d
				SET d.name=REPLACE(d.name, '$old-', '$new-')
				WHERE d.hno=?";
				
				if(!($stmt = $mysqli->prepare($query)))
				{
					$errorMessage[] = "Prepare failed: ChangeHNo($new,$old)-3 (" . $mysqli->errno . ") " . $mysqli->error;
				}
				else
				{
					$stmt->bind_Param('i', $new);
					if (!$stmt->execute())//execute
						//failed (errorNo-error)
						$errorMessage[] = "Failed to change HNo / rename devices ($new,$old) (" . $stmt->errno . "-" . $stmt->error . ") ";
						else
						{
							$affectedCount = $stmt->affected_rows;
							$resultMessage[] = "Sucsesfully changed HNo ($old -> $new) - Renamed $affectedCount devices.";
						}
				}
			}
		}
	}
	
	function UpdateDevicePortStatus($devicePortID, $status)
	{
		global $mysqli;
		global $userID;
		global $errorMessage;
		
		$query = "UPDATE dcim_deviceport SET status=?
				WHERE  deviceportid=? LIMIT 1 ";
		
		if (!($stmt = $mysqli->prepare($query)))
			$errorMessage[] = "UpdateDevicePortStatusAndUser: Prepare failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				$stmt->bind_Param('si', $status, $devicePortID);
				if (!$stmt->execute())//execute
					//failed (errorNo-error)
					$errorMessage[] = "Failed to execute Device Port Status Update ($devicePortID,$status) (" . $stmt->errno . "-" . $stmt->error . ") ";
					else
					{
						$affectedCount = $stmt->affected_rows;
						if($affectedCount==1)
						{
							//silent update
							UpdateRecordEditUser("dcim_deviceport","deviceportid",$devicePortID);
							LogDBChange("dcim_deviceport",$devicePortID,"U");
							return true;
						}
						else
						{
							$resultMessage[] = "Sucsesfully updated device port status but affected $affectedCount rows.";
						}
					}
			}
			return false;
	}
	
	function UpdateUserLastActivity($userID)
	{
		global $mysqli;
		global $errorMessage;
		
		$query = "UPDATE dcim_user SET lastactivity=CURRENT_TIMESTAMP
				WHERE userid=? LIMIT 1 ";
		
		if (!($stmt = $mysqli->prepare($query)))
			$errorMessage[] = "UpdateUserLastActivity: Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				$stmt->bind_Param('i', $userID);
				if (!$stmt->execute())//execute
					//failed (errorNo-error)
					$errorMessage[] = "Failed to Update User LastActivity ($userID) (" . $stmt->errno . "-" . $stmt->error . ") ";
					else
					{
						$affectedCount = $stmt->affected_rows;
						if($affectedCount==1)
						{
							//silent update
							return true;
						}
						else
						{
							$resultMessage[] = "Sucsesfully updated User LastActivity but affected $affectedCount rows.";
						}
					}
			}
			return false;
	}
?>