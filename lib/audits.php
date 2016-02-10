<?php 
	//This is just a set of functions used for gathering and displaying audits. 
	/*
		::need to add::
		Multiple Devices connencted to a single device port
		location without 2x power
		verify location.status if a device is linked
		power without location
		power amps at 0 or negative value
		port with connection thats not active - or visa versa
		port linked to invalid port - basicly any invalid ports in table
		port linked to more than 1  port- portid in portconnectiontable more than once
		unknown stati in all tables
		log records where first is not I - or missing
		Log records (not "D") missing parent records - parent missing log		
	*/
	
	//this function is inserted into page layout
	function BuildAuditsPage()
	{
		global $pageSubTitle;
		$pageSubTitle = "Data Audits";
		$result = "";
		
		//Audit functions
		$result .= "<script src='lib/js/sorttable.js'></script>";
		$result .= "<div class=\"panel\">\n";
		$result .= "<div class=\"panel-header\">Audit Functions</div>\n";
		$result .= "<div class=\"panel-body\">\n";
		
		$result .= "<button type='button' style='display:inline;' onClick='parent.location=\"./lib/createReport.php?report=ActiveBadgeList\"'>Export Active Badge List as CSV</button><BR><BR>";
		
		$result .= "<button type='button' style='display:inline;' onClick='parent.location=\"./?page=PowerAudit\"'>Power Audit</button>";
				
		$result .= "<div id='rppAuditHelpPopup' class='helpPopup'>".CustomFunctions::RemotePowerPanelAuditHelpPopup()."</div>";
		$result .= "<a class='helpLink' href='javascript:void(0)' onclick = \"CreatePopup('rppAuditHelpPopup');\">Create Remote Power Panel Audit Form</a>\n<BR><BR>";
		
		$result .= "<button type='button' style='display:inline;' onClick='parent.location=\"./lib/createReport.php?report=PowerAudit\"'>Export Location Power Readings as CSV</button>";
		
		if(CustomFunctions::UserHasDevPermission())
		{
			//in development features go here
		}
		$result .= "</div>\n</div>\n\n";//end panel and panel body
		
		
		$result .= "<div class=\"panel\">\n";
		$result .= "<div class=\"panel-header\">Data to QA</div>\n";
		$result .= "<div class=\"panel-body\">\n";
		$result .= Check_CustomerToQA();
		$result .= Check_BadgesToQA();
		$result .= "</div>\n</div>\n\n";//end panel and panel body
		
		
		$result .= "<div class=\"panel\">\n";
		$result .= "<div class=\"panel-header\">Data Inconsistencies</div>\n";
		$result .= "<div class=\"panel-body\">\n";
		$result .= Check_BadgesActiveUnderInactiveCustomer();
		$result .= Check_ColoPatch0();
		$result .= Check_DevicesActiveUnderInactiveCustomer();
		$result .= Check_VLANLinkedToDisabledPort();
		$result .= Check_CircuitOverLoaded();
		$result .= Check_CircuitInactiveWithLoad();
		//$result .= Check_DeviceWithInvalidLocation();
		//$result .= Check_SwitchIsMainDeviceOnDevicePortRecords();
		$result .= "</div>\n</div>\n\n";//end panel and panel body
		
		
		//admin only stuff - just because its stuff they cant fix
		if(UserHasAdminPermission())
		{
			$result .= "<div class=\"panel\">\n";
			$result .= "<div class=\"panel-header\">Admin Data Audits</div>\n";
			$result .= "<div class=\"panel-body\">\n";
			
			$output = "";
			$recCount = CountDBRecords($output);
			$result .= CreateReport("Database Record Counts","$recCount records",$output,"");

			$output = "";
			$lineCount = CountLinesInDir($output);
			$result .= CreateReport("Lines of Code","$lineCount lines",$output,"");
			
			$result .= Check_BadgesWithoutCustomers();
			$result .= Check_DevicesWithoutCustomersOrLocation();
			$result .= Check_DevicePortsWithoutCustomersOrDevices();
			$result .= Check_LocationWithoutPowerLocOrRoom();
			$result .= Check_PowerLocWithoutLocationOrPower();
			$result .= Check_PowerWithoutPowerLoc();
			$result .= Check_RecordsMisingInsertLog();
			$result .= "</div>\n</div>\n";//end panel and panel body
		}
		
		return $result;
	}
	
	function Check_CircuitInactiveWithLoad()
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Circuits off but still reporting load";
		$reportNote = "";
		
		//could properly sort circuits, but meh
		$query = "SELECT s.name AS site, r.name, l.locationid, l.name AS location, p.panel, p.circuit, p.volts, p.amps, p.status, p.load FROM dcim_power AS p 
				LEFT JOIN dcim_powerloc AS pl ON pl.powerid=p.powerid
				LEFT JOIN dcim_location AS l ON l.locationid=pl.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE p.status='D' AND p.load !=0
			ORDER BY 1,2,3";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "Prepare failed: Check_CircuitInactiveWithLoad() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_CircuitInactiveWithLoad()";
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($site, $room, $locationID, $locaiton, $panel, $circuit, $volts, $amps, $status, $load);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		//data title
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Location","Panel","Circuit","Volts","Amps","Load"));
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$longResult.= "<tr class='$rowClass'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>$locaiton</a></td>\n";
				$longResult.= "<td class='data-table-cell'>$panel</td>\n";
				$longResult.= "<td class='data-table-cell'>$circuit</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($volts)."V</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($amps)."A</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($load)."A</td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			
			//show results short
			$shortResult.= FormatSimpleMessage("$count Circuits",3);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_CircuitOverLoaded($threshold=80)
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Circuits past threshhold ($threshold%) utilization";
		$reportNote = "";
		
		//could properly sort circuits, but meh
		$query = "SELECT s.name AS site, l.locationid, r.name, l.name AS location, p.panel, p.circuit, p.volts, p.amps, p.status, p.load, (p.load/p.amps*100) AS utilization, d.deviceid, d.name, c.hno, c.name, p.edituser, p.editdate, p.qauser, p.qadate
			FROM dcim_power AS p 
				LEFT JOIN dcim_powerloc AS pl ON pl.powerid=p.powerid
				LEFT JOIN dcim_location AS l ON l.locationid=pl.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
				LEFT JOIN dcim_device AS d ON l.locationid=d.locationid AND d.status ='A'
				LEFT JOIN dcim_customer AS c ON d.hno=c.hno
			WHERE (p.load/p.amps*100) > $threshold
			GROUP BY p.powerid, c.hno
			ORDER BY s.name, r.name, p.panel, p.circuit";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "Prepare failed: Check_CircuitOverLoaded() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_CircuitOverLoaded()";
		}
				
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($site, $locationID, $room, $location, $panel, $circuit, $volts, $amps, $status, $load, $utilization, $deviceID, $deviceName, $hNo, $customer, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		//data title
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Location","Panel","Circuit","Volts","Amps","Load","Utilization","Reading","Customer"));
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$fullLocationName = FormatLocation($site, $room, $location);
				
				$longResult.= "<tr class='$rowClass'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'>$panel</td>\n";
				$longResult.= "<td class='data-table-cell'>$circuit</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($volts)."V</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($amps)."A</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($load)."A</td>\n";
				$longResult.= "<td class='data-table-cell'><font color=red>".substr($utilization,0,5)."%</font></td>\n";
				$longResult.= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate,date("F jS, Y",strtotime($editDate)), $qaUserID, $qaDate)."</td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a></td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			
			//show results short
			$shortResult.= FormatSimpleMessage("$count Circuits",2);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_VLANLinkedToDisabledPort()
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "VLAN Linked to Disabled Port";
		$reportNote = "These are VLANs linked to ports marked disabled.";
		
		$query = "SELECT dp.deviceid, dp.deviceportid, d.name, d.member, d.model, dp.pic, dp.port, dp.type, dp.status, dp.note, pv.vlan 
				FROM dcim_portvlan AS pv
					 LEFT JOIN dcim_deviceport AS dp ON pv.deviceportid=dp.deviceportid
					 LEFT JOIN dcim_device AS d on dp.deviceid=d.deviceid
				 WHERE dp.status='D'";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "Prepare failed: Check_VLANLinkedToDisabledPort() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_VLANLinkedToDisabledPort()";
		}
				
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($deviceID, $devicePortID, $deviceName, $member, $model, $pic, $port, $type, $status, $note, $vlan);
		$count = $stmt->num_rows;
	
		$shortResult = "";
		$longResult = "";
		//data title
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Device","Port","Status","Vlan","Note"));
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$deviceFullName = GetDeviceFullName($deviceName, $model, $member, true);
				$portFullName = FormatPort($member, $model, $pic, $port, $type);
				
				$longResult.= "<tr class='$rowClass'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'>$portFullName</td>\n";
				$longResult.= "<td class='data-table-cell'>$status</td>\n";
				$longResult.= "<td class='data-table-cell'>$vlan</td>\n";
				$longResult.= "<td class='data-table-cell'>$note</td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			
			//show results short
			$shortResult.= FormatSimpleMessage("$count VLANs",3);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_BadgesWithoutCustomers()
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Badges Without Customers";
		$reportNote = "Disconnected record(s).";
		
		$query = "SELECT c.name AS cust,b.name,b.badgeno, b.hno 
			FROM dcim_badge AS b 
				LEFT JOIN dcim_customer AS c ON c.hno=b.hno
			WHERE c.name IS NULL";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "Prepare failed: Check_BadgesWithoutCustomers() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_BadgesWithoutCustomers()";
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($cust, $name, $badgeNo, $hno);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		//data title
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("H#","Name","Badgeno"));
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$longResult.= "<tr class='$rowClass'>\n";
				$longResult.= "<td class='data-table-cell'>$hno</td>\n";
				$longResult.= "<td class='data-table-cell'>$name</td>\n";
				$longResult.= "<td class='data-table-cell'>$badgeNo</td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			
			//show results short
			$shortResult.= FormatSimpleMessage("$count Badges",3);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_ColoPatch0()
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Colos with patch 0";
		$reportNote= "These are impossible connections left over from old system.";
		
		//could properly sort circuits, but meh
		$query = "SELECT c.name AS cust, c.hno, s.name AS site, l.locationid, r.name AS room, l.name AS loc, d.deviceid, d.name, d.member, d.model, d.status, dp.edituser, dp.editdate, dp.qauser, dp.qadate
			FROM dcim_deviceport AS dp
				LEFT JOIN dcim_device AS d ON d.deviceid=dp.deviceid
				LEFT JOIN dcim_customer AS c ON d.hno=c.hno
				LEFT JOIN dcim_location AS l ON l.locationid=d.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE d.type IN ('F','C','H') AND dp.port=0
			ORDER BY c.name,d.name";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "Prepare failed: Check_ColoPatch0() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_ColoPatch0()";
		}
				
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($customer, $hNo, $site, $locationID, $room, $location, $deviceID, $deviceName, $member, $model, $status, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		//data title
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Customer","Location","Device","Status"),true);
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
			
				$deviceFullName = GetDeviceFullName($deviceName, $model, $member, true);
				$fullLocationName = FormatLocation($site, $room, $location);
					
				$longResult.= "<tr class='$rowClass'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'>".DeviceStatus($status,true)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate,"", $qaUserID, $qaDate)."</td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			
			//show results short
			$shortResult.= FormatSimpleMessage("$count Colos",3);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_BadgesToQA()
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Badges Pending QA";
		$reportNote = "These badges need to be verified in badge server.";
		
		//could properly sort circuits, but meh
		$query = "SELECT c.name AS cust, b.badgeid, b.hno, b.name, b.badgeno, b.status, b.issue, b.hand, b.returned, b.edituser, b.editdate, b.qauser, b.qadate 
			FROM dcim_badge AS b 
				LEFT JOIN dcim_customer AS c ON c.hno=b.hno
			WHERE b.qauser=-1
			ORDER BY cust,name";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "Prepare failed: Check_BadgesToQA() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_BadgesToQA()";
		}
				
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($customer, $badgeID, $hNo, $name, $badgeNo, $status, $issue, $hand, $returned, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Customer","Name","Badgeno","Status","Issue","Enroll"),true);
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$longResult.= "<tr class='$rowClass'>\n";
				$longResult.= "<td class='data-table-cell'>"."<A href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a>"."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($name)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($badgeNo)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".BadgeStatus($status)."</td>\n";
				$longResult.= "<td class='data-table-cell'>$issue</td>\n";
				$longResult.= "<td class='data-table-cell'>$hand</td>\n";
				$longResult.= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate,"", $qaUserID, $qaDate)."</td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			
			//show results short
			$shortResult.= FormatSimpleMessage("$count Pending",2);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_CustomerToQA()
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Customers Pending QA";
		$reportNote = "These just  need the name, status, and account numbers of the customer validated.";
		
		//could properly sort circuits, but meh
		$query = "SELECT c.name, c.hno, c.cno, c.note, c.status, c.edituser, c.editdate, c.qauser, c.qadate 
			FROM dcim_customer AS c
			WHERE c.qauser=-1
			ORDER BY name";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "Prepare failed: Check_CustomerToQA() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_CustomerToQA()";
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($name, $hNo, $cNo, $note, $status, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
	
		$shortResult = "";
		$longResult = "";
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Customer","H#","C#","Status","Note"),true);
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$note = Truncate($note);
				
				$longResult.= "<tr class='$rowClass'>\n";
				$longResult.= "<td class='data-table-cell'>"."<A href='./?host=$hNo'>".MakeHTMLSafe($name)."</a>"."</td>\n";
				$longResult.= "<td class='data-table-cell'>".$hNo."</td>\n";
				$longResult.= "<td class='data-table-cell'>".$cNo."</td>\n";
				$longResult.= "<td class='data-table-cell'>".CustomerStatus($status)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($note)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate,"", $qaUserID, $qaDate)."</td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			
			//show results short
			$shortResult.= FormatSimpleMessage("$count Pending",2);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_DevicesWithoutCustomersOrLocation()
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Devices Without Customer or Location";
		$reportNote = "Disconnected record(s) or in Unknown.";
		
		$query = "SELECT d.hno, d.deviceid, d.name, d.member, d.model, d.locationid, l.locationid, l.name
			FROM dcim_device AS  d
				LEFT JOIN dcim_customer AS c ON d.hno=c.hno
				LEFT JOIN dcim_location AS l ON d.locationid=l.locationid
			WHERE c.name IS NULL OR l.locationid IS NULL OR l.name='Unknown'
			ORDER BY d.name";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "Prepare failed: Check_DevicesWithoutCustomersOrLocation() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_DevicesWithoutCustomersOrLocation()";
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($hno, $deviceID, $deviceName, $member, $model, $locationID, $linkedLocationID,$locationName);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("DeviceID","Device","H#","LocationID","LinkedLocationID","LinkedLocationName"));
				
			//list result data
			$oddRow = false;
			while ($stmt->fetch())
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$deviceFullName = GetDeviceFullName($deviceName, $model, $member, false);
				
				$longResult.= "<tr class='$rowClass'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceID)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?host=$hno'>".MakeHTMLSafe($hno)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($locationID)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$linkedLocationID'>".MakeHTMLSafe($linkedLocationID)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$linkedLocationID'>".MakeHTMLSafe($locationName)."</a></td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			
			//show results short
			$shortResult.= FormatSimpleMessage("$count Devices",3);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_DevicePortsWithoutCustomersOrDevices()
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Device Ports Without Devices or Customers";
		$reportNote = "Disconnected record(s).";
		
		$query = "SELECT dp.deviceportid, d.hno, dp.deviceid, d.name, d.member, d.model, dp.pic, dp.port, dp.type
			FROM dcim_deviceport AS  dp
				LEFT JOIN dcim_device AS d ON dp.deviceid=d.deviceid
				LEFT JOIN dcim_customer AS c ON d.hno=c.hno
			WHERE c.name IS NULL OR d.name IS NULL
			ORDER BY d.name,d.member, dp.deviceid,dp.pic,dp.port";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "Prepare failed: Check_DevicePortsWithoutCustomersOrDevices() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_DevicePortsWithoutCustomersOrDevices()";
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($deviceportid, $hno, $deviceID, $deviceName, $member, $model, $pic, $port, $type);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("DevicePortID","Device","H#","Port"));
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch())
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$deviceFullName = GetDeviceFullName($deviceName, $model, $member, true);
				$portFullName = FormatPort($member, $model, $pic, $port, $type);
				
				$longResult.= "<tr class='$rowClass'>\n";
				$longResult.= "<td class='data-table-cell'>$deviceportid</td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe("#$deviceID - Ref:$deviceFullName")."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?host=$hno'>".MakeHTMLSafe($hno)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($portFullName)."</td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			
			//show results short
			$shortResult.= FormatSimpleMessage("$count Ports",3);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_BadgesActiveUnderInactiveCustomer()
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Active badges where customer is not active";
		$reportNote = "These badges need to be deactivated.";
		
		$query = "SELECT c.name AS cust,b.name,b.badgeno, b.hno 
			FROM dcim_badge AS b 
				LEFT JOIN dcim_customer AS c ON c.hno=b.hno
			WHERE c.status='I' AND NOT b.status IN ('D','R')";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "Prepare failed: Check_BadgesActiveUnderInactiveCustomer() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_BadgesActiveUnderInactiveCustomer()";
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($cust, $name, $badgeNo, $hno);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		//data title
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Customer","Name","Badgeno"));
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$longResult.= "<tr class='$rowClass'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?host=$hno'>".MakeHTMLSafe($cust)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'>$name</td>\n";
				$longResult.= "<td class='data-table-cell'>$badgeNo</td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			
			//show results short
			$shortResult.= FormatSimpleMessage("$count Badges",3);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}

	function Check_DevicesActiveUnderInactiveCustomer()
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Active devices/colos where parent customer is not active";
		$reportNote = "These need to be deactivated.";
		
		$query = "SELECT c.name AS cust,c.hno,d.deviceid,d.name, d.model, d.member
			FROM dcim_device AS d 
				LEFT JOIN dcim_customer AS c ON c.hno=d.hno
			WHERE c.status='I' AND NOT d.status='I'
			ORDER BY c.name, d.name, d.member";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "Prepare failed: Check_DevicesActiveUnderInactiveCustomer() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_DevicesActiveUnderInactiveCustomer()";
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($cust, $hno, $deviceID, $deviceName, $model, $member);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		//data title
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Customer","Device"));
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch())
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$deviceFullName = GetDeviceFullName($deviceName, $model, $member, true);
				
				$longResult.= "<tr class='$rowClass'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?host=$hno'>".MakeHTMLSafe($cust)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			
			//show results short
			$shortResult.= FormatSimpleMessage("$count Devices",3);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_PowerWithoutPowerLoc()
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Power records without any linking location record";
		$reportNote = "Disconnected record(s).";
		
		$query = "SELECT p.powerid, p.panel, p.circuit
			FROM dcim_power AS p
				LEFT JOIN dcim_powerloc AS pl ON p.powerid=pl.powerid
			WHERE pl.powerid IS NULL
			ORDER BY p.panel, p.circuit";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "Prepare failed: Check_PowerWithoutPowerLoc() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_PowerWithoutPowerLoc()";
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($powerID, $panel, $circuit);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("PowerID","Panel","Circuit"));
				
			//list result data
			$oddRow = false;
			while ($stmt->fetch())
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$longResult.= "<tr class='$rowClass'>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($powerID)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($panel)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($circuit)."</td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
		
			//show results short
			$shortResult.= FormatSimpleMessage("$count Circuits",3);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_PowerLocWithoutLocationOrPower()
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Power location records linked to missing records";
		$reportNote = "Disconnected record(s).";
		
		$query = "SELECT pl.powerlocid, pl.powerid, pl.locationid, p.powerid, l.locationid
			FROM dcim_powerloc AS pl
				LEFT JOIN dcim_location AS l ON pl.locationid=l.locationid
				LEFT JOIN dcim_power AS p ON pl.powerid=p.powerid
			WHERE l.locationid IS NULL OR p.powerid IS NULL
			ORDER BY 1";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "Prepare failed: Check_PowerLocWithoutLocationOrPower() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_PowerLocWithoutLocationOrPower()";
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($powerLocID, $powerID, $locationID,$linkedPowerID, $linkedLocationID);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("PowerLocID","PowerID","LocationID","LinkedPowerID","LinkedLocationID"));
				
			//list result data
			$oddRow = false;
			while ($stmt->fetch())
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$longResult.= "<tr class='$rowClass'>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($powerLocID)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($powerID)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($locationID)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($linkedPowerID)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($linkedLocationID)."</td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			
			//show results short
			$shortResult.= FormatSimpleMessage("$count Errors",3);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_LocationWithoutPowerLocOrRoom()
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Location records linked to missing records";
		$reportNote = "Disconnected from room or power (0 power count).";
		
		$query = "SELECT l.locationid, r.name, l.name, l.roomid, r.roomid, COUNT(pl.locationid) AS powerCount
			FROM dcim_location AS l
				LEFT JOIN dcim_powerloc AS pl ON l.locationid=pl.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			GROUP BY l.locationid
			HAVING powerCount<1 OR r.roomid IS NULL
			ORDER BY s.name, r.name, l.name";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "Prepare failed: Check_PowerLocWithoutLocationOrPower() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_PowerLocWithoutLocationOrPower()";
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($locationID, $room, $locationName,$roomID, $linkedRoomID, $powerCount);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("LocationID","Room","Location Name","RoomID","LinkedRoomID","PowerCount"));
				
			//list result data
			$oddRow = false;
			while ($stmt->fetch())
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$longResult.= "<tr class='$rowClass'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($locationID)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($room)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($locationName)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($roomID)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($linkedRoomID)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($powerCount)."</td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			
			//show results short
			$shortResult.= FormatSimpleMessage("$count Locations",2);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_RecordsMisingInsertLog()
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Data Records missing data history";
		$reportNote= "These are records that managed to exist without proper insert log records to match.";
		
		$query = "SELECT cur.* FROM 
					(
						SELECT 'site' AS `table`, s.siteid AS id, sl.siteid AS l_id, sl.logtype FROM dcim_site AS s LEFT JOIN dcimlog_site AS sl ON s.siteid = sl.siteid AND sl.logtype='I'
						UNION SELECT 'badge', b.badgeid, bl.badgeid, bl.logtype FROM dcim_badge AS b LEFT JOIN dcimlog_badge AS bl ON b.badgeid = bl.badgeid AND bl.logtype='I' 
						UNION SELECT 'customer', c.hno, cl.hno, cl.logtype FROM dcim_customer AS c LEFT JOIN dcimlog_customer AS cl ON c.hno = cl.hno AND cl.logtype='I' 
						UNION SELECT 'device', d.deviceid, dl.deviceid, dl.logtype FROM dcim_device AS d LEFT JOIN dcimlog_device AS dl ON d.deviceid = dl.deviceid AND dl.logtype='I' 
						UNION SELECT 'deviceport', dp.deviceportid, dpl.deviceportid, dpl.logtype FROM dcim_deviceport AS dp LEFT JOIN dcimlog_deviceport AS dpl ON dp.deviceportid = dpl.deviceportid AND dpl.logtype='I' 
						UNION SELECT 'location', l.locationid, ll.locationid, ll.logtype FROM dcim_location AS l LEFT JOIN dcimlog_location AS ll ON l.locationid = ll.locationid AND ll.logtype='I' 
						UNION SELECT 'portconnection', pc.portconnectionid, pcl.portconnectionid, pcl.logtype FROM dcim_portconnection AS pc LEFT JOIN dcimlog_portconnection AS pcl ON pc.portconnectionid = pcl.portconnectionid AND pcl.logtype='I' 
						UNION SELECT 'portvlan', pv.portvlanid, pvl.portvlanid, pvl.logtype FROM dcim_portvlan AS pv LEFT JOIN dcimlog_portvlan AS pvl ON pv.portvlanid = pvl.portvlanid AND pvl.logtype='I' 
						UNION SELECT 'power', p.powerid, pl.powerid, pl.logtype FROM dcim_power AS p LEFT JOIN dcimlog_power AS pl ON p.powerid = pl.powerid AND pl.logtype='I' 
						UNION SELECT 'powerloc', pl.powerlocid, pll.powerlocid, pll.logtype FROM dcim_powerloc AS pl LEFT JOIN dcimlog_powerloc AS pll ON pl.powerlocid = pll.powerlocid AND pll.logtype='I' 
						UNION SELECT 'room', r.roomid, rl.roomid, rl.logtype FROM dcim_room AS r LEFT JOIN dcimlog_room AS rl ON r.roomid = rl.roomid AND rl.logtype='I' 
						UNION SELECT 'vlan', v.vlanid, vl.vlanid, vl.logtype FROM dcim_vlan AS v LEFT JOIN dcimlog_vlan AS vl ON v.vlanid = vl.vlanid AND vl.logtype='I'
					) AS cur
					WHERE cur.l_id IS NULL
					ORDER BY 1, 2";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "Prepare failed: Check_RecordsMisingInsertLog() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_RecordsMisingInsertLog()";
		}
				
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($table, $id, $logID, $logType);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		//data title
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Table","ID"));
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch())
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";

				$table = "dcim_".$table;
				$pageKey = GetRecordPageKey($table);
				$idDisplay = $id;
				if($pageKey!=null)
					$idDisplay = "<a href='./?$pageKey=$id'>$id</a>";
				$tableDescription = GetTableRecordDescription($table);
				
				$longResult.= "<tr class='$rowClass'>\n";
				$longResult.= "<td class='data-table-cell'>$tableDescription</td>\n";
				$longResult.= "<td class='data-table-cell'>$idDisplay</td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			
			//show results short
			$shortResult.= FormatSimpleMessage("$count Records",3);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
?>