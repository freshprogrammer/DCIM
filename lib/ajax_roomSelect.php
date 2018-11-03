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
	
	$dev = GetInput("dev")==="true";
	$roomID = GetInput("roomid");
	$selectedLocationID = GetInput("selectedlocationid");
	
	if($dev) echo "Dev line 2 roomID = $roomID<BR>";
	if($dev) echo "Dev line 3 selectedlocationid = $selectedLocationID<BR>";
	global $mysqli;
	
	$query = "SELECT r.roomid, r.name, l.locationid, l.name
		FROM dcim_room AS r
			INNER JOIN dcim_location AS l ON l.roomid=r.roomid
		WHERE r.roomid=?
		ORDER BY r.name, l.name";
	
	if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $roomID) || !$stmt->execute())
	{
		echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "</BR>";
		return;
	}
	if($dev) echo "Dev line 5<BR>\n";
	
	$stmt->store_result();
		$stmt->bind_result($roomID, $room, $locationID, $location);
	
	if($dev) echo "Dev line 6. count = ".$stmt->num_rows." - query = '$query'<BR>\n";
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
	SQLIDisconnect();
?>