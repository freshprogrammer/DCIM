<?php 
//these are generaly validation and formmatting functions. They dont really interact with the data directly other that to check specific things
//some of this is also a type of documentation of DB values like LocationType() or DeviceType(), ect
	
	class DeviceModel
	{
		//default constants
		static $portWidth = 28;
		static $portHeight = 20;
		static $portSerperation = 2;
		
		//passed in in constructor
		public $name = "";
		public $startPort = 1;
		public $portCount = 24;
		public $coloDevice = false;
		public $gigabit = false;
		public $partOfChasis = false;
		public $doubleRow = false;
		public $portsPerSet = 12;
		public $showDeviceImage = false;
		
		//further defined in constructor based on model
		public $deviceWidthPx = 948;
		public $deviceHeightPx = 97;
		public $deviceImage = "images/devices/ex4200_front.jpg";
		
		function __construct($name, $startPort, $portCount, $coloDevice, $gigabit, $partOfChasis, $doubleRow, $portsPerSet,$showDeviceImage) 
		{
			$this->name = $name;
			$this->startPort = $startPort;
			$this->portCount = $portCount;
			$this->coloDevice = $coloDevice;
			$this->gigabit = $gigabit;
			$this->partOfChasis = $partOfChasis;
			$this->doubleRow = $doubleRow;
			$this->portsPerSet = $portsPerSet;
			$this->showDeviceImage = $showDeviceImage;
			
			//device render properties that are differnet for each model
			if($this->name=="Full Cab" || $this->name=="Half Cab-Top" || $this->name=="Half Cab-Bottom")
			{
				$this->deviceWidthPx = 950;
				$this->deviceHeightPx = 91;
				$this->deviceImage = "images/devices/patchpanel.jpg";
			}
			else if($this->name=="EX3200 24p" || $this->name=="EX4200 24p")
				$this->deviceImage = "images/devices/ex4200_24p_front.jpg";
			else if($this->name=="WS-X6348")
			{
				$this->deviceWidthPx = 950;
				$this->deviceHeightPx = 105;
				$this->deviceImage = "images/devices/ws-x6348_front.jpg";
			}
			else if($this->name=="Catalyst 3550")
			{
				$this->deviceWidthPx = 950;
				$this->deviceHeightPx = 89;
				$this->deviceImage = "images/devices/catalyst2950_front.jpg";
			}
		}
		
		public function __toString()
		{
			return $this->name;
		}
		
		public function GetPortPosition($port, &$resultX, &$resultY)
		{
			//this port possittioning per model should probably be properties of the instance setup in at instantiation
			
			$portSerperation = DeviceModel::$portSerperation;
			//dynamic defaults bassed on ex4200
			$topOffset = 27;
			$bottomOffset = 57;
			$set1Offset = 20;
			$set2Offset = 30;
			$set3Offset = 40;
			$set4Offset = 50;
			if($this->name=="Full Cab" || $this->name=="Half Cab-Top" || $this->name=="Half Cab-Bottom")
			{
				$topOffset = 2;
				$bottomOffset = 39;
				$set1Offset = 62;
				$set2Offset = 96;
				$set3Offset = 131;
				$set4Offset = 168;
			
				if($this->startPort==13)
				{//bottom half cab - shift ports to right
					$set1Offset = 491;
					$set2Offset = 527;
				}
			}
			else if($this->name=="WS-X6348")
			{
				$topOffset = 27;
				$bottomOffset = 59;
				$set1Offset = 55;
				$set2Offset = 74;
				$set3Offset = 83;
				$set4Offset = 85;
				$portSerperation = 7;
			}
			else if($this->name=="Catalyst 3550")
			{
				$topOffset = 21;
				$bottomOffset = 49;
				$set1Offset = 61;
				$set2Offset = 79;
				$set3Offset = 99;
			}
			
			//$truePortIndex is the true port no from 0 --EX: port 15 in a set 13-24 is 2
			$truePortIndex = $port-$this->startPort;
			$setNo = 0;
			$setOffset = 0;//no possition info for set 0
			if($truePortIndex < $this->portsPerSet * 1) //typicly less than 12
			{
				$setNo = 1;
				$setOffset = $set1Offset;
			}
			else if($truePortIndex < $this->portsPerSet * 2) //typicly less than 24
			{
				$setNo = 2;
				$setOffset = $set2Offset;
			}
			else if($truePortIndex < $this->portsPerSet * 3) //typicly less than 36
			{
				$setNo = 3;
				$setOffset = $set3Offset;
			}
			else if($truePortIndex < $this->portsPerSet * 4) //typicly less than 48
			{
				$setNo = 4;
				$setOffset = $set4Offset;
			}
			
			$xRow = $truePortIndex;
			if($this->doubleRow)
				$xRow = (int)($truePortIndex/2);
			
			$resultX = $setOffset + $xRow*(DeviceModel::$portWidth + $portSerperation);
			
			if($this->IsPortOnBottom($port))
				$resultY = $bottomOffset;
			else
				$resultY = $topOffset;
		}
		
		public function IsPortOnBottom($port)
		{
			//these are not mutualy exclusive - both can be on "bottom"
			if(!$this->doubleRow)
			{//single "bottom" row
				$oddOnTop = false;
				$evenOnTop = false;
			}
			else
			{
				$oddOnTop  = ($this->startPort%2!=0);
				$evenOnTop = ($this->startPort%2==0);
			}
			return (($port%2!=0 && !$oddOnTop) || ($port%2==0 && !$evenOnTop));
		}
	}
	
	function BuildDeviceModelArrays()
	{
		global $deviceModels;
		
		/* This is here and not in a small lookup table just because other code in ShowDevicePage() is customizing CSS for basicly each device and I feel better with code dependant on code instead of DB records
		 * I supose this could be a modelID in dcim_device and and a dcim_model table which might make future device additions easier but then small CSS tweaks show be done in DB values which sounds super anoying.
		 * If this ever gets so lots of new models are being added that might be a consideration, but until then this works just fine.
		 *  - That would probably require a UI way to add/edit device models including their images, offsets, colors, port counts, ect.-UGH
		 * 
		 * future field ideas:
		 * size(U), type, port spacing and seperation, brand
		 */
		$deviceModels = array();
		
		$deviceModels[] = new DeviceModel("Full Cab"		, 1,24, true,false,false,false, 6, true);//colo cabs and cages
		$deviceModels[] = new DeviceModel("Half Cab-Top"	, 1,12, true,false,false,false, 6, true);
		$deviceModels[] = new DeviceModel("Half Cab-Bottom"	,13,12, true,false,false,false, 6, true);
		$deviceModels[] = new DeviceModel("Cage"			, 1, 6, true,false,false,false, 6,false);
		
		$deviceModels[] = new DeviceModel("EX3200 24p"		, 0,24,false, true,false, true,12, true);//juniper
		$deviceModels[] = new DeviceModel("EX3200 48p"		, 0,48,false, true,false, true,12, true);
		$deviceModels[] = new DeviceModel("EX4200 24p"		, 0,24,false, true, true, true,12, true);
		$deviceModels[] = new DeviceModel("EX4200 48p"		, 0,48,false, true, true, true,12, true);
		
		$deviceModels[] = new DeviceModel("Catalyst 3550"	, 1,48,false,false,false, true,16, true);//cisco
		$deviceModels[] = new DeviceModel("WS-X6348"		, 1,48,false,false, true, true,12, true);
		$deviceModels[] = new DeviceModel("WS-X6K-SUP2-2GE"	, 1, 2,false, true, true,false, 1,false);
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
		else if($table=="dcim_room")				$descrip='Room';
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
		else if($table=="dcimlog_room")				$descrip='Room Log';
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
		else if($table=="dcim_room")				$keyFieldName='roomid';
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
		else if($table=="dcimlog_room")				$keyFieldName='roomlogid';
		else if($table=="dcimlog_site")				$keyFieldName='sitelogid';
		else if($table=="dcimlog_vlan")				$keyFieldName='vlanlogid';
		else $keyFieldName = false;
		return $keyFieldName;
	}
	
	function GetRecordPageKey($table)
	{
		//TODO cust is really the only odd ball, i should probably just change it to hno
		if($table=="dcim_customer")		$pageKey='host';
		else if($table=="dcim_device")	$pageKey='deviceid';
		else if($table=="dcim_location")$pageKey='locationid';
		else if($table=="dcim_room")	$pageKey='roomid';
		else if($table=="dcim_user")	$pageKey='userid';
		else $pageKey = null;
		return $pageKey;
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
		else if($table=="dcim_room")			$logTable='dcimlog_room';
		else if($table=="dcim_site")			$logTable='dcimlog_site';
		else if($table=="dcim_vlan")			$logTable='dcimlog_vlan';
		else $logTable = false;
		return $logTable;
	}
	
	function DoesTableHaveQAFields($table)
	{
		$result = true;
		if($table=="dcim_user")	$result=false;
		return $result;
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
	
	function CustomerDecomHelpPopup()
	{
		$result = "<span class='helpText'><span class='helpHeading'>Customer Decommission</span><BR>
1.) Delete subnets as IPs are decommissioned (note you can't currently delete IPs after deleting device connection).<BR>
2.) Delete connections to match cabling in DC.<BR>
3.) Disable & delete badges to match badge server.<BR>
4.) Update circuits turning off power if location is empty as you should at the panel.<BR>
5.) Mark device(s) inactive (will remain linked to this location for history).<BR>
6.) Mark customer as inactive.<BR>
7.) Ask co-worker to QA your work.<BR>
<BR>
Basically update everything as necessary to match the real counterparts (IPs, cables, badges, circuits)</span>";
		return $result;
	}
	
	function BadgeHelpPopup()
	{
		$result = "<span class='helpText'><span class='helpHeading'>Badges</span><BR>
All badge information here should match the information in the badge server with badges added, removed or disabled in both places at the same time.<BR>
<BR>
<span class='helpHeading'>Badge Statuses</span><BR>
<span class='helpDefinition'>Pending</span>Badge created and pending enrolment in hand scanner. Badge not given to customer yet.<BR>
<span class='helpDefinition'>Enroll</span>Updates badge enrolment date and changes status to Active.<BR>
<span class='helpDefinition'>Active</span>Badge is ready for use by the customer.<BR>
<span class='helpDefinition'>Disabled</span>Badge has been disabled manually disabled revoking customer access and can be deleted here and on the badge server at the discretion of the internal staff.<BR>
<span class='helpDefinition'>Returned</span>Badge has been returned to internal staff and can be deleted from here and badge server.<BR>
<BR>
<span class='helpDefinition'>Issue Date</span>Date badge is created.<BR> 
<span class='helpDefinition'>Enroll Date</span>Date badge holder is enrolled in hand scanner.<BR>
<span class='helpDefinition'>Badge #</span>Badge number as it appears on the physical badge and in the badge server.<BR>
<BR>
<span class='helpHeading'>Badge Deletion</span><BR>
Once a badge holder has returned their badge or it has been disabled it can be deleted with the delete button.</span>";
		return $result;
	}
	
	function UpdateSettingsForiPad()
	{
		global $loginCookieDurration;
		global $loginCookieDurrationOniPad;
		
		$isiPad = (bool) strpos($_SERVER['HTTP_USER_AGENT'],'iPad');
		
		if($isiPad)
		{
			$loginCookieDurration = $loginCookieDurrationOniPad;
		}
	}
	
	function CreateReport($title,$shortResult,$longResult, $note)
	{
		$hiddenCSSClass = "hidden";
		$visibleCSSClass = "auditDataTable";
		
		$reportID = MakeTextIntoUniqueJSVariableName($title);
		$reportDivID = $reportID;
		$reportResultLongID = $reportID."_resultLong";
		$reportResultShortID = $reportID."_resultShort";
		$reportHideFunctionName = $reportID."_table_toggle";
		$reportVisibilityVariable = $reportID."_visible";
		$reportToggleLink = $reportID."_toggleLink";
		
		$enableSwitcher = strlen($longResult)>0;
		
		$result = "";

		$result .= "<div class='auditReport' id='$reportDivID'>\n";
		$result .= "<span class='tableTitle'>$title</span>\n";
		if($enableSwitcher)
			$result .= " <a id='$reportToggleLink' href='#$reportDivID' onclick='$reportHideFunctionName();'>Toggle</a>\n";
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
		
		//js for this this report created synamicly as needed
		if($enableSwitcher)
		{
			$result .= "<script type='text/javascript'>\n";
			$result .= "	var $reportVisibilityVariable = true;//opposite of initial condition\n";
			$result .= "	function $reportHideFunctionName() {\n";
			$result .= "		$reportVisibilityVariable = !$reportVisibilityVariable;\n";
			$result .= "		if($reportVisibilityVariable)\n";
			$result .= "		{\n";
			$result .= "			//long visible\n";
			$result .= "			document.getElementById('$reportToggleLink').innerHTML = 'Hide';\n";
			$result .= "			document.getElementById('$reportResultLongID').className = '$visibleCSSClass';\n";
			$result .= "			document.getElementById('$reportResultShortID').className = '$hiddenCSSClass';\n";
			$result .= "		}\n";
			$result .= "		else\n";
			$result .= "		{\n";
			$result .= "			document.getElementById('$reportToggleLink').innerHTML = 'Details';\n";
			$result .= "			document.getElementById('$reportResultLongID').className = '$hiddenCSSClass';\n";
			$result .= "			document.getElementById('$reportResultShortID').className = '$visibleCSSClass';\n";
			$result .= "		}\n";
			$result .= "	}\n";
			$result .= "	$reportHideFunctionName();\n";
			$result .= "</script>\n";
		}
		$result .= "</div>\n";
		return $result;
	}
	
	function CreateDataTableHeader($headers, $showTech=false, $showEdit=false, $showQA=false)
	{
		$result = "<table class='data-table sortable'>\n";
		$result .= "<thead>\n";
		$result .= "<tr>\n";
		foreach($headers as $h) 
		{
			$result .= "<th class='date-table-subheadercell'>".MakeHTMLSafe($h)."</th>\n";
		}
		
		if($showTech)
			$result .= "<th class='date-table-subheadercell'>Tech</th>\n";
		if($showEdit)
		{
			$result .= "<th class='date-table-subheadercell  editButtons_hidden'>Edit</th>\n";
		}
		if($showQA)
		{
			$result .= "<th class='date-table-subheadercell  editButtons_hidden'>QA</th>\n";
		}
		$result .= "</tr>\n";
		$result .= "</thead>\n";
		return $result;
	}
	
	function CreateQACell($table, $recID, $formAction,$editUserID, $editDate, $qaUserID, $qaDate, $cell=true, $rowSpan=1)
	{
		if($cell)
			$resultHTML = "<td class='data-table-cell-button editButtons_hidden' align='center' rowspan='$rowSpan'>\n";
		else 
			$resultHTML = "<span class='editButtons_hidden'>QA: ";
			
		$qaStatus = DoesRecordRequireQA($editUserID, $editDate, $qaUserID, $qaDate);
		if($qaStatus==1)
		{
			$instanceID = end($_SESSION['page_instance_ids']);
			$resultHTML .= "<button onclick='QARecord(\"$table\",$recID,\"$formAction\",\"$instanceID\")'>QA</button>\n";
		}
		else if($qaStatus==0)
		{
			$resultHTML .= "<font color='green'>Good</font>";
		}
		else if($qaStatus==2)
		{
			$resultHTML .= "<font color='black'>Pending</font>";
		}
		if($cell)
			$resultHTML .= "</td>\n";
		else
			$resultHTML .= " </span>\n";
		return $resultHTML;
	}
	
	function CreateMessagePanel($panelTitle, $message)
	{
		$result = "<div class='panel'>\n";
		$result .= "<div class='panel-header'>$panelTitle</div>\n";
		$result .= "<div class='panel-body'>\n\n";
		$result .= $message;
		$result .= "</div>\n";
		$result .= "</div>\n";
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
				if($port==1)	 $portLetter = "(A)";
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
			$memberText = "";
			if($deviceInfo->partOfChasis)
				$memberText = "$member/";
			
			if($pic==99)//management port
			{
				$portFullName = "em-";
				$portFullName .= $memberText."0/".$port;
			}
			else if($pic==98)//console port
			{
				$portFullName = "con-";
				$portFullName .= $memberText."0/".$port;
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
				$portFullName .= $memberText.$pic."/".$port;
			}
		}
		return $portFullName;
	}
	
	function FormatLocation($siteName, $roomName, $locationName, $showSite=true)
	{
		if($showSite)
		{
			if($roomName==null)
				$fullLocationName = "(Unknown Site & Room) $locationName";
			else if($siteName==null)
				$fullLocationName = "(Unknown Site) $roomName $locationName";
			else
				$fullLocationName = "$siteName $roomName $locationName";
		}
		else
		{
			if($roomName==null)
				$fullLocationName = "(Unknown Room) $locationName";
			else
				$fullLocationName = "$roomName $locationName";
		}
		return trim($fullLocationName);
	}
	
	function Format208CircuitNumber($circuit)
	{
		return $circuit."/".((int)$circuit+2);
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
			else if($password=="7c6a180b36896a0a8c02787eeafb0e4c")//md5 of default password "password1"
				return true;
			else if($password=="b62a565853f37fb1ec1efc287bfcebf9")//md5 of default password "testPass"
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
	
	function RoomCustAccess($access)
	{
		if($access === "T") return "Customer Has Access";
		else if($access === "F") return "No Customer Access";
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
	
	function ChangeLogType($type)
	{
		if($type === "I") return "Insert";
		else if($type === "U") return "Update";
		else if($type === "D") return "Delete";
		else return "Unknown";
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
	{//1 char val
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
			$result .= " ($permission)";
		return $result;
	}
	
	function FormatSizeInFeet($w,$h)
	{
		return round($w,2)."' x ".round($h,2)."'";	
	}
	
	function RoomAccesClass($access)
	{//define room color
		if($access=="T")return "caBackground";
		else return "roomBackground";
	}
	
	function OritentationToDegrees($o)
	{
		if($o=="E")return 90;
		else if($o=="S")return 180;
		else if($o=="W")return 270;
		else return 0;//"N"
	}
	
	function Orientation($o)
	{//relative to parent
		if($o=="N") return "Normal";
		else if($o=="E") return "Right";
		else if($o=="S") return "Backwards";
		else if($o=="W") return "Left";
		else return "Unknown";
	}
	
	function FormatVLAN($vlan)
	{
		$vlan = (int)$vlan;
		if($vlan<0) return "Temp-".$vlan;
		else return $vlan;
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
			$qaDescription = "None";
		else 
		{
			if(!isset($userFullName[$qaUserID]))//not in array
				$qaUserFullName = "User#$editUserID";
			else
				$qaUserFullName = $userFullName[$qaUserID];
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
	
	//TODO these functions should be merged into one generic function
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
			//TODO handle Errors better
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
		else if($count==2)
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
			//TODO handle Errors better
			$errorMessage[] = "ValidLocation($locationID): Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
			return false;
		}
		
		$stmt->bind_Param('s', $locationID);
		
		if (!$stmt->execute())//execute 
		{
			//TODO handle Errors better
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
		else if($count==2)
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
	
	function ValidRoom(&$roomID, $shouldExist=true)
	{
		global $mysqli;
		global $errorMessage;
		
		if(!ValidGenericID($roomID,"Room ID"))
			return false;
		
		$query = "SELECT roomid 
			FROM dcim_room
			WHERE roomid=?";
				
		if (!($stmt = $mysqli->prepare($query))) 
		{
			//TODO handle Errors better
			$errorMessage[] = "ValidRoom($roomID): Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
			return false;
		}
		
		$stmt->bind_Param('s', $roomID);
		
		if (!$stmt->execute())//execute 
		{
			//TODO handle Errors better
			//failed (errorNo-error)
			$errorMessage[] = "Failed to execute ValidRoom($roomID) (" . $stmt->errno . "-" . $stmt->error . ").";
			return false;
		}
		
		$stmt->store_result();
		$count = $stmt->num_rows;
	
		if($count==1)
		{
			//update input roomid
			$stmt->bind_result($dbRoomID);
			$stmt->fetch();
			$locationID = $dbRoomID;
			
			if($shouldExist)
				return true;
			else
			{
				$errorMessage[] = "Room already exists - ValidRoom($roomID).";
				return false;
			}
		}
		else if($count==2)
		{
			$errorMessage[] = "Multiple rooms found in ValidRoom($roomID). Contact Admin.";
			return false;
		}
		else
		{
			if($shouldExist)
			{
				$errorMessage[] = "Room not found - ValidRoom($roomID).";
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
			//TODO handle Errors better
			$errorMessage[] = "Prepare failed: ValidRecord($keyField,$keyName,$key,$table,$shouldExist) (" . $mysqli->errno . ") " . $mysqli->error;
			return false;
		}
		
		$stmt->bind_Param('s', $key);
		
		if (!$stmt->execute())//execute 
		{
			//TODO handle Errors better
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
	{//this could/should also check old dates to force regular QAs
		global $userID;
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
		$query = "SELECT $keyField FROM $table WHERE $filter";
		
		if (!($stmt = $mysqli->prepare($query)))
			$errorMessage[] = "Prepare failed: GetKeysFromFilter($table, $filter, $keyField) (" . $mysqli->errno . ") " . $mysqli->error;
		else
		{
			if (!$stmt->execute())//execute 
			{
				$errorMessage[] = "Failed to execute: GetKeysFromFilter($table, $filter, $keyField) (" . $stmt->errno . "-" . $stmt->error . ").";
				return false;
			}
			$stmt->store_result();
			$count = $stmt->num_rows;
			$stmt->bind_result($key);
			
			if($count>0)
			{
				while ($stmt->fetch()) 
					$results[] = $key;
			}
		}
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
			//TODO handle Errors better
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
		return ValidString($input,"Customer Name",3,50);
	}
	
	function ValidCustomerStatus($input)
	{
		$validFlags = array('A','I');
		return ValidFlag($input,"Customer Status",$validFlags);
	}
	
	function ValidNotes($input)
	{//maybe should filter out html here to prevent HTML injection - actualy its rendered back in a text area and should be secure unless they break out of that.
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
		return ValidNumber($input,"BadgeNo",1,8);
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
		/*global $errorMessage;
		//this should matcht the JS
		$regex = "/^(\d|\d\d|\d-\d)$/"; 
		if(!preg_match($regex,$input))
		{
			$errorMessage[] = "Invalid Panel.";
			return false;
		}*/
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
		$validFlags = array('20','30','40','50','100');
		return ValidFlag($input,"Power Amps",$validFlags);
	}
	
	function ValidPowerStatus($input)
	{
		$validFlags = array('A','D');
		return ValidFlag($input,"Power Status",$validFlags);
	}
	
	function ValidPowerLoad($input, $amps)
	{
		return ValidNumber($input,"Power Load",1,0,0,$amps);
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

	function ValidLocationName($input)
	{
		return ValidString($input,"Location name",2,50);
	}
	
	function ValidLocationAltName($input)
	{
		return ValidString($input,"Location alt name",0,50);
	}
	
	function ValidLocationType($input)
	{
		$validFlags = array('C','F','H','M','R');
		return ValidFlag($input,"Location type",$validFlags);
	}
	
	function ValidLocationUnits($input)
	{
		return ValidNumber($input,"Location units",0,2,0,50);
	}
	
	function ValidLocationOrientation($input)
	{
		$validFlags = array('N','S','E','W');
		return ValidFlag($input,"Location orientation",$validFlags);
	}
	
	function ValidLocationXPos($input)
	{
		return ValidNumber($input,"Location left possition",1,8,-9999.99,9999.99);
	}
	
	function ValidLocationYPos($input)
	{
		return ValidNumber($input,"Location foreward possition",1,8,-9999.99,9999.99);
	}
	
	function ValidLocationWidth($input)
	{
		return ValidNumber($input,"Location width",1,8,0,9999.99);
	}
	
	function ValidLocationDepth($input)
	{
		return ValidNumber($input,"Location depth",1,8,0,9999.99);
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