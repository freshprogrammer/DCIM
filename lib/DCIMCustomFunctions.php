<?php
	//These are the functions that can be overwritten with custom code - defaults provided here dont have to be overwritten but can be
	//NOTE many functions refferance the CustomFunctions class that is actualy a child of this. This is not ideal but using self doesnt work (link back to this class instead of possible child), and 'this' is not an option since these are static functions. PHP 5.3 added get_called_class() for this.c
	class DCIMCustomFunctions
	{
		public static function UserHasDevPermission()
		{//this is used to hide in development proceedures and other stuff above typical admin prilages like access to phpmyadmin
			global $userID;
			return (int)$userID==0;//admin only
		}
		public static function UserHasCircuitPermission()
		{
			return UserHasWritePermission();
		}
		public static function UserHasLocationPermission()
		{
			return UserHasAdminPermission();
		}
		
		public static function CreateInternalLink($hNo, $cNo, $includeOldLink)
		{//these are internal links for cust and device pages to other applications
			$result = "<a href='https://internalApp.com?search=$hNo' target='_blank' class='internalLink'>(main system)</a>\n";
			if($includeOldLink)
				$result .= "<a href='https://internalApp.com?cno=$cNo&search=$hNo' target='_blank' class='internalLink'>(Old system)</a>\n";
			return $result;
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
				
			return $searchPlaceHolders[array_rand($searchPlaceHolders)];
		}
		
		public static function CreateNavigationQuickLinks()
		{
			//TODO this should be an actual room lookup not hardcoded roomids
			$result  = "<a class='navLinks' href='?roomid=2'>CA1</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?roomid=3'>CA2</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?roomid=4'>CA3</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?roomid=5'>CA4</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?roomid=6'>CA5</a>&nbsp;\n";
			return $result;
		}
		
		public static function CreateHomePageContent()
		{//just logged in - at home page
			global $appName;
			global $mysqli;
			global $errorMessage;
			
			$result = "<div class=\"panel\">\n";
			$result .= "<div class=\"panel-header\">\n";
			$result .= "$appName\n";
			$result .= "</div>\n";
			
			$result .= "<div class=\"panel-body\">\n\n";
			
			if(UserHasWritePermission() && IsUserUsingDefaultPassword())
			{
				$result .= "Please <a href='./?userid=$userID'>change your password</a> from the default when you get a chance.<BR><BR>";
			}
			
			//select site(s) from table for rendering each one
			$query = "SELECT siteid, name, fullname, width, depth
					FROM dcim_site
					WHERE width > 0 AND depth > 0";

			if (!($stmt = $mysqli->prepare($query)) || !$stmt->execute())
			{
				$errorMessage[]= "CreateHomePageContent() SQL Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
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
					$result .= CustomFunctions::CreateSiteLayout($siteID, $name, $fullName, $width, $depth);//this should be a lookup of all sites...
					$result .= "</div>\n";
				}
			}
			
			
			$result .= "</div>\n";//end panel
			$result .= "</div>\n";
			return $result;
		}
		
		public static function CreateSiteLayout($siteID, $name, $fullName, $siteWidth, $siteDepth)
		{
			global $mysqli;
			global $errorMessage;
			
			$result = CustomFunctions::CreateSiteCustomLayout($siteID, $name, $fullName, $siteWidth, $siteDepth);
			
			//select rooms from table for rendering each one
			$query = "SELECT roomid, name, fullname, custaccess, xpos, ypos, width, depth, orientation
					FROM dcim_room
					WHERE siteid=? AND width > 0 AND depth > 0";
			
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $siteID) || !$stmt->execute())
			{
				$errorMessage[]= "CreateSiteLayout() SQL setup failed: (" . $mysqli->errno . ") " . $mysqli->error;
			}
			else
			{
				$stmt->store_result();
				$stmt->bind_result($roomID, $name , $fullName, $custAccess, $xPos, $yPos, $width, $depth, $orientation);
				
				while($stmt->fetch())
				{
					$result .= CustomFunctions::CreateRoomLayout($roomID, $siteWidth, $siteDepth, $name , $fullName, $custAccess, $xPos, $yPos, $width, $depth, $orientation);
				}
			}
			return $result;
		}
		
		public static function CreateSiteCustomLayout($siteID, $name, $fullName, $width, $depth)
		{
			$borderThickness = 4;
			//key percentages for drawing site rectangles
			if(siteID==0)
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
		
		public static function CreateRoomLayout($roomID, $parentWidth, $parentDepth, $name , $fullName, $custAccess, $xPos, $yPos, $width, $depth, $orientation)
		{
			//calculated
			$relativeX = 100*$xPos/$parentWidth;
			$relativeY= 100*$yPos/$parentDepth;

			$rotation = OritentationToDegrees($orientation);
			$rotationTransform = "	transform: rotate(".$rotation."deg); -ms-transform: rotate(".$rotation."deg); -webkit-transform: rotate(".$rotation."deg);";
			
			//adjust dimentions if rotated
			if($orientation=="E" || $orientation=="W")
			{
				$relativeWidth= 100*(($width/$parentDepth)*($parentDepth/$parentWidth));
				$relativeDepth = 100*(($depth/$parentWidth)*($parentWidth/$parentDepth));
			}
			else
			{
				$relativeWidth = 100*$width/$parentWidth;
				$relativeDepth= 100*$depth/$parentDepth;
			}
			
			$roomCustomStyle = "";
			$roomCustomHTML = "";
			if($custAccess=="T")//define room color
				$roomTypeClass = "caBackground";
			else
				$roomTypeClass = "roomBackground";
			
			//custom layouts
			if($roomID==2)
			{//ca 1
				$cornerWidthInset = -16.272;//percent inset corner
				$cornerDepthInset = 50;
				CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span>$name</span>\n";
			}
			else if($roomID==3)
			{//ca 2
				//percent inset corner - close left corner
				$cornerWidthInset = 59.82;
				$cornerDepthInset = -23.48;
				CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span>$name</span>\n";
			}
			else if($roomID==4)
			{//ca 3
				//percent inset corner - close left corner
				$cornerWidthInset = 55.20;
				$cornerDepthInset = -19.27;
				CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span>$name</span>\n";
			}
			else if($roomID==5)
			{//ca 4
				$cornerWidthInset = -8.067;
				$cornerDepthInset = -9.55;
				CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span>$name</span>\n";
			}
			else if($roomID==6)
			{//ca 5
				$cornerWidthInset = -49.13;//percent inset corner
				$cornerDepthInset = 31.92;
				CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span>$name</span>\n";
			}
			else if($roomID==10)
			{//ups 1
				$cornerWidthInset = 25.159;//percent inset corner
				$cornerDepthInset = 35.263;
				CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span style='left:$cornerWidthInset%'>$name</span>\n";
			}
			else if($roomID==11)
			{//ups 2
				$cornerWidthInset = 24.855;//percent inset corner
				$cornerDepthInset = 21.875;
				CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span style='left:$cornerWidthInset%'>$name</span>\n";
			}
			else if($roomID==16)
			{//loading
				$cornerWidthInset = -75;//percent inset corner
				$cornerDepthInset = 25;
				CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span>$name</span>\n";
			}
			
			return CreateRoomLayout($roomID, $width, $depth, $name, $fullName, $relativeX, $relativeY, $relativeWidth, $relativeDepth, $rotationTransform, $roomTypeClass, $roomCustomHTML, $roomCustomStyle);
		}//end CreateRoomLayout()
	}// end DCIMCustomFunctions class
?>
