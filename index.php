<?php
	set_include_path('lib/'); 
	
	require_once 'DCIMCustomFunctions.php';
	require_once 'config.php';
	require_once 'customFunctions.php';
	require_once 'genericFunctions.php';
	require_once 'helperFunctions.php';
	require_once 'functions.php';
	
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

	global $appName;
	global $pageTitle;
	global $versionNote;
	
	//varibles definitions
	//dyanmic
	$pageSubTitle = "";
	$user = "";
	$userID = -1;
	$focusSearch = true;// can be diabled by functions when switching to a form page (like adding a new cust)
	
	$search = GetInput("search");
	$page = GetInput("page");
	$pa_roomID = GetInput("pa_roomid");
	$pa_panel = GetInput("pa_panel");
	$host = GetInput("host");
	$chassisnameInput = GetInput("chassisname");
	$deviceIDInput = GetInput("deviceid");
	$locationIDInput = GetInput("locationid");
	$userIDInput = GetInput("userid");
	$loc = GetInput("loc");
	$roomID = GetInput("roomid");
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
				<div id="appname"><h1><?php echo $appName;?></h1><?php echo $versionNote;?></div>
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
	
	<!-- Page Body -->
	<table id="pagecontainer" id="pagecontainer" cellpadding="0" cellspacing="0" class='center pageMinWidth'><tbody><tr><td>
		
	<?php
	
	//BackupDatabase();
	
	if(UserHasReadPermission())
	{
		?>
		<!-- HEADER LINKS -->
		<table width=100%><tr>
			<td>
				<?php echo CustomFunctions::CreateNavigationQuickLinks() ?>
			</td>
			<td align='right'>
				<a href='#' class='' id='showMessagesButton' onclick='ToggleMessgeVisibility()'>Show Messages</a>&nbsp;
			</td>
		</tr></table>
		<BR>
		<?php
	}
		
	//error and reporting mesages - filled in at the bottom of the page with JS
	echo "<!-- DEBUG MESSAGE  -->\n<div id='debugMessage'  style='display:none;' class='debugMessage'></div>\n";
	echo "<!-- ERROR MESSAGE  -->\n<div id='errorMessage'  style='display:none;' class='errorMessage'></div>\n";
	echo "<!-- RESULT MESSAGE -->\n<div id='resultMessage' style='display:none;' class='resultMessage'></div>\n";
		
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
			ShowCustomerPage($host);
			
		}
		else if(strlen($roomID) > 0)
		{
			ShowRoomPage($roomID);
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
				if(strlen($pa_roomID) > 0 && strlen($pa_panel) > 0)
				{
					PowerAuditPanel($pa_roomID,$pa_panel);
				}
				else
				{
					PowerAuditPanelList();
				}
			}
			else if($page==="Audits")
			{
				require_once 'audits.php';
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
				ShowCustomerPage($singleCustomerMatch);
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
					if($searchbtn==="T")//mock for empty search
						$errorMessage[] ="Search for nuthin yields a whole lot of nuthin.";
					
					echo CustomFunctions::CreateHomePageContent();
				}//end search len > 0
			}//single cust not found
		}//not specific row/ca/cust
		echo "<BR>";
		echo "<!--  PAGE BODY END-->\n";
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
		echo "InitializePage();\n";
		
		//populate messages - and dissable 'hidden' style is msg exists
		$debugMessageString  = str_replace('"',"&quot;", implode("<BR>",$debugMessage));
		$errorMessageString  = str_replace('"',"&quot;", implode("<BR>",$errorMessage));
		$resultMessageString = str_replace('"',"&quot;", implode("<BR>",$resultMessage));
		echo "UpdatePageLoadMessages(\"$debugMessageString\",\"$errorMessageString\",\"$resultMessageString\");\n";
		
		
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