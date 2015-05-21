<?php
	/* These functions process data or create HTML for the page */
	
	function UpdatePermissionLevel()
	{
		global $mysqli;
		global $user;
		global $userID;
		global $resultMessage;
		global $errorMessage;
		
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
			
			$password = md5($password);
		}
		
		//Validate User Authentication against DB - always
		$hasRights = false;
		if(isset($user) && strlen($user) > 0)
		{
			$query = "SELECT userid, username, pass, name, initials, note, permission
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
				$stmt->bind_result($uID, $uName, $dbPass, $uFullName, $uInitials, $note, $permission);
				$count = $stmt->num_rows;
				
				if($count>0)
				{
					$stmt->fetch();
					$userID = $uID;
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
		global $loginCookieDurration;
		//update cookie info
		setcookie("dcim_user", $user, time()+$loginCookieDurration);
		//this should be the MD5 of the password so it is never stored on the local machie as original
		setcookie("dcim_password", $password, time()+$loginCookieDurration);
		
		//update the current var - seesion cookie in browser doesn't actualy update till the page is reloaded
		$_COOKIE["dcim_user"] = $user;
		$_COOKIE["dcim_password"] = $password;
	}
	
	function LoginPrompt()
	{
		//would set $focusSearch=false here if search was visible while at this prompt
		
		$dest= "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		
		echo "<div class='panel'>\n";
		echo "<div class='panel-header'>Login<!--meh='$dest' --></div>\n";
		echo "<div class='panel-body'>\n\n";
		
		?>Please Log In<BR>
		<form name="login" action="<?php echo $dest;?>" method=post>
		<table>
			<TR><td align=right>Username:</td><td><input type="text" name="logInUserName" value="<?php echo GetInput("logInUserName"); ?>"></td></tr>
			<TR><td align=right>Password:</td><td><input type="password" name="logInPassword"></td></tr>
			<TR><td colspan=2 align=center><input type="submit" value="Submit"></td></tr>
		</table>
		</form>
		<script type="text/javascript" language="JavaScript">
			document.forms['login'].elements['logInUserName'].focus();
		</script>
		<BR>
		<?php

		//reset password link?
		
		echo "</div>\n";
		echo "</div>\n";
	}
	
	/* function ToggleVisibility(id)
	{
		var result = false;
		if(active)
		{
			document.getElementById(id).className = 'show'; 
			result = false;
		}
		else
		{
			document.getElementById(id).className = 'hide';
			result = true;
		}
		return result
	} */
	
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
		else if($action==="Circuit_Add" || $action==="Circuit_Edit" || $action==="Circuit_Delete")
		{
			$redirectPage = ProcessCircuitAction($action);
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
	
	function BackupDatabase()
	{
		/*
		global $db_host;
		global $db_user;
		global $db_password;
		global $database;
		
		$backup_file = "db_autoBackup_" . date("Y-m-d-H-i-s") . '.gz';
		$command = "mysqldump --opt -h $dbhost -u $dbuser -p $dbpass "."test_db | gzip > $backup_file";
		
		select * into outfile '/tmp/outfile.csv' FIELDS TERMINATED BY ',' ENCLOSED BY '"' ESCAPED BY '\\' LINES TERMINATED BY '\n' from database.table_name;
		
		system($command);
		*/
	}
	
	function ProcessPowerAuditPanelUpdate($action)
	{
		global $mysqli;
		global $userID;
		global $errorMessage;
		global $resultMessage;

		$valid = false;
		$powerIDs = array();
		$loads = array();
		$stati = array();
		$circuitsPerPanel = 42;
		
		for($circuit=1; $circuit<=$circuitsPerPanel; $circuit++)
		{
			$powerID = GetInput("c".$circuit."powerid");
			$load = GetInput("c".$circuit."load");
			$status = GetInput("c".$circuit."status");
			
			if(strlen($powerID)>0 && strlen($load)>0)
			{
				$powerIDs[] = $powerID;
				$loads[] = $load;
				if(!isset($status) || strlen($status)==0)
					$status = "D";
				$stati[] = $status;
			}
		}
		
		$inputCount = count($powerIDs);
		$valid = $inputCount>0;
		if($valid)
		{
			$query = "UPDATE dcim_power AS p
					SET p.load=?, p.status=?
					WHERE p.powerid=?
					LIMIT 1";
			
			if (!($stmt = $mysqli->prepare($query)))
				$errorMessage[] = "Prepare failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error.".";
			else
			{
				$stmt->bind_Param('dsi', $writeLoad, $writeStatus, $writePowerID);

				$goodCount = 0;
				$badCount = 0;
				for($i=0; $i<$inputCount; $i++)
				{
					$writePowerID = $powerIDs[$i];
					$writeLoad = $loads[$i];
					$writeStatus = $stati[$i];
					$badCount++;
						
					if (!$stmt->execute())//execute
						//failed (errorNo-error)
						$errorMessage[] = "Failed to execute panel power circuit update($writePowerID,$writeLoad,$writeStatus) (" . $stmt->errno . "-" . $stmt->error . ").";
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
							//$errorMessage[] = "Successfully updated power record (PowerID:$writePowerID Load:$writeLoad), but affected $affectedCount rows.";
						}
						
						//these moved out because i dont care if the values are the saem durring an audit - (probably just still 0)
						$resultMessage[] = "Successfully updated power circuit (PowerID:$writePowerID Load:$writeLoad Status:$writeStatus).";
						UpdateRecordEditUser("dcim_power","powerid",$writePowerID);//assume this is a full power audit so log it even if the data hasn't changed
						LogDBChange("dcim_power",$writePowerID,"U");
					}
				}
				$resultMessage[] = "Power Audit Panel - Updated: $inputCount ($goodCount Updates,$badCount Failures) Records.";
			}
		}
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
	
	function LogDBChange($table, $ukey, $action, $filter="")
	{
		//TODO this should be tested and probably adjusted to accomodate multiple updates where using filter instead of ukey and multiple records can be found (mostly for deleing linking recrods like powerloc deletion)
		// ^ also worth noting that thst there is no front end way to link double cross link power records so not super important
		global $mysqli;
		global $userID;
		global $errorMessage;
		global $resultMessage;
		
		$keyFieldName = GetKeyField($table);
		$logTable = GetLogTable($table);
		
		//run filter and re run with keys
		if(strlen($filter)>0)
		{
			$keys = GetKeysFromFilter($table,$filter,$keyFieldName);
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
			$query2 = "UPDATE tmptable_1 SET $logKeyField = NULL, logtype='$action', qauser=-1, qadate='';";
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
		}
	}
	
	function QARecord($table, $ukey, $keyField="", $liveRecord=true)
	{
		global $mysqli;
		global $userID;
		global $errorMessage;
		global $resultMessage;
		
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
			
			if (!$stmt->execute())//execute 
				//failed (errorNo-error)
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
				{
					$errorMessage[] = "QARecord($table, $ukey, $keyField, $liveRecord) Success, but affected $affectedCount rows.";
				}
			}	
		}	
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
				if(UserHasBadgeDeletePermission())
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
	
	function ProcessCircuitAction($action)
	{
		global $mysqli;
		global $userID;
		global $errorMessage;
		global $resultMessage;
		
		$add = $action==="Circuit_Add";
		$delete = $action==="Circuit_Delete";
		
		$powerID = GetInput("powerid");
		$panel = GetInput("panel");
		$circuit= GetInput("circuit");
		$volts = GetInput("volts");
		$amps = GetInput("amps");
		$status = GetInput("status");
		$load = GetInput("load");
		$locationID = GetInput("locationid");
		
		if(!isset($status) || strlen($status)==0)
			$status = "D";
		
		$totalAffectedCount = 0;
		$valid = true;
		
		if(!$add)
		{
			//if edit/delete then validate powerID, otherwise a new one will be generated on insert
			if($valid)$valid = ValidGenericID($powerID, "Power ID");
		}
		if($valid)$valid = ValidPowerPanel($panel);
		if($valid)$valid = ValidPowerCircuit($circuit);
		if($valid)$valid = ValidPowerVolts($volts);
		if($valid)$valid = ValidPowerAmps($amps);
		if($valid)$valid = ValidPowerStatus($status);
		if($valid)$valid = ValidPowerLoad($load);
		
		//DB CHECKS
		//check for location in table
		if($add && $valid)$valid = ValidLocation($locationID,true);
		
		//should find on edit/delete
		if(!$add && $valid)$valid = ValidPowerRecord($powerID,true);
		
		//check for existing panel circuit combo
		if($add && $valid)
		{
			$valid = false;
			$passedDBChecks = false;
			//this could be optomised by filtering inner selects by panel and/or range of circuit
			$isDoubleCircuit = (int)$volts == 208; 
			$filter = "";
			if(!$isDoubleCircuit)
				$filter = "csr.panel=? AND csr.circuit=?";
			else 
				$filter = "csr.panel=? AND (csr.circuit=? OR csr.circuit=?)";
			
			$query = "SELECT * FROM (
								SELECT powerid,panel,circuit,volts,amps
								FROM dcim_power
							UNION 
								SELECT powerid,panel,IF(volts=208,circuit+2,NULL) AS cir,volts,amps
								FROM dcim_power HAVING NOT(cir IS NULL)
						) AS csr
					WHERE $filter";
		
			if (!($stmt = $mysqli->prepare($query)))
				$errorMessage[] = "Prepare 0 failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error.".";
			else
			{
				if(!$isDoubleCircuit)
					$stmt->bind_Param('ss', $panel, $circuit);
				else 
				{
					$secondCircuit = 2+(int)$circuit;
					$stmt->bind_Param('sss', $panel, $circuit, $secondCircuit);
				}	
				if (!$stmt->execute())//execute 
					//failed (errorNo-error)
					$errorMessage[] = "Failed to execute power circuit locate verification (" . $stmt->errno . "-" . $stmt->error . ").";
				else
				{
					$stmt->store_result();
					$count = $stmt->num_rows;
					
					if($count==0)
						$passedDBChecks = true;
					else
					{
						$stmt->bind_result($k, $p, $c, $v, $a);
						$stmt->fetch();
						
						$errorMessage[] = "Existing panel Circuit found (Panel:$p, Circuit#$c) ID#$k. Cannot create duplicate.";
					}
				}
			}
			$valid=$passedDBChecks;
		}
		
		//push to DB
		if($valid)
		{
			if($add)
			{
				$query = "INSERT INTO dcim_power
					(panel,circuit,volts,amps,status,`load`,edituser,editdate) 
					VALUES(?,?,?,?,?,?,?,CURRENT_TIMESTAMP)";
					
				if (!($stmt = $mysqli->prepare($query)))
					$errorMessage[] = "Prepare failed: ($action-1) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{//					   pcvaslu
					$stmt->bind_Param('ssssssi', $panel, $circuit, $volts, $amps, $status, $load, $userID);
					
					if (!$stmt->execute())//execute 
						//failed (errorNo-error)
						$errorMessage[] = "Failed to execute power circuit add (" . $stmt->errno . "-" . $stmt->error . ").";
					else 
					{
						$affectedCount = $stmt->affected_rows;
						$totalAffectedCount += $affectedCount;
						if($affectedCount==1)
						{
							LogDBChange("dcim_power",-1,"I","panel='$panel' AND circuit='$circuit'");
							$resultMessage[] = "Successfully added power circuit (Panel:".$panel." Circuit#".$circuit.").";
						}
						else
							$errorMessage[] = "Power circuit added successfully, but affected $affectedCount rows.";
						
						//look up inserted id
						$query = "SELECT powerid FROM dcim_power WHERE panel=? AND circuit=?";
						
						if (!($stmt = $mysqli->prepare($query)))
							$errorMessage[] = "Prepare failed: ($action-2) (" . $mysqli->errno . ") " . $mysqli->error;
						else 
						{
							$stmt->bind_Param('ss', $panel, $circuit);
							$stmt->execute();
							$stmt->store_result();
							$count = $stmt->num_rows;
						
							if($count==1)
							{
								//update input locationid
								$stmt->bind_result($powerID);
								$stmt->fetch();
								//$resultMessage[] = "Sucsessfully found inserted power record ID#$powerID - dbID#$dbPowerID. - search for ($panel, $circuit)";
								
								//sucsessfull Insert - insert circuit-location link record
								$query = "INSERT INTO dcim_powerloc
									(powerid,locationid,edituser,editdate) 
									VALUES(?,?,?,CURRENT_TIMESTAMP)";
									
								if (!($stmt = $mysqli->prepare($query)))
									$errorMessage[] = "Prepare failed: ($action-3) (" . $mysqli->errno . ") " . $mysqli->error;
								else
								{//					   plu
									$stmt->bind_Param('iii', $powerID, $locationID, $userID);
									
									if (!$stmt->execute())//execute 
										//failed (errorNo-error)
										$errorMessage[] = "Failed to execute power circuit location link add (" . $stmt->errno . "-" . $stmt->error . ").";
									else 
									{
										$affectedCount = $stmt->affected_rows;
										$totalAffectedCount += $affectedCount;
										
										if($affectedCount==1)
										{
											LogDBChange("dcim_powerloc",-1,"I","powerid=$powerID AND locationid=$locationID");
											$resultMessage[] = "Successfully added power circuit location link (powerID:".$powerID.",locationID:".$locationID.").";
										}
										else
											$errorMessage[] = "Power circuit location link added successfully, but affected $affectedCount rows.";
									}
								}
								$resultMessage[] = "$totalAffectedCount total records created.";
							}
							else 
							{
								$errorMessage[] = "Failed to locate inserted record. Power (if created) is not linked to Location.";
							}
						}
					}	
				}
			}
			else if($delete)
			{
				//delete where powerid=? in power and powerloc
				$query = "DELETE FROM  dcim_power
					WHERE powerid=? 
					LIMIT 1";
		
				if (!($stmt = $mysqli->prepare($query)))
					$errorMessage[] = "Prepare failed: ($action-1) (" . $mysqli->errno . ") " . $mysqli->error.".";
				else
				{
					$stmt->bind_Param('i', $powerID);	
					
					if (!$stmt->execute())//execute 
						//failed (errorNo-error)
						$errorMessage[] = "Failed to execute power circuit delete (" . $stmt->errno . "-" . $stmt->error . ").";
					else
					{
						$affectedCount = $stmt->affected_rows;
						$totalAffectedCount += $affectedCount;
						if($affectedCount==1)
						{
							LogDBChange("dcim_power",$powerID,"D");
							$resultMessage[] = "Successfully deleted power circuit (Panel:$panel Circuit#$circuit).";
							
							//delete link - dont limit to 1 because this 1 power record could be linked to multiple locations
							$query = "DELETE FROM  dcim_powerloc
								WHERE powerid=?";
					
							if (!($stmt = $mysqli->prepare($query)))
								$errorMessage[] = "Prepare failed: ($action-2) (" . $mysqli->errno . ") " . $mysqli->error.".";
							else
							{
								$stmt->bind_Param('i', $powerID);	
								
								if (!$stmt->execute())//execute 
									//failed (errorNo-error)
									$errorMessage[] = "Failed to execute power circuit link delete (" . $stmt->errno . "-" . $stmt->error . ").";
								else
								{
									$affectedCount = $stmt->affected_rows;
									$totalAffectedCount += $affectedCount;
									if($affectedCount==1)
									{
										//TODO this should be tested to make sure multiple are updated in the case where 1 power circuit is connected to multiple locations
										LogDBChange("dcim_powerloc",-1,"D","powerid='$powerID'");
										$resultMessage[] = "Successfully un linked power circuit from location(Panel:$panel Circuit#$circuit).";
										
									}
									else
									{
										$errorMessage[] = "Successfully unlinked, but affected $affectedCount rows.";
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
				$query = "UPDATE dcim_power AS p
					SET p.amps=?, p.status=?, p.load=? 
					WHERE p.powerid=? 
					LIMIT 1";
		
				if (!($stmt = $mysqli->prepare($query)))
					$errorMessage[] = "Prepare failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error.".";
				else
				{
					$stmt->bind_Param('sssi', $amps, $status, $load, $powerID);	
					
					if (!$stmt->execute())//execute 
						//failed (errorNo-error)
						$errorMessage[] = "Failed to execute power circuit edit (" . $stmt->errno . "-" . $stmt->error . ").";
					else
					{
						$affectedCount = $stmt->affected_rows;
						$totalAffectedCount += $affectedCount;
						if($affectedCount==1)
						{
							$resultMessage[] = "Successfully edited power circuit (Panel:$panel Circuit#$circuit). $totalAffectedCount records updated.";
							UpdateRecordEditUser("dcim_power","powerid",$powerID);//do this seperate to distinquish actual record changes from identical updates (updates without changes)
							LogDBChange("dcim_power",$powerID,"U");
						}
						else
						{
							$errorMessage[] = "Successfully edited power record, but affected $affectedCount rows.";
						}
					}
				}
			}
		}
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
						//$resultMessage[] = "Sucsessfully found inserted power record ID#$powerID - dbID#$dbPowerID. - search for ($panel, $circuit)";
						
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
				//check if customer (hno or cno) already exists before insertion
				$query = "SELECT hno,cno FROM dcim_customer WHERE hno=? OR cno=?";
				
				if (!($stmt = $mysqli->prepare($query)))
				{
					$errorMessage[] = "Prepare failed: ($action-1) (" . $mysqli->errno . ") " . $mysqli->error;
				}
				else
				{
					$stmt->bind_Param('ss', $hNo, $cNo);		
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
							$errorMessage[] = "Error: Existing Customer with H# found. input(H$hNo) found(H$hNoDB).";
						else if(strcmp($cNo,$cNoDB)==0)
							$errorMessage[] = "Error: Existing Customer with C# found. input(C$cNo) found(C$cNoDB).";
						else
							$errorMessage[] = "Unknown Error matching H# & C# input(H$hNo,C$cNo) found(H$hNoDB,C$cNoDB).";
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
				$query = "SELECT deviceid,hno,name,model,member FROM dcim_device WHERE deviceid=?";
				
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
						$stmt->bind_result($deviceID, $hNo, $deviceName, $deviceModel, $deviceMember);
						$stmt->fetch();
						$deviceFullName = GetDeviceFullName($deviceName, $deviceModel, $deviceMember, true);
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
				if(UserHasPortAddEditPermission())
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
								LogDBChange("dcim_deviceport",-1,"I","deviceid='$deviceID' AND pic='$pic' AND port='$portNo' AND type='$type' ORDER BY deviceportid DESC LIMIT 1");
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
				if(UserHasPortDeletePermission())
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
				if(UserHasPortAddEditPermission())
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
			if($type=="F")
			{
				$model = "Full Cab";	
			}
			else if($type=="C")
			{
				$model = "Cage";	
			}
			else if($type=="H")
			{
				//this should be a more complicated lookup - but so rarely changes could just as easily be edited manualy
				$bottomIDs = array(142,144,146,148,150,152,154,156,158,160,162,164,166,168); //fix this hard code with #100
				$bottomHalf = in_array($locationID,$bottomIDs);
				if($bottomHalf)
					$model = "Half Cab-Bottom";
				else
					$model = "Half Cab-Top";
			}
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
					(hno, locationid, name, member, note, unit, type, size, status, asset, serial, model, edituser, editdate) 
					VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,CURRENT_TIMESTAMP)";
					
				if (!($stmt = $mysqli->prepare($query)))
					$errorMessage[] = "Process Device Insert Prepare failed: ($action -2) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{//					   hlnmnutssasmu
					$stmt->bind_Param('iisisissssssi', $hNo, $locationID, $deviceName, $member, $notes, $unit, $type, $size, $status, $asset, $serial, $model, $userID);
					
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
						status = ?, 
						asset = ?,
						serial = ?,
						note = ?
						WHERE deviceid =? LIMIT 1 ";
				
				if (!($stmt = $mysqli->prepare($query)))
					$errorMessage[] = "Process Device Update Prepare failed: ($action-3) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{//					   lusasnd
					$stmt->bind_Param('iissssi', $locationID, $unit, $status, $asset, $serial, $notes, $deviceID);
					
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
		
		$query = "UPDATE $table SET edituser=$userID, editdate=CURRENT_TIMESTAMP, qauser=-1, qadate=''
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
					
					$oldPasInput = md5($oldPasInput);
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
		
		if($valid)$valid = ValidPassword($newpas1);
		if($valid)
		{
			$valid = strcmp($newpas1,$newpas2)==0;
			if(!$valid)
				$errorMessage[] = "New passwords dont match";
		}
		
		
		if($valid)//push new password to DB
		{
			$newFinalPas = md5($newpas1);
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
	
	function ShowActivityPage()
	{
		/*
		//list 15 most recent badge changes (note: 15 changes, not necisarily 15 badges)
		SELECT c.hno,c.name,bl.logtype,bl.name ,bl.badgeno ,bl.status ,bl.hand ,bl.returned ,bl.editdate,bl.edituser  
			FROM dcimlog_badge AS bl 
			LEFT JOIN dcimlog_customer AS c ON c.hno=bl.hno
			WHERE 1 ORDER BY editdate DESC LIMIT 15
			*/ 
	}
	
	function ShowLocationPage($input)
	{
		global $mysqli;
		global $deviceModels;
		global $pageSubTitle;
		global $focusSearch;
		
		$addLocation = (int)$input==-1;
		
		//UNITS desc from 42 to 1 in most cabs
		
		//top panel - location info / form / search fail 
		echo "<div class='panel'>\n";
		echo "<div class='panel-header'>Location</div>\n";
		echo "<div class='panel-body'>\n\n";
		
		//location page notes
		//
		// need/todo - draw whole cab with empty spaces and rowspans
		// server cell css - support server image - contains device details
		//
		//-click to go to device
		// devices at unit 0 are listed at top
		// -if device at this unit then asses it (check for size > 1U) - span as necisary
		// ---pre calc deviice  ranges
		//
		//
		
		$query = "SELECT s.siteid, s.name AS site, r.roomid, r.name, l.locationid, l.name, l.altname, l.type, l.units, l.orientation, l.xpos, l.ypos, l.width, l.depth, l.note, l.visible, l.edituser, l.editdate, l.qauser, l.qadate
			FROM dcim_location AS l
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE l.locationid=?";
		
		if (!($stmt = $mysqli->prepare($query))) 
		{
			//TODO hadnle errors better
			echo "ShowLocationPage Prepare 1 failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		$stmt->bind_Param('i', $input);
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($siteID, $site, $roomID, $room, $locationID, $location, $altName, $type, $units, $orientation, $xPos, $yPos, $width, $depth, $note, $visible, $editUserID, $editDate, $qaUserID, $qaDate);
		$locationFound = $stmt->num_rows==1;
		
		if($locationFound)
		{
			$stmt->fetch();
			$fullLocationName = FormatLocation($site, $room, $location);
			
			if(CustomFunctions::UserHasLocationPermission() || CustomFunctions::UserHasCircuitPermission())
			{
				echo "<script src='lib/js/customerEditScripts.js'></script>\n";	
			}
			
			$size = "$width x $depth feet";
			   
			echo "<table width=100%><tr>\n";
			echo "<td align='left'>\n";
			echo "<span class='customerName'>$fullLocationName</span>\n";
			echo "</td>\n";
			
			echo "<td align='right'>\n";
			//edit Locationbutton - not visible till in edit mode
			if(CustomFunctions::UserHasLocationPermission())
			{
				$jsSafeName = MakeJSSafeParam($location);
				$jsSafeAltName = MakeJSSafeParam($altName);
				$jsSafeNote = MakeJSSafeParam($note);
				//add, locationID, roomID, name, altName, type, units, orientation, x, y, width, depth, note)
				$params = "false, $locationID, $roomID, '$jsSafeName', '$jsSafeAltName', '$type', $units, '$orientation', $xPos, $yPos, $width, $depth, '$jsSafeNote'";

				?><button type='button' class='editButtons_hidden' onclick="EditLocation(<?php echo $params;?>);">Edit Location</button>
				<?php 
			}
			//editMode button
			if(CustomFunctions::UserHasLocationPermission() || CustomFunctions::UserHasCircuitPermission())
			{
				echo "<button type='button' onclick='ToggleEditMode()' style='display:inline;'>Edit Mode</button>\n";
			}
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			
			//details
			echo "<table>\n";
			echo "<tr>\n";
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Type:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo LocationType($type);
			echo "</td>\n";
			
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Units:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo $units;
			echo "</td>\n";
			
			echo "</tr>\n";
			echo "<tr>\n";
			
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Size:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo "$size";
			echo "</td>\n";
			
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Visible:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo FormatTechDetails($editUserID,$editDate,LocationVisible($visible), $qaUserID, $qaDate);
			echo "</td>\n";
			
			echo "</tr></table>\n";
		}
		else 
		{
			echo "Location not found ('".MakeHTMLSafe($input)."')<BR>\n"; 
		}
		
		if(UserHasWritePermission())
		{
			EditLocationForm();
		}
		
		echo "</div>\n";
		echo "</div>\n\n";
		
		if($locationFound)
		{
			echo "<BR>\n";
			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>Location Details</div>\n";
			echo "<div class='panel-body'>\n\n";
			
			$query = "SELECT s.name AS site, r.name AS room, l.locationid, l.name AS loc, 
					c.hno, c.name AS cust,
					d.deviceid, d.unit, d.name, d.member, d.size, d.type, d.status, d.note, d.asset, d.serial, d.model, d.edituser, d.editdate, d.qauser, d.qadate
				FROM dcim_location AS l
					LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
					LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
					LEFT JOIN dcim_device AS d ON d.locationid=l.locationid
					LEFT JOIN dcim_customer AS c ON d.hno=c.hno
				WHERE l.locationid=?
				ORDER BY site, room, loc, unit!=0, unit DESC, name, member";
			
			
			if (!($stmt = $mysqli->prepare($query))) 
			{
				//TODO hadnle errors better
				echo "ShowLocationPage Prepare 2 failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			}
			$stmt->bind_Param('i', $locationID);
			
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($site, $room, $locationID, $location, $hNo, $customer, $deviceID, $unit, $name, $member, $size, $type, $status, $notes, $asset, $serial, $model, $editUserID, $editDate, $qaUserID, $qaDate);
			$count = $stmt->num_rows;
			
			
			echo "<span class='tableTitle'>Devices</span>\n";
			echo "<BR>\n";
			
			if($count>0)
			{
				//TODO for searches this should have some link, either device or hno or both

				echo CreateDataTableHeader(array("Unit","Customer","Device","Size","Type","Status","Note"),true);
				
				//list result data
				$oddRow = false;
				while ($stmt->fetch()) 
				{
					$oddRow = !$oddRow;
					if($oddRow) $rowClass = "dataRowOne";
					else $rowClass = "dataRowTwo";
					
					//XXX probbable bug - if truncation happend in the middle of a &lt; tag
					$visibleNotes = TruncateWithSpanTitle(htmlspecialchars(MakeHTMLSafe($notes)));
					$deviceFullName = GetDeviceFullName($name, $model, $member, true);
					
					echo "<tr class='$rowClass'>";
					echo "<td class='data-table-cell'>$unit</td>";
					echo "<td class='data-table-cell'><a href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a></td>";
					echo "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>";
					echo "<td class='data-table-cell'>$size</td>";
					echo "<td class='data-table-cell'>".DeviceType($type)."</td>\n";
					echo "<td class='data-table-cell'>".DeviceStatus($status)."</td>\n";
					echo "<td class='data-table-cell'>$visibleNotes</td>";
					echo "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
					echo "</tr>";
				}
				echo "</table>";
			}
			else 
			{
				//TODO fix this to say something better - some locations dont have units or no devices
				echo "No Devices found at location.<BR>\n";
			}
			
			echo "<BR>\n";
			
			//list circuits
			ListPowerCircuits(true,$locationID);
			
			echo "</div>\n";
			echo "</div>\n";
			
			if(CustomFunctions::UserHasLocationPermission() || CustomFunctions::UserHasCircuitPermission())
			{
				//initialize page JS
				echo "<script type='text/javascript'>InitializeEditButton();</script>\n";
				if($addLocation)//populate and make visible
				{
					$pageSubTitle = "Add Location";
					echo "<script type='text/javascript'>EditLocation(true,'','','','','A');</script>\n";
					$focusSearch = false;
				}
				else 
				{
					$pageSubTitle = MakeHTMLSafe($fullLocationName);
				}
			}
		}//location found
		//return $count;
	}
	
	function EditLocationForm()
	{
		global $errorMessage;
		global $mysqli;
		
		//-default values - never seen
		$actionText = "Addy";
		$roomIDInput = 2;
		$nameInput = "location";
		$altNameInput = "alt Name input";
		$typeInput = "F";
		$unitsInput = 6;
		$xPosInput = 211.11;
		$yPosInput = 311.11;
		$orientationInput = "E";
		$widthInput = 411.11;
		$depthInput = 511.11;
		$noteInput = "notes input";
		
		$sizeInput = "654321-1";//drop this
		$statusInput = "A";//drop this
		$visibleInput = "F";

		//build location combo options
		$locationOptions = "";
		$query = "SELECT s.siteid, s.name, r.roomid, r.name, r.fullname
			FROM dcim_room AS r
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			ORDER BY s.name, r.name";
			
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "EditLocationForm() Prepare 1 failed: (" . $mysqli->errno . ") " . $mysqli->error;
		}
		else
		{
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($siteID, $site, $roomID, $room, $roomFullName);
			while ($stmt->fetch())
			{
				$fullRoomName = "$site $roomFullName";
				$selected = ($roomID==$roomIDInput ? "Selected" : "");
				$roomOptions .= "<option value='$roomID' $selected>$fullRoomName</option>\n";
			}
		}
		
		?>
		<div id='EditLocationMsg' class='hidden'></div>
		<div id='EditLocationEntry' class='hidden'>
		<BR>
		<table><tr><td>
		<form action="<?php echo $action;?>" method='post' id='EditLocationForm' onsubmit='return SaveLocation()' class=''>
			<fieldset>
				<legend id=EditLocationEntryLegend><b><?php echo $actionText;?> Location</b></legend>
				<table>
					<tr>
						<td colspan=1 align=right>Room:</td>
						<td align='left'>
							<select id=EditLocation_roomid name="roomid" tabindex=1>
								<?php echo $roomOptions; ?>
							</select>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Name:</td>
						<td align='left'>
							<input id=EditLocation_name type='text' tabindex=2 size=18 name='name' value='<?php echo $nameInput;?>' placeholder="10.01, G2" class='' >
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Alt&nbsp;Name(s):</td>
						<td align='left'>
							<input id=EditLocation_altname type='text' tabindex=3 size=18 name='altname' value='<?php echo $altNameInput;?>' placeholder="10.01.A, G3, Cloud" class='' >
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Type:</td>
						<td align='left'>
							<select id=EditLocation_type onchange='EditLocationTypeChanged()' name="type" tabindex=4>
								<option value="C" <?php if($typeInput==="C") echo "Selected"; ?>>Cage</option>
								<option value="F" <?php if($typeInput==="F") echo "Selected"; ?>>Full Cab</option>
								<option value="H" <?php if($typeInput==="H") echo "Selected"; ?>>Half Cab</option>
								<option value="M" <?php if($typeInput==="M") echo "Selected"; ?>>Misc</option>
								<option value="R" <?php if($typeInput==="R") echo "Selected"; ?>>Rack</option>
							</select>
							Units:<input id=EditLocation_units type='number' tabindex=5 size=6 name='size' min='0' max='50' step='1' value='<?php echo $unitsInput;?>' placeholder='42' class=''>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Orientation:</td>
						<td align='left'>
							<div class='inputToolTipContainer'>
								<select id=EditLocation_orientation onchange='' name="orientation" tabindex=6>
									<option value="N" <?php if($orientationInput==="N") echo "Selected"; ?>>Normal</option>
									<option value="E" <?php if($orientationInput==="E") echo "Selected"; ?>>Right</option>
									<option value="S" <?php if($orientationInput==="S") echo "Selected"; ?>>Backwards</option>
									<option value="W" <?php if($orientationInput==="W") echo "Selected"; ?>>Left</option>
								</select>
							<span class=inputTooltip>When looing at location in room, relative orientation to room.</span></div>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Left:</td>
						<td align='left'>
							<div class='inputToolTipContainer'>
								<input id=EditLocation_xpos type='number' tabindex=7 size=3 min='0' max='9999.99' step='0.01' name='xpos' value='<?php echo $xPosInput;?>' placeholder="12.34" class='' >
							<span class=inputTooltip>Distance from left room edge to back left corner of location (feet)</span></div>
							Foreward:
							<div class='inputToolTipContainer'>
								<input id=EditLocation_ypos type='number' tabindex=8 size=3 min='0' max='9999.99' step='0.01' name='ypos' value='<?php echo $yPosInput;?>' placeholder="12.34" class='' >
							<span class=inputTooltip>Distance from far room edge to back left corner of location (feet)</span></div>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Width:</td>
						<td align='left'>
							<div class='inputToolTipContainer'>
								<input id=EditLocation_width type='number' tabindex=9 size=3 min='0' max='9999.99' step='0.01' name='width' value='<?php echo $widthInput;?>' placeholder="12.34" class='' >
							<span class=inputTooltip>In feet</span></div>
							Depth:
							<div class='inputToolTipContainer'>
								<input id=EditLocation_depth type='number' tabindex=10 size=3 min='0' max='9999.99' step='0.01' name='depth' value='<?php echo $depthInput;?>' placeholder="12.34" class='' >
							<span class=inputTooltip>In feet</span></div>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Notes:</td>
						<td align='left'>
							<input id=EditLocation_note type='text' tabindex=11 size=50 name='notes' value='<?php echo $noteInput;?>' placeholder='Notes' class=''>
						</td>
					</tr>
					<tr>
						<td colspan=2><table width=100%><tr>
							<td align=left>
								<button id='EditLocation_deletebutton' type='button' onclick='DeleteLocation()' tabindex=14>Delete</button>
							</td>
							<td align='right'>
								<button type="button" onclick="HideAllEditForms()" tabindex=13>Cancel</button>
								<input type="submit" value="Save" tabindex=12>
							</td>
						</tr></table></td>
					</tr>
				</table>
				<input id=EditLocation_locationid type='hidden' name='locationid' value=-1>
				<input id=EditLocation_action type='hidden' name='action' value='null'>
				<input type="hidden" name="page_instance_id" value="<?php echo end($_SESSION['page_instance_ids']); ?>"/>
			</fieldset>
		</form>
		</td></tr></table></div>
		<?php
	}
	
	function ShowUsersPage($input)
	{
		global $userID;
		global $mysqli;
		global $pageSubTitle;
		
		//TODO this could be optimized to only select from dcim_user once (with bellow) - admins only, who cares
		if(UserHasAdminPermission())
		{
			$pageSubTitle = "Accounts"; 
			
			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>User List</div>\n";
			echo "<div class='panel-body'>\n\n";
			
			$query = "SELECT userid,username, name, initials, permission, lastactivity, edituser, editdate
				FROM dcim_user
				ORDER BY name";
			
			if (!($stmt = $mysqli->prepare($query))) 
			{
				$errorMessage[] = "ShowUserPage Prepare 2 failed: (" . $mysqli->errno . ") " . $mysqli->error;
			}
			
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($dbUserID, $dbUserName, $dbName, $dbInitials, $dbPermission, $dbLastActivity, $editUserID, $editDate);
			$count = $stmt->num_rows;
			
			echo "<span class='tableTitle'>Users</span>\n";
			//Add User button here?
			echo "<BR>\n";
			
			if($count>0)
			{
				echo CreateDataTableHeader(array("Name","User Name","Initials","Permission","Last Activity"),true);
				
				//list result data
				$oddRow = false;
				while ($stmt->fetch()) 
				{
					$oddRow = !$oddRow;
					if($oddRow) $rowClass = "dataRowOne";
					else $rowClass = "dataRowTwo";
					
					echo "<tr class='$rowClass'>";
					echo "<td class='data-table-cell'><a href='./?userid=$dbUserID'>".MakeHTMLSafe($dbName)."</a></td>";
					echo "<td class='data-table-cell'>".MakeHTMLSafe($dbUserName)."</td>";
					echo "<td class='data-table-cell'>$dbInitials</td>";
					echo "<td class='data-table-cell'>".DescribeUserPermissionLevel($dbPermission,true,true)."</td>";
					echo "<td class='data-table-cell'>$dbLastActivity</td>";
					echo "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate)."</td>";
					echo "</tr>";
				}
				echo "</table>";
			}
			else 
			{
				echo "No users found. Ummmm....<BR>\n";//shouldn't be possible
			}
		
			/*if(UserHasWritePermission())
			{	
				$action = "./?host=$input";
				EditDeviceForm($action);
			}*/
			echo "</div>\n";
			echo "</div>\n\n";
			echo "<BR>\n";
		}
		else
		{
			$pageSubTitle = "Account";
		}
		
/////////////////user details
		$maxPasswordLength = 15;//totly arbitraty, MD5 conversion so len doesnt really matter
		
		//top panel - cust info / form / search fail 
		echo "<div class='panel'>\n";
		echo "<div class='panel-header'>User Info</div>\n";
		echo "<div class='panel-body'>\n\n";
		
		$query = "SELECT userid, username, name, email, initials, note, permission, lastactivity, edituser, editdate
			FROM dcim_user 
			WHERE userid=?
			LIMIT 1";
		
		if (!($stmt = $mysqli->prepare($query))) 
		{
			$errorMessage[] = "ShowUserPage Prepare 1 failed: (" . $mysqli->errno . ") " . $mysqli->error;
		}
		$stmt->bind_Param('i', $input);
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($dbUserID, $dbUserName, $name, $email, $initials, $note, $dbPermission, $lastActivity, $editUserID, $editDate);
		$count = $stmt->num_rows;
		
		if($count==1)
		{
			//show User data
			$stmt->fetch();
			
			$editingSelf = $dbUserID==$userID;
			
			$validateOldPas = true;
			if(!$editingSelf && UserHasAdminPermission())
				$validateOldPas = false;
			
			echo "<span class='tableTitle'>".MakeHTMLSafe($name)." - ".MakeHTMLSafe($dbUserName)." - $initials - ID#$dbUserID</span><BR>\n";
			echo "Permmision: ".DescribeUserPermissionLevel($dbPermission,false,UserHasAdminPermission())."<BR>\n";
			echo "Last Activity: $lastActivity<BR>\n";
		
			/*
			echo "<BR>\n";
			echo "<BR>\n";
			echo "<BR>\n";
			echo "dbUserID='$dbUserID'<BR>\n";
			echo "userID='$userID'<BR>\n";
			echo "dbUserID==userID = '".($dbUserID==$userID)."'<BR>\n";
			echo "dbUserID===userID = '".($dbUserID===$userID)."'<BR>\n";
			echo "(int)dbUserID==(int)userID = '".((int)$dbUserID==(int)$userID)."'<BR>\n";
			*/
			
			if(UserHasAdminPermission() ||($editingSelf && UserHasWritePermission()))
			{
				//change pass form
				//if not validdate old pas (admin changing someomen elses pass) then hide option and pass some crap as old pas to skip JS tests
				?>
					<BR>
	<script src='lib/js/customerEditScripts.js'></script>
	<div id='EditUserPasswordMsg' class='hidden'></div>
	<div id='EditUserPasswordEntry' class=''><table><tr><td>
	<form action="./?userid=<?php echo $input;?>" method='post' id='EditUserPasswordForm' onsubmit='return SavePassword()' class=''>
		<fieldset>
			<legend id=EditUserPasswordEntryLegend><b>Change Password</b></legend>
			<table>
				<tr>
					<td align='right'><?php 
		if(!$validateOldPas) 
			echo "Your Admin Password"; 
		else 
			echo "Current Password:"; ?></td>
					<td width=1>
						<input id=EditUserPassword_oldpas type='password' name='oldpas' maxlength=<?php echo $maxPasswordLength;?> value='' placeholder='x+Z*k^2+secure' class=''>
					</td>
				</tr>
				<tr>
					<td align='right'>New Password:</td>
					<td width=1>
						<input id=EditUserPassword_newpas1 type='password' name='newpas1' maxlength=<?php echo $maxPasswordLength;?> value='' placeholder='x+Z*k^2+secure' class=''>
					</td>
				</tr>
				<tr>
					<td align='right'>New Password Again:</td>
					<td width=1>
						<input id=EditUserPassword_newpas2 type='password' name='newpas2' maxlength=<?php echo $maxPasswordLength;?> value='' placeholder='x+Z*k^2+secure' class=''>
					</td>
				</tr>
				<tr>
					<td colspan='2' align='right'>
						<input type="submit" value="Save">
					</td>
				</tr>
			</table>
			<?php 
		if(!$validateOldPas) 
			echo "<input id=EditUserPassword_username type='hidden' name='username' value='".MakeHTMLSafe($dbUserName)."'>";?>
			<input id=EditUserPassword_userid type='hidden' name='userpasid' value=<?php echo $dbUserID; ?>>
			<input id=EditUserPassword_action type='hidden' name='action' value='UserPassword_Update'>
			<input type="hidden" name="page_instance_id" value="<?php echo end($_SESSION['page_instance_ids']); ?>" />
		</fieldset>
	</form>
	</td></tr></table><BR></div>
				<?php 
			}
		}
		else 
		{
			echo "User not Found\n";
		}
		echo "</div>\n";
		echo "</div>\n\n";
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
	
	function ShowCustomerPage($hNo)
	{
		global $mysqli;
		global $pageSubTitle;
		global $focusSearch;
		
		//main customer record or new
		$addCust = $hNo==="-1";
		$pageSubTitle = "";
		$custCount = 0;
		
		//look for 1 customer
		if(!$addCust)
		{
			$query = "SELECT hno,cno,name,note,status,edituser,editdate,qauser,qadate 
				FROM dcim_customer 
				WHERE hno=?";
			
			if (!($stmt = $mysqli->prepare($query)))
			{
				//TODO handle errors better
				echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			}
			$search = $hNo;//make backup of search val incase no match is found problems can be traced
			$stmt->bind_Param('s', $hNo);		
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($hNo,$cNo,$customer,$note,$status,$editUserID,$editDate, $qaUserID, $qaDate);
			$custCount = $stmt->num_rows;
		}
		
		//top panel - cust info / form / search fail 
		echo "<div class='panel'>\n";
		echo "<div class='panel-header'>Customer</div>\n";
		echo "<div class='panel-body'>\n\n";
		
		if($custCount>=1 || ($addCust && UserHasWritePermission()))
		{
			//hNo is propper H# or -1 for add cust (or failed direct search)
			if(UserHasWritePermission())
			{
				echo "<script src='lib/js/customerEditScripts.js'></script>\n";	
			}
			
			if(!$addCust)
			{
				$stmt->fetch();
				
				//this is the customer page - build it...
				echo "<table width=100%>\n";
				echo "<tr>\n";
				echo "<td valign=top>\n";
				echo "<span class='customerName'>".MakeHTMLSafe($customer)."</span>\n";
				echo CustomFunctions::CreateInternalLink($hNo, $cNo, true);
			
				echo "</td>\n";
				echo "<td valign=top align=right>\n";
				//edit Customer button - not visible till in edit mode
				if(UserHasWritePermission())
				{
					echo "<div id='customerDecomHelpPopup' class='helpPopup'>".CustomerDecomHelpPopup()."</div>";
					echo "<span class='editButtons_hidden'><a class='helpLink' href='javascript:void(0)' onclick = \"CreatePopup('customerDecomHelpPopup');\">Decom Help</a></span>\n";
					
					$formAction = "./?host=$hNo";
					echo CreateQACell("dcim_customer", $hNo, $formAction,$editUserID, $editDate, $qaUserID, $qaDate,false);
					
					$jsSafeCustomer = MakeJSSafeParam($customer);
					$jsSafeNote = MakeJSSafeParam($note);
					$params = "false, '$hNo', '$cNo', '$jsSafeCustomer', '$jsSafeNote', '$status'";
					?><button type='button' class='editButtons_hidden' onclick="EditCustomer(<?php echo $params;?>);">Edit Customer</button>
					<?php 
				}
				//editMode button
				if(UserHasWritePermission())
				{
					echo "<button type='button' onclick='ToggleEditMode()' style='display:inline;'>Edit Mode</button>\n";
				}
				echo "</td>\n";
				echo "</tr>\n";
				echo "</table>\n";
				echo "<table width=100%>\n";
				echo "<tr>\n";
				//cust details
				echo "<td valign=top width=105>\n";
				echo "<span class='customerDetails'>\n";
				echo "H<span id='customerHNo' onclick=\"SelectIDRange('customerHNo')\">$hNo</span>\n";
				echo "<BR>\n";
				echo "C<span id='customerCNo' onclick=\"SelectIDRange('customerCNo')\">$cNo</span>\n";
				echo "<BR>\n";
				//cust status wrapped in span tag that shows tech date on hover
				echo FormatTechDetails($editUserID,$editDate,CustomerStatus($status), $qaUserID, $qaDate)."\n";
				echo "<BR>\n";
				echo "</span>\n";
				echo "</td>\n";
				//cust notes
				echo "<td valign=top align=left>\n";
				echo "<textarea rows=3 cols=95 readonly placeholder=\"\">".MakeHTMLSafe($note)."</textarea>";
				echo "</td>\n";
				echo "</tr>\n";
				echo "</table>\n";
			}
			
			if(UserHasWritePermission())
			{	
				EditCustomerForm($hNo);
			}
			//end panel and panel body
			echo "</div>\n";
			echo "</div>\n";
		}
		else //cust not found and/or no write access 
		{
			//not found and can't create new
			echo "Customer not Found (H#$hNo)<BR>";
		}
		
		//bottom data panel
		if($custCount==1)
		{
			echo "<BR>";
			
			//data for customer
			
			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>Customer Details</div>\n";
			
			echo "<div class='panel-body'>\n\n";
				
			//show devices linked to this hNo
			$deviceCount = ListDevices(false,$hNo);
			echo "<BR>\n";
			
			//badges
			$badgeCount = ListBadges(false, $hNo);
			echo "<BR>\n";
			
			//ports
			$portCount = ListActiveCustomerDeviceConnections($hNo);
			echo "<BR>\n";
			
			//VLANs
			$vlanCount = ListCustomerSubnets($hNo);
			echo "<BR>\n";
			
			//Power Circuits of devices
			$powerCircuitsCount = ListPowerCircuits(false,$hNo);
			
			//end search (or customer details) panel and panel body
		}
		echo "</div>\n";
		echo "</div>\n";

		if(UserHasWritePermission())
		{
			//initialize page JS
			echo "<script type='text/javascript'>InitializeEditButton();</script>\n";
			if($addCust)//populate and make visible
			{
				$pageSubTitle = "Add Customer";
				echo "<script type='text/javascript'>EditCustomer(true,'','','','','A');</script>\n";
				$focusSearch = false;
			}
			else 
			{
				$pageSubTitle = MakeHTMLSafe($customer);
			}
		}
	}
	
	function EditCustomerForm($hNo)
	{
		//edit/Add badge form
		//-default values - overwritten by js - never seen
		$hNoInput = "000000";
		$cNoInput = "000000";
		$nameInput = "Customer Name";
		$statusInput = "A";
		$notesInput = "notes";
		$actionText = "action";
		
		?>
		<div id='EditCustomerMsg' class='hidden'></div>
		<div id='EditCustomerEntry' class='hidden'>
		<BR><table><tr><td>
		<form action="./?host=<?php echo $hNo;?>" method='post' id='EditCustomerForm' onsubmit='return SaveCustomer()' class=''>
			<fieldset>
				<legend id=EditCustomerEntryLegend><b><?php echo $actionText;?> Customer</b></legend>
				<table>
					<tr>
						<td align='right' width=1>Hosting&nbsp;Account&nbsp;#:</td>
						<td align='left' width=1>
							<input id=EditCustomer_hno type='number' min=100000 max=999999 step=1 tabindex=1 name='hno' maxlength=6 size=6 value='<?php echo $hNoInput;?>' placeholder='000000' class='' readonly>
							Customer&nbsp;#:
							<input id=EditCustomer_cno type='number' min=100000 max=999999 step=1 tabindex=2 name='cno' maxlength=6 size=6 value='<?php echo $cNoInput;?>' placeholder='000000' class='' readonly>
						</td>
					</tr>
					<tr>
						<td align='right'>Customer Name:</td>
						<td width=1 align='left'>
							<input id=EditCustomer_name type='text' tabindex=3 name='name' size=70 value='<?php echo $nameInput;?>' placeholder='Customer Name' class=''>
							Status:
							<select id=EditCustomer_status name="status" tabindex=4>
								<option value="A" <?php if($statusInput==="A") echo "Selected"; ?>>Active</option>
								<option value="I" <?php if($statusInput==="I") echo "Selected"; ?>>Inactive</option>
							</select>
						</td>
					</tr>
					<tr>
						<td align='right' valign='top'>Notes:</td>
						<td width=1 align='left'>
							<!--  NOTE the form atrb is not supported in IE - F* IE-->
							<textarea id=EditCustomer_notes form='EditCustomerForm' tabindex=5 rows=3 cols=95 name="notes" placeholder="Customer Notes"><?php echo $notesInput;?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan='2' align='right'>
							<button id=EditCustomer_cancelbutton type="button" onclick="HideAllEditForms()" tabindex=7>Cancel</button>
							<input type="submit" value="Save" tabindex=6>
						</td>
					</tr>
				</table>
				<input id=EditCustomer_action type='hidden' name='action' value='null'>
				<input type="hidden" name="page_instance_id" value="<?php echo end($_SESSION['page_instance_ids']); ?>" />
			</fieldset>
		</form>
		</td></tr></table></div>
		<?php
	}
	
	function ShowDevicePage($input)
	{
		global $mysqli;
		global $userFullName;
		global $pageSubTitle;
		
		$pageSubTitle = "";
		
		//top panel 
		echo "<div class='panel'>\n";
		echo "<div class='panel-header'>Device</div>\n";
		echo "<div class='panel-body'>\n\n";
		
		//get device info
		$query = "SELECT d.deviceid, d.hno, d.name, d.member, d.type, d.model, d.unit, d.size, d.status, d.asset, d.serial, d.note, c.name, s.name, r.name, d.locationid, l.name, d.edituser, d.editdate, d.qauser, d.qadate 
			FROM dcim_device AS d
				LEFT JOIN dcim_customer AS c ON c.hno=d.hno
				LEFT JOIN dcim_location AS l ON d.locationid=l.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE deviceid=? 
			LIMIT 1";
		
		$deviceCount = 0;
		if (!($stmt = $mysqli->prepare($query)))
		{
			//TODO handle errors better
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		else 
		{
			$stmt->bind_Param('i', $input);		
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($deviceID, $hNo, $deviceName, $member, $type, $model, $unit, $size, $status, $asset, $serial, $notes, $customerName, $siteName, $room, $locationID, $locationName,$editUserID,$editDate, $qaUserID, $qaDate);
			$deviceCount = $stmt->num_rows;
		}
		
		if($deviceCount==1)
		{
			//hNo is propper H# or -1 for add cust (or failed direct search)
			if(UserHasWritePermission())
			{
				echo "<script src='lib/js/customerEditScripts.js'></script>\n";	
			}
			
			$stmt->fetch();
			
			$deviceInfo = GetDeviceFromModelName($model);
			
			$deviceFullName = GetDeviceFullName($deviceName, $model, $member, false);
			$deviceFullNameShort = GetDeviceFullName($deviceName, $model, $member, true);
			$pageSubTitle = "Device: ".MakeHTMLSafe($deviceFullName);
			$fullLocationName = FormatLocation($siteName, $room, $locationName);
			
			//customer   model  status
			//location - size   unit
			//asset
			//serial
			//note
			
			//this is the device page - build it...
			echo "<table width=100%>\n";
			echo "<tr>\n";
			echo "<td valign=top>\n";
			echo "<span class='customerName'>".MakeHTMLSafe($deviceFullName)."</span>\n";
			echo CustomFunctions::CreateInternalLink($deviceName, "", false);
			
			//if switch give link to chassis - all matching device name
			if($type=="S")
				echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='./?chassisname=$deviceName'>Chassis</a>\n";
		
			echo "</td>\n";
			echo "<td valign=top align=right>\n";
			//edit Customer button - not visible till in edit mode
			if(UserHasWritePermission())
			{
				$jsSafeDeviceFullName = MakeJSSafeParam($deviceFullName);
				$jsSafeDeviceName = MakeJSSafeParam($deviceName);
				$jsSafeNotes = MakeJSSafeParam($notes);
				$jsSafeSize = MakeJSSafeParam($size);
				$jsSafeAsset = MakeJSSafeParam($asset);
				$jsSafeSerial = MakeJSSafeParam($serial);
				//EditDevice(add, deviceID, hNo, name, fullname, type, size, locationID, unit, status, notes, model, member, asset, serial)
				echo "<button class='editButtons_hidden' onclick=\"EditDevice(false, $deviceID, '$hNo', '$jsSafeDeviceName', '$jsSafeDeviceFullName', '$type', '$jsSafeSize', '$locationID', '$unit', '$status', '$jsSafeNotes', '$model', '$member', '$jsSafeAsset', '$jsSafeSerial')\">Edit Device</button>\n";
			}
			//editMode button
			if(UserHasWritePermission())
			{
				echo "<button type='button' onclick='ToggleEditMode()' style='display:inline;'>Edit Mode</button>\n";
			}
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			
			//details
			echo "<table>\n";
			echo "<tr>\n";
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Customer:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo "<a href='./?host=$hNo'>".MakeHTMLSafe($customerName)."</a>";
			echo "</td>\n";
			
			echo "<td align=right class='customerDetails'>\n";
			if($deviceInfo->coloDevice)
				echo "<b>Colo:</b>";
			else
				echo "<b>Model:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			if($deviceInfo->coloDevice)
				echo MakeHTMLSafe(DeviceType($type));
			else
				echo MakeHTMLSafe($model);
			echo "</td>\n";
			
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Status:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails'>\n";
			echo FormatTechDetails($editUserID,$editDate,DeviceStatus($status), $qaUserID, $qaDate);
			echo "</td>\n";
			echo "</tr>\n";
			
			echo "<tr>\n";
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Location:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo "<a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a>";
			echo "</td>\n";
			
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Unit:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo $unit;
			echo "</td>\n";
			
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Size:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails'>\n";
			echo MakeHTMLSafe($size);
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			
			//asset serial and notes
			echo "<table width=100%>\n";
			echo "<tr><td valign=top width=105 class='customerDetails'>\n";
			if(!$deviceInfo->coloDevice)
			{
				echo  "<b>Asset:</b> ".MakeHTMLSafe($asset)."<BR>\n";
				echo "<b>Serial:</b> ".MakeHTMLSafe($serial)."<BR>\n";
			}
			echo "</td>\n";
			//device notes
			echo "<td valign=top align=left>\n";
			echo "<textarea rows=3 cols=95 readonly placeholder=''>".MakeHTMLSafe($notes)."</textarea>";
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			
			
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			//show device image with port overlays
			if($deviceInfo->showDeviceImage)
			{
				//process port data for switchview
				$startPort = $deviceInfo->startPort;
				$endPort = $startPort + $deviceInfo->portCount -1;
				
				$dbPortCount = 0;
				
				$query = "SELECT 
						dp.deviceid, dp.deviceportid, d.name, d.member, d.model, dp.pic, dp.port, dp.mac,
						sp.deviceid AS sid, sp.deviceportid AS spid, s.name AS sname, s.member AS smember, s.model AS smodel, sp.pic AS spic, sp.port AS sport,
						dp.type, dp.speed, dp.note, dp.status, pc.portconnectionid, dp.edituser, dp.editdate, dp.qauser, dp.qadate,
						CAST(GROUP_CONCAT(IF(pv.vlan<0,CONCAT('Temp-',ABS(pv.vlan)),pv.vlan) ORDER BY pv.vlaN<0, ABS(pv.vlaN) SEPARATOR ', ') AS CHAR) AS vlans 
					FROM dcim_device AS d
						LEFT JOIN dcim_deviceport AS dp ON d.deviceid=dp.deviceid
							LEFT JOIN (
										SELECT pcA.portconnectionid,pcA.childportid AS srcportid,pcA.parentportid AS destportid,pcA.patches,pcA.edituser,pcA.editdate,pcA.qauser,pcA.qadate FROM dcim_portconnection AS pcA
										UNION ALL
										SELECT pcB.portconnectionid,pcB.parentportid AS srcportid,pcB.childportid AS destportid,pcB.patches,pcB.edituser,pcB.editdate,pcB.qauser,pcB.qadate FROM dcim_portconnection AS pcB) 
								AS pc ON dp.deviceportid=pc.srcportid
						LEFT JOIN dcim_deviceport AS sp ON pc.destportid=sp.deviceportid
						LEFT JOIN dcim_device AS s ON sp.deviceid=s.deviceid
						LEFT JOIN dcim_portvlan AS pv ON dp.deviceportid=pv.deviceportid
					WHERE d.deviceid=?
					GROUP BY dp.deviceportid
					ORDER BY 3,4,6,7";
				
				if (!($stmt = $mysqli->prepare($query)))
				{
					//TODO handle errors better
					echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
				}
				else 
				{
					$stmt->bind_Param('i', $deviceID);
					$stmt->execute();
					$stmt->store_result();
					$stmt->bind_result($deviceID, $devicePortID, $deviceName, $member, $model, $pic, $port, $mac, 
									   $switchID, $switchPortID, $switchName, $switchMember, $switchModel, $switchPic, $switchPort, 
									   $type, $speed, $note, $status, $portConnectionID, $editUserID, $editDate, $qaUserID, $qaDate, $vlan);
					$dbPortCount = $stmt->num_rows;
				}
				
				if($dbPortCount>=1)
				{
					$topPortDivs = "";
					$bottomPortDivs = "";
				
					//these are not mutualy exclusive - both cen be on "bottom"
					//
					if(!$deviceInfo->doubleRow)
					{
						//single "bottom" row
						$oddOnTop = false;
						$evenOnTop = false;
					}
					else 
					{
						$oddOnTop  = ($startPort%2!=0);
						$evenOnTop = ($startPort%2==0);
					}
					//build switch port divs
					while ($stmt->fetch()) 
					{
						if($port >= $startPort && $port <= $endPort)//valid port in range
						{
							$popupText = "";
							
							//$truePortIndex is the true port no from 0 --EX: port 15 in on 13-24 is 2
							$truePortIndex = $port-$startPort;
							$setStyle = "";
							if($truePortIndex < $deviceInfo->portsPerSet * 1) //less than 12
								$setStyle = "switchPortsSet1";
							else if($truePortIndex < $deviceInfo->portsPerSet * 2) //less than 24
								$setStyle = "switchPortsSet2";
							else if($truePortIndex < $deviceInfo->portsPerSet * 3) //less than 36
								$setStyle = "switchPortsSet3";
							else if($truePortIndex < $deviceInfo->portsPerSet * 4) //less than 48
								$setStyle = "switchPortsSet4";
							
							
							if($status=="A")
								$statusStyle = "switchPortActive";
							else if($status=="D")
								$statusStyle = "switchPortEmpty";
							else if($status=="R")
								$statusStyle = "switchPortReserved";
							else
								$statusStyle = "switchPortBad";
							$statusDescrip = DevicePortStatus($status,true);
								
							//if odd and not odd on top
							$onBottom = (($port%2!=0 && !$oddOnTop) || ($port%2==0 && !$evenOnTop));
							$bottomStyle = "";
							if($onBottom)
								$bottomStyle = "switchBottomPort";
							
							//XXX this does not support mutiple vlans, probably need to write fresh SQL and code for that
							$portFullName = FormatPort($member, $model, $pic, $port, $type);
							$connectionText = "N/A";
							if($switchID!=null)
							{
								$switchPortFullName = FormatPort($switchMember, $switchModel, $switchPic, $switchPort, $type);
								$connectionText = "$switchName $switchPortFullName";
							}
							
							$tech = $userFullName[$editUserID] . ": ".$editDate;
							//$tech = FormatTechDetails($editUserID, $editDate,"", $qaUserID, $qaDate);
							$popupText = MakeHTMLSafe($deviceName)." $portFullName <BR>
							Connection:".MakeHTMLSafe($connectionText)."<BR>
							Status:$statusDescrip<BR>
							MAC:".MakeHTMLSafe($mac)." <BR>
							Speed:".MakeHTMLSafe($speed)." <BR>
							VLAN(s):$vlan <BR>
							Tech:$tech <BR>
							Notes:".MakeHTMLSafe($note);
							
							if(CustomFunctions::UserHasDevPermission())
							{
								//debug
								$popupText .= "<BR>
								<BR>parentDeviceID=$deviceID
								<BR>DeviePortID=$devicePortID
								<BR>PortConnectionID=$portConnectionID
								<BR>SwitchID=$switchID
								<BR>SwitchPortID=$switchPortID";
							}
								
							
							$jsSafeDeviceFullName = MakeJSSafeParam($deviceFullNameShort);
							$jsSafePortFullName = MakeJSSafeParam($portFullName);
							$jsSafeMac = MakeJSSafeParam($mac);
							$jsSafeSpeed = MakeJSSafeParam($speed);
							$jsSafeNote = MakeJSSafeParam($note);
							//EditDevicePort(event,add, devicePortID, deviceID, deviceName, portName, pic, port, type, status, speed, mac, note)
							$portEditJS = "EditDevicePort(event,false,".(UserHasPortAddEditPermission()?"true":"false").",$devicePortID,$deviceID,'$jsSafeDeviceFullName','$jsSafePortFullName',$pic,$port,'$type','$status','$jsSafeSpeed','$jsSafeMac','$jsSafeNote')";
							
							$portDiv = "<div onClick=\"$portEditJS\" class='$statusStyle $setStyle tooltip $bottomStyle'><span class='classic'>$popupText</span></div>\n";
							
							if($pic==0)
							{
								if($onBottom)
									$bottomPortDivs .= $portDiv;
								else
									$topPortDivs .= $portDiv;
							}
							else 
							{
								//stub for fiber ports or specialty cases (extra NIC ports, console, MGMT, whatever)
								//this should actualy filter above with the range filter
								//for now dont include them here (on the visual render)  - will still be listed below
								//will need thier own positioning CSS classes and probably image overlays - meh
							}
						}
					}//while
				}//found ports
				
				$portWidth = 28;
				$portHeight = 20;
				//dynamic
				$switchWidth = 948;
				$switchHeight = 97;
				$switchImage = "images/devices/ex4200_front.jpg";
				$topOffset = 25;
				$bottomOffset = 30;
				$set1Offset = 18;
				$set2Offset = 28;
				$set3Offset = 38;
				$set4Offset = 48;
				$marginRight = 2;
				
				//TODO quick hack for patch panels
				if($deviceInfo->name=="Full Cab" || $deviceInfo->name=="Half Cab-Top" || $deviceInfo->name=="Half Cab-Bottom")
				{
					$switchWidth = 950;
					$switchHeight = 91;
					$switchImage = "images/devices/patchpanel.jpg";
					$topOffset = 0;
					$bottomOffset = 38;
					$set1Offset = 60;
					$set2Offset = 94;
					$set3Offset = 129;
					$set4Offset = 166;
				
					if($startPort==13)
					{
						//bottom half cab - shift ports to right
						$set1Offset = 489;
						$set2Offset = 525;
					}
				}
				else if($deviceInfo->name=="EX3200 24p" || $deviceInfo->name=="EX4200 24p")
				{
					$switchImage = "images/devices/ex4200_24p_front.jpg";
				}
				else if($deviceInfo->name=="WS-X6348")
				{
					$switchImage = "images/devices/ws-x6348_front.jpg";
					$switchWidth = 950;
					$switchHeight = 105;
					$topOffset = 25;
					$bottomOffset = 33;
					$set1Offset = 57;
					$set2Offset = 78;
					$set3Offset = 92;
					$set4Offset = 102;
					$marginRight = 6;
				}
				else if($deviceInfo->name=="Catalyst 3550")
				{
					$switchImage = "images/devices/catalyst2950_front.jpg";
					$switchWidth = 950;
					$switchHeight = 89;
					$topOffset = 19;
					$bottomOffset = 25;
					$set1Offset = 59;
					$set2Offset = 77;
					$set3Offset = 97;
				}
			
			?>

<style type="text/css">
#switch {
	width:<?php echo $switchWidth;?>;
	height:<?php echo $switchHeight;?>;
	background-image:url('<?php echo $switchImage;?>'); 
	background-repeat: no-repeat;
}
.switchPortsSet1{
	left: <?php echo $set1Offset;?>px;
}
.switchPortsSet2{
	left: <?php echo $set2Offset;?>px;
}
.switchPortsSet3{
	left: <?php echo $set3Offset;?>px;
}
.switchPortsSet4{
	left: <?php echo $set4Offset;?>px;
}
.switchPortActive, .switchPortBad,
.switchPortEmpty, .switchPortReserved{
	background-repeat: no-repeat;
	top: <?php echo $topOffset;?>px; /*over written by bottom ports*/
	width:<?php echo $portWidth;?>px;
	height:<?php echo $portHeight;?>px;
	margin-right: <?php echo $marginRight;?>px;
	margin-bottom: 3px;
	position: relative;
	float: left;
}
.switchBottomPort{
	top:<?php echo $bottomOffset;?>px;
}
</style>
<?php
				//switch div
				echo "<div id='switch'>\n";
				echo "	<table class='switchTable' width=100%><tr><td class='switchTableCell'>\n";
				if($deviceInfo->doubleRow)
				{
					echo $topPortDivs;
					echo "</td></tr><tr><td>\n";
				}
				echo $bottomPortDivs;
				echo "	</td></tr></table>\n";
				echo "</div>\n";
			}//show device
			
			if(UserHasWritePermission())
			{	
				$action = "./?deviceid=$input";
				EditDeviceForm($action);
			}
			
			//end top panel and panel body
			echo "</div>\n";
			echo "</div>\n";
			echo "<BR>\n";
			
			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>Device Details</div>\n";
			echo "<div class='panel-body'>\n\n";
			
			
			//all Ports
			ListDevicePorts($deviceID,$deviceFullNameShort);
		}
		else 
		{
			echo "Device Not Found";
		}//device found
		
		//end panel and panel body
		echo "</div>\n";
		echo "</div>\n";
		
		if(UserHasWritePermission())
		{
			//initialize page JS
			echo "<script type='text/javascript'>InitializeEditButton();</script>\n";
			//$pageSubTitle = $customer;
		}
	}
	
	function ShowChassisPage($chassisName)
	{
		global $pageSubTitle;
		$pageSubTitle = MakeHTMLSafe($chassisName);
		
		if(UserHasWritePermission())
		{
			echo "<script src='lib/js/customerEditScripts.js'></script>\n";	
		}
		
		echo "<div class='panel'>\n";
		echo "<div class='panel-header'>Chassis Details for $chassisName</div>\n";
		echo "<div class='panel-body'>\n\n";
		
		//all Ports
		ListDevicePorts($chassisName,"",true);
		
		if(UserHasWritePermission())
		{
			//initialize page JS
			echo "<script type='text/javascript'>InitializeEditButton();</script>\n";
			//$pageSubTitle = $customer;
		}
		
		//end panel and panel body
		echo "</div>\n";
		echo "</div>\n";
	}
	
	function ListBadges($search, $input)
	{
		//NOTE badges are not filtered by site as they are assumed to be cross site compatable
		global $mysqli;
		
		if($search)
		{
			$input = "%".$input."%";
			
			$query = "SELECT c.name AS customer, b.badgeid, b.hno, b.name, b.badgeno, b.status, b.issue, b.hand, b.returned, b.edituser, b.editDate, b.qauser, b.qadate
			FROM dcim_badge AS b
				LEFT JOIN dcim_customer AS c ON c.hno=b.hno 
			WHERE b.name LIKE ? OR b.badgeno LIKE ?
			ORDER BY b.name";
			
			if (!($stmt = $mysqli->prepare($query)))
			{
				//TODO hadnle errors better
				echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			}
			$stmt->bind_Param('ss', $input, $input);
		}
		else
		{
			//location search for locations to append to badge visit info - this is outdated and not used any more anyways
			//TODO delete this since its not needed anymore though this information might still be usefull in the future
			$locQuery = "SELECT s.name AS site, r.name AS room, l.name 
			FROM dcim_device AS d 
				LEFT JOIN dcim_location AS l ON d.locationid=l.locationid 
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE d.hno=? AND d.type IN ('C','F','H') AND d.status='A'";
			
			if (!($stmt = $mysqli->prepare($locQuery)))
			{
				//TODO handle errors better
				echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
				return -1;
			}
			$stmt->bind_Param('s', $input);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($site, $room, $locationName);
			$locCount = $stmt->num_rows;
			
			$customerLocations = "";
			if($locCount>0)
			{
				$locationArray = array();
				while ($stmt->fetch())
					$locationArray[] = FormatLocation($site, $room, $locationName);
				$customerLocations  = implode(" & ",$locationArray);
			}
			
			
			//select empty as customer to keep return results to match search query
			$query = "SELECT c.name AS customer, b.badgeid, b.hno, b.name, b.badgeno, b.status, b.issue, b.hand, b.returned, b.edituser, b.editdate, b.qauser, b.qadate
			FROM dcim_badge AS b 
				LEFT JOIN dcim_customer AS c ON c.hno=b.hno
			WHERE b.hno = ?
			ORDER BY b.status, b.name";

			if (!($stmt = $mysqli->prepare($query)))
			{
				//TODO handle errors better
				echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
				return -1;
			}
				
			$stmt->bind_Param('s', $input);
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($customer, $badgeID, $hNo, $name, $badgeNo, $status, $issue, $hand, $returned, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
		
		//data title
		echo "<span class='tableTitle'>Badges</span>\n";
		if(!$search && UserHasWritePermission())
		{
			// add button to add new badge
			echo "<button class='editButtons_hidden' onclick=\"EditBadge(true,-1,'$input','','','P','".date("Y-m-d")."')\">Add New</button>\n";
			
			echo "<div id='badgeHelpPopup' class='helpPopup'>".BadgeHelpPopup()."</div>";
			echo "<span class=''><a class='helpLink' href='javascript:void(0)' onclick = \"CreatePopup('badgeHelpPopup');\">Help</a></span>\n";
		}
		echo "<BR>\n";
		
		if($count>0)
		{
			if($search)
				echo CreateDataTableHeader(array("Customer","Name&#x25B2;","Badge#","Status","Issue","Enroll"),false,false);
			else
				echo CreateDataTableHeader(array(		   "Name&#x25B2;","Badge#","Status","Issue","Enroll"),true,UserHasWritePermission());
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				echo "<tr class='$rowClass'>";
				if($search == true)
				{
					echo "<td class='data-table-cell'>"."<A href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a>"."</td>\n";
					echo "<td class='data-table-cell'>".MakeHTMLSafe($name)."</td>\n";
				}
				else 
				{
					//visit info for clipboard - time will be appended in javascript function
					$visitDescription = " - ".MakeHTMLSafe($customerLocations)." - H$hNo - ".MakeHTMLSafe($customer)." - ".MakeHTMLSafe($name)." - Badge";
					echo "<td class='data-table-cell' ondblclick='CopyBadgeToClipboard(\"$visitDescription\")'>".MakeHTMLSafe($name)."</td>\n";
				}
				echo "<td class='data-table-cell'>".MakeHTMLSafe($badgeNo)."</td>\n";
				echo "<td class='data-table-cell'>".BadgeStatus($status)."</td>\n";
				echo "<td class='data-table-cell'>$issue</td>\n";
				echo "<td class='data-table-cell'>$hand</td>\n";
				if(!$search)
				{
					//last tech date 
					echo "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate,"", $qaUserID, $qaDate)."</td>";
					
					//edit button cell
					if(UserHasWritePermission())
					{
						//edit button
						echo "<td class='data-table-cell-button editButtons_hidden'>\n";
						
						$jsSafeName = MakeJSSafeParam($name);
						$jsSafeBadgeNo = MakeJSSafeParam($badgeNo);
						$jsSafeIssue = MakeJSSafeParam($issue);
						$params = "false,$badgeID,'$hNo','$jsSafeName','$jsSafeBadgeNo','$status','$jsSafeIssue'";
						?><button onclick="EditBadge(<?php echo $params;?>)">Edit</button>
						<?php 
						echo "</td>\n";
						
						$formAction = "./?host=$hNo";
						echo CreateQACell("dcim_badge", $badgeID, $formAction, $editUserID, $editDate, $qaUserID, $qaDate);
					}
				}
				echo "</tr>\n";
			}
			echo "</table>\n";
		}
		else 
		{
		  echo "No Badges Found.<BR>\n";
		}
		
		if(UserHasWritePermission())
		{	
			EditBadgeForm($input);
		}
		
		return $count;
	}
	
	function CreateQACell($table, $recID, $formAction,$editUserID, $editDate, $qaUserID, $qaDate, $cell=true)
	{
		if($cell)
			$resultHTML = "<td class='data-table-cell-button editButtons_hidden' align=center>\n";
		else 
			$resultHTML = "<span class='editButtons_hidden'>QA: ";
			
		$qaStatus = DoesRecordRequireQA($editUserID, $editDate, $qaUserID, $qaDate);
		if($qaStatus==1)
		{
			$instanceID = end($_SESSION['page_instance_ids']);
			$resultHTML .= "<button onclick='QARecord(\"$table\",$recID,\"$formAction\",\"$instanceID\")'>QA</button>\n";
		}
		else if($qaStatus==0)
		{
			$resultHTML .= "<font color='green'>Good</font>";
		}
		else if($qaStatus==2)
		{
			$resultHTML .= "<font color='black'>Pending</font>";
		}
		if($cell)
			$resultHTML .= "</td>\n";
		else
			$resultHTML .= " </span>\n";
		return $resultHTML;
	}
	
	function EditBadgeForm($inputHNo)
	{
		//edit/Add badge form
		//-default values - never seen
		$actionText = "Addy";
		$hnoInput = "000000";
		$nameInput = "Jimmy";
		$badgeNoInput = "0123456789";
		$statusInput = "A";
		
		?>
		<div id='EditBadgeMsg' class='hidden'></div>
		<div id='EditBadgeEntry' class='hidden'>
		<BR>
		<table><tr><td>
		<form action="./?host=<?php echo $inputHNo;?>" method='post' id='EditBadgeForm' onsubmit='return SaveBadge()' class=''>
			<fieldset>
				<legend id=EditBadgeEntryLegend><b><?php echo $actionText;?> Badge</b></legend>
				<table>
					<tr>
						<td align='right'>Hosting&nbsp;Account&nbsp;#:</td>
						<td width=1>
							<input id=EditBadge_hno type='text' name='hno' maxlength=6 value='<?php echo $hnoInput;?>' placeholder='000000' class='' readonly>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Badge&nbsp;Holder's&nbsp;Name:</td>
						<td align='left'>
							<input id=EditBadge_name type='text' tabindex=1 name='name' value='<?php echo $nameInput;?>' placeholder='John Doe' class='' >
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Badge&nbsp;#:</td>
						<td align='left'>
							<input id=EditBadge_badgeno type='text' tabindex=2 name='badgeno' value='<?php echo $badgeNoInput;?>' placeholder='0000000' class='' >
						</td>
					</tr>
					<tr>
						<td colspan=1 align=right>Status:</td>
						<td align='left'>
							<select id=EditBadge_status name="status" tabindex=3>
								<option value="A" <?php if($statusInput==="A") echo "Selected"; ?>>Active</option>
								<option value="P" <?php if($statusInput==="P") echo "Selected"; ?>>Pending</option>
								<option value="D" <?php if($statusInput==="D") echo "Selected"; ?>>Disabled</option>
								<option value="E" <?php if($statusInput==="E") echo "Selected"; ?>>Enroll (Active)</option>
								<option value="R" <?php if($statusInput==="R") echo "Selected"; ?>>Returned</option>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan=1 align=right>Issue date:</td>
						<td align='left'>
							<input id=EditBadge_issue type='date' tabindex=4 name='issue' value='' class='' >
						</td>
					</tr>
					<tr>
						<td colspan='1' align='Left'>
							<button id=EditBadge_deletebtn type="button" onclick="DeleteBadge()" tabindex=7>Delete</button>
						</td>
						<td colspan='1' align='right'>
							<button type="button" onclick="HideAllEditForms()" tabindex=6>Cancel</button>
							<input type="submit" value="Save" tabindex=5>
						</td>
					</tr>
				</table>
				<input id=EditBadge_badgeid type='hidden' name='badgeid' value=-1>
				<input id=EditBadge_paststatus type='hidden' name='paststatus' value='null'>
				<input id=EditBadge_action type='hidden' name='action' value='null'>
				<input type="hidden" name="page_instance_id" value="<?php echo end($_SESSION['page_instance_ids']); ?>" />
			</fieldset>
		</form>
		</td></tr></table></div>
		<?php  
	}
	
	function EditDevicePortForm($action)
	{
		//edit/Add Device Port form
		?>
		<div id='EditDevicePortMsg' class='hidden'></div>
		<div id='EditDevicePortEntry' class='hidden'>
		<BR>
		<table><tr><td>
		<form action="<?php echo $action;?>" method='post' id='EditDevicePortForm' onsubmit='return SaveDevicePort()' class=''>
			<fieldset>
				<legend id=EditDevicePortEntryLegend><b>Action Text</b></legend>
				<table>
					<tr>
						<td align='right'>Device:</td>
						<td width=1>
							<input id=EditDevicePort_devicename type='text' tabindex=1 value='' placeholder='DAS ##' class='' readonly>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Pic:</td>
						<td align='left'>
							<input id=EditDevicePort_pic type='number' tabindex=2 name='pic' value='0' min=0 max=99 placeholder='0' class='' style='width:50px' >
							Port:
							<input id=EditDevicePort_portno type='number' tabindex=3 name='portno' value='0' min=0 max=99 placeholder='0' class='' style='width:50px' >
						</td>
					</tr>
					<tr>
						<td colspan=1 align=right>Type:</td>
						<td align='left'>
							<select id=EditDevicePort_type name="type" tabindex=4>
								<option value="E">Ethernet</option>
								<option value="F">Fiber</option>
								<option value="O">Other</option>
							</select>
							Status:
							<select id=EditDevicePort_status name="status" tabindex=5>
								<option value="A">Active</option>
								<option value="D">Disabled</option>
								<option value="R">Reserved</option>
								<option value="B">Bad</option>
							</select>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Speed:</td>
						<td align='left'>
							<input id=EditDevicePort_speed type='text' tabindex=6 name='speed' value='' placeholder='AUTO, 100Mb, 1Gb' class='' >
						</td>
					</tr>
					<tr>
						<td align='right' width=1>MAC:</td>
						<td align='left'>
							<input id=EditDevicePort_mac type='text' tabindex=7 name='mac' value='' placeholder='ff:ff:ff:ff:ff:ff' class=''>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Note:</td>
						<td align='left'>
							<input id=EditDevicePort_note type='text' tabindex=8 name='note' value='' placeholder='' class=''>
						</td>
					</tr>
					<tr>
						<td colspan=2><table width=100%><tr>
							<td align='left'>
								<button id=EditDevicePort_deletebtn type="button" onclick="DeleteDevicePort()" tabindex=11>Delete</button>
							</td>
							<td align='right'>
								<button type="button" onclick="HideAllEditForms()" tabindex=10>Cancel</button>
								<input type="submit" value="Save" tabindex=9>
							</td>
						</tr></table></td>
					</tr>
				</table>
				<input id=EditDevicePort_deviceid type='hidden' name='deviceid' value=-1>
				<input id=EditDevicePort_deviceportid type='hidden' name='deviceportid' value=-1>
				<input id=EditDevicePort_action type='hidden' name='action' value='null'>
				<input type="hidden" name="page_instance_id" value="<?php echo end($_SESSION['page_instance_ids']); ?>" />
			</fieldset>
		</form>
		</td></tr></table></div>
		<?php  
	}
	
	function ListDevices($search, $input)
	{
		global $mysqli;
		global $deviceModels;
		
		$formAction = "./?host=$input";
			
		if($search)
		{
			$input = "%".$input."%";
			
			$query = "SELECT d.deviceid, s.name AS site, r.name AS room, c.hno, c.name AS cust, l.locationid, l.name as loc, d.unit, d.name, d.member, d.size, d.type, d.status, d.note, d.asset, d.serial, d.model, d.edituser, d.editdate, d.qauser, d.qadate
					FROM dcim_device AS d
						LEFT JOIN dcim_customer AS c ON c.hno=d.hno
						LEFT JOIN dcim_location AS l ON d.locationid=l.locationid
						LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
						LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
					WHERE d.name LIKE ? OR d.note LIKE ? OR CONCAT(s.name,' ',r.name,' ',l.name) LIKE ? OR CONCAT(s.name,' ',r.name,'.',l.name) LIKE ?
				UNION
					SELECT '', s.name, r.name, '', '', l.locationid, l.name, '', '', '', '', '', '', '', '', '', '', '', '', '', ''
						FROM dcim_location AS l
							LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
							LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
						WHERE l.visible='T' AND (CONCAT(s.name,' ',r.name,' ',l.name) LIKE ? OR CONCAT(s.name,' ',r.name,'.',l.name) LIKE ?)
				ORDER BY site, room, loc, length(name) DESC, unit DESC,name, member";
			
			if (!($stmt = $mysqli->prepare($query))) 
			{
				//TODO hadnle errors better
				echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			}
			$stmt->bind_Param('ssssss', $input, $input, $input, $input, $input, $input);
			
			echo "<span class='tableTitle'>Locations and Devices</span>\n";
		}
		else
		{
			$query = "SELECT d.deviceid, s.name AS site, r.name AS room, d.hno, '', l.locationid, l.name AS loc, d.unit, d.name, d.member, d.size, d.type, d.status, d.note, d.asset, d.serial, d.model, d.edituser, d.editdate, d.qauser, d.qadate
			FROM dcim_device AS d
				LEFT JOIN dcim_location AS l ON d.locationid=l.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE d.hno=?
			ORDER BY status, site, room, loc, unit, name, member";
			
			if (!($stmt = $mysqli->prepare($query))) 
			{
				//TODO hadnle errors better
				echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			}
			$stmt->bind_Param('s', $input);
			
			echo "<span class='tableTitle'>Devices</span>\n";
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($deviceID, $site, $room, $hNo, $customer, $locationID, $location, $unit, $name, $member, $size, $type, $status, $notes, $asset, $serial, $model, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
		
		
			
		
		if(!$search && UserHasWritePermission())
		{
			// add button to add new Device
			//EditDevice(add, deviceID, hNo, name, fullname, type, size, locationID, unit, status, notes, model, member, asset, serial)
			echo "<button class='editButtons_hidden' onclick=\"EditDevice(true, -1, '$input', '$input-?', '$input-?', 'F', 'Full', -1, '0', 'A', '', '', '-1', '', '')\">Add New</button>\n";
		}
		echo "<BR>\n";
		
		if($count>0)
		{
			if($search)
				echo CreateDataTableHeader(array("Customer","Location&#x25B2;","Device"),false,false);
			else
				echo CreateDataTableHeader(array(		   "Location&#x25B2;","Device","Unit","Size","Type","Status","Notes"),true,UserHasWritePermission());
			
			//list result data
			$oddRow = false;
			$lastLocationID = -1;
			while ($stmt->fetch()) 
			{
				// if same loction and no device here this record - skip
				if($locationID==$lastLocationID && strlen($name)<=0)
					continue;
				
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				//TODO test this with complex HTML
				$visibleNotes = TruncateWithSpanTitle(MakeHTMLSafe(htmlspecialchars($notes)));
				$deviceFullName = GetDeviceFullName($name, $model, $member, true);
				$fullLocationName = FormatLocation($site, $room, $location);
				
				echo "<tr class='$rowClass'>";
				if($search)
					echo "<td class='data-table-cell'><a href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a></td>";
				echo "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>";
				echo "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>";
				if(!$search)
				{
					echo "<td class='data-table-cell'>$unit</td>";
					echo "<td class='data-table-cell'>".MakeHTMLSafe($size)."</td>";
					echo "<td class='data-table-cell'>".DeviceType($type)."</td>\n";
					echo "<td class='data-table-cell'>".DeviceStatus($status)."</td>\n";
					echo "<td class='data-table-cell'>$visibleNotes</td>";
					echo "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
				}
				if(!$search && UserHasWritePermission())
				{
					//edit device - link to form
					echo "<td class='data-table-cell-button editButtons_hidden'>";
					$jsSafeFullName = MakeJSSafeParam($deviceFullName);
					$jsSafeName = MakeJSSafeParam($name);
					$jsSafeNotes = MakeJSSafeParam($notes);
					$jsSafeSize = MakeJSSafeParam($size);
					$jsSafeAsset = MakeJSSafeParam($asset);
					$jsSafeSerial = MakeJSSafeParam($serial);
					//EditDevice(add, deviceID, hNo, name, fullname, type, size, locationID, unit, status, notes, model, member, asset, serial)
					echo "<button onclick=\"EditDevice(false, $deviceID, '$hNo', '$jsSafeName', '$jsSafeFullName', '$type', '$jsSafeSize', '$locationID', '$unit', '$status', '$jsSafeNotes', '$model', '$member', '$jsSafeAsset', '$jsSafeSerial')\">Edit</button>\n";
					echo "</td>\n";
					
					echo CreateQACell("dcim_device", $deviceID, $formAction, $editUserID, $editDate, $qaUserID, $qaDate);
				}
				echo "</tr>";
				$lastLocationID = $locationID;
			}
			echo "</table>";
		}
		else 
		{
		  echo "No Devices Found.<BR>\n";
		}
	
		if(UserHasWritePermission())
		{	
			EditDeviceForm($formAction);
		}
		return $count;
	}
	
	function EditDeviceForm($action)
	{
		global $mysqli;
		global $deviceModels;
		global $errorMessage;
		
		//edit/Add Device form
		//-default values - never seen
		$actionText = "Addy";
		$hnoInput = "654321";
		$nameInput = "654321-1";
		$statusInput = "A";
		$typeInput = "F";
		$locationInput = 12;
		$sizeInput = "Full";
		$unitInput = "0";
		$assetInput = "asset";
		$serialInput = "serial";
		$notesInput = "notes input";
		
		//build location combo options
		$locationOptions = "";
		$query = "SELECT s.name, l.locationid, s.siteid, r.name, l.name, l.type
			FROM dcim_location AS l
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE l.visible='T'
			ORDER BY s.name, r.name, l.name";
			
		if (!($stmt = $mysqli->prepare($query))) 
		{
			$errorMessage[] = "EditDeviceForm() Prepare 1 failed: (" . $mysqli->errno . ") " . $mysqli->error;
		}
		else
		{
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($site, $locationID, $siteID, $room, $location, $type);
			while ($stmt->fetch()) 
			{
				$fullLocationName = FormatLocation($site, $room, $location);
				$selected = ($locationID==$locationInput ? "Selected" : "");
				$locationOptions .= "<option value='$locationID' $selected>$fullLocationName</option>\n";
			}
		}
		
		?>
		<div id='EditDeviceMsg' class='hidden'></div>
		<div id='EditDeviceEntry' class='hidden'>
		<BR>
		<table><tr><td>
		<form action="<?php echo $action;?>" method='post' id='EditDeviceForm' onsubmit='return SaveDevice()' class=''>
			<fieldset>
				<legend id=EditDeviceEntryLegend><b><?php echo $actionText;?> Device</b></legend>
				<table>
					<tr>
						<td align='right'>Hosting&nbsp;Account&nbsp;#:</td>
						<td width=1>
							<input id=EditDevice_hno type='text' name='hno' maxlength=6 size=6 value='<?php echo $hnoInput;?>' placeholder='000000' class='' readonly>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Device&nbsp;Name:</td>
						<td align='left'>
							<div class='inputToolTipContainer'>
								<input id=EditDevice_name type='text' tabindex=1 size=10 name='devicename' value='<?php echo $nameInput;?>' placeholder='000000-1' class='' >
							<span class=inputTooltip>Make sure to double check the dash number</span></div>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Type:</td>
						<td align='left'>
							<select id=EditDevice_type onchange='EditDeviceTypeChanged(true)' name="type" tabindex=2>
								<option value="F" <?php if($typeInput==="F") echo "Selected"; ?>>Full Cab</option>
								<option value="H" <?php if($typeInput==="H") echo "Selected"; ?>>Half Cab</option>
								<option value="C" <?php if($typeInput==="C") echo "Selected"; ?>>Cage</option>
								<option value="S" <?php if($typeInput==="S") echo "Selected"; ?>>Switch</option>
							</select>
							Size:
							<input id=EditDevice_size type='text' tabindex=3 size=6 name='size' value='<?php echo $sizeInput;?>' placeholder='5x7, Full, 2U, Half' class=''>
						</td>
					</tr>
					<tr>
						<td colspan=1 align=right>Location:</td>
						<td align='left'>
							<select id=EditDevice_location name="locationid" tabindex=4>
								<?php echo $locationOptions; ?>
							</select>
							Unit:
							<input id=EditDevice_unit type='text' tabindex=5 size=3 name='unit' value='<?php echo $unitInput;?>' placeholder='0' class=''>
						</td>
					</tr>
					<tr>
						<td colspan=1 align=right>Status:</td>
						<td align='left'>
							<select id=EditDevice_status name="status" tabindex=6>
								<option value="A" <?php if($statusInput==="A") echo "Selected"; ?>>Active</option>
								<option value="I" <?php if($statusInput==="I") echo "Selected"; ?>>Inactive</option>
							</select>
						</td>
					</tr>
					<tr id='EditDevice_interalFields_row1'>
						<td align='right' width=1>Model:</td>
						<td align='left'>
							<select id=EditDevice_model name="model" tabindex=7>
								<option value='Unknown'>Unknown</option>
							<?php 
							//This should be a list of all switch (or other non colo device) models
							foreach($deviceModels as $model)
							{
								if($model->coloDevice==false)
								{
									echo "<option value='$model->name'>$model->name</option>\n";
								}
							}
							//also include unknown to prevent accedental defualts
							?>
							</select>
							Member:
							<select id=EditDevice_member name="member" tabindex=8>
							<?php 
							for ($member = 0; $member <= 9; $member++) {
								echo "<option value='$member'>$member</option>\n";
							}?>
							</select>
						</td>
					</tr>
					<tr id='EditDevice_interalFields_row2'>
						<td align='right' width=1>Asset:</td>
						<td align='left'>
							<input id=EditDevice_asset type='text' tabindex=9 size=50 name='asset' value='<?php echo $assetInput;?>' placeholder='000000' class=''>
						</td>
					</tr>
					<tr id='EditDevice_interalFields_row3'>
						<td align='right' width=1>Serial:</td>
						<td align='left'>
							<input id=EditDevice_serial type='text' tabindex=10 size=50 name='serial' value='<?php echo $serialInput;?>' placeholder='FFFFFFFFFFFFFFFF' class=''>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Notes:</td>
						<td align='left'>
							<input id=EditDevice_notes type='text' tabindex=11 size=50 name='notes' value='<?php echo $notesInput;?>' placeholder='Notes' class=''>
						</td>
					</tr>
					<tr>
						<td colspan='2' align='right'>
							<button type="button" onclick="HideAllEditForms()" tabindex=13>Cancel</button>
							<input type="submit" value="Save" tabindex=12>
						</td>
					</tr>
				</table>
				<input id=EditDevice_locationid type='hidden' name='locationid' value=-1>
				<input id=EditDevice_action type='hidden' name='action' value='null'>
				<input type="hidden" name="page_instance_id" value="<?php echo end($_SESSION['page_instance_ids']); ?>"/>
			</fieldset>
		</form>
		</td></tr></table></div>
		<?php
	}
	
	function ListSearchCustomers($input)
	{
		global $mysqli;
		
		$query = "SELECT hno, cno, name, note, status, edituser
		FROM dcim_customer
		WHERE CONCAT('H',hno) LIKE ? OR CONCAT('C',cno) LIKE ? OR name LIKE ? OR note LIKE ?
		ORDER BY name";
		
		$input = "%".$input."%";
		if (!($stmt = $mysqli->prepare($query))) 
		{
			//TODO handle errors better
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		
		$stmt->bind_Param('ssss', $input, $input, $input, $input);
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($hNo, $cNo, $name, $note, $status, $editUser);
		$count = $stmt->num_rows;
		
		echo "<span class='tableTitle'>Customers</span>\n";
		echo "<BR>\n";
		
		if($count>0)
		{
			echo CreateDataTableHeader(array("H#","C#","Name&#x25B2;","Status","Note"));
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$note = Truncate($note);
				echo "<tr class='$rowClass'>";
				echo "<td class='data-table-cell'>"."<A href='./?host=$hNo'>$hNo</a>"."</td>";
				echo "<td class='data-table-cell'>$cNo</td>";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($name)."</td>";
				echo "<td class='data-table-cell'>".CustomerStatus($status)."</td>";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($note)."</td>";
				echo "</tr>";
			}
			echo "</table>\n";
		}
		else 
		{
			echo "No Customers Found.<BR>\n";
		}
		return $count;
	}
	
	function ListLocationCustomers($roomID)
	{
		//show all customers/devices at given locations - IE all devices in room 5 sorted by location - from nav links 	
		global $mysqli;
		global $pageSubTitle;
		
		$showEmpty = true;///this was a test feature to hide empty locations
		
		//lookup site room and circuit info for headers
		$query = "SELECT s.siteid, s.name, r.roomid, r.name, r.fullname
			FROM dcim_room AS r
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE r.roomid=?";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			//TODO handle errors better
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		
		$stmt->bind_Param('i', $roomID);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($siteID, $site, $roomID, $room, $roomFullName);
		$count = $stmt->num_rows;
		
		if($count==1 && $stmt->fetch())
		{//sucsessfull lookup
			$panelDescription = "Locations & devices in $site $roomFullName";
			$pageSubTitle = "$site $room";
			$searchTitle = "$site $roomFullName Location(s)";
			
			if($showEmpty)
				$query = "SELECT s.name AS site, r.name, l.locationid, l.name, c.hNo, c.name AS customer, d.deviceid, d.size AS devicesize, d.name AS devicename, d.model, d.member
					FROM dcim_location AS l
						LEFT JOIN dcim_device AS d ON l.locationID = d.locationid AND d.status='A'
						LEFT JOIN dcim_customer AS c ON c.hno = d.hno
						LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
						LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
					WHERE r.roomid=?
						AND l.visible='T'
					ORDER BY r.name, l.name";
			else
				$query = "SELECT s.name AS site, r.name, l.locationid, l.name, c.hNo, c.name AS customer, d.deviceid, d.size AS devicesize, d.name AS devicename, d.model, d.member
				FROM dcim_location AS l, dcim_device AS d, dcim_customer AS c
					LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
					LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
				WHERE r.roomid=?
					AND d.locationid=l.locationid
					AND d.hno=c.hno
					AND d.status='A'
				ORDER BY r.name, l.name";
	
				
			if (!($stmt = $mysqli->prepare($query))) 
			{
				//TODO handle errors better
				echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			}
			
			$stmt->bind_Param('s', $roomID);
			
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($site, $room, $locationID, $location, $hNo, $customer, $deviceID, $size, $deviceName, $deviceModel, $deviceMember);
			$count = $stmt->num_rows;
			
			$panelDescription = $panelDescription . " ($count)";

			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>$panelDescription</div>\n";
			echo "<div class='panel-body'>\n\n";
			
			if($count>0)
			{
				//show results
				echo "<span class='tableTitle'>$searchTitle</span>\n";
				echo "<BR>";
				
				echo CreateDataTableHeader(array("Location","Customer","Device","Size"));
				
				//list result data
				$oddRow = false;
				while ($stmt->fetch()) 
				{
					$oddRow = !$oddRow;
					if($oddRow) $rowClass = "dataRowOne";
					else $rowClass = "dataRowTwo";
					
					$fullLocationName = FormatLocation($site, $room, $location);
					$deviceFullName = GetDeviceFullName($deviceName, $deviceModel, $deviceMember, true);
					
					echo "<tr class='$rowClass'>";
					echo "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>";
					if(strlen($customer) > 0)
						echo "<td class='data-table-cell'><a href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a></td>";
					else 
						echo "<td class='data-table-cell'>Empty</td>";
					echo "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>";
					echo "<td class='data-table-cell'>".MakeHTMLSafe($size)."</td>";
					echo "</tr>";
				}
				echo "</table>";
			}
			else 
			{
				echo "No Locations or devices found in $roomFullName.<BR>\n";
			}
		}//sucsessfull lookup
		else
		{
			$pageSubTitle = "RoomID#$roomID";
			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>RoomID#$roomID</div>\n";
			echo "<div class='panel-body'>\n\n";

			echo "Room($roomID) not found.<BR>\n";
		}
			
		//end panel divs
		echo "</div>\n";
		echo "</div>\n";
		return $count;
	}
	
	function ListPowerCircuits($locationPage, $key)
	{
		global $mysqli;
		
		$formAction = "./?host=$key";
		
		if($locationPage)
		{
			$query = "SELECT s.siteid, s.name AS site, r.roomid, r.name, l.locationid, l.name AS location, p.powerid, p.panel, p.circuit, p.volts, p.amps, p.status, p.load, p.edituser, p.editdate, p.qauser, p.qadate
			FROM dcim_location AS l
				INNER JOIN dcim_powerloc AS pl ON l.locationid=pl.locationid
				LEFT JOIN dcim_power AS p ON pl.powerid=p.powerid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE l.locationid=?
			GROUP BY p.panel, p.circuit
			ORDER BY p.status, r.name, l.name, ABS(p.panel),panel, ABS(p.circuit)";
		}
		else
		{
			$query = "SELECT s.siteid, s.name AS site, r.roomid, r.name, l.locationid, l.name AS location, p.powerid, p.panel, p.circuit, p.volts, p.amps, p.status, p.load, p.edituser, p.editdate, p.qauser, p.qadate
			FROM dcim_device AS d
				LEFT JOIN dcim_location AS l ON d.locationid=l.locationid
				INNER JOIN dcim_powerloc AS pl ON l.locationid=pl.locationid
				LEFT JOIN dcim_power AS p ON pl.powerid=p.powerid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE d.hno=?
			GROUP BY p.panel, p.circuit
			ORDER BY p.status, r.name, l.name, ABS(p.panel),panel, ABS(p.circuit)";
		}
		
		
		//TODO this should also distinguish colo power vs other device power that they dont actualy pay for - only realy applies to customers with non colo devices
		//TODO This should also check the device status is active and or show/filter that here	
		
		if (!($stmt = $mysqli->prepare($query))) 
		{
			//TODO handle errors better
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		
		$stmt->bind_Param('s', $key);
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($siteID,$site,$roomID,$room,$locationID, $location, $powerID, $panel, $circuit, $volts, $amps, $status, $load, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
	
		echo "<span class='tableTitle'>Power Circuits</span>\n";
		//Add button
		if($locationPage && CustomFunctions::UserHasCircuitPermission())
		{
			?><button class='editButtons_hidden' onclick="EditCircuit(true,-1, '', '', 120, 20, 'D', 0)">Add New</button>
			<?php 
		}
		echo "<BR>";
			
		if($count>0)
		{
			echo CreateDataTableHeader(array("Location","Panel","Circuit","Volts","Amps","Status","Load"),true,CustomFunctions::UserHasCircuitPermission());
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$fullLocationName = FormatLocation($site, $room, $location);
			
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				if($amps>0)
				{
					$percentLoad = round(100*$load/$amps,2);
					
					if($percentLoad>80)
						$percentLoad = " (<font color=red>$percentLoad%</font>)";
					else if($load==0)
						$percentLoad = "";
					else
						$percentLoad = " ($percentLoad%)";
				}
				else
					$percentLoad = "";
					
				$visibleCircuit = $circuit;
				if($volts==208)
					$visibleCircuit = Format208CircuitNumber($circuit);
					
				echo "<tr class='$rowClass'>";
				echo "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>";
				echo "<td class='data-table-cell'><a href='./?page=PowerAudit&pa_roomid=$roomID&pa_panel=$panel'>".MakeHTMLSafe(FormatPanelName($panel))."</a></td>";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($visibleCircuit)."</td>";
				echo "<td class='data-table-cell'>$volts</td>";
				echo "<td class='data-table-cell'>$amps</td>";
				echo "<td class='data-table-cell'>".PowerStatus($status)."</td>";
				echo "<td class='data-table-cell'>".$load."A$percentLoad</td>";
				echo "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
				if(CustomFunctions::UserHasCircuitPermission())
				{
					//edit button
					echo "<td class='data-table-cell-button editButtons_hidden'>\n";
					
					$jsSafePanel = MakeJSSafeParam($panel);
					$jsSafeCircuit = MakeJSSafeParam($circuit);
					$params = "false,$powerID, '$jsSafePanel', '$jsSafeCircuit', $volts, $amps, '$status', $load";
					?><button onclick="EditCircuit(<?php echo $params;?>)">Edit</button>
					<?php 
					echo "</td>\n";
					
					echo CreateQACell("dcim_power", $powerID, $formAction, $editUserID, $editDate, $qaUserID, $qaDate);
				}
				echo "</tr>";
			}
			echo "</table>";
		}
		else 
		{
			echo "No relevant power records found.<BR>\n";
		}  
	
		if(CustomFunctions::UserHasCircuitPermission())
		{
			if($locationPage)
			{
				$action = "./?locationid=$key";
				EditCircuitForm($action, $key, $locationPage);
			}
			else 
			{
				//cant add power from hree so location is irrelevant
				EditCircuitForm($formAction, -1, $locationPage);
			}
		}
		return $count;
	}
	
	function EditCircuitForm($action, $locationID=-1, $locationPage)
	{
		global $mysqli;
		
		//edit/Add Circuit form
		?>
		<div id='EditCircuitMsg' class='hidden'></div>
		<div id='EditCircuitEntry' class='hidden'>
		<BR>
		<table><tr><td>
		<form action="<?php echo $action;?>" method='post' id='EditCircuitForm' onsubmit='return SaveCircuit()' class=''>
			<fieldset>
				<legend id=EditCircuitEntryLegend><b>Init Circuit</b></legend>
				<table>
					<tr>
						<td align='right'>Panel:</td>
						<td>
							<input id='EditCircuit_panel' type='text' tabindex=1 size=7 name='panel' value='' placeholder='4-1, 12' class=''>
							Circuit:
							<div class='inputToolTipContainer'><input id='EditCircuit_circuit' type='number' tabindex=2 size=5 name='circuit' value='' placeholder='1' step=1 min=1 max=50>
							<span class=inputTooltip>If 208v this must be the lesser circuit</span></div>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Volts:</td>
						<td align='left'>
							<select id=EditCircuit_volts name="volts" tabindex=3>
								<option value='120'>120v</option>
								<option value='208'>208v</option>
							</select>
							Amps:
							<select id=EditCircuit_amps name="amps" tabindex=4>
								<option value='20'>20A</option>
								<option value='30'>30A</option>
							</select>
						</td>
					</tr>
					<tr>
						<td align='right' valign='top'>Active</td>
						<td width=1 align='left'>
							<input id=EditCircuit_status type='checkbox' tabindex=5 name='status' value='A' onclick='EditCircuit_StatusClicked()' class=''>
							Load:
							<input id=EditCircuit_load type='number' tabindex=6 name='load' size=5 placeholder='2.04' min=0 max=30 step=0.01 onchange='EditCircuit_LoadChanged()' class=''>
						</td>
					</tr>
					<tr>
						<td colspan=2><table width=100%><tr>
							<td align=left>
								<?php if($locationPage)echo "<button id='EditCircuit_deletebutton' type='button' onclick='DeleteCircuit()' tabindex=9>Delete</button>";?>
							</td>
							<td align='right'>
								<button type="button" onclick="HideAllEditForms()" tabindex=8>Cancel</button>
								<input type="submit" value="Save" tabindex=7>
							</td>
						</tr></table></td>
					</tr>
				</table>
				<input id=EditCircuit_powerid type='hidden' name='powerid' value='-2'>
				<input id=EditCircuit_action type='hidden' name='action' value='null'>
				<input id=EditCircuit_locationid type='hidden' name='locationid' value='<?php echo $locationID; ?>'>
				<input type="hidden" name="page_instance_id" value="<?php echo end($_SESSION['page_instance_ids']); ?>"/>
			</fieldset>
		</form>
		</td></tr></table></div>
		<?php
	}
	
	function ListCustomerSubnets($hNo)
	{
		global $mysqli;
		$formAction = "./?host=$hNo";
		
		//GROUP to by VLAN/SUBNET to show unique networks link to customer
		$query = "SELECT dp.deviceportid, v.vlanid, v.vlan, v.subnet, v.mask, v.first, v.last, v.gateway, v.note, v.edituser, v.editdate, v.qauser, v.qadate
			FROM
				(SELECT pv.vlan
						FROM dcim_device AS d
							LEFT JOIN dcim_deviceport AS dp ON d.deviceid=dp.deviceid AND NOT(dp.status='D')
							INNER JOIN dcim_portconnection AS pc ON dp.deviceportid=pc.childportid
							INNER JOIN dcim_deviceport AS sp ON pc.parentportid=sp.deviceportid
							INNER JOIN dcim_device AS s ON sp.deviceid=s.deviceid
							LEFT JOIN dcim_portvlan AS pv ON sp.deviceportid=pv.deviceportid
						WHERE d.hno=?
					UNION 
					SELECT pv.vlan
						FROM dcim_device AS d
							LEFT JOIN dcim_deviceport AS dp ON d.deviceid=dp.deviceid AND NOT(dp.status='D')
							INNER JOIN dcim_portconnection AS pc ON dp.deviceportid=pc.parentportid
							INNER JOIN dcim_deviceport AS sp ON pc.childportid=sp.deviceportid
							INNER JOIN dcim_device AS s ON sp.deviceid=s.deviceid
							LEFT JOIN dcim_portvlan AS pv ON dp.deviceportid=pv.deviceportid
						WHERE d.hno=?) AS csr
				LEFT JOIN dcim_vlan AS v ON v.vlan=csr.vlan
				INNER JOIN dcim_portvlan AS pv ON pv.vlan=v.vlan
				INNER JOIN dcim_deviceport AS dp ON dp.deviceportid=pv.deviceportid
			GROUP BY v.vlanid
			ORDER BY v.vlan";
		
		if (!($stmt = $mysqli->prepare($query))) 
		{
			//TODO handle errors better
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		
		$stmt->bind_Param('ss', $hNo, $hNo);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($devicePortID, $vlanID, $vlan, $subnet, $mask, $first, $last, $gateway, $note, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
		
		echo "<span class='tableTitle'>Subnets</span>\n";
		//Add button
		if(UserHasWritePermission())
		{
			//function EditSubnet(add, portID,vlan,subnet,mask,gateway,first,last,note)
			?><button class='editButtons_hidden' onclick="EditSubnet(true,-1,-1,'','','','','','','')">Add New</button>
			<?php 
		}
		echo "<BR>";
		if($count>0)
		{
			echo CreateDataTableHeader(array("VLAN","Subnet","Mask","First","Last","Gateway","Note"),true,UserHasWritePermission());
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				echo "<tr class='$rowClass'>";
				echo "<td class='data-table-cell'>".FormatVLAN($vlan)."</td>";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($subnet)."</td>";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($mask)."</td>";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($first)."</td>";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($last)."</td>";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($gateway)."</td>";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($note)."</td>";
				echo "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
				if(UserHasWritePermission())
				{
					//edit button
					echo "<td class='data-table-cell-button editButtons_hidden'>\n";
					
					$jsSafeVLAN = MakeJSSafeParam($vlan);
					$jsSafeSubnet = MakeJSSafeParam($subnet);
					$jsSafeMask = MakeJSSafeParam($mask);
					$jsSafeFirst = MakeJSSafeParam($first);
					$jsSafeLast = MakeJSSafeParam($last);
					$jsSafeGateway = MakeJSSafeParam($gateway);
					$jsSafeNote = MakeJSSafeParam($note);
					//function EditSubnet(add, portID,vlan,subnet,mask,gateway,first,last,note)
					$params = "false,$vlanID, $devicePortID, '$jsSafeVLAN', '$jsSafeSubnet', '$jsSafeMask', '$jsSafeGateway', '$jsSafeFirst', '$jsSafeLast', '$jsSafeNote'";
					?><button onclick="EditSubnet(<?php echo $params;?>)">Edit</button>
					<?php 
					echo "</td>\n";
					
					echo CreateQACell("dcim_vlan", $vlanID, $formAction, $editUserID, $editDate, $qaUserID, $qaDate);
				}
				echo "</tr>";
			}
			echo "</table>";
		}
		else 
		{
			echo "No VLAN subnet records found.<BR>\n";
		}
		
		if(UserHasWritePermission())
		{
			EditSubnetForm($formAction,$hNo);
		}
		
		return $count;
	}
	
	function EditSubnetForm($action, $hNo)
	{
		global $mysqli;
		
		
		//select all Active Switch ports for/linked to this customer
		//UNION all connected chilren
		//UNION all connected parents
		//then looking up those port ids to get a list of all switch ports connected to or belonging to this customer 
		$query = "SELECT d.deviceid, d.model, d.name, d.member, dp.deviceportid, dp.pic, dp.port, dp.type 
			FROM(
				SELECT dp.deviceportid AS portid
					FROM dcim_device d
						LEFT JOIN dcim_deviceport AS dp ON d.deviceid=dp.deviceid
					WHERE d.hno=? AND d.type='S' AND dp.status='A'
				UNION
				SELECT pc.childportid AS portid
					FROM dcim_device d
						LEFT JOIN dcim_deviceport AS dp ON d.deviceid=dp.deviceid
						INNER JOIN dcim_portconnection AS pc ON pc.parentportid=dp.deviceportid
					WHERE d.hno=? AND dp.status='A'
				UNION
				SELECT pc.parentportid AS portid
					FROM dcim_device d
						LEFT JOIN dcim_deviceport AS dp ON d.deviceid=dp.deviceid
						INNER JOIN dcim_portconnection AS pc ON pc.childportid=dp.deviceportid
					WHERE d.hno=? AND dp.status='A'
				) AS csr
				INNER JOIN dcim_deviceport AS dp ON csr.portid=dp.deviceportid
				INNER JOIN dcim_device AS d ON d.deviceid=dp.deviceid
			WHERE d.type='S' AND dp.status='A'
			ORDER BY d.name, d.member, dp.pic, dp.port";
		
		
		$portOptions = "";
			
		if (!($stmt = $mysqli->prepare($query))) 
		{
			//TODO handle this better - this runs further down the page - so the error is never seen
			$errorMessage[] = "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
		}
		else
		{
			//$stmt->bind_Param('s', $input);// no params
			
			$stmt->bind_Param('sss', $hNo, $hNo, $hNo);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($deviceID, $model, $deviceName, $member, $devicePortID, $pic, $port, $type);
			while ($stmt->fetch()) 
			{
				$deviceFullName = GetDeviceFullName($deviceName, $model, $member, true);
				$portFullName = FormatPort($member, $model, $pic, $port, $type);
				
				$portOptions .= "<option value=$devicePortID>".MakeHTMLSafe($deviceFullName.$portFullName)."</option>\n";
			}
		}
		
		//edit/Add Subnet form
		?>
		<div id='EditSubnetMsg' class='hidden'></div>
		<div id='EditSubnetEntry' class='hidden'>
		<BR>
		<table><tr><td>
		<form action="<?php echo $action;?>" method='post' id='EditSubnetForm' onsubmit='return SaveSubnet()' class=''>
			<fieldset>
				<legend id=EditSubnetEntryLegend><b>Init Subnet</b></legend>
				<table>
					<tr>
						<td align='right'>Port:</td>
						<td>
							<select id=EditSubnet_portid name="deviceportid" tabindex=1>
								<?php echo $portOptions; ?>
							</select>
						</td>
					</tr>
					<tr>
						<td align='right'>VLAN:</td>
						<td>
							<input id='EditSubnet_vlan' type='text' tabindex=2 name='vlan' value='' placeholder='###' size=3 class=''>
							 Leave blank to create a new temp VLAN
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Subnet:</td>
						<td align='left'>
							<div class='inputToolTipContainer'><input id='EditSubnet_subnet' type='text' tabindex=3 size=15 name='subnet' value='' placeholder='###.###.###.###/##' onkeyup='EditSubnet_SubnetChanged()' class=''>
							<span class=inputTooltip>Editing this will update the next IP fields</span></div>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Subnet Mask:</td>
						<td align='left'>
							<input id='EditSubnet_mask' type='text' tabindex=4 size=12 name='mask' value='' placeholder='###.###.###.###' class=''>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Gateway:</td>
						<td align='left'>
							<input id='EditSubnet_gateway' type='text' tabindex=5 size=12 name='gateway' value='' placeholder='###.###.###.###' class=''>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>First IP:</td>
						<td align='left'>
							<input id='EditSubnet_first' type='text' tabindex=6 size=12 name='first' value='' placeholder='###.###.###.###' class=''>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Last IP:</td>
						<td align='left'>
							<input id='EditSubnet_last' type='text' tabindex=7 size=12 name='last' value='' placeholder='###.###.###.###' class=''>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Note:</td>
						<td align='left'>
							<input id='EditSubnet_note' type='text' tabindex=8 size=15 name='note' value='' placeholder='' class=''>
						</td>
					</tr>
					<tr>
						<td colspan=2><table width=100%><tr>
							<td align=left>
								<button id='EditSubnet_deletebutton' type='button' onclick='DeleteSubnet()' tabindex=11>Delete</button>
							</td>
							<td align='right'>
								<button type="button" onclick="HideAllEditForms()" tabindex=10>Cancel</button>
								<input type="submit" value="Save" tabindex=9>
							</td>
						</tr></table></td>
					</tr>
				</table>
				<input id=EditSubnet_vlanid type='hidden' name='vlanid' value='-2'>
				<input id=EditSubnet_action type='hidden' name='action' value='null'>
				<input type="hidden" name="page_instance_id" value="<?php echo end($_SESSION['page_instance_ids']); ?>"/>
			</fieldset>
		</form>
		</td></tr></table></div>
		<?php
	}
	
	function EditConnectionForm($action, $hNo)
	{
		global $mysqli;
		global $deviceModels;
		
		//edit/Add Connection form
		//-default values - never seen
		$actionText = "Addy";
		$patchesInput = "patches input";
		
		//build list of devices combo options
		$query = "SELECT d.deviceid, d.hno, d.name, d.model, d.member
			FROM dcim_device AS d
			ORDER BY /*d.type='S' DESC,*/ d.name, d.member";
			
		if (!($stmt = $mysqli->prepare($query))) 
		{
			//TODO handle errors better
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($deviceID, $deviceHNo, $deviceName, $deviceModel, $deviceMember);
		$count = $stmt->num_rows;
		
		$childDeviceOptions = "";
		$parentDeviceOptions = "";
		
		$customerDeviceOptions = "";
		$customerAndInternalOptions = "";
		$allDeviceOptions = "";
		if($count>0)
		{
			$short = true;
			while ($stmt->fetch()) 
			{
				$fullName = GetDeviceFullName($deviceName, $deviceModel, $deviceMember, $short);
				if($hNo==$deviceHNo)
				{
					$customerDeviceOptions .= "<option value='$deviceID'>".MakeHTMLSafe($fullName)."</option>\n";
					$customerAndInternalOptions .= "<option value='$deviceID'>".MakeHTMLSafe($fullName)."</option>\n";
				}
				$allDeviceOptions .= "<option value='$deviceID'>".MakeHTMLSafe($fullName)."</option>\n";
				
				//TODO: Hadle this selection better - put 'local' devices at top and maybe a divider between them - maybe add a internal flag to customer record #77
				if($deviceHNo=='189165')//Internal
					$customerAndInternalOptions .= "<option value='$deviceID'>".MakeHTMLSafe($fullName)."</option>\n";
			}
			
			$childDeviceOptions = $customerDeviceOptions;
			$parentDeviceOptions = $customerAndInternalOptions;
			
			//if(CustomFunctions::UserHasDevPermission())
			{
				//dont limit to just this customers devices - show all devices as child and parent
				$childDeviceOptions = $allDeviceOptions;
				$parentDeviceOptions = $allDeviceOptions;
			}
		}
		
		?>
		<div id='EditConnectionMsg' class='hidden'></div>
		<div id='EditConnectionEntry' class='hidden'>
		<BR>
		<table><tr><td>
		<form action="<?php echo $action;?>" method='post' id='EditConnectionForm' onsubmit='return SaveConnection()' class=''>
			<fieldset>
				<legend id=EditConnectionEntryLegend><b><?php echo $actionText;?> Connection</b></legend>
				<table>
					<tr>
						<td align='right'>Child Device:</td>
						<td width=1>
							<select name='childdeviceid' id='EditConnection_childdeviceid' onchange='DeviceSelectChanged(true,-1)' tabindex=1 diabled>
								<?php echo $childDeviceOptions;?>
							</select>
						</td>
						<td>
							<select name='childportid' id='EditConnection_childportid' tabindex=2>
								<option value=1>Loading...</option>
							</select>
						</td>
					</tr>
					<tr>
						<td align='right'>Parent Device:</td>
						<td width=1>
							<select name='parentdeviceid' id='EditConnection_parentdeviceid' onchange='DeviceSelectChanged(false,-1)' tabindex=3>
								<?php echo $parentDeviceOptions;?>
							</select>
						</td>
						<td>
							<select name='parentportid' id='EditConnection_parentportid' tabindex=4>
								<option value=1>Loading...</option>
							</select>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Patches:</td>
						<td align='left' colspan='2'>
							<input id='EditConnection_patches' type='text' tabindex=5 size=50 name='patches' value='<?php echo $patchesInput;?>' placeholder='10.01/4 - G/13 - 10.24/1' class=''>
						</td>
					</tr>
					<tr id='EditConnection_updateportsrow'>
						<td align='right' width=1>Port Changes:</td>
						<td align='left' colspan='2'>
							<select name='updateports' id='EditConnection_updateports' tabindex=4>
								<option value=T SELECTED>Update/Delete VLANs</option>
								<option value=F>No change</option>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan='1' align='left'>
							<button id='EditConnection_deletebutton' type="button" onclick="DeleteConnection()" tabindex=8>Delete</button>
						</td>
						<td colspan='2' align='right'>
							<button type="button" onclick="HideAllEditForms()" tabindex=7>Cancel</button>
							<input type="submit" value="Save" tabindex=6>
						</td>
					</tr>
				</table>
				<input id=EditConnection_portconnectionid type='hidden' name='portconnectionid' value='-2'>
				<input id=EditConnection_action type='hidden' name='action' value='null'>
				<input type="hidden" name="page_instance_id" value="<?php echo end($_SESSION['page_instance_ids']); ?>"/>
			</fieldset>
		</form>
		</td></tr></table></div>
		<?php
	}
	
	function ListDevicePorts($deviceKeyInput, $deviceFullName, $onChassisPage=false)
	{
		global $mysqli;
		
		
		if($onChassisPage)
			$filter = "d.name=?";
		else
			$filter = "d.deviceid=?";
		
		
		//group concat by port to combine vlans
		$query = "SELECT
				dp.deviceid, dp.deviceportid, d.name, d.member, d.model, dp.pic, dp.port, dp.mac,
				sp.deviceid AS sid, sp.deviceportid AS spid, s.name AS sname, s.member AS smember, s.model AS smodel, sp.pic AS spic, sp.port AS sport,l.locationid, site.name,r.name,l.name,
				d.hno, dp.type, dp.speed, dp.note, dp.status, sc.hno AS switchhno, sc.name AS switchcust, dp.edituser, dp.editdate, dp.qauser, dp.qadate,
				CAST(GROUP_CONCAT(IF(pv.vlan<0,CONCAT('Temp-',ABS(pv.vlan)),pv.vlan) ORDER BY pv.vlaN<0, ABS(pv.vlaN) SEPARATOR ', ') AS CHAR) AS vlans
			FROM dcim_device AS d
				LEFT JOIN dcim_deviceport AS dp ON d.deviceid=dp.deviceid
				LEFT JOIN (
							SELECT pcA.portconnectionid,pcA.childportid AS srcportid,pcA.parentportid AS destportid,pcA.patches,pcA.edituser,pcA.editdate FROM dcim_portconnection AS pcA
							UNION ALL
							SELECT pcB.portconnectionid,pcB.parentportid AS srcportid,pcB.childportid AS destportid,pcB.patches,pcB.edituser,pcB.editdate FROM dcim_portconnection AS pcB) 
					AS pc ON dp.deviceportid=pc.srcportid
				LEFT JOIN dcim_deviceport AS sp ON pc.destportid=sp.deviceportid
				LEFT JOIN dcim_device AS s ON sp.deviceid=s.deviceid
				LEFT JOIN dcim_customer AS sc ON sc.hno=s.hno
				LEFT JOIN dcim_portvlan AS pv ON dp.deviceportid=pv.deviceportid
				LEFT JOIN dcim_location AS l ON s.locationid=l.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS site ON r.siteid=site.siteid
			WHERE $filter
			GROUP BY dp.deviceportid
			ORDER BY 3,4,6,7";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			//TODO handle errors better
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		
		//might filter differenctely baced on chassis page and filter
		$stmt->bind_Param('s', $deviceKeyInput);
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($deviceID, $devicePortID, $deviceName, $member, $model, $pic, $port, $mac, 
						   $switchID, $switchPortID, $switchName, $switchMember, $switchModel, $switchPic, $switchPort, $switchLocationID,$switchSite,$switchRoom,$switchLocationName, 
						   $hNo, $type, $speed, $note, $status, $switchHNo, $switchCust, $editUserID, $editDate, $qaUserID, $qaDate, $vlan);
		$portCount = $stmt->num_rows;
		
		
		echo "<table width=100%><tr><td align=left>";
		
		if($onChassisPage)
			echo "<span class='tableTitle'>$deviceKeyInput Ports</span>\n";
		else
			echo "<span class='tableTitle'>Device Ports</span>\n";

		//show active ports button
		echo "<span class='showAllToggleButton'><button id='showAllPortsButton' onclick='ToggleShowAllPorts()'>Show all</button></span>\n";
			
		//add port button
		if(!$onChassisPage && UserHasAdminPermission())
		{
			// add button to add new Port
			$jsSafeDeviceFullName = MakeJSSafeParam($deviceFullName);
			//EditDevicePort(event,add, devicePortID, deviceID, deviceName, portName, pic, port, type, status, speed, mac, note)
			//redundant check for admin priv, but left for code completion sake
			echo "<button class='editButtons_hidden' onclick=\"EditDevicePort(event,true,".(UserHasPortAddEditPermission()?"true":"false").",-1,$deviceKeyInput,'$jsSafeDeviceFullName','',0,0,'E','D','','','')\">Add New</button>\n";
		}
		
		//editMode button
		echo "</td><td align=right>";
		if(UserHasWritePermission())
		{
			if($onChassisPage)
			{
				$formAction = "./?chassisname=$deviceKeyInput";
				echo "<button type='button' onclick='ToggleEditMode()' style='display:inline;'>Edit Mode</button>\n";
			}
			else 
			{
				$formAction = "./?deviceid=$deviceKeyInput";
			}
		}
		echo "</td></tr></table>";
		
		if($portCount>0)
		{
			$tableWithAllData = "";
			$tableWithActiveData = "";

			$tableHeader = CreateDataTableHeader(array("Device","Port&#x25B2;","MAC","Connected Device","Port","Speed","Status","VLANs","Note"),true,UserHasWritePermission());
			
			$tableWithAllData = $tableHeader;
			$tableWithActiveData = $tableHeader;
			
			//list result data
			$lastDevicePortID = -1;
			$lastSwitchPortID = -1;
			$oddRow = false;
			$oddActiveRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$record = "";
				$recordHead = "<tr class='$rowClass'>";
				
				$activeRow = $status=="A";
				if($activeRow)
				{
					$oddActiveRow = !$oddActiveRow;
					if($oddActiveRow) $activeRowClass = "dataRowOne";
					else $activeRowClass = "dataRowTwo";
					$activeRecordHead = "<tr class='$activeRowClass'>";
				}
				
				$deviceFullName = GetDeviceFullName($deviceName, $model, $member, true);
				$switchFullName = GetDeviceFullName($switchName, $switchModel, $switchMember, true);
				$switchLocationFullName = FormatLocation($switchSite,$switchRoom,$switchLocationName);
				
				$portFullName = FormatPort($member, $model, $pic, $port, $type);
				$switchPortFullName = FormatPort($switchMember, $switchModel, $switchPic, $switchPort, $type);
				
				if(strlen($switchFullName) > 0)
					$switchDisplayName = "<a href='./?deviceid=$switchID'>".MakeHTMLSafe($switchFullName)."</a> (<a href='./?host=$switchHNo'>$switchCust</a> - <a href='./?locationid=$switchLocationID'>$switchLocationFullName</a>)";
				else
					$switchDisplayName = "";
				
				//table values
				if($onChassisPage)
					$record .= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>";
				else 
					$record .= "<td class='data-table-cell'>".MakeHTMLSafe($deviceFullName)."</td>";
					
				if(CustomFunctions::UserHasDevPermission())
					$record .= "<td class='data-table-cell'><span title='deviceportid=$devicePortID'>$portFullName</span></td>";
				else
					$record .= "<td class='data-table-cell'>$portFullName</td>";
				$record .= "<td class='data-table-cell'>".MakeHTMLSafe($mac)."</td>";
				$record .= "<td class='data-table-cell'>$switchDisplayName</td>";
				if(CustomFunctions::UserHasDevPermission())
					$record .= "<td class='data-table-cell'><span title='switchportid=$switchPortID'>$switchPortFullName</span></td>";
				else
					$record .= "<td class='data-table-cell'>$switchPortFullName</td>";
				$record .= "<td class='data-table-cell'>".MakeHTMLSafe($speed)."</td>";
				$record .= "<td class='data-table-cell'>".DevicePortStatus($status)."</td>";
				$record .= "<td class='data-table-cell'>$vlan</td>";
				$record .= "<td class='data-table-cell'>".MakeHTMLSafe($note)."</td>";
				$record .= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
				
				//edit button cell
				if(UserHasWritePermission())
				{
					//edit button
					$record .= "<td class='data-table-cell-button editButtons_hidden'>\n";
					$jsSafeDeviceFullName = MakeJSSafeParam($deviceFullName);
					$jsSafePortFullName = MakeJSSafeParam($portFullName);
					$jsSafeMac = MakeJSSafeParam($mac);
					$jsSafeSpeed = MakeJSSafeParam($speed);
					$jsSafeNote = MakeJSSafeParam($note);
					//EditDevicePort(event,add, devicePortID, deviceID, deviceName, portName, pic, port, type, status, speed, mac, note)
					$portEditJS = "EditDevicePort(event,false,".(UserHasPortAddEditPermission()?"true":"false").",$devicePortID,$deviceID,'$jsSafeDeviceFullName','$jsSafePortFullName',$pic,$port,'$type','$status','$jsSafeSpeed','$jsSafeMac','$jsSafeNote')";
					
					$record .= "<button onclick=\"$portEditJS\">Edit</button>\n";
					$record .= "</td>\n";
					
					$record .= CreateQACell("dcim_deviceport", $devicePortID, $formAction, $editUserID, $editDate, $qaUserID, $qaDate);
				}
				
				$record .= "</tr>";
				
				$tableWithAllData .= $recordHead.$record;
				if($activeRow)
				{
					$tableWithActiveData .= $activeRecordHead.$record;
				}
				
				$lastDevicePortID = $devicePortID;
				$lastSwitchPortID = $switchPortID;
			}
			
			$tableWithAllData .= "</table>";
			$tableWithActiveData .= "</table>";
			
			echo "<div id='allPortsTable'>$tableWithAllData</div>\n";
			echo "<div id='activePortsTable'>$tableWithActiveData</div>\n";
			
			//Initialize show all ports
			echo "<script type='text/javascript'>InitializeShowAllPortsButton();</script>\n";
			
			if(UserHasWritePermission())
			{	
				//TODO this could fail on chasis page if no ports are found... shouldn't be possible - deviceID will be null
				EditDevicePortForm($formAction);
			}
		}
		else
			echo "No device port info found<BR>";
		return $portCount;
	}
	
	function ListActiveCustomerDeviceConnections($hNo)
	{
		global $mysqli;
		$formAction = "./?host=$hNo";
		
		$query = "SELECT 
				dp.deviceid, dp.deviceportid, d.name, d.member, d.model, dp.pic, dp.port, dp.mac,
				sp.deviceid AS sid, sp.deviceportid AS spid, s.name AS sname, s.member AS smember, s.model AS smodel, sp.pic AS spic, sp.port AS sport,
				dp.type, dp.speed, dp.note, dp.status, pc.portconnectionid, pc.patches, pc.relationship, pc.edituser, pc.editdate, pc.qauser, pc.qadate,
				CAST(GROUP_CONCAT(IF(pv.vlan<0,CONCAT('Temp-',ABS(pv.vlan)),pv.vlan) ORDER BY pv.vlaN<0, ABS(pv.vlaN) SEPARATOR ', ') AS CHAR) AS vlans 
			FROM dcim_device AS d
				INNER JOIN dcim_deviceport AS dp ON d.deviceid=dp.deviceid
				INNER JOIN (
							SELECT pcA.portconnectionid,pcA.childportid AS childportid,pcA.parentportid AS parentportid,
									pcA.patches,pcA.edituser,pcA.editdate,pcA.qauser,pcA.qadate, 'Child' AS relationship 
								FROM dcim_portconnection AS pcA
							UNION ALL
							SELECT pcB.portconnectionid,pcB.parentportid AS childportid,pcB.childportid AS parentportid,
									pcB.patches,pcB.edituser,pcB.editdate,pcB.qauser,pcB.qadate, 'Parent' AS relationship 
								FROM dcim_portconnection AS pcB)
					AS pc ON dp.deviceportid=pc.childportid
				INNER JOIN dcim_deviceport AS sp ON pc.parentportid=sp.deviceportid
				INNER JOIN dcim_device AS s ON sp.deviceid=s.deviceid
				LEFT JOIN dcim_portvlan AS pv ON sp.deviceportid=pv.deviceportid
			WHERE d.hno=?
			GROUP BY pc.portconnectionid
			ORDER BY 3,4,6,7";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			//TODO handle errors better
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		
		$stmt->bind_Param('s', $hNo);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($deviceID, $devicePortID, $deviceName, $member, $model, $pic, $port, $mac, 
						   $switchID, $switchPortID, $switchName, $switchMember, $switchModel, $switchPic, $switchPort, 
						   $type, $speed, $note, $status, $portConnectionID, $patches, $relationship, $editUserID, $editDate, $qaUserID, $qaDate, $vlan);
		$count = $stmt->num_rows;
		
		
		echo "<span class='tableTitle'>Device Connections</span>\n";
		if(UserHasWritePermission())
		{
			// add button to add new Connection
			echo "<button class='editButtons_hidden' onclick=\"EditConnection(true,-1,$deviceID,-1,-1,-1,'','')\">Add New</button>\n";
		}
		echo "<BR>\n";
		
		if($count>0)
		{
			echo CreateDataTableHeader(array("Child Device","Port&#x25B2;","Parent Device","Port","VLAN","Patches"),true,UserHasWritePermission());
			
			//list result data
			$lastDevicePortID = -1;
			$lastSwitchPortID = -1;
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				echo "<tr class='$rowClass'>";
				
				if($devicePortID==$lastDevicePortID && $switchPortID==$lastSwitchPortID)//should only need to test 1 but what the hell
				{
					//TODO use fancier SQL to combine VLANS into a single string so i can remove this crapy duplicate row crap
					//same ports - additional VLAN
					echo "<td colspan=4 class='data-table-cell'>Additional VLAN</td>";
					echo "<td class='data-table-cell'>$vlan</td>";
					echo "<td colspan=3 class='data-table-cell'>-</td>";
					echo "<td colspan=1 class='data-table-cell-button editButtons_hidden'>-</td>";
				}
				else 
				{
				
					$portFullName = FormatPort($member, $model, $pic, $port, $type);
					$switchPortFullName = FormatPort($switchMember, $switchModel, $switchPic, $switchPort, $type);
					
					$deviceFullName = GetDeviceFullName($deviceName, $model, $member, true);
					$switchFullName = GetDeviceFullName($switchName, $switchModel, $switchMember, true);
					
					if($relationship=="Child")
					{
						echo "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>";
						if(CustomFunctions::UserHasDevPermission())
							echo "<td class='data-table-cell'><span title='switchportid=$devicePortID'>$portFullName</span></td>";
						else
							echo "<td class='data-table-cell'>$portFullName</td>";
						echo "<td class='data-table-cell'><a href='./?deviceid=$switchID'>".MakeHTMLSafe($switchFullName)."</a></td>";
						if(CustomFunctions::UserHasDevPermission())
							echo "<td class='data-table-cell'><span title='switchportid=$switchPortID'>$switchPortFullName</span></td>";
						else
							echo "<td class='data-table-cell'>$switchPortFullName</td>";
					}
					else 
					{
						echo "<td class='data-table-cell'><a href='./?deviceid=$switchID'>".MakeHTMLSafe($switchFullName)."</a></td>";
						if(CustomFunctions::UserHasDevPermission())
							echo "<td class='data-table-cell'><span title='switchportid=$switchPortID'>$switchPortFullName</span></td>";
						else
							echo "<td class='data-table-cell'>$switchPortFullName</td>";
						echo "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>";
						if(CustomFunctions::UserHasDevPermission())
							echo "<td class='data-table-cell'><span title='switchportid=$devicePortID'>$portFullName</span></td>";
						else
							echo "<td class='data-table-cell'>$portFullName</td>";
					}
					echo "<td class='data-table-cell'>$vlan</td>";
					echo "<td class='data-table-cell'>".MakeHTMLSafe($patches)."</td>";
					echo "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate,"", $qaUserID, $qaDate)."</td>";
					
					//edit button cell
					if(UserHasWritePermission())
					{
						//edit button
						echo "<td class='data-table-cell-button editButtons_hidden'>\n";
						
						$jsSafePatchs = MakeJSSafeParam($patches);
						$editDescription = "$deviceName $portFullName <-> $switchName $switchPortFullName";
						$params = "false,$portConnectionID,$deviceID,$devicePortID,$switchID,$switchPortID,'$jsSafePatchs','$editDescription'";
						?><button onclick="EditConnection(<?php echo $params;?>)">Edit</button>
						<?php 
						echo "</td>\n";
						
						echo CreateQACell("dcim_portconnection", $portConnectionID, $formAction, $editUserID, $editDate, $qaUserID, $qaDate);
					}
				}
				echo "</tr>";
				$lastDevicePortID = $devicePortID;
				$lastSwitchPortID = $switchPortID;
			}
			echo "</table>";
		}
		else
			echo "No device connections found<BR>";
	
		if(UserHasWritePermission())
		{	
			EditConnectionForm($formAction, $hNo);
		}
		return $count;
	}
	
	function PowerAuditPanel($pa_roomID,$pa_panel)
	{
		
		//This really should be using a panelID from a panel table but that not currently necisarry
		global $mysqli;
		global $pageSubTitle;
		
		//lookup site room and circuit info for headers
		$query = "SELECT s.siteid, s.name, r.roomid, r.name, r.fullname, p.panel
			FROM dcim_power AS p
				LEFT JOIN dcim_powerloc AS pl ON p.powerid=pl.powerid
				LEFT JOIN dcim_location AS l ON pl.locationid=l.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE r.roomid=? AND p.panel=?
			GROUP BY r.roomid, p.panel, p.circuit
			ORDER BY p.circuit
			LIMIT 1";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			//TODO handle errors better
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		
		$stmt->bind_Param('ss', $pa_roomID,$pa_panel);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($siteID, $site, $roomID, $room, $roomFullName, $panel);
		$count = $stmt->num_rows;
		
		if($count==1 && $stmt->fetch())
		{//sucsessfull lookup
			$fullPanelDescription = MakeHTMLSafe("$site $roomFullName Panel:".FormatPanelName($panel));
			$pageSubTitle = "Power Audit - ".MakeHTMLSafe("$site $room Panel:".FormatPanelName($panel));//short room name
			echo "<script src='lib/js/customerEditScripts.js'></script>\n";
			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>\n";
			echo "Circuits for $fullPanelDescription\n";
			echo "</div>\n";
			
			echo "<div class='panel-body'>\n\n";
			
			//select power data
			$query = "SELECT s.siteid, s.name, r.roomid, r.name, l.locationid, l.name AS loc, LEFT(c.name,25) AS cust, p.powerid, p.panel, p.circuit, p.volts, p.amps, p.status, p.load, p.edituser, p.editdate
				FROM dcim_power AS p
					LEFT JOIN dcim_powerloc AS pl ON p.powerid=pl.powerid
					LEFT JOIN dcim_location AS l ON pl.locationid=l.locationid
					LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
					LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
					LEFT JOIN dcim_device AS d ON l.locationid=d.locationid AND d.status='A'
					LEFT JOIN dcim_customer AS c ON d.hno=c.hno
				WHERE r.roomid=? AND p.panel=?
				GROUP BY r.roomid, p.panel, p.circuit
				ORDER BY p.circuit";
			
			if (!($stmt = $mysqli->prepare($query)))
			{
				//TODO handle errors better
				echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			}
			
			$stmt->bind_Param('ss', $pa_roomID,$pa_panel);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($siteID, $site, $roomID, $room, $locationID, $location, $cust, $powerID, $panel, $circuit, $volts, $amps, $status, $load, $editUserID, $editDate);
			$count = $stmt->num_rows;
				
			if($count>0)
			{
				//show results
				echo "<a href='javascript:;' onclick='PowerAuditPanel_ConfirmPageChange(\"./?page=PowerAudit\");'>Back to panel list</a><BR><BR>\n";
				echo "<span class='tableTitle'>Circuits for $fullPanelDescription</span><BR>\n";
				echo "<form action='./?page=PowerAudit' method='post' id='PowerAuditPanelForm' onsubmit='return SavePowerAuditPanel()' class=''>\n";
				echo "<table style='border-collapse: collapse'>\n";
				
				$stmt->fetch();
				
				//count from 1 to $numberOfCircuitsPerPanel pulling records out of cursor as necisary
				$numberOfCircuitsPerPanel = 42;
				$tableCircuitNo = 0;
				$prevWas208Left = false;
				$prevWas208Right = false;
				while($tableCircuitNo<$numberOfCircuitsPerPanel)//42 circuits per panel
				{
					//odd circutis are on the left 
					$tableCircuitNo++;
					$left = ($tableCircuitNo%2)!=0;
					
					//this will make 0&1 odd color and 2&3 not for every set of 4
					if($tableCircuitNo%4<=1) $cellClass = "powerAuditCellOne";
					else $cellClass = "powerAuditCellTwo";
					
					if($circuit<$tableCircuitNo)
						$stmt->fetch();
					
					$tabIndex = $left ? $circuit : $circuit+$numberOfCircuitsPerPanel;
					$fullLocationName = FormatLocation($site, $room, $location, false);
					if($amps>0)
					{
						$percentLoad = round(100*$load/$amps,2);
						if($percentLoad>80)
							$percentLoad = "<font color=red>$percentLoad</font>";
					}
					else
						$percentLoad = "?";
					
					if($left)
					{//start a new row
						echo "<tr>\n";
					}
					
					$hasData = ($circuit==$tableCircuitNo);
					
					if($hasData)
					{
						$rowSpan="";
						$displayCircuit = $circuit;
						if($volts==208)//208 volt circuits take up double
						{
							if($left)
								$prevWas208Left = true;
							else
								$prevWas208Right = true;
							$cellClass .= " powerAuditCellDouble";
							$rowSpan = " rowspan=2";
							$displayCircuit = Format208CircuitNumber($circuit);
						}
						
						echo "<td $rowSpan class='$cellClass'>\n";
						echo "	<table width=100%><tr>\n";
						echo "	<td><b>".MakeHTMLSafe(FormatPanelName($panel))." CKT ".MakeHTMLSafe($displayCircuit)."</b></td>\n";
						echo "	<td align=right>".MakeHTMLSafe($cust)."</td>\n";
						echo "	</tr></table><table width=100%><tr>\n";
						//echo "	$fullLocationName ($percentLoad%) ";
						echo "	<td><a href='javascript:;' onclick='PowerAuditPanel_ConfirmPageChange(\"./?locationid=$locationID\");'>".MakeHTMLSafe($fullLocationName)."</a></b>&nbsp;&nbsp;</td>\n";
						echo "	<td align=right>".$volts."V-".$amps."A-<b>".PowerOnOff($status)."</b>\n";
						$statusFieldID = "PowerAuditPanel_Circuit".$circuit."_status";
						$loadFieldID = "PowerAuditPanel_Circuit".$circuit."_load";
						$checked = ($status==="A") ? " checked" : "";
						echo "	<input id='$statusFieldID' type='checkbox' name='c".$circuit."status' value='A' onclick='PowerAuditCircuit_StatusClicked(\"$statusFieldID\",\"$loadFieldID\");' $checked>\n";
						echo "	<input id='$loadFieldID' type='number' name='c".$circuit."load' tabindex=$tabIndex size=5 placeholder='$load' min=0 max=$amps step=0.01 onchange='PowerAuditCircuit_LoadChanged(\"$loadFieldID\",\"$statusFieldID\");'>\n";
						echo "	<input id=PowerAuditPanel_Circuit".$circuit."_powerid type='hidden' name='c".$circuit."powerid' value='$powerID'>\n";
						echo "	</td></tr>\n";
						echo "	</table>\n";
						
						echo "</td>\n";
					}
					else 
					{
						if($left && $prevWas208Left)
							$prevWas208Left = false;
						else if(!$left && $prevWas208Right)
							$prevWas208Right = false;
						else
							echo "<td class='$cellClass powerAuditCellEmpty'>".MakeHTMLSafe(FormatPanelName($panel))." / ".MakeHTMLSafe($tableCircuitNo)." - EMPTY</td>\n";
					}
					
					if(!$left)
					{//end row
						echo "</tr>\n";
					}
				}
				echo "<tr><td colspan='2' align='center' style='padding-top: 8px;'><input type='submit' value='Save' tabindex='".($numberOfCircuitsPerPanel*2+1)."'></td></tr>\n";
				echo "<input id=PowerAuditPanel_action type='hidden' name='action' value='PowerAudit_PanelUpdate'>\n";
				echo "<input type='hidden' name='page_instance_id' value='".end($_SESSION['page_instance_ids'])."'>\n";
				echo "</table></form>\n";
			}
			else
				echo "No power circuits found at $fullPanelDescription<BR>\n";
		}//sucsessfull lookup
		else//panel/room combo not found
		{
			$pageSubTitle = "Power Audit - RoomID:$pa_roomID Panel:$pa_panel not found";
			echo "<script src='lib/js/customerEditScripts.js'></script>\n";
			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>\n";
			echo "Circuits for RoomID:$pa_roomID Panel:$pa_panel\n";
			echo "</div>\n";
		
			echo "<div class='panel-body'>\n\n";
			echo "No power circuits found for RoomID:$pa_roomID Panel:$pa_panel<BR>\n";
		}	
		echo "</div>\n";
		echo "</div>\n";
		return $count;
	}
	
	function PowerAuditPanelList()
	{
		global $pageSubTitle;
		global $mysqli;
		
		$pageSubTitle = "Power Audit - Panel List";
		
		$query = "SELECT s.siteid, s.name,r.roomid, r.name, p.panel
			FROM dcim_power AS p
				LEFT JOIN dcim_powerloc AS pl ON p.powerid=pl.powerid
				LEFT JOIN dcim_location AS l ON pl.locationid=l.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			GROUP BY r.roomid, p.panel
			ORDER BY s.name, r.name, p.panel";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			//TODO handle errors better
			echo "Prepare failed: PowerAuditPanelList() (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($siteID,$site,$roomID,$room, $panel);
		$count = $stmt->num_rows;
		
		
		echo "<div class='panel'>\n";
		echo "<div class='panel-header'>\n";
		echo "Power Panel list\n";
		echo "</div>\n";
		
		echo "<div class='panel-body'>\n\n";
		if($count>0)
		{
			//show results
			echo "<span class='tableTitle'>All Panels</span><BR>\n";
			echo CreateDataTableHeader(array("Site","Room","Panel"));
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				echo "<tr class='$rowClass'>";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($site)."</td>";
				echo "<td class='data-table-cell'><a href='./?roomid=$roomID'>".MakeHTMLSafe($room)."</a></td>";
				echo "<td class='data-table-cell'><a href='./?page=PowerAudit&pa_roomid=$roomID&pa_panel=$panel'>".MakeHTMLSafe(FormatPanelName($panel))."</a></td>";
				echo "</tr>";
			}
			echo "</table>";
		}
		else
			echo "No Power panel data found<BR>";
						
		echo "</div>\n";
		echo "</div>\n";
		return $count;
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
			{//					nno
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
	
	function CreateRoomLayout($roomID, $width, $depth, $name, $fullName, $relativeX, $relativeY, $relativeWidth, $relativeDepth, $rotationTransform, $roomClass, $roomCustomHTML="", $roomCustomStyle="")
	{
		global $mysqli;
		global $errorMessage;
		
		$result = "<style>\n";
		$result .= "#room$roomID {\n";
		$result .= "	left: $relativeX%;\n";
		$result .= "	top: $relativeY%;\n";
		$result .= "	width: $relativeWidth%;\n";
		$result .= "	height: $relativeDepth%;\n";
		$result .= $rotationTransform;
		$result .= "}\n";
		$result .= $roomCustomStyle;
		$result .= "</style>\n";
		
		$result .= "<div id='room$roomID' class='roomContainer'>\n";
		$result .= "<a href='./?roomid=$roomID' title='$fullName'>\n";
		if($roomCustomHTML)
			$result .= $roomCustomHTML;
		else
		{
			$result .= "<div id='' class='roomBorders $roomClass'></div>\n";
			$result .= "<span>$name</span>\n";
		}
		
		//render locations
		$parentWidth = $width;
		$parentDepth = $depth;
		
		//select locations from table for rendering each one
		$query = "SELECT l.locationid, l.name, l.xpos, l.ypos, l.width, l.depth, l.orientation, l.visible, COUNT(d.deviceid) AS devicecount
				FROM dcim_location AS l
					LEFT JOIN dcim_device AS d ON d.locationid=l.locationid AND d.status = 'A'
				WHERE l.roomid=? AND l.visible='T' AND l.width > 0 AND l.depth > 0
				GROUP BY l.locationid
				ORDER BY l.name";
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $roomID) || !$stmt->execute())
		{
			$errorMessage[]= "CreateRoomLayout() SQL setup failed: (" . $mysqli->errno . ") " . $mysqli->error;
		}
		else
		{
			$stmt->store_result();
			$stmt->bind_result($locationID, $name, $xPos, $yPos, $width, $depth, $orientation, $visible, $deviceCount);
				
			while($stmt->fetch())
			{
				$relativeX = 100*$xPos/$parentWidth;
				$relativeY= 100*$yPos/$parentDepth;
		
				$rotation = OritentationToDegrees($orientation);
				$rotationTransform = "	transform: rotate(".$rotation."deg); -ms-transform: rotate(".$rotation."deg); -webkit-transform: rotate(".$rotation."deg);";
					
				//adjust dimentions if rotated
				if($orientation=="E" || $orientation=="W")
				{
					$relativeWidth= 100*(($width/$parentDepth)*($parentDepth/$parentWidth));
					$relativeDepth = 100*(($depth/$parentWidth)*($parentWidth/$parentDepth));
				}
				else
				{
					$relativeWidth = 100*$width/$parentWidth;
					$relativeDepth= 100*$depth/$parentDepth;
				}
				
				if($deviceCount>0)
					$locationTypeClass = "locationFullBackground";
				else
					$locationTypeClass = "locationEmptyBackground";
				$roomCustomStyle = "";
				$roomCustomHTML = "";
		
				$result .= CreateLocationLayout($locationID, $name, $relativeX, $relativeY, $relativeWidth, $relativeDepth, $rotationTransform, $locationTypeClass, $roomCustomHTML, $roomCustomStyle);
			}
		}
		
		$result .= "</a>\n";
		$result .= "</div>\n";
		
		return $result;
	}
	
	function CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass, &$roomCustomStyle, &$roomCustomHTML)
	{//percent inset corner
		//breaks rectangle into 4  corner rectangles #1 - #4 with #1 in top left
		//then set borders properly and background properly and disables the inset
		//determines which corner based on inset values such that negative x inset is inset from the right
		
		$borderThickness = 4;
		
		$cornerNo = 1;//TL
		if($cornerWidthInset<0)
			$cornerNo=2;//TR
		if($cornerWidthInset<0 && $cornerDepthInset<0)
			$cornerNo=3;//BR
		else if($cornerDepthInset<0)
			$cornerNo=4;//BL

		$leftWidth = $cornerWidthInset;
		$topHeight = $cornerDepthInset;
		
		if($cornerWidthInset<0)$leftWidth+=100;
		if($cornerDepthInset<0)$topHeight+=100;

		$rightWidth = 100-$leftWidth;
		$bottomHeight = 100-$topHeight;
		
		$roomCustomStyle .= "#room".$roomID."_TopLeft {\n";
		$roomCustomStyle .= "	width: calc($leftWidth% + ".$borderThickness."px);\n";
		$roomCustomStyle .= "	height: calc($topHeight% + ".$borderThickness."px);\n";
		$roomCustomStyle .= "	border-style: solid hidden hidden solid;\n";
		if($cornerNo==2)$roomCustomStyle .= "	border-right-style: solid;\n";
		if($cornerNo==4)$roomCustomStyle .= "	border-bottom-style: solid;\n";
		$roomCustomStyle .= "}\n";
		$roomCustomStyle .= "#room".$roomID."_TopRight {\n";
		$roomCustomStyle .= "	left: $leftWidth%;\n";
		$roomCustomStyle .= "	width: $rightWidth%;\n";
		$roomCustomStyle .= "	height: calc($topHeight% + ".$borderThickness."px);\n";
		$roomCustomStyle .= "	border-style: solid solid hidden hidden;\n";
		if($cornerNo==1)$roomCustomStyle .= "	border-left-style: solid;\n";
		if($cornerNo==3)$roomCustomStyle .= "	border-bottom-style: solid;\n";
		$roomCustomStyle .= "}\n";
		$roomCustomStyle .= "#room".$roomID."_BottomRight {\n";
		$roomCustomStyle .= "	top: $topHeight%;\n";
		$roomCustomStyle .= "	left: $leftWidth%;\n";
		$roomCustomStyle .= "	width: $rightWidth%;\n";
		$roomCustomStyle .= "	height: $bottomHeight%;\n";
		$roomCustomStyle .= "	border-style: hidden solid solid hidden;\n";
		if($cornerNo==2)$roomCustomStyle .= "	border-top-style: solid;\n";
		if($cornerNo==4)$roomCustomStyle .= "	border-left-style: solid;\n";
		$roomCustomStyle .= "}\n";
		$roomCustomStyle .= "#room".$roomID."_BottomLeft {\n";
		$roomCustomStyle .= "	top: $topHeight%;\n";
		$roomCustomStyle .= "	width: calc($leftWidth% + ".$borderThickness."px);\n";
		$roomCustomStyle .= "	height: $bottomHeight%;\n";
		$roomCustomStyle .= "	border-style: hidden hidden solid solid;\n";
		if($cornerNo==1)$roomCustomStyle .= "	border-top-style: solid;\n";
		if($cornerNo==3)$roomCustomStyle .= "	border-right-style: solid;\n";
		$roomCustomStyle .= "}\n";

		if($cornerNo==2)
		{//special case for this corner because corner 4(BL) is added last it would overlap the inner corner making it look bad 
			$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_BottomLeft'></div>\n";
			$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_TopLeft'></div>\n";
			$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_BottomRight'></div>\n";
		}
		else
		{
			if($cornerNo!=1)$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_TopLeft'></div>\n";
			if($cornerNo!=2)$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_TopRight'></div>\n";
			if($cornerNo!=3)$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_BottomRight'></div>\n";
			if($cornerNo!=4)$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_BottomLeft'></div>\n";
		}
	}
	
	function CreateRoomLayout_FarLeftCornerAngle($cornerWidth, $roomID, $roomTypeClass, $depthToWidthRatio, &$roomCustomStyle, &$roomCustomHTML)
	{//not finished and not 100% correct
		//$cornerWidth = 34.577;//width of the corner to be rotated into place -  will need to be converted to height percentage
		$cornerHeight = $cornerWidth*$depthToWidthRatio;
		$cornerLength = sqrt($cornerWidth*$cornerWidth + $cornerHeight*$cornerHeight);
		
		$roomCustomStyle .= "#room".$roomID."_right {\n";
		$roomCustomStyle .= "	left: $cornerWidth%;\n";
		$roomCustomStyle .= "	width: ".(100-$cornerWidth)."%;\n";
		$roomCustomStyle .= "	border-style: solid solid solid hidden;\n";
		$roomCustomStyle .= "}\n";
		$roomCustomStyle .= "#room".$roomID."_left {\n";
		$roomCustomStyle .= "	top: $cornerHeight%;\n";
		$roomCustomStyle .= "	width: $cornerWidth%;\n";
		$roomCustomStyle .= "	height: ".(100-$cornerHeight)."%;\n";
		$roomCustomStyle .= "	border-style: hidden hidden solid solid;\n";
		$roomCustomStyle .= "}\n";
		$roomCustomStyle .= "#room".$roomID."_corner {\n";
		$roomCustomStyle .= "	top: $cornerHeight%;\n";
		$roomCustomStyle .= "	height: $cornerWidth%;\n";//length into shape
		$roomCustomStyle .= "	width: $cornerLength%;\n";
		$roomCustomStyle .= "	border-style: solid hidden hidden hidden;\n";
		$roomCustomStyle .= "	transform: rotate(-45deg);\n";
		$roomCustomStyle .= "}\n";
		
		$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_right'></div>\n";
		$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_left'></div>\n";
		$roomCustomHTML .= "<div class='$roomTypeClass roomBorders roomCorner' id='room".$roomID."_corner'></div>\n";
	}
	
	function CreateLocationLayout($locationID, $name, $relativeX, $relativeY, $relativeWidth, $relativeDepth, $rotationTransform, $locationClass, $locationCustomHTML="", $locationCustomStyle="")
	{
		$result = "<style>\n";
		$result .= "#location$locationID {\n";
		$result .= "	left: $relativeX%;\n";
		$result .= "	top: $relativeY%;\n";
		$result .= "	width: $relativeWidth%;\n";
		$result .= "	height: $relativeDepth%;\n";
		$result .= $rotationTransform;
		$result .= "}\n";
		$result .= $locationCustomStyle;
		$result .= "</style>\n";

		$result .= "<div id='location$locationID' class='locationContainer'>\n";
		$result .= "<a href='./?locationid=$locationID' title='$name'>\n";
		if($locationCustomHTML)
			$result .= $locationCustomHTML;
		else
		{
			$result .= "<div id='' class='$locationClass'></div>\n";
			//$result .= "<span>$name</span>\n";
		}
		$result .= "</a>\n";
		$result .= "</div>\n";
		
		return $result;
		
	}
?>