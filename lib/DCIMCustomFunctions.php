<?php
	//These are the functions that can be overwritten with custom code - defaults provided here dont have to be overwritten but can be
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
		
		public static function CreateSiteLayout($siteID)
		{
			global $mysqli;
			
			//from DB in feet
			$xPos = 0;
			$yPos = 0;
			$siteWidth = 171.70;
			$siteDepth = 143.43;
			$orientation = "N"; // North East South West
			
			//calculated
			$depthToWidthRatio = 100*$siteDepth/$siteWidth;
			
			echo "<style>\n";
			echo "#siteContainer$siteID {\n";
			echo "	padding-bottom:$depthToWidthRatio%;\n";
			echo "}\n";
			echo "</style>\n";
			
			echo "<div id='siteContainer$siteID' class='dataceterContainer'>\n";
			//echo "Site $siteID<BR>\n";
			
			
			//select rooms from table
			$query = "SELECT roomid, name , fullname, custaccess, xpos, ypos, width, depth, orientation, layer
					FROM dcim_room
					WHERE siteid=? AND width > 0 AND depth > 0";
			
			if (!($stmt = $mysqli->prepare($query)))
			{
				//TODO handle errors better
				echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			}
			$stmt->bind_Param('i', $siteID);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($roomID, $name , $fullName, $custAccess, $xPos, $yPos, $width, $depth, $orientation, $layer);
			
			while($stmt->fetch())
			{
				CustomFunctions::CreateRoomLayout($roomID, $siteWidth, $siteDepth, $name , $fullName, $custAccess, $xPos, $yPos, $width, $depth, $orientation, $layer);
			}
			echo "</div>\n";
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
				$roomCustomHTML .= "<span>$name</span>\n";
			}
			if($roomID==6)
			{//ca 5
				$cornerWidthInset = 49.13;//percent inset corner
				$cornerDepthInset = 31.92;
				CreateRoomLayout_FarRightCornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass,$roomCustomStyle,$roomCustomHTML);
				$roomCustomHTML .= "<span>$name</span>\n";
			}//custom room visual layout
			
			CreateRoomLayout($roomID, $name , $fullName, $relativeX, $relativeY, $relativeWidth, $relativeDepth, $rotationTransform, $layer, $roomTypeClass, $roomCustomHTML, $roomCustomStyle);
		}//end CreateRoomLayout()
	}// end DCIMCustomFunctions class
?>
