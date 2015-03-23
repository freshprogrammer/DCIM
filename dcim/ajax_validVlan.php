<?php 
	set_include_path('../'); 

	include 'dcim/_dcimConfig.php';
	include 'dcim/_genericFunctions.php';
	include 'dcim/_helperFunctions.php';
	include 'dcim/_functions.php';

	function vlanFilter($v)
	{
		//return true for vlans to keep
		return $v!=0;
	}
	
	//startup
	SQLIConnect();
	BuildDeviceModelArrays();
	
	$dev = GetInput("dev");
	$dev = $dev==="true";
	
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
        foreach ($vlans as $v) 
        {
        	echo "Dev line 3- vlans[] = $v<BR>";
        }
    	echo "Dev line 4 vlanCount = $vlanCount<BR>";
	}
	$clause = implode(',', array_fill(0, count($vlans), '?'));

	$arrayOfResults = array();
	$array_of_params = array();
	$array_of_params[0] = "";
    foreach($vlans as $value){
        $array_of_params[0] .= 'i';
        $array_of_params[] = $value;
        $arrayOfResults[$value] = false;
    }



	
	global $mysqli;
	
	$query = "SELECT vlan FROM dcim_vlan 
				WHERE vlan IN ($clause) 
				GROUP BY vlan";
	
	if (!($stmt = $mysqli->prepare($query))) 
	{
		//TODO handle errors better
		echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "</BR>";
	}
	if($dev) echo "Dev line 5<BR>\n";
	            
	//$stmt->bind_Param('s', $vlanSQLFilter);
	call_user_func_array(array(&$stmt, 'bind_param'), $array_of_params);
	$stmt->execute();
	$stmt->store_result();
	$stmt->bind_result($vlan);
	$count = $stmt->num_rows;
	
	if($dev) echo "Dev line 6. query = '$query'<BR>\n";
	if($dev) echo "Dev line 7. count = $count<BR>";
	if($count>0)
	{
		if($count==$vlanCount)
			echo "valid";
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
            	{
            		//found
            		if($dev)echo "arrayOfResults[$v] = ".$arrayOfResults[$v]."<BR>";
            	}
            	else
            	{
            		//not found
            		echo "VLAN $v was not found. ";
            	}
            }
		}
	}
	
	SQLIDisconnect();
?>
