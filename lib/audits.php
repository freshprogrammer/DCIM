<?php 
	//This is a set of functions used for gathering and displaying data audits.
	/*
		::need to add::
		Multiple Devices connencted to a single device port
		location without 2x power
		verify location.status if a device is linked
		power amps at 0 or negative value
		port with connection thats not active - or visa versa
		port linked to invalid port - basicly any invalid ports in table
		port linked to more than 1  port- portid in portconnectiontable more than once
		unknown stati in all tables
	*/
	
	//this function is inserted into page layout
	function BuildAuditsPage($siteIDFilter)
	{
		global $mysqli;
		global $errorMessage;
		global $pageSubTitle;
		global $config_badgesEnabled;
		global $config_subnetsEnabled;
		global $config_dbVersion;
		global $config_codeVersion;
		
		if($siteIDFilter==null || strlen($siteIDFilter)==0)$siteIDFilter=-1;
		if($siteIDFilter!=-1)
		{
			$query = "SELECT s.siteid, s.name, s.fullname
				FROM dcim_site AS s
				WHERE s.siteid=?
				LIMIT 1";
			
			if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
			{
				$errorMessage[] = "Prepare failed: BuildAuditsPage($siteIDFilter) - (" . $mysqli->errno . ") " . $mysqli->error;
			}
			
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($siteID, $siteName, $siteFullName);
			if($stmt->num_rows>0) $stmt->fetch();
			else
			{
				$errorMessage[] = "Failed to location site#$siteIDFilter";
				$siteIDFilter = -1;
			}
		}
		
		if($siteIDFilter==-1)
		{
			$siteName = "All Sites";
			$siteFullName = $siteName;
			$siteIDFilter = "%";
		}
		
		$pageSubTitle = "Data Audits - $siteFullName";
		$result = "";
		
		//Audit Tools
		$result .= "<div class=\"panel\">\n";
		$result .= "<div class=\"panel-header\">Audit Tools</div>\n";
		$result .= "<div class=\"panel-body\">\n";
		
		if(UserHasAdminPermission())
		{
			$result .= "(Admin) - <a class='' href='./?page=Audits_History'>History Audits</a>\n";
			if($siteIDFilter!=-1)
			{
				$result .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
				$result .= "<a class='' href='./?page=Audits&siteid=-1'>All Sites Audits</a>\n";
			}
			$result .= "</BR></BR>\n";
		}
		
		/*disabled till multi site support  - this outputs all badges in DCIM when you probably want just badges for that site - requires filtereing baced on customers at site
		if($config_badgesEnabled) $result .= "<button type='button' style='display:inline;' onClick='parent.location=\"./lib/createReport.php?report=ActiveBadgeList\"'>Export $siteFullName Active Badge List as CSV</button><BR><BR>";
		*/
		
		/*disabled till multi site support
		$result .= "<div id='rppAuditHelpPopup' class='helpPopup'>".CustomFunctions::RemotePowerPanelAuditHelpPopup()."</div>";
		$result .= "<a class='' href='javascript:void(0)' onclick = \"CreatePopup('rppAuditHelpPopup');\">Create Remote Power Panel Audit Form</a>\n<BR><BR>";
		*/
		
		$result .= "<button type='button' title='Creates a spreadsheet listing all power circuits sorted and grouped by location with customer names. CSV format but for viewing, not import.' style='display:inline;' onClick='parent.location=\"./lib/createReport.php?report=PowerLocationList&siteid=$siteIDFilter\"'>Export $siteFullName Location Power Readings as CSV</button>";
		
		if(CustomFunctions::UserHasDevPermission())
		{
			//in development features go here
		}
		$result .= "</div>\n</div>\n\n";//end panel and panel body
		
		
		$result .= "<div class=\"panel\">\n";
		$result .= "<div class=\"panel-header\">$siteFullName - Data to QA</div>\n";
		$result .= "<div class=\"panel-body\">\n";
		$result .= Check_CustomersToQA($siteIDFilter);
		if($config_badgesEnabled)
			$result .= Check_BadgesToQA($siteIDFilter);
		$result .= Check_DevicesToQA($siteIDFilter);
		$result .= Check_LocationsToQA($siteIDFilter);
		$result .= "</div>\n</div>\n\n";//end panel and panel body
		
		$result .= "<div class=\"panel\">\n";
		$result .= "<div class=\"panel-header\">$siteFullName - Data Changes</div>\n";
		$result .= "<div class=\"panel-body\">\n";
		$result .= Check_CustomersRecentlyUpdated($siteIDFilter);
		$result .= Check_DevicesRecentlyUpdated($siteIDFilter);
		$result .= Check_DevicesRecentlyChangedStatus($siteIDFilter);
		$result .= "</div>\n</div>\n\n";//end panel and panel body
		
		$result .= "<div class=\"panel\">\n";
		$result .= "<div class=\"panel-header\">$siteFullName - Customer/Device Audits</div>\n";
		$result .= "<div class=\"panel-body\">\n";
		if($config_badgesEnabled)
			$result .= Check_BadgesActiveUnderInactiveCustomer($siteIDFilter);
		$result .= Check_ColoPatch0($siteIDFilter);
		$result .= Check_DevicesActiveUnderInactiveCustomer($siteIDFilter);
		if($config_subnetsEnabled)
			$result .= Check_VLANLinkedToDisabledPort($siteIDFilter);
		$result .= Check_DeviceWithoutAsset($siteIDFilter);
		$result .= Check_DeviceWithDuplicateAsset($siteIDFilter);
		$result .= Check_DevicesWithUnknownModel($siteIDFilter);
		$result .= "</div>\n</div>\n\n";//end panel and panel body
		
		$result .= "<div class=\"panel\">\n";
		$result .= "<div class=\"panel-header\">$siteFullName - Location/Power Audits</div>\n";
		$result .= "<div class=\"panel-body\">\n";
		$result .= Check_ActiveLocationWithoutPower($siteIDFilter);
		$result .= Check_LocationAllocation_IncorrectEmpty($siteIDFilter);
		$result .= Check_LocationAllocation_ShouldBeColo($siteIDFilter);
		$result .= Check_CircuitOverLoaded($siteIDFilter);
		$result .= Check_CircuitInactiveWithLoad($siteIDFilter);
		$result .= Check_PowerWithoutPowerLoc($siteIDFilter);
		$result .= "</div>\n</div>\n\n";//end panel and panel body
		
		//admin only stuff - just because its stuff they cant fix
		if(UserHasAdminPermission())
		{
			$result .= "<div class=\"panel\">\n";
			$result .= "<div class=\"panel-header\">Admin Data Audits - These should always be green, if not alert senior admin to investigate</div>\n";
			$result .= "<div class=\"panel-body\">\n";
			
			$result .= "PHP v".phpversion()." - Mysql v".GetMySqlVersion()." - DCIM v".$config_codeVersion." - DCIM DB v".$config_dbVersion."<BR>\n";
			
			$output = "";
			$lineCount = CountLinesInDir($output);
			$result .= "<a href='https://github.com/freshprogrammer/DCIM' target='_blank'>Source on GitHub</a><BR/>";
			$result .= CreateReport("Lines of Code","$lineCount lines",$output,"");
			
			$recCount = 0;
			$output = CreateTableRowCountTable($recCount);
			$result .= "<a href='https://github.com/freshprogrammer/DCIM/blob/master/documentation/database_structure.md' target='_blank'>DB Documentation on GitHub</a><BR/>";
			$result .= CreateReport("Database Record Counts","$recCount records",$output,"");
			
			$result .= Check_BadgesWithoutCustomers();
			$result .= Check_DevicesWithoutCustomersOrLocation();
			$result .= Check_DevicePortsWithoutCustomersOrDevices();
			$result .= Check_LocationWithoutRoom();
			$result .= Check_PowerLocWithoutLocationOrPower();
			$result .= "</div>\n</div>\n";//end panel and panel body
		}
		return $result;
	}
	
	function BuildAuditsHistoryPage()
	{
		global $pageSubTitle;
		$pageSubTitle = "Data History Audits";
		$result = "";
		
		//admin only stuff - just because its stuff they cant fix
		if(UserHasAdminPermission())
		{
			$result .= "<div class=\"panel\">\n";
			$result .= "<div class=\"panel-header\">Admin Data Audits</div>\n";
			$result .= "<div class=\"panel-body\">\n";
			$result .= Check_RecordsMisingInsertLog();
			$result .= "</div>\n</div>\n";//end panel and panel body
			
			$result .= "<div class=\"panel\">\n";
			$result .= "<div class=\"panel-header\">Admin Data Audits - Untracked data changes</div>\n";
			$result .= "<div class=\"panel-body\">\n";
			$result .= Check_RecordLogOutOfSync_AllTables();
			$result .= "</div>\n</div>\n";//end panel and panel body
		}
		else
		{
			CreateMessagePanel("History Audits","You do dont hvae access to view this page");
		}
		
		return $result;
	}
	
	function Check_CircuitInactiveWithLoad()
	{
		global $mysqli;
		global $errorMessage;
		$reportTitle = "Circuits off but still reporting load"; $reportNote = "";
		
		$query = "SELECT s.name AS site, r.name, l.locationid, l.name AS location, pp.powerpanelid, pp.name, pc.circuit, pc.volts, pc.amps, pc.status, pc.load 
			FROM dcim_powercircuit AS pc
				LEFT JOIN dcim_powercircuitloc AS pcl ON pc.powercircuitid=pcl.powercircuitid
				LEFT JOIN dcim_powerpanel AS pp ON pp.powerpanelid=pc.powerpanelid
				LEFT JOIN dcim_location AS l ON l.locationid=pcl.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE s.siteid LIKE ? AND pc.status='D' AND pc.load !=0
			ORDER BY s.name,r.name,l.name,pp.name,pc.circuit";
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_CircuitInactiveWithLoad() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_CircuitInactiveWithLoad()";
		}
		$stmt->store_result();
		$stmt->bind_result($site, $room, $locationID, $locaiton, $powerPanelID, $panel, $circuit, $volts, $amps, $status, $load);
		$shortResult = ""; $longResult = "";
		if($stmt->num_rows>0)
		{
			$longResult.= CreateDataTableHeader(array("Location","Panel","Circuit","Volts","Amps","Load"));
			while ($stmt->fetch()) 
			{
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>$locaiton</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?powerpanelid=$powerPanelID'>".MakeHTMLSafe($panel)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'>$circuit</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($volts)."V</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($amps)."A</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($load)."A</td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			$shortResult.= FormatSimpleMessage($stmt->num_rows." Circuits",3);
		}
		else $shortResult.= FormatSimpleMessage("All Good",1);
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_CircuitOverLoaded($siteIDFilter, $threshold=80)
	{
		global $mysqli;
		global $errorMessage;
		$reportTitle = "Circuits past threshhold ($threshold%) utilization";
		$reportNote = "";
		
		$query = "SELECT s.name AS site, l.locationid, r.name, l.name AS location, pp.powerpanelid, pp.name, pc.circuit, pc.volts, pc.amps, pc.status, pc.load, (pc.load/pc.amps*100) AS utilization, d.deviceid, d.name, c.hno, c.name, pc.edituser, pc.editdate, pc.qauser, pc.qadate
			FROM dcim_powercircuit AS pc
				LEFT JOIN dcim_powercircuitloc AS pcl ON pc.powercircuitid=pcl.powercircuitid
				LEFT JOIN dcim_powerpanel AS pp ON pp.powerpanelid=pc.powerpanelid
				LEFT JOIN dcim_location AS l ON l.locationid=pcl.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
				LEFT JOIN dcim_device AS d ON l.locationid=d.locationid AND d.status ='A'
				LEFT JOIN dcim_customer AS c ON d.hno=c.hno
			WHERE s.siteid LIKE ? AND (pc.load/pc.amps*100) > $threshold
			GROUP BY pc.powercircuitid, c.hno
			ORDER BY s.name, r.name, pp.name, pc.circuit";
		
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_CircuitOverLoaded() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_CircuitOverLoaded()";
		}
		$stmt->store_result();
		$stmt->bind_result($site, $locationID, $room, $location, $powerPanelID, $panel, $circuit, $volts, $amps, $status, $load, $utilization, $deviceID, $deviceName, $hNo, $customer, $editUserID, $editDate, $qaUserID, $qaDate);
		$shortResult = ""; $longResult = "";
		if($stmt->num_rows>0)
		{
			$longResult.= CreateDataTableHeader(array("Location","Panel","Circuit","Volts","Amps","Load","Utilization","Reading","Customer"));
			while ($stmt->fetch()) 
			{
				$fullLocationName = FormatLocation($site, $room, $location);
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?powerpanelid=$powerPanelID'>".MakeHTMLSafe($panel)."</a></td>\n";
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
			$shortResult.= FormatSimpleMessage($stmt->num_rows." Circuits",2);
		}
		else $shortResult.= FormatSimpleMessage("All Good",1);
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_VLANLinkedToDisabledPort($siteIDFilter)
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "VLAN Linked to Disabled Port";
		$reportNote = "These are VLANs linked to ports marked disabled.";
		
		$query = "SELECT dp.deviceid, dp.deviceportid, d.name, d.altname, d.member, d.model, dp.pic, dp.port, dp.type, dp.status, dp.note, pv.vlan 
				FROM dcim_portvlan AS pv
					LEFT JOIN dcim_deviceport AS dp ON pv.deviceportid=dp.deviceportid
					LEFT JOIN dcim_device AS d on dp.deviceid=d.deviceid
					INNER JOIN dcim_location AS l ON l.locationid=d.locationid
					INNER JOIN dcim_room AS r ON r.roomid=l.roomid
					INNER JOIN dcim_site AS s ON s.siteid=r.siteid
				WHERE s.siteid LIKE ? AND dp.status='D'
				ORDER BY s.name, d.name, d.member, dp.port";
		
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_VLANLinkedToDisabledPort() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_VLANLinkedToDisabledPort()";
		}
		$stmt->store_result();
		$stmt->bind_result($deviceID, $devicePortID, $deviceName,$deviceAltName, $member, $model, $pic, $port, $type, $status, $note, $vlan);
		$count = $stmt->num_rows;
	
		$shortResult = "";
		$longResult = "";
		//data title
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Device","Port","Status","Vlan","Note"));
			
			//list result data
			while ($stmt->fetch()) 
			{
				$deviceFullName = GetDeviceFullName($deviceName, $model, $member,$deviceAltName, true);
				$portFullName = FormatPort($member, $model, $pic, $port, $type);
				
				$longResult.= "<tr class='dataRow'>\n";
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
			while ($stmt->fetch()) 
			{
				$longResult.= "<tr class='dataRow'>\n";
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
	
	function Check_ColoPatch0($siteIDFilter)
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Colos with patch 0";
		$reportNote= "These are impossible connections left over from old system.";
		
		$query = "SELECT c.name AS cust, c.hno, s.name AS site, l.locationid, r.name AS room, l.name AS loc, d.deviceid, d.name, d.altname, d.member, d.model, d.status, dp.edituser, dp.editdate, dp.qauser, dp.qadate
			FROM dcim_deviceport AS dp
				LEFT JOIN dcim_device AS d ON d.deviceid=dp.deviceid
				LEFT JOIN dcim_customer AS c ON d.hno=c.hno
				LEFT JOIN dcim_location AS l ON l.locationid=d.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE s.siteid LIKE ? AND d.type IN ('F','C','H') AND dp.port=0
			ORDER BY s.name, c.name,d.name";
		
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_ColoPatch0() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_ColoPatch0()";
		}
		$stmt->store_result();
		$stmt->bind_result($customer, $hNo, $site, $locationID, $room, $location, $deviceID, $deviceName,$deviceAltName, $member, $model, $status, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		//data title
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Location","Customer","Device","Status"),true);
			
			//list result data
			while ($stmt->fetch()) 
			{
				$deviceFullName = GetDeviceFullName($deviceName, $model, $member,$deviceAltName, true);
				$fullLocationName = FormatLocation($site, $room, $location);
				
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a></td>\n";
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
	
	function Check_DeviceWithoutAsset($siteIDFilter)
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Devices Missing assets";
		$reportNote= "These are active physical devices that do not have assets.";
		
		$query = "SELECT d.deviceid, s.name AS site, r.name AS room, d.hno, c.name, l.locationid, l.name AS loc, l.note, d.unit, d.name, d.altname, d.member, d.size, d.type, d.status, d.note, d.asset, d.serial, d.model, d.edituser, d.editdate, d.qauser, d.qadate
			FROM dcim_device AS d
				LEFT JOIN dcim_location AS l ON d.locationid=l.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
				LEFT JOIN dcim_customer AS c ON d.hno=c.hno
			WHERE s.siteid LIKE ? AND d.status='A' AND d.type='S' AND d.asset=''
			ORDER BY s.name, d.status, site, room, loc, unit, d.name, member";
		
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_DeviceWithoutAsset() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_DeviceWithoutAsset()";
		}
		$stmt->store_result();
		$stmt->bind_result($deviceID, $site, $room, $hNo, $customer, $locationID, $location, $locationNote, $unit, $name,$deviceAltName, $member, $size, $type, $status, $notes, $asset, $serial, $model, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		//data title
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Location","Unit","Device","Model","Size","Type","Asset","Status","Notes"),true);
			
			//list result data
			while ($stmt->fetch()) 
			{
				$visibleNotes = TruncateWithSpanTitle(MakeHTMLSafe(htmlspecialchars($notes)));
				$deviceFullName = GetDeviceFullName($name, $model, $member,$deviceAltName, true);
				$fullLocationName = FormatLocation($site, $room, $location);
				
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>";
				$longResult.= "<td class='data-table-cell'>$unit</td>";
				$longResult.= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>";
				//$longResult.= "<td class='data-table-cell'><a href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a></td>";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($model)."</td>";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($size)."</td>";
				$longResult.= "<td class='data-table-cell'>".DeviceType($type)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($asset)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".DeviceStatus($status)."</td>\n";
				$longResult.= "<td class='data-table-cell'>$visibleNotes</td>";
				$longResult.= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			$shortResult.= FormatSimpleMessage("$count Devices",2);
		}
		else $shortResult.= FormatSimpleMessage("All Good",1);
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_DeviceWithDuplicateAsset($siteIDFilter)
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Devices with duplicate assets";
		$reportNote= "These are active physical devices that have identical assets.";
		
		$query = "SELECT d.deviceid, s.name AS site, r.name AS room, d.hno, c.name, l.locationid, l.name AS loc, l.note, d.unit, d.name, d.altname, d.member, d.size, d.type, d.status, d.note, d.asset, d.serial, d.model, d.edituser, d.editdate, d.qauser, d.qadate
			FROM (SELECT d.deviceid, d.name, d.asset, COUNT(d.asset) AS count
					FROM dcim_device AS d
					WHERE d.status='A' AND d.asset<>''
					GROUP BY d.asset
					HAVING count>1) AS cur
				LEFT JOIN dcim_device AS d ON d.asset=cur.asset
				LEFT JOIN dcim_location AS l ON d.locationid=l.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
				LEFT JOIN dcim_customer AS c ON d.hno=c.hno
			WHERE s.siteid LIKE ?
			ORDER BY s.name, d.asset, d.name, d.member";
		
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_DeviceWithDuplicateAsset() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_DeviceWithDuplicateAsset()";
		}
		$stmt->store_result();
		$stmt->bind_result($deviceID, $site, $room, $hNo, $customer, $locationID, $location, $locationNote, $unit, $name,$deviceAltName, $member, $size, $type, $status, $notes, $asset, $serial, $model, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		//data title
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Device","Location","Unit","Model","Size","Type","Asset","Status","Notes"),true);
			while ($stmt->fetch())
			{
				$visibleNotes = TruncateWithSpanTitle(MakeHTMLSafe(htmlspecialchars($notes)));
				$deviceFullName = GetDeviceFullName($name, $model, $member,$deviceAltName, true);
				$fullLocationName = FormatLocation($site, $room, $location);
				
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>";
				//$longResult.= "<td class='data-table-cell'><a href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a></td>";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>";
				$longResult.= "<td class='data-table-cell'>$unit</td>";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($model)."</td>";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($size)."</td>";
				$longResult.= "<td class='data-table-cell'>".DeviceType($type)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($asset)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".DeviceStatus($status)."</td>\n";
				$longResult.= "<td class='data-table-cell'>$visibleNotes</td>";
				$longResult.= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			$shortResult.= FormatSimpleMessage("$count Devices",3);
		}
		else $shortResult.= FormatSimpleMessage("All Good",1);
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_DevicesRecentlyUpdated($siteIDFilter, $days=90)
	{
		global $mysqli;
		global $errorMessage;
		$reportTitle = "Recent Device Changes";
		$reportNote= "These devices have been updated in the past $days days.";
		
		$query = "SELECT * FROM (SELECT d.deviceid, s.name AS site, r.name AS room, d.hno, c.name, l.locationid, l.name AS loc, d.unit, d.name AS devicename, d.altname, d.member, d.size, d.type, d.status, d.note, d.asset, d.serial, d.model, d.edituser, d.editdate as editdate, d.qauser, d.qadate, d.logtype
				FROM dcimlog_device AS d
					LEFT JOIN dcim_location AS l ON d.locationid=l.locationid
					LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
					LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
					LEFT JOIN dcim_customer AS c ON d.hno=c.hno
				WHERE s.siteid LIKE ? AND d.editdate BETWEEN NOW() - INTERVAL $days DAY AND NOW()
				ORDER BY d.editdate DESC) AS cur
			GROUP BY cur.deviceid
			ORDER BY cur.editdate DESC";
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_DevicesRecentlyUpdated() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_DevicesRecentlyUpdated()";
		}
		$stmt->store_result();
		$stmt->bind_result($deviceID, $site, $room, $hNo, $customer, $locationID, $location, $unit, $name,$deviceAltName, $member, $size, $type, $status, $notes, $asset, $serial, $model, $editUserID, $editDate, $qaUserID, $qaDate, $logType);
		$shortResult = ""; $longResult = "";
		if($stmt->num_rows>0)
		{
			$longResult.= CreateDataTableHeader(array("Device","Location","Unit","Model","Size","Type","Asset","Status","Notes","Tech","Action","Date&#x25BC;"));
			while ($stmt->fetch())
			{
				$visibleNotes = TruncateWithSpanTitle(MakeHTMLSafe(htmlspecialchars($notes)));
				$deviceFullName = GetDeviceFullName($name, $model, $member,$deviceAltName, true);
				$fullLocationName = FormatLocation($site, $room, $location);
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>";
				//$longResult.= "<td class='data-table-cell'><a href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a></td>";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>";
				$longResult.= "<td class='data-table-cell'>$unit</td>";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($model)."</td>";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($size)."</td>";
				$longResult.= "<td class='data-table-cell'>".DeviceType($type)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($asset)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".DeviceStatus($status)."</td>\n";
				$longResult.= "<td class='data-table-cell'>$visibleNotes</td>";
				$longResult.= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
				$longResult.= "<td class='data-table-cell'>".DBLogType($logType,true)."</td>";
				$longResult.= "<td class='data-table-cell'>$editDate</td>";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			$shortResult.= FormatSimpleMessage($stmt->num_rows." Devices",1);
		}
		else $shortResult.= FormatSimpleMessage("All Good",1);
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	
	
	function Check_CustomersRecentlyUpdated($siteIDFilter, $days=90)
	{
		global $mysqli;
		global $errorMessage;
		$reportTitle = "Recent Customer Changes";
		$reportNote= "These customers have been updated in the past $days days.";
		
		$query = "SELECT * FROM (SELECT c.hno, c.cno, c.name, c.note, c.edituser, c.editdate, c.qauser, c.qadate, c.logtype
			FROM dcim_device AS d
				INNER JOIN dcim_location AS l ON d.locationid=l.locationid
				INNER JOIN dcim_room AS r ON l.roomid=r.roomid
				INNER JOIN dcim_site AS s ON r.siteid=s.siteid
				INNER JOIN dcimlog_customer AS c ON d.hno=c.hno
			WHERE s.siteid LIKE ? AND c.editdate BETWEEN NOW() - INTERVAL $days DAY AND NOW()
			ORDER BY c.editdate DESC) AS cur
		GROUP BY cur.hno
		ORDER BY cur.editdate DESC";
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_CustomersRecentlyUpdated() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_CustomersRecentlyUpdated()";
		}
		$stmt->store_result();
		$stmt->bind_result($hNo, $cNo, $customer, $notes, $editUserID, $editDate, $qaUserID, $qaDate, $logType);
		$shortResult = ""; $longResult = "";
		if($stmt->num_rows>0)
		{
			$longResult.= CreateDataTableHeader(array("Customer","Hosting#","Customer#","Note","Tech","Action","Date&#x25BC;"));
			while ($stmt->fetch())
			{
				$visibleNotes = TruncateWithSpanTitle(MakeHTMLSafe(htmlspecialchars($notes)));
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a></td>";
				$longResult.= "<td class='data-table-cell'>$hNo</td>";
				$longResult.= "<td class='data-table-cell'>$cNo</td>";
				$longResult.= "<td class='data-table-cell'>$visibleNotes</td>";
				$longResult.= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
				$longResult.= "<td class='data-table-cell'>".DBLogType($logType,true)."</td>";
				$longResult.= "<td class='data-table-cell'>$editDate</td>";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			$shortResult.= FormatSimpleMessage($stmt->num_rows." Customers",1);
		}
		else $shortResult.= FormatSimpleMessage("All Good",1);
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_DevicesRecentlyChangedStatus($siteIDFilter, $days=90)
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Recent Devices added or removed";
		$reportNote= "These devices have been added or remvoed (marked inactive) in the past $days days.";
		
		$query = "SELECT group_concat(cur.status separator '') as allstati,cur.* 
			FROM (SELECT d.deviceid, s.name AS site, r.name AS room, d.hno, c.name, l.locationid, l.name AS loc, d.unit, d.name AS devicename, d.altname, d.member, d.size, d.type, d.status, d.note, d.asset, d.serial, d.model, d.edituser, d.editdate as editdate, d.qauser, d.qadate, d.logtype
				FROM dcimlog_device AS d
					LEFT JOIN dcim_location AS l ON d.locationid=l.locationid
					LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
					LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
					LEFT JOIN dcim_customer AS c ON d.hno=c.hno
				WHERE s.siteid LIKE ? AND d.editdate BETWEEN NOW() - INTERVAL $days DAY AND NOW()
				ORDER BY d.editdate DESC) AS cur
			GROUP BY cur.deviceid
			HAVING (allstati LIKE '%A%' AND allstati LIKE '%I%') OR logtype='I'
			ORDER BY cur.editdate DESC";
		
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_DevicesRecentlyChangedStatus() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_DevicesRecentlyChangedStatus()";
		}
		$stmt->store_result();
		$stmt->bind_result($stati, $deviceID, $site, $room, $hNo, $customer, $locationID, $location, $unit, $name,$deviceAltName, $member, $size, $type, $status, $notes, $asset, $serial, $model, $editUserID, $editDate, $qaUserID, $qaDate, $logType);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Device","Location","Unit","Model","Size","Type","Asset","Status","Notes","Tech","Action","Date&#x25BC;"));
			
			while ($stmt->fetch())
			{
				$visibleNotes = TruncateWithSpanTitle(MakeHTMLSafe(htmlspecialchars($notes)));
				$deviceFullName = GetDeviceFullName($name, $model, $member,$deviceAltName, true);
				$fullLocationName = FormatLocation($site, $room, $location);
				
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>";
				//$longResult.= "<td class='data-table-cell'><a href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a></td>";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>";
				$longResult.= "<td class='data-table-cell'>$unit</td>";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($model)."</td>";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($size)."</td>";
				$longResult.= "<td class='data-table-cell'>".DeviceType($type)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($asset)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".DeviceStatus($status)."</td>\n";
				$longResult.= "<td class='data-table-cell'>$visibleNotes</td>";
				$longResult.= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
				$longResult.= "<td class='data-table-cell'>".DBLogType($logType,true)."</td>";
				$longResult.= "<td class='data-table-cell'>$editDate</td>";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			
			//show results short
			$shortResult.= FormatSimpleMessage("$count Devices",1);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_BadgesToQA($siteIDFilter)
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Badges Pending QA";
		$reportNote = "These badges need to be verified with badge server.";
		
		$query = "SELECT GROUP_CONCAT(Distinct s.name) AS sites, c.name AS cust, b.badgeid, b.hno, b.name, b.badgeno, b.status, b.issue, b.hand, b.returned, b.edituser, b.editdate, b.qauser, b.qadate
			FROM dcim_site AS s
				INNER JOIN dcim_room AS r ON r.siteid=s.siteid
				INNER JOIN dcim_location AS l ON l.roomid=r.roomid
				INNER JOIN dcim_device AS d ON d.locationid=l.locationid
				INNER JOIN dcim_customer AS c ON c.hno=d.hno
				INNER JOIN dcim_badge AS b ON b.hno=c.hno
			WHERE b.qauser=-1 AND s.siteid LIKE ?
			GROUP BY b.badgeid
			ORDER BY sites, c.name,b.name";
		
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_BadgesToQA() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_BadgesToQA()";
		}
		$stmt->store_result();
		$stmt->bind_result($sites, $customer, $badgeID, $hNo, $name, $badgeNo, $status, $issue, $hand, $returned, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Site","Customer","Name","Badgeno","Status","Issue","Enroll"),true);
			//list result data
			while ($stmt->fetch()) 
			{
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($sites)."</td>\n";
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
	
	function Check_CustomersToQA($siteIDFilter)
	{
		global $mysqli;
		global $errorMessage;
		$reportTitle = "Customers Pending QA";
		$reportNote = "These need the name, status, and account numbers of the customer validated.";
		
		$query = "SELECT c.name, c.hno, c.cno, c.note, c.status, c.edituser, c.editdate, c.qauser, c.qadate 
			FROM dcim_site AS s
				INNER JOIN dcim_room AS r ON r.siteid=s.siteid
				INNER JOIN dcim_location AS l ON l.roomid=r.roomid
				INNER JOIN dcim_device AS d ON d.locationid=l.locationid
				INNER JOIN dcim_customer AS c ON c.hno=d.hno
			WHERE c.qauser=-1 AND s.siteid LIKE ?
			GROUP BY c.hno
			ORDER BY c.name";
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_CustomerToQA() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_CustomerToQA()";
		}
		$stmt->store_result();
		$stmt->bind_result($name, $hNo, $cNo, $note, $status, $editUserID, $editDate, $qaUserID, $qaDate);
		$shortResult = ""; $longResult = "";
		if($stmt->num_rows>0)
		{
			$longResult.= CreateDataTableHeader(array("Customer","H#","C#","Status","Note"),true);
			while ($stmt->fetch()) 
			{
				$note = Truncate($note);
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'>"."<A href='./?host=$hNo'>".MakeHTMLSafe($name)."</a>"."</td>\n";
				$longResult.= "<td class='data-table-cell'>".$hNo."</td>\n";
				$longResult.= "<td class='data-table-cell'>".$cNo."</td>\n";
				$longResult.= "<td class='data-table-cell'>".CustomerStatus($status)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($note)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate,"", $qaUserID, $qaDate)."</td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			$shortResult.= FormatSimpleMessage($stmt->num_rows." Pending",2);
		}
		else $shortResult.= FormatSimpleMessage("All Good",1);
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_DevicesToQA($siteIDFilter)
	{
		global $mysqli;
		global $errorMessage;
		$reportTitle = "Devices Pending QA";
		$reportNote = "These devices need their recent changes validated.";
		
		$query = "SELECT d.deviceid, s.name AS site, r.name AS room, d.hno, c.name, l.locationid, l.name AS loc, d.unit, d.name AS devicename, d.altname, d.member, d.size, d.type, d.status, d.note, d.asset, d.serial, d.model, d.edituser, d.editdate, d.qauser, d.qadate
			FROM dcim_site AS s
				INNER JOIN dcim_room AS r ON r.siteid=s.siteid
				INNER JOIN dcim_location AS l ON l.roomid=r.roomid
				INNER JOIN dcim_device AS d ON d.locationid=l.locationid
				INNER JOIN dcim_customer AS c ON c.hno=d.hno
			WHERE d.qauser=-1 AND s.siteid LIKE ?
			GROUP BY d.deviceid
			ORDER BY s.name, d.name, d.member";
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_CustomerToQA() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_CustomerToQA()";
		}
		$stmt->store_result();
		$stmt->bind_result($deviceID, $site, $room, $hNo, $customer, $locationID, $location, $unit, $name,$deviceAltName, $member, $size, $type, $status, $notes, $asset, $serial, $model, $editUserID, $editDate, $qaUserID, $qaDate);
		$shortResult = ""; $longResult = "";
		if($stmt->num_rows>0)
		{
			$longResult.= CreateDataTableHeader(array("Device","Customer","Location","Unit","Model","Size","Type","Asset","Status","Notes","Tech","Date&#x25BC;"));
			while ($stmt->fetch())
			{
				$visibleNotes = TruncateWithSpanTitle(MakeHTMLSafe(htmlspecialchars($notes)));
				$deviceFullName = GetDeviceFullName($name, $model, $member,$deviceAltName, true);
				$fullLocationName = FormatLocation($site, $room, $location);
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>";
				$longResult.= "<td class='data-table-cell'><a href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a></td>";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>";
				$longResult.= "<td class='data-table-cell'>$unit</td>";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($model)."</td>";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($size)."</td>";
				$longResult.= "<td class='data-table-cell'>".DeviceType($type)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($asset)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".DeviceStatus($status)."</td>\n";
				$longResult.= "<td class='data-table-cell'>$visibleNotes</td>";
				$longResult.= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
				$longResult.= "<td class='data-table-cell'>$editDate</td>";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			$shortResult.= FormatSimpleMessage($stmt->num_rows." Pending",2);
		}
		else $shortResult.= FormatSimpleMessage("All Good",1);
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_LocationsToQA($siteIDFilter)
	{
		global $mysqli;
		global $errorMessage;
		$reportTitle = "Locations Pending QA";
		$reportNote = "These locations need their recent changes validated.";
		
		$query = "SELECT s.siteid, s.name AS site, r.roomid, r.name AS room, l.locationid, l.name AS loc, l.edituser, l.editdate, l.qauser, l.qadate
			FROM dcim_site AS s
				INNER JOIN dcim_room AS r ON r.siteid=s.siteid
				INNER JOIN dcim_location AS l ON l.roomid=r.roomid
			WHERE l.qauser=-1 AND s.siteid LIKE ?
			GROUP BY l.locationid
			ORDER BY s.name, r.name, l.name";
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_CustomerToQA() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_CustomerToQA()";
		}
		$stmt->store_result();
		$stmt->bind_result($siteID, $site, $roomID, $room, $locationID, $location, $editUserID, $editDate, $qaUserID, $qaDate);
		$shortResult = ""; $longResult = "";
		if($stmt->num_rows>0)
		{
			$longResult.= CreateDataTableHeader(array("Site","Room","Location","Tech","Date&#x25BC;"));
			while ($stmt->fetch())
			{
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?siteid=$siteID'>".MakeHTMLSafe($site)."</a></td>";
				$longResult.= "<td class='data-table-cell'><a href='./?roomid=$roomID'>".MakeHTMLSafe($room)."</a></td>";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($location)."</a></td>";
				$longResult.= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
				$longResult.= "<td class='data-table-cell'>$editDate</td>";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			$shortResult.= FormatSimpleMessage($stmt->num_rows." Pending",2);
		}
		else $shortResult.= FormatSimpleMessage("All Good",1);
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_DevicesWithoutCustomersOrLocation()
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Devices Without Customer or Location";
		$reportNote = "Disconnected record(s) or in Unknown.";
		
		$query = "SELECT d.hno, d.deviceid, d.name, d.altname, d.member, d.model, d.locationid, l.locationid, l.name, r.name, s.name
			FROM dcim_device AS  d
				LEFT JOIN dcim_customer AS c ON d.hno=c.hno
				LEFT JOIN dcim_location AS l ON d.locationid=l.locationid
				LEFT JOIN dcim_room AS r ON r.roomid=l.roomid
				LEFT JOIN dcim_site AS s ON s.siteid=r.siteid
			WHERE c.name IS NULL OR l.locationid IS NULL OR l.name='Unknown'
			ORDER BY s.name, d.name";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "Prepare failed: Check_DevicesWithoutCustomersOrLocation() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_DevicesWithoutCustomersOrLocation()";
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($hno, $deviceID, $deviceName,$deviceAltName, $member, $model, $locationID, $linkedLocationID,$locationName, $room, $site);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("DeviceID","Device","H#","LocationID","LinkedLocationID","LinkedLocationName"));
				
			while ($stmt->fetch())
			{
				$fullLocationName = FormatLocation($site, $room, $locationName);
				$deviceFullName = GetDeviceFullName($deviceName, $model, $member,$deviceAltName, false);
				
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceID)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?host=$hno'>".MakeHTMLSafe($hno)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($locationID)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$linkedLocationID'>".MakeHTMLSafe($linkedLocationID)."</a></td>\n";
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
		
		$query = "SELECT dp.deviceportid, d.hno, dp.deviceid, d.name, d.altname, d.member, d.model, dp.pic, dp.port, dp.type
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
		$stmt->bind_result($deviceportid, $hno, $deviceID, $deviceName,$deviceAltName, $member, $model, $pic, $port, $type);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("DevicePortID","Device","H#","Port"));
			
			while ($stmt->fetch())
			{
				$deviceFullName = GetDeviceFullName($deviceName, $model, $member,$deviceAltName, true);
				$portFullName = FormatPort($member, $model, $pic, $port, $type);
				
				$longResult.= "<tr class='dataRow'>\n";
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
	
	function Check_BadgesActiveUnderInactiveCustomer($siteIDFilter)
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Active badges where customer is not active";
		$reportNote = "These badges need to be deactivated.";
		
		$query = "SELECT GROUP_CONCAT(Distinct s.name) AS sites, c.name AS cust,b.name,b.badgeno, b.hno 
			FROM dcim_site AS s
				INNER JOIN dcim_room AS r ON r.siteid=s.siteid
				INNER JOIN dcim_location AS l ON l.roomid=r.roomid
				INNER JOIN dcim_device AS d ON d.locationid=l.locationid
				INNER JOIN dcim_customer AS c ON c.hno=d.hno
				INNER JOIN dcim_badge AS b ON b.hno=c.hno
			WHERE c.status='I' AND NOT b.status IN ('D','R') AND s.siteid LIKE ?
			GROUP BY b.badgeid
			ORDER BY sites, c.name,b.name";
		
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_BadgesActiveUnderInactiveCustomer() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_BadgesActiveUnderInactiveCustomer()";
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($sites, $cust, $name, $badgeNo, $hno);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		//data title
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Site", "Customer","Name","Badgeno"));
			
			//list result data
			while ($stmt->fetch()) 
			{
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($sites)."</td>\n";
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

	function Check_DevicesActiveUnderInactiveCustomer($siteIDFilter)
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Active devices/colos where parent customer is not active";
		$reportNote = "These need to be deactivated.";
		
		$query = "SELECT c.name AS cust,c.hno,d.deviceid,d.name, d.altname, d.model, d.member, s.name, r.name, l.name, l.locationid
			FROM dcim_device AS d 
				LEFT JOIN dcim_customer AS c ON c.hno=d.hno
				INNER JOIN dcim_location AS l ON l.locationid=d.locationid
				INNER JOIN dcim_room AS r ON r.roomid=l.roomid
				INNER JOIN dcim_site AS s ON s.siteid=r.siteid
			WHERE s.siteid LIKE ? AND c.status='I' AND NOT d.status='I'
			ORDER BY s.name, c.name, d.name, d.member";
		
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_DevicesActiveUnderInactiveCustomer() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_DevicesActiveUnderInactiveCustomer()";
		}
		$stmt->store_result();
		$stmt->bind_result($cust, $hno, $deviceID, $deviceName,$deviceAltName, $model, $member, $site, $room, $location, $locationID);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		//data title
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Location","Customer","Device"));
			while ($stmt->fetch())
			{
				$deviceFullName = GetDeviceFullName($deviceName, $model, $member,$deviceAltName, true);
				$fullLocationName = FormatLocation($site, $room, $location);
				
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?host=$hno'>".MakeHTMLSafe($cust)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			$shortResult.= FormatSimpleMessage("$count Devices",3);
		}
		else $shortResult.= FormatSimpleMessage("All Good",1);
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_DevicesWithUnknownModel($siteIDFilter)
	{
		global $mysqli;
		global $errorMessage;
		global $deviceModels;
		
		$reportTitle = "Devices With unknown model";
		$reportNote = "Active Devices that have a model that is not in the DB. <a href='https://github.com/freshprogrammer/DCIM/blob/master/lib/helperFunctions.php#L184'>Models</a>";//TODO this should list available models some how
		
		$filter = "d.model NOT IN (";
		foreach($deviceModels as $model)
		{
			$filter .= "'".$model->name."',";
		}
		$filter = substr($filter,0,-1).")";
		
		$query = "SELECT d.hno, d.deviceid, d.name, d.altname, d.member, d.model, s.name
			FROM dcim_device AS  d
				INNER JOIN dcim_location AS l ON l.locationid=d.locationid
				INNER JOIN dcim_room AS r ON r.roomid=l.roomid
				INNER JOIN dcim_site AS s ON s.siteid=r.siteid
			WHERE s.siteid LIKE ? AND d.status='A' AND $filter
			ORDER BY s.name, d.model";
		
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_DevicesWithUnknownModel() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_DevicesWithUnknownModel()";
		}
		$stmt->store_result();
		$stmt->bind_result($hno, $deviceID, $deviceName,$deviceAltName, $member, $model, $site);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Site","DeviceID","Device","H#","Model"));
			while ($stmt->fetch())
			{
				$deviceFullName = GetDeviceFullName($deviceName, $model, $member,$deviceAltName, false);
				
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($site)."</td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceID)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?host=$hno'>".MakeHTMLSafe($hno)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($model)."</td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			$shortResult.= FormatSimpleMessage("$count Devices",3);
		}
		else $shortResult.= FormatSimpleMessage("All Good",1);
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_PowerWithoutPowerLoc()
	{
		global $mysqli;
		global $errorMessage;
		$reportTitle = "Power records without any linking location record";
		$reportNote = "Power circuits not linked to a location. (Circuits added for panel audits)";
		
		$query = "SELECT pc.powercircuitid, s.siteid, s.name, pp.powerpanelid, pp.name, pc.circuit
			FROM dcim_powercircuit AS pc
				LEFT JOIN dcim_powercircuitloc AS pcl ON pc.powercircuitid=pcl.powercircuitid
				LEFT JOIN dcim_powerpanel AS pp ON pp.powerpanelid=pc.powerpanelid
				LEFT JOIN dcim_room AS r ON r.roomid=pp.roomid
				LEFT JOIN dcim_site AS s ON s.siteid=r.siteid
			WHERE s.siteid LIKE ? AND pcl.powercircuitid IS NULL
			ORDER BY s.name, pp.name, pc.circuit";
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_PowerWithoutPowerLoc() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_PowerWithoutPowerLoc()";
		}
		$stmt->store_result();
		$stmt->bind_result($powerCircuitID, $siteID, $siteName, $powerPanelID, $panel, $circuit);
		$shortResult = ""; $longResult = "";
		if($stmt->num_rows>0)
		{
			$longResult.= CreateDataTableHeader(array("Site","Panel","Circuit","PowerCircuitID"));
			while ($stmt->fetch())
			{
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?siteid=$siteID'>".MakeHTMLSafe($siteName)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?powerpanelid=$powerPanelID'>".MakeHTMLSafe($panel)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($circuit)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($powerCircuitID)."</td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			$shortResult.= FormatSimpleMessage($stmt->num_rows." Circuits",2);
		}
		else $shortResult.= FormatSimpleMessage("All Good",1);
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_PowerLocWithoutLocationOrPower()
	{
		global $mysqli;
		global $errorMessage;
		$reportTitle = "Power location records linked to missing records";
		$reportNote = "Disconnected record(s).";
		
		$query = "SELECT pcl.powercircuitlocid, pcl.powercircuitid, pcl.locationid, pc.powercircuitid, l.locationid, l.name
			FROM dcim_powercircuitloc AS pcl
				LEFT JOIN dcim_location AS l ON pcl.locationid=l.locationid
				LEFT JOIN dcim_powercircuit AS pc ON pcl.powercircuitid=pc.powercircuitid
			WHERE l.locationid IS NULL OR pc.powercircuitid IS NULL
			ORDER BY 1";
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_PowerLocWithoutLocationOrPower() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_PowerLocWithoutLocationOrPower()";
		}
		$stmt->store_result();
		$stmt->bind_result($powerLocID, $powerCircuitID, $locationID,$linkedPowerCircuitID, $linkedLocationID, $locationName);
		$shortResult = ""; $longResult = "";
		if($stmt->num_rows>0)
		{
			$longResult.= CreateDataTableHeader(array("PowerLocID","PowerCircuitID","LocationID","LinkedPowerCircuitID","LinkedLocationID"));
			while ($stmt->fetch())
			{
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($powerLocID)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($powerCircuitID)."</td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($locationID)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($linkedPowerCircuitID)."</td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$linkedLocationID'>".MakeHTMLSafe("($locationID) - $locationName")."</a></td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			$shortResult.= FormatSimpleMessage($stmt->num_rows." Errors",3);
		}
		else $shortResult.= FormatSimpleMessage("All Good",1);
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_LocationWithoutRoom()
	{
		global $mysqli;
		global $errorMessage;
		$reportTitle = "Location records linked to invalid room"; $reportNote = "";
		
		$query = "SELECT l.locationid, r.name, l.name, l.roomid, r.roomid
			FROM dcim_location AS l
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			GROUP BY l.locationid
			HAVING r.roomid IS NULL
			ORDER BY s.name, r.name, l.name";
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_LocationWithoutRoom() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_LocationWithoutRoom()";
		}
		$stmt->store_result();
		$stmt->bind_result($locationID, $room, $locationName,$roomID, $linkedRoomID);
		$shortResult = ""; $longResult = "";
		if($stmt->num_rows>0)
		{
			$longResult.= CreateDataTableHeader(array("LocationID","Location Name","Invalid RoomID"));
			while ($stmt->fetch())
			{
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($locationID)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($locationName)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($linkedRoomID)."</td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			$shortResult.= FormatSimpleMessage($stmt->num_rows." Invalid Locations",3);
		}
		else $shortResult.= FormatSimpleMessage("All Good",1);
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_LocationAllocation_IncorrectEmpty($siteIDFilter)
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Empty Locations with active device(s)";
		$reportNote = "Locations allocated Empty/Reserved with active device(s).";
		
		$query = "SELECT s.name, r.name, l.name, l.locationid, l.allocation, COUNT(d.deviceid) AS cnt
			FROM dcim_location AS l
				LEFT JOIN dcim_room AS r ON  r.roomid=l.roomid
				LEFT JOIN dcim_site AS s ON  s.siteid=r.siteid
				LEFT JOIN dcim_device AS d ON  d.locationid=l.locationid
			WHERE s.siteid LIKE ? AND l.allocation IN ('E','R') AND d.status='A'
			GROUP BY s.siteid, r.roomid, l.locationid
			HAVING cnt>0
			ORDER BY s.name, r.name, l.name";
		
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_LocationAllocation_IncorrectEmpty() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_LocationAllocation_IncorrectEmpty()";
		}
		$stmt->bind_result($siteName, $roomName, $locationName,$locationID, $allocation, $deviceCount);
		$shortResult = "";
		$longResult = "";
		if($stmt->num_rows>0)
		{
			$longResult.= CreateDataTableHeader(array("Location","Allocation","Device Count"));
			
			//list result data
			while ($stmt->fetch())
			{
				$fullLocationName = MakeHTMLSafe(FormatLocation($siteName, $roomName, $locationName));
				
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>$fullLocationName</a></td>\n";
				$longResult.= "<td class='data-table-cell'>".LocationAllocation($allocation)."</td>\n";
				$longResult.= "<td class='data-table-cell'>$deviceCount</td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			$shortResult.= FormatSimpleMessage($stmt->num_rows." Invalid Empty Allocations",3);
		}
		else $shortResult.= FormatSimpleMessage("All Good",1);
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_LocationAllocation_ShouldBeColo($siteIDFilter)
	{
		global $mysqli;
		global $errorMessage;
		$reportTitle = "Colo devices in non Colo locations";
		$reportNote = "Colo type devices in locations not allocated for Colo.";
		
		$query = "SELECT s.name, r.name, l.name, l.locationid, l.allocation, COUNT(d.deviceid) AS cnt
			FROM dcim_location AS l
				LEFT JOIN dcim_room AS r ON  r.roomid=l.roomid
				LEFT JOIN dcim_site AS s ON  s.siteid=r.siteid
				LEFT JOIN dcim_device AS d ON  d.locationid=l.locationid
			WHERE s.siteid LIKE ? AND l.allocation NOT IN ('C') AND d.status='A' AND d.type!='S'
			GROUP BY s.siteid, r.roomid, l.locationid
			HAVING cnt>0
			ORDER BY s.name, r.name, l.name";
		
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_LocationAllocation_ShouldBeColo() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_LocationAllocation_ShouldBeColo()";
		}
		$stmt->store_result();
		$stmt->bind_result($siteName, $roomName, $locationName,$locationID, $allocation, $deviceCount);
		$shortResult = ""; $longResult = "";
		if($stmt->num_rows>0)
		{
			$longResult.= CreateDataTableHeader(array("Location","Allocation","Colo Device Count"));
			while ($stmt->fetch())
			{
				$fullLocationName = MakeHTMLSafe(FormatLocation($siteName, $roomName, $locationName));
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>$fullLocationName</a></td>\n";
				$longResult.= "<td class='data-table-cell'>".LocationAllocation($allocation)."</td>\n";
				$longResult.= "<td class='data-table-cell'>$deviceCount</td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			$shortResult.= FormatSimpleMessage($stmt->num_rows." Invalid Allocations",3);
		}
		else $shortResult.= FormatSimpleMessage("All Good",1);
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_ActiveLocationWithoutPower($siteIDFilter)
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Active location with no power";
		$reportNote = "Location with active device(s) but not linked to power";
		
		$query = "SELECT l.locationid, s.name, r.name, l.name, l.roomid, r.roomid, COUNT(d.locationid) AS devicecount, COUNT(pcl.locationid) AS powercount
			FROM dcim_location AS l
				LEFT JOIN dcim_powercircuitloc AS pcl ON l.locationid=pcl.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
				LEFT JOIN dcim_device AS d ON l.locationid=d.locationid
			WHERE s.siteid LIKE ? AND d.status='A'
			GROUP BY l.locationid
			HAVING powercount=0 AND devicecount>=1
			ORDER BY s.name, r.name, l.name";
		
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $siteIDFilter)|| !$stmt->execute())
		{
			$errorMessage[] = "Prepare failed: Check_ActiveLocationWithoutPower() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_ActiveLocationWithoutPower()";
		}
		$stmt->store_result();
		$stmt->bind_result($locationID, $site, $room, $locationName,$roomID, $linkedRoomID, $deviceCount, $powerCount);
		$shortResult = "";$longResult = "";
		if($stmt->num_rows>0)
		{
			$longResult.= CreateDataTableHeader(array("Location","Device Count","Power Count"));
			while ($stmt->fetch())
			{
				$fullLocationName = FormatLocation($site, $room, $locationName);
				
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($deviceCount)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($powerCount)."</td>\n";
				$longResult.= "</tr>\n";
			}
			$longResult.= "</table>\n";
			$shortResult.= FormatSimpleMessage($stmt->num_rows." Locations",3);
		}
		else $shortResult.= FormatSimpleMessage("All Good",1);
		return CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_RecordsMisingInsertLog()
	{
		global $mysqli;
		global $errorMessage;
		
		$reportTitle = "Data Records missing data history";
		$reportNote= "These are records that managed to exist without proper insert log records to match.";
		
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
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "Prepare failed: Check_RecordsMisingInsertLog() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_RecordsMisingInsertLog()";
		}
				
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($table, $id, $parentTable, $parentID, $logID, $logType, $editUserID, $editDate);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		//data title
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Table","ID","Parent Table","Parent ID","Tech", "Date"));
			
			//list result data
			while ($stmt->fetch())
			{
				$table = "dcim_".$table;
				$pageKey = GetRecordPageKey($table);
				$idDisplay = $id;
				if($pageKey!=null)
					$idDisplay = "<a href='./?$pageKey=$id'>$id</a>";
				$tableDescription = GetTableRecordDescription($table);

				$parentTable = "dcim_".$parentTable;
				$parentPageKey = GetRecordPageKey($parentTable);
				$parentIDDisplay = $parentID;
				if($parentPageKey!=null)
					$parentIDDisplay = "<a href='./?$parentPageKey=$parentID'>$parentID</a>";
				$parentTableDescription = GetTableRecordDescription($parentTable);
				
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'>$tableDescription</td>\n";
				$longResult.= "<td class='data-table-cell'>$idDisplay</td>\n";
				$longResult.= "<td class='data-table-cell'>$parentTableDescription</td>\n";
				$longResult.= "<td class='data-table-cell'>$parentIDDisplay</td>\n";
				$longResult.= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate)."</td>\n";
				$longResult.= "<td class='data-table-cell'>$editDate</td>\n";
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
	
	function Check_RecordLogOutOfSync_AllTables()
	{	
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
		
		$result = "";
		foreach ($tables as $table)
		{
			$result.= Check_RecordLogOutOfSync_Table($table);
		}
		return $result;
	}
	
	function Check_RecordLogOutOfSync_Table($table)
	{
		global $mysqli;
		global $errorMessage;
		
		$logTable = GetLogTable($table);
		$keyfield = GetKeyField($table);
		$fields = GetTableFieldsFromDocs($table);
		
		$reportTitle = "Records out of sync in $table";
		$reportNote = "Records where the current record in $table doesnt match the most recent log record in $logTable";
		
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
			HAVING cur!=log OR log IS NULL
			ORDER BY $keyfield";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "Prepare failed: Check_RecordLogOutOfSync_Table() - (" . $mysqli->errno . ") " . $mysqli->error;
			return "Prepare failed in Check_RecordLogOutOfSync_Table()";
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($key, $cur, $log);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		if($count>0)
		{
			$longResult.= CreateDataTableHeader(array("Table","Record","Current","Log"));
			$pageURLKey = GetRecordPageKey($table);
			
			//list result data
			while ($stmt->fetch())
			{
				$longResult.= "<tr class='dataRow'>\n";
				$longResult.= "<td class='data-table-cell'>$table</td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?$pageURLKey=$key'>$key</a></td>";
				$longResult.= "<td class='data-table-cell'>$cur</td>\n";
				$longResult.= "<td class='data-table-cell'>$log</td>\n";
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