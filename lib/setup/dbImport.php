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
		$hno="";
		$cno="";
		$name="";
		$status="";
		$notes="";
		$fieldCount = 5;
		$descriptor = "customer";//for error reporting
		
		$importData= GetInput("importdata",true,false);
		$lines = explode("\n", $importData);
		$importObjects = array();
		foreach ($lines as $line)
		{
			$fields= explode(",", $line);
			if(count($fields)!=$fieldCount)
			{
				$errorMessage[]="Unknown field count while adding $descriptor - $line";
				continue;
			}
			$i = 0;
			foreach ($fields as $field)
			{
				switch($i % $fieldCount)
				{
					case 0:$hno		=trim($field);break;
					case 1:$cno		=trim($field);break;
					case 2:$name	=trim($field);break;
					case 3:$status	=trim($field);break;
					case 4:$notes	=trim($field);break;
				}
				$i++;
			}
			$importObjects[]= new CustomerRec($hno,$cno,$name,$status,$notes);
		}
		if($fullProcessing) $resultMessage[]="Atempting import of ".count($importObjects)." ".$descriptor."s...";
		else $resultMessage[]="Dry run adding ".count($importObjects)." ".$descriptor."s";
		
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
		$fieldCount = 14;
		$descriptor = "location";//for error reporting
		
		$importData= GetInput("importdata",true,false);
		$lines = explode("\n", $importData);
		$importObjects = array();
		foreach ($lines as $line)
		{
			$fields= explode(",", $line);
			if(count($fields)!=$fieldCount)
			{
				$errorMessage[]="Unknown field count while adding $descriptor - $line";
				continue;
			}
			$i = 0;
			foreach ($fields as $field)
			{
				switch($i % $fieldCount)
				{
					case 0:$roomid		=trim($field);break;
					case 1:$name		=trim($field);break;
					case 2:$altname		=trim($field);break;
					case 3:$allocation	=trim($field);break;
					case 4:$keyno		=trim($field);break;
					case 5:$type		=trim($field);break;
					case 6:$units		=trim($field);break;
					case 7:$order		=trim($field);break;
					case 8:$xpos		=trim($field);break;
					case 9:$ypos		=trim($field);break;
					case 10:$width		=trim($field);break;
					case 11:$depth		=trim($field);break;
					case 12:$orientation=trim($field);break;
					case 13:$notes		=trim($field);break;
				}
				$i++;
			}
			$importObjects[]= new LocationRec($roomid,$name,$altname,$allocation,$keyno,$type,$units,$order,$xpos,$ypos,$width,$depth,$orientation,$notes);
		}
		if($fullProcessing) $resultMessage[]="Atempting import of ".count($importObjects)." ".$descriptor."s...";
		else $resultMessage[]="Dry run adding ".count($importObjects)." ".$descriptor."s";
		
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
		$fieldCount = 12;
		$descriptor = "power panel";//for error reporting
		
		$importData= GetInput("importdata",true,false);
		$lines = explode("\n", $importData);
		$importObjects = array();
		foreach ($lines as $line)
		{
			$fields= explode(",", $line);
			if(count($fields)!=$fieldCount)
			{
				$errorMessage[]="Unknown field count while adding $descriptor - $line";
				continue;
			}
			$i = 0;
			foreach ($fields as $field)
			{
				switch($i % $fieldCount)
				{
					case 0:$site		=trim($field);break;
					case 1:$room		=trim($field);break;
					case 2:$name		=trim($field);break;
					case 3:$ups			=trim($field);break;
					case 4:$circuits	=trim($field);break;
					case 5:$amps		=trim($field);break;
					case 6:$xpos		=trim($field);break;
					case 7:$ypos		=trim($field);break;
					case 8:$width		=trim($field);break;
					case 9:$depth		=trim($field);break;
					case 10:$orientation=trim($field);break;
					case 11:$notes		=trim($field);break;
				}
				$i++;
			}
			$importObjects[]= new PowerPanelRec($site,$room,$name,$ups,$circuits,$amps,$xpos,$ypos,$width,$depth,$orientation,$notes);
		}
		if($fullProcessing) $resultMessage[]="Atempting import of ".count($importObjects)." ".$descriptor."s...";
		else $resultMessage[]="Dry run adding ".count($importObjects)." ".$descriptor."s";
		
		foreach ($importObjects as $rec)
		{
			//get upsid
			if(strlen($rec->ups)>0)$upsID = ValidImportUPS($rec->site,$rec->ups,$rec->name);
			else $upsID = -1;
			$validUPS = $upsID!=-1 || strlen($rec->ups)==0;
			
			//get roomid
			$roomID = ValidImportRoom($rec->site,$rec->room,$rec->name);
			
			if($validUPS && $roomID !=-1)
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
		$fieldCount = 10;
		$descriptor = "power circuit";//for error reporting
		
		$importData= GetInput("importdata",true,false);
		$lines = explode("\n", $importData);
		$importObjects = array();
		foreach ($lines as $line)
		{
			$fields= explode(",", $line);
			if(count($fields)!=$fieldCount)
			{
				$errorMessage[]="Unknown field count while adding $descriptor - $line";
				continue;
			}
			$i = 0;
			foreach ($fields as $field)
			{
				switch($i % $fieldCount)
				{
					case 0:$site		=trim($field);break;
					case 1:$room		=trim($field);break;
					case 2:$location 	=trim($field);break;
					case 3:$panel		=trim($field);break;
					case 4:$circuit		=trim($field);break;
					case 5:$volts		=trim($field);break;
					case 6:$amps		=trim($field);break;
					case 7:$status		=trim($field);break;
					case 8:$load		=trim($field);break;
					case 9:$phase		=trim($field);break;
				}
				$i++;
			}
			$importObjects[]= new PowerCircuitRec($site,$room,$location,$panel,$circuit,$volts,$amps,$status,$load,$phase);
		}
		if($fullProcessing) $resultMessage[]="Atempting import of ".count($importObjects)." ".$descriptor."s...";
		else $resultMessage[]="Dry run adding ".count($importObjects)." ".$descriptor."s";
		
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
		public $locationid;//infered from import
		
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
		
		//devices- roomid,name,altname,allocation,keyno,type,units,order,xpos,ypos,width,depth,orientation,notes
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
		$fieldCount = 15;
		$descriptor = "device";//for error reporting
		
		$importData= GetInput("importdata",true,false);
		$lines = explode("\n", $importData);
		$importObjects = array();
		foreach ($lines as $line)
		{
			$fields= explode(",", $line);
			if(count($fields)!=$fieldCount)
			{
				$errorMessage[]="Unknown field count while adding $descriptor - $line";
				continue;
			}
			$i = 0;
			foreach ($fields as $field)
			{
				switch($i % $fieldCount)
				{
					case 0:$siteName	= ($field);break;
					case 1:$roomName	= ($field);break;
					case 2:$locName		= ($field);break;
					case 3:$hno			= ($field);break;
					case 4:$name		= ($field);break;
					case 5:$altname		= ($field);break;
					case 6:$member		= ($field);break;
					case 7:$model		= ($field);break;
					case 8:$unit		= ($field);break;
					case 9:$type		= ($field);break;
					case 10:$size		= ($field);break;
					case 11:$status		= ($field);break;
					case 12:$asset		= ($field);break;
					case 13:$serial		= ($field);break;
					case 14:$note		= ($field);break;
				}
				$i++;
			}
			$importObjects[]= new DeviceImportRec($siteName,$roomName,$locName,$hno,$name,$altname,$member,$model,$unit,$type,$size,$status,$asset,$serial,$note);
		}
		if($fullProcessing) $resultMessage[]="Atempting import of ".count($importObjects)." ".$descriptor."s...";
		else $resultMessage[]="Dry run adding ".count($importObjects)." ".$descriptor."s";
		
		$validCount = 0;
		foreach ($importObjects as $rec)
		{
			if(ImportDevice_Validation($rec,$fullProcessing))
			{//spoof input - do import
				$_GET['hno']= $rec->hno;
				$_GET['devicename']= $rec->name;
				$_GET['devicealtname']= $rec->altname;
				$_GET['type']= $rec->type;
				$_GET['size']= $rec->size;
				$_GET['locationid']= $rec->locationid;
				$_GET['unit']= $rec->unit;
				$_GET['status']= $rec->status;
				$_GET['notes']= $rec->note;
				$_GET['model']= $rec->model;
				$_GET['member']= $rec->member;
				$_GET['asset']= $rec->asset;
				$_GET['serial']= $rec->serial;
				
				$validCount++;
				if($fullProcessing) ProcessDeviceAction("Device_Add");
			}
		}
		$resultMessage[]="Processed $validCount devices past initial validation";
	}
	
	function ImportDevice_Validation($rec,$fullProcessing)
	{//test device reports all exceptions - and/or update fields for import process - return true if data seems valid
		global $errorMessage;
		global $resultMessage;
		
		global $deviceModels;
		global $validModels;
		$validModels = array();
		foreach($deviceModels as $device) $validModels[] = $device->name;
		
		//test location - get locationid
		$locationid = ValidImportLocation($rec->siteName, $rec->roomName, $rec->locName, $rec->name);
		
		if($rec->type=="Rack Enclosure" || $rec->type=="Colo")
		{
			$rec->model = "Colo Cabinet";
			$rec->type = "F";//full cab - assume - should be importing colo anyways
			$rec->size = "Full";
		}
		else
		{
			$rec->type = "S";//physical
		}
		
		//update status
		if($rec->status = "Active")$rec->status="A";
		else $rec->status="I";
		
		//update member
		if(strlen($rec->member)==0)
		{
			$rec->member=0;
		}
		
		//testModel - update size
		if(!ValidImportDeviceModel($rec->model))
		{//unknown model
			$rec->size = "1U";
			//$errorMessage[]="Warning - Unknown Model (".$rec->model.") Device:".$rec->name;
		}
		else
		{
			$deviceInfo = GetDeviceFromModelName($rec->model);
			$rec->size = $deviceInfo->units."U";//pull from model array
		}
		
		$valid = true;
		if($locationid==-1)$valid = false;
		
		if($valid)//additional validation will be done when actualy adding the device
		{
			//if($fullProcessing)$resultMessage[]="done with Device".$rec->name." at location #$locationid(".$rec->siteName.", ".$rec->roomName.", ".$rec->locName.")";
			$rec->locationid = $locationid;
		}
		
		return $valid;
	}
	
	function ValidImportUPS($site, $ups, $searchIdentifier="null")
	{
		//looks up ups - returns upsid or -1 if not found
		global $mysqli;
		global $errorMessage;
		global $resultMessage;
		global $debugMessage;
		
		if(strlen($ups)==0)
		{//null ups is allowed
			return -1;
		}
		
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
				else $errorMessage[] = "ValidImportLocation($site $room $loc) - Invalid Site for device:($deviceName)";
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
				else $errorMessage[] = "ValidImportLocation($site $room $loc) - Invalid Room for device:($deviceName)";
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
				else $errorMessage[] = "ValidImportLocation($site $room $loc) - Invalid Location for device:($deviceName)";
			}
		}
		return $result;
	}
	
	function ValidImportDeviceModel(&$model)
	{
		global $validModels;
		
		//ordered by occurancs in test data
		if(strcasecmp($model,"")==0)$model="";//really just here to keep the formating of the rest of the lines
		else if(strcasecmp($model,"Poweredge R710")==0)$model="Dell PowerEdge R710";
		else if(strcasecmp($model,"PowerEdge R410")==0)$model="Dell PowerEdge R410";
		else if(strcasecmp($model,"SRX300")==0)$model="Juniper SRX300";
		else if(strcasecmp($model,"EX4200-48T")==0)$model="Juniper EX4200-48T";
		else if(strcasecmp($model,"1950 Gen 3")==0)$model="Dell PowerEdge 1950 Gen 3";
		else if(strcasecmp($model,"SSG-5-SH")==0)$model="Juniper SSG-5-SH";
		else if(strcasecmp($model,"Juniper Networks Secure Services Gateway SSG 5 - security appliance")==0)$model="Juniper SSG-5-SH";
		else if(strcasecmp($model,"SC811T-260")==0)$model="SuperMicro SC811T-260";
		else if(strcasecmp($model,"R820")==0)$model="Dell PowerEdge R820";
		else if(strcasecmp($model,"SRX100H2")==0)$model="Juniper SRX100H2";
		else if(strcasecmp($model,"6026T-NTR+")==0)$model="SuperMicro SuperServe 6026T-NTR+";
		else if(strcasecmp($model,"R720")==0)$model="Dell PowerEdge R720";
		else if(strcasecmp($model,"2950 Gen 3")==0)$model="Dell PowerEdge 2950 Gen 3";
		else if(strcasecmp($model,"1950 Gen 2")==0)$model="Dell PowerEdge 1950 Gen 2";
		else if(strcasecmp($model,"EX3200-24T")==0)$model="Juniper EX3200-24T";
		else if(strcasecmp($model,"SSG 140 System  512MB")==0)$model="SSG 140 System  512MB";
		else if(strcasecmp($model,"PowerEdge R610")==0)$model="Dell PowerEdge R610";
		else if(strcasecmp($model,"CSE-811TQ-520")==0)$model="SuperMicro SuperChasis CSE-811TQ-520";
		else if(strcasecmp($model,"EX3300-48T")==0)$model="Juniper EX3300-48T";
		else if(strcasecmp($model,"SRX220H2")==0)$model="Juniper SRX220H2";
		else if(strcasecmp($model,"SRX340")==0)$model="Juniper SRX340";
		else if(strcasecmp($model,"PowerEdge R730")==0)$model="Dell PowerEdge R730";
		else if(strcasecmp($model,"Power Edge R510")==0)$model="Dell PowerEdge R510";
		else if(strcasecmp($model,"2950 Gen 2")==0)$model="Dell PowerEdge 2950 Gen 2";
		else if(strcasecmp($model,"SSG-5-SB 128mb")==0)$model="Juniper SSG-5-SB 128mb";
		else if(strcasecmp($model,"SRX345")==0)$model="Juniper SRX345";
		else if(strcasecmp($model,"PowerEdge R630")==0)$model="Dell PowerEdge R630";
		else if(strcasecmp($model,"Ex3200-48T")==0)$model="Juniper Ex3200-48T";
		else if(strcasecmp($model,"DS-300B-8G")==0)$model="EMC Connectrix DS-300B-8G";
		else if(strcasecmp($model,"SSG-6027R")==0)$model="SuperMicro SuperServe SSG-6027R";
		else if(strcasecmp($model,"SRX220H")==0)$model="Juniper SRX220H";
		else if(strcasecmp($model,"WS-C2960-48TC-L")==0)$model="Cisco Catalyst WS-C2960-48TC-L";
		else if(strcasecmp($model,"ProCurve 2650")==0)$model="HP ProCurve 2650";
		else if(strcasecmp($model,"6015V-T")==0)$model="SuperMicro SuperServe 6015V-T";
		else if(strcasecmp($model,"R420")==0)$model="Dell PowerEdge R420";
		else if(strcasecmp($model,"SRX100H")==0)$model="Juniper SRX100H";
		else if(strcasecmp($model,"BIG-IP SWCH LTM 2000S 8GB")==0)$model="F5 BIG-IP SWCH LTM 2000S 8GB";
		else if(strcasecmp($model,"SRX240H2")==0)$model="Juniper SRX240H2";
		else if(strcasecmp($model,"SSG-350M-SH")==0)$model="Juniper SSG-350M-SH";
		else if(strcasecmp($model,"1950 Gen 1")==0)$model="Dell PowerEdge 1950 Gen 1";
		else if(strcasecmp($model,"Log Manager Device")==0)$model="Science Logic Log Manager Device";
		else if(strcasecmp($model,"PowerEdge R810")==0)$model="Dell PowerEdge R810";
		else if(strcasecmp($model,"6013P-8 Dual 32BIT Xeon 12GB DualU320")==0)$model="SuperMicro SuperServe 6013P-8";
		else if(strcasecmp($model,"5015M-MT+")==0)$model="SuperMicro SuperServe 5015M-MT+";
		else if(strcasecmp($model,"E350GX")==0)$model="Coyote Point E350GX";
		else if(strcasecmp($model,"CSE-825TQ-R720LPB")==0)$model="SuperMicro SuperChasis CSE-825TQ-R720LPB";
		else if(strcasecmp($model,"SYS-6025W-NTR+")==0)$model="SuperMicro SuperServe 6025W-NTR+";
		else if(strcasecmp($model,"6025W-NTR+")==0)$model="SuperMicro SuperServe 6025W-NTR+";
		else if(strcasecmp($model,"2950 Gen 1")==0)$model="Dell PowerEdge 2950 Gen 1";
		else if(strcasecmp($model,"EX3300-24T")==0)$model="Juniper EX3300-24T";
		else if(strcasecmp($model,"PowerEdge 1850 Dual Xeon 3.0 2GB DDR2 (2X1GB) 73GB Raid")==0)$model="Dell PowerEdge 1850";
		else if(strcasecmp($model,"Ex4200-24T")==0)$model="Juniper Ex4200-24T";
		else if(strcasecmp($model,"BIG-IP SWCH LTM 1600 4G")==0)$model="F5 BIG-LTM-1600-4G-R";
		else if(strcasecmp($model,"Supermicro 4U 45 Drive JBOD Chassis")==0)$model="SuperMicro SuperServer 4U";
		else if(strcasecmp($model,"5015M-MT Single Pentium4 D 8GB 4xSATA")==0)$model="SuperMicro SuperServe 5015M-MT";
		else if(strcasecmp($model,"QFX5100-96S-AFO")==0)$model="Juniper QFX5100-96S-AFO";
		else if(strcasecmp($model,"SSG 140 System  256MB  ")==0)$model="Juniper SSG-140";
		else if(strcasecmp($model,"Procurve 2848")==0)$model="HP Procurve 2848";
		else if(strcasecmp($model,"EX6210-S64-96T-A25")==0)$model="Juniper EX6210-S64-96T-A25";
		else if(strcasecmp($model,"PowerEdge R430")==0)$model="Dell PowerEdge R430";
		else if(strcasecmp($model,"SRX650-BASE-SRE6-645AP")==0)$model="Juniper SRX650-BASE-SRE6-645AP";
		else if(strcasecmp($model,"DS-5300B-8G")==0)$model="EMC Connectrix DS-5300B-8G";
		else if(strcasecmp($model,"CSE-836E-16-R1200B")==0)$model="SuperMicro SuperChasis CSE-836E-16-R1200B";
		else if(strcasecmp($model,"FAS2554A- 001-R6")==0)$model="Netapp FAS2554A- 001-R6";
		else if(strcasecmp($model,"SRX345-SYS-JB")==0)$model="Juniper SRX345-SYS-JB";
		else if(strcasecmp($model,"SRX300-SYS-JB")==0)$model="Juniper SRX300-SYS-JB";
		else if(strcasecmp($model,"DS-5100B-8G ")==0)$model="EMC Connectrix DS-5100B-8G";
		else if(strcasecmp($model,"SRX550-645AP")==0)$model="Juniper SRX550-645AP";
		else if(strcasecmp($model,"PowerEdge R830")==0)$model="Dell PowerEdge R830";
		else if(strcasecmp($model,"6014V-T2")==0)$model="SuperMicro SuperServe 6014V-T2";
		else if(strcasecmp($model,"PowerEdge 1850 Dual Xeon 3.2 4GB DDR2 (2X2Gb) 146GB Raid")==0)$model="Dell PowerEdge 1850";
		else if(strcasecmp($model,"J9020A#ABA (2510-48)")==0)$model="HP ProCurve 2810-48G";
		else if(strcasecmp($model,"SYS-6026TT-BTRF")==0)$model="SuperMicro SuperServer SYS-6026TT-BTRF";
		else if(strcasecmp($model,"Procurve 2824")==0)$model="HP ProCurve 2824";
		else if(strcasecmp($model,"6012")==0)$model="SuperMicro SuperServe 6012";
		else if(strcasecmp($model,"6013A-T Dual 32BIT Xeon 8GB 2xSATA ICH5R (RAID)")==0)$model="SuperMicro SuperServe 6013A-T";
		else if(strcasecmp($model,"F5-BIG-LTM-3900-8G")==0)$model="F5 BIG-LTM-3900-8G";
		else if(strcasecmp($model,"QFX5100-96S-AFI")==0)$model="Juniper QFX5100-96S-AFI";
		else if(strcasecmp($model,"VNX5700SPEF")==0)$model="EMC VNX VNX5700SPEF";
		else if(strcasecmp($model,"CSE-847E26-RJBOD1")==0)$model="SuperMicro SuperChasis CSE-847E26-RJBOD1";
		else if(strcasecmp($model,"PowerVault MD1000 3U")==0)$model="Dell PowerVault MD1000";
		else if(strcasecmp($model,"PowerVault MD3000 4U")==0)$model="Dell PowerVault MD3000";
		else if(strcasecmp($model,"PROCURVE SWITCH 2510G-24 ")==0)$model="HP ProCurve 2510G-24";
		else if(strcasecmp($model,"WS-C2960G-24TC-L CATALYST 2960 24PORT GIGABIT")==0)$model="Cisco Catalyst WS-C2960G-24TC-L";
		else if(strcasecmp($model,"PowerEdge 2850 Dual Xeon 3.2 8GB DDR2 (4X2gb) 2x73gb 4x300GB")==0)$model="Dell PowerEdge 2850";
		else if(strcasecmp($model,"E450GX")==0)$model="Coyote Point E450GX";
		else if(strcasecmp($model,"PowerEdge 2850 Dual Xeon 3.2 8GB DDR2 (4X2gb) 6 x 146GB")==0)$model="Dell PowerEdge 2850";
		else if(strcasecmp($model,"EX4200-24F")==0)$model="Juniper EX4200-24F";
		else if(strcasecmp($model,"WS-C6509")==0)$model="Cisco Catalyst WS-C6509";
		else if(strcasecmp($model,"MX480BASE-AC")==0)$model="Juniper MX480BASE-AC";
		else if(strcasecmp($model,"MAG4610")==0)$model="Juniper Junos Pulse MAG4610";
		else if(strcasecmp($model,"BIG-IP-LTM-6900")==0)$model="F5 BIG-IP-LTM-6900";
		else if(strcasecmp($model,"MX240BASE-AC-HIGH")==0)$model="Juniper MX240BASE-AC-HIGH";
		else if(strcasecmp($model,"QFX5100-24Q-AFI")==0)$model="Juniper QFX5100-24Q-AFI";
		else if(strcasecmp($model,"EX8208-BASE-AC")==0)$model="Juniper EX8208-BASE-AC";
		else if(strcasecmp($model,"EX3300-24P")==0)$model="Juniper EX3300-24P";
		else if(strcasecmp($model,"NS-ISG 1000 Advanced System  4-10/100/1000 ports")==0)$model="Juniper NS-ISG 1000";
		else if(strcasecmp($model,"VNX53D153015F")==0)$model="EMC VNX VNX53D153015F";
		else if(strcasecmp($model,"VNX51D15605F")==0)$model="EMC VNX VNX53D156015F";
		else if(strcasecmp($model,"CSE-837E26-RJBOD1")==0)$model="SuperMicro SuperChasis CSE-837E26-RJBOD1";
		else if(strcasecmp($model,"PowerEdge 2850 Dual Xeon 3.2 4GB DDR2 (2X2gb) 6 x 300GB")==0)$model="Dell PowerEdge 2850";
		else if(strcasecmp($model,"PowerEdge 2850 Dual Xeon 3.2 4GB DDR2 (2X2gb) 6 x 73GB")==0)$model="Dell PowerEdge 2850";
		else if(strcasecmp($model,"PowerEdge 2850 Dual Xeon 3.2 8GB DDR2 (4X2gb) 2x73gb 4x146GB")==0)$model="Dell PowerEdge 2850";
		else if(strcasecmp($model,"2610-24")==0)$model="HP ProCurve 2610-24";
		else if(strcasecmp($model,"6012L-6 Dual 32BIT Xeon 8GB Dual U160 ")==0)$model="SuperMicro SuperServe 6012L-6";
		else if(strcasecmp($model,"MAG2600")==0)$model="Juniper Junos Pulse MAG2600";
		else if(strcasecmp($model,"PowerEdge R730xd")==0)$model="Dell PowerEdge R730xd";
		else if(strcasecmp($model,"FAS8060A- 001-R6")==0)$model="Netapp FAS8060A- 001-R6";
		else if(strcasecmp($model,"FAS8040A- 001-R6")==0)$model="Netapp FAS8040A- 001-R6";
		else if(strcasecmp($model,"FAS8080AE-001-R6")==0)$model="Netapp FAS8080AE-001-R6";
		else if(strcasecmp($model,"FAS9000A-101-C")==0)$model="Netapp FAS9000A-101-C";
		else if(strcasecmp($model,"2810-48G")==0)$model="HP ProCurve 2810-48G";
		else if(strcasecmp($model,"PowerEdge 1850 Dual Xeon 3.0 2GB DDR2 (4X512MB) 146GB Raid")==0)$model="Dell PowerEdge 1850";
		else if(strcasecmp($model,"6014H-82 Dual 64BIT Xeon 16GB (DDRII) Dual U320")==0)$model="SuperMicro SuperServe 6014H-82";
		else if(strcasecmp($model,"PowerEdge 1850  Dual Xeon 3.0 4GB DDR2 (4X1GB) 146GB Raid")==0)$model="Dell PowerEdge 1850";
		else if(strcasecmp($model,"SRX340-SYS-JB")==0)$model="Juniper SRX340-SYS-JB";
		else if(strcasecmp($model,"CX4-480C")==0)$model="EMC Clariion CX4-480C";
		else if(strcasecmp($model,"PowerEdge 1850 Dual Xeon 3.0 4GB DDR2 (4X1GB) 73GB Raid")==0)$model="Dell PowerEdge 1850";
		else if(strcasecmp($model,"PowerEdge 1850 Dual Xeon 3.2  2GB DDR2 (2X1GB) 73GB Raid")==0)$model="Dell PowerEdge 1850";
		else if(strcasecmp($model,"PowerEdge 2850 Dual Xeon 3.0 4GB DDR2 (4X1gb) 3 x 73GB Raid")==0)$model="Dell PowerEdge 2850";
		else if(strcasecmp($model,"PowerEdge 1850 Dual Xeon 3.0 8GB DDR2 (4X2GB) 300GB Raid")==0)$model="Dell PowerEdge 1850";
		else if(strcasecmp($model,"OptiPlex 9020 Small Form Factor")==0)$model="Dell OptiPlex 9020";
		else if(strcasecmp($model,"optiplex 780")==0)$model="Dell Optiplex 780";
		else if(strcasecmp($model,"EX4300-24T")==0)$model="Juniper EX4300-24T";
		else if(strcasecmp($model,"2900-24G")==0)$model="HP ProCurve 2900-24G";
		else if(strcasecmp($model,"EX4200-24P")==0)$model="Juniper EX4200-24P";
		else if(strcasecmp($model,"EX4300-48T")==0)$model="Juniper EX4300-48T";
		
		
		return (in_array($model,$validModels));
	}
?>