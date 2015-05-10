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
			
			if($custAccess=="T")//define room color
				$roomClass= "caBackground";
			else
				$roomClass= "roomBackground";
			
			if(!in_array($roomID,array(2,5,6)))
				CreateGenericRoomLayout($roomID, $name , $fullName, $relativeX, $relativeY, $relativeWidth, $relativeDepth, $rotationTransform, $layer, $roomClass); //not a custom room so use the default
			else
			{//custom layouts
				$roomCustomStyle = "";
				$roomCustomHTML = "";
				if($roomID==2)
				{//ca 1
					//percent inset corner
					$cornerWidthInset = 15;
					$cornerDepthInset = 50; 
					
					$roomCustomStyle .= "#ca1_topWall {\n";
					$roomCustomStyle .= "	width: ".(100-$cornerWidthInset)."%;\n";
					$roomCustomStyle .= "	border-style: solid hidden hidden hidden;\n";
					$roomCustomStyle .= "}\n";
					$roomCustomStyle .= "#ca1_rightWall {\n";
					$roomCustomStyle .= "	top: $cornerDepthInset%;\n";
					$roomCustomStyle .= "	height: ".(100-$cornerDepthInset)."%;\n";
					$roomCustomStyle .= "	border-style: hidden solid hidden hidden;\n";
					$roomCustomStyle .= "}\n";
					$roomCustomStyle .= "#ca1_bottomWall {\n";
					$roomCustomStyle .= "	border-style: hidden hidden solid hidden;\n";
					$roomCustomStyle .= "}\n";
					$roomCustomStyle .= "#ca1_leftWall {\n";
					$roomCustomStyle .= "	border-style: hidden hidden hidden solid;\n";
					$roomCustomStyle .= "}\n";
					
					$roomCustomStyle .= "#ca1_rightInnerWall {\n";
					$roomCustomStyle .= "	height: $cornerDepthInset%;\n";
					$roomCustomStyle .= "	width: ".(100-$cornerWidthInset)."%;\n";
					$roomCustomStyle .= "}\n";
					$roomCustomStyle .= "#ca1_topInnerWall {\n";
					$roomCustomStyle .= "	left: ".(100-$cornerWidthInset)."%;\n";
					$roomCustomStyle .= "	width: $cornerWidthInset%;\n";
					$roomCustomStyle .= "	height: $cornerDepthInset%;\n";
					$roomCustomStyle .= "	border-style: hidden hidden solid solid;\n";
					$roomCustomStyle .= "}\n";
					
					$roomCustomStyle .= "#ca1_centerBackground {\n";
					$roomCustomStyle .= "	width: ".(100-$cornerWidthInset)."%;\n";
					$roomCustomStyle .= "}\n";
					$roomCustomStyle .= "#ca1_rightBackground {\n";
					$roomCustomStyle .= "	top: $cornerDepthInset%;\n";
					$roomCustomStyle .= "	height: ".(100-$cornerDepthInset)."%;\n";
					$roomCustomStyle .= "}\n";
					
					$roomCustomHTML .= "<div id='ca1_centerBackground' class='$roomClass'></div>\n";
					$roomCustomHTML .= "<div id='ca1_rightBackground' class='$roomClass'></div>\n";
					$roomCustomHTML .= "<div id='ca1_topWall' class='roomBorders'></div>\n";
					$roomCustomHTML .= "<div id='ca1_topInnerWall' class='roomBorders'></div>\n";
					$roomCustomHTML .= "<div id='ca1_leftWall' class='roomBorders'></div>\n";
					$roomCustomHTML .= "<div id='ca1_bottomWall' class='roomBorders'></div>\n";
					$roomCustomHTML .= "<div id='ca1_rightWall' class='roomBorders'></div>\n";
				}
				else if($roomID==5)
				{//ca 4
					$roomCustomStyle .= "#ca4_topWall {\n";
					$roomCustomStyle .= "	left: 10%;\n";
					$roomCustomStyle .= "	width: 90%;\n";
					$roomCustomStyle .= "	border-style: solid hidden hidden hidden;\n";
					$roomCustomStyle .= "}\n";
					$roomCustomStyle .= "#ca4_rightWall {\n";
					$roomCustomStyle .= "	border-style: hidden solid hidden hidden;\n";
					$roomCustomStyle .= "}\n";
					$roomCustomStyle .= "#ca4_bottomWall {\n";
					$roomCustomStyle .= "	border-style: hidden hidden solid hidden;\n";
					$roomCustomStyle .= "}\n";
					$roomCustomStyle .= "#ca4_leftWall {\n";
					$roomCustomStyle .= "	top: 10%;\n";
					$roomCustomStyle .= "	height: 90%;\n";
					$roomCustomStyle .= "	border-style: hidden hidden hidden solid;\n";
					$roomCustomStyle .= "}\n";
					
					$roomCustomHTML .= "<div id='' class='$roomClass'></div>\n";
					$roomCustomHTML .= "<div id='ca4_topWall' class='roomBorders'></div>\n";
					$roomCustomHTML .= "<div id='ca4_leftWall' class='roomBorders'></div>\n";
					$roomCustomHTML .= "<div id='ca4_bottomWall' class='roomBorders'></div>\n";
					$roomCustomHTML .= "<div id='ca4_rightWall' class='roomBorders'></div>\n";
				}
				if($roomID==6)
				{//ca 5
					//percent inset corner
					$cornerWidthInset = 49.13;
					$cornerDepthInset = 31.92; 
					
					$roomCustomStyle .= "#ca5_topWall {\n";
					$roomCustomStyle .= "	width: ".(100-$cornerWidthInset)."%;\n";
					$roomCustomStyle .= "	border-style: solid hidden hidden hidden;\n";
					$roomCustomStyle .= "}\n";
					$roomCustomStyle .= "#ca5_rightWall {\n";
					$roomCustomStyle .= "	top: $cornerDepthInset%;\n";
					$roomCustomStyle .= "	height: ".(100-$cornerDepthInset)."%;\n";
					$roomCustomStyle .= "	border-style: hidden solid hidden hidden;\n";
					$roomCustomStyle .= "}\n";
					$roomCustomStyle .= "#ca5_bottomWall {\n";
					$roomCustomStyle .= "	border-style: hidden hidden solid hidden;\n";
					$roomCustomStyle .= "}\n";
					$roomCustomStyle .= "#ca5_leftWall {\n";
					$roomCustomStyle .= "	border-style: hidden hidden hidden solid;\n";
					$roomCustomStyle .= "}\n";
					
					$roomCustomStyle .= "#ca5_rightInnerWall {\n";
					$roomCustomStyle .= "	height: $cornerDepthInset%;\n";
					$roomCustomStyle .= "	width: ".(100-$cornerWidthInset)."%;\n";
					$roomCustomStyle .= "}\n";
					$roomCustomStyle .= "#ca5_topInnerWall {\n";
					$roomCustomStyle .= "	left: ".(100-$cornerWidthInset)."%;\n";
					$roomCustomStyle .= "	width: $cornerWidthInset%;\n";
					$roomCustomStyle .= "	height: $cornerDepthInset%;\n";
					$roomCustomStyle .= "	border-style: hidden hidden solid solid;\n";
					$roomCustomStyle .= "}\n";
					
					$roomCustomStyle .= "#ca5_centerBackground {\n";
					$roomCustomStyle .= "	width: ".(100-$cornerWidthInset)."%;\n";
					$roomCustomStyle .= "}\n";
					$roomCustomStyle .= "#ca5_rightBackground {\n";
					$roomCustomStyle .= "	top: $cornerDepthInset%;\n";
					$roomCustomStyle .= "	height: ".(100-$cornerDepthInset)."%;\n";
					$roomCustomStyle .= "}\n";
					
					$roomCustomHTML .= "<div id='ca5_centerBackground' class='$roomClass'></div>\n";
					$roomCustomHTML .= "<div id='ca5_rightBackground' class='$roomClass'></div>\n";
					$roomCustomHTML .= "<div id='ca5_topWall' class='roomBorders'></div>\n";
					$roomCustomHTML .= "<div id='ca5_topInnerWall' class='roomBorders'></div>\n";
					$roomCustomHTML .= "<div id='ca5_leftWall' class='roomBorders'></div>\n";
					$roomCustomHTML .= "<div id='ca5_bottomWall' class='roomBorders'></div>\n";
					$roomCustomHTML .= "<div id='ca5_rightWall' class='roomBorders'></div>\n";
				}//custom room visual layout
				
				echo "<style>\n";
				echo "#room$roomID {\n";
				echo "	left: $relativeX%;\n";
				echo "	top: $relativeY%;\n";
				echo "	width: $relativeWidth%;\n";
				echo "	height: $relativeDepth%;\n";
				echo "	z-index: $layer;\n";
				echo $rotationTransform;
				echo "	transform-origin: 0% 0%;\n";
				echo "}\n";
				echo $roomCustomStyle;
				echo "</style>\n";
				
				echo "<div id='room$roomID' class='room'>\n";
				echo "<a href='./?roomid=$roomID' title='$fullName'>\n";
				echo "<span style='z-index: ".($layer+1).";'>$name</span>\n";
				echo $roomCustomHTML;
				echo "</a>\n";
				echo "</div>\n";
			}//custom room
		}
	}
?>
