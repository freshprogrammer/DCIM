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
	LoadConfigVariables();//probably not necisarry
	
	$function = GetInput("function");
	
	if($function=='RoomSelect') RoomSelect();
	else if($function=='DevicePortSelect') DevicePortSelect();
	else if($function=='ValidVlan') ValidVlan();
	else if($function=='QA') QA();
	
	SQLIDisconnect();
	return;//end of base code
	
	/* Ajax Functions */
	
	function QA()
	{
		global $userID;
		$permissionLevel = UpdatePermissionLevel();
		
		$dev = GetInput("dev")==="true";
		$table = GetInput("table");
		$key = GetInput("id");
		
		if($dev) echo "Dev line 2 table = $table<BR>";
		if($dev) echo "Dev line 3 key = $key<BR>";
		
		$result = QARecord($table,$key);
		if($dev)
		{//bring in results/errors from QARecord()
			global $errorMessage;
			global $resultMessage;
			echo "<br><br>errors:<br>";
			echo implode("<br>",$errorMessage);
			echo "<br><br>results:<br>";
			echo implode("<br>",$resultMessage);
		}
		if($result==1) echo "<font color=green>Success</font>";
		else if($result==0) echo "<font color=yellow>Successfully QAed - but $result affected</font>";
		else echo "<font color=red>Failed to QA</font>";
	}
	
	function RoomSelect()
	{
		global $mysqli;
		
		$dev = GetInput("dev")==="true";
		$roomID = GetInput("roomid");
		$selectedLocationID = GetInput("selectedlocationid");
		
		if($dev) echo "Dev- roomID = $roomID<BR>";
		if($dev) echo "Dev- selectedlocationid = $selectedLocationID<BR>";
		
		$query = "SELECT r.roomid, r.name, l.locationid, l.name
			FROM dcim_room AS r
				INNER JOIN dcim_location AS l ON l.roomid=r.roomid
			WHERE r.roomid=?
			ORDER BY r.name, l.name";
		
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $roomID) || !$stmt->execute())
		{
			echo "RoomSelect() Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "</BR>";
			return;
		}
		if($dev) echo "Dev line 5<BR>\n";
		
		$stmt->store_result();
			$stmt->bind_result($roomID, $room, $locationID, $location);
		
		if($dev) echo "Dev- count = ".$stmt->num_rows." - query = '$query'<BR>\n";
		if($stmt->num_rows>0) while ($stmt->fetch()) 
		{//list result data
			if($dev) echo "Dev line 8. ($roomID,$locationID,$location) <BR>";
			$fullLocationName = FormatLocation("", $room, $location, false);
			$selected = ($locationID==$selectedLocationID? "Selected" : "");
			echo "<option value='$locationID' $selected>$fullLocationName</option>\n";
		}
		else
		{
			echo "<option value='-1'> - No Locations Found - </option>\n";
		}
	}
	
	function DevicePortSelect()
	{
		global $mysqli;
		
		BuildDeviceModelArrays();
		
		$dev = GetInput("dev")==="true";
		$deviceID = GetInput("deviceid");
		$selectedPortID = GetInput("selectedportid");
		
		if($dev) echo "Dev- deviceID = $deviceID<BR>";
		if($dev) echo "Dev- selectedPortID = $selectedPortID<BR>";
		
		$query = "SELECT
				dp.deviceid, dp.deviceportid, d.name, d.member, d.model, dp.pic, dp.port, dp.mac,
				dp.type, dp.speed, dp.note, dp.status, dp.edituser, dp.editdate
			FROM dcim_device AS d
				LEFT JOIN dcim_deviceport AS dp ON d.deviceid=dp.deviceid
			WHERE d.deviceid=? AND (dp.status='D' OR dp.deviceportid=?)
			ORDER BY 3,4,6,7";
		
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('ss', $deviceID, $selectedPortID) || !$stmt->execute())
		{
			echo "DevicePortSelect() Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "</BR>";
			return;
		}
		$stmt->store_result();
		$stmt->bind_result($deviceID, $devicePortID, $deviceName, $member, $model, $pic, $port, $mac,
				$type, $speed, $note, $status, $editUserID, $editDate);
		
		if($dev) echo "Dev- count = ".$stmt->num_rows." - query = '$query'<BR>\n";
		if($stmt->num_rows>0)
		{
			//list result data
			while ($stmt->fetch())
			{
				if($dev) echo "Dev line 8. devicePortID = $devicePortID<BR>";
				$portFullName = FormatPort($member, $model, $pic, $port, $type);
				echo "<option value='$devicePortID'>$portFullName</option>\n";
			}
		}
		else
		{
			if($deviceID==-1) echo "<option value=-1>-Select a device-</option>\n";
			else echo "<option value=-1>-No available ports found</option->\n";
		}
	}
	
	function vlanFilter($v)
	{//return true for vlans to keep
		return $v!=0;
	}
	function ValidVlan()
	{//THIS IS NEVER CALLED
		global $mysqli;
		
		$dev = GetInput("dev")==="true";
		$vlanInput = GetInput("vlan");
		
		$vlanInput = str_replace(" ","", $vlanInput);
		$vlanInput = str_replace(";",",", $vlanInput);
		$vlanInput = str_replace(":",",", $vlanInput);
		
		$vlans = explode(",", $vlanInput);
		$vlans = array_filter($vlans, "vlanFilter");
		$vlans = array_unique($vlans);
		$vlanCount = count($vlans);
		
		if($dev)
		{
			echo "Dev line 2 vlanInput = $vlanInput<BR>";
			foreach ($vlans as $v) echo "Dev line 3- vlans[] = $v<BR>";
			echo "Dev line 4 vlanCount = $vlanCount<BR>";
		}
		$clause = implode(',', array_fill(0, count($vlans), '?'));
		
		$arrayOfResults = array();
		$array_of_params = array();
		$array_of_params[0] = "";
		foreach($vlans as $value)
		{
			$array_of_params[0] .= 'i';
			$array_of_params[] = $value;
			$arrayOfResults[$value] = false;
		}
		
		$query = "SELECT vlan FROM dcim_vlan
			WHERE vlan IN ($clause)
			GROUP BY vlan";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "</BR>";
			return;
		}
		if($dev) echo "Dev line 5<BR>\n";
		
		//$stmt->bind_Param('s', $vlanSQLFilter);
		call_user_func_array(array(&$stmt, 'bind_param'), $array_of_params);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($vlan);
		
		if($dev) echo "Dev- count = ".$stmt->num_rows." - query = '$query'<BR>\n";
		if($stmt->num_rows>0)
		{
			if($stmt->num_rows==$vlanCount) echo "valid";
			else
			{
				//list result data
				while ($stmt->fetch())
				{
					if($dev) echo "Dev line 8 - IN DB. vlan = $vlan<BR>\n";
					$arrayOfResults[$vlan] = true;
				}
				
				if($dev)echo "SQL Results<BR>";
				foreach ($vlans as $v)
				{
					if($arrayOfResults[$v])
					{//found
						if($dev)echo "arrayOfResults[$v] = ".$arrayOfResults[$v]."<BR>";
					}
					else
					{//not found
						echo "VLAN $v was not found. ";
					}
				}
			}
		}
	}
?>