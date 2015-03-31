<?php 
//these are generaly validation and formmatting functions. They dont really interact with the data directly other that to check specific things

	function SQLIConnect()
	{
		global $mysqli;
		global $db_host;
		global $db_user;
		global $db_password;
		global $database;
		
		$mysqli = new mysqli($db_host, $db_user, $db_password, $database);
		
		/* check connection */
		if (mysqli_connect_errno()) {
			printf("Connect failed: %s\n", mysqli_connect_error());
			exit();
		}
	}
	
	function SQLIDisconnect()
	{
		global $mysqli;
		
		$mysqli->close();
	}
	
	class DeviceModel 
	{
		public $name = "";
		public $startPort = 1;
		public $portCount = 24;
		public $coloDevice = false;
		public $gigabit = false;
		public $partOfChasis = false;
		public $doubleRow = false;
		public $portsPerSet = 12;
		
		function __construct($name, $startPort, $portCount, $coloDevice, $gigabit, $partOfChasis, $doubleRow, $portsPerSet) 
		{
			$this->name = $name;
			$this->startPort = $startPort; 
			$this->portCount = $portCount; 
			$this->coloDevice = $coloDevice; 
			$this->gigabit = $gigabit; 
			$this->partOfChasis = $partOfChasis; 
			$this->doubleRow = $doubleRow; 
			$this->portsPerSet = $portsPerSet; 
		}

		public function __toString()
		{
			return $this->name;
		}
	}
	
	function BuildDeviceModelArrays()
	{
		global $deviceModels;
		
		//portStartNo,portCount, size, type, port spacing, brand
		$deviceModels = array();
		
		$deviceModels[] = new DeviceModel("Full Cab"		, 1,24, true,false,false,false, 6);//colo
		$deviceModels[] = new DeviceModel("Half Cab-Top"	, 1,12, true,false,false,false, 6);
		$deviceModels[] = new DeviceModel("Half Cab-Bottom"	,13,12, true,false,false,false, 6);
		$deviceModels[] = new DeviceModel("Cage"			, 1, 6, true,false,false,false, 6);
		
		$deviceModels[] = new DeviceModel("EX3200 24p"		, 0,24,false, true, true, true,12);//juniper
		$deviceModels[] = new DeviceModel("EX3200 48p"		, 0,48,false, true, true, true,12);
		$deviceModels[] = new DeviceModel("EX4200 24p"		, 0,24,false, true, true, true,12);
		$deviceModels[] = new DeviceModel("EX4200 48p"		, 0,48,false, true, true, true,12);
		
		$deviceModels[] = new DeviceModel("Catalyst 3550"	, 1,48,false,false,false, true,16);//leaf
		$deviceModels[] = new DeviceModel("WS-X6348"		, 1,48,false,false, true, true,12);//DAS13
		$deviceModels[] = new DeviceModel("WS-X6K-SUP2-2GE"	, 1, 2,false, true, true,false, 1);//DAS13 supervisor
	}
	
	function GetTableRecordDescription($table) 
	{
		if($table=="dcim_badge")					$descrip='Badge';
		else if($table=="dcim_customer")			$descrip='Customer';
		else if($table=="dcim_device")				$descrip='Device';
		else if($table=="dcim_deviceport")			$descrip='Device Port';
		else if($table=="dcim_portconnection")		$descrip='Port Connection';
		else if($table=="dcim_location")			$descrip='Location';
		else if($table=="dcim_portvlan")			$descrip='Port VLAN';
		else if($table=="dcim_power")				$descrip='Power Circuit';
		else if($table=="dcim_powerloc")			$descrip='Power Location';
		else if($table=="dcim_site")				$descrip='Site';
		else if($table=="dcim_user")				$descrip='User';
		else if($table=="dcim_vlan")				$descrip='Subnet';
		else if($table=="dcimlog_badge")			$descrip='Badge Log';
		else if($table=="dcimlog_customer")			$descrip='Customer Log';
		else if($table=="dcimlog_device")			$descrip='Device Log';
		else if($table=="dcimlog_deviceport")		$descrip='Device Port Log';
		else if($table=="dcimlog_portconnection")	$descrip='Port Connection Log';
		else if($table=="dcimlog_location")			$descrip='Location Log';
		else if($table=="dcimlog_portvlan")			$descrip='Port VLAN Log';
		else if($table=="dcimlog_power")			$descrip='Power Circuit Log';
		else if($table=="dcimlog_powerloc")			$descrip='Power Location Log';
		else if($table=="dcimlog_site")				$descrip='Site Log';
		else if($table=="dcimlog_vlan")				$descrip='Subnet Log';
		else $descrip = false;
		return $descrip;
	}
	
	function GetKeyField($table) 
	{
		if($table=="dcim_badge")					$keyFieldName='badgeid';
		else if($table=="dcim_customer")			$keyFieldName='hno';
		else if($table=="dcim_device")				$keyFieldName='deviceid';
		else if($table=="dcim_deviceport")			$keyFieldName='deviceportid';
		else if($table=="dcim_portconnection")		$keyFieldName='portconnectionid';
		else if($table=="dcim_location")			$keyFieldName='locationid';
		else if($table=="dcim_portvlan")			$keyFieldName='portvlanid';
		else if($table=="dcim_power")				$keyFieldName='powerid';
		else if($table=="dcim_powerloc")			$keyFieldName='powerlocid';
		else if($table=="dcim_site")				$keyFieldName='siteid';
		else if($table=="dcim_user")				$keyFieldName='userid';
		else if($table=="dcim_vlan")				$keyFieldName='vlanid';
		else if($table=="dcimlog_badge")			$keyFieldName='badgelogid';
		else if($table=="dcimlog_customer")			$keyFieldName='customerlogid';
		else if($table=="dcimlog_device")			$keyFieldName='devicelogid';
		else if($table=="dcimlog_deviceport")		$keyFieldName='deviceportlogid';
		else if($table=="dcimlog_portconnection")	$keyFieldName='portconnectionlogid';
		else if($table=="dcimlog_location")			$keyFieldName='locationlogid';
		else if($table=="dcimlog_portvlan")			$keyFieldName='portvlanlogid';
		else if($table=="dcimlog_power")			$keyFieldName='powerlogid';
		else if($table=="dcimlog_powerloc")			$keyFieldName='powerloclogid';
		else if($table=="dcimlog_site")				$keyFieldName='sitelogid';
		else if($table=="dcimlog_vlan")				$keyFieldName='vlanlogid';
		else $keyFieldName = false;
		return $keyFieldName;
	}
	
	function GetLogTable($table) 
	{
		if($table=="dcim_badge")				$logTable='dcimlog_badge';
		else if($table=="dcim_customer")		$logTable='dcimlog_customer';
		else if($table=="dcim_device")			$logTable='dcimlog_device';
		else if($table=="dcim_deviceport")		$logTable='dcimlog_deviceport';
		else if($table=="dcim_portconnection")	$logTable='dcimlog_portconnection';
		else if($table=="dcim_location")		$logTable='dcimlog_location';
		else if($table=="dcim_portvlan")		$logTable='dcimlog_portvlan';
		else if($table=="dcim_power")			$logTable='dcimlog_power';
		else if($table=="dcim_powerloc")		$logTable='dcimlog_powerloc';
		else if($table=="dcim_site")			$logTable='dcimlog_site';
		else if($table=="dcim_vlan")			$logTable='dcimlog_vlan';
		else $logTable = false;
		return $logTable;
	}
	
	function ClearUserLogin()
	{
		//update cookie info - remove rights - set time to -1h - for some reason just the un set doesnt work and the cooking still exists a frame later
		setcookie("dcim_user", "", time()-(3600));
		setcookie("dcim_password", "", time()-(3600));
		
		unset($_COOKIE['dcim_user']);
		unset($_COOKIE['dcim_password']);
	}
	
	//more specific functions
	function BuildUsersHashTable()
	{
		//NOTE using PHP associative array and not TRUE HashTables
		global $mysqli;
		global $userName;
		global $userFullName;
		global $userInitials;
		
		$userName = array();
		$userFullName = array();
		$userInitials = array();
		
		$query = "SELECT userid, username, name, initials
		FROM dcim_user";

		if (!($stmt = $mysqli->prepare($query)))
		{
			//TODO hadnle errors better
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($userID, $uName, $uFullName, $uInitials);
		
		while ($stmt->fetch()) 
		{
			$userName["$userID"] = MakeHTMLSafe($uName);
			$userFullName["$userID"] = MakeHTMLSafe($uFullName);
			$userInitials["$userID"] = MakeHTMLSafe($uInitials);
		}
	}
 	
 	function CreateReport($title,$shortResult,$longResult, $note)
 	{
 	 	$hiddenCSSClass = "hidden";
 	 	$visibleCSSClass = "auditDataTable";
 		
 		$reportID = MakeTextIntoUniqueJSVariableName($title);
 	 	$reportResultLongID = $reportID."_resultLong";
 	 	$reportResultShortID = $reportID."_resultShort";
 	 	$reportHideFunctionName = $reportID."_table_toggle";
 	 	$reportVisibilityVariable = $reportID."_visible";
 	 	$reportToggleLink = $reportID."_toggleLink";
 	 	
 	 	$enableSwitcher = strlen($longResult)>0;
 	 	
 	 	$result = "";

 	 	$result .= "<div class='auditReport'>\n";
		$result .= "<span class='tableTitle'>$title</span>\n";
		if($enableSwitcher)
		 	$result .= " <a id='$reportToggleLink' href='#' onclick='$reportHideFunctionName();'>Toggle</a>\n";
		$result .= "<BR>\n";
		
		if(strlen($note)>0)
		 	$result .= "$note<BR>\n";
		
		if($enableSwitcher)
		{
 		 	$result .= "<div id='$reportResultLongID'>\n";
 		 	$result .= $longResult."\n";
 		 	$result .= "</div>\n";
		}
		
		if($enableSwitcher)
	 	 	$result .= "<div id='$reportResultShortID' onclick='$reportHideFunctionName();'>\n";
 	 	else
	 	 	$result .= "<div id='$reportResultShortID'>\n";
	 	$result .= $shortResult."\n";
	 	$result .= "</div>\n";
	 	
 			//js
		if($enableSwitcher)
		{
 			$result .= "<script type='text/javascript'>\n";
 	 	 	$result .= "	var $reportVisibilityVariable = true;//opposite of initial condition\n";
 	 	 	$result .= " 	function $reportHideFunctionName() {\n";
 	 	 	$result .= " 	 	$reportVisibilityVariable = !$reportVisibilityVariable;\n";
 	 	 	$result .= " 	 	if($reportVisibilityVariable)\n";
 	 	 	$result .= " 	 	{\n";
 	 	 	$result .= "	 	 	//long visible\n";
 	 	 	$result .= "			document.getElementById('$reportToggleLink').innerHTML = 'Hide';\n";
 	 	 	$result .= "			document.getElementById('$reportResultLongID').className = '$visibleCSSClass';\n";
 	 	 	$result .= "			document.getElementById('$reportResultShortID').className = '$hiddenCSSClass';\n";
 	 	 	$result .= " 	 	}\n";
 	 	 	$result .= " 	 	else\n";
 	 	 	$result .= " 	 	{\n";
 	 	 	$result .= "			document.getElementById('$reportToggleLink').innerHTML = 'Details';\n";
 	 	 	$result .= "			document.getElementById('$reportResultLongID').className = '$hiddenCSSClass';\n";
 	 	 	$result .= "			document.getElementById('$reportResultShortID').className = '$visibleCSSClass';\n";
 	 	 	$result .= " 	 	}\n";
 	 	 	$result .= " 	}\n";
 	 	 	$result .= " 	$reportHideFunctionName();\n";
 	 	 	$result .= "</script>\n";
		}
 	 	$result .= "</div>\n";
		echo $result;
 	}
	
	function CreateDataTableHeader($headers, $showTech=false, $showEditAndQA=false)
	{
		$result = "<table class='data-table'>\n";
		$result .= "<thead>\n";
		$result .= "<tr>\n";
		foreach($headers as $h) 
		{
			$result .= "<th class='date-table-subheadercell'>".MakeHTMLSafe($h)."</th>\n";
		}
		
		if($showTech)
			$result .= "<th class='date-table-subheadercell'>Tech</th>\n";
		if($showEditAndQA)
		{
			$result .= "<th class='date-table-subheadercell  editButtons_hidden'>Edit</th>\n";
			$result .= "<th class='date-table-subheadercell  editButtons_hidden'>QA</th>\n";
		}
		$result .= "</tr>\n";
		$result .= "</thead>\n";
		return $result;
	}
 	
 	function FormatSimpleMessage($msg, $severity)
 	{
 	 	if ($severity==3)
 	 	 	$result = "<table margin=><tr><td bgcolor=red class='simpleMessageCell'>$msg</td></tr></table>";
 	 	else if ($severity==2)
 	 	 	$result = "<table margin=><tr><td bgcolor=orange class='simpleMessageCell'>$msg</td></tr></table>";
 	 	else if ($severity==1)
 	 	 	$result = "<table margin=><tr><td bgcolor=lime class='simpleMessageCell'>$msg</td></tr></table>";
 	 	else
 	 	 	$result = "<table margin=><tr><td bgcolor=pink class='simpleMessageCell'>$msg</td></tr></table>";
 	 	 	
 	 	return $result;
 	}
	
	function GetDeviceFromModelName($deviceModelName)
	{
		global $deviceModels;
		
		$device = null;
		foreach($deviceModels as $model)
		{
			if($model->name==$deviceModelName)
			{
			 	$device = $model;
			 	break;
			}
		}
		return $device;
	}
	
	function GetDeviceFullName($deviceName, $deviceModel, $deviceMember, $short)
	{
		$deviceInfo = GetDeviceFromModelName($deviceModel);
		
		$deviceFullName = $deviceName;
 		if($deviceInfo!=null && $deviceInfo->partOfChasis)
 		{
 		 	if($short)
	 	 	 	$deviceFullName = $deviceName." M#".$deviceMember;
 		 	else
	 	 	 	$deviceFullName = $deviceName." Member #".$deviceMember;
 		}
 		return $deviceFullName;
	}
	
	function FormatPort($member, $deviceModelName, $pic, $port, $type)
	{
		$portFullName = "";
		
		$deviceInfo = GetDeviceFromModelName($deviceModelName);
		
		if($deviceInfo==null)
		{
			$portFullName = "??-".$member."/".$pic."/".$port;
		}
		else if($deviceInfo->coloDevice)
		{
		 	if($deviceModelName=="Cage" && $pic>0)
		 	{
		 	 	if($port==1) 	 $portLetter = "(A)";
		 	 	else if($port==2)$portLetter = "(B)";
		 	 	else if($port==3)$portLetter = "(C)";
		 	 	else if($port==4)$portLetter = "(D)";
		 	 	else if($port==5)$portLetter = "(E)";
		 	 	else if($port==6)$portLetter = "(F)";
		 	 	$portFullName = "Patch# $pic.$port$portLetter";
		 	}
		 	else 
		 	{
		 	 	$portFullName = "Patch# ".$port;
		 	}
		}
		else
		{
		 	if($pic==99)//management port
		 	{
				$portFullName = "em-";
 			 	$portFullName .= $member."/0/".$port;
		 	}
		 	else if($pic==98)//console port
		 	{
				$portFullName = "con-";
 			 	$portFullName .= $member."/0/".$port;
		 	}
			else 
			{
 				if($deviceInfo->gigabit)
 				{
 					if($type=="E")
 						$portFullName = "ge-";
 					else if($type=="F")
 						$portFullName = "sfp-";
 					else
 						$portFullName = "?-";
 				}
 				else
 					$portFullName = "fe-";
 			 	$portFullName .= $member."/".$pic."/".$port;
			}
		}
		return $portFullName;
	}
	
	function FormatLocation($siteName, $colo, $locationName)
	{
		if($siteName==null)
		 	$fullLocationName = "None";
	 	else
	 	{
	 	 	if((int)$colo >= 1)//TODO this may not be multi site compatable - should be fixed by "room" table instead of colo field
		 	 	$fullLocationName = "$siteName CA$colo $locationName";
		 	else 
		 	 	$fullLocationName = "$siteName $locationName";
	 	}
	 	return $fullLocationName;
	}
	
	function TestForSingleCustomerMatch($input)
	{
		global $mysqli;
		
		$query = "SELECT hno, cno, name, note, status, edituser 
			FROM dcim_customer 
			WHERE CONCAT('H',hno) LIKE ? OR CONCAT('C',cno) LIKE ? OR name LIKE ? OR note LIKE ?";
		
		$input = "%".$input."%";
		if (!($stmt = $mysqli->prepare($query))) 
		{
			//TODO handle errors better
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		
		$stmt->bind_Param('ssss', $input, $input, $input, $input);
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($hNo, $cNo, $name, $note, $status, $editUser);
		$count = $stmt->num_rows;
		
		if($count==1)
		{
			$stmt->fetch();
			return $hNo;
		}
		else
			return false;
	}
	
	function IsUserUsingDefaultPassword()
	{
	 	$result = false;
		if(isset($_COOKIE["dcim_password"]))
		{
 			$password = $_COOKIE["dcim_password"];
 			if($password=="f23c9a5dca7aef19a3db264c5c21a2f8")//md5 of default password "Pa55word"
 	 	 	 	return true;
		}
	 	return $result;
	}
	
	function BadgeStatus($status, $formatted=true)
	{
		if($status === "A") return  $formatted==true ? "<font color=green>Active</font>" : "Active";
		else if($status === "R") return "Returned";
		else if($status === "P") return "Pending";
		else if($status === "D") return  $formatted==true ? "<font color=red>Disabled</font>" : "Disabled";
		else return "Unknown";
	}
	
	function CustomerStatus($status, $formatted=true)
	{
		if($status === "A") return "Active";
		else if($status === "I") return  $formatted==true ? "<font color=red>Inactive</font>" : "Inactive";
		else return "Unknown";
	}
	
	function DeviceStatus($status, $formatted=true)
	{
		if($status === "A") return "Active";
		else if($status === "I") return  $formatted==true ? "<font color=red>Inactive</font>" : "Inactive";
		else return "Unknown";
	}
	
	function DevicePortStatus($status, $formatted=true)
	{
		if($status === "A") return  $formatted==true ? "<font style='color:rgb(0,209,0)'>Active</font>" : "Active";
		else if($status === "B") return  $formatted==true ? "<font color=red>Bad</font>" : "Bad";
		else if($status === "D") return  $formatted==true ? "Disabled" : "Disabled";
		else if($status === "R") return  $formatted==true ? "<font color=orange>Reserved</font>" : "Reserved";
		else return "Unknown";
	}
	
	function DevicePortType($type, $formatted=true)
	{
		if($type === "E") return "Ethernet";
		else if($type === "F") return "Fiber";
		else if($type === "O") return "Other";
		else return "Unknown";
	}
	
	function PowerStatus($status, $formatted=true)
	{
		if($status === "A") return "Active";
		else if($status === "D") return $formatted==true ? "<font color=red>Disabled</font>" : "Disabled";
		else return "Unknown";
	}
	
	function PowerOnOff($status, $formatted=true)
	{
		if($status === "A") return $formatted==true ? "<font color=green>ON</font>" : "ON";
		else if($status === "D") return $formatted==true ? "<font color=red>OFF</font>" : "OFF";
		else return "Unknown";
	}
	
	function DeviceType($type)
	{
		if($type === "C") return "Cage";
		else if($type === "F") return "Full Cab";
		else if($type === "H") return "Half Cab";
		else if($type === "S") return "Switch";
		else return "Unknown";
	}
	
	function LocationType($type)
	{
		if($type === "C") return "Cage";
		else if($type === "F") return "Full Cab";
		else if($type === "H") return "Half Cab";
		else if($type === "M") return "Misc";
		else if($type === "R") return "Rack";
		else return "Unknown";
	}
	
	function LocationVisible($visible)
	{
		if($visible === "T") return "Visible";
		else if($visible === "F") return "Invisible";
		else return "Unknown";
	}
	
	function ErrorLogType($type)
	{
		if($type === "I") return "Invalid Input";
		else if($type === "M") return "Mising Record";
		else if($type === "X") return "Existing Conflict";
		else if($type === "G") return "General Error";
		else if($type === "C") return "Connect Failure";
		else if($type === "P") return "Prepare Failure";
		else if($type === "E") return "Execute Failure";
		else if($type === "A") return "Access Restricted";
		else if($type === "Z") return "Zero Changes";
		else if($type === "T") return "Too Many Changes";
		else if($type === "0") return "Code Crash";
		else if($type === "D") return "Development";
		else return "Unknown";
	}
	
	function IsErrorTypeUserError($type)
	{
		if($type === "I") return true;
		else if($type === "M") return true;
		else if($type === "X") return true;
		else if($type === "G") return true;
		else if($type === "C") return false;
		else if($type === "P") return false;
		else if($type === "E") return false;
		else if($type === "A") return true;
		else if($type === "Z") return true;
		else if($type === "T") return false;
		else if($type === "0") return false;
		else if($type === "D") return true;
		else return true;
	}
	
	function ChangeLogType($type)
	{
		if($type === "I") return "Insert";
		else if($type === "U") return "Update";
		else if($type === "D") return "Delete";
		else return "Unknown";
	}
	
	//permission levels are limited to the magic nubers here - code should refference these functions exclusively for perm testing
	function UserHasCircuitPermission()
	{
	 	return UserHasWritePermission();
	}
	function UserHasLocationPermission()
	{
	 	return UserHasAdminPermission();
	}
	function UserHasPortAddEditPermission()
	{
	 	return UserHasWritePermission();
	}
	function UserHasPortDeletePermission()
	{
	 	return UserHasWritePermission();
	}
	function ValidReadPermission($permissionLevel)
	{
		return $permissionLevel >= 1;
	}
	function ValidWritePermission($permissionLevel)
	{
		return $permissionLevel >= 8;
	}
	function ValidAdminPermission($permissionLevel)
	{
		return $permissionLevel >= 9;
	}
	function UserHasReadPermission()
	{
	 	global $permissionLevel;
		return ValidReadPermission($permissionLevel);
	}
	function UserHasWritePermission()
	{
	 	global $permissionLevel;
		return ValidWritePermission($permissionLevel);
	}
	function UserHasAdminPermission()
	{
	 	global $permissionLevel;
		return ValidAdminPermission($permissionLevel);
	}
	function DescribeUserPermissionLevel($permission, $simple=false, $showVal=false)
	{
		//1 char val
 	 	$result = "";
 	 	
		if(ValidAdminPermission($permission))
		{
		 	if($simple)
			 	$result = "Admin";
		 	else
			 	$result = "Administrator Access";
		}
		else if(ValidWritePermission($permission))
		{
		 	if($simple)
			 	$result = "Write";
		 	else
			 	$result = "Write Access";
		}
		else if(ValidReadPermission($permission))
		{
		 	if($simple)
			 	$result = "Read";
		 	else
			 	$result = "Read Only Access";
		}
		else
		{
		 	if($simple)
			 	$result = "None";
		 	else
			 	$result = "No Permission";
		}
			
		if($showVal)
		{
		 	$result .= " ($permission)";
		}
		return $result;
	}
	
	function FormatVLAN($vlan)
	{
	 	$vlan = (int)$vlan;
	 	if($vlan<0)
	 	 	return "Temp-".$vlan;
 	 	else
 	 	 	return $vlan;
	}
	
	function FormatTechDetails($editUserID, $editDate, $visibleText="", $qaUserID=-1, $qaDate="")
	{
		global $userInitials;
		global $userFullName;
		
		$editUserID = (int)$editUserID;
		
		if(is_nan($editUserID))
		{//not a number
		 	return "$editUserID is nan";
		}
		
		$editUserInitials = "";
		$editUserFullName = "";
		$qaDescription = "";
		if(!isset($userFullName[$editUserID]))
		{//not in array
		 	$editUserFullName = "User#$editUserID";
		 	$editUserInitials = "#$editUserID";
		}
		else
		{
		 	$editUserFullName = $userFullName[$editUserID];
		 	$editUserInitials = $userInitials[$editUserID];
		}
	
		if($qaUserID ==-1)
		{
		 	$qaDescription = "None";
		}
		else 
		{
 			if(!isset($userFullName[$qaUserID]))
 			{//not in array
 			 	$qaUserFullName = "User#$editUserID";
 			}
 			else
 			{
 			 	$qaUserFullName = $userFullName[$qaUserID];
 			}
		 	$qaDescription = "$qaUserFullName";//no need to show date here
		}
		
		$lastEditBrief = $userInitials[$editUserID];
		$lastEditFull = $editUserFullName . ": ".$editDate." QA:$qaDescription";
		
		if(strlen($visibleText)==0)
			$visibleText = $lastEditBrief;
		return "<span title=\"$lastEditFull\">$visibleText</span>";
	}
	
	function ValidNumber($input, $fieldName, $minLen, $maxLen=0, $minVal=-1, $maxVal=-1)
	{
		global $errorMessage;
		$input = (int)$input;
		if(strlen($input) < $minLen)
		{
			$errorMessage[] = $fieldName." length cannot be less than ".$minLen.".";
			return false;
		}
		else if($maxLen >0 && strlen($input) > $maxLen)
		{
			$errorMessage[] = $fieldName." length cannot be greater than ".$maxLen.".";
			return false;
		}
		else if(!is_numeric($input))
		{
			$errorMessage[] = $fieldName." \"".$input ."\" must be numeric";
			return false;
		}
		else if($minVal!=-1 && $input<$minVal)
		{
			$errorMessage[] = $fieldName." ".$input ." must be greater than ".$minVal;
			return false;
		}
		else if($maxVal!=-1 && $input>$maxVal)
		{
			$errorMessage[] = $fieldName." ".$input ." must be less than ".$maxVal;
			return false;
		}
		else if($minVal==-1 && $input <0)// by default must be positive
		{
			$errorMessage[] = $fieldName." cannot be negative";
			return false;
		}
		else
			return true;
	}
	
	function ValidString($input, $fieldName, $minLen=0, $maxLen=0)
	{
		global $errorMessage;
		if(strlen($input) < $minLen)
		{
			$errorMessage[] = $fieldName." length cannot be less than ".$minLen.".";
			return false;
		}
		else if($maxLen >0 && strlen($input) > $maxLen)
		{
			$errorMessage[] = $fieldName." length cannot be greater than ".$maxLen.".";
			return false;
		}
		else
			return true;
	}
	
	function ValidFlag($input, $fieldName, $validFlags)
	{
		global $errorMessage;
		$minLen = 1;
		$maxLen = 0;
		if(strlen($input) < $minLen)
		{
			$errorMessage[] = $fieldName." length cannot be less than ".$minLen.".";
			return false;
		}
		else if($maxLen >0 && strlen($input) > $maxLen)
		{
			$errorMessage[] = $fieldName." length cannot be greater than ".$maxLen.".";
			return false;
		}
		else
		{
			$valid = false;
			foreach($validFlags as $flag)
			{
				if(strcmp($input,$flag)==0)
				{
					$valid=true;
					break;
				}
			}
			
			if(!$valid)
			{
				$errorMessage[] = $fieldName." \"".$input ."\" is invalid";
				return false;
			}
			else
				return true;
		}
	}
	
	function ValidCustomer($hNo, $shouldFind=true)
	{
		global $mysqli;
		global $errorMessage;
		
		if(!ValidHNo($hNo))
			return false;
		
		$query = "SELECT * 
			FROM dcim_customer
			WHERE hno =?";
				
		if (!($stmt = $mysqli->prepare($query))) 
		{
			$errorMessage[] = "ValidCustomer($hNo): Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			return false;
		}
		$stmt->bind_Param('s', $hNo);
		
		$stmt->execute();
		$stmt->store_result();
		$count = $stmt->num_rows;
	
		if($count==1)
		{
			if($shouldFind)
				return true;
			else
			{
				$errorMessage[] = "Customer already exists - ValidCustomer($hNo).";
				return false;
			}
		}
		if($count==2)
		{
			$errorMessage[] = "Multiple Customers found in ValidCustomer($hNo). Contact Admin.";
			return false;
		}
		else
		{
			if($shouldFind)
			{
				$errorMessage[] = "Customer not found - ValidCustomer($hNo).";
				return false;
			}
			else
				return true;
		}
	}
	
	function ValidLocation(&$locationID, $shouldExist=true)
	{
		global $mysqli;
		global $errorMessage;
		
		if(!ValidGenericID($locationID,"Location ID"))
			return false;
		
		$query = "SELECT locationid 
			FROM dcim_location
			WHERE locationid=?";
				
		if (!($stmt = $mysqli->prepare($query))) 
		{
			$errorMessage[] = "ValidLocation($locationID): Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
			return false;
		}
		
		$stmt->bind_Param('s', $locationID);
		
		if (!$stmt->execute())//execute 
		{
			//failed (errorNo-error)
			$errorMessage[] = "Failed to execute ValidLocation($locationID) (" . $stmt->errno . "-" . $stmt->error . ").";
			return false;
		}
		
		$stmt->store_result();
		$count = $stmt->num_rows;
	
		if($count==1)
		{
		 	//update input locationid
		 	$stmt->bind_result($dbLocationID);
			$stmt->fetch();
		 	$locationID = $dbLocationID;
		 	
			if($shouldExist)
				return true;
			else
			{
				$errorMessage[] = "Location already exists - ValidLocation($locationID).";
				return false;
			}
		}
		if($count==2)
		{
			$errorMessage[] = "Multiple locations found in ValidLocation($locationID). Contact Admin.";
			return false;
		}
		else
		{
			if($shouldExist)
			{
				$errorMessage[] = "Location not found - ValidLocation($locationID).";
				return false;
			}
			else
				return true;
		}
	}
	
	function ValidRecord($keyField, $keyName, &$key, $table, $shouldExist=true, $pullField="", &$pullFieldResult="", $reportErrors=true, $multiplesOK=false)
	{
		global $mysqli;
		global $errorMessage;
		
		if(!ValidGenericID($key,$keyName))
			return false;
		
		if(strlen($pullField)>0)
		 	$query = "SELECT $keyField, $pullField FROM $table WHERE $keyField=?";
		else
		 	$query = "SELECT $keyField FROM $table WHERE $keyField=?";
				
		if (!($stmt = $mysqli->prepare($query))) 
		{
			$errorMessage[] = "Prepare failed: ValidRecord($keyField,$keyName,$key,$table,$shouldExist) (" . $mysqli->errno . ") " . $mysqli->error;
			return false;
		}
		
		$stmt->bind_Param('s', $key);
		
		if (!$stmt->execute())//execute 
		{
			//failed (errorNo-error)
			$errorMessage[] = "Failed to execute: ValidRecord($keyField,$keyName,$key,$table,$shouldExist) (" . $stmt->errno . "-" . $stmt->error . ").";
			return false;
		}
		$stmt->store_result();
		$count = $stmt->num_rows;
	
		if($count==1)
		{
		 	//update input locationid
		 	if(strlen($pullField)>0)
		 	 	$stmt->bind_result($dbKeyID, $pullFieldResult);
		 	else
		 	 	$stmt->bind_result($dbKeyID);
			$stmt->fetch();
		 	$key = $dbKeyID;
		 	
			if($shouldExist)
				return true;
			else
			{
				if($reportErrors)$errorMessage[] = "$keyName already exists (ID:$key).";
				return false;
			}
		}
		if($count==2)
		{
		 	if(strlen($pullField)>0)
		 	 	$stmt->bind_result($dbKeyID, $pullFieldResult);
		 	else
		 	 	$stmt->bind_result($dbKeyID);
			$stmt->fetch();
		 	$key = $dbKeyID;
		 	
			if(!$multiplesOK || !$shouldExist)
		 	{
 				if($reportErrors)$errorMessage[] = "Multiple ".$keyName."s found (ID:$key).";
 				return false;
		 	}
		 	else 
		 	 	return true;
		}
		else
		{
			if($shouldExist)
			{
				if($reportErrors)$errorMessage[] = "$keyName not found (ID:$key).";
				return false;
			}
			else
				return true;
		}
	}
	
	/*
	 * returns:
	 * 0:all good
	 * 1:yes
	 * 2:requires seperate user
	 */
	function DoesRecordRequireQA($editUserID, $editDate, $qaUserID, $qaDate)
	{
		global $userID;
		
		//this could/should also check old dates to force regular QAs
		
	 	if($qaUserID==-1)
	 	{
 		 	if($editUserID===$userID)//can't QA self
 		 	 	return 2;
 		 	else//un QAed edit
 		 	 	return 1;
	 	}
 	 	return 0;
	}
	
	function GetKeysFromFilter($table, $filter, $keyField)
	{
		global $mysqli;
		global $errorMessage;
		
		$results = array();
		//$errorMessage[] = "Dev - GetKeysFromFilter($table, $filter, $keyField) -start- results = (".implode(",",$results).")";
	 	$query = "SELECT $keyField FROM $table WHERE $filter";
	 	
 	 	if (!($stmt = $mysqli->prepare($query)))
			$errorMessage[] = "Prepare failed: GetKeysFromFilter($table, $filter, $keyField) (" . $mysqli->errno . ") " . $mysqli->error;
		else
		{
 			if (!$stmt->execute())//execute 
 			{
 				//failed (errorNo-error)
 				$errorMessage[] = "Failed to execute: GetKeysFromFilter($table, $filter, $keyField) (" . $stmt->errno . "-" . $stmt->error . ").";
 				return false;
 			}
 			$stmt->store_result();
 			$count = $stmt->num_rows;
 		
 			$stmt->bind_result($key);
 			
 			if($count>0)
 			{
 			 	while ($stmt->fetch()) 
 				{
 	 	 			//$errorMessage[] = "Dev - GetKeysFromFilter($table, $filter, $keyField) -append- key = $key";
 				 	$results[] = $key;
 				}
 			}
		}
		
		//$errorMessage[] = "Dev - GetKeysFromFilter($table, $filter, $keyField) -end- results = (".implode(",",$results).")";
		return $results;
	}
	
	function ValidPowerRecord(&$powerID, $shouldExist=true, $reportErrors=true)
	{
		global $mysqli;
		global $errorMessage;
		
		if(!ValidGenericID($powerID,"Power ID"))
			return false;
		
		$query = "SELECT powerid 
			FROM dcim_power
			WHERE powerid=?";
				
		if (!($stmt = $mysqli->prepare($query))) 
		{
		 	if($reportErrors)
			 	$errorMessage[] = "ValidPowerRecord($powerID,$shouldExist): Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
			return false;
		}
		$stmt->bind_Param('i', $powerID);
		
		$stmt->execute();
		$stmt->store_result();
		$count = $stmt->num_rows;
	
		if($count==1)
		{
		 	//update input locationid
		 	$stmt->bind_result($powerID);
 	 	 	$stmt->fetch();
		 	
			if($shouldExist)
				return true;
			else
			{
				if($reportErrors)
			 	 	$errorMessage[] = "Power record already exists - ValidPowerRecord($powerID,$shouldExist).";
				return false;
			}
		}
		if($count==2)
		{
			if($reportErrors)
			 	$errorMessage[] = "Multiple Power records found in ValidPowerRecord($powerID,$shouldExist). Contact Admin.";
			return false;
		}
		else
		{
			if($shouldExist)
			{
				if($reportErrors)
			 	 	$errorMessage[] = "Power record not found - ValidPowerRecord($powerID,$shouldExist).";
				return false;
			}
			else
				return true;
		}
	}
	
	function ValidDate($input, $fieldName)
	{
		global $errorMessage;
	 	if(strlen($input) > 0)
	 	{
	 	 	$regex = "/^(19|20)\d\d-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/"; 
 			if(!preg_match($regex,$input))
 			{
			 	$errorMessage[] = "Invalid $fieldName ($input). Please format as '0000-00-00'.";
 				return false;
 			}
	 	}
		return true;
	}
	
	function ValidHNo($input)
	{
		return ValidNumber($input,"Hosting Account #",6,6);
	}
	
	function ValidCNo($input)
	{
		return ValidNumber($input,"Customer #",6,6);
	}
	
	function ValidCustomerName($input)
	{
		return ValidString($input,"Customer Name",3);
	}
	
	function ValidCustomerStatus($input)
	{
		$validFlags = array('A','I');
		return ValidFlag($input,"Customer Status",$validFlags);
	}
	
	function ValidNotes($input)
	{
		//TODO - i should filter out html here to prevent HTML injection
		//actualy its in a text area and should be secure unless they break out of that.
		return true;
	}
	
	function ValidGenericID($input, $fieldName)
	{
		return ValidNumber($input,$fieldName,1,8);//Db structure set to 8 chars for all ID fields
	}
	
	function ValidBadgeID($input)
	{
		return ValidNumber($input,"BadgeID",1);
	}
	
	function ValidBadgeNo($input)
	{
		return ValidNumber($input,"BadgeNo",1);
	}
	
	function ValidBadgeIssue($input)
	{
		return ValidDate($input,"Issue Date");
	}
	
	function ValidBadgeName($input)
	{
		return ValidString($input,"Badge Name",3);
	}
	
	function ValidDeviceName($input)
	{
		return ValidString($input,"Device Name",3);
	}
	
	function ValidDeviceUnit($input)
	{
		return ValidNumber($input,"Device Unit",1,0,0,999);
	}
	
	function ValidDeviceSize(&$input)
	{
		global $errorMessage;
	 	$input = trim(strtoupper($input));
	
 		if($input=="FULL")
 		{
 		 	$input = "Full";
	 	 	return true;
 		}
 		else if($input=="HALF")
 		{
 		 	$input = "Half";
	 	 	return true;
 		}
 		else if($input=="1/2" || $input=="1/4")
 		{
	 	 	return true;
 		}
		else if(preg_match("/^([0-5][0-9]|[1-9])U$/",$input))// #U or ##U
		{
	 	 	return true;
		}
		else if(preg_match("/^([1-9][0-9]|[0-9])X([1-9][0-9]|[0-9])$/",$input))// ##x## or #x#
		{
	 	 	$input =strtolower($input);
	 	 	return true;
		}
 		else 
 		{
 			$errorMessage[] = "Invalid Device Size";
 			return false;
 		}
	}
	
	function ValidBadgeStatus($input)
	{
		$validFlags = array('A','D','R','E','P');
		return ValidFlag($input,"Badge Status",$validFlags);
	}
	
	function ValidDeviceStatus($input)
	{
		$validFlags = array('A','I');
		return ValidFlag($input,"Device Status",$validFlags);
	}
	
	function ValidDeviceType($input)
	{
		$validFlags = array('F','H','C','S');
		return ValidFlag($input,"Device Type",$validFlags);
	}
	
	function ValidDeviceSerial($input)
	{
		return true;
	}
	
	function ValidDeviceAsset($input)
	{
		//return ValidNumber($input,"Device Asset",1,0,0,9999999);
		return true;
	}
	
	function ValidDeviceMember($input)
	{
		return ValidNumber($input,"Device Member",1,0,0,9);
	}
	
	function ValidDeviceModel($input)
	{
		global $deviceModels;
		
		$validFlags = array();
		foreach($deviceModels as $device)
		{
			$validFlags[] = $device->name;
		}
		return ValidFlag($input,"Device Model",$validFlags);
	}
	
	function ValidPassword($input)
	{
		global $errorMessage;
	 	$minLen=8;
	 	$maxLen=15;
		if(strlen($input) < $minLen)
		{
			$errorMessage[] = "Password length cannot be less than ".$minLen.".";
			return false;
		}
		else if($maxLen >0 && strlen($input) > $maxLen)
		{
			$errorMessage[] = "Password length cannot be greater than ".$maxLen.".";
			return false;
		}
		return true;
	}
	
	function ValidDevicePortType($input)
	{
		$validFlags = array('F','E','O');
		return ValidFlag($input,"Device Port Type",$validFlags);
	}
	
	function ValidDevicePortStatus($input)
	{
		$validFlags = array('A','D','R','B');
		return ValidFlag($input,"Device Port Status",$validFlags);
	}
	
	function ValidDevicePortPic($input)
	{
		return ValidNumber($input,"Port Pic",1,2,0,99);
	}
	
	function ValidDevicePortPortNo($input)
	{
		return ValidNumber($input,"Port Number",1,2,0,99);
	}
	
	function ValidDevicePortSpeed($input)
	{
		return ValidString($input,"Port Speed",0,8);
	}
	
	function ValidDevicePortNote($input)
	{
		return true;
	}
	
	function ValidDevicePortMac($input)
	{
		global $errorMessage;
 	 	//this should matcht the JS
	 	if(strlen($input) > 0)
	 	{
 		 	$regex = "/^([0-9a-f]{2}[:]){5}([0-9a-f]{2})$/"; 
 			if(!preg_match($regex,$input))
 			{
 				$errorMessage[] = "Invalid MAC address. Please format as 'ff:ff:ff:ff:ff:ff'.";
 				return false;
 			}
	 	}
		return true;
	}
	
 	function ValidPowerPanel($input)
 	{
		global $errorMessage;
 	 	//this should matcht the JS
	 	$regex = "/^(\d|\d\d|\d-\d)$/"; 
		if(!preg_match($regex,$input))
		{
			$errorMessage[] = "Invalid Panel.";
			return false;
		}
	 	return true;
 	}
 	
 	function ValidPowerCircuit($input)
 	{
		return ValidNumber($input,"Power Circuit",1,2,0,50);
 	}
	
	function ValidPowerVolts($input)
	{
		$validFlags = array('120','208');
		return ValidFlag($input,"Power Volts",$validFlags);
	}
	
	function ValidPowerAmps($input)
	{
		$validFlags = array('20','30');
		return ValidFlag($input,"Power Amps",$validFlags);
	}
	
	function ValidPowerStatus($input)
	{
		$validFlags = array('A','D');
		return ValidFlag($input,"Power Status",$validFlags);
	}
 	
 	function ValidPowerLoad($input)
 	{
		return ValidNumber($input,"Power Load",1,0,0,33);
 	}
 	
	function ValidSubnet($input)
	{
		global $errorMessage;
 	 	//this should match the JS
 	 	$regex = "/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\/(30|2[4-9])$/"; 
		if(!preg_match($regex,$input))
		{
			$errorMessage[] = "Invalid Subnet. Please format as '###.###.###.###/##'.";
			return false;
		}
		else 
 	 	 	return true;
	}
 	
	function ValidIPAddress($input, $fieldName)
	{
		global $errorMessage;
 	 	//this should match the JS
 	 	$regex = "/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/"; 
		if(!preg_match($regex,$input))
		{
			$errorMessage[] = "Invalid $fieldName ($input). Please format as '###.###.###.###'.";
			return false;
		}
		else 
 	 	 	return true;
	}
	
	function ValidSubnetMask($input)
	{
	 	return ValidIPAddress($input, "Subnet Mask");
	}
	
	function ValidSubnetGateway($input)
	{
	 	return ValidIPAddress($input, "Gateway");
	}
	
	function ValidSubnetFirstIP($input)
	{
	 	return ValidIPAddress($input, "First IP Address");
	}
	
	function ValidSubnetLastIP($input)
	{
	 	return ValidIPAddress($input, "Last IP Address");
	}
	
	function ValidSubnetNote($input)
	{
	 	return true;
	}
	
	function ValidSubnetVLAN($vlan, &$resultVLAN, &$isTemp)
	{
	 	$isTemp = $vlan<0 || (strpos($vlan,'T') !== false);
		$vlan = str_replace("T","",$vlan);
	 	if(!ValidNumber($vlan, "VLAN",1,5,-9999,9999))
	 	{
 	 	 	return false;
	 	}
	 	
 	 	$resultVLAN = abs($vlan);
 	 	if($isTemp)$resultVLAN *= -1; 
 	 	return true;
	}
	
	function ValidQARootTable($table)
	{
		global $errorMessage;
 	 	$logTable = GetLogTable($table);
 	
		if(!$logTable)
		{
		 	$errorMessage[] = "Invalid table for QA.";
		 	return false; 
		}
	 	return true; 
	}
?>