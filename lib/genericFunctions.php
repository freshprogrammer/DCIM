<?php
	function SQLIConnect()
	{
		global $mysqli;
		global $db_host;
		global $database;
		global $db_user;
		global $db_password;
		
		$mysqli = new mysqli($db_host, $db_user, $db_password, $database);
		
		/* check connection */
		if (mysqli_connect_errno()) {
			printf("Connect failed: %s\n", mysqli_connect_error());
			exit();
		}
	}
	
	function SQLIConnect_Admin()
	{
		global $mysqli;
		global $db_host;
		global $database;
		global $db_admin_user;
		global $db_admin_password;
		
		$mysqli = new mysqli($db_host, $db_admin_user, $db_admin_password, $database);
		
		/* check connection */
		if (mysqli_connect_errno()) {
			printf("Connect failed: %s\n", mysqli_connect_error());
			exit();
		}
	}
	
	function SQLIDisconnect()
	{
		global $mysqli;
		
		$mysqli->close();
	}
	
	function SessionSetup()
	{
		//http://spotlesswebdesign.com/blog.php?id=11
		global $_SESSION;
		// start session
		session_start();
		
		// set page instance id 
		if (!isset($_SESSION['page_instance_ids']))
		{
			$_SESSION['page_instance_ids'] = array();
		}
		$_SESSION['page_instance_ids'][] = uniqid('', true);
	}
	
	function IsValidSession()
	{//returns true if this is a valid session - IE this is a fresh submit and not a refresh because a valid page_instance_id was submitted with the last form and it has not been used and removed
		global $_SESSION;
		
		if (isset($_POST['page_instance_id']) && isset($_SESSION['page_instance_ids']))
		{
			$page_id_index = array_search($_POST['page_instance_id'], $_SESSION['page_instance_ids']);
			if ($page_id_index !== false) {
				unset($_SESSION['page_instance_ids'][$page_id_index]);
				// do form processing
				return true;
			}
		}
		return false;
	}
	
	function HTMLTrim($input)
	{
		$prevLen = 0;
		while($prevLen < strlen($input))
		{
			$prevLen = strlen($input);
			
			$input = trim($input);
			
			//trim last br
			$input = preg_replace('/(<BR>)+$/', '', $input);
			$input = preg_replace('/(<br>)+$/', '', $input);
			$input = preg_replace('/(<//BR>)+$/', '', $input);
			$input = preg_replace('/(<//br>)+$/', '', $input);
			$input = preg_replace('/(<BR>//)+$/', '', $input);
			$input = preg_replace('/(<br//>)+$/', '', $input);
		}
		return $input;
	}
	
	function MakeJSSafeParam($input)
	{
		$result = str_replace("\\","\\\\", $input);// \ -> \\
		$result = str_replace(chr(10),"\\n", $result);// needs to be "\n" in javascript - otherwise the return characters will be break in the src and break the javascript code
		$result = str_replace(chr(13),"", $result);//everything support just the \n
		$result = str_replace("\"","&quot;", $result);// " -> &quot;
		$result = str_replace("'","\'", $result);// ' -> &apos;
		return $result;
	}
	
	$uniqueIDNo = 0;
	function MakeTextIntoUniqueJSVariableName($input)
	{
		global $uniqueIDNo;
		$uniqueIDNo++;
		
		$result = str_replace("<","", $input);
		$result = str_replace(">","", $result);
		$result = str_replace("\\","", $result);
		$result = str_replace("/","", $result);
		$result = str_replace(".","", $result);
		$result = str_replace(";","", $result);
		$result = str_replace(",","", $result);
		$result = str_replace("'","", $result);
		$result = str_replace("\"","", $result);
		$result = str_replace("%","", $result);
		$result = str_replace("(","", $result);
		$result = str_replace(")","", $result);
		$result = str_replace(" ","_", $result);
		return $result.$uniqueIDNo;
	}
	
	function MakeHTMLSafe($input)
	{
		$result = str_replace("<","&lt;", $input);
		$result = str_replace(">","&gt;", $result);
		return $result;
	}
	
	function MakeRecoverySQLInsert($table, $id)
	{// get the record
		$selectSQL = "SELECT * FROM `" . $table . "` WHERE `id` = " . $id . ';';
		
		$result = mysql_query($selectSQL, $YourDbHandle);
		$row = mysql_fetch_assoc($result);
		
		$insertSQL = "INSERT INTO `" . $table . "` SET ";
		foreach ($row as $field => $value)
			$insertSQL .= " `" . $field . "` = '" . $value . "', ";
		$insertSQL = trim($insertSQL, ", ");
		
		return $insertSQL;
	}
	
	function GetInput($name, $checkPost=true, $checkGet=true)
	{
		if($checkPost && isset($_POST[$name]))
		{
			$input = $_POST[$name];
		}
		else if($checkGet && isset($_GET[$name]))
		{
			$input = $_GET[$name];
		}
		else
		{
			$input = "";
		}
		return trim($input);
	}
	
	function Truncate($string, $length=50, $stopanywhere=false) 
	{
		//truncates a string to a certain char length, stopping on a word if not specified otherwise.
		if (strlen($string) > $length) 
		{//limit hit!
			$string = substr($string,0,($length -3));
			if ($stopanywhere)//stop anywhere
				$string .= '...';
			else //stop on a word
				$string = substr($string,0,strrpos($string,' ')).'...';
		}
		return $string;
	}
	
	function TruncateWithSpanTitle($string, $length=50, $stopanywhere=false) 
	{
		//truncates a string to a certain char length, stopping on a word if not specified otherwise.
		$original = $string;
		$string = Truncate($string,$length,$stopanywhere);
		$string ="<span title='$original'>$string</span>";
		return $string;
	}
	
	function CountLinesInFile($file)
	{
		$linecount = 0;
		if (file_exists($file))
		{
			$handle = fopen($file, "r");
			if($handle!==false)
			{
				while(!feof($handle))
				{
					$line = fgets($handle);
					$linecount++;
				}
			}
			fclose($handle);
		}
		else
			return "N/A-'$file'";
			
		return $linecount;
	}
	
	function CountLinesInDir(&$verboseResult = "", $srcDir="./")
	{
		$debug = false;
		$dir = scandir($srcDir); 
		$totalLines = 0;
		if($debug)echo "CountLinesInDir('$srcDir') - START<BR>";
		foreach ($dir as $file) 
		{
			$file = $srcDir.$file;
			//echo "CountLinesInDir('$srcDir') - stepping - '$file'<BR>";
			if (substr($file, -4)==".git")
			{
				if($debug)echo "CountLinesInDir('$srcDir') - skippedE1 - '$file'<BR>";
				continue;
			}
			if (substr($file, -1)==".")//end in '.'
			{
				if($debug)echo "CountLinesInDir('$srcDir') - skippedE2 - '$file'<BR>";
				continue;
			}
			if (substr($file, -10)=="phpmyadmin")//end in 'phpmyadmin'
			{
				if($debug)echo "CountLinesInDir('$srcDir') - skippedE3 - '$file'<BR>";
				continue;
			}
			if (is_dir($file))//recursive
			{
				if($debug)echo "CountLinesInDir('$srcDir') - counting folder - '$file'<BR>";
				$totalLines += CountLinesInDir($verboseResult,$file."/");
			}
			else
			{
				$count = false;
				if (substr($file, -4)==".php") $count = true;
				else if (substr($file, -4)==".css") $count = true;
				else if (substr($file, -4)==".txt") $count = true;
				else if (substr($file, -3)==".md") $count = true;
				else if (substr($file, -3)==".js") $count = true;
				else if (substr($file, -7)==".sample") $count = true;
				
				if($count)
				{
					$count = CountLinesInFile($file);
					$totalLines += $count;
					$verboseResult .= "file:$file - $count lines - total=$totalLines<BR>";
				}
			}
		}
		$verboseResult .= "Grand total in '$srcDir':$totalLines<BR>";
		return $totalLines;
	}
	
	function CountDBRecords(&$verboseResult = "")
	{//show count for all tables in DB
		global $mysqli;
		global $errorMessage;
		
		$verboseResult = "";
		$grandTotal = 0;
		
		$query = "SHOW TABLES";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = $errorMessage."ShowDBCounts() - Prepare 1 failed: ($query) (" . $mysqli->errno . ") " . $mysqli->error . "</BR>";
		}
		else
		{
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($table);
			
			while ($stmt->fetch()) 
			{
				$query2 = "SELECT COUNT(*) FROM $table";
					
				if (!($stmt2 = $mysqli->prepare($query2)))
				{
					$errorMessage[] = $errorMessage."ShowDBCounts() - Prepare 2 failed: ($query2) (" . $mysqli->errno . ") " . $mysqli->error . "</BR>";
				}
				else
				{
					$stmt2->execute();
					$stmt2->store_result();
					//$count = $stmt2->num_rows; //this is a count() this will always be 1
					$stmt2->bind_result($count);
					$stmt2->fetch();
					$grandTotal += $count;
					
					$verboseResult .= "--$table - $count records.<BR>";
				}
			}
			$verboseResult .= "-DB total: - $grandTotal records.</BR>\n\n\n";
		}
		return $grandTotal;
	}
	
	function CreateTableRowCountTable(&$grandTotal=0)
	{//show count for all tables in DB
		global $mysqli;
		global $errorMessage;
		
		$result = "";
		$grandTotal = 0;
		
		$query = "SHOW TABLES";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = $errorMessage."ShowDBCounts() - Prepare 1 failed: ($query) (" . $mysqli->errno . ") " . $mysqli->error . "</BR>";
			$result = "SQL Failed - CountDBRowsTable()";
		}
		else
		{
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($table);
			
			$cellStyle = "style='border:1px solid black; border-collapse:collapse; padding:2px;'";
			$result .= "<table $cellStyle>\n";
			$result .= "<tr>
			<th $cellStyle>Table</th>
			<th $cellStyle>Rows</th>
			</tr>\n";
			
			while ($stmt->fetch()) 
			{
				$query2 = "SELECT COUNT(*) FROM $table";
					
				if (!($stmt2 = $mysqli->prepare($query2)))
				{
					$errorMessage[] = $errorMessage."ShowDBCounts() - Prepare 2 failed: ($query2) (" . $mysqli->errno . ") " . $mysqli->error . "</BR>";
					$result .= "<tr><td colspan=2>SQL Error with table $table - CountDBRowsTable()</td></tr>\n";
				}
				else
				{
					$stmt2->execute();
					$stmt2->store_result();
					//$count = $stmt2->num_rows; //this is a count() this will always be 1
					$stmt2->bind_result($count);
					$stmt2->fetch();
					$grandTotal += $count;
					
					$result .= "<tr>
					<td $cellStyle>$table</td>
					<td $cellStyle>$count</td>
					</tr>\n";
				}
			}
			$result .= "<tr>
			<td $cellStyle><b>Total</b></td>
			<td $cellStyle><b>$grandTotal</b</td>
			</tr>\n
			</table><BR>\n";
		}
		return $result;
	}
	
	function DescribeDBInMarkDown()
	{//show count for all tables in DB
		global $mysqli;
		global $errorMessage;
		
		$result = "";
		
		$query = "SHOW TABLES";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = $errorMessage."DescribeDBInMarkDown() - Prepare 1 failed: ($query) (" . $mysqli->errno . ") " . $mysqli->error . "</BR>";
		}
		else
		{
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($table);
			
			while ($stmt->fetch())
			{
				$query2 = "Describe  $table";
					
				if (!($stmt2 = $mysqli->prepare($query2)))
				{
					$errorMessage[] = $errorMessage."DescribeDBInMarkDown() - Prepare 2 failed: ($query2) (" . $mysqli->errno . ") " . $mysqli->error . "</BR>";
				}
				else
				{
					$result .= "#### Describe $table\n";
					$result .= "|Field|Type|Null|Key|Default|Extra|\n";
					$result .= "|---|---|---|---|---|---|\n";
					
					$stmt2->execute();
					$stmt2->store_result();
					$stmt2->bind_result($field,$type,$null,$key,$default,$extra);
					
					while ($stmt2->fetch())
					{
						$result .= "|$field|$type|$null|$key|".(is_null($default)?"*NULL*":$default)."|$extra|\n";
					}
					$result .= "\n";
				}
			}
		}
		return $result;
	}
	
	function DescribeDBInTables()
	{//show count for all tables in DB
		global $mysqli;
		global $errorMessage;
		
		$result = "";
		
		$query = "SHOW TABLES";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = $errorMessage."DescribeDBInTables() - Prepare 1 failed: ($query) (" . $mysqli->errno . ") " . $mysqli->error . "</BR>";
		}
		else
		{
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($table);
			
			while ($stmt->fetch())
			{
				$query2 = "Describe  $table";
					
				if (!($stmt2 = $mysqli->prepare($query2)))
				{
					$errorMessage[] = $errorMessage."DescribeDBInTables() - Prepare 2 failed: ($query2) (" . $mysqli->errno . ") " . $mysqli->error . "</BR>";
				}
				else
				{
					$cellStyle = "style='border:1px solid black; border-collapse:collapse; padding:2px;'";
					$result .= "<B> Describe $table</B><BR>\n";
					$result .= "<table $cellStyle>\n";
					$result .= "<tr>
							<th $cellStyle>Field</th>
							<th $cellStyle>Type</th>
							<th $cellStyle>Null</th>
							<th $cellStyle>Key</th>
							<th $cellStyle>Default</th>
							<th $cellStyle>Extra</th>
							</tr>\n";
					
					$stmt2->execute();
					$stmt2->store_result();
					$stmt2->bind_result($field,$type,$null,$key,$default,$extra);
					
					while ($stmt2->fetch())
					{
						$result .= "<tr>
							<td $cellStyle>$field</td>
							<td $cellStyle>$type</td>
							<td $cellStyle>$null</td>
							<td $cellStyle>$key</td>
							<td $cellStyle>".(is_null($default)?"<i>NULL</i>":$default)."</td>
							<td $cellStyle>$extra</td>
							</tr>\n";
					}
					$result .= "</table><BR>\n";
				}
			}
		}
		return $result;
	}
	
	function OutputCSV($fileName,$data) 
	{
		header("Content-Type: text/csv");
		header("Content-Disposition: attachment; filename=$fileName");
		// Disable caching
		header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
		header("Pragma: no-cache"); // HTTP 1.0
		header("Expires: 0"); // Proxies
		
		$output = fopen("php://output", "w");
		foreach ($data as $row)
		{
			fputcsv($output, $row); // here you can change delimiter/enclosure
		}
		fclose($output);
	}
	
	function SQLFileToCmdArray($fileName)
	{
		//kept very simple for a purpose
		//scan file deleting all blank lines or lines that start with "--" or "/*"
		$result = array();
		
		if (file_exists($fileName))
		{
			$handle = fopen($fileName, "r");
			if($handle!==false)
			{
				$cmd = "";
				while(!feof($handle))
				{
					$line = fgets($handle);
					if(substr($line,0,2)=="--")
						continue;
					if(substr($line,0,2)=="/*")
						continue;
					$cmd = trim($cmd." ".$line);
					if(substr($cmd,-1)==";")
					{
						$cmd = substr($cmd, 0, -1);//trim semicolon
						$result[]=$cmd;
						$cmd = "";
					}
				}
			}	
			fclose($handle);
		}
		return $result;
	}
	
	function DoesTableExist($tableName)
	{
		global $mysqli;
		global $errorMessage;
		
		$query = "SHOW TABLES LIKE '$tableName'";
		
		$result = false;
		if (!($stmt = $mysqli->prepare($query)))
			$errorMessage[] = "DoesTableExist($tableName)-Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
		else
		{
			if(!$stmt->execute())
				$errorMessage[] = "DoesTableExist($tableName)-Error executing($query).";
			else
			{
				$stmt->store_result();
				$count = $stmt->num_rows;
				if($count==1)
					$result = true;
				/* //dont report errors
				else if($count>1)
					$errorMessage[] = "DoesTableExist($tableName)-Error:Multiple tables found.";
				else
					$errorMessage[] = "DoesTableExist($tableName)-Error:Table not found.";
				*/
			}
			$stmt->close();
		}
		return $result;
	}
	
	function DoesFieldExist($tableName, $fieldName)
	{
		global $mysqli;
		global $errorMessage;
		
		$query = "SHOW COLUMNS FROM `$tableName` LIKE '$fieldName'";
		
		$result = false;
		if (!($stmt = $mysqli->prepare($query)))
			$errorMessage[] = "DoesFieldExist($tableName,$fieldName)-Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
		else
		{
			if(!$stmt->execute())
				$errorMessage[] = "DoesFieldExist($tableName,$fieldName)-Error executing($query).";
			else
			{
				$stmt->store_result();
				$count = $stmt->num_rows;
				if($count==1)
					$result = true;
				/* //dont report errors
				else if($count>1)
					$errorMessage[] = "DoesTableExist($tableName)-Error:Multiple fields found.";
				else
					$errorMessage[] = "DoesTableExist($tableName)-Error:Field not found.";
				*/
			}
			$stmt->close();
		}
		return $result;
	}
?>