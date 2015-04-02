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

		//Audit functions
		echo "<div class=\"panel\">\n";
		echo "<div class=\"panel-header\">Audit Functions</div>\n";
		echo "<div class=\"panel-body\">\n";

		echo "<button type='button' style='display:inline;' onClick='parent.location=\"./lib/createReport.php?report=ActiveBadgeList\"'>Export Active Badge List as CSV</button><BR><BR>";
		echo "<button type='button' style='display:inline;' onClick='parent.location=\"./?page=PowerAudit\"'>Power Audit</button>&nbsp;&nbsp;";
		echo "<button type='button' style='display:inline;' onClick='parent.location=\"./lib/createReport.php?report=PowerAudit\"'>Export Current Power Readings as CSV</button> ";
		
		if(UserHasDevPermission())
		{
			//in development features
		}
		echo "</div>\n</div>\n<BR>\n\n";//end panel and panel body
		

		echo "<div class=\"panel\">\n";
		echo "<div class=\"panel-header\">Data to QA</div>\n";
		echo "<div class=\"panel-body\">\n";
		Check_CustomerToQA();
		Check_BadgesToQA();
		echo "</div>\n</div>\n<BR>\n\n";//end panel and panel body
		
		
		echo "<div class=\"panel\">\n";
		echo "<div class=\"panel-header\">Data Inconsistencies</div>\n";
		echo "<div class=\"panel-body\">\n";
		Check_BadgesActiveUnderInactiveCustomer();
		Check_ColoPatch0();
		Check_DevicesActiveUnderInactiveCustomer();
		Check_VLANLinkedToDisabledPort();
		Check_CircuitOverLoaded();
		Check_CircuitInactiveWithLoad();
		//Check_DeviceWithInvalidLocation();
		//Check_SwitchIsMainDeviceOnDevicePortRecords();
		echo "</div>\n</div>\n\n";//end panel and panel body
		
		
		//admin only stuff - just because its stuff they cant fix
		if(UserHasAdminPermission())
		{
			echo "<BR>\n";
			echo "<div class=\"panel\">\n";
			echo "<div class=\"panel-header\">Admin Data Audits</div>\n";
			echo "<div class=\"panel-body\">\n";
			
			$output = "";
			$recCount = CountDBRecords($output);
			CreateReport("Database Record Counts","$recCount records",$output,"");
			
			$lineCount = CountLinesInDir($output);
			CreateReport("Lines of Code","$lineCount lines",$output,"");
			
			Check_BadgesWithoutCustomers();
			Check_DevicesWithoutCustomersOrLocation();
			Check_DevicePortsWithoutCustomersOrDevices();
			Check_LocationWithoutPowerLocOrSite();
			Check_PowerLocWithoutLocationOrPower();
			Check_PowerWithoutPowerLoc();
			echo "</div>\n</div>\n";//end panel and panel body
		}
	}
	
	function Check_CircuitInactiveWithLoad()
	{
		global $mysqli;

		$reportTitle = "Circuits off but still reporting load";
		$reportNote = "";
		
		//could properly sort circuits, but meh
		$query = "SELECT s.name AS site, l.locationid, l.colo, l.name AS location, p.panel, p.circuit, p.volts, p.amps, p.status, p.cload FROM dcim_power AS p 
				LEFT JOIN dcim_powerloc AS pl ON pl.powerid=p.powerid
				LEFT JOIN dcim_location AS l ON l.locationid=pl.locationid
				LEFT JOIN dcim_site AS s ON l.siteid=s.siteid
			WHERE p.status='D' AND p.cload !=0
			ORDER BY 1,2,3";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			echo "Prepare failed: Check_CircuitInactiveWithLoad() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>\n";
			return -1;
		}
				
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($site, $locationID, $colo, $locaiton, $panel, $circuit, $volts, $amps, $status, $cload);
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
				$longResult.= "<td class='data-table-cell'>".FormatPanelName($panel)."</td>\n";
				$longResult.= "<td class='data-table-cell'>$circuit</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($volts)."V</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($amps)."A</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($cload)."A</td>\n";
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
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_CircuitOverLoaded($threshold=80)
	{
		global $mysqli;

		$reportTitle = "Circuits past threshhold ($threshold%) utilization";
		$reportNote = "";
		
		//could properly sort circuits, but meh
		$query = "SELECT s.name AS site, l.locationid, l.colo, l.name AS location, p.panel, p.circuit, p.volts, p.amps, p.status, p.cload, (p.cload/p.amps*100) AS utilization, d.deviceid, d.name, c.hno, c.name
			FROM dcim_power AS p 
				LEFT JOIN dcim_powerloc AS pl ON pl.powerid=p.powerid
				LEFT JOIN dcim_location AS l ON l.locationid=pl.locationid
				LEFT JOIN dcim_site AS s ON l.siteid=s.siteid
				LEFT JOIN dcim_device AS d ON l.locationid=d.locationid
				LEFT JOIN dcim_customer AS c ON d.hno=c.hno
			WHERE (p.cload/p.amps*100) > $threshold
			ORDER BY 1,2,3";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			echo "Prepare failed: Check_CircuitOverLoaded() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>\n";
			return -1;
		}
				
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($site, $locationID, $colo, $location, $panel, $circuit, $volts, $amps, $status, $cload, $utilization, $deviceID, $deviceName, $hNo, $customer);
		$count = $stmt->num_rows;
	
		$shortResult = "";
		$longResult = "";
		//data title
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Location","Panel","Circuit","Volts","Amps","Load","Utilization","Customer"));
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";

				$fullLocationName = FormatLocation($site, $colo, $location);
				
				$longResult.= "<tr class='$rowClass'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'>".FormatPanelName($panel)."</td>\n";
				$longResult.= "<td class='data-table-cell'>$circuit</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($volts)."V</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($amps)."A</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($cload)."A</td>\n";
				$longResult.= "<td class='data-table-cell'><font color=red>".substr($utilization,0,5)."%</font></td>\n";
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
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_VLANLinkedToDisabledPort()
	{
		global $mysqli;
		
		$reportTitle = "VLAN Linked to Disabled Port";
		$reportNote = "These are VLANs linked to ports marked disabled.";
		
		$query = "SELECT dp.deviceid, dp.deviceportid, d.name, d.member, d.model, dp.pic, dp.port, dp.type, dp.status, dp.note, pv.vlan 
				FROM dcim_portvlan AS pv
					 LEFT JOIN dcim_deviceport AS dp ON pv.deviceportid=dp.deviceportid
					 LEFT JOIN dcim_device AS d on dp.deviceid=d.deviceid
				 WHERE dp.status='D'";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			echo "Prepare failed: Check_VLANLinkedToDisabledPort() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>\n";
			return -1;
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
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_BadgesWithoutCustomers()
	{
		global $mysqli;
		
		$reportTitle = "Badges Without Customers";
		$reportNote = "Disconnected record(s).";
		
		$query = "SELECT c.name AS cust,b.name,b.badgeno, b.hno 
			FROM dcim_badge AS b 
				LEFT JOIN dcim_customer AS c ON c.hno=b.hno
			WHERE c.name IS NULL";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			echo "Prepare failed: Check_BadgesWithoutCustomers() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>\n";
			return -1;
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
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_ColoPatch0()
	{
		global $mysqli;
		
		$reportTitle = "Colos with patch 0";
		$reportNote= "These are impossible connections left over from old system.";
		
		//could properly sort circuits, but meh
		$query = "SELECT c.name AS cust, c.hno, s.name AS site, l.locationid, l.colo, l.name AS loc, d.deviceid, d.name, d.member, d.model, d.status, dp.edituser, dp.editdate, dp.qauser, dp.qadate
			FROM dcim_deviceport AS dp
				LEFT JOIN dcim_device AS d ON d.deviceid=dp.deviceid
				LEFT JOIN dcim_customer AS c ON d.hno=c.hno
				LEFT JOIN dcim_location AS l ON l.locationid=d.locationid
				LEFT JOIN dcim_site AS s ON l.siteid=s.siteid
			WHERE d.type IN ('F','C','H') AND dp.port=0
			ORDER BY cust,name";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			echo "Prepare failed: Check_ColoPatch0() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>\n";
			return -1;
		}
				
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($customer, $hNo, $site, $locationID, $colo, $location, $deviceID, $deviceName, $member, $model, $status, $editUserID, $editDate, $qaUserID, $qaDate);
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
				$fullLocationName = FormatLocation($site, $colo, $location);
					
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
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_BadgesToQA()
	{
		global $mysqli;
		
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
			echo "Prepare failed: Check_BadgesToQA() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>\n";
			return -1;
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
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_CustomerToQA()
	{
		global $mysqli;
		
		$reportTitle = "Customers Pending QA";
		$reportNote = "These just  need the name, status, and account numbers of the customer validated.";
		
		//could properly sort circuits, but meh
		$query = "SELECT c.name, c.hno, c.cno, c.note, c.status, c.edituser, c.editdate, c.qauser, c.qadate 
			FROM dcim_customer AS c
			WHERE c.qauser=-1
			ORDER BY name";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			echo "Prepare failed: Check_CustomerToQA() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>\n";
			return -1;
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
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_DevicesWithoutCustomersOrLocation()
	{
		global $mysqli;
		
		$reportTitle = "Devices Without Customer or Location";
		$reportNote = "Disconnected record(s).";

		$query = "SELECT d.hno, d.deviceid, d.name, d.member, d.model, d.locationid, l.locationid, l.name
			FROM dcim_device AS  d
				LEFT JOIN dcim_customer AS c ON d.hno=c.hno
				LEFT JOIN dcim_location AS l ON d.locationid=l.locationid
			WHERE c.name IS NULL OR l.locationid IS NULL OR l.name='Unknown'
			ORDER BY d.name";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			echo "Prepare failed: Check_DevicesWithoutCustomersOrLocation() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>\n";
			return -1;
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($hno, $deviceID, $deviceName, $member, $model, $locationID, $refLocationID,$locationName);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("DeviceID","Device","H#","LocationID","RefLocationID","RefLocationName"));
				
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
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$refLocationID'>".MakeHTMLSafe($refLocationID)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$refLocationID'>".MakeHTMLSafe($locationName)."</a></td>\n";
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
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_DevicePortsWithoutCustomersOrDevices()
	{
		global $mysqli;
	
		$reportTitle = "Device Ports Without Customers or Devices";
		$reportNote = "Disconnected record(s).";
	
		$query = "SELECT dp.deviceportid, d.hno, dp.deviceid, d.name, d.member, d.model, dp.pic, dp.port, dp.type
			FROM dcim_deviceport AS  dp
				LEFT JOIN dcim_device AS d ON dp.deviceid=d.deviceid
				LEFT JOIN dcim_customer AS c ON d.hno=c.hno
			WHERE c.name IS NULL OR d.name IS NULL
			ORDER BY d.name,d.member,dp.pic,dp.port";
	
		if (!($stmt = $mysqli->prepare($query)))
		{
			echo "Prepare failed: Check_DevicePortsWithoutCustomersOrDevices() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>\n";
			return -1;
		}
	
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($deviceportid, $hno, $deviceID, $deviceName, $member, $model, $pic, $port, $type);
		$count = $stmt->num_rows;
	
		$shortResult = "";
		$longResult = "";
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("H#","Device","Port"));
	
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
				$longResult.= "<td class='data-table-cell'><a href='./?host=$hno'>".MakeHTMLSafe($hno)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>\n";
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
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_BadgesActiveUnderInactiveCustomer()
	{
		global $mysqli;
		
		$reportTitle = "Active badges where customer is not active";
		$reportNote = "These badges need to be deactivated.";
		
		$query = "SELECT c.name AS cust,b.name,b.badgeno, b.hno 
			FROM dcim_badge AS b 
				LEFT JOIN dcim_customer AS c ON c.hno=b.hno
			WHERE c.status='I' AND NOT b.status IN ('D','R')";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			echo "Prepare failed: Check_BadgesActiveUnderInactiveCustomer() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>\n";
			return -1;
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
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}

	function Check_DevicesActiveUnderInactiveCustomer()
	{
		global $mysqli;
	
		$reportTitle = "Active devices/colos where parent customer is not active";
		$reportNote = "These need to be deactivated.";
	
		$query = "SELECT c.name AS cust,c.hno,d.deviceid,d.name, d.model, d.member
			FROM dcim_device AS d 
				LEFT JOIN dcim_customer AS c ON c.hno=d.hno
			WHERE c.status='I' AND NOT d.status='I'";
	
		if (!($stmt = $mysqli->prepare($query)))
		{
			echo "Prepare failed: Check_DevicesActiveUnderInactiveCustomer() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>\n";
			return -1;
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
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_PowerWithoutPowerLoc()
	{
		global $mysqli;
		
		$reportTitle = "Power records without any linking location record";
		$reportNote = "Disconnected record(s).";

		$query = "SELECT p.powerid, p.panel, p.circuit
			FROM dcim_power AS p
				LEFT JOIN dcim_powerloc AS pl ON p.powerid=pl.powerid
			WHERE pl.powerid IS NULL
			ORDER BY p.panel, p.circuit";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			echo "Prepare failed: Check_PowerWithoutPowerLoc() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>\n";
			return -1;
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
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_PowerLocWithoutLocationOrPower()
	{
		global $mysqli;
		
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
			echo "Prepare failed: Check_PowerLocWithoutLocationOrPower() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>\n";
			return -1;
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($powerLocID, $powerID, $locationID,$refPowerID, $refLocationID);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("PowerLocID","PowerID","LocationID","RefPowerID","RefLocationID"));
				
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
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($refPowerID)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($refLocationID)."</td>\n";
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
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_LocationWithoutPowerLocOrSite()
	{
		global $mysqli;
		
		$reportTitle = "Location records linked to missing records";
		$reportNote = "Disconnected from site or power.";

		$query = "SELECT l.locationid, l.colo, l.name, l.siteid, s.siteid, COUNT(pl.locationid) AS powerCount
			FROM dcim_location AS l
				LEFT JOIN dcim_powerloc AS pl ON l.locationid=pl.locationid
				LEFT JOIN dcim_site AS s ON l.siteid=s.siteid
			GROUP BY l.locationid
			HAVING powerCount<1 OR s.siteid IS NULL";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			echo "Prepare failed: Check_PowerLocWithoutLocationOrPower() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>\n";
			return -1;
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($locationID, $colo, $locationName,$siteID, $refSiteID, $powerCount);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("LocationID","Colo","Location Name","SiteID","RefSiteID","PowerCount"));
				
			//list result data
			$oddRow = false;
			while ($stmt->fetch())
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$longResult.= "<tr class='$rowClass'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($locationID)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($colo)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($locationName)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($siteID)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($refSiteID)."</td>\n";
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
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
?>