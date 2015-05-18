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
		
		public static function CreateInternalLink($hNo, $cNo, $includeOldLink)
		{//these are internal links for cust and device pages to other applications
			$result = "<a href='https://internalApp.com?search=$hNo' target='_blank' class='internalLink'>(main system)</a>\n";
			if($includeOldLink)
				$result .= "<a href='https://internalApp.com?cno=$cNo&search=$hNo' target='_blank' class='internalLink'>(Old system)</a>\n";
			return $result;
		}
		
		public static function CreateNavigationQuickLinks()
		{
			$result  = "<a class='navLinks' href='?roomid=2'>CA1</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?roomid=3'>CA2</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?roomid=4'>CA3</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?roomid=5'>CA4</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?roomid=6'>CA5</a>&nbsp;\n";
			return $result;
		}
		
		public static function CreateHomePageContent()
		{//just logged in - at home page
			global $siteName;
			
			$result = "<div class=\"panel\">\n";
			$result .= "<div class=\"panel-header\">\n";
			$result .= "$siteName\n";
			$result .= "</div>\n";
			
			$result .= "<div class=\"panel-body\">\n\n";
			
			$result .= CustomFunctions::CreateSiteLayout(0, "", "", 0, 0, 0, 0, "N");//this should be a lookup of all sites...
			
			if(UserHasWritePermission() && IsUserUsingDefaultPassword())
			{
				$result .= "<BR><BR>Please <a href='./?userid=$userID'>change your password</a> from the default when you get a chance.";
			}
			
			$result .= "</div>\n";
			$result .= "</div>\n";
			return $result;
		}
		
		public static function CreateSiteLayout($siteID, $name, $fullName, $xPos, $yPos, $siteWidth, $siteDepth, $orientation)
		{
			global $mysqli;
			
			//- site should really always be facing N and be at 0,0
			$orientation = "N"; // North East South West
			//from DB in feet
			$xPos = 0;
			$yPos = 0;
			$siteWidth = 171.70;
			$siteDepth = 143.43;
			$name = "Site";
			$fullName = "Site Name";
			
			$result = CustomFunctions::CreateSiteCustomLayout($siteID, $name, $fullName, $xPos, $yPos, $siteWidth, $siteDepth, $orientation);
			
			//select rooms from table for rendering each one
			$query = "SELECT roomid, name , fullname, custaccess, xpos, ypos, width, depth, orientation, layer
					FROM dcim_room
					WHERE siteid=? AND width > 0 AND depth > 0";
			
			if (!($stmt = $mysqli->prepare($query)))
			{
				//TODO handle errors better
				$result .= "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			}
			$stmt->bind_Param('i', $siteID);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($roomID, $name , $fullName, $custAccess, $xPos, $yPos, $width, $depth, $orientation, $layer);
			
			while($stmt->fetch())
			{
				$result .= "<div id='room$roomID' class='roomContainer'>\n";
				$result .= CustomFunctions::CreateRoomLayout($roomID, $siteWidth, $siteDepth, $name , $fullName, $custAccess, $xPos, $yPos, $width, $depth, $orientation, $layer);
				$result .= "</div>\n";
			}
			$result .= "</div>\n";
			return $result;
		}
		
		public static function CreateSiteCustomLayout($siteID, $name, $fullName, $xPos, $yPos, $width, $depth, $orientation)
		{
			$borderThickness = 4;
			//key percentages for drawing site rectangles
			$rightTopX = 37;
			$rightTopY = 12;
			$leftBottomY = 85.5;
			$leftBottomBottom = 91;//very bottom left side
			$rightBottomX = 57;
			$rightBottomY = 77;
			$nocWidth = 13.5;
			
			//calculated
			$depthToWidthRatio = 100*$depth/$width;//key proportian of the site
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
			$result .= "#siteContainer$siteID {\n";
			$result .= "	padding-bottom:$depthToWidthRatio%;\n";
			$result .= "}\n";
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
			$result .= "#site".$siteID."_Name {\n";
			$result .= "	left: $rightTopX%;\n";
			$result .= "}\n";
			$result .= "</style>\n";
			
			$result .= "<div id='siteContainer$siteID' class='siteContainer'>\n"; //this is closed after this functions
			$result .= "<div class='siteBackground' id='site".$siteID."_TopLeft'></div>\n";
			$result .= "<div class='siteBackground' id='site".$siteID."_Left'></div>\n";
			$result .= "<div class='siteBackground' id='site".$siteID."_LeftBottom'></div>\n";
			$result .= "<div class='siteBackground' id='site".$siteID."_RightTop'></div>\n";
			$result .= "<div class='siteBackground' id='site".$siteID."_RightBottom'></div>\n";
			$result .= "<div class='siteBackground' id='site".$siteID."_NOC'></div>\n";
			$result .= "<div class='siteBackground' id='site".$siteID."_StairsWall'></div>\n";
			$result .= "<div class='siteLayoutName' id='site".$siteID."_Name'>$fullName</div>\n";
			return $result;
		}
		
		public static function CreateRoomLayout($roomID, $parentWidth, $parentDepth, $name , $fullName, $custAccess, $xPos, $yPos, $width, $depth, $orientation, $layer)
		{
			$baseLayer = 100;
			
			//calculated
			$parentDepthToWidthRatio = $parentDepth/$parentWidth;
			$relativeX = 100*$xPos/$parentWidth;
			$relativeY= 100*$yPos/$parentDepth;
			$layer += $baseLayer;
			
			if($orientation=="W")
				$rotation = -90;
			else if($orientation=="E")
				$rotation = 90;
			else if($orientation=="S")
				$rotation = 180;
			else if($orientation=="N")
				$rotation = 0;
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
				$cornerWidthInset = 15;//percent inset corner
				$cornerDepthInset = 50;
				CreateRoomLayout_FarRightCornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span>$name</span>\n";
			}
			else if($roomID==3)
			{//ca 2
				//percent inset corner - close left corner
				$cornerWidthInset = 59.82;
				$cornerDepthInset = 23.48;
				
				$roomCustomStyle .= "#room".$roomID."_topWall {\n";
				$roomCustomStyle .= "	border-style: solid hidden hidden hidden;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_rightWall {\n";
				$roomCustomStyle .= "	border-style: hidden solid hidden hidden;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_bottomWall {\n";
				$roomCustomStyle .= "	left: $cornerWidthInset%;\n";
				$roomCustomStyle .= "	width: ".(100-$cornerWidthInset)."%;\n";
				$roomCustomStyle .= "	border-style: hidden hidden solid hidden;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_leftWall {\n";
				$roomCustomStyle .= "	height: ".(100-$cornerDepthInset)."%;\n";
				$roomCustomStyle .= "	border-style: hidden hidden hidden solid;\n";
				$roomCustomStyle .= "}\n";
				
				$roomCustomStyle .= "#room".$roomID."_leftInnerWall {\n";
				$roomCustomStyle .= "	height: $cornerDepthInset%;\n";
				$roomCustomStyle .= "	width: ".(100-$cornerWidthInset)."%;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_cornerWalls {\n";
				$roomCustomStyle .= "	top: ".(100-$cornerDepthInset)."%;\n";
				$roomCustomStyle .= "	width: $cornerWidthInset%;\n";
				$roomCustomStyle .= "	height: $cornerDepthInset%;\n";
				$roomCustomStyle .= "	border-style: solid solid hidden hidden;\n";
				$roomCustomStyle .= "}\n";
				
				$roomCustomStyle .= "#room".$roomID."_centerBackground {\n";
				$roomCustomStyle .= "	left: $cornerWidthInset%;\n";
				$roomCustomStyle .= "	width: ".(100-$cornerWidthInset)."%;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_rightBackground {\n";
				$roomCustomStyle .= "	height: ".(100-$cornerDepthInset)."%;\n";
				$roomCustomStyle .= "}\n";
				
				$roomCustomHTML .= "<div id='room".$roomID."_centerBackground' class='$roomTypeClass'></div>\n";
				$roomCustomHTML .= "<div id='room".$roomID."_rightBackground' class='$roomTypeClass'></div>\n";
				$roomCustomHTML .= "<div id='room".$roomID."_topWall' class='roomBorders'></div>\n";
				$roomCustomHTML .= "<div id='room".$roomID."_cornerWalls' class='roomBorders'></div>\n";
				$roomCustomHTML .= "<div id='room".$roomID."_leftWall' class='roomBorders'></div>\n";
				$roomCustomHTML .= "<div id='room".$roomID."_bottomWall' class='roomBorders'></div>\n";
				$roomCustomHTML .= "<div id='room".$roomID."_rightWall' class='roomBorders'></div>\n";
				$roomCustomHTML .= "<span>$name</span>\n";
			}
			else if($roomID==4)
			{//ca 3
				//percent inset corner - close left corner
				$cornerWidthInset = 55.20;
				$cornerDepthInset = 19.27;
				
				$roomCustomStyle .= "#room".$roomID."_topWall {\n";
				$roomCustomStyle .= "	border-style: solid hidden hidden hidden;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_rightWall {\n";
				$roomCustomStyle .= "	border-style: hidden solid hidden hidden;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_bottomWall {\n";
				$roomCustomStyle .= "	left: $cornerWidthInset%;\n";
				$roomCustomStyle .= "	width: ".(100-$cornerWidthInset)."%;\n";
				$roomCustomStyle .= "	border-style: hidden hidden solid hidden;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_leftWall {\n";
				$roomCustomStyle .= "	height: ".(100-$cornerDepthInset)."%;\n";
				$roomCustomStyle .= "	border-style: hidden hidden hidden solid;\n";
				$roomCustomStyle .= "}\n";
				
				$roomCustomStyle .= "#room".$roomID."_leftInnerWall {\n";
				$roomCustomStyle .= "	height: $cornerDepthInset%;\n";
				$roomCustomStyle .= "	width: ".(100-$cornerWidthInset)."%;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_cornerWalls {\n";
				$roomCustomStyle .= "	top: ".(100-$cornerDepthInset)."%;\n";
				$roomCustomStyle .= "	width: $cornerWidthInset%;\n";
				$roomCustomStyle .= "	height: $cornerDepthInset%;\n";
				$roomCustomStyle .= "	border-style: solid solid hidden hidden;\n";
				$roomCustomStyle .= "}\n";
				
				$roomCustomStyle .= "#room".$roomID."_centerBackground {\n";
				$roomCustomStyle .= "	left: $cornerWidthInset%;\n";
				$roomCustomStyle .= "	width: ".(100-$cornerWidthInset)."%;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_rightBackground {\n";
				$roomCustomStyle .= "	height: ".(100-$cornerDepthInset)."%;\n";
				$roomCustomStyle .= "}\n";
				
				$roomCustomHTML .= "<div id='room".$roomID."_centerBackground' class='$roomTypeClass'></div>\n";
				$roomCustomHTML .= "<div id='room".$roomID."_rightBackground' class='$roomTypeClass'></div>\n";
				$roomCustomHTML .= "<div id='room".$roomID."_topWall' class='roomBorders'></div>\n";
				$roomCustomHTML .= "<div id='room".$roomID."_cornerWalls' class='roomBorders'></div>\n";
				$roomCustomHTML .= "<div id='room".$roomID."_leftWall' class='roomBorders'></div>\n";
				$roomCustomHTML .= "<div id='room".$roomID."_bottomWall' class='roomBorders'></div>\n";
				$roomCustomHTML .= "<div id='room".$roomID."_rightWall' class='roomBorders'></div>\n";
				$roomCustomHTML .= "<span>$name</span>\n";
			}
			else if($roomID==5)
			{//ca 4
				/*
				$roomCustomStyle .= "#room".$roomID."_topWall {\n";
				$roomCustomStyle .= "	left: 10%;\n";
				$roomCustomStyle .= "	width: 90%;\n";
				$roomCustomStyle .= "	border-style: solid hidden hidden hidden;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_rightWall {\n";
				$roomCustomStyle .= "	border-style: hidden solid hidden hidden;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_bottomWall {\n";
				$roomCustomStyle .= "	border-style: hidden hidden solid hidden;\n";
				$roomCustomStyle .= "}\n";
				$roomCustomStyle .= "#room".$roomID."_leftWall {\n";
				$roomCustomStyle .= "	top: 10%;\n";
				$roomCustomStyle .= "	height: 90%;\n";
				$roomCustomStyle .= "	border-style: hidden hidden hidden solid;\n";
				$roomCustomStyle .= "}\n";
				
				$roomCustomHTML .= "<div id='' class='$roomTypeClass'></div>\n";
				$roomCustomHTML .= "<div id='room".$roomID."_topWall' class='roomBorders'></div>\n";
				$roomCustomHTML .= "<div id='room".$roomID."_leftWall' class='roomBorders'></div>\n";
				$roomCustomHTML .= "<div id='room".$roomID."_bottomWall' class='roomBorders'></div>\n";
				$roomCustomHTML .= "<div id='room".$roomID."_rightWall' class='roomBorders'></div>\n";
				$roomCustomHTML .= "<span>$name</span>\n";*/
			}
			if($roomID==6)
			{//ca 5
				$cornerWidthInset = 49.13;//percent inset corner
				$cornerDepthInset = 31.92;
				CreateRoomLayout_FarRightCornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span>$name</span>\n";
			}//custom room visual layout
			
			return CreateRoomLayout($roomID, $name , $fullName, $relativeX, $relativeY, $relativeWidth, $relativeDepth, $rotationTransform, $layer, $roomTypeClass, $roomCustomHTML, $roomCustomStyle);
		}//end CreateRoomLayout()
	}// end DCIMCustomFunctions class
?>
