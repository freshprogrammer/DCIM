<?php 
	set_include_path('../'); 

	include 'dcim/_customization.php';
	include 'dcim/_genericFunctions.php';
	include 'dcim/_helperFunctions.php';
	include 'dcim/_functions.php';

	//startup
	SQLIConnect();

	header("Content-Type: text/csv");
	header("Content-Disposition: attachment; filename=badgelist.csv");
	// Disable caching
	header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
	header("Pragma: no-cache"); // HTTP 1.0
	header("Expires: 0"); // Proxies
	
	outputCSV(CreateBadgeExportArray());
	
	function CreateBadgeExportArray()
	{
		global $mysqli;
		
		$result = array();
		
		//select empty as customer to keep return results to match search query
		$query = "SELECT c.hno, c.name AS customer, b.name, b.badgeno, b.issue, b.hand
			FROM dcim_badge AS b
			LEFT JOIN dcim_customer AS c ON c.hno = b.hno
			WHERE b.status =  'A'
			ORDER BY c.name, b.name";

		if (!($stmt = $mysqli->prepare($query)))
		{
			//TODO handle errors better
			$result[] = array("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>");
			return $result;
		}
			
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($hNo, $customer,$name, $badgeNo, $issue, $hand);
		$count = $stmt->num_rows;
	
		if($count>0)
		{
			//create result array
			//headers
			$result[] = array("Active Badge list as of ".date("F j, Y"));//EX: March 10, 2001
			$result[] = array("HNo", "Customer Name","Badge Holder", "Badge No", "Issue Date", "Enrol date");
			
			//list result data
			while ($stmt->fetch()) 
			{
				$result[] = array($hNo, $customer,$name, $badgeNo, $issue, $hand);
			}
		}
		return $result;
	}
	
?>
