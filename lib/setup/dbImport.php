<?php
	set_include_path('../'); 
	
	require_once 'DCIMCustomFunctions.php';
	require_once 'config.php';
	require_once 'customFunctions.php';
	require_once 'genericFunctions.php';
	require_once 'helperFunctions.php';
	require_once 'dataFunctions.php';
	require_once 'htmlFunctions.php';
	
	$resultMessage = array();
	$errorMessage = array();
	$debugMessage = array();
	$timeStamp = date("Y-m-d H:i:s");
	
	SQLIConnect_Admin();
	SessionSetup();
	LoadConfigVariables();
	
	//globals
	$permissionLevel = UpdatePermissionLevel();
?>
<head>
<title><?php echo $config_pageTitle;?> - DB Import</title>
<link rel="icon" type="image/x-icon" href="../../images/favicon.ico">
<link rel="stylesheet" href="../css/default.css">
<script type='text/javascript'>
function ConfirmIntent()
{
	var needConfirm = false;
	//only prompt on values that do things. - Could algo give custom messges here
	if(needConfirm)
	{
		var confirmed = false;
		var confirm = prompt("Are you sure you want to import this data? Enter 'YES' to confirm.", "");
		
		if (confirm != null)
		{
			if(confirm=="YES")
				confirmed = true;
			else
				alert("Failed Authentication. You must be not be sure.");
		}
		return confirmed;
	}
	else
		return true;
}
var importForm = null;
function SelectImportForm()
{
	var type = document.getElementById('importselect').value;
	if(importForm!=null)
	{
		importForm.style="display:none;";
	}
	importForm = document.getElementById(type+'ImportForm');
	importForm.style="";
}
</script>
</head>
<font size=5><b><?php echo $config_appName;?> - Database Import</b></font><?php
 	echo " on PHP v".phpversion()."<BR>\n";
	
	$resultMessage[] = "Results - $timeStamp";
	$errorMessage[] = "Errors";
	$debugMessage[] = "Debug Messages - $timeStamp";
	
	// End Definitions - Start Processing ---------------------------------------------------------------------------------------------
	
	$commited = false;
	
	$debugMessage[]= "-Start - testing permisions and db status";
	
	if(CustomFunctions::UserHasDevPermission())
	{
		$debugMessage[]= "-Start - has permission()";
		BuildDeviceModelArrays();
		
		$validSession = IsValidSession();
		if($validSession)
			$commited = true; //TestUserCommitment($dbScriptID);//validated in JS
			
		$debugMessage[]= "-Start - validSession = $validSession";
		$debugMessage[]= "-Start - commited = $commited";
		if($commited)
		{//must have passed all checks
			$debugMessage[]= "-Start - RunImport()";
			RunImport(true);
			$debugMessage[]= "-End - RunImport()";
		}
		else
		{
			$errorMessage[]="User not commited. Aborted.";
		}
		
		$selectOptions = "";
		$importForms = "";
		
		//Customer import form
		$formType = "Customer";
		$expextedFields = "hno,cno,name,status,note";
		$importForms .= CreateImportForm($formType,$expextedFields);
		$selectOptions .= "<option value='$formType'>$formType</option>\n";
		
		//Device import form
		$formType = "Device";
		$expextedFields = "siteName,roomName,locName,hno,name,altname,member,model,unit,type,size,status,asset,serial,note";
		$importForms .= CreateImportForm($formType,$expextedFields);
		$selectOptions .= "<option value='$formType'>$formType</option>\n";
		
		//location import form
		$formType = "Location";
		$expextedFields = "roomid,name,altname,allocation,keyno,type,units,order,xpos,ypos,width,depth,orientation,notes";
		$importForms .= CreateImportForm($formType,$expextedFields);
		$selectOptions .= "<option value='$formType'>$formType</option>\n";
		
		//Power Panel import form
		$formType = "Power Panel";
		$expextedFields = "site,room,name,ups,circuits,amps,xpos,ypos,width,depth,orientation,notes";
		$importForms .= CreateImportForm($formType,$expextedFields);
		$selectOptions .= "<option value='$formType'>$formType</option>\n";
		
		//Power Circuits import form
		$formType = "Power Circuits";
		$expextedFields = "site, room, location, panel, circuit, volts, amps, status, load, phase";
		$importForms .= CreateImportForm($formType,$expextedFields);
		$selectOptions .= "<option value='$formType'>$formType</option>\n";
		
		echo "</BR>";
		echo "Import Type:
		<select id='importselect' name='scriptid' onchange='SelectImportForm()'>
			<option value='0'> - Select Import Type - </option>
			$selectOptions
		</select>
		</BR>";
		echo $importForms;
		
		$debugMessageString  = implode("<BR>\n",$debugMessage);
		$errorMessageString  = implode("<BR>\n",$errorMessage);
		$resultMessageString = implode("<BR>\n",$resultMessage);
		if(strlen($debugMessageString) > 0) echo "<!-- DEBUG MESSAGE  -->\n<div id='debugMessage'  class='debugMessage'>$debugMessageString</div>\n";
		if(strlen($errorMessageString) > 0) echo "<!-- ERROR MESSAGE  -->\n<div id='errorMessage'  class='errorMessage'>$errorMessageString</div>\n";
		if(strlen($resultMessageString) > 0)echo "<!-- RESULT MESSAGE -->\n<div id='resultMessage' class='resultMessage'>$resultMessageString</div>\n";
	}
	else
	{
		$errorMessage = array();
		$errorMessage[]="You do not have access to import data.";
		
		$errorMessageString  = implode("<BR>\n",$errorMessage);
		if(strlen($errorMessageString) > 0) echo "<!-- ERROR MESSAGE  -->\n<div id='errorMessage'  class='errorMessage'>$errorMessageString</div>\n";
	}
	//echo "Page Content - Page End<BR>";
	
//END PAGE - Begin local Functions
	
	function CreateImportForm($importType, $expectedFields)
	{
		return "<form action='' id='".$importType."ImportForm' method='post' onsubmit='return ConfirmIntent()' style='display:none;'>
		$importType Import Data - $expectedFields:</BR>
		<textarea name='importdata' rows='5' cols='140' placeholder='$expectedFields'></textarea></BR>
		<input type='submit' value='$importType Import'>
		<input type='hidden' name='importtype' value='$importType'>
		<input type='hidden' name='page_instance_id' value='".end($_SESSION['page_instance_ids'])."'>
		</form>";
	}
	
	function RunImport($fullProcessing=false)
	{
		global $debugMessage;
		$importType= GetInput("importtype",true,false);
		$debugMessage[]="Running $importType Import";
		
		if($importType=="Customer")	ImportCustomers($fullProcessing);
		else if($importType=="Location")	ImportLocations($fullProcessing);
		else if($importType=="Power Panel")	ImportPowerPanels($fullProcessing);
		else if($importType=="Power Circuits")	ImportPowerCircuits($fullProcessing);
		else if($importType=="Device")	ImportDevices($fullProcessing);
	}
	
	class CustomerRec
	{
		public $hno;
		public $cno;
		public $name;
		public $status;
		public $notes;
		
		function __construct($hno,$cno,$name,$status,$notes)
		{
			//final data processing
			$this->hno		= $hno;
			$this->cno		= $cno;
			$this->name		= $name;
			$this->status	= $status;
			$this->notes	= $notes;
		}
	}
	
	function ImportCustomers($fullProcessing=false)
	{
		global $errorMessage;
		global $resultMessage;
		
		//customer - hno,cno,name,status,note
		$customerData= GetInput("importdata",true,false);
		
		$customerData= str_replace("\n",",",$customerData);
		$customerData= explode(",", $customerData);
		
		$importObjects = array();
		$i = 0;
		$fields = 5;
		
		$hno="";
		$cno="";
		$name="";
		$status="";
		$notes="";
		
		foreach ($customerData as $rec)
		{
			switch($i % $fields)
			{
				case 0:$hno		=trim($rec);break;
				case 1:$cno		=trim($rec);break;
				case 2:$name	=trim($rec);break;
				case 3:$status	=trim($rec);break;
				case 4:$notes	=trim($rec);
				
				$importObjects[]= new CustomerRec($hno,$cno,$name,$status,$notes);
				break;
			}
			$i++;
		}
		
		//add Customer
		if($fullProcessing) $resultMessage[]="Adding ".count($importObjects)." Customers...";
		else $resultMessage[]="Dry run adding ".count($importObjects)." Customers";
		
		foreach ($importObjects as $rec)
		{//spoof input
			$_GET['hno']= $rec->hno;
			$_GET['cno']= $rec->cno;
			$_GET['name']= $rec->name;
			$_GET['notes']= $rec->notes;
			$_GET['status']= $rec->status;
			
			if($fullProcessing) ProcessCustomerAction("Customer_Add");
		}
	}
	
	class LocationRec
	{
		public $roomid;
		public $name;
		public $altname;
		public $type;
		public $units;
		public $orientation;
		public $keyno;
		public $allocation;
		public $order;
		public $xpos;
		public $ypos;
		public $width;
		public $depth;
		public $notes;
		
		function __construct($roomid,$name,$altname,$allocation,$keyno,$type,$units,$order,$xpos,$ypos,$width,$depth,$orientation,$notes)
		{
			//final data processing
			if($roomid=="Area 1")$roomid = 24;//DEN02 Area 1
			if($roomid=="Area 2")$roomid = 25;//DEN02 Area 1
			$allocation=$allocation[0];//get first char
			if($allocation=="O")$allocation = "E";//Open should be Empty
			$type=$type[0];
			if($type=="2")$type = "R";//2post should be Rack
			if($order=="TRUE")$order="N";
			else $order = "R";
			
			$this->roomid		= $roomid;
			$this->name			= $name;
			$this->altname		= $altname;
			$this->type			= $type;
			$this->units		= $units;
			$this->orientation	= $orientation;
			$this->keyno		= $keyno;
			$this->allocation	= $allocation;
			$this->order		= $order;
			$this->xpos			= $xpos;
			$this->ypos			= $ypos;
			$this->width		= $width;
			$this->depth		= $depth;
			$this->notes		= $notes;
		}
	}
	
	function ImportLocations($fullProcessing=false)
	{
		global $errorMessage;
		global $resultMessage;
		
		//locations- roomid,name,altname,allocation,keyno,type,units,order,xpos,ypos,width,depth,orientation,notes
		$locationData= GetInput("importdata",true,false);
		
		$locationData= str_replace("\n",",",$locationData);
		$locationData= explode(",", $locationData);
		
		$importObjects = array();
		$i = 0;
		$fields = 14;
		
		$roomid="";
		$name="";
		$altname="";
		$allocation="";
		$keyno="";
		$type="";
		$units="";
		$order="";
		$xpos="";
		$ypos="";
		$width="";
		$depth="";
		$orientation="";
		$notes="";
		
		foreach ($locationData as $rec)
		{
			switch($i % $fields)
			{
				case 0:$roomid		=trim($rec);break;
				case 1:$name		=trim($rec);break;
				case 2:$altname		=trim($rec);break;
				case 3:$allocation	=trim($rec);break;
				case 4:$keyno		=trim($rec);break;
				case 5:$type		=trim($rec);break;
				case 6:$units		=trim($rec);break;
				case 7:$order		=trim($rec);break;
				case 8:$xpos		=trim($rec);break;
				case 9:$ypos		=trim($rec);break;
				case 10:$width		=trim($rec);break;
				case 11:$depth		=trim($rec);break;
				case 12:$orientation=trim($rec);break;
				case 13:$notes		=trim($rec);
				
				$importObjects[]= new LocationRec($roomid,$name,$altname,$allocation,$keyno,$type,$units,$order,$xpos,$ypos,$width,$depth,$orientation,$notes);
				break;
			}
			$i++;
		}
		
		//add Location
		if($fullProcessing) $resultMessage[]="Adding ".count($importObjects)." locations...";
		else $resultMessage[]="Dry run adding ".count($importObjects)." locations";
		
		foreach ($importObjects as $rec)
		{//spoof input
			$_GET['roomid']= $rec->roomid;
			$_GET['name']= $rec->name;
			$_GET['altname']= $rec->altname;
			$_GET['type']= $rec->type;
			$_GET['units']= $rec->units;
			$_GET['orientation']= $rec->orientation;
			$_GET['keyno']= $rec->keyno;
			$_GET['allocation']= $rec->allocation;
			$_GET['order']= $rec->order;
			$_GET['xpos']= $rec->xpos;
			$_GET['ypos']= $rec->ypos;
			$_GET['width']= $rec->width;
			$_GET['depth']= $rec->depth;
			$_GET['notes']= $rec->notes;
			
			if($fullProcessing) ProcessLocationAction("Location_Add");
		}
	}
	
	class PowerPanelRec
	{
		public $site;
		public $room;
		public $name;
		public $ups;
		public $circuits;
		public $amps;
		public $xpos;
		public $ypos;
		public $width;
		public $depth;
		public $orientation;
		public $notes;
		
		function __construct($site,$room,$name,$ups,$circuits,$amps,$xpos,$ypos,$width,$depth,$orientation,$notes)
		{
			$this->site			= $site;
			$this->room			= $room;
			$this->name			= $name;
			$this->ups			= $ups;
			$this->circuits		= $circuits;
			$this->amps			= $amps;
			$this->xpos			= $xpos;
			$this->ypos			= $ypos;
			$this->width		= $width;
			$this->depth		= $depth;
			$this->orientation	= $orientation;
			$this->notes		= $notes;
		}
	}
	
	function ImportPowerPanels($fullProcessing=false)
	{
		global $errorMessage;
		global $resultMessage;
		
		//Power Panels- site,room,name,ups,circuits,amps,xpos,ypos,width,depth,orientation,notes
		$panelData= GetInput("importdata",true,false);
		
		$panelData= str_replace("\n",",",$panelData);
		$panelData= explode(",", $panelData);
		
		$importObjects = array();
		$i = 0;
		$fields = 12;
		
		$site = "";
		$room = "";
		$name = "";
		$ups = "";
		$circuits = "";
		$amps = "";
		$xpos = "";
		$ypos = "";
		$width = "";
		$depth = "";
		$orientation = "";
		$notes = "";
		
		foreach ($panelData as $rec)
		{
			switch($i % $fields)
			{
				case 0:$site		=trim($rec);break;
				case 1:$room		=trim($rec);break;
				case 2:$name		=trim($rec);break;
				case 3:$ups			=trim($rec);break;
				case 4:$circuits	=trim($rec);break;
				case 5:$amps		=trim($rec);break;
				case 6:$xpos		=trim($rec);break;
				case 7:$ypos		=trim($rec);break;
				case 8:$width		=trim($rec);break;
				case 9:$depth		=trim($rec);break;
				case 10:$orientation=trim($rec);break;
				case 11:$notes		=trim($rec);
				
				$importObjects[]= new PowerPanelRec($site,$room,$name,$ups,$circuits,$amps,$xpos,$ypos,$width,$depth,$orientation,$notes);
				break;
			}
			$i++;
		}
		
		//add Panel
		if($fullProcessing) $resultMessage[]="Adding ".count($importObjects)." power panels...";
		else $resultMessage[]="Dry run adding ".count($importObjects)." power panels";
		
		foreach ($importObjects as $rec)
		{
			//get upsid
			$upsID = ValidImportUPS($rec->site,$rec->ups,$rec->name);
			
			//get roomid
			$roomID = ValidImportRoom($rec->site,$rec->room,$rec->name);
			
			if($upsID !=-1 && $roomID !=-1)
			{//Do import - spoof input
				$_GET['upsid']= $upsID;
				$_GET['roomid']= $roomID;
				$_GET['name']= $rec->name;
				$_GET['amps']= $rec->amps;
				$_GET['circuits']= $rec->circuits;
				$_GET['xpos']= $rec->xpos;
				$_GET['ypos']= $rec->ypos;
				$_GET['width']= $rec->width;
				$_GET['depth']= $rec->depth;
				$_GET['orientation']= $rec->orientation;
				$_GET['notes']= $rec->notes;
				
				if($fullProcessing)ProcessPowerPanelAction("PowerPanel_Add");
				else $resultMessage[]= "Would have processed Panel(".$rec->name.")";
			}
			else $errorMessage[]= "Failed to import panel(".$rec->name.") - upsid($upsID) roomid($roomID)"; 
		}
	}
	
	class PowerCircuitRec
	{
		public $site;
		public $room;
		public $location;
		public $panel;
		public $circuit;
		public $volts;
		public $amps;
		public $status;
		public $load;
		public $phase;
		
		function __construct($site,$room,$location,$panel,$circuit,$volts,$amps,$status,$load,$phase)
		{
			$this->site			= $site;
			$this->room			= $room;
			$this->location 	= $location;
			$this->panel		= $panel;
			$this->circuit		= $circuit;
			$this->volts		= $volts;
			$this->amps			= $amps;
			$this->status 		= $status;
			$this->load			= $load;
			$this->phase		= $phase;
		}
	}
	
	function ImportPowerCircuits($fullProcessing=false)
	{
		global $mysqli;
		global $errorMessage;
		global $resultMessage;
		global $debugMessage;
		global $userID;
		$importUserID = $userID;
		
		//Power Circuits- site, room, location, panel, circuit, volts, amps, status, load, phase
		$data= GetInput("importdata",true,false);
		
		$data= str_replace("\n",",",$data);
		$data= explode(",", $data);
		
		$importObjects = array();
		$i = 0;
		$fields = 10;
		
		$site = "";
		$room = "";
		$location = "";
		$panel = "";
		$circuit = "";
		$volts = "";
		$amps = "";
		$status = "";
		$load = "";
		$phase = "";
		
		foreach ($data as $rec)
		{
			switch($i % $fields)
			{
				case 0:$site		=trim($rec);break;
				case 1:$room		=trim($rec);break;
				case 2:$location 	=trim($rec);break;
				case 3:$panel		=trim($rec);break;
				case 4:$circuit		=trim($rec);break;
				case 5:$volts		=trim($rec);break;
				case 6:$amps		=trim($rec);break;
				case 7:$status		=trim($rec);break;
				case 8:$load		=trim($rec);break;
				case 9:$phase		=trim($rec);
				
				$importObjects[]= new PowerCircuitRec($site,$room,$location,$panel,$circuit,$volts,$amps,$status,$load,$phase);
				break;
			}
			$i++;
		}
		
		//add Location
		if($fullProcessing) $resultMessage[]="Adding ".count($importObjects)." power circuits...";
		else $resultMessage[]="Dry run adding ".count($importObjects)." power circuits";
		
		$totalAffectedCount = 0;
		if($fullProcessing) foreach ($importObjects as $rec)
		{
			//validate fields
			$powerPanelID = GetPowerPanelID($rec->site,$rec->panel);
			$locationID = GetLocationID($rec->site,$rec->room,$rec->location);
			$userID = 0;
			
			$valid = true;
			if($locationID==-1)
			{
				$valid = strlen($rec->location)==0;
				if(!$valid)$errorMessage[]="ImportPowerCircuits() Failed to locate location (".$rec->site.",".$rec->room.",".$rec->location.")";
			}
			
			if($valid)
			{
				$valid = $powerPanelID!=-1;
				if(!$valid)
					$errorMessage[]="ImportPowerCircuits() Panel (".$rec->site.",".$rec->panel.") not found - aborting circuit validate - ";
			}
			
			$powerCircuitID = -1;
			$circuit= $rec->circuit;
			$volts = $rec->volts;
			$amps = $rec->amps;
			$status = $rec->status;
			$load = $rec->load;
			
			if($status!="On") $status = "D";
			else $status = "A";
			
			if($rec->phase==3)
			{
				$volts = 308;
			}
			
			if($valid)
			{//Do import - spoof input
				$_GET['powerpanelid']= $powerPanelID;
				$_GET['locationid']= $locationID;
				$_GET['circuit']= $circuit;
				$_GET['volts']= $volts;
				$_GET['amps']= $amps;
				$_GET['status']= $status;
				$_GET['load']= $load;
				
				if($fullProcessing)ProcessPowerCircuitAction("PowerCircuit_Add");
				else $resultMessage[]= "Would have processed Circuit(".$rec->site." ".$rec->panel." C#".$rec->circuit.")";
				$totalAffectedCount++;
			}
			else $errorMessage[]= "Failed to import Circuit(".$rec->site." ".$rec->panel." C#".$rec->circuit.") -powerpanelid($powerPanelID) locationid($locationID)";
		}
		$resultMessage[] = "Successfully added $totalAffectedCount records.";
		$userID = $importUserID;
	}
	
	class DeviceImportRec
	{
		public $siteName;
		public $roomName;
		public $locName;
		public $hno;
		public $name;
		public $altname;
		public $member;
		public $model;
		public $unit;
		public $type;
		public $size;
		public $status;
		public $asset;
		public $serial;
		public $note;
		
		function __construct($siteName,$roomName,$locName,$hno,$name,$altname,$member,$model,$unit,$type,$size,$status,$asset,$serial,$note)
		{
			$this->siteName	= $siteName;
			$this->roomName	= $roomName;
			$this->locName	= $locName;
			$this->hno		= $hno;
			$this->name		= $name;
			$this->altname	= $altname;
			$this->member	= $member;
			$this->model	= $model;
			$this->unit		= $unit;
			$this->type		= $type;
			$this->size		= $size;
			$this->status	= $status;
			$this->asset	= $asset;
			$this->serial	= $serial;
			$this->note		= $note;
		}
	}
	
	function ImportDevices($fullProcessing=false)
	{
		global $errorMessage;
		global $resultMessage;
		
		//locations- roomid,name,altname,allocation,keyno,type,units,order,xpos,ypos,width,depth,orientation,notes
		$deviceData= GetInput("importdata",true,false);
		
		$deviceData= str_replace("\n",",",$deviceData);
		$deviceData= explode(",", $deviceData);
		
		$importObjects = array();
		$i = 0;
		$fields = 15;
		
		$siteName="";
		$roomName="";
		$locName="";
		$hno="";
		$name="";
		$altname="";
		$member="";
		$model="";
		$unit="";
		$type="";
		$size="";
		$status="";
		$asset="";
		$serial="";
		$note="";
		
		foreach ($deviceData as $rec)
		{
			if(strlen($rec)>2 && $rec[0]=='"' && substr($rec,-1)=='"')
			{
				$rec = substr($rec, 1, -1);//trim start and end quotes
				$rec= str_replace('""', '', $rec);//replace double double quotes with single double quotes
			}
			switch($i % $fields)
			{
				case 0:$siteName	= ($rec);break;
				case 1:$roomName	= ($rec);break;
				case 2:$locName		= ($rec);break;
				case 3:$hno			= ($rec);break;
				case 4:$name		= ($rec);break;
				case 5:$altname		= ($rec);break;
				case 6:$member		= ($rec);break;
				case 7:$model		= ($rec);break;
				case 8:$unit		= ($rec);break;
				case 9:$type		= ($rec);break;
				case 10:$size		= ($rec);break;
				case 11:$status		= ($rec);break;
				case 12:$asset		= ($rec);break;
				case 13:$serial		= ($rec);break;
				case 14:$note		= ($rec);
				
				$importObjects[]= new DeviceImportRec($siteName,$roomName,$locName,$hno,$name,$altname,$member,$model,$unit,$type,$size,$status,$asset,$serial,$note);
				break;
			}
			$i++;
		}
		
		//add Location
		if($fullProcessing) $resultMessage[]="Adding ".count($importObjects)." devices...";
		else $resultMessage[]="Dry run adding ".count($importObjects)." devices";
		
		$validCount = 0;
		foreach ($importObjects as $rec)
		{
			if(ImportDevice_Test($rec))$validCount++;
		}
		$resultMessage[]="Sucsess with  $validCount devices...";
	}
	
	function ImportDevice_Test($rec)
	{//reports all exceptions
		global $errorMessage;
		global $resultMessage;
		
		global $deviceModels;
		global $validModels;
		$validModels = array();
		foreach($deviceModels as $device) $validModels[] = $device->name;
		
		//test location - get locationid
		$locationid = ValidImportLocation($rec->siteName, $rec->roomName, $rec->locName, $rec->name);
		
		//attempt hno extraction
		//attempt altname extraction
		
		//report devices with no hno
		//test unit
		
		/*
		//testModel
		if(!ValidImportDeviceModel($rec->model))
		{
			$errorMessage[]="Unknown Model (".$rec->model.") Device:".$rec->name;
		}
		else
		{
			//$resultMessage[]="Valid Model (".$rec->model.") Device:".$rec->name;
		}*/
		
		
		$resultMessage[]="done with Device".$rec->name." at location #$locationid(".$rec->siteName.", ".$rec->roomName.", ".$rec->locName.")";
	}
	
	function ValidImportUPS($site, $ups, $searchIdentifier="null")
	{
		//looks up ups - returns upsid or -1 if not found
		global $mysqli;
		global $errorMessage;
		global $resultMessage;
		global $debugMessage;
		
		$testSite = true;
		$result = -1;
		
		$validUPS = false;
		$validSite = !$testSite;
		if($testSite)
		{
			$query = "SELECT s.siteid, s.name
				FROM dcim_site AS s
				WHERE s.name=?";
			
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $site) || !$stmt->execute())
				$errorMessage[] = "ValidImportUPS() - Prepare failed: (u1) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{
					$stmt->store_result();
					if($stmt->num_rows==1) $validSite = true;
					else $errorMessage[] = "ValidImportUPS() - Invalid Site for ValidImportUPS($site $ups) iden:($searchIdentifier)";
				}
		}
		
		if($validSite)
		{
			$query = "SELECT pu.powerupsid, pu.name
				FROM dcim_powerups AS pu
					INNER JOIN dcim_site AS s ON pu.siteid=s.siteid
				WHERE s.name=? AND pu.name=?";
			
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('ss', $site, $ups) || !$stmt->execute())
				$errorMessage[] = "ValidImportUPS() - Prepare failed: (u2) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{
					$stmt->store_result();
					if($stmt->num_rows==1)
					{
						$validUPS= true;
						$stmt->bind_result($upsID, $ups);
						$stmt->fetch();
						$result = $upsID;
					}
					else $errorMessage[] = "ValidImportUPS() - Invalid UPS for ValidImportUPS($site $ups) iden:($searchIdentifier)";
				}
		}
		return $result;
	}
	
	function ValidImportRoom($site, $room, $searchIdentifier="null")
	{
		//looks up room - returns roomid or -1 if not found
		global $mysqli;
		global $errorMessage;
		global $resultMessage;
		global $debugMessage;
		
		$testSite = true;
		$result = -1;
		
		$validRoom = false;
		$validSite = !$testSite;
		if($testSite)
		{
			$query = "SELECT s.siteid, s.name
				FROM dcim_site AS s
				WHERE s.name=?";
			
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $site) || !$stmt->execute())
				$errorMessage[] = "ValidImportRoom() - Prepare failed: (r1) (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				$stmt->store_result();
				if($stmt->num_rows==1) $validSite = true;
				else $errorMessage[] = "ValidImportRoom() - Invalid Site for ValidImportRoom($site $room) iden:($searchIdentifier)";
			}
		}
		
		if($validSite)
		{
			$query = "SELECT r.roomid, r.name
				FROM dcim_room AS r
					INNER JOIN dcim_site AS s ON r.siteid=s.siteid
				WHERE s.name=? AND r.name=?";
			
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('ss', $site, $room) || !$stmt->execute())
				$errorMessage[] = "ValidImportRoom() - Prepare failed: (r2) (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				$stmt->store_result();
				if($stmt->num_rows==1)
				{
					$validRoom = true;
					$stmt->bind_result($roomID, $room);
					$stmt->fetch();
					$result = $roomID;
				}
				else $errorMessage[] = "ValidImportRoom() - Invalid Room for ValidImportRoom($site $room) iden:($searchIdentifier)";
			}
		}
		return $result;
	}
	
	function ValidImportPowerPanel($site, $panel, $searchIdentifier="null")
	{
		//looks up power panel - returns powerpanelid or -1 if not found
		global $mysqli;
		global $errorMessage;
		global $resultMessage;
		global $debugMessage;
		
		$testSite = true;
		$result = -1;
		
		$validPanel = false;
		$validSite = !$testSite;
		if($testSite)
		{
			$query = "SELECT s.siteid, s.name
				FROM dcim_site AS s
				WHERE s.name=?";
			
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $site) || !$stmt->execute())
				$errorMessage[] = "ValidImportPowerPanel() - Prepare failed: (pp1) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{
					$stmt->store_result();
					if($stmt->num_rows==1) $validSite = true;
					else $errorMessage[] = "ValidImportPowerPanel() - Invalid Site for ValidImportPowerPanel($site $panel) iden:($searchIdentifier)";
				}
		}
		
		if($validSite)
		{
			$query = "SELECT pp.powerpanelid, pp.name
				FROM dcim_powerpanel AS pp
					INNER JOIN dcim_room AS r ON pp.roomid=r.roomid
					INNER JOIN dcim_site AS s ON r.siteid=s.siteid
				WHERE s.name=? AND pp.name=?";
			
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('ss', $site, $panel) || !$stmt->execute())
				$errorMessage[] = "ValidImportPowerPanel() - Prepare failed: (pp2) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{
					$stmt->store_result();
					if($stmt->num_rows==1)
					{
						$validPanel= true;
						$stmt->bind_result($roomID, $panel);
						$stmt->fetch();
						$result = $roomID;
					}
					else $errorMessage[] = "ValidImportPowerPanel() - Invalid Panel for ValidImportPowerPanel($site $panel) iden:($searchIdentifier)";
				}
		}
		return $result;
	}
	
	function ValidImportLocation($site, $room, $loc, $deviceName="null")
	{
		//looks up location - returns locationid or -1 if not found
		global $mysqli;
		global $errorMessage;
		global $resultMessage;
		global $debugMessage;
		
		$testSite = true;
		$testRoom = true;
		$result = -1;
		
		$validRoom = !$testRoom;
		$validSite = !$testSite;
		if($testSite)
		{
			$query = "SELECT s.siteid, s.name 
				FROM dcim_site AS s 
				WHERE s.name=?";
			
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $site) || !$stmt->execute())
				$errorMessage[] = "ValidImportLocation() - Prepare failed: (l1) (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				$stmt->store_result();
				if($stmt->num_rows==1) $validSite = true;
				else $errorMessage[] = "ValidImportLocation() - Invalid Site for ValidImportLocation($site $room $loc) device:($deviceName)";
			}
		}
		
		if($validSite && $testRoom)
		{
			$query = "SELECT r.roomid, r.name
				FROM dcim_room AS r
					INNER JOIN dcim_site AS s ON r.siteid=s.siteid
				WHERE s.name=? AND r.name=?";
			
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('ss', $site, $room) || !$stmt->execute())
				$errorMessage[] = "ValidImportLocation() - Prepare failed: (l2) (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				$stmt->store_result();
				if($stmt->num_rows==1) $validRoom = true;
				else $errorMessage[] = "ValidImportLocation() - Invalid Room for ValidImportLocation($site $room $loc) device:($deviceName)";
			}
		}
		
		if($validSite && $validRoom)
		{
			$query = "SELECT l.locationid, l.name
				FROM dcim_location AS l
					INNER JOIN dcim_room AS r ON l.roomid=r.roomid
					INNER JOIN dcim_site AS s ON r.siteid=s.siteid
				WHERE s.name=? AND r.name=? AND l.name=?";
			
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('sss', $site, $room, $loc) || !$stmt->execute())
				$errorMessage[] = "ValidImportLocation() - Prepare failed: (l3) (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				$stmt->store_result();
				if($stmt->num_rows==1)
				{
					$stmt->bind_result($locationID, $location);
					$stmt->fetch();
					$result = $locationID;
				}
				else $errorMessage[] = "ValidImportLocation() - Invalid Location for ValidImportLocation($site $room $loc) device:($deviceName)";
			}
		}
		return $result;
	}
	
	function ValidImportDeviceModel(&$model)
	{
		global $validModels;
		
		$model = str_replace('Poweredge', 'PowerEdge', $model);
		$model = str_replace('poweredge', 'PowerEdge', $model);
		$model = str_replace('POWEREDGE', 'PowerEdge', $model);
		
			 if($model=="R410")$model= "Dell Poweredge $model";
		else if($model=="R420")$model= "Dell Poweredge $model";
		else if($model=="R710")$model= "Dell Poweredge $model";
		else if($model=="R820")$model= "Dell Poweredge $model";
		else if($model=="PowerEdge R410")$model= "Dell $model";
		else if($model=="PowerEdge R420")$model= "Dell $model";
		else if($model=="PowerEdge R710")$model= "Dell $model";
		else if($model=="PowerEdge R810")$model= "Dell $model";
		
		return (in_array($model,$validModels));
	}
?>