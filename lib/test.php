<?php
	require_once 'DCIMCustomFunctions.php';
	require_once 'config.php';
	require_once 'customFunctions.php';
	require_once 'genericFunctions.php';
	require_once 'helperFunctions.php';
	require_once 'dataFunctions.php';
	require_once 'htmlFunctions.php';
	
	SQLIConnect();
	
	global $config_appName;
	
	?>
<head><title>Test <?php echo $config_appName;?></title></head>
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
