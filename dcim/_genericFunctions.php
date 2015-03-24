<?php 

	function SessionSetup()
	{
		//http://spotlesswebdesign.com/blog.php?id=11
		global $_SESSION;
		// start session 
		session_start(); 

		// set page instance id 
		if (!isset($_SESSION['page_instance_ids'])) { 
			$_SESSION['page_instance_ids'] = array(); 
		} 
		$_SESSION['page_instance_ids'][] = uniqid('', true); 
	}
	
	function IsValidSession()
	{
	    //TODO note this throws an error if page_instance_id is not found
		//returns true if this is a valid session - IE this is a fresh submit and not a refresh
		
		global $_SESSION;
		
		$page_id_index = array_search($_POST['page_instance_id'], $_SESSION['page_instance_ids']); 
		if ($page_id_index !== false) { 
			unset($_SESSION['page_instance_ids'][$page_id_index]); 
			// do form processing 
			return true;
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
		$result = str_replace(chr(10),"\\n", $result);// needs to be "\n" in javascript - otherwise the return characters will be break in the src and break the javascript cod
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
    {
        // get the record          
        $selectSQL = "SELECT * FROM `" . $table . "` WHERE `id` = " . $id . ';';
    
        $result = mysql_query($selectSQL, $YourDbHandle);
        $row = mysql_fetch_assoc($result); 
    
        $insertSQL = "INSERT INTO `" . $table . "` SET ";
        foreach ($row as $field => $value) {
            $insertSQL .= " `" . $field . "` = '" . $value . "', ";
        }
        $insertSQL = trim($insertSQL, ", ");
    
        return $insertSQL;
    }
	
	function GetInput($name)
	{
		if(isset($_POST[$name]))
		{
			$input = $_POST[$name];
		}
		else if(isset($_GET[$name]))
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
		{
			//limit hit!
			$string = substr($string,0,($length -3));
			if ($stopanywhere) 
			{
				//stop anywhere
				$string .= '...';
			} else
			{
				//stop on a word.
				$string = substr($string,0,strrpos($string,' ')).'...';
			}
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
            while(!feof($handle)){
              $line = fgets($handle);
              $linecount++;
            }
            
            fclose($handle);
        }
        else
            return "N/A-$file";
            
        return $linecount;
	}
	
	function CountLinesInDir(&$verboseResult = "")
	{
	    $verboseResult = "";
	    $dir = scandir('.'); 
        $totalLines = 0;
        foreach ($dir as $file) 
        { 
            if (!(strpos($file,'.sql') !== false)) 
            {
                $count = CountLinesInFile($file);
                $totalLines += $count;
                $verboseResult .= "file:$file - $count lines - total=$totalLines<BR>";
            }
        }
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

?>