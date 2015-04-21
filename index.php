<?php 
	set_include_path('lib/'); 
	
	include 'config.php';
	include 'genericFunctions.php';
	include 'helperFunctions.php';
	include 'functions.php';
	
	UpdateSettingsForiPad();
	SessionSetup();
	SQLIConnect();
	
	//globals
	$resultMessage = array();
	$errorMessage = array();
	$debugMessage = array();
	$redirectAroundFormResubmitWarning = true;
	
	if($redirectAroundFormResubmitWarning)
	{
		//read session message vars and cleaf session vars
		if(isset($_SESSION['resultMessage'])){
			$resultMessage = $_SESSION['resultMessage']; 
			unset($_SESSION['resultMessage']);
		}
		if(isset($_SESSION['errorMessage'])){
			$errorMessage = $_SESSION['errorMessage']; 
			unset($_SESSION['errorMessage']);
		}
		if(isset($_SESSION['debugMessage'])){
			$debugMessage = $_SESSION['debugMessage']; 
			unset($_SESSION['debugMessage']);
		}
	}
	
	//$resultMessage[] = "initialized";
	//$errorMessage[] = "initialized";
	//$debugMessage[] = "initialized";

	global $siteName;
	global $pageTitle;
	global $versionNote;
	
	//varibles definitions
	$siteID = 0;
	//dyanmic
	$pageSubTitle = "";
	$user = "";
	$userID = -1;
	$focusSearch = true;// can be diabled by functions when switching to a form page (like adding a new cust)
	
	$search = GetInput("search");
	$page = GetInput("page");
	$pa_siteid = GetInput("pa_siteid");
	$pa_room = GetInput("pa_room");
	$pa_panel = GetInput("pa_panel");
	$host = GetInput("host");
	$chassisnameInput = GetInput("chassisname");
	$deviceIDInput = GetInput("deviceid");
	$locationIDInput = GetInput("locationid");
	$userIDInput = GetInput("userid");
	$loc = GetInput("loc");
	$row = GetInput("row");
	$ca = GetInput("ca");
	$loginbtn = GetInput("loginbtn");
	$searchbtn = GetInput("searchbtn");


	//see DescribeUserPermissionLevel($p) for permission details
	
	//setup code
	BuildUsersHashTable();
	
	//loging button(s)
	if($loginbtn==="N")
	{
		//clicked log out
		ClearUserLogin();
		//drop to user login
	}
	else if($loginbtn==="Y")
	{
		//clicked log in.... um do nothing special... already on the login page
	}
	
	//this edits cookies which should be done in the header
	$permissionLevel = UpdatePermissionLevel();


	if(UserHasReadPermission())
		BuildDeviceModelArrays();
	 
	if(UserHasWritePermission())
		ProcessFormActions();
	
	//START PAGE CODE **************************************************************************************************
?>
<head>
<script src="lib/js/genericScripts.js"></script>
<script src="lib/js/scripts.js"></script>
<title><?php echo $pageTitle;?></title>
<link rel="icon" type="image/x-icon" href="images/favicon.ico">
<link rel="stylesheet" href="lib/css/default.css">
</head>
<body>
	<!-- Title -->
	<div id="header-bg">
	<table align=center border="0" class='pageMinWidth center'><tbody><tr>
			<td width="1">
				<a href="./"><img src="images/logo.png" border="0"></a>
			</td>
			<td valign="middle">
				<div id="appname"><h1><?php echo $siteName;?></h1><?php echo $versionNote;?></div>
			</td>
			<?php
	if(!UserHasReadPermission())
	{
		//login button
		?>
		  <td width="1" valign="middle">
			<a href="./"><img src="images/login.png" border=0 /></a>
		  </td><?php
	}
	else 
	{
		//show welcome and logout button
		?>
		<td width="1" valign="middle" align=right><h2>Welcome, <?php echo $user; ?>!</h2></td>
		<td width="1" valign="middle">
			<a style="padding-left: 18px;" href="?loginbtn=N"><img src="images/logout.png" border=0 /></a>
		</td><?php
	}
		?>
		</tr></tbody></table>
	</div>
	
	<!--  MENU  -->
	<div id="top-nav">
		<table border="0" align=center cellpadding="0" cellspacing="0" class="dr-toolbar-ext rich-toolbar pageMinWidth center">
		<tbody>
		<tr valign="middle">
	<?php
	$menuItems = "";//consolidate to single echo call to prevent menu flicker on page load - no effect =/
	//MENU Items
	$menuItems .= "<td class='dr-toolbar-int rich-toolbar-item' width='1'>\n";
	$menuItems .= "	<a href='.'>Home</a>\n";
	$menuItems .= "</td>\n";
				
	if(UserHasWritePermission())
	{
		$menuItems .= "<td class='dr-toolbar-int rich-toolbar-item' width='1'>\n";
		$menuItems .= "	<a href='./?host=-1'>Add Customer</a>\n";
		$menuItems .= "</td>\n";
	}
	if(UserHasWritePermission())
	{
		$menuItems .= "<td class='dr-toolbar-int rich-toolbar-item' width='1'>\n";
		$menuItems .= "	<a href='./?page=Audits'>Audits</a>\n";
		$menuItems .= "</td>\n";
	}
	if(UserHasWritePermission())
	{
		
		$menuItems .= "<td class='dr-toolbar-int rich-toolbar-item' width='1'>\n";
		if(UserHasAdminPermission())
			$menuItems .= "	<a href='./?userid=$userID'>Accounts</a>\n";
		else
			$menuItems .= "	<a href='./?userid=$userID'>Account</a>\n";
		$menuItems .= "</td>\n";
	}
	if(UserHasDevPermission())
	{
		//this is only setup for local phpmyadmin access 
		$menuItems .= "<td class='dr-toolbar-int rich-toolbar-item' width='1'>\n";
		$menuItems .= "	<a href='/phpmyadmin/' target='_blank'>PHPMyAdmin</a>\n";
		$menuItems .= "</td>\n";
	}
	
	//search
	if(UserHasReadPermission())
	{
		$placeholder = "";
		//random placeholders
		if(true)
		{
			$linesOfCode = CountLinesInDir();
			$dbRecs = CountDBRecords();
			$rand = rand(0,90);
			$searchPlaceHolders = array();
			//ROUGH LIMIT           "------------------------"
			$searchPlaceHolders[] = "Search";
			$searchPlaceHolders[] = "Search from here";
			$searchPlaceHolders[] = "Everyone loves to search";
			$searchPlaceHolders[] = "Your companion";
			$searchPlaceHolders[] = "Like a pet but useful";
			$searchPlaceHolders[] = "Type here";
			$searchPlaceHolders[] = "Waiting...";
			$searchPlaceHolders[] = "Got your back";
			$searchPlaceHolders[] = "It's cool, I got this";
			$searchPlaceHolders[] = "You know what to do";
			$searchPlaceHolders[] = "Self explanatory";
			$searchPlaceHolders[] = "This is where your search";
			$searchPlaceHolders[] = "See KB for details";
			$searchPlaceHolders[] = "Search is your friend";
			$searchPlaceHolders[] = "Search could save your life some day";
			$searchPlaceHolders[] = "Indexes to the rescue";
			$searchPlaceHolders[] = "Pioneering Omni-Search";
			$searchPlaceHolders[] = "Into the depths";
			$searchPlaceHolders[] = "Almost lunch time";
			$searchPlaceHolders[] = "Who's behind you";
			$searchPlaceHolders[] = "Not on break";
			$searchPlaceHolders[] = "Almost never on break";
			$searchPlaceHolders[] = "Like an unpaid intern";
			$searchPlaceHolders[] = "I am so smart... SMRT";
			$searchPlaceHolders[] = "Your coffee is ready";
			$searchPlaceHolders[] = "Search never sleeps";
			$searchPlaceHolders[] = "Wont save you on car inssurance";
			$searchPlaceHolders[] = "Smoke free for $rand days";
			$searchPlaceHolders[] = "NSA free for 0 days";
			$searchPlaceHolders[] = "Not a calculator";
			$searchPlaceHolders[] = "Still not a calculator";
			$searchPlaceHolders[] = "Sponsored by your tax dollars";
			$searchPlaceHolders[] = "You are here *";
			$searchPlaceHolders[] = "I search to serve";
			$searchPlaceHolders[] = "Give a man a match...";
			$searchPlaceHolders[] = "Not from The Simpsons&reg;";
			$searchPlaceHolders[] = "One man's dream of data";
			$searchPlaceHolders[] = "Pronounced 'kid'";
			$searchPlaceHolders[] = "Please like and subscribe";
			$searchPlaceHolders[] = "Doesn't search Facebook";
			$searchPlaceHolders[] = "No Facebook login";
			$searchPlaceHolders[] = "No like buttons here";
			
			$searchPlaceHolders[] = "$linesOfCode+ lines of code";
			$searchPlaceHolders[] = "$linesOfCode+ free range lines";
			$searchPlaceHolders[] = "$linesOfCode lines, but cutting back";
			$searchPlaceHolders[] = "10K+ line club";
			$searchPlaceHolders[] = "$dbRecs+ DB Records";
			$searchPlaceHolders[] = "$dbRecs Records and counting";
			
			$searchPlaceHolders[] = "Google's nemesis";
			$searchPlaceHolders[] = "Googol * Googol";
			$searchPlaceHolders[] = "Who cares about Bing";
			$searchPlaceHolders[] = "Remember Yahoo!?";
			$searchPlaceHolders[] = "DuckDuckGo";
			$searchPlaceHolders[] = "One DogPile for all";
			$searchPlaceHolders[] = "Making search Cuil again";
			$searchPlaceHolders[] = "Not AltaVista";
			$searchPlaceHolders[] = "Fetch Jeeves";
			$searchPlaceHolders[] = "Fresh Search";
			
			$searchPlaceHolders[] = "e.g. H######";
			$searchPlaceHolders[] = "e.g. C######";
			$searchPlaceHolders[] = "e.g. Company name";
			$searchPlaceHolders[] = "e.g. Company note";
			$searchPlaceHolders[] = "e.g. Device Name";
			$searchPlaceHolders[] = "e.g. Badge Holder";
			$searchPlaceHolders[] = "e.g. Location Name";
			$placeholder = $searchPlaceHolders[array_rand($searchPlaceHolders)];
		}

		$menuItems .= "<td class='dr-toolbar-int rich-toolbar-item'  align='right''>\n";
		$menuItems .= "	<form name='MainSearch' action='./' method=get>\n";
		$menuItems .= "		Search: <input type='text' name='search' placeholder=\"$placeholder\">\n";
		$menuItems .= "	<input type='hidden' name='searchbtn' value='T'>\n";
		$menuItems .= "	</form>\n";
		$menuItems .= "</td>\n";
	}
	else 
	{
		//keep format and left seperator
		$menuItems .= "<td class='dr-toolbar-int rich-toolbar-item'>&nbsp;</td>\n";
	}
	echo $menuItems;
	?>
		</tr>
		</tbody>
		</table>
	</div>
	
	<!-- Page Body -->
	<table id="pagecontainer" id="pagecontainer" cellpadding="0" cellspacing="0" class='center pageMinWidth'><tbody><tr><td>
		
	<?php
	
	//BackupDatabase();
	
	/* test code
	$input = "test";  $debugMessage[] = "validDeviceSize '$input'  R-".ValidDeviceSize($input)." '$input'";
	$input = "2x2";   $debugMessage[] = "validDeviceSize '$input'  R-".ValidDeviceSize($input)." '$input'";
		*/
	
	if(UserHasReadPermission())
	{
		?>
		<!-- HEADER LINKS -->
		<table width=100%><tr>
			<td>
				<a class='navLinks' href='?ca=01'>CA1</a>&nbsp;
				<a class='navLinks' href='?ca=02'>CA2</a>&nbsp;
				<a class='navLinks' href='?ca=03'>CA3</a>&nbsp;
				<a class='navLinks' href='?ca=04'>CA4</a>&nbsp;
				<a class='navLinks' href='?ca=05'>CA5</a>&nbsp;
				&nbsp;&nbsp;
				<div class="navLinks">ROW:</div> 
				<a class='navLinks' href='?row=01'>1</a>&nbsp;
				<a class='navLinks' href='?row=02'>2</a>&nbsp;
				<a class='navLinks' href='?row=03'>3</a>&nbsp;
				<a class='navLinks' href='?row=04'>4</a>&nbsp;
				<a class='navLinks' href='?row=05'>5</a>&nbsp;
				<a class='navLinks' href='?row=06'>6</a>&nbsp;
				<a class='navLinks' href='?row=07'>7</a>&nbsp;
				<a class='navLinks' href='?row=08'>8</a>&nbsp;
				<a class='navLinks' href='?row=09'>9</a>&nbsp;
				<a class='navLinks' href='?row=10'>10</a>&nbsp;
				<a class='navLinks' href='?row=11'>11</a>&nbsp;
				<a class='navLinks' href='?row=12'>12</a>&nbsp;
			</td>
			<td align='right'>
				<a href='#' class='' id='showMessagesButton' onclick='ToggleMessgeVisibility()'>Show Messages</a>&nbsp;
			</td>
		</tr></table>
		<BR>
		<?php
	}
		
	//trim mesages
	$debugMessageString  = implode("<BR>\n",$debugMessage);
	$errorMessageString  = implode("<BR>\n",$errorMessage);
	$resultMessageString = implode("<BR>\n",$resultMessage);
	if(strlen($debugMessageString) > 0) echo "<!-- DEBUG MESSAGE  -->\n<div id='debugMessage'  class='debugMessage'>$debugMessageString</div>\n";
	if(strlen($errorMessageString) > 0) echo "<!-- ERROR MESSAGE  -->\n<div id='errorMessage'  class='errorMessage'>$errorMessageString</div>\n";
	if(strlen($resultMessageString) > 0)echo "<!-- RESULT MESSAGE -->\n<div id='resultMessage' class='resultMessage'>$resultMessageString</div>\n";
		
	if(!UserHasReadPermission())
	{
		echo "<!--  LOGIN PROMPT  -->\n";
		echo "<BR>\n";
		LoginPrompt();
	}
	else 
	{
		//officaliy logged in with min read access-------------------------------------------------------------------------------------------------
		echo "<!--  PAGE BODY-->\n";
		
		if(strlen($host) > 0)
		{
			//build customer page
			ShowCustomerPage($host, $siteID);
			
		}
		else if(strlen($ca) > 0 || strlen($row) > 0)
		{
			//show all customer at given locations - IE all customers in a location range, like row X or CA X
			//--these vars are from the header links - should be formatted propperly
			ListLocationCustomers($siteID, $ca, $row);
		}
		else if(strlen($deviceIDInput) > 0)
		{
			ShowDevicePage($deviceIDInput);
		}
		else if(strlen($chassisnameInput) > 0)
		{
			ShowChassisPage($chassisnameInput);
		}
		else if(strlen($locationIDInput) > 0)
		{
			ShowLocationPage($locationIDInput);
		}
		else if(strlen($userIDInput) > 0)
		{
			ShowUsersPage($userIDInput);
		}
		else if(strlen($page) > 0)
		{
			if($page==="PowerAudit")
			{
				if(strlen($pa_siteid) > 0 && strlen($pa_room) > 0 && strlen($pa_panel) > 0)
				{
					PowerAuditPanel($pa_siteid,$pa_room,$pa_panel);
				}
				else
				{
					PowerAuditPanelList();
				}
			}
			else if($page==="Audits")
			{
				include 'audits.php';
				BuildAuditsPage();
			}
		}
		else
		{
			//quick check to see if this is a company name or hno to sckip strait to customer page
			$singleCustomerMatch = TestForSingleCustomerMatch($search);
			if($singleCustomerMatch!=false)
			{
				//single customer
				ShowCustomerPage($singleCustomerMatch, $siteID);
			}
			else 
			{
				//search all	
				$resultCount = 0;
				if(strlen($search) > 0)
				{
					$pageSubTitle = "Search:\"".MakeHTMLSafe($search)."\"";
					echo "<div class=\"panel\">\n";
					echo "<div class=\"panel-header\">\n";
					echo "Search for \"".MakeHTMLSafe($search)."\" yields:\n";
					echo "</div>\n";
					
					echo "<div class=\"panel-body\">\n\n";
					
					//search in customer (hno, cno, name, note)
					$resultCount += ListSearchCustomers($search);
					echo "<BR>\n";
					
					//search in badge (name, badgeno)
					$resultCount += ListBadges(true,$search);
					echo "<BR>\n";
					
					//search in device (name, note)
					$resultCount += ListDevices(true,$search);
					
					echo "</div>\n";
					echo "</div>\n";
				}
				else 
				{
					if($searchbtn==="T")
					{
						//mock for empty search
						$pageSubTitle = "Blank Search";
						echo "<div class=\"panel\">\n";
						echo "<div class=\"panel-header\">\n";
						echo "Search for nuthin ($search) yields a whole lot of nuthin.\n";
						echo "</div>\n";
						
						echo "<div class=\"panel-body\">\n\n";
						
						//nothing here
						
						echo "</div>\n";
						echo "</div>\n";
					}
					else 
					{
						//just logged in
						echo "<div class=\"panel\">\n";
						echo "<div class=\"panel-header\">\n";
						echo "Welcome\n";
						echo "</div>\n";
						
						echo "<div class=\"panel-body\">\n\n";
						
						echo "Welcome to $siteName.";
						
						if(UserHasWritePermission() && IsUserUsingDefaultPassword())
						{
							echo "<BR><BR>Please <a href='./?userid=$userID'>change your password</a> from the default when you get a chance.";
						}
						
						echo "</div>\n";
						echo "</div>\n";
					}
				}//end search len > 0
			}//single cust not found
		}//not specific row/ca/cust
		echo "<BR>";
	}//end - page body vs login - //officaliy logged in with min read access
	
	//end of Body
	SQLIDisconnect();
?>
	</td></tr></tbody></table>
	<div id='outOfFocusOverlay' class='outOfFocusOverlay' onclick = 'ClearActivePopup();'></div>
</body>

<footer>
<script type="text/javascript" language="JavaScript">
	<?php 
		//move focus as necisary
		echo "InitializePage();";

		if($focusSearch)
			echo "FocusMainSearch();\n";
		//update title if necisarry
		if(strlen($pageSubTitle) > 0)
		{
			$pageTitle = $pageTitle." - ".$pageSubTitle;
			echo "document.title = '$pageTitle';\n";
		}
	?>
</script>

</footer>
</html>