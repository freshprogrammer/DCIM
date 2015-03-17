<?php 
	//This is just a set of functions used for gathering and displaying audits. 
	
	set_include_path('../'); 

	$dbChecksPage = true;
	
	include 'dcim/index.php';

	/*
		::need to add::
		Multiple Devices conenctd to a single device port
		device with invalid location
		location without 2x power
		verify location.status if a device is linked
		power without location
		power amps at 0 or negative value
		active device for inactive customer
		&active badge for inactive customer
		port with connection thats not active - or visa versa
		port linked to invalid port - basicly any invalid ports in table
		port linked to more than 1  port- portid in portconnectiontable more than once
		unknown stati in all tables
		log records where first is not I - or missing
		Log records (not "D") missing parent records - parent missing log		
	*/
	
	//this function is inserted into page layout
    function BuildAuditsPage()
    {
        global $pageSubTitle;
        $pageSubTitle = "Data Audits";

        //Data functions
        echo "<div class=\"panel\">\n";
        echo "<div class=\"panel-header\">Data Functions</div>\n";
        echo "<div class=\"panel-body\">\n\n";

        echo "<a href='../dcim/exportBadgesAsCSV.php'>Export Active Badge List as CSV</a>";
        
        echo "</div>\n";//end panel and panel body
        echo "</div>\n\n";
        echo "<BR>\n";
        
        
        
		//data panel - cust info / form / search fail 
		echo "<div class=\"panel\">\n";
		echo "<div class=\"panel-header\">Data Audits</div>\n";
		echo "<div class=\"panel-body\">\n\n";
		
	
    	//echo "Systems Check...</BR></BR>";
    	
    	//Check_CustomersWithoutDevices();
    	//Check_CustomersWithoutBadges();
    	//Check_BadgesWithoutCustomers();
    	//Check_DevicesWithoutCustomers();
    	//Check_DeviceWithInvalidLocation();
    	//Check_SwitchIsMainDeviceOnDevicePortRecords();
    	
		
		//generic stuff
		Check_BadgesToQA();echo "</BR></BR>\n";
		Check_CustomerToQA();echo "</BR></BR>\n";
		Check_ColoPatch0();echo "</BR></BR>\n";
    	Check_VLANLinkedToDisabledPort();echo "</BR></BR>\n";
    	Check_CircuitInactiveWithLoad();echo "\n";
    	
    	echo "</div>\n";//end panel and panel body
		echo "</div>\n\n";
		echo "<BR>\n";
    	
		
		//admin only stuff - just because its stuff they cant fix
	    if(UserHasAdminPermission())
	    {
    		echo "<div class=\"panel\">\n";
    		echo "<div class=\"panel-header\">Admin Data Audits</div>\n";
    		echo "<div class=\"panel-body\">\n\n";
        	
        	$output = "";
        	$recCount = CountDBRecords($output);
        	CreateReport("Database Record Counts","$recCount records",$output,"");echo "</BR></BR>\n";
        	
        	
        	$lineCount = CountLinesInDir($output);
        	CreateReport("Lines of Code","$lineCount lines",$output,"");echo "</BR></BR>\n";
        	
    		Check_BadgesWithoutCustomers();echo "</BR></BR>\n";
    		Check_DevicesWithoutCustomers();echo "\n";
        	
        	echo "</div>\n";//end panel and panel body
    		echo "</div>\n\n";
	    }
    }
    
    function Check_CircuitInactiveWithLoad()
    {
		global $mysqli;

		$reportTitle = "Circuits off but still reporting load";
        $reportNote = "";
        
        //could properly sort circuits, but meh
        $query = "SELECT s.name AS site, l.locationid, l.colo, l.name AS location, p.panel, p.circuit, p.volts, p.amps, p.status, p.cload FROM dcim_power AS p 
                LEFT JOIN dcim_powerloc AS pl ON pl.powerid=p.powerid
                LEFT JOIN dcim_location AS l ON l.locationid=pl.locationid
                LEFT JOIN dcim_site AS s ON l.siteid=s.siteid
            WHERE p.status='D' AND p.cload !=0
            ORDER BY 1,2,3";
		
        if (!($stmt = $mysqli->prepare($query)))
		{
			echo "Prepare failed: Check_CircuitInactiveWithLoad() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			return -1;
		}
				
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($site, $locationID, $colo, $locaiton, $panel, $circuit, $volts, $amps, $status, $cload);
		$count = $stmt->num_rows;
	
		$shortResult = "";
		$longResult = "";
		//data title
		if($count>0)
		{
			//show results
			$longResult.= "<table class='data-table'>\n";
			$longResult.= "<tr>\n";
			//headers
			$longResult.= "<th class='data-table-subheadercell'>Location</th>\n";
			$longResult.= "<th class='data-table-subheadercell'>Panel</th>\n";
			$longResult.= "<th class='data-table-subheadercell'>Circuit</th>\n";
			$longResult.= "<th class='data-table-subheadercell'>Volts</th>\n";
			$longResult.= "<th class='data-table-subheadercell'>Amps</th>\n";
			$longResult.= "<th class='data-table-subheadercell'>Load</th>\n";
			$longResult.= "</tr>\n";
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$longResult.= "<tr class='$rowClass'>";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>$locaiton</a></td>\n";
				$longResult.= "<td class='data-table-cell'>$panel</td>\n";
				$longResult.= "<td class='data-table-cell'>$circuit</td>\n";
				$longResult.= "<td class='data-table-cell'>$volts</td>\n";
				$longResult.= "<td class='data-table-cell'>$amps</td>\n";
				$longResult.= "<td class='data-table-cell'>$cload</td>\n";
				$longResult.= "</tr>";
			}
			$longResult.= "</table>\n";
		    
		    //show results short
			$shortResult.= FormatSimpleMessage("$count Errors",3);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
    }
    
    function Check_VLANLinkedToDisabledPort()
    {
        global $mysqli;
		
        $reportTitle = "VLAN Linked to Disabled Port";
		$reportNote = "These are VLANs linked to ports marked diabled.";
        
        $query = "SELECT dp.deviceid, dp.deviceportid, d.name, d.member, d.model, dp.pic, dp.port, dp.type, dp.status, dp.note, pv.vlan 
        		FROM dcim_portvlan AS pv
                     LEFT JOIN dcim_deviceport AS dp ON pv.deviceportid=dp.deviceportid
                     LEFT JOIN dcim_device AS d on dp.deviceid=d.deviceid
                 WHERE dp.status='D'";
		
        if (!($stmt = $mysqli->prepare($query)))
		{
			echo "Prepare failed: Check_VLANLinkedToDisabledPort() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			return -1;
		}
				
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($deviceID, $devicePortID, $deviceName, $member, $model, $pic, $port, $type, $status, $note, $vlan);
		$count = $stmt->num_rows;
	
		$shortResult = "";
		$longResult = "";
		//data title
		if($count>0)
		{
			//show results
			$longResult.= "<table class='data-table'>\n";
			$longResult.= "<tr>\n";
			//headers
			$longResult.= "<th class='data-table-subheadercell'>Device</th>\n";
			$longResult.= "<th class='data-table-subheadercell'>Port</th>\n";
			$longResult.= "<th class='data-table-subheadercell'>Status</th>\n";
			$longResult.= "<th class='data-table-subheadercell'>Vlan</th>\n";
			$longResult.= "<th class='data-table-subheadercell'>Note</th>\n";
			$longResult.= "</tr>\n";
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$deviceFullName = GetDeviceFullName($deviceName, $model, $member, true);
			    $portFullName = FormatPort($member, $model, $pic, $port, $type);
				
				$longResult.= "<tr class='$rowClass'>";
				$longResult.= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'>$portFullName</td>\n";
				$longResult.= "<td class='data-table-cell'>$status</td>\n";
				$longResult.= "<td class='data-table-cell'>$vlan</td>\n";
				$longResult.= "<td class='data-table-cell'>$note</td>\n";
				$longResult.= "</tr>";
			}
			$longResult.= "</table>\n";
		    
		    //show results short
			$shortResult.= FormatSimpleMessage("$count Errors",3);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
    }
	
	function Check_CustomersWithoutBadges()
	{
		$query = "SELECT c.name AS customer, c.hno, b.name AS name 
		FROM dcim_customer AS c 
			LEFT JOIN dcim_badge AS b ON c.hno=b.hno 
		WHERE b.name IS NULL 
		ORDER BY c.name";
		
		echo "Check_CustomersWithoutBadges() - ";
		
		echo "Found 0 (stub)</BR></BR>";
	}
	
	function Check_CustomersWithoutDevices()
	{
		$query = "SELECT c.name AS customer, c.hno, d.name AS name 
		FROM dcim_customer AS c 
			LEFT JOIN dcim_device AS d ON c.hno=d.hno 
		WHERE d.name IS NULL 
		ORDER BY c.name";
		
		echo "Check_CustomersWithoutDevices() - ";
		
		echo "Found 0 (stub)</BR></BR>";
	}
	
	function Check_BadgesWithoutCustomers()
	{
		global $mysqli;
		
        $reportTitle = "Badges Without Customers";
		$reportNote = "These are orphaned records. Something is bugged or crashed leaving impossible unconnected record(s).";
        
        $query = "SELECT c.name AS cust,b.name,b.badgeno, b.hno 
    		FROM dcim_badge AS b 
                LEFT JOIN dcim_customer AS c ON c.hno=b.hno
            WHERE c.name IS NULL";
		
        if (!($stmt = $mysqli->prepare($query)))
		{
			echo "Prepare failed: Check_BadgesWithoutCustomers() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			return -1;
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($cust, $name, $badgeNo, $hno);
		$count = $stmt->num_rows;
	
		$shortResult = "";
		$longResult = "";
		//data title
		if($count>0)
		{
			//show results
			$longResult.= "<table class='data-table'>\n";
			$longResult.= "<tr>\n";
			//headers
			$longResult.= "<th class='data-table-subheadercell'>H#</th>\n";
			$longResult.= "<th class='data-table-subheadercell'>Name</th>\n";
			$longResult.= "<th class='data-table-subheadercell'>Badgeno</th>\n";
			$longResult.= "</tr>\n";
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$longResult.= "<tr class='$rowClass'>";
				$longResult.= "<td class='data-table-cell'>$hno</td>\n";
				$longResult.= "<td class='data-table-cell'>$name</td>\n";
				$longResult.= "<td class='data-table-cell'>$badgeNo</td>\n";
				$longResult.= "</tr>";
			}
			$longResult.= "</table>\n";
		    
		    //show results short
			$shortResult.= FormatSimpleMessage("$count Errors",3);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_ColoPatch0()
	{
		global $mysqli;
		
        $reportTitle = "Colos with patch 0";
		$reportNote= "These are impossible connections left over from old system.";
        
        //could properly sort circuits, but meh
        $query = "SELECT c.name AS cust, c.hno, s.name AS site, l.locationid, l.colo, l.name AS loc, d.deviceid, d.name, d.member, d.model, d.status, dp.edituser, dp.editdate, dp.qauser, dp.qadate
			FROM dcim_deviceport AS dp
				LEFT JOIN dcim_device AS d ON d.deviceid=dp.deviceid
				LEFT JOIN dcim_customer AS c ON d.hno=c.hno
				LEFT JOIN dcim_location AS l ON l.locationid=d.locationid
				LEFT JOIN dcim_site AS s ON l.siteid=s.siteid
			WHERE d.type IN ('F','C','H') AND dp.port=0
        	ORDER BY cust,name";
        
        if (!($stmt = $mysqli->prepare($query)))
		{
			echo "Prepare failed: Check_ColoPatch0() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			return -1;
		}
				
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($customer, $hNo, $site, $locationID, $colo, $location, $deviceID, $deviceName, $member, $model, $status, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
	
		$shortResult = "";
		$longResult = "";
		//data title
		if($count>0)
		{
			//show results
			$longResult.= "<table class='data-table'>\n";
			$longResult.= "<tr>\n";
			//headers
			$longResult.= "<th class='date-table-subheadercell'>Customer</th>\n";
			$longResult.= "<th class='date-table-subheadercell'>Location</th>\n";
			$longResult.= "<th class='date-table-subheadercell'>Device</th>\n";
			$longResult.= "<th class='date-table-subheadercell'>Status</th>\n";
			$longResult.= "<th class='date-table-subheadercell'>Tech</th>\n";
			$longResult.= "</tr>\n";
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
			
				$deviceFullName = GetDeviceFullName($deviceName, $model, $member, true);
		        $fullLocationName = FormatLocation($site, $colo, $location);
		        	
				$longResult.= "<tr class='$rowClass'>";
				$longResult.= "<td class='data-table-cell'><a href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a></td>";
				$longResult.= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>";
				$longResult.= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>";
				$longResult.= "<td class='data-table-cell'>".DeviceStatus($status,true)."</td>";
				$longResult.= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate,"", $qaUserID, $qaDate)."</td>";
				$longResult.= "</tr>";
			}
			$longResult.= "</table>\n";
		    
		    //show results short
			$shortResult.= FormatSimpleMessage("$count Errors",3);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_BadgesToQA()
	{
		global $mysqli;
		
        $reportTitle = "Badges Pending QA";
		$reportNote = "These badges need to be verified in badge server.";
        
        //could properly sort circuits, but meh
        $query = "SELECT c.name AS cust, b.badgeid, b.hno, b.name, b.badgeno, b.status, b.issue, b.hand, b.returned, b.edituser, b.editdate, b.qauser, b.qadate 
    		FROM dcim_badge AS b 
                LEFT JOIN dcim_customer AS c ON c.hno=b.hno
            WHERE b.qauser=-1
            ORDER BY cust,name";
		
        if (!($stmt = $mysqli->prepare($query)))
		{
			echo "Prepare failed: Check_BadgesToQA() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			return -1;
		}
				
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($customer, $badgeID, $hNo, $name, $badgeNo, $status, $issue, $hand, $returned, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
	
		$shortResult = "";
		$longResult = "";
		if($count>0)
		{
			//show results
			$longResult.= "<table class='data-table'>\n";
			$longResult.= "<tr>\n";
			//headers
			$longResult.= "<th class='date-table-subheadercell'>Customer</th>\n";
			$longResult.= "<th class='date-table-subheadercell'>Name&#x25B2;</th>\n";
			$longResult.= "<th class='date-table-subheadercell'>Badge#</th>\n";
			$longResult.= "<th class='date-table-subheadercell'>Status</th>\n";
			$longResult.= "<th class='date-table-subheadercell'>Issue</th>\n";
			$longResult.= "<th class='date-table-subheadercell'>Enroll</th>\n";
			$longResult.= "<th class='date-table-subheadercell'>Tech</th>\n";
			$longResult.= "</tr>\n";
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$longResult.= "<tr class='$rowClass'>";
				$longResult.= "<td class='data-table-cell'>"."<A href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a>"."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($name)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($badgeNo)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".BadgeStatus($status)."</td>\n";
				$longResult.= "<td class='data-table-cell'>$issue</td>\n";
				$longResult.= "<td class='data-table-cell'>$hand</td>\n";
				$longResult.= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate,"", $qaUserID, $qaDate)."</td>";
				$longResult.= "</tr>";
			}
			$longResult.= "</table>\n";
		    
		    //show results short
			$shortResult.= FormatSimpleMessage("$count Pending",2);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_CustomerToQA()
	{
		global $mysqli;
		
        $reportTitle = "Customers Pending QA";
		$reportNote = "These just  need the name, status, and account numbers of the customer validated.";
        
        //could properly sort circuits, but meh
        $query = "SELECT c.name, c.hno, c.cno, c.note, c.status, c.edituser, c.editdate, c.qauser, c.qadate 
    		FROM dcim_customer AS c
            WHERE c.qauser=-1
            ORDER BY name";
		
        if (!($stmt = $mysqli->prepare($query)))
		{
			echo "Prepare failed: Check_CustomerToQA() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			return -1;
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($name, $hNo, $cNo, $note, $status, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
	
		$shortResult = "";
		$longResult = "";
		if($count>0)
		{
			//show results
			$longResult.= "<table class='data-table'>\n";
			$longResult.= "<tr>\n";
			//headers
			$longResult.= "<th class='date-table-subheadercell'>Customer</th>\n";
			$longResult.= "<th class='date-table-subheadercell'>H#</th>\n";
			$longResult.= "<th class='date-table-subheadercell'>C#</th>\n";
			$longResult.= "<th class='date-table-subheadercell'>Status</th>\n";
			$longResult.= "<th class='date-table-subheadercell'>Note</th>\n";
			$longResult.= "<th class='date-table-subheadercell'>Tech</th>\n";
			$longResult.= "</tr>\n";
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$note = Truncate($note);
				
				$longResult.= "<tr class='$rowClass'>";
				$longResult.= "<td class='data-table-cell'>"."<A href='./?host=$hNo'>".MakeHTMLSafe($name)."</a>"."</td>\n";
				$longResult.= "<td class='data-table-cell'>".$hNo."</td>\n";
				$longResult.= "<td class='data-table-cell'>".$cNo."</td>\n";
				$longResult.= "<td class='data-table-cell'>".CustomerStatus($status)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".MakeHTMLSafe($note)."</td>\n";
				$longResult.= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate,"", $qaUserID, $qaDate)."</td>";
				$longResult.= "</tr>";
			}
			$longResult.= "</table>\n";
		    
		    //show results short
			$shortResult.= FormatSimpleMessage("$count Pending",2);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_DevicesWithoutCustomers()
	{
		global $mysqli;
		
		$reportTitle = "Devices Without Customers";
		$reportNote = "These are orphaned records. Something is bugged or crashed leaving impossible unconnected record(s).";

		$query = "SELECT d.hno, d.deviceid, d.name, d.member, d.model
			FROM dcim_device AS  d
				LEFT JOIN dcim_customer AS c ON c.hno=d.hno
			WHERE c.name IS NULL
			ORDER BY d.name";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			echo "Prepare failed: Check_DevicesWithoutCustomers() - (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			return -1;
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($hno, $deviceID, $deviceName, $member, $model);
		$count = $stmt->num_rows;
		
		$shortResult = "";
		$longResult = "";
		if($count>0)
		{
			//show results
			$longResult.= "<table class='data-table'>\n";
			$longResult.= "<tr>\n";
			//headers
			$longResult.= "<th class='data-table-subheadercell'>H#</th>\n";
			$longResult.= "<th class='data-table-subheadercell'>Device</th>\n";
			$longResult.= "</tr>\n";
				
			//list result data
			$oddRow = false;
			while ($stmt->fetch())
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";

				$deviceFullName = GetDeviceFullName($deviceName, $model, $member, false);
		
				$longResult.= "<tr class='$rowClass'>";
				$longResult.= "<td class='data-table-cell'><a href='./?host=$hno'>".MakeHTMLSafe($hno)."</a></td>\n";
				$longResult.= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>\n";
				$longResult.= "</tr>";
			}
			$longResult.= "</table>\n";
		
			//show results short
			$shortResult.= FormatSimpleMessage("$count Errors",3);
		}
		else
		{
			$shortResult.= FormatSimpleMessage("All Good",1);
		}
		CreateReport($reportTitle,$shortResult,$longResult,$reportNote);
	}
	
	function Check_SwitchIsMainDeviceOnDevicePortRecords()
	{
		echo "Check_SwitchIsMainDeviceOnDevicePortRecords() - ";
		
		echo "Found 0 (stub)</BR></BR>";
	}
	
	function Check_DeviceWithInvalidLocation()
	{
		echo "Check_DeviceWithInvalidLocation() - ";
		/*
		<script>
		var Check_DeviceWithInvalidLocationVisibible=false;
		//ToggleVisibility
		</script>
		<a onclick="if(active){document.getElementById('stuffID').className = 'show'; active=false;}else  {document.getElementById('stuffID').className = 'hide';active = true;}" href="#">Morer/Lesser</a>
		*/
		echo "Found 0 (stub)</BR></BR>";
	}
	
	function Check_MultiplePortConnections()
	{
		/*
	 	//will want the modifed to show counts of child and parent or something
	 	
	 	should be a select from device port into connection then group and count 
	 	
		SELECT count(*) AS count,pc1.* FROM dcim_portconnection AS pc1 GROUP BY pc1.childportid HAVING count>1
UNION
SELECT count(*) AS count,pc2.* FROM dcim_portconnection AS pc2 GROUP BY pc2.parentportid HAVING count>1
		
		 */
	}
?>