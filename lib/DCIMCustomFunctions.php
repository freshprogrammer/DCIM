<?php
	//These are the functions that can be overwritten with custom code - defaults provided here dont have to be overwritten but can be
	//NOTE many functions refferance the CustomFunctions class that is actualy a child of this. This is not ideal but using self doesnt work (link back to this class instead of possible child), and 'this' is not an option since these are static functions. PHP 5.3 added get_called_class() for this.c
	class DCIMCustomFunctions
	{
		static $locationBorderThickness = 1;
		static $roomBorderThickness = 3;
		static $siteBorderThickness = 3;
		
		//these permissions are custom-ish so they are here so indivudual users can be given specific permissions (like if Tony was in charge of switch ports)
		public static function UserHasDevPermission()
		{//this is used to hide in development proceedures and other stuff above typical admin prilages like access to phpmyadmin
			global $userID;
			return (int)$userID==0;//admin only
		}
		public static function UserHasBadgeDeletePermission()
		{
			return UserHasWritePermission();
		}
		public static function UserHasPowerCircuitPermission()
		{
			return UserHasWritePermission();
		}
		public static function UserHasPanelPermission()
		{
			return UserHasAdminPermission();
		}
		public static function UserHasUPSPermission()
		{
			return UserHasAdminPermission();
		}
		public static function UserHasLocationPermission()
		{
			return UserHasAdminPermission();
		}
		public static function UserHasPortAddEditPermission()
		{
			return UserHasWritePermission();
		}
		public static function UserHasPortDeletePermission()
		{
			return UserHasWritePermission();
		}
		public static function UserHasRoomPermission()
		{
			return UserHasAdminPermission();
		}
		public static function UserHasSitePermission()
		{
			return UserHasAdminPermission();
		}
		
		public static function IsThisHNoInternal($hNo)
		{
			return $hNo=="387046";
		}
		
		public static function IsThisLocationABottomHalfCab($locationID)
		{
			//this should be a more complicated lookup - but so rarely changes could just as easily be edited manualy
			$bottomIDs = array(142,144,146,148,150,152,154,156,158,160,162,164,166,168);
			return in_array($locationID,$bottomIDs);
		}
		
		public static function RemotePowerPanelAuditHelpPopup()
		{
			//links to direct files left off so custom links can be included.
			//Power Audit Creator can be found in a seperate repo here http://github.com/freshprogrammer/ExcelController/releases
			$result = "<span class='helpText'><span class='helpHeading'>Remote Power Panel Audit Automation</span><BR>
After a DC power audit has been done and the data has all been updated a RPP Audit workbook can be created and uploaded as necessary. 
Simply export the power data then run the RPP Audit creation tool. 
The tool will prompt for a data file and a template file and then create the new workbook for you. 
From there you can easily save it with an appropriate name and store it.<BR>
</span>";
			return $result;
		}
		
		public static function CreateInternalLink($hNo, $cNo, $includeOldLink)
		{//these are internal links for cust and device pages to other applications
			$result = "<a href='https://internalApp.com?search=$hNo' target='_blank' class='internalLink'>(main system)</a>\n";
			if($includeOldLink)
				$result .= "<a href='https://internalApp.com?cno=$cNo&search=$hNo' target='_blank' class='internalLink'>(Old system)</a>\n";
			return $result;
		}
		
		public static function CreateInternalInventoryLink($asset)
		{//these are internal links for more info about the asset number
			if(is_numeric($asset))
				return "<a href='https://internalApp.com?search=$asset' target='_blank' class='internalLink'>$asset</a>\n";
			else
				return MakeHTMLSafe($asset);
		}
		
		public static function GetSearchPlaceholder()
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
			$searchPlaceHolders[] = "Please like and subscribe";
			$searchPlaceHolders[] = "Doesn't search Facebook";
			$searchPlaceHolders[] = "No Facebook login";
			$searchPlaceHolders[] = "No like buttons here";
				
			$searchPlaceHolders[] = "$linesOfCode+ lines of code";
			$searchPlaceHolders[] = "$linesOfCode+ free range lines";
			$searchPlaceHolders[] = "$linesOfCode lines, but cutting back";
			$searchPlaceHolders[] = "15K+ line club";
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
				
			return $searchPlaceHolders[array_rand($searchPlaceHolders)];
		}
		
		public static function CreateNavigationQuickLinks()
		{
			$result  = "<a class='navLinks' href='?roomid=1'>CA1</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?roomid=2'>CA2</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?roomid=3'>CA3</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?roomid=4'>CA4</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?roomid=5'>CA5</a>&nbsp;\n";
			return $result;
		}
		
		public static function CreateHomePageContent()
		{//just logged in - at home page
			global $appName;
			global $mysqli;
			global $errorMessage;
			global $pageSubTitle;
			global $userID;
			
			$pageSubTitle = "Home";
			
			$result = "";
			if(UserHasWritePermission() && IsUserUsingDefaultPassword())
			{
				$result .= CreateMessagePanel("Warning","Please <a href='./?userid=$userID'>change your password</a> from the default when you get a chance.");
			}
			
			$result .= "<div class=\"panel\">\n";
			$result .= "<div class=\"panel-header\">\n";
			$result .= "$appName\n";
			$result .= "</div>\n";
			
			$result .= "<div class=\"panel-body\">\n\n";
			
			//select site(s) from table for rendering each one
			$query = "SELECT siteid, name, fullname, width, depth
					FROM dcim_site
					WHERE width > 0 AND depth > 0";
			
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->execute())
			{
				$errorMessage[]= "CreateHomePageContent() SQL Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
			}
			else
			{
				$stmt->store_result();
				$stmt->bind_result($siteID, $name , $fullName, $width, $depth);
				
				while($stmt->fetch())
				{
					$depthToWidthRatio = 100*$depth/$width;//key proportian of the site
					$result .= "<style>\n";
					$result .= "#siteContainer$siteID {\n";
					$result .= "	padding-bottom:$depthToWidthRatio%;\n";
					$result .= "}\n";
					$result .= "</style>\n";
					$result .= "<div id='siteContainer$siteID' class='siteContainer'>\n";
					$result .= CreateSiteLayout($siteID, $name, $fullName, $width, $depth);//this should be a lookup of all sites...
					$result .= "</div>\n";
					
					$result .= "<BR>\n";
					$result .= ListSiteRooms($siteID, $fullName);
				}
			}
			
			$result .= "</div>\n";//end panel
			$result .= "</div>\n";
			return $result;
		}
		
		public static function CreateSiteCustomLayout($siteID, $name, $fullName, $width, $depth)
		{
			$borderThickness = CustomFunctions::$siteBorderThickness;
			//key percentages for drawing site rectangles
			if($siteID==0)
			{
				$rightTopX = 37;
				$rightTopY = 12;
				$leftBottomY = 85.5;
				$leftBottomBottom = 91;//very bottom left side
				$rightBottomX = 57;
				$rightBottomY = 77;
				$nocWidth = 13.5;
				
				//calculated
				$leftHeight = $leftBottomY-$rightTopY;
				$leftBottomHeight = $leftBottomBottom-$leftBottomY;
				$rightTopWidth = 100-$rightTopX;
				$rightTopHeight = $rightBottomY-$rightTopY;
				$nocHeight = $leftBottomY-$rightBottomY;
				$rightBottomWidth = 100-$rightBottomX;
				$rightBottomHeight = 100-$rightBottomY;
				$stairsWallX = $rightTopX+$nocWidth;
				$stairsWallWidth = 100 - ($stairsWallX + $rightBottomWidth);
				
				$result = "<style>\n";
				$result .= "#site".$siteID."_TopLeft {\n";
				$result .= "	width: $rightTopX%;\n";
				$result .= "	height: $rightTopY%;\n";
				$result .= "	border-style: solid solid hidden solid;\n";
				$result .= "}\n";
				$result .= "#site".$siteID."_Left {\n";
				$result .= "	top: $rightTopY%;\n";
				$result .= "	width: $rightTopX%;\n";
				$result .= "	height: $leftHeight%;\n";
				$result .= "	border-style: hidden hidden hidden solid;\n";
				$result .= "}\n";
				$result .= "#site".$siteID."_LeftBottom {\n";
				$result .= "	top: $leftBottomY%;\n";
				$result .= "	width: $rightTopX%;\n";
				$result .= "	height: $leftBottomHeight%;\n";
				$result .= "	border-style: hidden solid solid solid;\n";
				$result .= "}\n";
				$result .= "#site".$siteID."_RightTop {\n";
				$result .= "	left: calc($rightTopX% - ".$borderThickness."px);\n";
				$result .= "	top: $rightTopY%;\n";
				$result .= "	width: calc($rightTopWidth% + ".$borderThickness."px);\n";
				$result .= "	height: $rightTopHeight%;\n";
				$result .= "	border-style: solid solid hidden hidden;\n";
				$result .= "}\n";
				$result .= "#site".$siteID."_RightBottom {\n";
				$result .= "	left: $rightBottomX%;\n";
				$result .= "	top: $rightBottomY%;\n";
				$result .= "	width: $rightBottomWidth%;\n";
				$result .= "	height: $rightBottomHeight%;\n";
				$result .= "	border-style: hidden solid solid solid;\n";
				$result .= "}\n";
				$result .= "#site".$siteID."_NOC {\n";
				$result .= "	left: calc($rightTopX% - ".$borderThickness."px);\n";
				$result .= "	top: $rightBottomY%;\n";
				$result .= "	width: calc($nocWidth% + ".$borderThickness."px);\n";
				$result .= "	height: $nocHeight%;\n";
				$result .= "	border-style: hidden solid solid hidden;\n";
				$result .= "}\n";
				$result .= "#site".$siteID."_StairsWall {\n";
				$result .= "	left: $stairsWallX%;\n";
				$result .= "	top: $rightBottomY%;\n";
				$result .= "	width: $stairsWallWidth%;\n";
				$result .= "	height: ".$borderThickness."px;\n";
				$result .= "	border-style: solid hidden hidden hidden;\n";
				$result .= "}\n";
				$result .= "#site".$siteID."_Label {\n";//label possitioning
				$result .= "}\n";
				$result .= "</style>\n";
				
				$result .= "<div class='siteBackground' id='site".$siteID."_TopLeft'></div>\n";
				$result .= "<div class='siteBackground' id='site".$siteID."_Left'></div>\n";
				$result .= "<div class='siteBackground' id='site".$siteID."_LeftBottom'></div>\n";
				$result .= "<div class='siteBackground' id='site".$siteID."_RightTop'></div>\n";
				$result .= "<div class='siteBackground' id='site".$siteID."_RightBottom'></div>\n";
				$result .= "<div class='siteBackground' id='site".$siteID."_NOC'></div>\n";
				$result .= "<div class='siteBackground' id='site".$siteID."_StairsWall'></div>\n";
			}//siteID 0 custom layout
			else if($siteID==1)
			{//default site layout
				$result = "<style>\n";
				$result .= "#site".$siteID." {\n";
				$result .= "}\n";
				$result .= "#site".$siteID."_Label {\n";//label possitioning
				$result .= "	left: 15%;\n";
				$result .= "}\n";
				$result .= "</style>\n";
				
				$result .= "<div class='siteBackground' id='site".$siteID."'></div>\n";
			}
			else
			{//default site layout
				$result = "<style>\n";
				$result .= "#site".$siteID." {\n";
				$result .= "}\n";
				$result .= "#site".$siteID."_Label {\n";//label possitioning
				$result .= "}\n";
				$result .= "</style>\n";
				
				$result .= "<div class='siteBackground' id='site".$siteID."'></div>\n";
			}
			$result .= "<span class='siteLabel siteBackground' id='site".$siteID."_Label' title='$fullName'>$name</span>\n";
			return $result;
		}
		
		public static function CreateRoomCustomLayout($roomID, $name, $custAccess, &$roomCustomHTML, &$roomCustomStyle)
		{//creates custom html and style for specific rooms - returned with ref passes variables roomCustomHTML and roomCustomStyle
			$borderThickness = CustomFunctions::$roomBorderThickness;
			$roomTypeClass = RoomAccesClass($custAccess);
			
			if(false)
			{//show room true area for working on new layouts
				//test room border
				$roomCustomStyle .= "#room".$roomID."_TestAll {\n";
				$roomCustomStyle .= "	border-style: hidden;\n background:#ffc0cb80;}\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_TestAll'></div>\n";//last because of overlap
			}
			
			//custom layouts
			if($roomID==1)
			{//ca 1
				$cornerWidthInset = -16.272;//percent inset corner
				$cornerDepthInset = 49;
				CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span class='roomLayoutTitle'>$name</span>\n";
			}
			else if($roomID==2)
			{//ca 2 - two close left corner insets
				$cornerWidthInset1 = 11;
				$cornerDepthInset1 = -68;
				$cornerWidthInset2 = 57.82;
				$cornerDepthInset2 = -22.48;
				
				$rightWidth = 100-$cornerWidthInset2;
				$midWidth = 100-$cornerWidthInset1-$rightWidth;
				$leftTopDepth = 100+$cornerDepthInset1;
				$midBottomDepth = 100-$leftTopDepth - (-1*$cornerDepthInset2);
				$rightTopDepth = $leftTopDepth+$midBottomDepth;//wrong
				$rightBottomDepth = -1*$cornerDepthInset2;
				
				$roomCustomStyle .= "#room".$roomID."_LeftTop {\n";
				$roomCustomStyle .= "	width: $cornerWidthInset1%;\n";
				$roomCustomStyle .= "	height: $leftTopDepth%;\n";
				$roomCustomStyle .= "	border-style: solid hidden solid solid;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_MidTop {\n";
				$roomCustomStyle .= "	left: $cornerWidthInset1%;\n";
				$roomCustomStyle .= "	width: $midWidth%;\n";
				$roomCustomStyle .= "	height: $leftTopDepth%;\n";
				$roomCustomStyle .= "	border-style: solid hidden hidden hidden;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_MidBottom {\n";
				$roomCustomStyle .= "	left: $cornerWidthInset1%;\n";
				$roomCustomStyle .= "	top: calc($leftTopDepth% - ".$borderThickness."px);\n";
				$roomCustomStyle .= "	width: calc($midWidth% + ".$borderThickness."px);\n";
				$roomCustomStyle .= "	height:calc($midBottomDepth% + ".$borderThickness."px);\n";
				$roomCustomStyle .= "	border-style: hidden hidden solid solid;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_RightTop {\n";
				$roomCustomStyle .= "	left: $cornerWidthInset2%;\n";
				$roomCustomStyle .= "	width: $rightWidth%;\n";
				$roomCustomStyle .= "	height: $rightTopDepth%;\n";
				$roomCustomStyle .= "	border-style: solid solid hidden hidden;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_RightBottom {\n";
				$roomCustomStyle .= "	left: $cornerWidthInset2%;\n";
				$roomCustomStyle .= "	top: $rightTopDepth%;\n";
				$roomCustomStyle .= "	width: $rightWidth%;\n";
				$roomCustomStyle .= "	height: $rightBottomDepth%;\n";
				$roomCustomStyle .= "	border-style: hidden solid solid solid;\n";
				$roomCustomStyle .= "}\n";
				
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_LeftTop'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_MidTop'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_RightTop'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_RightBottom'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_MidBottom'></div>\n";//last because of overlap
				$roomCustomHTML .= "<span class='roomLayoutTitle'>$name</span>\n";
			}
			else if($roomID==3)
			{//ca 3
				$cornerWidthInset = 53;
				$cornerDepthInset = -19.27;
				CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span class='roomLayoutTitle'>$name</span>\n";
			}
			else if($roomID==4)
			{//ca 4
				$cornerWidthInset = -8.067;
				$cornerDepthInset = -9.55;
				CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span class='roomLayoutTitle'>$name</span>\n";
			}
			else if($roomID==5)
			{//ca 5
				/* roughly (3 is entrance, 7 is exit)
				 * 111111
				 * 111111
				 * 2444445555556
				 * 2444445555556
				 * 2444445555556
				 * 2444445555556
				 * 3           7
				 *             7*/
				$entranceWidth = 11;//percent inset corner
				$entranceDepth = 4.5;
				$farLeftWidth = 51.87;
				$farLeftDepth= 31.92;
				$exitWidth = 13;
				$exitDepth = 20;// width of AC room
				
				$x1 = $entranceWidth;//4 left
				$x2 = $farLeftWidth;//5 left
				$x3 = 100-$exitWidth;//6 left
				$y1 = 100-$exitDepth;//3 top
				$midDepth = 100-$exitDepth-$farLeftDepth;
				$width4 = $x2-$x1;
				$width5 = $x3-$x2;
				
				$roomCustomStyle .= "#room".$roomID."_1FarLeft {\n";
				$roomCustomStyle .= "	width: $farLeftWidth%;\n";
				$roomCustomStyle .= "	height: $farLeftDepth%;\n";
				$roomCustomStyle .= "	border-style: solid solid hidden solid;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_2MidLeft {\n";
				$roomCustomStyle .= "	top: $farLeftDepth%;\n";
				$roomCustomStyle .= "	width: $entranceWidth%;\n";
				$roomCustomStyle .= "	height: $midDepth%;\n";
				$roomCustomStyle .= "	border-style: hidden hidden hidden solid;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_3Entrance {\n";
				$roomCustomStyle .= "	top: $y1%;\n";
				$roomCustomStyle .= "	width: $entranceWidth%;\n";
				$roomCustomStyle .= "	height:$entranceDepth%;\n";
				$roomCustomStyle .= "	border-style: hidden solid solid solid;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_4 {\n";
				$roomCustomStyle .= "	left: calc($x1% - ".$borderThickness."px);\n";
				$roomCustomStyle .= "	top: $farLeftDepth%;\n";
				$roomCustomStyle .= "	width: calc($width4% + ".$borderThickness."px);\n";
				$roomCustomStyle .= "	height: $midDepth%;\n";
				$roomCustomStyle .= "	border-style: hidden hidden solid hidden;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_5 {\n";
				$roomCustomStyle .= "	left: calc($x2% - ".$borderThickness."px);\n";
				$roomCustomStyle .= "	top: $farLeftDepth%;\n";
				$roomCustomStyle .= "	width: calc($width5% + 2*".$borderThickness."px);\n";
				$roomCustomStyle .= "	height: $midDepth%;\n";
				$roomCustomStyle .= "	border-style: solid hidden solid hidden;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_6Right {\n";
				$roomCustomStyle .= "	left: $x3%;\n";
				$roomCustomStyle .= "	top: $farLeftDepth%;\n";
				$roomCustomStyle .= "	width: $exitWidth%;\n";
				$roomCustomStyle .= "	height: $midDepth%;\n";
				$roomCustomStyle .= "	border-style: solid solid hidden hidden;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_7Exit {\n";
				$roomCustomStyle .= "	left: $x3%;\n";
				$roomCustomStyle .= "	top: $y1%;\n";
				$roomCustomStyle .= "	width: $exitWidth%;\n";
				$roomCustomStyle .= "	height: $exitDepth%;\n";
				$roomCustomStyle .= "	border-style: hidden solid solid solid;\n";
				$roomCustomStyle .= "}\n";
				
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_1FarLeft'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_2MidLeft'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_3Entrance'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_6Right'></div>\n";//4 and 5 overlap so this is out of order
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_4'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_5'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_7Exit'></div>\n";
				$roomCustomHTML .= "<span class='roomLayoutTitle'>$name</span>\n";
			}
			else if($roomID==9)
			{//ups 1
				$cornerWidthInset = 22.159;//percent inset corner
				$cornerDepthInset = 31.263;
				CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span style='left:$cornerWidthInset%' class='roomLayoutTitle'>$name</span>\n";
			}
			else if($roomID==10)
			{//ups 2
				$cornerWidthInset = 24.855;//percent inset corner
				$cornerDepthInset = 21.875;
				CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span style='left:$cornerWidthInset%' class='roomLayoutTitle'>$name</span>\n";
			}
			else if($roomID==15)
			{//loading - two far right corner insets
				$cornerWidthInset1 = -65;
				$cornerDepthInset1 = 15;
				$cornerWidthInset2 = -18;
				$cornerDepthInset2 = 36;
				
				$leftWidth = 100+$cornerWidthInset1;
				$midWidth = 100+$cornerWidthInset2-$leftWidth;
				$rightWidth = -1*$cornerWidthInset2;

				$rightX = $leftWidth+$midWidth;
				$midTopDepth = $cornerDepthInset2-$cornerDepthInset1;
				$midBottomDepth = 100- (-1*$cornerDepthInset2);
				$leftBottomDepth = 100-$cornerDepthInset1;
				$rightBottomDepth = 100-$cornerDepthInset2;

				$roomCustomStyle .= "#room".$roomID."_LeftTop {\n";
				$roomCustomStyle .= "	width: $leftWidth%;\n";
				$roomCustomStyle .= "	height: $cornerDepthInset1%;\n";
				$roomCustomStyle .= "	border-style: solid solid hidden solid;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_LeftBottom {\n";
				$roomCustomStyle .= "	top: $cornerDepthInset1%;\n";
				$roomCustomStyle .= "	width: $leftWidth%;\n";
				$roomCustomStyle .= "	height: $leftBottomDepth%;\n";
				$roomCustomStyle .= "	border-style: hidden hidden solid solid;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_MidTop {\n";
				$roomCustomStyle .= "	left: calc($leftWidth% - ".$borderThickness."px);\n";
				$roomCustomStyle .= "	top: $cornerDepthInset1%;\n";
				$roomCustomStyle .= "	width: calc($midWidth% + ".$borderThickness."px);\n";
				$roomCustomStyle .= "	height:calc($midTopDepth% + ".$borderThickness."px);\n";
				$roomCustomStyle .= "	border-style: solid solid hidden hidden;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_MidBottom {\n";
				$roomCustomStyle .= "	left: $leftWidth%;\n";
				$roomCustomStyle .= "	top: $cornerDepthInset2%;\n";
				$roomCustomStyle .= "	width: $midWidth%;\n";
				$roomCustomStyle .= "	height: $rightBottomDepth%;\n";
				$roomCustomStyle .= "	border-style: hidden hidden solid hidden;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_RightBottom {\n";
				$roomCustomStyle .= "	left: $rightX%;\n";
				$roomCustomStyle .= "	top: $cornerDepthInset2%;\n";
				$roomCustomStyle .= "	width: $rightWidth%;\n";
				$roomCustomStyle .= "	height: $rightBottomDepth%;\n";
				$roomCustomStyle .= "	border-style: solid solid solid hidden;\n";
				$roomCustomStyle .= "}\n";
				
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_LeftTop'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_LeftBottom'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_MidBottom'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_RightBottom'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_MidTop'></div>\n";//last because of overlap
				$roomCustomHTML .= "<span class='roomLayoutTitle'>$name</span>\n";
			}
			else if($roomID==20)
			{//Area1
				//    -112-
				//  -----------
				//  |       __| d1
				//  |     __|   d2
				//  |   __|
				//  |  _|
				//  |__|
				//   w1 w2
				$roomWidth = 112;
				$col1Width = 32;
				$col2Width = 34;
				$col3Width = 3;
				$col4Width = 19;
				$col5Width = 24;
				
				$roomDepth = 75;
				$row1Depth = 25;
				$row2Depth = 8;
				$row3Depth = 7;
				$row4Depth = 3;
				$row5Depth = 32;
				
				$col0x = 0;//just to keep the code clean
				$col1x = 100*($col1Width)/$roomWidth;//x pos of col 2 (col0 is at 0)
				$col2x = 100*($col1Width+$col2Width)/$roomWidth;
				$col3x = 100*($col1Width+$col2Width+$col3Width)/$roomWidth;
				$col4x = 100*($col1Width+$col2Width+$col3Width+$col4Width)/$roomWidth;
				
				$row0y = 0;//just to keep the code clean
				$row1y = 100*($row1Depth)/$roomDepth;//y pos of col 2 (col0 is at 0)
				$row2y = 100*($row1Depth+$row2Depth)/$roomDepth;
				$row3y = 100*($row1Depth+$row2Depth+$row3Depth)/$roomDepth;
				$row4y = 100*($row1Depth+$row2Depth+$row3Depth+$row4Depth)/$roomDepth;
				
				// width and depth percentages
				$col1W = 100*$col1Width/$roomWidth;
				$col2W = 100*$col2Width/$roomWidth;
				$col3W = 100*$col3Width/$roomWidth;
				$col4W = 100*$col4Width/$roomWidth;
				$col5W = 100*$col5Width/$roomWidth;
				
				$row1D = 100*$row1Depth/$roomDepth;
				$row2D = 100*$row2Depth/$roomDepth;
				$row3D = 100*$row3Depth/$roomDepth;
				$row4D = 100*$row4Depth/$roomDepth;
				$row5D = 100*$row5Depth/$roomDepth;
				
				//top cells
				$roomCustomStyle .= "#room".$roomID."_LeftTop {\n";
				$roomCustomStyle .= "	left: $col0x%;\n     top: $row0y%;\n";
				$roomCustomStyle .= "	width: $col1W%;\n height: ".($row1D+$row2D+$row3D+$row4D)."%;\n";
				$roomCustomStyle .= "	border-style: solid hidden hidden solid;\n}\n";
				$roomCustomStyle .= "#room".$roomID."_LeftRightTop {\n";
				$roomCustomStyle .= "	left: $col1x%;\n     top: $row0y%;\n";
				$roomCustomStyle .= "	width: $col2W%;\n height: ".($row1D+$row2D+$row3D)."%;\n";
				$roomCustomStyle .= "	border-style: solid hidden hidden hidden;\n}\n";
				$roomCustomStyle .= "#room".$roomID."_MidTop {\n";
				$roomCustomStyle .= "	left: $col2x%;\n     top: $row0y%;\n";
				$roomCustomStyle .= "	width: $col3W%;\n height: ".($row1D+$row2D)."%;\n";
				$roomCustomStyle .= "	border-style: solid hidden hidden hidden;\n}\n";
				$roomCustomStyle .= "#room".$roomID."_RightLeftTop {\n";
				$roomCustomStyle .= "	left: $col3x%;\n     top: $row0y%;\n";
				$roomCustomStyle .= "	width: $col4W%;\n height: $row1D%;\n";
				$roomCustomStyle .= "	border-style: solid hidden hidden hidden;\n}\n";
				$roomCustomStyle .= "#room".$roomID."_RightTop {\n";
				$roomCustomStyle .= "	left: calc($col4x% - ".$borderThickness."px);\n     top: $row0y%;\n";
				$roomCustomStyle .= "	width: calc($col5W% + ".$borderThickness."px);\n height: $row1D%;\n";
				$roomCustomStyle .= "	border-style: solid solid solid hidden;\n}\n";
				//bottom cells - offset by border size so the borders match better
				$roomCustomStyle .= "#room".$roomID."_LeftBottom {\n";
				$roomCustomStyle .= "	left: $col0x%;\n     top: calc($row4y% - ".$borderThickness."px);\n";
				$roomCustomStyle .= "	width: $col1W%;\n height: calc($row5D% + ".$borderThickness."px);\n";
				$roomCustomStyle .= "	border-style: hidden solid solid solid;\n}\n";
				$roomCustomStyle .= "#room".$roomID."_LeftRightBottom {\n";
				$roomCustomStyle .= "	left: $col1x%;\n     top: $row3y%;\n";
				$roomCustomStyle .= "	width: $col2W%;\n height: $row4D%;\n";
				$roomCustomStyle .= "	border-style: hidden solid solid hidden;\n}\n";
				$roomCustomStyle .= "#room".$roomID."_MidBottom {\n";
				$roomCustomStyle .= "	left: calc($col2x% - ".$borderThickness."px);\n     top: calc($row2y% - ".$borderThickness."px);\n";
				$roomCustomStyle .= "	width: calc($col3W% + ".$borderThickness."px);\n height: calc($row3D% + ".$borderThickness."px);\n";
				$roomCustomStyle .= "	border-style: hidden solid solid hidden;\n}\n";
				$roomCustomStyle .= "#room".$roomID."_RightLeftBottom {\n";
				$roomCustomStyle .= "	left: $col3x%;\n     top: $row1y%;\n";
				$roomCustomStyle .= "	width: $col4W%;\n height: $row2D%;\n";
				$roomCustomStyle .= "	border-style: hidden solid solid hidden;\n}\n";
				
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_LeftTop'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_LeftRightTop'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_MidTop'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_RightLeftTop'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_RightTop'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_LeftBottom'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_LeftRightBottom'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_MidBottom'></div>\n";
				$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_RightLeftBottom'></div>\n";
				
				$roomCustomHTML .= "<span class='roomLayoutTitle'>$name</span>\n";
			}
			else if($roomID==21)
			{//area2 2
				$cornerWidthInset = 100*32/150;//percent inset corner
				$cornerDepthInset = 100*(-5)/54;
				CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span style='' class='roomLayoutTitle'>$name</span>\n";
			}
			else if($roomID==27)
			{//LOU area3
				$cornerWidthInset = 100*(-18)/55;//percent inset corner
				$cornerDepthInset = 100*(-35)/66;
				CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span style='' class='roomLayoutTitle'>$name</span>\n";
			}
			else if($roomID==28)
			{//NWK DC2
				$cornerWidthInset = 100*(44)/83;//percent inset corner
				$cornerDepthInset = 100*(-24)/89;
				CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span style='' class='roomLayoutTitle'>$name</span>\n";
			}
			else if($roomID==29)
			{//NWK DC3
				$cornerWidthInset = 100*(-10)/41;//percent inset corner
				$cornerDepthInset = 100*(-9)/95;
				CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span style='' class='roomLayoutTitle'>$name</span>\n";
			}
			else if($roomID==36)
			{//SFO colo4
				$cornerWidthInset = 100*(-10)/81;//percent inset corner
				$cornerDepthInset = 100*(16)/61;
				CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span style='' class='roomLayoutTitle'>$name</span>\n";
			}
		}//end CreateRoomCustomLayout()
	}// end DCIMCustomFunctions class
?>
