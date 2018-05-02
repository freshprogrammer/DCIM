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
		<script>
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
				$resultMessage[] = "Power Audit Panel - Updated $inputCount Records. ($goodCount Updates,$badCount Failures)";
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
		if($valid)$valid = ValidPowerLoad($load, $amps);
		
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
			$isTrippleCircuit = (int)$volts == 308; 
			$filter = "";
			if($isDoubleCircuit)
				$filter = "csr.panel=? AND (csr.circuit=? OR csr.circuit=?)";
			else if($isTrippleCircuit)
				$filter = "csr.panel=? AND (csr.circuit=? OR csr.circuit=? OR csr.circuit=?)";
			else
				$filter = "csr.panel=? AND csr.circuit=?";
			
			$query = "SELECT * FROM (
								SELECT powerid,panel,circuit,volts,amps
								FROM dcim_power
							UNION 
								SELECT powerid,panel,IF(volts=208 OR volts=308,circuit+2,NULL) AS cir,volts,amps
								FROM dcim_power HAVING NOT(cir IS NULL)
							UNION 
								SELECT powerid,panel,IF(volts=308,circuit+4,NULL) AS cir,volts,amps
								FROM dcim_power HAVING NOT(cir IS NULL)
						) AS csr
					WHERE $filter";
		
			if (!($stmt = $mysqli->prepare($query)))
				$errorMessage[] = "Prepare 0 failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error.".";
			else
			{
				if($isDoubleCircuit)
				{
					$secondCircuit = 2+(int)$circuit;
					$stmt->bind_Param('sss', $panel, $circuit, $secondCircuit);
				}
				else if($isTrippleCircuit)
				{
					$secondCircuit = 2+(int)$circuit;
					$thirdCircuit = 4+(int)$circuit;
					$stmt->bind_Param('ssss', $panel, $circuit, $secondCircuit,$thirdCircuit);
				}
				else 
				{
					$stmt->bind_Param('ss', $panel, $circuit);
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
						
						$errorMessage[] = "Existing panel Circuit conflict found (Panel:$p, Circuit#$c) ID#$k. Cannot create duplicate.";
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
			$errorMessage[] = "Your do not have permission to edit locations. Please contact your administrator.";
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
		
		//validate room id and look up parent dimentions
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
				$errorMessage[] = "ProcessLocationAction() Prepare failed: ($action-1) (" . $mysqli->errno . ") " . $mysqli->error;
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
						SELECT pl.locationid, 'P' as recType, pl.powerid, CONCAT(p.panel,' CRK#',p.circuit)
							FROM dcim_powerloc AS pl
							LEFT JOIN dcim_power AS p ON p.powerid=pl.powerid
							WHERE pl.locationid=?";
			
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('ii', $locationID,$locationID) || !$stmt->execute())
				$errorMessage[] = "ProcessLocationAction() Prepare failed: ($action-2) (" . $mysqli->errno . ") " . $mysqli->error;
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
					(roomid,name,altname,type,units,xpos,ypos,width,depth,orientation,visible,note,edituser,editdate)
					VALUES(?,?,?,?,?,?,?,?,?,?,'T',?,?,CURRENT_TIMESTAMP)";
				//                                                             rnatuxywdonu
				if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('isssiddddssi', $roomID,$name,$altName,$type,$units,$xPos,$yPos,$width,$depth,$orientation,$notes,$userID))
					$errorMessage[] = "Prepare failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error;
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
					$errorMessage[] = "Process Location Delete Prepare failed: ($action-4) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{
					$affectedCount = $stmt->affected_rows;
					$totalAffectedCount += $affectedCount;
					if($affectedCount==1)
					{
						$resultMessage[] = "Successfully Deleted Location#$locationID '".$name."' to Room#$roomID..";
						LogDBChange("dcim_location",$locationID,"D");
						$redirectPage = "./?roomid=$roomID";
					}
					else
						$errorMessage[] = "Success, but affected $affectedCount rows.";
				}
			}
			else
			{
				$query = "UPDATE dcim_location
					SET roomid=?,name=?,altname=?,type=?,units=?,xpos=?,ypos=?,width=?,depth=?,orientation=?,note=?
					WHERE locationid=?
					LIMIT 1";
				//                                                             rnatuxywdonk
				if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('isssiddddssi', $roomID,$name,$altName,$type,$units,$xPos,$yPos,$width,$depth,$orientation,$notes, $locationID))
					$errorMessage[] = "Process Location Update Prepare failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error.".";
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
							$resultMessage[] = "Successfully edited location '".$name."' to Room#$roomID.. $totalAffectedCount records updated.";
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
				//$query = "SELECT hno,cno FROM dcim_customer WHERE hno=? OR cno=?";//disabled cno check
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
				$bottomHalf = CustomFunctions::IsThisLocationABottomHalfCab($locationID);
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
					(hno, locationid, name, member, note, unit, type, size, status, asset, serial, model, edituser, editdate) 
					VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,CURRENT_TIMESTAMP)";
				
				//TODO handle errors better
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
		
		$query = "SELECT s.siteid, s.name AS site, r.roomid, r.name, r.fullName, l.locationid, l.name, l.altname, l.type, l.units, l.orientation, l.xpos, l.ypos, l.width, l.depth, l.note, l.edituser, l.editdate, l.qauser, l.qadate
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
		$stmt->bind_result($siteID, $site, $roomID, $room, $roomFullName, $locationID, $location, $altName, $type, $units, $orientation, $xPos, $yPos, $width, $depth, $note, $editUserID, $editDate, $qaUserID, $qaDate);
		$locationFound = $stmt->num_rows==1;
		
		if($locationFound)
		{
			$stmt->fetch();
			$fullLocationName = FormatLocation($site, $room, $location);
			
			if(CustomFunctions::UserHasLocationPermission() || CustomFunctions::UserHasCircuitPermission())
			{
				echo "<script src='lib/js/customerEditScripts.js'></script>\n";	
			}
			
			$pos = FormatSizeInFeet($xPos,$yPos);
			$size = FormatSizeInFeet($width,$depth);
			
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
			echo "<b>Alt Name:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo MakeHTMLSafe($altName);
			echo "</td>\n";
			echo "</tr>\n";
			
			echo "<tr>\n";
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Room:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo "<a href='./?roomid=$roomID'>$roomFullName</a>";
			echo "</td>\n";

			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Position:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo "$pos";
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
			echo "<b>Type:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo LocationType($type);
			echo "</td>\n";
			
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Size:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo "$size";
			echo "</td>\n";
			
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Orientation:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo FormatTechDetails($editUserID,$editDate,Orientation($orientation), $qaUserID, $qaDate);
			echo "</td>\n";

			//device notes
			echo "<tr>\n";
			echo "<td align=right class='customerDetails' valign='top'>\n";
			echo "<b>Notes:</b>";
			echo "</td>\n";
			echo "<td valign=top align=left colspan='5'>\n";
			echo "<textarea rows=3 cols=95 readonly placeholder=''>".MakeHTMLSafe($note)."</textarea>";
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
			$descendingUnits = $type!="R";
			$unitStart = $units+1;//start decending 42
			$unitLimit = 1;//stop decending at 1
			$sqlOrder = "DESC";
			if(!$descendingUnits)
			{
				$unitStart = 0;
				$unitLimit = $units;
				$sqlOrder = "";
			}
			$showEmptyUnits = $type!="C";
			
			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>Location Details</div>\n";
			echo "<div class='panel-body'>\n\n";
			
			//list devices at this location - cant use notml list function because this list is ordered by unit for displaying devices in the cab as they actualy are
			$query = "SELECT s.name AS site, r.name AS room, l.locationid, l.name AS loc, 
					c.hno, c.name AS cust,
					d.deviceid, d.unit, d.name, d.member, d.size, d.type, d.status, d.note, d.asset, d.serial, d.model, d.edituser, d.editdate, d.qauser, d.qadate
				FROM dcim_device AS d
					LEFT JOIN dcim_location AS l ON d.locationid=l.locationid
					LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
					LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
					LEFT JOIN dcim_customer AS c ON d.hno=c.hno
				WHERE l.locationid=? AND d.status='A'
				ORDER BY status, site, room, loc, unit!=0, unit $sqlOrder, name, member";
			
			
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
				echo CreateDataTableHeader(array("Unit","Customer","Device","Model","Size","Type","Status","Note"),true);
				
				//list result data
				$oddRow = false;
				$lastUnit = $unitStart;
				while ($stmt->fetch())
				{
					if($unit!=0 && $showEmptyUnits)
					{
						while($lastUnit!=$unit && $lastUnit>-100 && $lastUnit<200)
						{
							if($descendingUnits)
								$lastUnit--;
							else
								$lastUnit++;
							if($lastUnit!=$unit)
							{
								$oddRow = !$oddRow;
								if($oddRow) $rowClass = "dataRowOne";
								else $rowClass = "dataRowTwo";
								//empty unit
								echo "<tr class='$rowClass'>";
								echo "<td class='data-table-cell'>$lastUnit</td>";
								echo "<td class='data-table-cell' colspan=8></td>";
								echo "</tr>";
							}
						}
					}
					
					$oddRow = !$oddRow;
					if($oddRow) $rowClass = "dataRowOne";
					else $rowClass = "dataRowTwo";
					
					//XXX probbable bug - if truncation happend in the middle of a &lt; tag
					$visibleNotes = TruncateWithSpanTitle(htmlspecialchars(MakeHTMLSafe($notes)));
					$deviceFullName = GetDeviceFullName($name, $model, $member, true);

					$unitSize=1;
					if($size[strlen($size)-1]=="U" && $status=="A")
					{
						$unitSize = substr($size,0,strlen($size)-1);
						if($unitSize<1)
							$unitSize=1;
					}
					
					echo "<tr class='$rowClass'>";
					echo "<td class='data-table-cell'>$unit</td>";
					echo "<td class='data-table-cell' rowspan=$unitSize><a href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a></td>";
					echo "<td class='data-table-cell' rowspan=$unitSize><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>";
					echo "<td class='data-table-cell' rowspan=$unitSize>$model</td>";
					echo "<td class='data-table-cell' rowspan=$unitSize>$size</td>";
					echo "<td class='data-table-cell' rowspan=$unitSize>".DeviceType($type)."</td>\n";
					echo "<td class='data-table-cell' rowspan=$unitSize>".DeviceStatus($status)."</td>\n";
					echo "<td class='data-table-cell' rowspan=$unitSize>$visibleNotes</td>";
					echo "<td class='data-table-cell' rowspan=$unitSize>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
					echo "</tr>";
					
					if($unitSize>1)
					{
						if($descendingUnits)
						{
							for ($i = $unit-1; $i >= $unit-$unitSize+1; $i--)
							{
								$oddRow = !$oddRow;
								if($oddRow) $rowClass = "dataRowOne";
								else $rowClass = "dataRowTwo";
								echo "<tr class='$rowClass'>";
								echo "<td class='data-table-cell'>$i</td>";
								echo "</tr>";
							}
						}
						else
						{
							for ($i = $unit+1; $i <= $unit+$unitSize-1; $i++)
							{
								$oddRow = !$oddRow;
								if($oddRow) $rowClass = "dataRowOne";
								else $rowClass = "dataRowTwo";
								echo "<tr class='$rowClass'>";
								echo "<td class='data-table-cell'>$i</td>";
								echo "</tr>";
							}
						}
					}

					if($unit!=0)
					{
						if($descendingUnits)
							$lastUnit=$unit-$unitSize+1;
						else
							$lastUnit=$unit+$unitSize-1;
					}
				}
				while($showEmptyUnits && $lastUnit!=$unitLimit && $lastUnit>-100 && $lastUnit<200)
				{
					if($descendingUnits)
						$lastUnit--;
					else
						$lastUnit++;
					$oddRow = !$oddRow;
					if($oddRow) $rowClass = "dataRowOne";
					else $rowClass = "dataRowTwo";
					//empty unit
					echo "<tr class='$rowClass'>";
					echo "<td class='data-table-cell'>$lastUnit</td>";
					echo "<td class='data-table-cell' colspan=8></td>";
					echo "</tr>";
				}
				echo "</table>";
			}
			else 
			{
				//TODO fix this to say something better - some locations dont have units or no devices
				echo "No devices found at this location.<BR>\n";
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
		$action = "";
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
			$roomOptions = "";
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
							Units:<input id=EditLocation_units type='number' tabindex=5 size=6 name='units' min='0' max='50' step='1' value='<?php echo $unitsInput;?>' placeholder='42' class=''>
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
								<input id=EditLocation_xpos type='number' tabindex=7 size=3 min='-9999.99' max='9999.99' step='0.01' name='xpos' value='<?php echo $xPosInput;?>' placeholder="12.34" class='' >
							<span class=inputTooltip>Distance from left room edge to back left corner of location in feet (negative for distance from right wall)</span></div>
							Foreward:
							<div class='inputToolTipContainer'>
								<input id=EditLocation_ypos type='number' tabindex=8 size=3 min='-9999.99' max='9999.99' step='0.01' name='ypos' value='<?php echo $yPosInput;?>' placeholder="12.34" class='' >
							<span class=inputTooltip>Distance from far room edge to back left corner of location in feet (negative for distance from close wall)</span></div>
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
						<td colspan='2'>
							<table style='width:100%;'>
								<tr>
									<td align=left>
										<button id='EditLocation_deletebtn' type='button' onclick='DeleteLocation()' tabindex=14>Delete</button>
									</td>
									<td align='right'>
										<button type='button' onclick='HideAllEditForms()' tabindex=13>Cancel</button>
										<input type='submit' value='Save' tabindex=12>
									</td>
								</tr>
							</table>
						</td>
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
		global $config_badgesEnabled;
		global $config_subnetsEnabled;
		
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
			//data for customer
			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>Customer Details</div>\n";
			echo "<div class='panel-body'>\n\n";
				
			//show devices linked to this hNo
			$deviceCount = ListDevices(false,$hNo);
			echo "<BR>\n";
			
			//badges
			if($config_badgesEnabled)
			{
				$badgeCount = ListBadges(false, $hNo);
				echo "<BR>\n";
			}
			
			//ports
			$portCount = ListActiveCustomerDeviceConnections($hNo);
			echo "<BR>\n";
			
			//VLANs
			if($config_subnetsEnabled)
			{
				$vlanCount = ListCustomerSubnets($hNo);
				echo "<BR>\n";
			}
			
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
							<input id=EditCustomer_hno type='number' min=10000 max=999999 step=1 tabindex=1 name='hno' maxlength=6 size=6 value='<?php echo $hNoInput;?>' placeholder='000000' class='' readonly>
							Customer&nbsp;#:
							<input id=EditCustomer_cno type='number' min=10000 max=999999 step=1 tabindex=2 name='cno' maxlength=6 size=6 value='<?php echo $cNoInput;?>' placeholder='000000' class='' readonly>
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
		global $config_subnetsEnabled;
		
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
				echo  "<b>Asset:</b> ".CustomFunctions::CreateInternalInventoryLink($asset)."<BR>\n";
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
			$connectionSymbol = ">";
								
			if($deviceInfo->showDeviceImage)
			{
				//process port data for switchview
				$startPort = $deviceInfo->startPort;
				$endPort = $startPort + $deviceInfo->portCount -1;
				
				$dbPortCount = 0;
				
				$query = "SELECT 
						dp.deviceid, dp.deviceportid, d.name, d.member, d.model, dp.pic, dp.port, c.hno, c.name AS cname, dp.mac,
						sp.deviceid AS sid, sp.deviceportid AS spid, s.name AS sname, s.member AS smember, s.model AS smodel, sp.pic AS spic, sp.port AS sport, s.hno AS shno, sc.name AS scname,
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
						LEFT JOIN dcim_customer AS c ON d.hno=c.hno
						LEFT JOIN dcim_customer AS sc ON s.hno=sc.hno
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
					$stmt->bind_result($deviceID, $devicePortID, $deviceName, $member, $model, $pic, $port, $hno, $customer, $mac, 
									   $switchID, $switchPortID, $switchName, $switchMember, $switchModel, $switchPic, $switchPort, $shno, $switchCustomer, 
									   $type, $speed, $note, $status, $portConnectionID, $editUserID, $editDate, $qaUserID, $qaDate, $vlan);
					$dbPortCount = $stmt->num_rows;
				}
				
				if($dbPortCount>=1)
				{
					$portDivs = "";
					$portCSSTags = "";
					//build switch port divs
					while ($stmt->fetch()) 
					{
						if($port >= $startPort && $port <= $endPort)//valid port in range
						{
							if($pic==0)
							{
								if($status=="A")
									$statusStyle = "switchPortActive";
								else if($status=="D")
									$statusStyle = "switchPortEmpty";
								else if($status=="R")
									$statusStyle = "switchPortReserved";
								else
									$statusStyle = "switchPortBad";
								$statusDescrip = DevicePortStatus($status,true);
								
								//XXX this does not support mutiple vlans, probably need to write fresh SQL and code for that
								$portFullName = FormatPort($member, $model, $pic, $port, $type);
								$connectionText = "$connectionSymbol Not Connected";
								if($switchID!=null)
								{
									$switchPortFullName = FormatPort($switchMember, $switchModel, $switchPic, $switchPort, $type);
									$connectionText = "$connectionSymbol <a href='./?host=$shno'>".MakeHTMLSafe($switchCustomer)."</a><BR>
										$connectionSymbol <a href='./?deviceid=$switchID'>".MakeHTMLSafe($switchName)."</a> ".MakeHTMLSafe($switchPortFullName);
								}
								
								//define popup text
								$popupText = "";
								$tech = $userFullName[$editUserID] . ": ".$editDate;
								//$tech = FormatTechDetails($editUserID, $editDate,"", $qaUserID, $qaDate);
								$popupText = "<a href='./?host=$hno'>$customer</a><BR>
									<a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceName)."</a> $portFullName <BR>
									Status:$statusDescrip<BR>
									$connectionText<BR>
									MAC:".MakeHTMLSafe($mac)." <BR>
									Speed:".MakeHTMLSafe($speed)." <BR>\n";
									
									if($config_subnetsEnabled)
										$popupText .= "VLAN(s):$vlan <BR>\n";
									
									$popupText .= "Tech:$tech <BR>
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
								
								
								//define js for click to edit port
								$jsSafeDeviceFullName = MakeJSSafeParam($deviceFullNameShort);
								$jsSafePortFullName = MakeJSSafeParam($portFullName);
								$jsSafeMac = MakeJSSafeParam($mac);
								$jsSafeSpeed = MakeJSSafeParam($speed);
								$jsSafeNote = MakeJSSafeParam($note);
								//EditDevicePort(event,add, devicePortID, deviceID, deviceName, portName, pic, port, type, status, speed, mac, note)
								$portEditJS = "EditDevicePort(event,false,".(CustomFunctions::UserHasPortAddEditPermission()?"true":"false").",$devicePortID,$deviceID,'$jsSafeDeviceFullName','$jsSafePortFullName',$pic,$port,'$type','$status','$jsSafeSpeed','$jsSafeMac','$jsSafeNote')";
								
								//get port possition
								$xPos = 0; $yPos = 0;
								$deviceInfo->GetPortPosition($port,$xPos,$yPos);
								
								$bottomStyle = "";
								if($deviceInfo->IsPortOnBottom($port))
									$bottomStyle = "switchBottomPort";
								
								//define Create div and position CSS
								$portDiv = "<div id='port$devicePortID' onClick=\"$portEditJS\" class='$statusStyle $bottomStyle tooltip'><span class='toolTip_PortDetails'>$popupText</span></div>\n";
								$portCSS = "#port$devicePortID {
									margin-left: ".$xPos."px;
									margin-top: ".$yPos."px;
								}";
								
								$portDivs .= $portDiv;
								$portCSSTags .= $portCSS;
							}
							else 
							{
								//stub for fiber ports or specialty cases (extra NIC ports, console, MGMT, whatever)
								//this should actualy filter above with the range filter
								//for now dont include them here (on the visual render)  - will still be listed below
								//will need thier own positioning CSS classes and probably image overlays - meh
							}
						}//port in range
					}//while
				}//found ports
				
			?>
<style type="text/css">
#deviceLayout {
	width:<?php echo $deviceInfo->deviceWidthPx;?>;
	height:<?php echo $deviceInfo->deviceHeightPx;?>;
	background-image:url('<?php echo $deviceInfo->deviceImage;?>'); 
	background-repeat: no-repeat;
}
.switchPortActive, .switchPortBad,
.switchPortEmpty, .switchPortReserved{
	background-repeat: no-repeat;
	position: absolute;
	width:<?php echo DeviceModel::$portWidth;?>px;
	height:<?php echo DeviceModel::$portHeight;?>px;
}
<?php echo $portCSSTags;?>
</style>
<?php
				//deviceLayout div
				echo "<div id='deviceLayout'>\n";
				echo $portDivs;
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
				echo CreateDataTableHeader(array("Customer","Name&#x25B2;","Badge#","Status","Issue","Enroll"));
			else
				echo CreateDataTableHeader(array(		   "Name&#x25B2;","Badge#","Status","Issue","Enroll"),true,UserHasWritePermission(),UserHasWritePermission());
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				echo "<tr class='$rowClass'>";
				if($search == true)
					echo "<td class='data-table-cell'>"."<A href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a>"."</td>\n";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($name)."</td>\n";
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
						<td colspan=2><table style='width:100%;'><tr>
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
			
			$query = "SELECT d.deviceid, s.name AS site, r.name AS room, c.hno, c.name AS cust, l.locationid, l.name as loc, l.note, d.unit, d.name, d.member, d.size, d.type, d.status, d.note, d.asset, d.serial, d.model, d.edituser, d.editdate, d.qauser, d.qadate
					FROM dcim_device AS d
						LEFT JOIN dcim_customer AS c ON c.hno=d.hno
						LEFT JOIN dcim_location AS l ON d.locationid=l.locationid
						LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
						LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
					WHERE d.name LIKE ? OR d.note LIKE ? OR l.note LIKE ? OR CONCAT(s.name,' ',r.name,' ',l.name) LIKE ? OR CONCAT(s.name,' ',r.name,'.',l.name) LIKE ? OR d.asset LIKE ? OR d.serial LIKE ? OR d.model LIKE ?
				UNION
					SELECT '', s.name, r.name, '', '', l.locationid, l.name, l.note, '', '', '', '', '', '', '', '', '', '', '', '', '', ''
						FROM dcim_location AS l
							LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
							LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
						WHERE (CONCAT(s.name,' ',r.name,' ',l.name) LIKE ? OR CONCAT(s.name,' ',r.name,' ',l.altname) LIKE ? OR CONCAT(s.name,' ',r.name,'.',l.name) LIKE ? OR l.note LIKE ?)
				ORDER BY site, room, loc, length(name) DESC, unit DESC,name, member";
			
			if (!($stmt = $mysqli->prepare($query))) 
			{
				//TODO hadnle errors better
				echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			}
			$stmt->bind_Param('ssssssssssss', $input, $input, $input, $input, $input, $input, $input, $input, $input, $input, $input, $input);
			
			echo "<span class='tableTitle'>Locations and Devices</span>\n";
		}
		else
		{
			$query = "SELECT d.deviceid, s.name AS site, r.name AS room, d.hno, '', l.locationid, l.name AS loc, l.note, d.unit, d.name, d.member, d.size, d.type, d.status, d.note, d.asset, d.serial, d.model, d.edituser, d.editdate, d.qauser, d.qadate
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
		$stmt->bind_result($deviceID, $site, $room, $hNo, $customer, $locationID, $location, $locationNote, $unit, $name, $member, $size, $type, $status, $notes, $asset, $serial, $model, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
		
		
			
		
		if(!$search && UserHasWritePermission())
		{
			// add button to add new Device
			//EditDevice(add, deviceID, hNo, name, fullname, type, size, locationID, unit, status, notes, model, member, asset, serial)
			echo "<button class='editButtons_hidden' onclick=\"EditDevice(true, -1, '$input', '$input-?', '$input-?', 'S', '1U', -1, '0', 'A', '', '', '-1', '', '')\">Add New</button>\n";
		}
		echo "<BR>\n";
		
		if($count>0)
		{
			if($search)
				echo CreateDataTableHeader(array("Location&#x25B2;","Customer","Device","Model","Serial","Asset","Note"));
			else
				echo CreateDataTableHeader(array("Location&#x25B2;",		   "Device","Unit","Model","Size","Type","Status","Notes"),true,UserHasWritePermission(),UserHasWritePermission());
			
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
				echo "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>";
				if($search)
					echo "<td class='data-table-cell'><a href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a></td>";
				echo "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>";
				if($search)
				{
					echo "<td class='data-table-cell'>".MakeHTMLSafe($model)."</td>";
					echo "<td class='data-table-cell'>".MakeHTMLSafe($serial)."</td>";
					echo "<td class='data-table-cell'>".MakeHTMLSafe($asset)."</td>";
					echo "<td class='data-table-cell'>$visibleNotes</td>";
				}
				else
				{//!search
					echo "<td class='data-table-cell'>$unit</td>";
					echo "<td class='data-table-cell'>".MakeHTMLSafe($model)."</td>";
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
		$typeInput = "S";
		$locationInput = 1;
		$sizeInput = "1U";
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
								<option value="S" <?php if($typeInput==="S") echo "Selected"; ?>>Physical</option>
								<option value="F" <?php if($typeInput==="F") echo "Selected"; ?>>Full Cab</option>
								<option value="H" <?php if($typeInput==="H") echo "Selected"; ?>>Half Cab</option>
								<option value="C" <?php if($typeInput==="C") echo "Selected"; ?>>Cage</option>
							</select>
							Size:
							<input id=EditDevice_size type='text' tabindex=3 size=6 name='size' value='<?php echo $sizeInput;?>' placeholder='2U, Full, 5x7, Half' class=''>
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
				<input id=EditDevice_deviceid type='hidden' name='deviceid' value=-1>
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
	
	function ShowRoomPage($roomID)
	{
		global $mysqli;
		global $deviceModels;
		global $pageSubTitle;
		global $focusSearch;
		
		$query = "SELECT s.siteid, s.name AS site, s.fullname, r.roomid, r.name, r.fullname, r.custaccess, r.orientation, r.xpos, r.ypos, r.width, r.depth, s.width, s.depth, r.edituser, r.editdate, r.qauser, r.qadate
			FROM dcim_room AS r
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE r.roomid=?";
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $roomID) || !$stmt->execute()) 
			$errorMessage[]= "ShowRoomPage Prepare 1 failed: (" . $mysqli->errno . ") " . $mysqli->error;
		else
		{
			$stmt->store_result();
			$stmt->bind_result($siteID, $site, $siteFullName, $roomID, $name, $fullName, $custAccess, $orientation, $xPos, $yPos, $width, $depth, $siteWidth, $siteDepth, $editUserID, $editDate, $qaUserID, $qaDate);
			$roomFound = $stmt->num_rows==1;
			
			if($roomFound)
			{
				$stmt->fetch();
				$fullRoomName = FormatLocation($site, $fullName, "");
				$pageSubTitle = "$fullRoomName";
				
				if(CustomFunctions::UserHasLocationPermission() || CustomFunctions::UserHasRoomPermission())
				{
					echo "<script src='lib/js/customerEditScripts.js'></script>\n";	
				}
				
				$pos = FormatSizeInFeet($xPos,$yPos);
				$size = FormatSizeInFeet($width,$depth);
				
				echo "<div class='panel'>\n";
				echo "<div class='panel-header'>$fullRoomName</div>\n";
				echo "<div class='panel-body'>\n\n";
				
				echo "<table width=100%><tr>\n";
				echo "<td align='left'>\n";
				echo "<span class='customerName'>$fullName</span>\n";
				echo "</td>\n";
				
				echo "<td align='right'>\n";
				//edit Locationbutton - not visible till in edit mode
				/*if(CustomFunctions::UserHasLocationPermission())
				{
					$jsSafeName = MakeJSSafeParam($location);
					$jsSafeAltName = MakeJSSafeParam($altName);
					$jsSafeNote = MakeJSSafeParam($note);
					//add, locationID, roomID, name, altName, type, units, orientation, x, y, width, depth, note)
					$params = "false, $locationID, $roomID, '$jsSafeName', '$jsSafeAltName', '$type', $units, '$orientation', $xPos, $yPos, $width, $depth, '$jsSafeNote'";
					
					?><button type='button' class='editButtons_hidden' onclick="EditLocation(<?php echo $params;?>);">Edit Location</button>
					<?php
				}*/
				//editMode button
				if(CustomFunctions::UserHasLocationPermission() || CustomFunctions::UserHasRoomPermission())
				{
					echo "<button type='button' onclick='ToggleEditMode()' style='display:inline;'>Edit Mode</button>\n";
				}
				echo "</td>\n";
				echo "</tr>\n";
				echo "</table>\n";
				
				//details//details
				echo "<table>\n";
				echo "<tr>\n";
				echo "<td align=right class='customerDetails'>\n";
				echo "<b>Site:</b>";
				echo "</td>\n";
				echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
				echo $siteFullName;
				echo "</td>\n";
				
				echo "<td align=right class='customerDetails'>\n";
				echo "<b>Position:</b>";
				echo "</td>\n";
				echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
				echo "$pos";
				echo "</td>\n";
				
				/*echo "<td align=right class='customerDetails'>\n";
				echo "<b>Units:</b>";
				echo "</td>\n";
				echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
				echo $units;
				echo "</td>\n";*/
				
				echo "</tr>\n";
				echo "<tr>\n";
				
				echo "<td align=right class='customerDetails'>\n";
				echo "<b>Cust Access:</b>";
				echo "</td>\n";
				echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
				echo RoomCustAccess($custAccess);
				echo "</td>\n";
				
				echo "<td align=right class='customerDetails'>\n";
				echo "<b>Size:</b>";
				echo "</td>\n";
				echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
				echo "$size";
				echo "</td>\n";
				
				echo "<td align=right class='customerDetails'>\n";
				echo "<b>Orientation:</b>";
				echo "</td>\n";
				echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
				echo FormatTechDetails($editUserID,$editDate,Orientation($orientation), $qaUserID, $qaDate);
				echo "</td>\n";
				
				echo "</tr></table>\n";
				
				//render room - ignore 0 width or height rooms
				if($width>0 && $depth>0)
					echo CreateRoomLayout($roomID, $name, $fullName, $xPos, $yPos, $width, $depth, $orientation, 0, 0, $custAccess);
			}
			else
			{
				echo "<div class='panel'>\n";
				echo "<div class='panel-header'>Room</div>\n";
				echo "<div class='panel-body'>\n\n";
				echo "Room ID#$roomID not found.<BR>\n";
			}
		}
		
		if(UserHasWritePermission())
		{
			//EditRoomForm();
		}
		
		echo "</div>\n";
		echo "</div>\n\n";
		
		if($roomFound)
		{
			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>$fullRoomName Details</div>\n";
			echo "<div class='panel-body'>\n\n";

			ListRoomLocationsAndDevices($roomID);
			echo "<BR>";
			echo ListPowerPanels(false, $roomID);
			
			echo "</div>\n";
			echo "</div>\n";
			
			if(CustomFunctions::UserHasLocationPermission() || CustomFunctions::UserHasRoomPermission())
			{
				//initialize page JS
				echo "<script type='text/javascript'>InitializeEditButton();</script>\n";
			}
		}//room found
		//return $count;
	}
	
	function ListRoomLocationsAndDevices($roomID)
	{
		//show all customers/devices at given locations - IE all devices in room 5 sorted by location - from nav links 	
		global $mysqli;
		
		$showEmpty = true;///this was a test feature to hide empty locations
		
		//lookup site/room name for headers
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
			$searchTitle = "$site $roomFullName Locations";
			
			$deviceJoin = "LEFT";
			if(!$showEmpty)
				$deviceJoin = "INNER";
			
			$query = "SELECT s.name AS site, r.name, l.locationid, l.name, l.altname, l.type, l.units, l.orientation, l.xpos, l.ypos, l.width, l.depth, l.note, l.edituser, l.editdate, l.qauser, l.qadate, 
					c.hNo, c.name AS customer, d.deviceid, d.name AS devicename, d.model, d.member,
					COUNT(d.locationid) AS count
				FROM dcim_location AS l
					$deviceJoin JOIN dcim_device d ON l.locationID = d.locationid AND d.status='A'
					LEFT JOIN dcim_customer c ON c.hno = d.hno
					LEFT JOIN dcim_room r ON l.roomid=r.roomid
					LEFT JOIN dcim_site s ON r.siteid=s.siteid
				WHERE r.roomid=?
				GROUP BY l.locationid
				ORDER BY r.name, l.name";
			
			if (!($stmt = $mysqli->prepare($query)))
			{
				//TODO handle errors better
				echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			}
			
			$stmt->bind_Param('s', $roomID);
			
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($site, $room, $locationID, $location, $altName, $locType, $units, $orientation, $xPos, $yPos, $width, $depth, $note, $editUserID, $editDate, $qaUserID, $qaDate, $hNo, $customer, $deviceID, $deviceName, $deviceModel, $deviceMember, $deviceCount);
			$count = $stmt->num_rows;
			
			echo "<span class='tableTitle'>$searchTitle ($count)</span>\n";
			//add location button
			if(CustomFunctions::UserHasLocationPermission())
			{
				//add, locationID, roomID, name, altName, type, units, orientation, x, y, width, depth, note)
				$params = "true, -1, $roomID, '', '', '', 0, 'N', 0, 0, 0, 0, ''";
				?><button type='button' class='editButtons_hidden' onclick="EditLocation(<?php echo $params;?>);">Add New</button><?php
			}
			echo "<BR>";
			
			if($count>0)
			{//show results
				echo CreateDataTableHeader(array("Location","Size","Customer","Device"), true, CustomFunctions::UserHasLocationPermission(), CustomFunctions::UserHasLocationPermission());
				
				//list result data
				$lastLocID = -1;
				$oddRow = false;
				while ($stmt->fetch())
				{
					$oddRow = !$oddRow;
					if($oddRow) $rowClass = "dataRowOne";
					else $rowClass = "dataRowTwo";
					
					$additionalDevice = ($locationID==$lastLocID);
					$lastLocID = $locationID;
					
					$fullLocationName = FormatLocation($site, $room, $location);
					$deviceFullName = GetDeviceFullName($deviceName, $deviceModel, $deviceMember, true);
					$pos = FormatSizeInFeet($xPos,$yPos);//not used
					$size = FormatSizeInFeet($width,$depth);
					
					echo "<tr class='$rowClass'>";
					if(!$additionalDevice)
					{
						echo "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>";
						echo "<td class='data-table-cell'>".MakeHTMLSafe($size)."</td>";
					}
					
					if(strlen($customer) > 0)
						echo "<td class='data-table-cell'><a href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a></td>";
					else 
						echo "<td class='data-table-cell'>Empty</td>";
					if($deviceCount==0)
						echo "<td class='data-table-cell'></td>";
					else if($deviceCount==1)
						echo "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>";
					else
						echo "<td class='data-table-cell'>$deviceCount Devices</td>";
					
					if(!$additionalDevice)
					{//on spanned location record
						echo "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate,"", $qaUserID, $qaDate)."</td>";
						
						if(CustomFunctions::UserHasLocationPermission())//disabled cuz there could be multiples entries for this location for each device and that seems confusing and there is no real need to edit the location here anyways
						{
							$jsSafeName = MakeJSSafeParam($location);
							$jsSafeAltName = MakeJSSafeParam($altName);
							$jsSafeNote = MakeJSSafeParam($note);
							//add, locationID, roomID, name, altName, type, units, orientation, x, y, width, depth, note)
							$params = "false, $locationID, $roomID, '$jsSafeName', '$jsSafeAltName', '$locType', $units, '$orientation', $xPos, $yPos, $width, $depth, '$jsSafeNote'";
						
							?><td class='data-table-cell-button editButtons_hidden'><button type='button' class='editButtons_hidden' onclick="EditLocation(<?php echo $params;?>);">Edit</button></td>
										<?php 
							
							echo CreateQACell("dcim_location", $locationID, "", $editUserID, $editDate, $qaUserID, $qaDate, true, 1);
						}
					}
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
			echo "Room($roomID) not found.<BR>\n";
		}
		
		if(CustomFunctions::UserHasLocationPermission())
			EditLocationForm();
		
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
			WHERE d.hno=? AND d.status='A'
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
			echo CreateDataTableHeader(array("Location","Panel","Circuit","Volts","Amps","Status","Load"),true,CustomFunctions::UserHasCircuitPermission(),CustomFunctions::UserHasCircuitPermission());
			
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
				
				$visibleVolts = FormatVolts($volts);
				$visibleCircuit = $circuit;
				if($volts==208)
					$visibleCircuit = Format208CircuitNumber($circuit);
				else if($volts==308)
					$visibleCircuit = Format3Phase208CircuitNumber($circuit);
					
				echo "<tr class='$rowClass'>";
				echo "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>";
				echo "<td class='data-table-cell'><a href='./?page=PowerAudit&pa_roomid=$roomID&pa_panel=$panel'>".MakeHTMLSafe($panel)."</a></td>";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($visibleCircuit)."</td>";
				echo "<td class='data-table-cell'>$visibleVolts</td>";
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
								<option value='308'>208v3p</option>
							</select>
							Amps:
							<select id=EditCircuit_amps name="amps" tabindex=4>
								<option value='20'>20A</option>
								<option value='30'>30A</option>
								<option value='40'>40A</option>
								<option value='50'>50A</option>
								<option value='100'>100A</option>
							</select>
						</td>
					</tr>
					<tr>
						<td align='right' valign='top'>Active</td>
						<td width=1 align='left'>
							<input id=EditCircuit_status type='checkbox' tabindex=5 name='status' value='A' onclick='EditCircuit_StatusClicked()' class=''>
							Load:
							<input id=EditCircuit_load type='number' tabindex=6 name='load' size=5 placeholder='2.04' min=0 max=100 step=0.01 onchange='EditCircuit_LoadChanged()' class=''>
						</td>
					</tr>
					<tr>
						<td colspan=2><table style='width:100%;'><tr>
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
			echo CreateDataTableHeader(array("VLAN","Subnet","Mask","First","Last","Gateway","Note"),true,UserHasWritePermission(),UserHasWritePermission());
			
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
	
	function ListSiteRooms($siteID, $siteFullName)
	{
		global $mysqli;
		global $errorMessage;
		//$formAction = "./?host=$hNo";
		
		$query = "SELECT r.roomid, r.name, r.fullname, r.custaccess, s.name, r.edituser, r.editdate, r.qauser, r.qadate
			FROM dcim_room AS r
				LEFT JOIN dcim_site AS s ON s.siteid=r.siteid
			WHERE s.siteid=?
			ORDER BY r.fullname";
		
		if (!($stmt = $mysqli->prepare($query))) 
		{
			$errorMessage[] = "ListSiteRooms() Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
		}
		
		$stmt->bind_Param('i', $siteID);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($roomID, $roomName, $roomFullName, $custAccess, $siteName, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
		
		$result = "<span class='tableTitle'>$siteFullName Rooms</span>\n";
		//Add button
		/*if(UserHasWritePermission())
		{
		$result .=
			//function EditSubnet(add, portID,vlan,subnet,mask,gateway,first,last,note)
			?><button class='editButtons_hidden' onclick="EditSubnet(true,-1,-1,'','','','','','','')">Add New</button>
			<?php 
		}*/
		$result .= "<BR>";
		if($count>0)
		{
			//echo CreateDataTableHeader(array("Site","Room","Full Name","Cust Access"),true,UserHasWritePermission(),UserHasWritePermission());
			$result .= CreateDataTableHeader(array("Site","Room","Full Name","Cust Access"),true, false, false);
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				if($custAccess=="T")$custAccess = "Yes";
				else $custAccess = "No";
				
				$result .= "<tr class='$rowClass'>";
				$result .= "<td class='data-table-cell'>".MakeHTMLSafe($siteName)."</a></td>";
				$result .= "<td class='data-table-cell'><a href='?roomid=$roomID'>".MakeHTMLSafe($roomName)."</a></td>";
				$result .= "<td class='data-table-cell'><a href='?roomid=$roomID'>".MakeHTMLSafe($roomFullName)."</a></td>";
				$result .= "<td class='data-table-cell'>".$custAccess."</td>";
				$result .= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
				/*if(UserHasWritePermission())
				{
					//edit button
					$result .= "<td class='data-table-cell-button editButtons_hidden'>\n";
					
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
					$result .= "</td>\n";
					
					$result .= CreateQACell("dcim_vlan", $vlanID, $formAction, $editUserID, $editDate, $qaUserID, $qaDate);
				}*/
				$result .= "</tr>";
			}
			$result .= "</table>";
		}
		else 
		{
			$result .= "No room records found.<BR>\n";
		}
		
		/*if(UserHasWritePermission())
		{
			EditSubnetForm($formAction,$hNo);
		}*/
		
		return $result;
	}
	
	function EditSubnetForm($action, $hNo)
	{
		global $mysqli;
		global $errorMessage;
		
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
				
				$portOptions .= "<option value=$devicePortID>".MakeHTMLSafe($deviceFullName." ".$portFullName)."</option>\n";
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
						<td colspan=2><table style='width:100%;'><tr>
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
		global $config_subnetsEnabled;
		
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
							<select name='childdeviceid' id='EditConnection_childdeviceid' onchange='DeviceSelectChanged(true,-1)' tabindex=1>
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
					<?php if($config_subnetsEnabled){?>
					<tr id='EditConnection_updateportsrow'>
						<td align='right' width=1>Port Changes:</td>
						<td align='left' colspan='2'>
							<select name='updateports' id='EditConnection_updateports' tabindex=4>
								<option value=T SELECTED>Update/Delete VLANs</option>
								<option value=F>No change</option>
							</select>
						</td>
					</tr>
					<?php }?>
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
		global $config_subnetsEnabled;
		
		
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
			echo "<button class='editButtons_hidden' onclick=\"EditDevicePort(event,true,".(CustomFunctions::UserHasPortAddEditPermission()?"true":"false").",-1,$deviceKeyInput,'$jsSafeDeviceFullName','',0,0,'E','D','','','')\">Add New</button>\n";
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

			if($config_subnetsEnabled)
				$tableHeader = CreateDataTableHeader(array("Device","Port&#x25B2;","MAC","Connected Device","Port","Speed","Status","VLANs","Note"),true,UserHasWritePermission(),UserHasWritePermission());
			else 
				$tableHeader = CreateDataTableHeader(array("Device","Port&#x25B2;","MAC","Connected Device","Port","Speed","Status","Note"),true,UserHasWritePermission(),UserHasWritePermission());
			
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
				{
					$switchDisplayName = "";
					$switchPortFullName = "";
				}
				
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
				if($config_subnetsEnabled)
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
					$portEditJS = "EditDevicePort(event,false,".(CustomFunctions::UserHasPortAddEditPermission()?"true":"false").",$devicePortID,$deviceID,'$jsSafeDeviceFullName','$jsSafePortFullName',$pic,$port,'$type','$status','$jsSafeSpeed','$jsSafeMac','$jsSafeNote')";
					
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
		global $config_subnetsEnabled;
		$formAction = "./?host=$hNo";
		
		$query = "SELECT 
				dp.deviceid, dp.deviceportid, d.name, d.member, d.model, dp.pic, dp.port, dp.mac,
				sp.deviceid AS sid, sp.deviceportid AS spid, s.name AS sname, s.member AS smember, s.model AS smodel, sp.pic AS spic, sp.port AS sport,
				dp.type, dp.speed, dp.note, dp.status, pc.portconnectionid, pc.patches, pc.relationship, pc.edituser, pc.editdate, pc.qauser, pc.qadate,
				CAST(GROUP_CONCAT(IF(pv.vlan<0,CONCAT('Temp-',ABS(pv.vlan)),pv.vlan) ORDER BY pv.vlaN<0, ABS(pv.vlaN) SEPARATOR ', ') AS CHAR) AS vlans,
				l.locationid, l.name, sl.locationid, sl.name
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
				INNER JOIN dcim_location AS l ON d.locationid=l.locationid
				INNER JOIN dcim_location AS sl ON s.locationid=sl.locationid
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
						   $type, $speed, $note, $status, $portConnectionID, $patches, $relationship, $editUserID, $editDate, $qaUserID, $qaDate, 
							$vlan, $dLocID, $dLocName, $sLocID, $sLocName);
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
			if($config_subnetsEnabled)
				echo CreateDataTableHeader(array("Loc","Child Device","Port&#x25B2;","Loc","Parent Device","Port","VLAN","Patches"),true,UserHasWritePermission(),UserHasWritePermission());
			else
				echo CreateDataTableHeader(array("Loc","Child Device","Port&#x25B2;","Loc","Parent Device","Port","Patches"),true,UserHasWritePermission(),UserHasWritePermission());
			
			
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
				
				$portFullName = FormatPort($member, $model, $pic, $port, $type);
				$switchPortFullName = FormatPort($switchMember, $switchModel, $switchPic, $switchPort, $type);
				
				$deviceFullName = GetDeviceFullName($deviceName, $model, $member, true);
				$switchFullName = GetDeviceFullName($switchName, $switchModel, $switchMember, true);

				$devicePortTitle = "";
				$switchPortTitle = "";
				if(CustomFunctions::UserHasDevPermission())
				{
					$devicePortTitle = "switchportid=$devicePortID";
					$switchPortTitle = "switchportid=$switchPortID";
				}

				$childCells = "<td class='data-table-cell'><a href='./?locationid=$dLocID'>".MakeHTMLSafe($dLocName)."</a></td>\n";
				$childCells .= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>\n";
				$childCells .=  "<td class='data-table-cell'><span title='$devicePortTitle'>$portFullName</span></td>\n";

				$parentCells = "<td class='data-table-cell'><a href='./?locationid=$sLocID'>".MakeHTMLSafe($sLocName)."</a></td>\n";
				$parentCells .=  "<td class='data-table-cell'><a href='./?deviceid=$switchID'>".MakeHTMLSafe($switchFullName)."</a></td>\n";
				$parentCells .=  "<td class='data-table-cell'><span title='$switchPortTitle'>$switchPortFullName</span></td>\n";
				
				if($relationship!="Child")
				{//swap parent and child
					$t = $childCells;
					$childCells = $parentCells;
					$parentCells = $t;
				}
				echo $childCells;
				echo $parentCells;
				
				if($config_subnetsEnabled)
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
	
	function PowerAuditPanel($pa_panel)
	{
		//TODO This really should be using a panelID from a panel table but that not currently necisarry
		global $mysqli;
		global $pageSubTitle;
		
		//lookup site room and circuit info for headers
		$query = "SELECT s.siteid, s.name, r.roomid, r.name, r.fullname, p.panel
			FROM dcim_power AS p
				LEFT JOIN dcim_powerloc AS pl ON p.powerid=pl.powerid
				LEFT JOIN dcim_location AS l ON pl.locationid=l.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE p.panel=?
			GROUP BY r.roomid, p.panel, p.circuit
			ORDER BY p.circuit
			LIMIT 1";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			//TODO handle errors better
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		
		$stmt->bind_Param('s' ,$pa_panel);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($siteID, $site, $roomID, $room, $roomFullName, $panel);
		$count = $stmt->num_rows;
		
		if($count==1 && $stmt->fetch())
		{//sucsessfull lookup
			//TODO: This room name is not guranteed to be the room the panel is in so it could be a little confusing. it is just a room linked to at least one of the circuitts.
			$fullPanelDescription = MakeHTMLSafe("$site $roomFullName Panel:".$panel);
			$pageSubTitle = "Power Audit - ".MakeHTMLSafe("$site $room Panel:".$panel);//short room name 
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
				WHERE p.panel=?
				GROUP BY r.roomid, p.panel, p.circuit
				ORDER BY p.circuit";
			
			if (!($stmt = $mysqli->prepare($query)))
			{
				//TODO handle errors better
				echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			}
			
			$stmt->bind_Param('s', $pa_panel);
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
				$leftSpan = 1;
				$rightSpan = 1;
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
						$displayVolts = FormatVolts($volts);
						$displayCircuit = $circuit;
						if($volts==208)//208 volt circuits take up double
						{
							if($left)
								$leftSpan = 2;
							else
								$rightSpan = 2;
							$cellClass .= " powerAuditCellDouble";
							$rowSpan = " rowspan=2";
							$displayCircuit = Format208CircuitNumber($circuit);
						}
						else if($volts==308)//208 3 phase circuits take up tripple
						{
							if($left)
								$leftSpan = 3;
							else
								$rightSpan = 3;
							$cellClass .= " powerAuditCellTripple";
							$rowSpan = " rowspan=3";
							$displayCircuit = Format3Phase208CircuitNumber($circuit);
						}
						
						echo "<td $rowSpan class='$cellClass'>\n";
						echo "	<table width=100%><tr>\n";
						echo "	<td><b>".MakeHTMLSafe($panel)." CKT ".MakeHTMLSafe($displayCircuit)."</b></td>\n";
						echo "	<td align=right>".MakeHTMLSafe($cust)."</td>\n";
						echo "	</tr></table><table width=100%><tr>\n";
						//echo "	$fullLocationName ($percentLoad%) ";
						echo "	<td><a href='javascript:;' onclick='PowerAuditPanel_ConfirmPageChange(\"./?locationid=$locationID\");'>".MakeHTMLSafe($fullLocationName)."</a></b>&nbsp;&nbsp;</td>\n";
						echo "	<td align=right>".$displayVolts."-".$amps."A-<b>".PowerOnOff($status)."</b>\n";
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
						if($left && $leftSpan>1)
							$leftSpan--;
						else if(!$left && $rightSpan>1)
							$rightSpan--;
						else
							echo "<td class='$cellClass powerAuditCellEmpty'>".MakeHTMLSafe($panel)." / ".MakeHTMLSafe($tableCircuitNo)." - EMPTY</td>\n";
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
			$pageSubTitle = "Power Audit - Panel:$pa_panel not found";
			echo "<script src='lib/js/customerEditScripts.js'></script>\n";
			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>\n";
			echo "Circuits for Panel:$pa_panel\n";
			echo "</div>\n";
		
			echo "<div class='panel-body'>\n\n";
			echo "No power circuits found for Panel:$pa_panel<BR>\n";
		}	
		echo "</div>\n";
		echo "</div>\n";
		return $count;
	}
	
	function PowerAuditPanelList()
	{
		global $pageSubTitle;
		
		$pageSubTitle = "Power Audit - Panel List";
		
		$result = "<div class='panel'>\n";
		$result.= "<div class='panel-header'>\n";
		$result.= "Power Panel list\n";
		$result.= "</div>\n";
		
		$result.= "<div class='panel-body'>\n\n";
		
		$result.= ListPowerPanels(false);
		
		$result.= "</div>\n";
		$result.= "</div>\n";
		echo $result;
	}
	
	function ListPowerPanels($search, $input=-1)
	{
		global $mysqli;
		global $errorMessage;
		
		$filter = "";
		if($search)
		{
			//replace '-' and ' ' to '' and compare to search
			//$input = str_replace("-","",$input);
			//$input = str_replace(" ","",$input);
			$input = "%".$input."%";
			//$filter = "REAPLCE(REAPLCE(p.panel,'-',''),' ','') LIKE = ?";
			$filter = "p.panel LIKE ?";
		}
		else
		{
			if($input!=-1)
				$filter = "r.roomid=?";
			else
				$filter = "-1=?";
		}
		
		$query = "SELECT s.siteid, s.name,r.roomid, r.name, p.panel
			FROM dcim_power AS p
				LEFT JOIN dcim_powerloc AS pl ON p.powerid=pl.powerid
				LEFT JOIN dcim_location AS l ON pl.locationid=l.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE $filter
			GROUP BY p.panel
			ORDER BY s.name, p.panel";
		
		$result = "<span class='tableTitle'>Power Panels</span><BR>\n";
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $input) || !$stmt->execute())
		{
			$errorMessage[]= "Prepare failed: PowerAuditPanelList() (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			$result .= "SQL error locating power panels";
		}
		else
		{
			$stmt->store_result();
			$stmt->bind_result($siteID,$site,$roomID,$room, $panel);
			$count = $stmt->num_rows;
			
			if($count>0)
			{
				//show results
				$result .= CreateDataTableHeader(array("Site","Panel","Room"));
					
				//list result data
				$oddRow = false;
				while ($stmt->fetch())
				{
					$oddRow = !$oddRow;
					if($oddRow) $rowClass = "dataRowOne";
					else $rowClass = "dataRowTwo";
			
					$result .= "<tr class='$rowClass'>";
					$result .= "<td class='data-table-cell'>".MakeHTMLSafe($site)."</td>";
					$result .= "<td class='data-table-cell'><a href='./?page=PowerAudit&pa_panel=$panel'>".MakeHTMLSafe($panel)."</a></td>";
					$result .= "<td class='data-table-cell'><a href='./?roomid=$roomID'>".MakeHTMLSafe($room)."</a></td>";
					$result .= "</tr>";
				}
				$result .= "</table>";
			}
			else
				$result .= "No Power panel data found<BR>";
		}
		return $result;
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
	
	function CreatePanel($panel, $circuitCount, $status, $locationID, $userID)
	{
		//used for batch creating of circuits (a whole panel worth)
		//create all circuits (power records)
		//create link to location (connected to "unknown" or generic location
		
		global $mysqli;
		global $errorMessage;
		global $resultMessage;
		
		$errorMessage[] = "CreatePanel disabled untill suport for 208v3p.";
		/*
		//for error reporting
		$action = "CreatePanel()";
		
		//default generic values
		$volts = 120;
		$amps = 20;
		$load = 0;

		$circuit= GetInput("circuit");
		
		if(!isset($status) || strlen($status)==0)
			$status = "D";
		
		$totalAffectedCount = 0;
		$valid = true;
		
		if($valid)$valid = ValidPowerPanel($panel);
		if($valid)$valid = ValidPowerVolts($volts);
		if($valid)$valid = ValidPowerAmps($amps);
		if($valid)$valid = ValidPowerStatus($status);
		if($valid)$valid = ValidPowerLoad($load, $amps);
		
		//check for location in table
		if($valid)$valid = ValidLocation($locationID,true);
		
		
		
		for($circuit=1; $circuit<=$circuitCount; $circuit++)
		{
			if($valid)
			{
				//check for existing panel circuit combo
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
						$errorMessage[] = "CreatePanel() Failed to execute power circuit locate verification (" . $stmt->errno . "-" . $stmt->error . ").";
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
							
							$errorMessage[] = "CreatePanel() Existing panel Circuit found (Panel:$p, Circuit#$c) ID#$k. Cannot create duplicate.";
						}
					}
				}
				$valid=$passedDBChecks;
			}
			
			//push each circuit to DB
			if($valid)
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
						$errorMessage[] = "CreatePanel() Failed to execute power circuit add (" . $stmt->errno . "-" . $stmt->error . ").";
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
										$errorMessage[] = "CreatePanel() Failed to execute power circuit location link add (" . $stmt->errno . "-" . $stmt->error . ").";
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
											$errorMessage[] = "CreatePanel() Power circuit location link added successfully, but affected $affectedCount rows.";
									}
								}
								$resultMessage[] = "$totalAffectedCount total records created.";
							}
							else
							{
								$errorMessage[] = "CreatePanel() Failed to locate inserted record. Power (if created) is not linked to Location.";
							}
						}
					}
				}
			}
		}*/
	}
	
	function CreateSiteLayout($siteID, $name, $fullName, $siteWidth, $siteDepth)
	{
		global $mysqli;
		global $errorMessage;
		
		$result = CustomFunctions::CreateSiteCustomLayout($siteID, $name, $fullName, $siteWidth, $siteDepth);
		
		//select rooms from table for rendering each one - NOTE these are sorted by layer so rooms that may over lap others can have a proper layer
		$query = "SELECT roomid, name, fullname, custaccess, xpos, ypos, width, depth, orientation
				FROM dcim_room
				WHERE siteid=? AND width > 0 AND depth > 0
				ORDER BY layer";
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $siteID) || !$stmt->execute())
		{
			$errorMessage[]= "CreateSiteLayout() SQL setup failed: (" . $mysqli->errno . ") " . $mysqli->error;
		}
		else
		{
			$stmt->store_result();
			$stmt->bind_result($roomID, $name , $fullName, $custAccess, $xPos, $yPos, $width, $depth, $orientation);
			
			while($stmt->fetch())
			{
				$result .= CreateRoomLayout($roomID, $name, $fullName, $xPos, $yPos, $width, $depth, $orientation, $siteWidth, $siteDepth, $custAccess);
			}
		}
		return $result;
	}
	
	function CreateRoomLayout($roomID, $name, $fullName, $xPos, $yPos, $width, $depth, $orientation, $parentWidth, $parentDepth, $custAccess)
	{
		global $mysqli;
		global $errorMessage;
		
		//calculated
		$relativeX = 0;
		$relativeY = 0;
		$relativeWidth = 0;
		$relativeDepth = 0;
		
		$renderingWithinParent = ($parentWidth > 0 && $parentDepth>0);
		if($renderingWithinParent)
		{
			$relativeX = 100*$xPos/$parentWidth;
			$relativeY= 100*$yPos/$parentDepth;
		
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
		}
		else
			$orientation = "N";
		
		$rotation = OritentationToDegrees($orientation);
		$rotationTransform = "";
		if($rotation!=0)
			$rotationTransform = "	transform: rotate(".$rotation."deg); -ms-transform: rotate(".$rotation."deg); -webkit-transform: rotate(".$rotation."deg);\n";
		
		//create custom style and html
		$roomCustomHTML = "";
		$roomCustomStyle = "";
		CustomFunctions::CreateRoomCustomLayout($roomID, $name, $custAccess, $roomCustomHTML, $roomCustomStyle);
		
		//begin creating style and html for this room
		$result = "<style>\n";
		if($renderingWithinParent)
		{
			$result .= "#room$roomID {\n";
			$result .= "	left: $relativeX%;\n";
			$result .= "	top: $relativeY%;\n";
			$result .= "	width: $relativeWidth%;\n";
			$result .= "	height: $relativeDepth%;\n";
			$result .= $rotationTransform;
			$result .= "}\n";
		}
		else
		{
			$heightMax = 650;
			$widthMax = 948;
			
			$renderHeight = $heightMax;
			$renderWidth = $renderHeight*($width/$depth);
			if($renderWidth>$widthMax)
			{
				$renderWidth = $widthMax;
				$renderHeight = $renderWidth*($depth/$width);
			}
			
			//rendering this room standalone
			$result .= "#room$roomID {\n";
			$result .= "	position: relative;\n";
			$result .= "	width: ".$renderWidth."px;\n";
			$result .= "	height: ".$renderHeight."px;\n";
			$result .= $rotationTransform;
			$result .= "}\n";
		}
		$result .= $roomCustomStyle;
		$result .= "</style>\n";
		
		$result .= "<div id='room$roomID' class='roomContainer'>\n";
		if($renderingWithinParent)$result .= "<a href='./?roomid=$roomID' title='$fullName'>\n";
		if($roomCustomHTML)
			$result .= $roomCustomHTML;
		else
		{
			$roomTypeClass = RoomAccesClass($custAccess);
			$result .= "<div id='' class='roomBorders $roomTypeClass'></div>\n";
			$result .= "<span class='roomLayoutTitle'>$name</span>\n";
		}
		
		//render locations
		$parentWidth = $width;
		$parentDepth = $depth;
		
		//select locations from table for rendering each one
		$query = "SELECT l.locationid, l.name, l.xpos, l.ypos, l.width, l.depth, l.orientation, s.name, r.fullname, d.hno, c.name AS cust, d.deviceid, d.name as device, COUNT(Distinct l.locationid) AS countl, COUNT(Distinct d.deviceid) AS countd
				FROM dcim_location AS l
					LEFT JOIN dcim_device AS d ON d.locationid=l.locationid AND d.status = 'A'
					LEFT JOIN dcim_customer AS c ON d.hno = c.hno
					LEFT JOIN dcim_room AS r ON r.roomid = l.roomid
					LEFT JOIN dcim_site AS s ON s.siteid = r.siteid
				WHERE l.roomid=? AND l.width > 0 AND l.depth > 0
				GROUP BY l.xpos, l.ypos, l.width, l.depth, l.orientation
				ORDER BY l.name";
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $roomID) || !$stmt->execute())
			$errorMessage[]= "CreateRoomLayout() SQL setup 1 failed: (" . $mysqli->errno . ") " . $mysqli->error;
		else
		{
			$stmt->store_result();
			$stmt->bind_result($locationID, $name, $xPos, $yPos, $width, $depth, $orientation, $site, $room, $hNo, $customer, $deviceid, $deviceName, $countL, $countD);

			$lastLocationID = PHP_INT_MAX;
			while($stmt->fetch())
			{
				$firstDevice = true;
				$popupText = "";
				
				if(!$renderingWithinParent)
				{
					//countL should always be >=1
					if(true || $countL>1 || $countD>1)//XXX this extra SELECT per locaition* could be avoided if the counts were 1 but would require duplication the popup text generation code 
					{
						//full locattion name, type, size
						//full device name type,size, sorted by cust, devicename
						
						//look up each location  & customer here
						$query2 = "SELECT l.locationid, l.name, l.xpos, l.ypos, l.width, l.depth, l.orientation, s.name, r.fullname, d.hno, c.name AS cust, d.deviceid, d.name as device, d.model, d.member
							FROM dcim_location AS l
								LEFT JOIN dcim_device AS d ON d.locationid=l.locationid AND d.status = 'A'
								LEFT JOIN dcim_customer AS c ON d.hno = c.hno
								LEFT JOIN dcim_room AS r ON r.roomid = l.roomid
								LEFT JOIN dcim_site AS s ON s.siteid = r.siteid
							WHERE r.roomid=? AND l.xpos=? AND l.ypos=? AND l.width=? AND l.depth=? AND l.orientation=?
							ORDER BY l.name, c.name, c.hno, d.name, d.unit";
						
						if (!($stmt2 = $mysqli->prepare($query2)) || !$stmt2->bind_Param('idddds', $roomID, $xPos,$yPos,$width,$depth,$orientation) || !$stmt2->execute())
							$errorMessage[]= "CreateRoomLayout() SQL setup 2 failed: (" . $mysqli->errno . ") " . $mysqli->error;
						else
						{
							$stmt2->store_result();
							$stmt2->bind_result($locationID, $name, $xPos, $yPos, $width, $depth, $orientation, $site, $room, $hNo, $customer, $deviceid, $deviceName, $model, $member);
							
							$lastCustomerID = PHP_INT_MAX;
							while($stmt2->fetch())
							{
								$newLocation = ($lastLocationID!=$locationID);
								$newCustomer = ($lastCustomerID!=$hNo || $newLocation);
								$fullLocationName = FormatLocation($site, $room, $name);
								$deviceFullName = GetDeviceFullName($deviceName, $model, $member, true);

								if(!$firstDevice)
									$popupText .= "<BR>";
								
								if($newLocation && !$firstDevice)
									$popupText .= "<BR>";//blank line between locations
								
								if($newLocation)
									$popupText .= "<b><a href='?locationid=$locationID'>$fullLocationName</a></b><BR>\n";
								
								if($hNo==NULL)
									$popupText .= "Empty";
								else
								{
									if($newCustomer)
										$popupText .= "<a href='?host=$hNo'>$customer</a><BR>\n";
									$popupText .= "&nbsp;&nbsp;&nbsp;&nbsp;<a href='?deviceid=$deviceid'>$deviceFullName</a>";
								}
								$firstDevice = false;
								$lastLocationID=$locationID;
								$lastCustomerID = $hNo;
							}
						}
					}
					else
					{ /* disabled since this code is not active and not up to date - see above comment
						$fullLocationName = FormatLocation($site, $room, $name);
						if($countD==0)
							$popupText = "<b><a href='?locationid=$locationID'>$fullLocationName</a></b><BR>Empty";
						else if($countD==1)
							$popupText = "<b><a href='?locationid=$locationID'>$fullLocationName</a></b><BR>
						<a href='?host=$hNo'>$customer</a><BR>
						<a href='?deviceid=$deviceid'>$deviceName</a>";*/
					}
				}
				else
				{//rendering within parent - simplify popup for title
					//if($countD>1)$customer = "Multiple";//could do this better with SQL group
					//if($countL>1)$name = "Multiple";
				}
				$result .= CreateLocationLayout($locationID, $name, $xPos, $yPos, $width, $depth, $orientation, $countD, $hNo, $customer, $parentWidth, $parentDepth, $renderingWithinParent, $popupText);
			}
		}
		
		if($renderingWithinParent)$result .= "</a>\n";
		$result .= "</div>\n";
		
		return $result;
	}
	
	function CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass, &$roomCustomStyle, &$roomCustomHTML)
	{//percent inset corner
		//breaks rectangle into 4  corner rectangles #1 - #4 with #1 in top left
		//then set borders properly and background properly and disables the inset
		//determines which corner based on inset values such that negative x inset is inset from the right
		$borderThickness = CustomFunctions::$roomBorderThickness;
		
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
	
	function CreateLocationLayout($locationID, $name, $xPos, $yPos, $width, $depth, $orientation, $deviceCount, $hNo, $customer, $parentWidth, $parentDepth, $renderingWithinSite, $popupText)
	{
		$toolTipOffset = 15;
		
		//these are the back left of the location so it can be rotated into place
		$relativeX = 100*$xPos/$parentWidth;
		$relativeY= 100*$yPos/$parentDepth;
		
		//these are true top left on the screen for tooltips
		$relativeTopLeftX = $xPos;
		$relativeTopLeftY = $yPos;
		if($orientation=="E")
			$relativeTopLeftX -= $depth;
		else if($orientation=="W")
			$relativeTopLeftY -= $width;
		else if($orientation=="S")
		{
			$relativeTopLeftX -= $width;
			$relativeTopLeftY -= $depth;
		}
		
		$relativeTopLeftX = 100*($relativeTopLeftX/$parentWidth);
		$relativeTopLeftY= 100*($relativeTopLeftY/$parentDepth);
		
		//adjust dimentions if rotated
		if($orientation=="E" || $orientation=="W")
		{
			$relativeWidth= 100*(($width/$parentDepth)*($parentDepth/$parentWidth));
			$relativeDepth = 100*(($depth/$parentWidth)*($parentWidth/$parentDepth));
		}
		else
		{
			$relativeWidth = 100*($width/$parentWidth);
			$relativeDepth= 100*($depth/$parentDepth);
		}
		
		if($deviceCount>0)
		{
			if(CustomFunctions::IsThisHNoInternal($hNo) || $deviceCount>1)
				$name = $name . " ($customer [$deviceCount device".($deviceCount>1?"s":"")."])";
			else
				$name = $name . " ($customer)";//maybe show device names if this is a non cust access room like the MDF
			$locationClass = "locationFullBackground";
		}
		else
			$locationClass = "locationEmptyBackground";
		
		$rotation = OritentationToDegrees($orientation);
		$rotationTransform = "";
		$reverseRotationTransform = "";
		if($rotation!=0)
		{
			$rotationTransform = "	transform: rotate(".$rotation."deg); -ms-transform: rotate(".$rotation."deg); -webkit-transform: rotate(".$rotation."deg);\n";
			$reverseRotationTransform = "	transform: rotate(".-$rotation."deg); -ms-transform: rotate(".-$rotation."deg); -webkit-transform: rotate(".-$rotation."deg);\n";
		}
		
		$titleWidth = 100;
		$titleHeight = 100;
		if($orientation=="E" || $orientation=="W")
		{
			$titleWidth= 100*($depth/$width);
			$titleHeight = 100*($width/$depth);
		}
		
		$result = "<style>\n";
		$result .= "#location$locationID {\n";
		$result .= "	left: $relativeX%;\n";
		$result .= "	top: $relativeY%;\n";
		$result .= "	width: $relativeWidth%;\n";
		$result .= "	height: $relativeDepth%;\n";
		$result .= $rotationTransform;
		$result .= "}\n";
		$result .= "#location".$locationID."_title {\n";
		$result .= "	width: $titleWidth%;\n"; 
		$result .= "	height: $titleHeight%;\n";
		$result .= $reverseRotationTransform;
		
		if($orientation=="E")
			$result .= "	top: 100%;\n";
		else if($orientation=="S"){
			$result .= "	top: 100%;\n";
			$result .= "	left: 100%;\n";}
		else if($orientation=="W")
			$result .= "	left: 100%;\n";
		
		$result .= "}\n";
		if(!$renderingWithinSite)
		{
			$result .= "#location".$locationID."_tooltip {\n";
			$result .= "	left: $relativeTopLeftX%;\n";
			$result .= "	top: $relativeTopLeftY%;\n";
			$result .= "}\n";
		}
		$result .= "</style>\n";
		
		$result .= "<div id='location$locationID' class='locationContainer tooltip'>\n";
		if($renderingWithinSite)
			$result .= "	<a href='./?locationid=$locationID' title='$name'>\n";
		$result .= "	<div id='' class='$locationClass'>\n";
		$result .= "		<div id='location".$locationID."_title' class='locationTitle'>$name</div>\n";
		$result .= "	</div>\n";
		if($renderingWithinSite)
			$result .= "	</a>\n";
		$result .= "</div>\n";
		if(!$renderingWithinSite)
		{
			$result .= "<span id='location".$locationID."_tooltip' class='toolTip_LocationDetails'>$popupText</span>\n";
		}
		return $result;
	}
?>