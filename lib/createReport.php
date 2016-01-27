<?php
	require_once 'DCIMCustomFunctions.php';
	require_once 'config.php';
	require_once 'customFunctions.php';
	require_once 'genericFunctions.php';
	require_once 'helperFunctions.php';
	require_once 'functions.php';
	
	//startup
	SQLIConnect();

	$report = GetInput("report");
	$date = GetInput("date");
	
	if($report==="ActiveBadgeList")
		OutputCSV("Active Badge List-".date("Y-m-d").".csv",CreateBadgeExportArray());
	else if($report==="PowerAudit")
		OutputCSV("Location Power Audit - ".date("Y-m-d").".csv",CreatePowerAuditExportArray(0));//NOTE: this is only running for site 0
	else if($report==="PowerHistory")
		OutputCSV("Power Audit - $date.csv",CreatePowerHistoryExportArray(0,$date));//NOTE: this is only running for site 0
	else
	{
		echo "No Report Specified";
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
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$result[] = array("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
			return $result;
		}
		
		$stmt->execute();
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
	
	function CreatePowerAuditExportArray($siteID)
	{
		global $mysqli;
		
		$result = array();
		
		//this is grouped by circuit
		$query = "SELECT s.name AS site,r.name AS room,l.name AS location,p.panel,p.circuit,c.name AS cust, c.hno, p.load, (p.load/p.amps*100) AS percent,p.amps, p.volts, p.status, p.editdate
			FROM dcim_location AS l
				INNER JOIN dcim_powerloc AS pl ON l.locationid=pl.locationid
				INNER JOIN dcim_power AS p ON pl.powerid=p.powerid
				INNER JOIN dcim_room AS r ON r.roomid=l.roomid 
				INNER JOIN dcim_site AS s ON s.siteid=r.siteid AND s.siteid=$siteID
				LEFT JOIN dcim_device AS d ON l.locationid=d.locationid AND d.status='A'
				LEFT JOIN dcim_customer AS c ON d.hno=c.hno
			GROUP BY l.locationid,p.powerid
			ORDER BY s.name, r.name,l.name,p.panel,p.circuit";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$result[] = array("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
			return $result;
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($site,$room,$locationName,$panel,$circuit,$cust,$hNo,$load,$percent,$amps,$volts,$status,$editDate);
		$count = $stmt->num_rows;
		
		if($count>0)
		{
			//create result array
			//headers
			$result[] = array("All Power Readings as of ".date("F j, Y"));//EX: March 10, 2001
			$result[] = array("Customer","H#","Location","Panel","Circuit","Reading","Reading%","Amps","Volts","On/Off","Date");

			$showNAForEmptyLocations = true;
			$skipLinesBetweenLocationsAndOnlyFirstCustName = true;
			$lastLoc = "";
			//list result data - collected by customer
			while ($stmt->fetch())
			{
				$fullLocationName = FormatLocation($site, $room, $locationName);
				if($showNAForEmptyLocations)
					$cust = (strlen($cust)>0)?$cust:"N/A";
				$panel = (strrpos($panel, '-')||strrpos($panel, '/'))?("=\"$panel\""):$panel;
				$circuit = ($volts==208)?("=\"".$circuit."/".($circuit+2)."\""):$circuit;
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
				$result[] = array($cust,$hNo,$fullLocationName,$panel,$circuit,$load."A",substr($percent,0,5)."%",$amps."A",$volts."V",($status==="A")?"On":"Off",substr($editDate,0,10));
			}
		}
		return $result;
	}
	
	/*
	 * url: root/lib/createReport.php?report=PowerHistory&date=2016-01-01
	 */
	function CreatePowerHistoryExportArray($siteID, $date)
	{
		global $mysqli;
		
		$result = array();
		
		//this is grouped by circuit
		$query = "SELECT cur.* FROM (
				SELECT p.powerid, p.panel,p.circuit, p.load, (p.load/p.amps*100) AS percent,p.amps, p.volts, p.status, p.editdate
					FROM dcimlog_power AS p
						WHERE DATE(p.editdate)<='$date'
					ORDER BY p.panel,p.circuit,p.editdate DESC
				) AS cur
			GROUP BY cur.powerid
			ORDER BY panel,circuit,editdate DESC";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$result[] = array("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
			return $result;
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($powerid, $panel,$circuit,$load,$percent,$amps,$volts,$status,$editDate);
		$count = $stmt->num_rows;
		
		if($count>0)
		{
			//create result array
			//headers
			$result[] = array("All Power Readings as of date($date)");//
			$result[] = array("Panel","Circuit","Reading","Reading%","Amps","Volts","On/Off","Date");

			//list result data
			while ($stmt->fetch())
			{
				$panel = (strrpos($panel, '-')||strrpos($panel, '/'))?("=\"$panel\""):$panel;
				$circuit = ($volts==208)?("=\"".$circuit."/".($circuit+2)."\""):$circuit;
				
				$result[] = array($panel,$circuit,$load,substr($percent,0,5)."%",$amps,$volts,($status==="A")?"On":"Off",substr($editDate,0,10));
			}
		}
		return $result;
	}
?>
