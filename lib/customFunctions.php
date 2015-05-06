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

		public static function CreateSiteLayout($siteID)
		{
			//from DB in feet
			$xPos = 0;
			$yPos = 0;
			$width = 171.70;
			$depth = 143.43;
			$orientation = "N"; // North East South West
			
			//calculated
			$depthToWidthRatio = 100*$depth/$width;
			
			echo "<style>\n";
			echo "#siteContainer$siteID {\n";
			echo "	padding-bottom:$depthToWidthRatio%;\n";
			echo "}\n";
			echo "</style>\n";
			
			echo "<div id='siteContainer$siteID' class='dataceterContainer'>\n";
			echo "Site $siteID<BR>\n";
			CustomFunctions::CreateRoomLayout(1, $width, $depth);
			CustomFunctions::CreateRoomLayout(2, $width, $depth);
			CustomFunctions::CreateRoomLayout(5, $width, $depth);
			echo "</div>\n";
		}
		
		public static function CreateRoomLayout($roomID, $parentWidth, $parentDepth)
		{
			if(in_array($roomID,array(2,5)))
			{
				//custom layouts
			}
			else
				CreateRoomLayout($roomID, $parentWidth, $parentDepth); //not a custom room so use the default
			
			if($roomID==2)
			{//ca 1
				//from DB in feet
				$xPos = 68.79;
				$yPos = 74.98;
				$width = 37.32;
				$depth = 37.97;
				$orientation = "W"; // North East South West
				
				//percent inset corners
				$cornerWidthInset = 15;
				$cornerDepthInset = 75; 
				
				//calculated
				$depthToWidthRatio = 100*$depth/$width;
				$relativeX = 100*$xPos/$parentWidth;
				$relativeY= 100*$yPos/$parentDepth;

				if($orientation=="E" || $orientation=="W")
				{//will be rotated so make width relative to parents height
					$relativeWidth = 100*$width/$parentDepth;
					$relativeDepth= 100*$depth/$parentWidth;
				}
				else
				{
					$relativeWidth = 100*$width/$parentWidth;
					$relativeDepth= 100*$depth/$parentDepth;
				}
				
				echo "<style>\n";
				echo "#ca1 {\n";
				echo "	left: $relativeX%;\n";
				echo "	top: $relativeY%;\n";
				echo "	width: $relativeWidth%;\n";
				echo "	height: $relativeDepth%;\n";
				if($orientation=="W")
					echo "	transform: rotate(-90deg);\n";
				else if($orientation=="E")
					echo "	transform: rotate(90deg);\n";
				else if($orientation=="S")
					echo "	transform: rotate(180deg);\n";
				echo "	transform-origin: 0% 0%;\n";
				echo "}\n";
				
				echo "#ca1_topWall {\n";
				echo "	width: ".(100-$cornerWidthInset)."%;\n";
				echo "	border-style: solid hidden hidden hidden;\n";
				echo "}\n";
				echo "#ca1_rightWall {\n";
				echo "	top: $cornerDepthInset%;\n";
				echo "	height: ".(100-$cornerDepthInset)."%;\n";
				echo "	border-style: hidden solid hidden hidden;\n";
				echo "}\n";
				echo "#ca1_bottomWall {\n";
				echo "	border-style: hidden hidden solid hidden;\n";
				echo "}\n";
				echo "#ca1_leftWall {\n";
				echo "	border-style: hidden hidden hidden solid;\n";
				echo "}\n";
				
				echo "#ca1_rightInnerWall {\n";
				echo "	height: $cornerDepthInset%;\n";
				echo "	width: ".(100-$cornerWidthInset)."%;\n";
				echo "}\n";
				echo "#ca1_topInnerWall {\n";
				echo "	left: ".(100-$cornerWidthInset)."%;\n";
				echo "	width: $cornerWidthInset%;\n";
				echo "	height: $cornerDepthInset%;\n";
				echo "	border-bottom-style: solid;\n";
				echo "	border-left-style: solid;\n";
				echo "	border-top-style: hidden;\n";
				echo "	border-right-style: hidden;\n";
				echo "}\n";
				
				echo "#ca1_centerBackground {\n";
				echo "	width: ".(100-$cornerWidthInset)."%;\n";
				echo "}\n";
				echo "#ca1_rightBackground {\n";
				echo "	top: $cornerDepthInset%;\n";
				echo "	height: ".(100-$cornerDepthInset)."%;\n";
				echo "}\n";
				
				echo "</style>\n";
				
				echo "<div id='ca1' class='room'>\n";
				echo "<div id='ca1_centerBackground' class='roomBackground'></div>\n";
				echo "<div id='ca1_rightBackground' class='roomBackground'></div>\n";
				echo "<div id='ca1_topWall' class='roomBorders'></div>\n";
				echo "<div id='ca1_topInnerWall' class='roomBorders'></div>\n";
				echo "<div id='ca1_leftWall' class='roomBorders'></div>\n";
				echo "<div id='ca1_bottomWall' class='roomBorders'></div>\n";
				echo "<div id='ca1_rightWall' class='roomBorders'></div>\n";
				echo "</div>\n";
			}
			else if($roomID==5)
			{//ca 4
				//from DB in feet
				$xPos = 0.33;
				$yPos = 129.3;
				$width = 58.63;
				$depth = 61.94;
				$orientation = "W"; // North East South West
				
				//calculated
				$depthToWidthRatio = 100*$depth/$width;
				$relativeX = 100*$xPos/$parentWidth;
				$relativeY= 100*$yPos/$parentDepth;

				if($orientation=="E" || $orientation=="W")
				{//will be rotated so make width relative to parents height
					$relativeWidth = 100*$width/$parentDepth;
					$relativeDepth= 100*$depth/$parentWidth;
				}
				else
				{
					$relativeWidth = 100*$width/$parentWidth;
					$relativeDepth= 100*$depth/$parentDepth;
				}
				
				echo "<style>\n";
				echo "#ca4 {\n";
				echo "	left: $relativeX%;\n";
				echo "	top: $relativeY%;\n";
				echo "	width: $relativeWidth%;\n";
				echo "	height: $relativeDepth%;\n";
				if($orientation=="W")
					echo "	transform: rotate(-90deg);\n";
				else if($orientation=="E")
					echo "	transform: rotate(90deg);\n";
				else if($orientation=="S")
					echo "	transform: rotate(180deg);\n";
				echo "	transform-origin: 0% 0%;\n";
				echo "}\n";
				echo "#ca4_topWall {\n";
				echo "	left: 10%;\n";
				echo "	width: 90%;\n";
				echo "	border-style: solid hidden hidden hidden;\n";
				echo "}\n";
				echo "#ca4_rightWall {\n";
				echo "	border-style: hidden solid hidden hidden;\n";
				echo "}\n";
				echo "#ca4_bottomWall {\n";
				echo "	border-style: hidden hidden solid hidden;\n";
				echo "}\n";
				echo "#ca4_leftWall {\n";
				echo "	top: 10%;\n";
				echo "	height: 90%;\n";
				echo "	border-style: hidden hidden hidden solid;\n";
				echo "}\n";
				echo "</style>\n";
				
				echo "<div id='ca4' class='room'>\n";
				//echo "Room ID $roomID<BR>\n";
				echo "<div id='ca4_topWall' class='roomBorders'></div>\n";
				echo "<div id='ca4_leftWall' class='roomBorders'></div>\n";
				echo "<div id='ca4_bottomWall' class='roomBorders'></div>\n";
				echo "<div id='ca4_rightWall' class='roomBorders'></div>\n";
				echo "</div>\n";
			}
		}
	}
?>
