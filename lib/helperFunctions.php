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
		public $portCount = 2;
		public $coloDevice = false;
		public $gigabit = true;
		public $partOfChasis = false;
		public $doubleRow = false;
		public $portsPerSet = 12;
		public $units = 1;
		public $showDeviceImage = false;
		
		//further defined in constructor based on model
		public $deviceWidthPx = 948;
		public $deviceHeightPx = 97;
		public $deviceImage = "images/devices/ex4200_front.jpg";
		
		function __construct($name, $startPort, $portCount, $coloDevice, $gigabit, $partOfChasis, $doubleRow, $portsPerSet, $units,$showDeviceImage) 
		{
			$this->name = $name;
			$this->startPort = $startPort;
			$this->portCount = $portCount;
			$this->coloDevice = $coloDevice;
			$this->gigabit = $gigabit;
			$this->partOfChasis = $partOfChasis;
			$this->doubleRow = $doubleRow;
			$this->portsPerSet = $portsPerSet;
			$this->units = $units;
			$this->showDeviceImage = $showDeviceImage;
			
			//device render properties that are differnet for each model
			if($this->name=="Colo Cabinet" || $this->name=="Colo Half Cabinet")
			{
				$this->deviceWidthPx = 950;
				$this->deviceHeightPx = 91;
				$this->deviceImage = "images/devices/patchpanel.jpg";
			}
			else if($this->name=="Juniper EX3200-24P" || $this->name=="Juniper EX3300-24P" || $this->name=="Juniper EX4200-24P" || $this->name=="Juniper EX4300-24P" ||
					$this->name=="Juniper EX3200-24T" || $this->name=="Juniper EX3300-24T" || $this->name=="Juniper EX4200-24T" || $this->name=="Juniper EX4300-24T")
				$this->deviceImage = "images/devices/ex4200_24p_front.jpg";
			else if($this->name=="Cisco WS-X6348")
			{
				$this->deviceWidthPx = 950;
				$this->deviceHeightPx = 105;
				$this->deviceImage = "images/devices/ws-x6348_front.jpg";
			}
			else if($this->name=="Cisco Catalyst 3550")
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
			if($this->name=="Colo Cabinet" || $this->name=="Colo Half Cabinet")
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
			else if($this->name=="Cisco WS-X6348")
			{
				$topOffset = 27;
				$bottomOffset = 59;
				$set1Offset = 55;
				$set2Offset = 74;
				$set3Offset = 83;
				$set4Offset = 85;
				$portSerperation = 7;
			}
			else if($this->name=="Cisco Catalyst 3550")
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
		global $genericDeviceModel;
		$genericDeviceModel = new DeviceModel("Generic Device", 1, 1				,false,true,false,false, 1, 1,false);
		
		/* This is here and not in a small lookup table just because other code in ShowDevicePage() is customizing CSS for basicly each device and I feel better with code dependant on code instead of DB records
		 * I supose this could be a modelID in dcim_device and and a dcim_model table which might make future device additions easier but then small CSS tweaks show be done in DB values which sounds super anoying.
		 * If this ever gets so lots of new models are being added that might be a consideration, but until then this works just fine.
		 *  - That would probably require a UI way to add/edit device models including their images, offsets, colors, port counts, ect.-UGH
		 * 
		 * future field ideas:
		 * size(U), type, port spacing and seperation, brand
		 */
		$deviceModels = array();
		
		$deviceModels[] = new DeviceModel("Colo Cabinet"							, 1, 2, true,false,false,false, 6, 1, true);//colo cabs and cages
		$deviceModels[] = new DeviceModel("Colo Half Cabinet"						, 1, 2, true,false,false,false, 6, 1, true);
		$deviceModels[] = new DeviceModel("Colo Cage"								, 1, 2, true,false,false,false, 6, 1,false);
		
		$deviceModels[] = new DeviceModel("Misc"									, 1, 1,false, true,false,false, 1, 1,false);
		
		$deviceModels[] = new DeviceModel("Alert Logic"								, 1, 1,false, true,false,false, 1, 1,false);
		$deviceModels[] = new DeviceModel("Cisco WS-X6348"							, 1,48,false,false, true, true,12, 1, true);
		$deviceModels[] = new DeviceModel("Cisco WS-X6K-SUP2-2GE"					, 1, 2,false, true, true,false, 1, 1,false);
		$deviceModels[] = new DeviceModel("Arbor Networks PKF-SP-CP5500-2"			, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Cisco Catalyst 4510R"					, 1,48,false, true,false,false,12,14,false);
		$deviceModels[] = new DeviceModel("Cisco Catalyst ASA5505"					, 0, 8,false,false,false,false, 8, 1,false);
		$deviceModels[] = new DeviceModel("Cisco Catalyst ASA5510"					, 0, 4,false, true,false,false, 4, 1,false);
		$deviceModels[] = new DeviceModel("Cisco Catalyst ASA5550"					, 0,12,false, true,false,false,12, 1,false);
		$deviceModels[] = new DeviceModel("Cisco Catalyst 2900"						, 0, 8,false,false,false,false, 8, 1,false);
		$deviceModels[] = new DeviceModel("Cisco Catalyst 3550"						, 0,24,false,false,false, true, 8, 1, true);
		$deviceModels[] = new DeviceModel("Cisco Catalyst 3560-24PS"				, 0,24,false,false,false, true, 8, 1,false);
		$deviceModels[] = new DeviceModel("Cisco Catalyst 6500"						, 0,48,false,false,false, true, 8,14,false);
		$deviceModels[] = new DeviceModel("Cisco Catalyst WS-C2960-48TC-L"			, 1,48,false, true, true, true,16, 1,false);
		$deviceModels[] = new DeviceModel("Cisco Catalyst WS-C2960G-24TC-L"			, 1,24,false, true, true, true,12, 1,false);
		$deviceModels[] = new DeviceModel("Cisco Catalyst WS-C6509"					, 1, 0,false,false, true,false, 0, 6,false);
		$deviceModels[] = new DeviceModel("Cisco PIX 506E"							, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Cisco PIX 515E"							, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Cisco Nexus 3064-E 48Port"				, 0,48,false, true,false, true,16, 1,false);
		$deviceModels[] = new DeviceModel("Coyote Point E350GX"						, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Coyote Point E450GX"						, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Dell Force 10 S25-01-GE-24T"				, 0,24,false, true,false, true,12, 1,false);
		$deviceModels[] = new DeviceModel("Dell PowerConnect 3348"					, 0,48,false, true,false, true,16, 1,false);
		$deviceModels[] = new DeviceModel("Dell PowerConnect 5424"					, 0,24,false, true,false, true,12, 1,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge 1500"						, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge 1550"						, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge 1650"						, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge 1750"						, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge 1850"						, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge 1950 Gen 1"				, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge 1950 Gen 2"				, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge 1950 Gen 3"				, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge 2850"						, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge 2900"						, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge 2950 Gen 1"				, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge 2950 Gen 2"				, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge 2950 Gen 3"				, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge 6950"						, 0, 2,false,false,false,false, 2, 4,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge R200"						, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge R200"						, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge R210"						, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge R300"						, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge R310"						, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge R410"						, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge R420"						, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge R430"						, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge R510"						, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge R610"						, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge R630"						, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge R710"						, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge R720"						, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge R730"						, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge R730xd"					, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge R810"						, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge R820"						, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Dell PowerEdge R830"						, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Dell PowerVault 114T"					, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Dell PowerVault 136T"					, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Dell PowerVault 220S"					, 0, 2,false,false,false,false, 2, 3,false);
		$deviceModels[] = new DeviceModel("Dell PowerVault MD1000"					, 0, 2,false,false,false,false, 2, 3,false);
		$deviceModels[] = new DeviceModel("Dell PowerVault MD3000"					, 0, 2,false,false,false,false, 2, 4,false);
		$deviceModels[] = new DeviceModel("Dell PowerVault NX3000"					, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("EMC Clariion CLARiiON CX-320c"			, 0, 2,false,false,false,false, 2, 3,false);
		$deviceModels[] = new DeviceModel("EMC Clariion CX4-480C"					, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("EMC Clariion CX4-4PDAE"					, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("EMC Connectrix DS-300B-8G"				, 0,24,false,false,false, true, 4, 1,false);
		$deviceModels[] = new DeviceModel("EMC Connectrix DS-5100B-8G"				, 0,40,false,false,false, true, 4, 1,false);
		$deviceModels[] = new DeviceModel("EMC Connectrix DS-5300B-8G"				, 0,40,false,false,false, true, 4, 1,false);
		$deviceModels[] = new DeviceModel("EMC VNX VNX51D15605F"					, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("EMC VNX VNX53D153015F"					, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("EMC VNX VNX53D156015F"					, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("EMC VNX VNX5700SPEF"						, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("F5 BIG-IP SWCH LTM 2000S 8GB"			, 0, 8,false,false,false, true, 2, 1,false);
		$deviceModels[] = new DeviceModel("F5 BIG-IP SWCH LTM 4200V 16GB"			, 0, 8,false,false,false, true, 2, 1,false);
		$deviceModels[] = new DeviceModel("F5 BIG-IP-LTM-6900"						, 0,16,false, true,false, true,16, 1,false);
		$deviceModels[] = new DeviceModel("F5 BIG-LTM-1600-4G-R"					, 0, 4,false,false,false, true, 2, 1,false);
		$deviceModels[] = new DeviceModel("F5 BIG-LTM-3900-8G"						, 0, 8,false,false,false, true, 2, 1,false);
		$deviceModels[] = new DeviceModel("F5 BIG-LTM-I2600"						, 0, 4,false,false,false, true, 2, 1,false);
		$deviceModels[] = new DeviceModel("HP ProCurve 2610-24"						, 0,24,false, true,false, true,12, 1,false);
		$deviceModels[] = new DeviceModel("HP ProCurve 2650"						, 0,48,false, true,false, true,16, 1,false);
		$deviceModels[] = new DeviceModel("HP ProCurve 2810-48G"					, 0,48,false, true,false, true,12, 1,false);
		$deviceModels[] = new DeviceModel("HP ProCurve 2824"						, 0,24,false, true,false, true,12, 2,false);
		$deviceModels[] = new DeviceModel("HP ProCurve 2848"						, 0,48,false, true,false, true,16, 2,false);
		$deviceModels[] = new DeviceModel("HP ProCurve 2900-24G"					, 0,24,false, true,false, true,12, 1,false);
		$deviceModels[] = new DeviceModel("HP ProCurve 2510G-24"					, 0,24,false, true,false, true,12, 2,false);
		$deviceModels[] = new DeviceModel("HP ProLiant 1850R"						, 0, 0,false,false,false,false, 0, 8,false);
		$deviceModels[] = new DeviceModel("HP ProLiant DL320"						, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("HP ProLiant DL360"						, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("HP ProLiant DL360 G3"					, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("HP ProLiant DL360 G4"					, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("HP ProLiant DL380"						, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("HP ProLiant DL380 G3"					, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("HP ProLiant DL580"						, 0, 4,false,false,false,false, 4, 4,false);
		$deviceModels[] = new DeviceModel("HP ProLiant ML 370"						, 0, 2,false,false,false,false, 2, 4,false);
		$deviceModels[] = new DeviceModel("IBM eserver xSeries 300"					, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("IBM eserver xSeries 342"					, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("IBM eserver xSeries 350"					, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("IBM netfinity 8656-4RY"					, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("IBM System Storage DS-4700"				, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Juniper CHAS-BP-MX240-S"					, 1, 0,false,false,false,false, 0, 6,false);
		$deviceModels[] = new DeviceModel("Juniper EX3200-24P"						, 0,24,false, true, true, true,12, 1, true);
		$deviceModels[] = new DeviceModel("Juniper EX3200-24T"						, 0,24,false, true, true, true,12, 1, true);
		$deviceModels[] = new DeviceModel("Juniper EX3200-48P"						, 0,48,false, true, true, true,12, 1, true);
		$deviceModels[] = new DeviceModel("Juniper Ex3200-48T"						, 0,48,false, true, true, true,12, 1, true);
		$deviceModels[] = new DeviceModel("Juniper EX3300-24P"						, 0,24,false, true, true, true,12, 1, true);
		$deviceModels[] = new DeviceModel("Juniper EX3300-24T"						, 0,24,false, true, true, true,12, 1, true);
		$deviceModels[] = new DeviceModel("Juniper EX3300-48T"						, 0,48,false, true, true, true,12, 1, true);
		$deviceModels[] = new DeviceModel("Juniper EX3400-48T-AFI"					, 0,48,false, true, true, true,12, 1, true);
		$deviceModels[] = new DeviceModel("Juniper EX4200-24F"						, 0,24,false, true, true, true,12, 1, true);
		$deviceModels[] = new DeviceModel("Juniper EX4200-24P"						, 0,24,false, true, true, true,12, 1, true);
		$deviceModels[] = new DeviceModel("Juniper EX4200-24PX"						, 0,24,false, true, true, true,12, 1, true);
		$deviceModels[] = new DeviceModel("Juniper EX4200-24T"						, 0,24,false, true, true, true,12, 1, true);
		$deviceModels[] = new DeviceModel("Juniper EX4200-48P"						, 0,48,false, true, true, true,12, 1, true);
		$deviceModels[] = new DeviceModel("Juniper EX4200-48PX"						, 0,48,false, true, true, true,12, 1, true);
		$deviceModels[] = new DeviceModel("Juniper EX4200-48T"						, 0,48,false, true, true, true,12, 1, true);
		$deviceModels[] = new DeviceModel("Juniper EX4300-24T"						, 0,24,false, true, true, true,12, 1, true);
		$deviceModels[] = new DeviceModel("Juniper EX4300-48T"						, 0,48,false, true, true, true,12, 1, true);
		$deviceModels[] = new DeviceModel("Juniper EX4500-40F-VC1-BF"				, 0,48,false, true,false, true,12, 1,false);
		$deviceModels[] = new DeviceModel("Juniper EX4550-32F-AFI"					, 0,48,false, true,false, true,12, 1,false);
		$deviceModels[] = new DeviceModel("Juniper EX6210-S64-96T-A25"				, 0,48,false, true,false, true,12, 1,false);
		$deviceModels[] = new DeviceModel("Juniper EX8208-BASE-AC"					, 0, 0,false,false,false,false, 0,14,false);
		$deviceModels[] = new DeviceModel("Juniper Junos Pulse MAG2600"				, 0, 3,false,false,false,false, 3, 1,false);
		$deviceModels[] = new DeviceModel("Juniper Junos Pulse MAG4610"				, 0, 4,false,false,false,false, 4, 1,false);
		$deviceModels[] = new DeviceModel("Juniper M10i"							, 0, 0,false,false,false,false, 0, 5,false);
		$deviceModels[] = new DeviceModel("Juniper MX104-PREM-AC-BNDL"				, 0, 0,false,false,false,false, 0, 4,false);
		$deviceModels[] = new DeviceModel("Juniper MX240BASE-AC-HIGH"				, 0, 0,false,false,false,false, 0, 4,false);
		$deviceModels[] = new DeviceModel("Juniper MX240BASE-DC"					, 0, 0,false,false,false,false, 0, 4,false);
		$deviceModels[] = new DeviceModel("Juniper MX240-PREMIUM-AC-LOW"			, 0, 0,false,false,false,false, 0, 4,false);
		$deviceModels[] = new DeviceModel("Juniper MX480BASE-AC"					, 0, 0,false,false,false,false, 0,10,false);
		$deviceModels[] = new DeviceModel("Juniper MX5-T-AC"						, 0, 2,false, true,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Juniper MX80-AC"							, 0, 2,false, true,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Juniper MX80-AC-B"						, 0, 2,false, true,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("Juniper NS-ISG 1000"						, 0, 0,false, true,false,false, 0, 2,false);
		$deviceModels[] = new DeviceModel("Juniper QFX5100-24Q-AFI"					, 0,24,false, true,false, true,12, 1,false);
		$deviceModels[] = new DeviceModel("Juniper QFX5100-48S-AFI"					, 0,48,false, true,false, true,16, 1,false);
		$deviceModels[] = new DeviceModel("Juniper QFX5100-96S-AFI"					, 0,96,false, true,false, true,16, 2,false);
		$deviceModels[] = new DeviceModel("Juniper QFX5100-96S-AFO"					, 0,96,false, true,false, true,16, 2,false);
		$deviceModels[] = new DeviceModel("Juniper SA SA2500"						, 0,24,false, true,false, true,12, 1,false);
		$deviceModels[] = new DeviceModel("Juniper SRX100H"							, 0, 8,false, true,false,false, 8, 1,false);
		$deviceModels[] = new DeviceModel("Juniper SRX100H2"						, 0, 8,false, true,false,false, 8, 1,false);
		$deviceModels[] = new DeviceModel("Juniper SRX1500-AC"						, 0,12,false, true,false, true,12, 1,false);
		$deviceModels[] = new DeviceModel("Juniper SRX220H"							, 0, 8,false, true,false,false, 8, 1,false);
		$deviceModels[] = new DeviceModel("Juniper SRX220H2"						, 0, 8,false, true,false,false, 8, 1,false);
		$deviceModels[] = new DeviceModel("Juniper SRX240H"							, 0,16,false, true,false, true, 8, 1,false);
		$deviceModels[] = new DeviceModel("Juniper SRX240H2"						, 0,16,false, true,false, true, 8, 1,false);
		$deviceModels[] = new DeviceModel("Juniper SRX300"							, 0, 6,false, true,false,false, 6, 1,false);
		$deviceModels[] = new DeviceModel("Juniper SRX300-SYS-JB"					, 0, 6,false, true,false,false, 6, 1,false);
		$deviceModels[] = new DeviceModel("Juniper SRX340"							, 0, 6,false, true,false,false, 6, 1,false);
		$deviceModels[] = new DeviceModel("Juniper SRX340-SYS-JB"					, 0, 6,false, true,false,false, 6, 1,false);
		$deviceModels[] = new DeviceModel("Juniper SRX345"							, 0, 6,false, true,false,false, 6, 1,false);
		$deviceModels[] = new DeviceModel("Juniper SRX345-SYS-JB"					, 0, 6,false, true,false,false, 6, 1,false);
		$deviceModels[] = new DeviceModel("Juniper SRX3600BASE-AC"					, 0, 0,false,false,false, true, 0, 5,false);
		$deviceModels[] = new DeviceModel("Juniper SRX550-645AP"					, 0, 0,false,false,false, true, 0, 4,false);
		$deviceModels[] = new DeviceModel("Juniper SRX650-BASE-SRE6-645AP"			, 0, 0,false,false,false, true, 0, 4,false);
		$deviceModels[] = new DeviceModel("Juniper SSG 140 System 256MB"			, 0, 8,false, true,false,false, 8, 1,false);
		$deviceModels[] = new DeviceModel("Juniper SSG 140 System 512MB"			, 0, 8,false, true,false,false, 8, 1,false);
		$deviceModels[] = new DeviceModel("Juniper SSG 550M"						, 0, 0,false,false,false,false, 0, 2,false);
		$deviceModels[] = new DeviceModel("Juniper SSG-140"							, 0, 8,false, true,false,false, 8, 1,false);
		$deviceModels[] = new DeviceModel("Juniper SSG-350M-SH"						, 0,16,false, true,false, true,16, 2,false);
		$deviceModels[] = new DeviceModel("Juniper SSG-550M-SH"						, 0, 7,false, true,false,false, 7, 1,false);
		$deviceModels[] = new DeviceModel("Juniper SSG-550-SB"						, 0, 7,false, true,false,false, 7, 1,false);
		$deviceModels[] = new DeviceModel("Juniper SSG-5-SB 128mb"					, 0, 7,false, true,false,false, 7, 1,false);
		$deviceModels[] = new DeviceModel("Juniper SSG-5-SH"						, 0, 7,false, true,false,false, 7, 1,false);
		$deviceModels[] = new DeviceModel("Lantronix SLC01612N-02 -16PT"			, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Lantronix SLC16"							, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("Lantronix SLC80481201S"					, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("NetApp AllFlash AFF8040A-001-R6"			, 0, 2,false, true,false,false, 2, 4,false);
		$deviceModels[] = new DeviceModel("NetApp ClusterNet X190001"				, 0,32,false, true,false, true,16, 1,false);
		$deviceModels[] = new DeviceModel("NetApp ClusterNet X1960-R6-C"			, 0,16,false, true,false, true,16, 1,false);
		$deviceModels[] = new DeviceModel("NetApp ClusterNet X1967-R6"				, 0,48,false, true,false, true,16, 1,false);
		$deviceModels[] = new DeviceModel("NetApp ClusterNet X1980-R6-C"			, 0,16,false, true,false, true,16, 1,false);
		$deviceModels[] = new DeviceModel("Netapp FAS2554- 313-R6-C"				, 0, 2,false,false,false,false, 2, 4,false);
		$deviceModels[] = new DeviceModel("Netapp FAS2554A- 001-R6"					, 0, 2,false,false,false,false, 2, 4,false);
		$deviceModels[] = new DeviceModel("Netapp FAS2554A- 302-R6"					, 0, 2,false,false,false,false, 2, 4,false);
		$deviceModels[] = new DeviceModel("Netapp FAS3220AE-BASE-R6"				, 0, 2,false,false,false,false, 2, 4,false);
		$deviceModels[] = new DeviceModel("Netapp FAS3250AE-FC-BASE-R6"				, 0, 2,false,false,false,false, 2, 4,false);
		$deviceModels[] = new DeviceModel("Netapp FAS8040A- 001-R6"					, 0, 2,false,false,false,false, 2, 4,false);
		$deviceModels[] = new DeviceModel("Netapp FAS8060A- 001-R6"					, 0, 2,false,false,false,false, 2, 4,false);
		$deviceModels[] = new DeviceModel("Netapp FAS8080AE-001-R6"					, 0, 2,false,false,false,false, 2, 4,false);
		$deviceModels[] = new DeviceModel("Netapp FAS8200A-002"						, 0, 2,false,false,false,false, 2, 4,false);
		$deviceModels[] = new DeviceModel("Netapp FAS9000A-101-C"					, 0, 2,false,false,false,false, 2, 4,false);
		$deviceModels[] = new DeviceModel("OpenGear CM7100"							, 0,32,false,false,false, true,16, 2,false);
		$deviceModels[] = new DeviceModel("Palo Alto Networks PA-220"				, 0, 8,false, true,false,false, 8, 1,false);
		$deviceModels[] = new DeviceModel("Palo Alto Networks PA-820"				, 0,12,false, true,false, true, 2, 1,false);
		$deviceModels[] = new DeviceModel("Science Logic Log Manager"				, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("StarTech SV1108IPEXT"					, 0, 0,false,false,false,false, 0, 1,false);
		$deviceModels[] = new DeviceModel("StarTech SV1115IPEXT"					, 0, 0,false,false,false,false, 0, 1,false);
		$deviceModels[] = new DeviceModel("SuperMicro SC811T-260"					, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperChasis CSE-811TQ-520"	, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperChasis CSE-825TQ-R720LPB", 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperChasis CSE-836E-16-R1200B",0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperChasis CSE-837E26-RJBOD1", 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperChasis CSE-847E26-RJBOD1", 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperServe 5013C-MT"			, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperServe 5013C-T"			, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperServe 5015M-MT"			, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperServe 5015M-MT+"			, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperServe 6012"				, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperServe 6012L-6"			, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperServe 6013A-T"			, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperServe 6013P-8"			, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperServe 6014H-82"			, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperServe 6014V-T2"			, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperServe 6015V-T"			, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperServe 6025W-NTR+"		, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperServe 6026T-NTR+"		, 0, 2,false,false,false,false, 2, 2,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperServe SSG-6027R"			, 0, 4,false, true,false,false, 4, 2,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperServer 4U"				, 0, 2,false,false,false,false, 2, 4,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperServer SYS-6025W-NTR+"	, 0, 2,false,false,false,false, 2, 1,false);
		$deviceModels[] = new DeviceModel("SuperMicro SuperServer SYS-6026TT-BTRF"	, 0, 2,false,false,false,false, 2, 1,false);
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
		else if($table=="dcim_powerpanel")			$descrip='Power Panel';
		else if($table=="dcim_powerups")			$descrip='Power UPS';
		else if($table=="dcim_powercircuit")		$descrip='Power Circuit';
		else if($table=="dcim_powercircuitloc")		$descrip='Power Circuit Location';
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
		else if($table=="dcimlog_powerpanel")		$descrip='Power Panel Log';
		else if($table=="dcimlog_powerups")			$descrip='Power UPS Log';
		else if($table=="dcimlog_powercircuit")		$descrip='Power Circuit Log';
		else if($table=="dcimlog_powercircuitloc")	$descrip='Power Circuit Location Log';
		else if($table=="dcimlog_room")				$descrip='Room Log';
		else if($table=="dcimlog_site")				$descrip='Site Log';
		else if($table=="dcimlog_vlan")				$descrip='Subnet Log';
		else $descrip = false;
		return $descrip;
	}
	
	function GetKeyField($table)
	{//ignores config table that has no real key
		if($table=="dcim_badge")					$keyFieldName='badgeid';
		else if($table=="dcim_customer")			$keyFieldName='hno';
		else if($table=="dcim_device")				$keyFieldName='deviceid';
		else if($table=="dcim_deviceport")			$keyFieldName='deviceportid';
		else if($table=="dcim_portconnection")		$keyFieldName='portconnectionid';
		else if($table=="dcim_location")			$keyFieldName='locationid';
		else if($table=="dcim_portvlan")			$keyFieldName='portvlanid';
		else if($table=="dcim_powerpanel")			$keyFieldName='powerpanelid';
		else if($table=="dcim_powerups")			$keyFieldName='powerupsid';
		else if($table=="dcim_powercircuit")		$keyFieldName='powercircuitid';
		else if($table=="dcim_powercircuitloc")		$keyFieldName='powercircuitlocid';
		else if($table=="dcim_room")				$keyFieldName='roomid';
		else if($table=="dcim_site")				$keyFieldName='siteid';
		else if($table=="dcim_user")				$keyFieldName='userid';
		else if($table=="dcim_vlan")				$keyFieldName='vlanid';
		//logs
		else if($table=="dcimlog_badge")			$keyFieldName='badgelogid';
		else if($table=="dcimlog_customer")			$keyFieldName='customerlogid';
		else if($table=="dcimlog_device")			$keyFieldName='devicelogid';
		else if($table=="dcimlog_deviceport")		$keyFieldName='deviceportlogid';
		else if($table=="dcimlog_portconnection")	$keyFieldName='portconnectionlogid';
		else if($table=="dcimlog_location")			$keyFieldName='locationlogid';
		else if($table=="dcimlog_portvlan")			$keyFieldName='portvlanlogid';
		else if($table=="dcimlog_powerpanel")		$keyFieldName='powerpanellogid';
		else if($table=="dcimlog_powerups")			$keyFieldName='powerupslogid';
		else if($table=="dcimlog_powercircuit")		$keyFieldName='powercircuitlogid';
		else if($table=="dcimlog_powercircuitloc")	$keyFieldName='powercircuitloclogid';
		else if($table=="dcimlog_room")				$keyFieldName='roomlogid';
		else if($table=="dcimlog_site")				$keyFieldName='sitelogid';
		else if($table=="dcimlog_vlan")				$keyFieldName='vlanlogid';
		else $keyFieldName = false;
		return $keyFieldName;
	}
	
	function GetRecordPageKey($table)
	{
		//TODO cust is really the only odd ball, i should probably just change it to hno
		if($table=="dcim_customer")			$pageKey='host';
		else if($table=="dcim_device")		$pageKey='deviceid';
		else if($table=="dcim_location")	$pageKey='locationid';
		else if($table=="dcim_room")		$pageKey='roomid';
		else if($table=="dcim_user")		$pageKey='userid';
		else if($table=="dcim_powerpanel")	$pageKey='powerpanelid';
		else if($table=="dcim_powerups")	$pageKey='powerupsid';
		else if($table=="dcim_site")		$pageKey='siteid';
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
		else if($table=="dcim_powerpanel")		$logTable='dcimlog_powerpanel';
		else if($table=="dcim_powerups")		$logTable='dcimlog_powerups';
		else if($table=="dcim_powercircuit")	$logTable='dcimlog_powercircuit';
		else if($table=="dcim_powercircuitloc")	$logTable='dcimlog_powercircuitloc';
		else if($table=="dcim_room")			$logTable='dcimlog_room';
		else if($table=="dcim_site")			$logTable='dcimlog_site';
		else if($table=="dcim_vlan")			$logTable='dcimlog_vlan';
		else $logTable = false;
		return $logTable;
	}
	
	function GetParentTable($table)
	{
		if($table=="dcim_badge")				$parentTable='dcim_customer';
		else if($table=="dcim_customer")		$parentTable=false;
		else if($table=="dcim_device")			$parentTable='dcim_customer';
		else if($table=="dcim_deviceport")		$parentTable='dcim_device';
		else if($table=="dcim_portconnection")	$parentTable='dcim_deviceport';
		else if($table=="dcim_location")		$parentTable='dcim_room';
		else if($table=="dcim_portvlan")		$parentTable='dcim_deviceport';
		else if($table=="dcim_powerpanel")		$parentTable='dcim_powerups';
		else if($table=="dcim_powerups")		$parentTable='dcim_site';
		else if($table=="dcim_powercircuit")	$parentTable='dcim_powerpanel';
		else if($table=="dcim_powercircuitloc")	$parentTable='dcim_powercircuit';
		else if($table=="dcim_room")			$parentTable='dcim_site';
		else if($table=="dcim_site")			$parentTable=false;
		else if($table=="dcim_vlan")			$parentTable=false;
		else $parentTable= false;
		return $parentTable;
	}
	
	function GetTableNameField($table)
	{
		if($table=="dcim_badge")				$nameField='name';
		else if($table=="dcim_customer")		$nameField='name';
		else if($table=="dcim_device")			$nameField='name';
		else if($table=="dcim_deviceport")		$nameField='port';
		else if($table=="dcim_portconnection")	$nameField='portconnectionid';
		else if($table=="dcim_location")		$nameField='name';
		else if($table=="dcim_portvlan")		$nameField='portvlanid';
		else if($table=="dcim_powerpanel")		$nameField='name';
		else if($table=="dcim_powerups")		$nameField='name';
		else if($table=="dcim_powercircuit")	$nameField='circuit';
		else if($table=="dcim_powercircuitloc")	$nameField='powercircuitlocid';
		else if($table=="dcim_room")			$nameField='name';
		else if($table=="dcim_site")			$nameField='name';
		else if($table=="dcim_vlan")			$nameField='vlan';
		else $nameField= false;
		return $nameField;
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
		
		$query = "SELECT userid, username, name, initials FROM dcim_user";

		if (!($stmt = $mysqli->prepare($query)) || !$stmt->execute())
		{
			echo "BuildUsersHashTable() Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		else
		{
			$stmt->store_result();
			$stmt->bind_result($userID, $uName, $uFullName, $uInitials);
			
			while ($stmt->fetch())
			{
				$userName["$userID"] = MakeHTMLSafe($uName);
				$userFullName["$userID"] = MakeHTMLSafe($uFullName);
				$userInitials["$userID"] = MakeHTMLSafe($uInitials);
			}
		}
	}
	
	function GetMySqlVersion()
	{
		global $mysqli;
		
		$query = "SHOW VARIABLES LIKE 'version'";
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->execute())
		{
			echo "GetMySqlVersion() Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		else
		{
			$stmt->store_result();
			$stmt->bind_result($variableName, $value);
			$stmt->fetch();
			return $value;
		}
		return null;
	}
	
	function UpdateSettingsForiPad()
	{
		global $config_loginCookieDurration;
		global $config_loginCookieDurrationOniPad;
		
		$isiPad = (bool) strpos($_SERVER['HTTP_USER_AGENT'],'iPad');
		
		if($isiPad)
		{
			$config_loginCookieDurration = $config_loginCookieDurrationOniPad;
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
		$result .= "<span class='tableTitle'>$title</span><a href='#$reportDivID' onclick='$reportHideFunctionName();'>&#x1F517;</a>\n";
		if($enableSwitcher)
			$result .= " <a id='$reportToggleLink' href='#$reportDivID' onclick='$reportHideFunctionName(); return false;'>Toggle</a>\n";
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
		$result = "<table class='data-table'>\n";
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
		$cellID = "qaButtonID_".$table."_".$recID;
		if($cell)
			$resultHTML = "<td class='data-table-cell-button editButtons_hidden' align='center' id='$cellID' rowspan='$rowSpan'>\n";
		else 
			$resultHTML = "<span class='editButtons_hidden'>QA: <span id='$cellID'>";
			
		$qaStatus = DoesRecordRequireQA($editUserID, $editDate, $qaUserID, $qaDate);
		if($qaStatus==1)
		{
			$instanceID = end($_SESSION['page_instance_ids']);
			$resultHTML .= "<button onclick='QARecord(\"$table\",$recID,\"$cellID\")'>QA</button>\n";
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
			$resultHTML .= " </span></span>\n";
		return $resultHTML;
	}
	
	function GetTableFieldsFromDocs($table, $runFromSetup=false)
	{
		$result = null;
		$path = "documentation/";
		if($runFromSetup)$path = "../../".$path;
		$dbDocs = file_get_contents($path."database_structure.md");
		$lines = explode("\n", $dbDocs);
		$found = false;
		$fieldNo = -1;
		foreach ($lines as $line)
		{
			if(!$found && strpos($line,$table))
			{
				$found = true;
				$result = array();
				continue;
			}
			if($found)
			{
				if(strlen(trim($line))==0)break;//end of table
				$fieldNo++;
				if($fieldNo<=1)continue;//headers
				
				$end = strpos($line,"|",1);
				$field = substr($line,1,$end-1);
				$result[]=$field;
				//echo "F#$fieldNo - line=$line -e#$end Field:$field<BR>";
			}
		}
		return  $result;
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
		if ($severity==3)//bad
			$result = "<table margin=><tr><td bgcolor=red class='simpleMessageCell'>$msg</td></tr></table>";
		else if ($severity==2)//warning
			$result = "<table margin=><tr><td bgcolor=orange class='simpleMessageCell'>$msg</td></tr></table>";
		else if ($severity==1)//good
			$result = "<table margin=><tr><td bgcolor=lime class='simpleMessageCell'>$msg</td></tr></table>";
		else//unknown
			$result = "<table margin=><tr><td bgcolor=pink class='simpleMessageCell'>$msg</td></tr></table>";
			
		return $result;
	}
	
	function GetDeviceFromModelName($deviceModelName)
	{
		global $deviceModels;
		global $genericDeviceModel;
		
		$device = $genericDeviceModel;
		foreach($deviceModels as $model)
		{
			if(strcasecmp($model->name,$deviceModelName)==0)
			{
				$device = $model;
				break;
			}
		}
		return $device;
	}
	
	function GetDeviceFullName($deviceName, $deviceModel, $deviceMember, $altName, $short)
	{
		$deviceInfo = GetDeviceFromModelName($deviceModel);
		
		if(strLen($altName)>0)
		{
			$altName = " ($altName)";
		}
		
		$deviceFullName = $deviceName.$altName;
		if($deviceInfo!=null && $deviceInfo->partOfChasis)
		{
			if($short)
				$deviceFullName = $deviceName.$altName." M#".$deviceMember;
			else
				$deviceFullName = $deviceName.$altName." Member #".$deviceMember;
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
	
	function FormatLocation($siteName, $roomName, $locationName, $showSite=true, $showLocation=true)
	{
		if($locationName==null)
			$locationName = "N/A";
		if(!$showLocation)
			$locationName = "";
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
	
	function FormatVolts($volts)
	{
		if($volts==308)
			return "208v3p";
		else
			return $volts."v";
		
	}
	
	function Format208CircuitNumber($circuit)
	{
		return $circuit."/".((int)$circuit+2);
	}
	
	function FormatPowerUtilization($load,$amps, $limit=80)
	{
		if($amps>0)
		{
			$utilization = round(100*$load/$amps,2);
				
			if($utilization>=$limit)
				$utilization = " (<font color=red>$utilization%</font>)";
			else if($load==0)
				$utilization = "";
			else
				$utilization = " ($utilization%)";
		}
		else
			$utilization = "";
		return $utilization;
	}
	
	function TestForSingleCustomerMatch($input)
	{
		global $mysqli;
		/* this is dissabled for #232
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
		else*/
			return false;
	}
	
	function IsUserUsingDefaultPassword()
	{//these are only valid if the sdefault salt is used - should be move to the DCIM functions to allow salt to be changed
		global $config_userPasswordSalt;
		$result = false;
		if(isset($_COOKIE["dcim_password"]))
		{
			$password = $_COOKIE["dcim_password"];
			if($password==     md5("Pa55word".$config_userPasswordSalt))return true;
			else if($password==md5("password1".$config_userPasswordSalt))return true;
			else if($password==md5("testPass".$config_userPasswordSalt))return true;
			else if($password==md5("chang3me".$config_userPasswordSalt))return true;
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
	
	function DBLogType($type, $pastTenseVerb=false)
	{
		if(!$pastTenseVerb)
		{
			if($type === "I") return "Insert";
			else if($type === "U") return "Update";
			else if($type === "D") return "Delete";
			else return "Unknown";
		}
		else
		{
			if($type === "I") return "Inserted";
			else if($type === "U") return "Updated";
			else if($type === "D") return "Deleted";
			else return "Unknown";
		}
	}
	
	function DeviceType($type)
	{
		if($type === "C") return "Cage";
		else if($type === "F") return "Cabinet";
		else if($type === "H") return "Half Cabinet";
		else if($type === "S") return "Physical";
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
		else if($type === "F") return "Cabinet";
		else if($type === "H") return "Half Cabinet";
		else if($type === "M") return "Misc";
		else if($type === "R") return "Rack";
		else return "Unknown";
	}
	
	function LocationAllocation($allocation)
	{
		if($allocation === "E") return "Empty";
		else if($allocation === "C") return "Colo";
		else if($allocation === "I") return "Internal";
		else if($allocation === "M") return "Managed";
		else if($allocation === "R") return "Reserved";
		else return "Unknown";
	}
	
	function LocationOrder($order)
	{
		if($order === "N") return "Normal";
		else if($order === "R") return "Reversed";
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
		if(is_nan($editUserID)) return "$editUserID is NaN";
		
		$editUserInitials = "??";
		$editUserFullName = "????";
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
				$qaUserFullName = "User#$qaUserID";
			else
				$qaUserFullName = $userFullName[$qaUserID];
			$qaDescription = "$qaUserFullName";//no need to show date here
		}
		
		$lastEditBrief = $editUserInitials;
		$lastEditFull = $editUserFullName . ": ".$editDate." QA:$qaDescription";
		
		if(strlen($visibleText)==0)
			$visibleText = $lastEditBrief;
		return "<span title=\"$lastEditFull\">$visibleText</span>";
	}
	
	function ValidNumber($input, $fieldName, $minLen, $maxLen=0, $minVal=-1, $maxVal=-1)
	{
		global $errorMessage;
		//$input = (int)$input;
		if(strlen($input) < $minLen)
		{
			$errorMessage[] = $fieldName."('$input') length(".strlen($input).") cannot be less than ".$minLen.".";
			return false;
		}
		else if($maxLen >0 && strlen($input) > $maxLen)
		{
			$errorMessage[] = $fieldName."('$input') length(".strlen($input).") cannot be greater than ".$maxLen.".";
			return false;
		}
		else if(!is_numeric($input))
		{
			$errorMessage[] = $fieldName." \"".$input ."\" must be numeric";
			return false;
		}
		else if($minVal!=-1 && $input<$minVal)
		{
			$errorMessage[] = $fieldName." ".$input ." must be greater than or equal to ".$minVal;
			return false;
		}
		else if($maxVal!=-1 && $input>$maxVal)
		{
			$errorMessage[] = $fieldName." ".$input ." must be less than or equal to ".$maxVal;
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
	
	function GetPowerPanelID($site, $panel)
	{
		global $mysqli;
		global $errorMessage;
		$query = "SELECT pp.powerpanelid
			FROM dcim_powerpanel AS pp
				INNER JOIN dcim_room AS r ON r.roomid=pp.roomid
				INNER JOIN dcim_site AS s ON s.siteid=r.siteid
			WHERE s.name = ? AND pp.name = ?";
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('ss', $site,$panel)|| !$stmt->execute())
		{
			$errorMessage[] = "GetPowerPanelID($site,$panel): Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			return false;
		}
		$stmt->store_result();
		$stmt->bind_result($id);
		if($stmt->num_rows==1)
		{
			$stmt->fetch();
			return $id;
		}
		else if($stmt->num_rows==2)
		{
			$errorMessage[] = "Multiple records found in GetPowerPanelID($site,$panel). Contact Admin.";
			return -1;
		}
		else
		{
			//$errorMessage[] = "Power Panel not found - GetPowerPanelID($site,$panel).";
			return -1;
		}
	}
	
	function GetLocationID($site, $room, $location)
	{
		global $mysqli;
		global $errorMessage;
		$query = "SELECT l.locationid
			FROM dcim_location AS l
				INNER JOIN dcim_room AS r ON r.roomid=l.roomid
				INNER JOIN dcim_site AS s ON s.siteid=r.siteid
			WHERE s.name = ? AND r.name = ? AND l.name = ?";
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('sss', $site,$room,$location)|| !$stmt->execute())
		{
			$errorMessage[] = "GetLocationID($site,$room,$location): Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			return false;
		}
		$stmt->store_result();
		$stmt->bind_result($id);
		if($stmt->num_rows==1)
		{
			$stmt->fetch();
			return $id;
		}
		else if($stmt->num_rows==2)
		{
			$errorMessage[] = "Multiple records found in GetLocationID($site,$room,$location). Contact Admin.";
			return -1;
		}
		else
		{
			//$errorMessage[] = "Location not found - GetLocationID($site,$room,$location).";
			return -1;
		}
	}
	
	function GetUserID($username)
	{
		global $mysqli;
		global $errorMessage;
		$query = "SELECT u.userid
			FROM dcim_user AS u
			WHERE u.username = ?";
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $username)|| !$stmt->execute())
		{
			$errorMessage[] = "GetUserID($username): Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			return false;
		}
		$stmt->store_result();
		$stmt->bind_result($id);
		if($stmt->num_rows==1)
		{
			$stmt->fetch();
			return $id;
		}
		else if($stmt->num_rows==2)
		{
			$errorMessage[] = "Multiple records found in GetUserID($username). Contact Admin.";
			return -1;
		}
		else
		{
			//$errorMessage[] = "User not found - GetUserID($username)";
			return -1;
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
			WHERE hno = ?";
				
		if (!($stmt = $mysqli->prepare($query))) 
		{
			$errorMessage[] = "ValidCustomer(H#$hNo): Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
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
				$errorMessage[] = "Customer already exists - ValidCustomer(H#$hNo).";
				return false;
			}
		}
		else if($count==2)
		{
			$errorMessage[] = "Multiple Customers found in ValidCustomer(H#$hNo). Contact Admin.";
			return false;
		}
		else
		{
			if($shouldFind)
			{
				$errorMessage[] = "Customer not found - ValidCustomer(H#$hNo).";
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
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $key) || !$stmt->execute()) 
		{
			$errorMessage[] = "Prepare failed: ValidRecord($keyField,$keyName,$key,$table,$shouldExist) (" . $mysqli->errno . ") " . $mysqli->error;
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
				if($reportErrors)$errorMessage[] = "Multiple ".$keyName."s found (ID:$key). Contact Admin";
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
		$query = "SELECT $keyField FROM $table WHERE $filter GROUP BY $keyField";
		
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
	
	function ValidPowerCircuitRecord(&$powerCircuitID, $shouldExist=true, $reportErrors=true)
	{
		global $mysqli;
		global $errorMessage;
		
		$errorMessage[]="ValidPowerCircuitRecord() has been Deprecated() should use ValidRecord() instead";
		return false;
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
		return ValidNumber($input,"Hosting Account #",4,6);
	}
	
	function ValidCNo($input)
	{
		return ValidNumber($input,"Customer #",4,6);
	}
	
	function ValidCustomerName($input)
	{
		return ValidString($input,"Customer Name",3,128);
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
	
	function ValidXYCoordinate($input,$fieldName)
	{
		return ValidNumber($input,$fieldName,1,8,-9999.99,9999.99);
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
	
	function ValidDeviceAltName($input)
	{
		return ValidString($input,"Device Alt Name",0,64);
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
		/*
		$validFlags = array();
		foreach($deviceModels as $device)
		{
			$validFlags[] = $device->name;
		}
		return ValidFlag($input,"Device Model",$validFlags);*/
		return true;
	}
	
	function ValidUserPassword($input)
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
	
	function ValidPowerPanelName($input)
	{
		/*global $errorMessage;
		//this should matcht the JS
		$regex = "/^(\d|\d\d|\d-\d)$/"; 
		if(!preg_match($regex,$input))
		{
			$errorMessage[] = "Invalid Panel.";
			return false;
		}*/
		return ValidString($input,"Power Panel Name",1,50);
	}
	
	function ValidPowerPanelAmps($input)
	{
		return ValidNumber($input,"Power Panel Amps",1,3,0,400);
	}
	
	function ValidPowerPanelCircuits($input)
	{
		return ValidNumber($input,"Power Panel Circuits",1,3,1,126);
	}
	
	function ValidPowerPanelOrientation($input)
	{
		$validFlags = array('N','S','E','W');
		return ValidFlag($input,"Power panel orientation",$validFlags);
	}
	
	function ValidPowerPanelXPos($input)
	{
		return ValidXYCoordinate($input,"Power panel left possition");
	}
	
	function ValidPowerPanelYPos($input)
	{
		return ValidXYCoordinate($input,"Power panel foreward possition");
	}
	
	function ValidPowerPanelWidth($input)
	{
		return ValidNumber($input,"Power panel width",1,5,0,6);
	}
	
	function ValidPowerPanelDepth($input)
	{
		return ValidNumber($input,"Power panel depth",1,5,0,6);
	}
	
	function ValidPowerPanelNote($input)
	{
		return true;
	}
	
	function ValidPowerCircuitNo($input, $max)
	{
		return ValidNumber($input,"Power Circuit",1,3,1,$max);
	}
	
	function ValidPowerCircuitVolts($input)
	{
		$validFlags = array('120','208','308');
		return ValidFlag($input,"Power Circuit Volts",$validFlags);
	}
	
	function ValidPowerCircuitAmps($input)
	{
		$validFlags = array('15','20','30','40','50','60','100');
		return ValidFlag($input,"Power Circuit Amps",$validFlags);
	}
	
	function ValidPowerCircuitStatus($input)
	{
		$validFlags = array('A','D');
		return ValidFlag($input,"Power Circuit Status",$validFlags);
	}
	
	function ValidPowerCircuitLoad($input, $amps)
	{
		return ValidNumber($input,"Power Circuit Load",1,0,0,$amps);
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
	
	function ValidLocationKeyno($input)
	{
		return ValidString($input,"Location alt name",0,20);
	}
	
	function ValidLocationAllocation($input)
	{
		$validFlags = array('E','C','I','M','R');
		return ValidFlag($input,"Location Allocation",$validFlags);
	}
	
	function ValidLocationOrder($input)
	{
		$validFlags = array('N','R');
		return ValidFlag($input,"Location Order",$validFlags);
	}
	
	function ValidLocationOrientation($input)
	{
		$validFlags = array('N','S','E','W');
		return ValidFlag($input,"Location orientation",$validFlags);
	}
	
	function ValidLocationXPos($input)
	{
		return ValidXYCoordinate($input,"Location left possition");
	}
	
	function ValidLocationYPos($input)
	{
		return ValidXYCoordinate($input,"Location foreward possition");
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