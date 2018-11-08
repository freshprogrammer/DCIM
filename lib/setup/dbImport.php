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
	var needConfirm = true;
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
		$debugMessage[]= "-Start - has permision()";
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
		
		$debugMessageString  = implode("<BR>\n",$debugMessage);
		$errorMessageString  = implode("<BR>\n",$errorMessage);
		$resultMessageString = implode("<BR>\n",$resultMessage);
		if(strlen($debugMessageString) > 0) echo "<!-- DEBUG MESSAGE  -->\n<div id='debugMessage'  class='debugMessage'>$debugMessageString</div>\n";
		if(strlen($errorMessageString) > 0) echo "<!-- ERROR MESSAGE  -->\n<div id='errorMessage'  class='errorMessage'>$errorMessageString</div>\n";
		if(strlen($resultMessageString) > 0)echo "<!-- RESULT MESSAGE -->\n<div id='resultMessage' class='resultMessage'>$resultMessageString</div>\n";
		
		$selectOptions = "";
		$importForms = "";
		
		//location import form
		$formType = "Location";
		$expextedFields = "roomid,name,altname,allocation,keyno,type,units,order,xpos,ypos,width,depth,orientation,notes";
		$importForms .= CreateImportForm($formType,$expextedFields);
		$selectOptions .= "<option value='$formType'>$formType</option>\n";
		
		//Power Panel import form
		$formType = "Power Panel";
		$expextedFields = "powerupsid,roomid,name,amps,circuits,xpos,ypos,width,depth,orientation,notes";
		$importForms .= CreateImportForm($formType,$expextedFields);
		$selectOptions .= "<option value='$formType'>$formType</option>\n";
		
		//Power Circuits import form
		$formType = "Power Circuits";
		$expextedFields = "panel,circuit,volts,amps,load,status,user,date,site,room,location";
		$importForms .= CreateImportForm($formType,$expextedFields);
		$selectOptions .= "<option value='$formType'>$formType</option>\n";
		
		//Customer import form
		$formType = "Customer";
		$expextedFields = "hno,cno,name,note,status";
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
		
		if($importType=="Location")	ImportLocations($fullProcessing);
		else if($importType=="Power Panel")	ImportPowerPanels($fullProcessing);
		else if($importType=="Power Circuits")	ImportPowerCircuits($fullProcessing);
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
		public $upsid;
		public $roomid;
		public $name;
		public $amps;
		public $circuits;
		public $xpos;
		public $ypos;
		public $width;
		public $depth;
		public $orientation;
		public $notes;
		
		function __construct($upsid,$roomid,$name,$amps,$circuits,$xpos,$ypos,$width,$depth,$orientation,$notes)
		{
			$this->upsid		= $upsid;
			$this->roomid		= $roomid;
			$this->name			= $name;
			$this->amps			= $amps;
			$this->circuits		= $circuits;
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
		global $mysqli;
		global $resultMessage;
		
		//Power Panels- powerupsid,roomid,name,amps,circuits,xpos,ypos,width,depth,orientation,notes
		$panelData= GetInput("importdata",true,false);
		
		$panelData= str_replace("\n",",",$panelData);
		$panelData= explode(",", $panelData);
		
		$importObjects = array();
		$i = 0;
		$fields = 11;
		
		$upsid = "";
		$roomid = "";
		$name = "";
		$amps = "";
		$circuits = "";
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
				case 0:$upsid		=trim($rec);break;
				case 1:$roomid		=trim($rec);break;
				case 2:$name		=trim($rec);break;
				case 3:$amps		=trim($rec);break;
				case 4:$circuits	=trim($rec);break;
				case 5:$xpos		=trim($rec);break;
				case 6:$ypos		=trim($rec);break;
				case 7:$width		=trim($rec);break;
				case 8:$depth		=trim($rec);break;
				case 9:$orientation	=trim($rec);break;
				case 10:$notes		=trim($rec);
				
				$importObjects[]= new PowerPanelRec($upsid,$roomid,$name,$amps,$circuits,$xpos,$ypos,$width,$depth,$orientation,$notes);
				break;
			}
			$i++;
		}
		
		//add Location
		if($fullProcessing) $resultMessage[]="Adding ".count($importObjects)." power panels...";
		else $resultMessage[]="Dry run adding ".count($importObjects)." power panels";
		
		foreach ($importObjects as $rec)
		{//spoof input
			$_GET['upsid']= $rec->upsid;
			$_GET['roomid']= $rec->roomid;
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
		}
	}
	
	class PowerCircuitRec
	{
		public $panel;
		public $circuit;
		public $volts;
		public $amps;
		public $load;
		public $status;
		public $user;
		public $date;
		public $site;
		public $room;
		public $location;
		
		function __construct($panel,$circuit,$volts,$amps,$load,$status,$user,$date,$site,$room,$location)
		{
			$this->panel		= $panel;
			$this->circuit		= $circuit;
			$this->volts		= $volts;
			$this->amps			= $amps;
			$this->load			= $load;
			$this->status 		= $status;
			$this->user			= $user;
			$this->date			= $date;
			$this->site			= $site;
			$this->room			= $room;
			$this->location 	= $location;
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
		
		//Power Panels- powerupsid,roomid,name,amps,circuits,xpos,ypos,width,depth,orientation,notes
		$data= GetInput("importdata",true,false);
		
		$data= str_replace("\n",",",$data);
		$data= explode(",", $data);
		
		$importObjects = array();
		$i = 0;
		$fields = 11;
		
		$panel = "";
		$circuit = "";
		$volts = "";
		$amps = "";
		$load = "";
		$status = "";
		$user = "";
		$date = "";
		$site = "";
		$room = "";
		$location = "";
		
		foreach ($data as $rec)
		{
			switch($i % $fields)
			{
				case 0:$panel		=trim($rec);break;
				case 1:$circuit		=trim($rec);break;
				case 2:$volts		=trim($rec);break;
				case 3:$amps		=trim($rec);break;
				case 4:$load		=trim($rec);break;
				case 5:$status		=trim($rec);break;
				case 6:$user		=trim($rec);break;
				case 7:$date		=trim($rec);break;
				case 8:$site		=trim($rec);break;
				case 9:$room		=trim($rec);break;
				case 10:$location 	=trim($rec);
				
				$importObjects[]= new PowerCircuitRec($panel,$circuit,$volts,$amps,$load,$status,$user,$date,$site,$room,$location);
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
			$userID = GetUserID($rec->user);
			
			if($locationID==-1 && strlen($rec->room.$rec->location)>0)
				$errorMessage[]="ImportPowerCircuits() Failed to locate location (".$rec->site.",".$rec->room.",".$rec->location.") - proceeding to attempt circuit add";
			
			$add = true;
			$valid = true;
			$circuitExisted = false;
			
			if($valid)
			{
				$valid = $powerPanelID!=-1;
				if(!$valid)
					$errorMessage[]="ImportPowerCircuits() Panel (".$rec->site.",".$rec->panel.") not found - aborting circuit validate - ";
			}
			if($valid)
			{
				$valid = $userID!=-1;
				if(!$valid)
					$errorMessage[]="ImportPowerCircuits() User (".$rec->user.") not found - aborting circuit validate - ";
			}
			
			$powerCircuitID = -1;
			$circuit= $rec->circuit;
			$volts = $rec->volts;
			$amps = $rec->amps;
			$status = $rec->status;
			$load = $rec->load;
			
			
			/*   validation code coppied from processCircuitAction() */
			if(!isset($status) || strlen($status)==0)
				$status = "D";
			
			if($valid)$valid = ValidPowerCircuitVolts($volts);
			if($valid)$valid = ValidPowerCircuitAmps($amps);
			if($valid)$valid = ValidPowerCircuitStatus($status);
			if($valid)$valid = ValidPowerCircuitLoad($load, $amps);
			
			$isDoubleCircuit = (int)$volts == 208;
			$isTrippleCircuit = (int)$volts == 308;
			$updateAll = false;//need to update all 3 in 3 phase
			
			//DB CHECKS
			//check valid IDs in tables
			if($valid && $add && $locationID!=-1)$valid = ValidRecord("locationid","Location ID",$locationID,"dcim_location",true);
			
			if($valid)
			{//validate panel and look up info
				$valid = false;
				$query = "SELECT pp.powerpanelid, pp.name, pp.circuits
					FROM dcim_powerpanel AS pp
					WHERE pp.powerpanelid=?";
				
				if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $powerPanelID) || !$stmt->execute())
					$errorMessage[] = "Prepare 0 failed: ImportPowerCircuits($action) (" . $mysqli->errno . ") " . $mysqli->error;
				else
				{
					$stmt->store_result();
					$count = $stmt->num_rows;
					
					if($count==1)
					{
						$valid=true;
						$stmt->bind_result($powerPanelID, $powerPanelName, $panelCircuits);
						$stmt->fetch();
					}
					else if($count==0)
						$errorMessage[] = "ImportPowerCircuits() Power Panel #$powerPanelID not found";
					else
						$errorMessage[] = "ImportPowerCircuits() Found more than 1 Power Panel with ID#$powerPanelID";
				}
			}
			
			if($valid)$valid = ValidPowerCircuitNo($circuit, $panelCircuits);
			if($valid && $add && ($isDoubleCircuit || $isTrippleCircuit))$valid = ValidPowerCircuitNo($circuit+2, $panelCircuits);
			if($valid && $add && $isTrippleCircuit)$valid = ValidPowerCircuitNo($circuit+4, $panelCircuits);
			
			//check for existing panel circuit combo
			if($add && $valid)
			{
				$valid = false;
				$passedDBChecks = false;
				//this could be optomised by filtering inner selects by panel and/or range of circuit
				$filter = "";
				if($isDoubleCircuit)
					$filter = "csr.powerpanelid=? AND (csr.circuit=? OR csr.circuit=?)";
				else if($isTrippleCircuit)
					$filter = "csr.powerpanelid=? AND (csr.circuit=? OR csr.circuit=? OR csr.circuit=?)";
				else
					$filter = "csr.powerpanelid=? AND csr.circuit=?";
					
				$query = "SELECT pp.name, csr.* FROM (
					SELECT powerpanelid,powercircuitid,circuit,volts,amps, '' AS reserved
					FROM dcim_powercircuit
					UNION
					SELECT powerpanelid,powercircuitid,IF(volts=208,circuit+2,NULL) AS cir,volts,amps, 'T'
					FROM dcim_powercircuit HAVING NOT(cir IS NULL)
					) AS csr
					LEFT JOIN dcim_powerpanel AS pp ON pp.powerpanelid=csr.powerpanelid
					WHERE $filter
					ORDER BY circuit";
				
				if (!($stmt = $mysqli->prepare($query)))
					$errorMessage[] = "ImportPowerCircuits() Prepare 2 failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error.".";
				else
				{
					$failed = false;
					if($isDoubleCircuit)
					{
						$secondCircuit = 2+(int)$circuit;
						$failed = !$stmt->bind_Param('iss', $powerPanelID, $circuit, $secondCircuit);
						if($failed)
							$errorMessage[] = "ImportPowerCircuits() Bind 2b2 failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error.".";
					}
					else if($isTrippleCircuit)
					{
						$secondCircuit = 2+(int)$circuit;
						$thirdCircuit = 4+(int)$circuit;
						$failed = !$stmt->bind_Param('isss', $powerPanelID, $circuit, $secondCircuit,$thirdCircuit);
						if($failed)
							$errorMessage[] = "ImportPowerCircuits() Bind 2b3 failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error.".";
					}
					else
					{
						$failed = !$stmt->bind_Param('is', $powerPanelID, $circuit);
						if($failed)
							$errorMessage[] = "ImportPowerCircuits() Bind 2b1 failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error.".";
					}
					if (!$failed && !$stmt->execute())//execute
						$errorMessage[] = "ImportPowerCircuits() Execute 2c failed: ($action) (" . $mysqli->errno . ") " . $mysqli->error.".";
					else
					{
						$stmt->store_result();
						$count = $stmt->num_rows;
						
						if($count==0)
							$passedDBChecks = true;
						else
						{
							$stmt->bind_result($ppName, $ppID,$existingCircuitID, $c, $v, $a, $r);
							$stmt->fetch();
							
							$circuitExisted= true;
							$passedDBChecks = true;//will ignore circuit add bellow
							//$errorMessage[] = "Existing panel Circuit conflict found (Power Panel:$ppName(#$ppID), Circuit#$c) ID#$existingCircuitID. Cannot create duplicate.";
						}
					}
				}
				$valid=$passedDBChecks;
			}
			
			if($valid && $isTrippleCircuit && !$add)
			{
				//look up 2083p power circuits
				$lookupResult = Get3PhasePowerLookup($powerPanelID,$powerCircuitID);
				if($lookupResult==null)$valid = false;
				if($valid)
				{
					list($c1,$c2,$c3) = $lookupResult;
					//changes that should be pushed to all 3 records
					if($c1->status!=$status || $c2->status!=$status || $c3->status!=$status)$updateAll = true;
					if($c1->amps!=$amps|| $c2->amps!=$amps|| $c3->amps!=$amps)$updateAll = true;
				}
			}
			
			if($valid)
			{//do work
				if(!$circuitExisted)
				{//Import/add circuits
					$query = "INSERT INTO dcim_powercircuit
						(powerpanelid,circuit,volts,amps,status,`load`,edituser,editdate)
						VALUES(?,?,?,?,?,?,?,?)";
					
					//															   pcvaslud
					if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('isssssis', $powerPanelID, $circuit, $volts, $amps, $status, $load, $userID, $rec->date) || !$stmt->execute())
						$errorMessage[] = "ImportPowerCircuits_Add - Prepare failed: (a1) (" . $mysqli->errno . ") " . $mysqli->error;
					else
					{
						$affectedCount = $stmt->affected_rows;
						$totalAffectedCount += $affectedCount;
						if($affectedCount==1)
						{
							LogDBChange("dcim_powercircuit",-1,"I","powerpanelid='$powerPanelID' AND circuit='$circuit'");
							$resultMessage[] = "Successfully added power circuit (Panel#:".$powerPanelID." Circuit#".$circuit.").";
						}
						else
							$errorMessage[] = "ImportPowerCircuits() Power circuit added successfully, but affected $affectedCount rows.";
					}
				}
				if($circuitExisted || $affectedCount==1)
				{
					//look up inserted id
					$query = "SELECT pc.powercircuitid, pp.name
		FROM dcim_powercircuit AS pc
			LEFT JOIN dcim_powerpanel AS pp ON pp.powerpanelid=pc.powerpanelid
		WHERE pc.powerpanelid=? AND pc.circuit=?";
					
					if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('is', $powerPanelID, $circuit) || !$stmt->execute())
						$errorMessage[] = "ImportPowerCircuits_Add - Prepare failed: (a2) (" . $mysqli->errno . ") " . $mysqli->error;
					else
					{
						$stmt->store_result();
						$count = $stmt->num_rows;
						if($count==1)
						{
							$stmt->bind_result($powerCircuitID, $powerPanelName);
							$stmt->fetch();
							//update result message with the more usefull panel name
							if(!$circuitExisted) 
							{
								array_pop($resultMessage);
								$resultMessage[]= "Successfully added power circuit (Panel:".$powerPanelName." Circuit#".$circuit.").";
							}
						}
						
						if($count==1 && $locationID!=-1)
						{//create power circuit loc record
							//check if this powercircuitLoc rec exists
							$valid = false;
							$query = "SELECT pcl.powercircuitlocid
								FROM dcim_powercircuitloc AS pcl
								WHERE pcl.powercircuitid=? AND pcl.locationid=?";
							if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('ii', $powerCircuitID,$locationID)|| !$stmt->execute())
							{
								$errorMessage[] = "ImportPowerCircuits()_pcl lookup: Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
								return false;
							}
							$stmt->store_result();
							$stmt->bind_result($id);
							$valid = $stmt->num_rows==0;
							
							if($valid)
							{
								//sucsessfull Insert - insert circuit-location link record
								$query = "INSERT INTO dcim_powercircuitloc
									(powercircuitid,locationid,edituser,editdate)
									VALUES(?,?,?,?)";
								
								//															   plud
								if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('iiis', $powerCircuitID, $locationID, $userID,$rec->date) || !$stmt->execute())
									$errorMessage[] = "ImportPowerCircuits_Add - Prepare failed: (a3) (" . $mysqli->errno . ") " . $mysqli->error;
								else
								{
									$affectedCount = $stmt->affected_rows;
									$totalAffectedCount += $affectedCount;
									
									if($affectedCount==1)
									{
										LogDBChange("dcim_powercircuitloc",-1,"I","powercircuitid=$powerCircuitID AND locationid=$locationID");
										$resultMessage[] = "Successfully added power circuit location link (powerCircuitID:".$powerCircuitID.",locationID:".$locationID.").";
									}
									else
										$errorMessage[] = "ImportPowerCircuits() Power circuit location link added successfully, but affected $affectedCount rows.";
								}
							}
							else
							{//existing pcl record already exists
								$errorMessage[] = "ImportPowerCircuits() PowerCircuitLoc rec already exists ($powerCircuitID, $locationID).";
							}
						}
						else
						{
							if($count!=1)//only report error if circuit was not found, otherwist location was deleberately skipped
								$errorMessage[] = "ImportPowerCircuits() Failed to locate inserted record. Power (if created) is not linked to Location. PowerID:$powerPanelID Circuit:$circuit";
						}
					}
				}
			}
		}
		$resultMessage[] = "Successfully added $totalAffectedCount records.";
		$userID = $importUserID;
	}
?>