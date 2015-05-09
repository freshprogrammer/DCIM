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
			
			$rotationTransform = "";
			if($orientation=="W")
				$rotationTransform = "	transform: rotate(-90deg);\n";
			else if($orientation=="E")
				$rotationTransform = "	transform: rotate(90deg);\n";
			else if($orientation=="S")
				$rotationTransform = "	transform: rotate(180deg);\n";
			
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
			
			if(!in_array($roomID,array(2,5)))
				CreateGenericRoomLayout($roomID, $name , $fullName, $custAccess, $relativeX, $relativeY, $relativeWidth, $relativeDepth, $rotationTransform, $layer); //not a custom room so use the default
			else
			{//custom layouts
				if($roomID==2)
				{//ca 1
					//percent inset corners
					$cornerWidthInset = 15;
					$cornerDepthInset = 50; 
					
					echo "<style>\n";
					echo "#ca1 {\n";
					echo "	left: $relativeX%;\n";
					echo "	top: $relativeY%;\n";
					echo "	width: $relativeWidth%;\n";
					echo "	height: $relativeDepth%;\n";
					echo "	z-index: $layer;\n";
					echo $rotationTransform;
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
					
					echo "<a href='./?roomid=$roomID'>\n";
					echo "<div id='ca1' class='room'>\n";
					echo "<div id='ca1_centerBackground' class='roomBackground'></div>\n";
					echo "<div id='ca1_rightBackground' class='roomBackground'></div>\n";
					echo "<div id='ca1_topWall' class='roomBorders'></div>\n";
					echo "<div id='ca1_topInnerWall' class='roomBorders'></div>\n";
					echo "<div id='ca1_leftWall' class='roomBorders'></div>\n";
					echo "<div id='ca1_bottomWall' class='roomBorders'></div>\n";
					echo "<div id='ca1_rightWall' class='roomBorders'></div>\n";
					echo "</div>\n";
					echo "</a>\n";
				}
				else if($roomID==5)
				{//ca 4
					echo "<style>\n";
					echo "#ca4 {\n";
					echo "	left: $relativeX%;\n";
					echo "	top: $relativeY%;\n";
					echo "	width: $relativeWidth%;\n";
					echo "	height: $relativeDepth%;\n";
					echo "	z-index: $layer;\n";
					echo $rotationTransform;
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
					
					echo "<a href='./?roomid=$roomID'>\n";
					echo "<div id='ca4' class='room'>\n";
					//echo "Room ID $roomID<BR>\n";
					echo "<div id='' class='roomBackground'></div>\n";
					echo "<div id='ca4_topWall' class='roomBorders'></div>\n";
					echo "<div id='ca4_leftWall' class='roomBorders'></div>\n";
					echo "<div id='ca4_bottomWall' class='roomBorders'></div>\n";
					echo "<div id='ca4_rightWall' class='roomBorders'></div>\n";
					echo "</div>\n";
					echo "</a>\n";
				}//room layout
			}//custom room
		}
	}
?>
