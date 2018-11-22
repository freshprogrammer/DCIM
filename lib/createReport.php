<?php
	require_once 'DCIMCustomFunctions.php';
	require_once 'config.php';
	require_once 'customFunctions.php';
	require_once 'genericFunctions.php';
	require_once 'helperFunctions.php';
	require_once 'dataFunctions.php';
	require_once 'htmlFunctions.php';
	
	//startup
	SQLIConnect();

	$report = GetInput("report");
	$date = GetInput("date");
	$siteID= GetInput("siteid");
	$dump= GetInput("dump");
	
	$dataDump = $dump=="T";//data dumps will just dump the data to the source instead of returning as a CSV
	
	if($report==="ActiveBadgeList")
	{
		$output = CreateBadgeExportArray();
		if($dataDump) echo FormatCSVArrayAsString($output);
		else OutputCSV("Active Badge List-".date("Y-m-d").".csv",$output);
	}
	else if($report==="PowerLocationList")
	{
		$output = CreateLocationPowerAuditExportArray($siteID);
		if($dataDump) echo FormatCSVArrayAsString($output);
		else OutputCSV("Location Power List - ".date("Y-m-d").".csv",$output);
	}
	else if($report==="PowerAudit")
	{
		$output = CreatePowerHistoryExportArray($date, $siteID);
		if($dataDump) echo FormatCSVArrayAsString($output);
		else OutputCSV("Power Audit - $date.csv",$output);
	}
	else
	{
		echo "No Report Specified";
	}
	
	function FormatCSVArrayAsString($data)
	{
		$result = "";
		foreach($data as $line)
		{
			$result .= implode($line, ",")."\n";
		}
		return $result;
	}
	
	function CreateBadgeExportArray()
	{
		global $mysqli;
		
		$result = array();
		//select empty as customer to keep return results to match search query
		$query = "SELECT cur.hno, cur.cno, cur.customer, cur.name, cur.badgeno, cur.issue, cur.hand, GROUP_CONCAT(cur.fullloc SEPARATOR ', ') AS alllocs
			FROM (SELECT b.badgeid, c.hno, c.cno, c.name AS customer, b.name, b.badgeno, b.issue, b.hand,
				CONCAT(s.name,' ',r.name,' ',l.name) AS fullloc
				FROM dcim_badge AS b
					LEFT JOIN dcim_customer AS c ON c.hno = b.hno
					LEFT JOIN dcim_device AS d ON d.hno=c.hno
					LEFT JOIN dcim_location AS l ON d.locationid=l.locationid 
					LEFT JOIN dcim_room AS r ON r.roomid=l.roomid 
					LEFT JOIN dcim_site AS s ON s.siteid=r.siteid 
				WHERE b.status =  'A' AND d.type IN ('C','F','H') AND d.status='A') 
					AS cur
			GROUP BY cur.badgeid
			ORDER BY cur.customer, cur.name";
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->execute())
		{
			$result[] = array("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
			return $result;
		}
		$stmt->store_result();
		$stmt->bind_result($hNo, $cNo, $customer,$name, $badgeNo, $issue, $hand, $locs);
		$count = $stmt->num_rows;
	
		if($count>0)
		{
			//create result array
			//headers
			$result[] = array("Active Badge list as of ".date("F j, Y"));//EX: March 10, 2001
			$result[] = array("HNo", "CNo", "Customer Name","Badge Holder", "Badge No", "Issue Date", "Enrol date","Access");
			
			//list result data
			while ($stmt->fetch()) 
			{
				$result[] = array($hNo, $cNo, $customer,$name, $badgeNo, $issue, $hand, $locs);
			}
		}
		return $result;
	}
	
	function CreateLocationPowerAuditExportArray($siteID)
	{//location power report - probably doesn't need to list circuits with no location
		global $mysqli;
		
		$result = array();
		//this is grouped by circuit
		$query = "SELECT s.name AS site,r.name AS room,l.name AS location,pp.name,pc.circuit,c.name AS cust, c.hno, pc.load, (pc.load/pc.amps*100) AS percent,pc.amps, pc.volts, pc.status, pc.editdate
			FROM dcim_site AS s
				INNER JOIN dcim_room AS r ON s.siteid=r.siteid
				INNER JOIN dcim_powerpanel AS pp ON r.roomid=pp.roomid
				INNER JOIN dcim_powercircuit AS pc ON pp.powerpanelid=pc.powerpanelid
				LEFT JOIN dcim_powercircuitloc AS pcl ON pcl.powercircuitid=pc.powercircuitid
				LEFT JOIN dcim_location AS l ON l.locationid=pcl.locationid
				LEFT JOIN dcim_device AS d ON l.locationid=d.locationid AND d.status='A'
				LEFT JOIN dcim_customer AS c ON d.hno=c.hno
			WHERE s.siteid=?
			GROUP BY l.locationid,pc.powercircuitid
			ORDER BY s.name, r.name,l.name,pp.name,pc.circuit";
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i',$siteID) || !$stmt->execute())
		{
			$result[] = array("Prepare failed: CreatePowerAuditExportArray($siteID) (" . $mysqli->errno . ") " . $mysqli->error);
			return $result;
		}
		$stmt->store_result();
		$stmt->bind_result($site,$room,$locationName,$panel,$circuit,$cust,$hNo,$load,$percent,$amps,$volts,$status,$editDate);
		$count = $stmt->num_rows;
		
		if($count>0)
		{
			$result = null;
			$showNAForEmptyLocations = true;
			$skipLinesBetweenLocationsAndOnlyFirstCustName = true;
			$lastLoc = "";
			//list result data - collected by customer
			while ($stmt->fetch())
			{//create result array
				if($result==null)
				{//headers
					$result = array();
					$result[] = array("All $site Power Readings as of ".date("F j, Y"));//EX: March 10, 2001
					$result[] = array("Customer","H#","Location","Panel","Circuit","Reading","Reading%","Amps","Volts","On/Off","Date");
				}
				$fullLocationName = FormatLocation($site, $room, $locationName);
				if($showNAForEmptyLocations)
					$cust = (strlen($cust)>0)?$cust:"N/A";
				$panel = (strrpos($panel, '-')||strrpos($panel, '/'))?("=\"$panel\""):$panel;
				
				if($volts==208)$circuit = "=\"".Format208CircuitNumber($circuit)."\"";
				$volts=FormatVolts($volts);
				
				if($skipLinesBetweenLocationsAndOnlyFirstCustName)
				{
					if($lastLoc==$fullLocationName)
					{
						$cust = "";//dont show customer name and number on multiple circuits fo same customer 
						$hNo = "";
					}
					else 
					{//add a blank line afer each new location - skip the first (new) location
						if(strlen($lastLoc)>0)//this is just for the first loc
							$result[] = array("");
					}
					$lastLoc = $fullLocationName;
				}
				$result[] = array($cust,$hNo,$fullLocationName,$panel,$circuit,$load."A",substr($percent,0,5)."%",$amps."A",$volts,($status==="A")?"On":"Off",substr($editDate,0,10));
			}
		}
		return $result;
	}
	
	//url: root/lib/createReport.php?report=PowerHistory&date=2016-01-01&siteid=0
	function CreatePowerHistoryExportArray($date, $siteID)
	{
		global $mysqli;
		
		$result = array();
		//this is grouped by circuit - 'latest' record
		//specificly not looking in the panel logs - want to use the most recent panel name - inner join with live data to not report on old deleted circuits
		$query = "SELECT cur.* FROM (
				SELECT s.name, pc.powercircuitid, pp.name AS panel,pc.circuit, pc.load, (pc.load/pc.amps*100) AS percent,pc.amps, pc.volts, pc.status, pc.editdate
					FROM dcimlog_powercircuit AS pc
						INNER JOIN dcim_powercircuit AS pclive ON pclive.powercircuitid=pc.powercircuitid
						LEFT JOIN dcim_powerpanel AS pp ON pc.powerpanelid=pp.powerpanelid
						LEFT JOIN dcim_room AS r ON r.roomid=pp.roomid
						LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
					WHERE DATE(pc.editdate)<=? AND r.siteid=?
					ORDER BY pp.name,pc.circuit,pc.editdate DESC
				) AS cur
			GROUP BY cur.powercircuitid
			ORDER BY panel,circuit,editdate DESC";
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('si',$date,$siteID) || !$stmt->execute())
		{
			$result[] = array("Prepare failed: CreatePowerHistoryExportArray($date, $siteID)(" . $mysqli->errno . ") " . $mysqli->error);
			return $result;
		}
		$stmt->store_result();
		$stmt->bind_result($site, $powerid, $panel,$circuit,$load,$percent,$amps,$volts,$status,$editDate);
		$count = $stmt->num_rows;
		
		if($count>0)
		{//create result array
			$result = null;
			//list result data
			while ($stmt->fetch())
			{
				if($result==null)
				{//headers
					$result = array();
					$result[] = array("All $site Power Readings as of date($date)");//
					$result[] = array("Panel","Circuit","Reading","Reading%","Amps","Volts","On/Off","Date");
				}
				$panel = (strrpos($panel, '-')||strrpos($panel, '/'))?("=\"$panel\""):$panel;
				
				if($volts==208)$circuit = "=\"".Format208CircuitNumber($circuit)."\"";
				$volts=FormatVolts($volts);
				$result[] = array($panel,$circuit,$load,substr($percent,0,5)."%",$amps,$volts,($status==="A")?"On":"Off",substr($editDate,0,10));
			}
		}
		return $result;
	}
?>
