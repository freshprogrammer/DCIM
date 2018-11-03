<?php
	set_include_path('lib/'); 
	
	require_once 'DCIMCustomFunctions.php';
	require_once 'config.php';
	require_once 'customFunctions.php';
	require_once 'genericFunctions.php';
	require_once 'helperFunctions.php';
	require_once 'dataFunctions.php';
	require_once 'htmlFunctions.php';
	
	//globals
	$resultMessage = array();
	$errorMessage = array();
	$debugMessage = array();
	$redirectAroundFormResubmitWarning = true;
	
	UpdateSettingsForiPad();
	SessionSetup();
	SQLIConnect();
	
	LoadConfigVariables();
	
	if($redirectAroundFormResubmitWarning)
	{
		//read session message vars and clear session vars
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

	global $config_appName;
	global $config_pageTitle;
	global $config_versionNote;
	
	//varibles definitions
	//dyanmic
	$pageSubTitle = "";
	$user = "";
	$userID = -1;
	$focusSearch = true;// can be diabled by functions when switching to a form page (like adding a new cust)
	
	$search = GetInput("search");
	$page = GetInput("page");
	$host = GetInput("host");
	$chassisnameInput = GetInput("chassisname");
	$deviceIDInput = GetInput("deviceid");
	$locationIDInput = GetInput("locationid");
	$userIDInput = GetInput("userid");
	$powerPanelIDInput = GetInput("powerpanelid");
	$powerUPSIDInput = GetInput("powerupsid");
	$roomIDInput = GetInput("roomid");
	$siteIDInput = GetInput("siteid");
	$loginbtn = GetInput("loginbtn");
	$searchbtn = GetInput("searchbtn");
	
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
	
	//see DescribeUserPermissionLevel($p) for permission details
	//this edits cookies which should be done in the header
	$permissionLevel = UpdatePermissionLevel();
	
	
	if(UserHasReadPermission())
		BuildDeviceModelArrays();
	 
	if(UserHasWritePermission())
		ProcessFormActions();
	
	$onload = "";
	if($page==="Audits")
	{
		$onload = "GotoAuditsFragment();";
	}
	
	//START PAGE CODE **************************************************************************************************
?>
<head>
<script src="lib/js/genericScripts.js"></script>
<script src="lib/js/scripts.js"></script>
<title><?php echo $config_pageTitle;?></title>
<link rel="icon" type="image/x-icon" href="images/favicon.ico">
<link rel="stylesheet" href="lib/css/default.css">
</head>
<body onload='<?php echo $onload;?>'>
	<!-- Title -->
	<div id="header-bg">
	<table class='pageMinWidth center'><tbody><tr>
			<td width="1">
				<a href="./"><img src="images/logo.png" border="0"></a>
			</td>
			<td valign="middle">
				<div id="appname"><h1><?php echo $config_appName;?></h1><?php echo $config_versionNote;?></div>
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
		<td width="1" valign="middle" align=right><h2>Welcome <?php echo $user; ?>!</h2></td>
		<td width="1" valign="middle">
			<a style="padding-left: 18px;" href="?loginbtn=N"><img src="images/logout.png" border=0 /></a>
		</td><?php
	}
		?>
		</tr></tbody></table>
	</div>
	
	<!--  MENU  -->
	<div id="top-nav">
		<table class="dr-toolbar-ext rich-toolbar pageMinWidth center">
		<tbody>
		<tr valign="middle">
	<?php
	$menuItems = "";//consolidate to single echo call to prevent menu flicker on page load - no effect =/
	//MENU Items
	$menuItems .= "<td class='dr-toolbar-int rich-toolbar-item' width='1'>\n";
	$menuItems .= "	<a href='.'>Home</a>\n";
	$menuItems .= "</td>\n";
	
	$multipleSites = true;
	if($multipleSites)
	{
		$menuItems .= "<td class='dr-toolbar-int rich-toolbar-item' width='1'>\n";
		$menuItems .= "	<a href='./?siteid=-1'>All Sites</a>\n";
		$menuItems .= "</td>\n";
	}
	if(UserHasWritePermission())
	{
		$menuItems .= "<td class='dr-toolbar-int rich-toolbar-item' width='1'>\n";
		$menuItems .= "	<a href='./?host=-1'>Add Customer</a>\n";
		$menuItems .= "</td>\n";
	}
	if(UserHasWritePermission())
	{
		$menuItems .= "<td class='dr-toolbar-int rich-toolbar-item' width='1'>\n";
		$menuItems .= "	<a href='./?page=Audits&siteid=$userSiteID'>Audits</a>\n";
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
	if(CustomFunctions::UserHasDevPermission())
	{
		//this is only setup for local phpmyadmin access 
		$menuItems .= "<td class='dr-toolbar-int rich-toolbar-item' width='1'>\n";
		$menuItems .= "	<a href='/phpmyadmin/' target='_blank'>PHPMyAdmin</a>\n";
		$menuItems .= "</td>\n";
	}
	
	//search
	if(UserHasReadPermission())
	{
		$placeholder = "Search";
		
		if(true)//custom search placeholder(s)
			$placeholder = CustomFunctions::GetSearchPlaceholder();
		
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
	<table id="pagecontainer" id="pagecontainer" class='center pageMinWidth'><tbody><tr><td>
	
	<?php
	
	$output= "<!-- HEADER LINKS -->\n";
	$output.= "<table width='100%'><tr>\n";
	$output.= "	<td>\n";
	if(UserHasReadPermission())
	{
		$output.= CustomFunctions::CreateNavigationQuickLinks();
	}
	$output.= "	</td>\n";
	$output.= "	<td align='right'>\n";
	$output.= "		<a href='#' class='hidden' id='showMessagesButton' onclick='ToggleMessgeVisibility()'>Show Messages</a>&nbsp;\n";
	$output.= "	</td>\n";
	$output.= "</tr></table>\n";
	
	//error and reporting mesages - filled in at the bottom of the page with JS
	$output.= "<!-- DEBUG MESSAGE  -->\n<div id='debugMessage'  style='display:none;' class='debugMessage'></div>\n";
	$output.= "<!-- ERROR MESSAGE  -->\n<div id='errorMessage'  style='display:none;' class='errorMessage'></div>\n";
	$output.= "<!-- RESULT MESSAGE -->\n<div id='resultMessage' style='display:none;' class='resultMessage'></div>\n";
	
	//populate messages - and dissable 'hidden' style is msg exists - NOTE: this is run again in the footer its here just in case the page crashes before then
	$debugMessageString  = str_replace('"',"&quot;", implode("<BR>",$debugMessage));
	$errorMessageString  = str_replace('"',"&quot;", implode("<BR>",$errorMessage));
	$resultMessageString = str_replace('"',"&quot;", implode("<BR>",$resultMessage));
	$debugMessageString  = str_replace("\n","<BR>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $debugMessageString);
	$errorMessageString  = str_replace("\n","<BR>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $errorMessageString);
	$resultMessageString = str_replace("\n","<BR>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $resultMessageString);
	$output.= "<script type='text/javascript' language='JavaScript'>\n";
	$output.= "	UpdatePageLoadMessages(\"$debugMessageString\",\"$errorMessageString\",\"$resultMessageString\");\n";
	$output.= "</script>\n";
	echo $output;
	
	if(!UserHasReadPermission())
	{
		if(isset($config_demoSiteEnabled) && $config_demoSiteEnabled)
		{
			echo "<!--  Demo Message  -->\n<BR>\n";
			$demoMessage = "<span style='font-size: 12px;'>
			This is a demo environment of DCIM. Check out the source code at the <a href='https://github.com/freshprogrammer/DCIM' target='_blank'>GIT repository here</a><BR>
			Test login credentials can be found <a href='https://github.com/freshprogrammer/DCIM/blob/master/documentation/creds.md' target='_blank'>here</a><BR>
			<BR>
			The entire demo database can be reset back to the last restore point <a href='lib/setup/dbControl.php' target='_blank'>here</a>.
			</span>";
			echo CreateMessagePanel("Demo Notice:",$demoMessage);
		}
		echo "<!--  LOGIN PROMPT  -->\n<BR>\n";
		LoginPrompt();
	}
	else
	{//officaliy logged in with min read access-------------------------------------------------------------------------------------------------
		echo "<!--  PAGE BODY-->\n";
		
		if(strlen($powerPanelIDInput) > 0)
		{
			if($page==="PowerAudit")
				ShowPowerPanelAuditPage($powerPanelIDInput);
			else
				ShowPowerPanelPage($powerPanelIDInput);
		}
		else if(strlen($page) > 0)
		{
			if($page==="Audits")
			{
				require_once 'audits.php';
				echo BuildAuditsPage($siteIDInput);
			}
			else if($page==="Audits_History")
			{
				require_once 'audits.php';
				echo BuildAuditsHistoryPage();
			}
		}
		else if(strlen($host) > 0)
			ShowCustomerPage($host);
		else if(strlen($roomIDInput) > 0)
			ShowRoomPage($roomIDInput);
		else if(strlen($siteIDInput) > 0)
		{
			if($siteIDInput==-1)
				ShowSiteListPage();
			else
				ShowSitePage($siteIDInput);
		}
		else if(strlen($deviceIDInput) > 0)
			ShowDevicePage($deviceIDInput);
		else if(strlen($chassisnameInput) > 0)
			ShowChassisPage($chassisnameInput);
		else if(strlen($locationIDInput) > 0)
			ShowLocationPage($locationIDInput);
		else if(strlen($userIDInput) > 0)
			ShowUsersPage($userIDInput);
		else if(strlen($powerUPSIDInput) > 0)
			ShowPowerUPSPage($powerUPSIDInput);
		else
		{
			//quick check to see if this is a company name or hno to sckip strait to customer page
			$singleCustomerMatch = TestForSingleCustomerMatch($search);
			if($singleCustomerMatch!=false)
			{
				//single customer
				ShowCustomerPage($singleCustomerMatch);
			}
			else
			{
				//search all
				if(strlen($search) > 0)
				{
					$pageSubTitle = "Search:\"".MakeHTMLSafe($search)."\"";
					echo "<div class=\"panel\">\n";
					echo "<div class=\"panel-header\">\n";
					echo "Search for \"".MakeHTMLSafe($search)."\" yields:\n";
					echo "</div>\n";
					echo "<div class=\"panel-body\">\n\n";
					
					//search for sites (name, fullname)
					echo ListSites("?", $search);
					echo "<BR>\n";
					
					//search for rooms (name, fullname)
					echo ListRooms("?", $search);
					echo "<BR>\n";
					
					//search in customer (hno, cno, name, note)
					ListSearchCustomers($search);
					echo "<BR>\n";
					
					if($config_badgesEnabled)
					{
						//search in badge (name, badgeno)
						ListBadges(true,$search);
						echo "<BR>\n";
					}
					
					//search in locaion/device (l.name, l.altname, l.note, d.name, d.model, d.asset, d.serial, d.note)
					ListDevices(true,$search);
					echo "<BR>\n";
					
					if($config_subnetsEnabled)
					{
						//search in VLANs (vlan name, subnet, note)
						ListCustomerSubnets("?",$search);
						echo "<BR>\n";
					}
					
					//search in powerups (name, note)
					echo ListUPSs("?",$search);
					echo "<BR>\n";
					
					//search in powerpanel (name, note)
					ListPowerPanels("?", $search);
					
					echo "</div>\n";
					echo "</div>\n";
				}
				else
				{
					if($searchbtn==="T")//mock for empty search
						$errorMessage[] ="Search for nuthin yields a whole lot of nuthin.";
					
					CustomFunctions::CreateHomePageContent();
				}//end search len > 0
			}//single cust not found
		}//not specific row/ca/cust
		echo "<!--  PAGE BODY END-->\n";
	}//end - page body vs login - //officaliy logged in with min read access
	
	//end of Body
	SQLIDisconnect();
?>
	</td></tr></tbody></table>
	<div id='outOfFocusOverlay' class='outOfFocusOverlay' onclick = 'ClearActivePopup();'></div>
</body>

<footer>
<script>
	//move focus as necisary
	InitializePage();
	<?php 
		//populate messages - and dissable 'hidden' style is msg exists
		$debugMessageString  = str_replace('"',"&quot;", implode("<BR>",$debugMessage));
		$errorMessageString  = str_replace('"',"&quot;", implode("<BR>",$errorMessage));
		$resultMessageString = str_replace('"',"&quot;", implode("<BR>",$resultMessage));
		$debugMessageString  = str_replace("\n","<BR>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $debugMessageString);
		$errorMessageString  = str_replace("\n","<BR>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $errorMessageString);
		$resultMessageString = str_replace("\n","<BR>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $resultMessageString);
		echo "UpdatePageLoadMessages(\"$debugMessageString\",\"$errorMessageString\",\"$resultMessageString\");\n";
		
		if(UserHasReadPermission() && $focusSearch)
			echo "FocusMainSearch();\n";
		//update title if necisarry
		if(strlen($pageSubTitle) > 0)
		{
			$pageTitle = $config_pageTitle." - ".$pageSubTitle;
			echo "document.title = '$pageTitle';\n";
		}
	?>
</script>

</footer>
</html>