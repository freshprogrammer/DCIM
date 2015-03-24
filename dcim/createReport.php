<?php 
	set_include_path('../'); 

	include 'dcim/_dcimConfig.php';
	include 'dcim/_genericFunctions.php';
	include 'dcim/_helperFunctions.php';
	include 'dcim/_functions.php';

	//startup
	SQLIConnect();
	
	$report = GetInput("report");
	
	if($report==="ActiveBadgeList")
	{
		OutputCSV("badgelist.csv",CreateBadgeExportArray());
	}
	else if($report==="OtherReport")
	{
		OutputCSV("badgelist.csv",CreateBadgeExportArray());
	}
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
			//TODO handle errors better
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
	
?>
