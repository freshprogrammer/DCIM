<?php 
	include 'config.php';
	include 'genericFunctions.php';
	include 'helperFunctions.php';
	include 'functions.php';

	//startup
	SQLIConnect();
	
	$report = GetInput("report");
	
	if($report==="ActiveBadgeList")
		OutputCSV("badgelist.csv",CreateBadgeExportArray());
	else if($report==="PowerAudit")
		OutputCSV("PowerAudit-".date("Y-m-d").".csv",CreatePowerAuditExportArray());
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
				CONCAT(s.name,' CA',CAST(l.colo AS UNSIGNED),' ',l.name) AS fullloc
				FROM dcim_badge AS b
					LEFT JOIN dcim_customer AS c ON c.hno = b.hno
					LEFT JOIN dcim_device AS d ON d.hno=c.hno
					LEFT JOIN dcim_location AS l ON d.locationid=l.locationid 
					LEFT JOIN dcim_site AS s ON s.siteid=l.siteid 
				WHERE b.status =  'A' AND d.type IN ('C','F','H') AND d.status='A') 
					AS cur
			GROUP BY cur.badgeid
			ORDER BY cur.customer, cur.name";

		if (!($stmt = $mysqli->prepare($query)))
		{
			$result[] = array("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>");
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
	
	function CreatePowerAuditExportArray()
	{
		global $mysqli;
		
		$result = array();
		$siteID = 0;
		
		$query = "SELECT s.name AS site,l.colo,l.name AS location,p.panel,p.circuit,c.name AS cust, c.hno, p.cload, (p.cload/p.amps*100) AS percent,p.amps, p.volts, p.status, p.editdate
			FROM dcim_location AS l
				INNER JOIN dcim_powerloc AS pl ON l.locationid=pl.locationid
				INNER JOIN dcim_power AS p ON pl.powerid=p.powerid
				INNER JOIN dcim_site AS s ON l.siteid=s.siteid
				LEFT JOIN dcim_device AS d ON l.locationid=d.locationid AND d.status='A'
				LEFT JOIN dcim_customer AS c ON d.hno=c.hno
			WHERE l.siteid=$siteID
			GROUP BY s.siteid,l.colo,l.name,p.panel,p.circuit
			ORDER BY l.colo,l.name,p.panel,CAST(p.circuit AS UNSIGNED)";

		if (!($stmt = $mysqli->prepare($query)))
		{
			$result[] = array("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>");
			return $result;
		}
			
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($site,$colo,$locationName,$panel,$circuit,$cust,$hNo,$cLoad,$percent,$amps,$volts,$status,$editDate);
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
			//list result data
			while ($stmt->fetch())
			{
				$fullLocationName = FormatLocation($site, $colo, $locationName);
				if($showNAForEmptyLocations)
					$cust = (strlen($cust)>0)?$cust:"N/A";
				$panel = (strrpos($panel, '-')||strrpos($panel, '/'))?("=\"$panel\""):$panel;
				$circuit = ($volts==208)?("=\"".$circuit."/".($circuit+2)."\""):$circuit;
				if($skipLinesBetweenLocationsAndOnlyFirstCustName)
				{
					if($lastLoc==$fullLocationName)
					{
						$cust = "";//dont show this 
						$hNo = "";
					}
					else 
					{
						if(strlen($lastLoc)>0)//this is just for the first loc
							$result[] = array("");
					}
					$lastLoc = $fullLocationName;
				}
				$result[] = array($cust,$hNo,$fullLocationName,$panel,$circuit,$cLoad."A",substr($percent,0,5)."%",$amps."A",$volts."V",($status==="A")?"On":"Off",substr($editDate,0,10));
			}
		}
		return $result;
	}
?>
