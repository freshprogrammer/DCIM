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
</script>
</head>
<font size=5><b><?php echo $config_appName;?> - Database Import</b></font><?php
 	echo " on PHP v".phpversion()."<BR>\n";
	
	$resultMessage[] = "Results - $timeStamp";
	$errorMessage[] = "Errors";
	$debugMessage[] = "Debug Messages - $timeStamp";
	
	// End Definitions - Start Processing ---------------------------------------------------------------------------------------------
	
	$validAction = false;
	$dbStatus = 0;
	$commited = false;
	$validSession = false;
	
	$debugMessage[]= "-Start - testing permisions and db status";
	if(!isset($config_demoSiteEnabled) || !$config_demoSiteEnabled)//this is not a demo server - anthing other that an update will screw with the core data or structure and is not allowed
		$validAction = true;
	else
		$validAction = true;//demo server - go wild
	
	if($validAction)
		$validSession = IsValidSession();
	if($validSession)
		$commited = true; //TestUserCommitment($dbScriptID);//validated in JS
	if($commited)
	{//must have passed all checks
		RunImport(true);
	}
	else
	{
		$errorMessage[]="User not commited. Aborted.";
	}
	
	if(CustomFunctions::UserHasDevPermission())
	{
		$debugMessageString  = implode("<BR>\n",$debugMessage);
		$errorMessageString  = implode("<BR>\n",$errorMessage);
		$resultMessageString = implode("<BR>\n",$resultMessage);
		if(strlen($debugMessageString) > 0) echo "<!-- DEBUG MESSAGE  -->\n<div id='debugMessage'  class='debugMessage'>$debugMessageString</div>\n";
		if(strlen($errorMessageString) > 0) echo "<!-- ERROR MESSAGE  -->\n<div id='errorMessage'  class='errorMessage'>$errorMessageString</div>\n";
		if(strlen($resultMessageString) > 0)echo "<!-- RESULT MESSAGE -->\n<div id='resultMessage' class='resultMessage'>$resultMessageString</div>\n";
	
		//simple import input form
		echo "<form action='' method='post' onsubmit='return ConfirmIntent()'>
		Location Import Data - roomid,name,altname,allocation,keyno,type,units,order,xpos,ypos,width,depth,orientation,notes:</BR>
		<textarea name='importdata' rows='5' cols='140' placeholder='roomid,name,altname,allocation,keyno,type,units,order,xpos,ypos,width,depth,orientation,notes'></textarea></BR>
		<input type='submit' value='Location Import'>
		<input type='hidden' name='page_instance_id' value='".end($_SESSION['page_instance_ids'])."'>
		</form>";
		
		//show db structure documentation on page for reffereance
		echo "<BR>";
	}
	else
	{
		$errorMessage = array();
		$errorMessage[]="You do not have access to import data.";
		
		$errorMessageString  = implode("<BR>\n",$errorMessage);
		if(strlen($errorMessageString) > 0) echo "<!-- ERROR MESSAGE  -->\n<div id='errorMessage'  class='errorMessage'>$errorMessageString</div>\n";
	}
	//echo "Page Content - Page End<BR>";
	
	//END PAGE - Begin local Functions - All actual processing functions are in the refferenced file
	
	function RunImport($fullProcessing=false)
	{
		ImportLocations($fullProcessing);
	}
	
	class locationRec
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
		global $mysqli;
		global $debugMessage;
		global $errorMessage;
		global $resultMessage;
		
		//$debugMessage[] = "Test Debug";
		//$errorMessage[] = "Test Error";
		//$resultMessage[] = "Test Result";
		
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
				case 0:$roomid		=$rec;break;
				case 1:$name		=$rec;break;
				case 2:$altname		=$rec;break;
				case 3:$allocation	=$rec;break;
				case 4:$keyno		=$rec;break;
				case 5:$type		=$rec;break;
				case 6:$units		=$rec;break;
				case 7:$order		=$rec;break;
				case 8:$xpos		=$rec;break;
				case 9:$ypos		=$rec;break;
				case 10:$width		=$rec;break;
				case 11:$depth		=$rec;break;
				case 12:$orientation=$rec;break;
				case 13:$notes		=$rec;
				
				//final data processing
				if($roomid=="Area 1")$roomid = 24;//DEN02 Area 1
				if($roomid=="Area 2")$roomid = 25;//DEN02 Area 1
				$allocation=$allocation[0];
				if($allocation=="O")$allocation = "E";//Open should be Empty
				$type=$type[0];
				if($type=="2")$type = "R";//2post should be Rack
				if($order=="True")$order="N";
				else $order = "R";
				
				$importObjects[]= new locationRec($roomid,$name,$altname,$allocation,$keyno,$type,$units,$order,$xpos,$ypos,$width,$depth,$orientation,$notes);
				break;
			}
			$i++;
		}
		
		//add Location
		if($fullProcessing)
			$resultMessage[]="Adding ".count($importObjects)." locations...";
		else
			$resultMessage[]="Dry run adding ".count($importObjects)." locations";
		foreach ($importObjects as $rec)
		//$rec = $customers [5];
		{
			//$resultMessage[] = "working on adding::$rec->roomid,$rec->name,'$rec->allocation','$rec->type',$rec->notes";
			
			//spoof input
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
			
			if($fullProcessing)
			{
				ProcessLocationAction("Location_Add");
			}
		}
	}
?>