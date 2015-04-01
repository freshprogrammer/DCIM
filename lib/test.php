<?php 
	set_include_path('../'); 

	include 'dcim/_dcimConfig.php';
	include 'dcim/_genericFunctions.php';
	include 'dcim/_helperFunctions.php';
	include 'dcim/_functions.php';

	SQLIConnect();
	?>
<head><title>Test DCIM</title></head>
<!--
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
</script> -->

</body>

<?php 

 //test php
global $errorMessage;
 
echo DescribeDBInMarkDown();
echo "<BR>";
echo "Errors:<BR>\n".implode("<BR>\n",$errorMessage);;


 ?>
