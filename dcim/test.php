<?php 
	set_include_path('../'); 
	
	include 'dcim/_functions.php';

	?>
<head><title>Test DCIM</title></head>

<body>

Test page<BR>
<BR>
TestAjax - vlan authentication<BR>
VLAN:<input id=input1 type=text onchange="ValidateVlan"><BR> 
<input id=input2 type=text> <BR>

<script type="text/javascript" language="JavaScript">
function ValidateVlan()
{
	var page = "ajax_devicePortSelect.php";
	var data = 'deviceid='+document.getElementById(deviceSelect).value +
	'&selectedportid='+selectedPortID;
	var dest = portSelect;
	AJAXCall(page,data,dest);
	
	return false;
}
</script>

</body>

<?php 

 //test php

 ?>
