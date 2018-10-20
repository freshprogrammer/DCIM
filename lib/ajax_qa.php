<?php
	require_once 'DCIMCustomFunctions.php';
	require_once 'config.php';
	require_once 'customFunctions.php';
	require_once 'genericFunctions.php';
	require_once 'helperFunctions.php';
	require_once 'dataFunctions.php';
	require_once 'htmlFunctions.php';
	
	//startup
	SQLIConnect();
	
	$dev = GetInput("dev");
	$dev = $dev==="true";
	
	$table = GetInput("table");
	$key = GetInput("id");
	
	if($dev) echo "Dev line 2 table = $table<BR>";
	if($dev) echo "Dev line 3 key = $key<BR>";
	
	$result = QARecord($table,$key);
	if($dev)
	{
		global $errorMessage;
		global $resultMessage;
		echo "<br><br>errors:<br>";
		echo implode("<br>",$errorMessage);
		echo "<br><br>results:<br>";
		echo implode("<br>",$resultMessage);
	}
	
	if($result==1) echo "<font color=green>Success</font>";
	else if($result==0) echo "<font color=yellow>Successfully QAed - but $result affected</font>";
	else echo "<font color=red>Failed to QA</font>";
	
	SQLIDisconnect();
?>
