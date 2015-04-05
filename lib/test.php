<?php 
	include 'config.php';
	include 'genericFunctions.php';
	include 'helperFunctions.php';
	include 'functions.php';
	
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
	var page = "js/ajax_devicePortSelect.php";
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
$errorMessage = array();


echo "START<BR>";
echo DescribeDBInMarkDown();
echo "<BR>";
echo "Errors:<BR>\n".implode("<BR>\n",$errorMessage);;


 ?>
