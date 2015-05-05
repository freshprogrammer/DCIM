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
			CreateRoomLayout(1, $width, $depth);
			CustomFunctions::CreateRoomLayout(5, $width, $depth);
			echo "</div>\n";
		}
		
		public static function CreateRoomLayout($roomID, $parentWidth, $parentDepth)
		{
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
			$relativeWidth = 100*$width/$parentWidth;
			$relativeDepth= 100*$depth/$parentDepth;
			
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
			echo "Room ID $roomID<BR>\n";
			echo "<div id='ca4_topWall' class='roomBorders'></div>\n";
			echo "<div id='ca4_leftWall' class='roomBorders'></div>\n";
			echo "<div id='ca4_bottomWall' class='roomBorders'></div>\n";
			echo "<div id='ca4_rightWall' class='roomBorders'></div>\n";
			echo "</div>\n";
		}
	}
?>
