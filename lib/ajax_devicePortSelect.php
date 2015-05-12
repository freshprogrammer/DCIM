<?php
	require_once 'DCIMCustomFunctions.php';
	require_once 'config.php';
	require_once 'customFunctions.php';
	require_once 'genericFunctions.php';
	require_once 'helperFunctions.php';
	require_once 'functions.php';
	
	//startup
	SQLIConnect();
	BuildDeviceModelArrays();
	
	$dev = GetInput("dev");
	$dev = $dev==="true";
	
	$deviceID = GetInput("deviceid");
	$selectedPortID = GetInput("selectedportid");
	
	if($dev) echo "Dev line 2 deviceID = $deviceID<BR>";
	if($dev) echo "Dev line 3 selectedPortID = $selectedPortID<BR>";
	global $mysqli;
	
	$query = "SELECT 
				dp.deviceid, dp.deviceportid, d.name, d.member, d.model, dp.pic, dp.port, dp.mac,
				dp.type, dp.speed, dp.note, dp.status, dp.edituser, dp.editdate
			FROM dcim_device AS d
				LEFT JOIN dcim_deviceport AS dp ON d.deviceid=dp.deviceid
			WHERE d.deviceid=? AND (dp.status='D' OR dp.deviceportid=?)
			ORDER BY 3,4,6,7";
	
	if (!($stmt = $mysqli->prepare($query))) 
	{
		//TODO handle errors better
		echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "</BR>";
	}
	if($dev) echo "Dev line 5<BR>\n";
	
	$stmt->bind_Param('ss', $deviceID, $selectedPortID);
	$stmt->execute();
	$stmt->store_result();
		$stmt->bind_result($deviceID, $devicePortID, $deviceName, $member, $model, $pic, $port, $mac, 
						   $type, $speed, $note, $status, $editUserID, $editDate);
	$count = $stmt->num_rows;
	
	if($dev) echo "Dev line 6. query = '$query'<BR>\n";
	if($dev) echo "Dev line 7. count = $count<BR>";
	if($count>0)
	{
		//list result data
		while ($stmt->fetch()) 
		{
			if($dev) echo "Dev line 8. devicePortID = $devicePortID<BR>";
			$portFullName = FormatPort($member, $model, $pic, $port, $type);
			echo "<option value='$devicePortID'>$portFullName</option>\n";
		}
	}
	
	SQLIDisconnect();
?>
