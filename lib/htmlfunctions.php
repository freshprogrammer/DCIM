<?php
	/* These functions create HTML for the page - Forms, Lists, Layouts, Whole pages */
	function LoginPrompt()
	{
		//would set $focusSearch=false here if search was visible while at this prompt
		
		$dest= "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		
		echo "<div class='panel'>\n";
		echo "<div class='panel-header'>Login<!--meh='$dest' --></div>\n";
		echo "<div class='panel-body'>\n\n";
		
		?>Please Log In<BR>
		<form name="login" action="<?php echo $dest;?>" method=post>
		<table>
			<TR><td align=right>Username:</td><td><input type="text" name="logInUserName" value="<?php echo GetInput("logInUserName"); ?>"></td></tr>
			<TR><td align=right>Password:</td><td><input type="password" name="logInPassword"></td></tr>
			<TR><td colspan=2 align=center><input type="submit" value="Submit"></td></tr>
		</table>
		</form>
		<script>
			document.forms['login'].elements['logInUserName'].focus();
		</script>
		<BR>
		<?php

		//reset password link?
		
		echo "</div>\n";
		echo "</div>\n";
	}
	
	function ShowLocationPage($input)
	{
		global $mysqli;
		global $deviceModels;
		global $pageSubTitle;
		global $focusSearch;
		
		$addLocation = (int)$input==-1;
		
		//UNITS desc from 42 to 1 in most cabs
		
		//top panel - location info / form / search fail 
		echo "<div class='panel'>\n";
		echo "<div class='panel-header'>Location</div>\n";
		echo "<div class='panel-body'>\n\n";
		
		//location page notes
		//
		// need/todo - draw whole cab with empty spaces and rowspans
		// server cell css - support server image - contains device details
		//
		//-click to go to device
		// devices at unit 0 are listed at top
		// -if device at this unit then asses it (check for size > 1U) - span as necisary
		// ---pre calc deviice  ranges
		//
		//
		
		$query = "SELECT s.siteid, s.name AS site, r.roomid, r.name, r.fullName, l.locationid, l.name, l.altname, l.type, l.units, l.orientation, l.keyno, l.allocation, l.order, l.xpos, l.ypos, l.width, l.depth, l.note, l.edituser, l.editdate, l.qauser, l.qadate
			FROM dcim_location AS l
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE l.locationid=?";
		
		if (!($stmt = $mysqli->prepare($query))) 
		{
			//TODO hadnle errors better
			echo "ShowLocationPage Prepare 1 failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		$stmt->bind_Param('i', $input);
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($siteID, $site, $roomID, $room, $roomFullName, $locationID, $location, $altName, $type, $units, $orientation, $keyno, $allocation, $order, $xPos, $yPos, $width, $depth, $note, $editUserID, $editDate, $qaUserID, $qaDate);
		$locationFound = $stmt->num_rows==1;
		
		if($locationFound)
		{
			$stmt->fetch();
			$fullLocationName = FormatLocation($site, $room, $location);
			
			if(CustomFunctions::UserHasLocationPermission() || CustomFunctions::UserHasPowerCircuitPermission())
			{
				echo "<script src='lib/js/customerEditScripts.js'></script>\n";	
			}
			
			$pos = FormatSizeInFeet($xPos,$yPos);
			$size = FormatSizeInFeet($width,$depth);
			
			echo "<table width=100%><tr>\n";
			echo "<td align='left'>\n";
			echo "<span class='customerName'>$fullLocationName</span>\n";
			echo "</td>\n";
			
			echo "<td align='right'>\n";
			//edit Locationbutton - not visible till in edit mode
			if(CustomFunctions::UserHasLocationPermission())
			{
				$jsSafeName = MakeJSSafeParam($location);
				$jsSafeAltName = MakeJSSafeParam($altName);
				$jsSafeNote = MakeJSSafeParam($note);
				$jsSafeKeyno = MakeJSSafeParam($keyno);
				//add, locationID, roomID, name, altName, type, units, orientation, keyno, allocation, order, x, y, width, depth, note)
				$params = "false, $locationID, $roomID, '$jsSafeName', '$jsSafeAltName', '$type', $units, '$orientation', '$jsSafeKeyno', '$allocation', '$order', $xPos, $yPos, $width, $depth, '$jsSafeNote'";
				
				?><button type='button' class='editButtons_hidden' onclick="EditLocation(<?php echo $params;?>);">Edit Location</button>
				<?php 
			}
			//editMode button
			if(CustomFunctions::UserHasLocationPermission() || CustomFunctions::UserHasPowerCircuitPermission())
			{
				echo "<button type='button' onclick='ToggleEditMode()' style='display:inline;'>Edit Mode</button>\n";
			}
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			
			//details
			echo "<table>\n";
			echo "<tr>\n";
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Alt Name:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo MakeHTMLSafe($altName);
			echo "</td>\n";
			echo "</tr>\n";
			
			echo "<tr>\n";
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Room:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo "<a href='./?roomid=$roomID'>$roomFullName</a>";
			echo "</td>\n";
			
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Position:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo "$pos";
			echo "</td>\n";
			
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Units:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo $units;
			echo "</td>\n";
			echo "</tr>\n";
			
			echo "<tr>\n";
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Type:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo LocationType($type);
			echo "</td>\n";
			
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Size:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo "$size";
			echo "</td>\n";
			
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Orientation:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo FormatTechDetails($editUserID,$editDate,Orientation($orientation), $qaUserID, $qaDate);
			echo "</td>\n";
			echo "</tr>\n";
			
			echo "<tr>\n";
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Key#:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo MakeHTMLSafe($keyno);
			echo "</td>\n";
			
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Allocation:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo LocationAllocation($allocation);
			echo "</td>\n";
			
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Order:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo LocationOrder($order);
			echo "</td>\n";
			echo "</tr>\n";
			
			echo "<tr>\n";
			echo "<td align=right class='customerDetails' valign='top'>\n";
			echo "<b>Notes:</b>";
			echo "</td>\n";
			echo "<td valign=top align=left colspan='5'>\n";
			echo "<textarea rows=3 cols=95 readonly placeholder=''>".MakeHTMLSafe($note)."</textarea>";
			echo "</td>\n";
			
			echo "</tr></table>\n";
		}
		else 
		{
			echo "Location not found ('".MakeHTMLSafe($input)."')<BR>\n"; 
		}
		
		if(UserHasWritePermission())
		{
			EditLocationForm();
		}
		
		echo "</div>\n";
		echo "</div>\n\n";
		
		if($locationFound)
		{
			$descendingUnits = $order=="R";
			$unitStart = $units+1;//start decending 42
			$unitLimit = 1;//stop decending at 1
			$sqlOrder = "DESC";
			if(!$descendingUnits)
			{
				$unitStart = 0;
				$unitLimit = $units;
				$sqlOrder = "";
			}
			//ignore empty units on cages
			$showEmptyUnits = $type!="C";
			
			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>Location Details</div>\n";
			echo "<div class='panel-body'>\n\n";
			
			//list devices at this location - cant use notml list function because this list is ordered by unit for displaying devices in the cab as they actualy are
			$query = "SELECT s.name AS site, r.name AS room, l.locationid, l.name AS loc, 
					c.hno, c.name AS cust,
					d.deviceid, d.unit, d.name, d.altname, d.member, d.size, d.type, d.status, d.note, d.asset, d.serial, d.model, d.edituser, d.editdate, d.qauser, d.qadate
				FROM dcim_device AS d
					LEFT JOIN dcim_location AS l ON d.locationid=l.locationid
					LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
					LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
					LEFT JOIN dcim_customer AS c ON d.hno=c.hno
				WHERE l.locationid=? AND d.status='A'
				ORDER BY status, site, room, loc, unit!=0, unit $sqlOrder, name, member";
			
			
			if (!($stmt = $mysqli->prepare($query))) 
			{
				//TODO hadnle errors better
				echo "ShowLocationPage Prepare 2 failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			}
			$stmt->bind_Param('i', $locationID);
			
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($site, $room, $locationID, $location, $hNo, $customer, $deviceID, $unit, $name,$deviceAltName, $member, $size, $type, $status, $notes, $asset, $serial, $model, $editUserID, $editDate, $qaUserID, $qaDate);
			$count = $stmt->num_rows;
			
			
			echo "<span class='tableTitle'>Devices</span>\n";
			echo "<BR>\n";
			
			if($count>0)
			{
				echo CreateDataTableHeader(array("Unit","Customer","Device","Model","Size","Type","Status","Note"),true);
				
				//list result data
				$oddRow = false;
				$lastUnit = $unitStart;
				while ($stmt->fetch())
				{
					if($unit==0 && $count==1)//if there is only one device at 0 skip the rest - colo
						$showEmptyUnits = false;
					
					if($unit!=0 && $showEmptyUnits)
					{
						while($lastUnit!=$unit && $lastUnit>-100 && $lastUnit<200)
						{
							if($descendingUnits)
								$lastUnit--;
							else
								$lastUnit++;
							if($lastUnit!=$unit)
							{
								$oddRow = !$oddRow;
								if($oddRow) $rowClass = "dataRowOne";
								else $rowClass = "dataRowTwo";
								//empty unit
								echo "<tr class='$rowClass'>";
								echo "<td class='data-table-cell'>$lastUnit</td>";
								echo "<td class='data-table-cell' colspan=8></td>";
								echo "</tr>";
							}
						}
					}
					
					$oddRow = !$oddRow;
					if($oddRow) $rowClass = "dataRowOne";
					else $rowClass = "dataRowTwo";
					
					//XXX probbable bug - if truncation happend in the middle of a &lt; tag
					$visibleNotes = TruncateWithSpanTitle(htmlspecialchars(MakeHTMLSafe($notes)));
					$deviceFullName = GetDeviceFullName($name, $model, $member,$deviceAltName, true);

					$unitSize=1;
					if($size[strlen($size)-1]=="U" && $status=="A")
					{
						$unitSize = substr($size,0,strlen($size)-1);
						if($unitSize<1)
							$unitSize=1;
					}
					
					echo "<tr class='$rowClass'>";
					echo "<td class='data-table-cell'>$unit</td>";
					echo "<td class='data-table-cell' rowspan=$unitSize><a href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a></td>";
					echo "<td class='data-table-cell' rowspan=$unitSize><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>";
					echo "<td class='data-table-cell' rowspan=$unitSize>$model</td>";
					echo "<td class='data-table-cell' rowspan=$unitSize>$size</td>";
					echo "<td class='data-table-cell' rowspan=$unitSize>".DeviceType($type)."</td>\n";
					echo "<td class='data-table-cell' rowspan=$unitSize>".DeviceStatus($status)."</td>\n";
					echo "<td class='data-table-cell' rowspan=$unitSize>$visibleNotes</td>";
					echo "<td class='data-table-cell' rowspan=$unitSize>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
					echo "</tr>";
					
					if($unitSize>1)
					{
						if($descendingUnits)
						{
							for ($i = $unit-1; $i >= $unit-$unitSize+1; $i--)
							{
								$oddRow = !$oddRow;
								if($oddRow) $rowClass = "dataRowOne";
								else $rowClass = "dataRowTwo";
								echo "<tr class='$rowClass'>";
								echo "<td class='data-table-cell'>$i</td>";
								echo "</tr>";
							}
						}
						else
						{
							for ($i = $unit+1; $i <= $unit+$unitSize-1; $i++)
							{
								$oddRow = !$oddRow;
								if($oddRow) $rowClass = "dataRowOne";
								else $rowClass = "dataRowTwo";
								echo "<tr class='$rowClass'>";
								echo "<td class='data-table-cell'>$i</td>";
								echo "</tr>";
							}
						}
					}

					if($unit!=0)
					{
						if($descendingUnits)
							$lastUnit=$unit-$unitSize+1;
						else
							$lastUnit=$unit+$unitSize-1;
					}
				}
				while($showEmptyUnits && $lastUnit!=$unitLimit && $lastUnit>-100 && $lastUnit<200)
				{
					if($descendingUnits)
						$lastUnit--;
					else
						$lastUnit++;
					$oddRow = !$oddRow;
					if($oddRow) $rowClass = "dataRowOne";
					else $rowClass = "dataRowTwo";
					//empty unit
					echo "<tr class='$rowClass'>";
					echo "<td class='data-table-cell'>$lastUnit</td>";
					echo "<td class='data-table-cell' colspan=8></td>";
					echo "</tr>";
				}
				echo "</table>";
			}
			else 
			{
				//TODO fix this to say something better - some locations dont have units or no devices
				echo "No devices found at this location.<BR>\n";
			}
			
			echo "<BR>\n";
			
			//list power circuits
			ListPowerCircuits("L",$locationID, -1);
			
			echo "</div>\n";
			echo "</div>\n";
			
			if(CustomFunctions::UserHasLocationPermission() || CustomFunctions::UserHasPowerCircuitPermission())
			{
				//initialize page JS
				echo "<script type='text/javascript'>InitializeEditButton();</script>\n";
				if($addLocation)//populate and make visible
				{
					/* dont think this page is accessable - and JS is outdated
					$pageSubTitle = "Add Location";
					echo "<script type='text/javascript'>EditLocation(true,'','','','','A');</script>\n";
					$focusSearch = false;
					
					*/
				}
				else 
				{
					$pageSubTitle = MakeHTMLSafe($fullLocationName);
				}
			}
		}//location found
		//return $count;
	}
	
	function EditLocationForm()
	{
		global $errorMessage;
		global $mysqli;
		
		//-default values - never seen
		$action = "";
		$actionText = "Addy";
		$roomIDInput = 2;
		$nameInput = "location";
		$altNameInput = "alt Name input";
		$typeInput = "F";
		$unitsInput = 6;
		$xPosInput = 211.11;
		$yPosInput = 311.11;
		$orientationInput = "E";
		$widthInput = 411.11;
		$depthInput = 511.11;
		$noteInput = "notes input";
		$keynoInput = "key";
		
		$sizeInput = "654321-1";//drop this
		$statusInput = "A";//drop this
		$visibleInput = "F";

		//build location combo options
		$locationOptions = "";
		$query = "SELECT s.siteid, s.name, r.roomid, r.name, r.fullname
			FROM dcim_room AS r
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			ORDER BY s.name, r.name";
			
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "EditLocationForm() Prepare 1 failed: (" . $mysqli->errno . ") " . $mysqli->error;
		}
		else
		{
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($siteID, $site, $roomID, $room, $roomFullName);
			$roomOptions = "";
			while ($stmt->fetch())
			{
				$fullRoomName = "$site $roomFullName";
				$selected = ($roomID==$roomIDInput ? "Selected" : "");
				$roomOptions .= "<option value='$roomID' $selected>$fullRoomName</option>\n";
			}
		}
		
		?>
		<div id='EditLocationMsg' class='hidden'></div>
		<div id='EditLocationEntry' class='hidden'>
		<BR>
		<table><tr><td>
		<form action="<?php echo $action;?>" method='post' id='EditLocationForm' onsubmit='return SaveLocation()' class=''>
			<fieldset>
				<legend id=EditLocationEntryLegend><b><?php echo $actionText;?> Location</b></legend>
				<table>
					<tr>
						<td colspan=1 align=right>Room:</td>
						<td align='left'>
							<select id=EditLocation_roomid name="roomid" tabindex=1>
								<?php echo $roomOptions; ?>
							</select>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Name:</td>
						<td align='left'>
							<input id=EditLocation_name type='text' tabindex=2 size=18 name='name' value='<?php echo $nameInput;?>' placeholder="10.01, G2" class='' >
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Alt&nbsp;Name(s):</td>
						<td align='left'>
							<input id=EditLocation_altname type='text' tabindex=3 size=18 name='altname' value='<?php echo $altNameInput;?>' placeholder="10.01.A, G3, Cloud" class='' >
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Type:</td>
						<td align='left'>
							<select id=EditLocation_type onchange='EditLocationTypeChanged()' name="type" tabindex=4>
								<option value="C" <?php if($typeInput==="C") echo "Selected"; ?>>Cage</option>
								<option value="F" <?php if($typeInput==="F") echo "Selected"; ?>>Full Cab</option>
								<option value="H" <?php if($typeInput==="H") echo "Selected"; ?>>Half Cab</option>
								<option value="M" <?php if($typeInput==="M") echo "Selected"; ?>>Misc</option>
								<option value="R" <?php if($typeInput==="R") echo "Selected"; ?>>Rack</option>
							</select>
							Allocation:
							<select id=EditLocation_allocation name="allocation" tabindex=5>
								<option value="E" <?php if($typeInput==="E") echo "Selected"; ?>>Empty</option>
								<option value="C" <?php if($typeInput==="C") echo "Selected"; ?>>Colo</option>
								<option value="I" <?php if($typeInput==="I") echo "Selected"; ?>>Internal</option>
								<option value="M" <?php if($typeInput==="M") echo "Selected"; ?>>Managed</option>
							</select>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Units:</td>
						<td align='left'>
							<input id=EditLocation_units type='number' tabindex=6 size=6 name='units' min='0' max='50' step='1' value='<?php echo $unitsInput;?>' placeholder='42' class=''>
							Order:
							<div class='inputToolTipContainer'>
								<select id=EditLocation_order onchange='' name="order" tabindex=7>
									<option value="N" <?php if($orientationInput==="N") echo "Selected"; ?>>Normal</option>
									<option value="R" <?php if($orientationInput==="R") echo "Selected"; ?>>Reversed</option>
								</select>
							<span class=inputTooltip>Numbered top to bottom or reversed.</span></div>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Orientation:</td>
						<td align='left'>
							<div class='inputToolTipContainer'>
								<select id=EditLocation_orientation onchange='' name="orientation" tabindex=8>
									<option value="N" <?php if($orientationInput==="N") echo "Selected"; ?>>Normal</option>
									<option value="E" <?php if($orientationInput==="E") echo "Selected"; ?>>Right</option>
									<option value="S" <?php if($orientationInput==="S") echo "Selected"; ?>>Backwards</option>
									<option value="W" <?php if($orientationInput==="W") echo "Selected"; ?>>Left</option>
								</select>
							<span class=inputTooltip>When looking at location in room, relative orientation to room.</span></div>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Left:</td>
						<td align='left'>
							<div class='inputToolTipContainer'>
								<input id=EditLocation_xpos type='number' tabindex=9 size=3 min='-9999.99' max='9999.99' step='0.01' name='xpos' value='<?php echo $xPosInput;?>' placeholder="12.34" class='' >
							<span class=inputTooltip>Distance from left room edge to back left corner of location in feet (negative for distance from right wall)</span></div>
							Foreward:
							<div class='inputToolTipContainer'>
								<input id=EditLocation_ypos type='number' tabindex=10 size=3 min='-9999.99' max='9999.99' step='0.01' name='ypos' value='<?php echo $yPosInput;?>' placeholder="12.34" class='' >
							<span class=inputTooltip>Distance from far room edge to back left corner of location in feet (negative for distance from close wall)</span></div>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Width:</td>
						<td align='left'>
							<div class='inputToolTipContainer'>
								<input id=EditLocation_width type='number' tabindex=11 size=3 min='0' max='9999.99' step='0.01' name='width' value='<?php echo $widthInput;?>' placeholder="12.34" class='' >
							<span class=inputTooltip>In feet</span></div>
							Depth:
							<div class='inputToolTipContainer'>
								<input id=EditLocation_depth type='number' tabindex=12 size=3 min='0' max='9999.99' step='0.01' name='depth' value='<?php echo $depthInput;?>' placeholder="12.34" class='' >
							<span class=inputTooltip>In feet</span></div>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Key#:</td>
						<td align='left'>
							<input id=EditLocation_keyno type='text' tabindex=13 size=18 name='keyno' value='<?php echo $keynoInput;?>' placeholder="master" class='' >
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Notes:</td>
						<td align='left'>
							<input id=EditLocation_note type='text' tabindex=14 size=50 name='notes' value='<?php echo $noteInput;?>' placeholder='Notes' class=''>
						</td>
					</tr>
					<tr>
						<td colspan='2'>
							<table style='width:100%;'>
								<tr>
									<td align=left>
										<button id='EditLocation_deletebtn' type='button' onclick='DeleteLocation()' tabindex=14>Delete</button>
									</td>
									<td align='right'>
										<button type='button' onclick='HideAllEditForms()' tabindex=16>Cancel</button>
										<input type='submit' value='Save' tabindex=15>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
				<input id=EditLocation_locationid type='hidden' name='locationid' value=-1>
				<input id=EditLocation_action type='hidden' name='action' value='null'>
				<input type="hidden" name="page_instance_id" value="<?php echo end($_SESSION['page_instance_ids']); ?>"/>
			</fieldset>
		</form>
		</td></tr></table></div>
		<?php
	}
	
	function ShowUsersPage($input)
	{
		global $userID;
		global $mysqli;
		global $pageSubTitle;
		global $errorMessage;
		
		if(UserHasAdminPermission())
		{
			$pageSubTitle = "Accounts"; 
			
			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>User List</div>\n";
			echo "<div class='panel-body'>\n\n";
			
			$query = "SELECT u.siteid, s.name, u.userid, u.username, u.name, u.initials, u.permission, u.lastactivity, u.edituser, u.editdate
				FROM dcim_user AS u
					LEFT JOIN dcim_site AS s ON s.siteid=u.siteid
				ORDER BY s.name, u.name";
			
			if (!($stmt = $mysqli->prepare($query)) || !$stmt->execute())
				$errorMessage[] = "ShowUserPage() Prepare 2 failed: (" . $mysqli->errno . ") " . $mysqli->error;
			else
			{
				$stmt->store_result();
				$stmt->bind_result($dbSiteID, $dbSiteName, $dbUserID, $dbUserName, $dbName, $dbInitials, $dbPermission, $dbLastActivity, $editUserID, $editDate);
				$count = $stmt->num_rows;
				
				echo "<span class='tableTitle'>Users</span>\n";
				//Add User button here?
				echo "<BR>\n";
				
				if($count>0)
				{
					echo CreateDataTableHeader(array("Site","Name","User Name","Initials","Permission","Last Activity"),true);
					
					//list result data
					$oddRow = false;
					while ($stmt->fetch())
					{
						$oddRow = !$oddRow;
						if($oddRow) $rowClass = "dataRowOne";
						else $rowClass = "dataRowTwo";
						
						echo "<tr class='$rowClass'>";
						echo "<td class='data-table-cell'><a href='./?siteid=$dbSiteID'>".MakeHTMLSafe($dbSiteName)."</a></td>";
						echo "<td class='data-table-cell'><a href='./?userid=$dbUserID'>".MakeHTMLSafe($dbName)."</a></td>";
						echo "<td class='data-table-cell'>".MakeHTMLSafe($dbUserName)."</td>";
						echo "<td class='data-table-cell'>$dbInitials</td>";
						echo "<td class='data-table-cell'>".DescribeUserPermissionLevel($dbPermission,true,true)."</td>";
						echo "<td class='data-table-cell'>$dbLastActivity</td>";
						echo "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate)."</td>";
						echo "</tr>";
					}
					echo "</table>";
				}
				else
					echo "No users found. Ummmm....<BR>\n";//shouldn't be possible
			}
			
			/*if(UserHasWritePermission())
			{	
				$action = "./?host=$input";
				EditDeviceForm($action);
			}*/
			echo "</div>\n";
			echo "</div>\n\n";
			echo "<BR>\n";
		}
		else
		{
			$pageSubTitle = "Account";
		}
		
/////////////////user details
		$maxPasswordLength = 15;//totly arbitrary, MD5 conversion so len doesnt really matter
		
		//top panel - cust info / form / search fail 
		echo "<div class='panel'>\n";
		echo "<div class='panel-header'>User Info</div>\n";
		echo "<div class='panel-body'>\n\n";
		
		$query = "SELECT userid, username, name, email, initials, note, permission, lastactivity, edituser, editdate
			FROM dcim_user
			WHERE userid=?
			LIMIT 1";
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $input) || !$stmt->execute())
		{
			$errorMessage[] = "ShowUserPage Prepare 1 failed: (" . $mysqli->errno . ") " . $mysqli->error;
		}
		$stmt->store_result();
		$stmt->bind_result($dbUserID, $dbUserName, $name, $email, $initials, $note, $dbPermission, $lastActivity, $editUserID, $editDate);
		$count = $stmt->num_rows;
		
		if($count==1)
		{
			//show User data
			$stmt->fetch();
			
			$editingSelf = $dbUserID==$userID;
			
			$validateOldPas = true;
			if(!$editingSelf && UserHasAdminPermission())
				$validateOldPas = false;
			
			echo "<span class='tableTitle'>".MakeHTMLSafe($name)." - ".MakeHTMLSafe($dbUserName)." - $initials - ID#$dbUserID</span><BR>\n";
			echo "Permmision: ".DescribeUserPermissionLevel($dbPermission,false,UserHasAdminPermission())."<BR>\n";
			echo "Last Activity: $lastActivity<BR>\n";
			
			if(UserHasAdminPermission() ||($editingSelf && UserHasWritePermission()))
			{
				//change pass form
				//if not validdate old pas (admin changing someomen elses pass) then hide option and pass some crap as old pas to skip JS tests
				?>
					<BR>
	<script src='lib/js/customerEditScripts.js'></script>
	<div id='EditUserPasswordMsg' class='hidden'></div>
	<div id='EditUserPasswordEntry' class=''><table><tr><td>
	<form action="./?userid=<?php echo $input;?>" method='post' id='EditUserPasswordForm' onsubmit='return SavePassword()' class=''>
		<fieldset>
			<legend id=EditUserPasswordEntryLegend><b>Change Password</b></legend>
			<table>
				<tr>
					<td align='right'><?php 
		if(!$validateOldPas) 
			echo "Your Admin Password"; 
		else 
			echo "Current Password:"; ?></td>
					<td width=1>
						<input id=EditUserPassword_oldpas type='password' name='oldpas' maxlength=<?php echo $maxPasswordLength;?> value='' placeholder='x+Z*k^2+secure' class=''>
					</td>
				</tr>
				<tr>
					<td align='right'>New Password:</td>
					<td width=1>
						<input id=EditUserPassword_newpas1 type='password' name='newpas1' maxlength=<?php echo $maxPasswordLength;?> value='' placeholder='x+Z*k^2+secure' class=''>
					</td>
				</tr>
				<tr>
					<td align='right'>New Password Again:</td>
					<td width=1>
						<input id=EditUserPassword_newpas2 type='password' name='newpas2' maxlength=<?php echo $maxPasswordLength;?> value='' placeholder='x+Z*k^2+secure' class=''>
					</td>
				</tr>
				<tr>
					<td colspan='2' align='right'>
						<input type="submit" value="Save">
					</td>
				</tr>
			</table>
			<?php 
		if(!$validateOldPas) 
			echo "<input id=EditUserPassword_username type='hidden' name='username' value='".MakeHTMLSafe($dbUserName)."'>";?>
			<input id=EditUserPassword_userid type='hidden' name='userpasid' value=<?php echo $dbUserID; ?>>
			<input id=EditUserPassword_action type='hidden' name='action' value='UserPassword_Update'>
			<input type="hidden" name="page_instance_id" value="<?php echo end($_SESSION['page_instance_ids']); ?>" />
		</fieldset>
	</form>
	</td></tr></table><BR></div>
				<?php 
			}
		}
		else 
		{
			echo "User not Found\n";
		}
		echo "</div>\n";
		echo "</div>\n\n";
	}
	
	function ShowCustomerPage($hNo)
	{
		global $mysqli;
		global $pageSubTitle;
		global $focusSearch;
		global $config_badgesEnabled;
		global $config_subnetsEnabled;
		
		//main customer record or new
		$addCust = $hNo==="-1";
		$pageSubTitle = "";
		$custCount = 0;
		
		//look for 1 customer
		if(!$addCust)
		{
			$query = "SELECT hno,cno,name,note,status,edituser,editdate,qauser,qadate 
				FROM dcim_customer 
				WHERE hno=?";
			
			if (!($stmt = $mysqli->prepare($query)))
			{
				//TODO handle errors better
				echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			}
			$search = $hNo;//make backup of search val incase no match is found problems can be traced
			$stmt->bind_Param('s', $hNo);		
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($hNo,$cNo,$customer,$note,$status,$editUserID,$editDate, $qaUserID, $qaDate);
			$custCount = $stmt->num_rows;
		}
		
		//top panel - cust info / form / search fail 
		echo "<div class='panel'>\n";
		echo "<div class='panel-header'>Customer</div>\n";
		echo "<div class='panel-body'>\n\n";
		
		if($custCount>=1 || ($addCust && UserHasWritePermission()))
		{
			//hNo is propper H# or -1 for add cust (or failed direct search)
			if(UserHasWritePermission())
			{
				echo "<script src='lib/js/customerEditScripts.js'></script>\n";	
			}
			
			if(!$addCust)
			{
				$stmt->fetch();
				
				//this is the customer page - build it...
				echo "<table width=100%>\n";
				echo "<tr>\n";
				echo "<td valign=top>\n";
				echo "<span class='customerName'>".MakeHTMLSafe($customer)."</span>\n";
				echo CustomFunctions::CreateInternalLink($hNo, $cNo, true);
			
				echo "</td>\n";
				echo "<td valign=top align=right>\n";
				//edit Customer button - not visible till in edit mode
				if(UserHasWritePermission())
				{
					echo "<div id='customerDecomHelpPopup' class='helpPopup'>".CustomerDecomHelpPopup()."</div>";
					echo "<span class='editButtons_hidden'><a class='helpLink' href='javascript:void(0)' onclick = \"CreatePopup('customerDecomHelpPopup');\">Decom Help</a></span>\n";
					
					$formAction = "./?host=$hNo";
					echo CreateQACell("dcim_customer", $hNo, $formAction,$editUserID, $editDate, $qaUserID, $qaDate,false);
					
					$jsSafeCustomer = MakeJSSafeParam($customer);
					$jsSafeNote = MakeJSSafeParam($note);
					$params = "false, '$hNo', '$cNo', '$jsSafeCustomer', '$jsSafeNote', '$status'";
					?><button type='button' class='editButtons_hidden' onclick="EditCustomer(<?php echo $params;?>);">Edit Customer</button>
					<?php 
				}
				//editMode button
				if(UserHasWritePermission())
				{
					echo "<button type='button' onclick='ToggleEditMode()' style='display:inline;'>Edit Mode</button>\n";
				}
				echo "</td>\n";
				echo "</tr>\n";
				echo "</table>\n";
				echo "<table width=100%>\n";
				echo "<tr>\n";
				//cust details
				echo "<td valign=top width=105>\n";
				echo "<span class='customerDetails'>\n";
				echo "H<span id='customerHNo' onclick=\"SelectIDRange('customerHNo')\">$hNo</span>\n";
				echo "<BR>\n";
				echo "C<span id='customerCNo' onclick=\"SelectIDRange('customerCNo')\">$cNo</span>\n";
				echo "<BR>\n";
				//cust status wrapped in span tag that shows tech date on hover
				echo FormatTechDetails($editUserID,$editDate,CustomerStatus($status), $qaUserID, $qaDate)."\n";
				echo "<BR>\n";
				echo "</span>\n";
				echo "</td>\n";
				//cust notes
				echo "<td valign=top align=left>\n";
				echo "<textarea rows=3 cols=95 readonly placeholder=\"\">".MakeHTMLSafe($note)."</textarea>";
				echo "</td>\n";
				echo "</tr>\n";
				echo "</table>\n";
			}
			
			if(UserHasWritePermission())
			{	
				EditCustomerForm($hNo);
			}
			//end panel and panel body
			echo "</div>\n";
			echo "</div>\n";
		}
		else //cust not found and/or no write access 
		{
			//not found and can't create new
			echo "Customer not Found (H#$hNo)<BR>";
		}
		
		//bottom data panel
		if($custCount==1)
		{
			//data for customer
			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>Customer Details</div>\n";
			echo "<div class='panel-body'>\n\n";
				
			//show devices linked to this hNo
			$deviceCount = ListDevices(false,$hNo);
			echo "<BR>\n";
			
			//badges
			if($config_badgesEnabled)
			{
				$badgeCount = ListBadges(false, $hNo);
				echo "<BR>\n";
			}
			
			//ports
			$portCount = ListActiveCustomerDeviceConnections($hNo);
			echo "<BR>\n";
			
			//VLANs
			if($config_subnetsEnabled)
			{
				$vlanCount = ListCustomerSubnets("C",$hNo);
				echo "<BR>\n";
			}
			
			//Power Circuits of devices
			$powerCircuitsCount = ListPowerCircuits("C",$hNo, -1);
			
			//end search (or customer details) panel and panel body
		}
		echo "</div>\n";
		echo "</div>\n";

		if(UserHasWritePermission())
		{
			//initialize page JS
			echo "<script type='text/javascript'>InitializeEditButton();</script>\n";
			if($addCust)//populate and make visible
			{
				$pageSubTitle = "Add Customer";
				echo "<script type='text/javascript'>EditCustomer(true,'','','','','A');</script>\n";
				$focusSearch = false;
			}
			else 
			{
				$pageSubTitle = MakeHTMLSafe($customer);
			}
		}
	}
	
	function EditCustomerForm($hNo)
	{
		//edit/Add badge form
		//-default values - overwritten by js - never seen
		$hNoInput = "000000";
		$cNoInput = "000000";
		$nameInput = "Customer Name";
		$statusInput = "A";
		$notesInput = "notes";
		$actionText = "action";
		
		?>
		<div id='EditCustomerMsg' class='hidden'></div>
		<div id='EditCustomerEntry' class='hidden'>
		<BR><table><tr><td>
		<form action="./?host=<?php echo $hNo;?>" method='post' id='EditCustomerForm' onsubmit='return SaveCustomer()' class=''>
			<fieldset>
				<legend id=EditCustomerEntryLegend><b><?php echo $actionText;?> Customer</b></legend>
				<table>
					<tr>
						<td align='right' width=1>Hosting&nbsp;Account&nbsp;#:</td>
						<td align='left' width=1>
							<input id=EditCustomer_hno type='number' min=10000 max=999999 step=1 tabindex=1 name='hno' maxlength=6 size=6 value='<?php echo $hNoInput;?>' placeholder='000000' class='' readonly>
							Customer&nbsp;#:
							<input id=EditCustomer_cno type='number' min=10000 max=999999 step=1 tabindex=2 name='cno' maxlength=6 size=6 value='<?php echo $cNoInput;?>' placeholder='000000' class='' readonly>
						</td>
					</tr>
					<tr>
						<td align='right'>Customer Name:</td>
						<td width=1 align='left'>
							<input id=EditCustomer_name type='text' tabindex=3 name='name' size=70 value='<?php echo $nameInput;?>' placeholder='Customer Name' class=''>
							Status:
							<select id=EditCustomer_status name="status" tabindex=4>
								<option value="A" <?php if($statusInput==="A") echo "Selected"; ?>>Active</option>
								<option value="I" <?php if($statusInput==="I") echo "Selected"; ?>>Inactive</option>
							</select>
						</td>
					</tr>
					<tr>
						<td align='right' valign='top'>Notes:</td>
						<td width=1 align='left'>
							<!--  NOTE the form atrb is not supported in IE - F* IE-->
							<textarea id=EditCustomer_notes form='EditCustomerForm' tabindex=5 rows=3 cols=95 name="notes" placeholder="Customer Notes"><?php echo $notesInput;?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan='2' align='right'>
							<button id=EditCustomer_cancelbutton type="button" onclick="HideAllEditForms()" tabindex=7>Cancel</button>
							<input type="submit" value="Save" tabindex=6>
						</td>
					</tr>
				</table>
				<input id=EditCustomer_action type='hidden' name='action' value='null'>
				<input type="hidden" name="page_instance_id" value="<?php echo end($_SESSION['page_instance_ids']); ?>" />
			</fieldset>
		</form>
		</td></tr></table></div>
		<?php
	}
	
	function ShowDevicePage($input)
	{
		global $mysqli;
		global $userFullName;
		global $pageSubTitle;
		global $config_subnetsEnabled;
		
		$pageSubTitle = "";
		
		//top panel 
		echo "<div class='panel'>\n";
		echo "<div class='panel-header'>Device</div>\n";
		echo "<div class='panel-body'>\n\n";
		
		//get device info
		$query = "SELECT d.deviceid, d.hno, d.name, d.altname, d.member, d.type, d.model, d.unit, d.size, d.status, d.asset, d.serial, d.note, c.name, s.name, r.name, d.locationid, l.name, d.edituser, d.editdate, d.qauser, d.qadate 
			FROM dcim_device AS d
				LEFT JOIN dcim_customer AS c ON c.hno=d.hno
				LEFT JOIN dcim_location AS l ON d.locationid=l.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE deviceid=? 
			LIMIT 1";
		
		$deviceCount = 0;
		if (!($stmt = $mysqli->prepare($query)))
		{
			//TODO handle errors better
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		else 
		{
			$stmt->bind_Param('i', $input);		
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($deviceID, $hNo, $deviceName, $deviceAltName, $member, $type, $model, $unit, $size, $status, $asset, $serial, $notes, $customerName, $siteName, $room, $locationID, $locationName,$editUserID,$editDate, $qaUserID, $qaDate);
			$deviceCount = $stmt->num_rows;
		}
		
		if($deviceCount==1)
		{
			//hNo is propper H# or -1 for add cust (or failed direct search)
			if(UserHasWritePermission())
			{
				echo "<script src='lib/js/customerEditScripts.js'></script>\n";	
			}
			
			$stmt->fetch();
			
			$deviceInfo = GetDeviceFromModelName($model);
			
			$deviceFullName = GetDeviceFullName($deviceName, $model, $member,$deviceAltName, false);
			$deviceFullNameShort = GetDeviceFullName($deviceName, $model, $member,$deviceAltName, true);
			$pageSubTitle = "Device: ".MakeHTMLSafe($deviceFullName);
			$fullLocationName = FormatLocation($siteName, $room, $locationName);
			
			//customer   model  status
			//location - size   unit
			//asset
			//serial
			//note
			
			//this is the device page - build it...
			echo "<table width=100%>\n";
			echo "<tr>\n";
			echo "<td valign=top>\n";
			echo "<span class='customerName'>".MakeHTMLSafe($deviceFullName)."</span>\n";
			echo CustomFunctions::CreateInternalLink($deviceName, "", false);
			
			//if switch give link to chassis - all matching device name
			if($type=="S")
				echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='./?chassisname=$deviceName'>Chassis</a>\n";
		
			echo "</td>\n";
			echo "<td valign=top align=right>\n";
			//edit Customer button - not visible till in edit mode
			if(UserHasWritePermission())
			{
				$jsSafeDeviceFullName = MakeJSSafeParam($deviceFullName);
				$jsSafeDeviceName = MakeJSSafeParam($deviceName);
				$jsSafeAltName = MakeJSSafeParam($deviceAltName);
				$jsSafeNotes = MakeJSSafeParam($notes);
				$jsSafeSize = MakeJSSafeParam($size);
				$jsSafeAsset = MakeJSSafeParam($asset);
				$jsSafeSerial = MakeJSSafeParam($serial);
				//EditDevice(add, deviceID, hNo, name, altname, fullname, type, size, locationID, unit, status, notes, model, member, asset, serial)
				echo "<button class='editButtons_hidden' onclick=\"EditDevice(false, $deviceID, '$hNo', '$jsSafeDeviceName', '$jsSafeAltName', '$jsSafeDeviceFullName', '$type', '$jsSafeSize', '$locationID', '$unit', '$status', '$jsSafeNotes', '$model', '$member', '$jsSafeAsset', '$jsSafeSerial')\">Edit Device</button>\n";
			}
			//editMode button
			if(UserHasWritePermission())
			{
				echo "<button type='button' onclick='ToggleEditMode()' style='display:inline;'>Edit Mode</button>\n";
			}
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			
			//details
			echo "<table>\n";
			echo "<tr>\n";
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Customer:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo "<a href='./?host=$hNo'>".MakeHTMLSafe($customerName)."</a>";
			echo "</td>\n";
			
			echo "<td align=right class='customerDetails'>\n";
			if($deviceInfo->coloDevice)
				echo "<b>Colo:</b>";
			else
				echo "<b>Model:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			if($deviceInfo->coloDevice)
				echo MakeHTMLSafe(DeviceType($type));
			else
				echo MakeHTMLSafe($model);
			echo "</td>\n";
			
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Status:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails'>\n";
			echo FormatTechDetails($editUserID,$editDate,DeviceStatus($status), $qaUserID, $qaDate);
			echo "</td>\n";
			echo "</tr>\n";
			
			echo "<tr>\n";
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Location:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo "<a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a>";
			echo "</td>\n";
			
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Unit:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
			echo $unit;
			echo "</td>\n";
			
			echo "<td align=right class='customerDetails'>\n";
			echo "<b>Size:</b>";
			echo "</td>\n";
			echo "<td align=left class='customerDetails'>\n";
			echo MakeHTMLSafe($size);
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			
			//asset serial and notes
			echo "<table width=100%>\n";
			echo "<tr><td valign=top width=150 class='customerDetails'>\n";
			if(!$deviceInfo->coloDevice)
			{
				echo  "<b>Asset:</b> ".CustomFunctions::CreateInternalInventoryLink($asset)."<BR>\n";
				echo "<b>Serial:</b> ".MakeHTMLSafe($serial)."<BR>\n";
			}
			echo "</td>\n";
			//device notes
			echo "<td valign=top align=left>\n";
			echo "<textarea rows=3 cols=95 readonly placeholder=''>".MakeHTMLSafe($notes)."</textarea>";
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			
			
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			//show device image with port overlays
			$connectionSymbol = ">";
								
			if($deviceInfo->showDeviceImage)
			{
				//process port data for switchview
				$startPort = $deviceInfo->startPort;
				$endPort = $startPort + $deviceInfo->portCount -1;
				
				$dbPortCount = 0;
				
				$query = "SELECT 
						dp.deviceid, dp.deviceportid, d.name, d.member, d.model, dp.pic, dp.port, c.hno, c.name AS cname, dp.mac,
						sp.deviceid AS sid, sp.deviceportid AS spid, s.name AS sname, s.member AS smember, s.model AS smodel, sp.pic AS spic, sp.port AS sport, s.hno AS shno, sc.name AS scname,
						dp.type, dp.speed, dp.note, dp.status, pc.portconnectionid, dp.edituser, dp.editdate, dp.qauser, dp.qadate,
						CAST(GROUP_CONCAT(IF(pv.vlan<0,CONCAT('Temp-',ABS(pv.vlan)),pv.vlan) ORDER BY pv.vlaN<0, ABS(pv.vlaN) SEPARATOR ', ') AS CHAR) AS vlans 
					FROM dcim_device AS d
						LEFT JOIN dcim_deviceport AS dp ON d.deviceid=dp.deviceid
							LEFT JOIN (
										SELECT pcA.portconnectionid,pcA.childportid AS srcportid,pcA.parentportid AS destportid,pcA.patches,pcA.edituser,pcA.editdate,pcA.qauser,pcA.qadate FROM dcim_portconnection AS pcA
										UNION ALL
										SELECT pcB.portconnectionid,pcB.parentportid AS srcportid,pcB.childportid AS destportid,pcB.patches,pcB.edituser,pcB.editdate,pcB.qauser,pcB.qadate FROM dcim_portconnection AS pcB) 
								AS pc ON dp.deviceportid=pc.srcportid
						LEFT JOIN dcim_deviceport AS sp ON pc.destportid=sp.deviceportid
						LEFT JOIN dcim_device AS s ON sp.deviceid=s.deviceid
						LEFT JOIN dcim_portvlan AS pv ON dp.deviceportid=pv.deviceportid
						LEFT JOIN dcim_customer AS c ON d.hno=c.hno
						LEFT JOIN dcim_customer AS sc ON s.hno=sc.hno
					WHERE d.deviceid=?
					GROUP BY dp.deviceportid
					ORDER BY 3,4,6,7";
				
				if (!($stmt = $mysqli->prepare($query)))
				{
					//TODO handle errors better
					echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
				}
				else 
				{
					$stmt->bind_Param('i', $deviceID);
					$stmt->execute();
					$stmt->store_result();
					$stmt->bind_result($deviceID, $devicePortID, $deviceName, $member, $model, $pic, $port, $hno, $customer, $mac, 
									   $switchID, $switchPortID, $switchName, $switchMember, $switchModel, $switchPic, $switchPort, $shno, $switchCustomer, 
									   $type, $speed, $note, $status, $portConnectionID, $editUserID, $editDate, $qaUserID, $qaDate, $vlan);
					$dbPortCount = $stmt->num_rows;
				}
				
				if($dbPortCount>=1)
				{
					$portDivs = "";
					$portCSSTags = "";
					//build switch port divs
					while ($stmt->fetch()) 
					{
						if($port >= $startPort && $port <= $endPort)//valid port in range
						{
							if($pic==0)
							{
								if($status=="A")
									$statusStyle = "switchPortActive";
								else if($status=="D")
									$statusStyle = "switchPortEmpty";
								else if($status=="R")
									$statusStyle = "switchPortReserved";
								else
									$statusStyle = "switchPortBad";
								$statusDescrip = DevicePortStatus($status,true);
								
								//XXX this does not support mutiple vlans, probably need to write fresh SQL and code for that
								$portFullName = FormatPort($member, $model, $pic, $port, $type);
								$connectionText = "$connectionSymbol Not Connected";
								if($switchID!=null)
								{
									$switchPortFullName = FormatPort($switchMember, $switchModel, $switchPic, $switchPort, $type);
									$connectionText = "$connectionSymbol <a href='./?host=$shno'>".MakeHTMLSafe($switchCustomer)."</a><BR>
										$connectionSymbol <a href='./?deviceid=$switchID'>".MakeHTMLSafe($switchName)."</a> ".MakeHTMLSafe($switchPortFullName);
								}
								
								//define popup text
								$popupText = "";
								$tech = $userFullName[$editUserID] . ": ".$editDate;
								//$tech = FormatTechDetails($editUserID, $editDate,"", $qaUserID, $qaDate);
								$popupText = "<a href='./?host=$hno'>$customer</a><BR>
									<a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceName)."</a> $portFullName <BR>
									Status:$statusDescrip<BR>
									$connectionText<BR>
									MAC:".MakeHTMLSafe($mac)." <BR>
									Speed:".MakeHTMLSafe($speed)." <BR>\n";
									
									if($config_subnetsEnabled)
										$popupText .= "VLAN(s):$vlan <BR>\n";
									
									$popupText .= "Tech:$tech <BR>
									Notes:".MakeHTMLSafe($note);
								
								if(CustomFunctions::UserHasDevPermission())
								{
									//debug
									$popupText .= "<BR>
									<BR>parentDeviceID=$deviceID
									<BR>DeviePortID=$devicePortID
									<BR>PortConnectionID=$portConnectionID
									<BR>SwitchID=$switchID
									<BR>SwitchPortID=$switchPortID";
								}
								
								
								//define js for click to edit port
								$jsSafeDeviceFullName = MakeJSSafeParam($deviceFullNameShort);
								$jsSafePortFullName = MakeJSSafeParam($portFullName);
								$jsSafeMac = MakeJSSafeParam($mac);
								$jsSafeSpeed = MakeJSSafeParam($speed);
								$jsSafeNote = MakeJSSafeParam($note);
								//EditDevicePort(event,add, devicePortID, deviceID, deviceName, portName, pic, port, type, status, speed, mac, note)
								$portEditJS = "EditDevicePort(event,false,".(CustomFunctions::UserHasPortAddEditPermission()?"true":"false").",$devicePortID,$deviceID,'$jsSafeDeviceFullName','$jsSafePortFullName',$pic,$port,'$type','$status','$jsSafeSpeed','$jsSafeMac','$jsSafeNote')";
								
								//get port possition
								$xPos = 0; $yPos = 0;
								$deviceInfo->GetPortPosition($port,$xPos,$yPos);
								
								$bottomStyle = "";
								if($deviceInfo->IsPortOnBottom($port))
									$bottomStyle = "switchBottomPort";
								
								//define Create div and position CSS
								$portDiv = "<div id='port$devicePortID' onClick=\"$portEditJS\" class='$statusStyle $bottomStyle tooltip'><span class='toolTip_PortDetails'>$popupText</span></div>\n";
								$portCSS = "#port$devicePortID {
									margin-left: ".$xPos."px;
									margin-top: ".$yPos."px;
								}";
								
								$portDivs .= $portDiv;
								$portCSSTags .= $portCSS;
							}
							else 
							{
								//stub for fiber ports or specialty cases (extra NIC ports, console, MGMT, whatever)
								//this should actualy filter above with the range filter
								//for now dont include them here (on the visual render)  - will still be listed below
								//will need thier own positioning CSS classes and probably image overlays - meh
							}
						}//port in range
					}//while
				}//found ports
				
			?>
<style type="text/css">
#deviceLayout {
	width:<?php echo $deviceInfo->deviceWidthPx;?>;
	height:<?php echo $deviceInfo->deviceHeightPx;?>;
	background-image:url('<?php echo $deviceInfo->deviceImage;?>'); 
	background-repeat: no-repeat;
}
.switchPortActive, .switchPortBad,
.switchPortEmpty, .switchPortReserved{
	background-repeat: no-repeat;
	position: absolute;
	width:<?php echo DeviceModel::$portWidth;?>px;
	height:<?php echo DeviceModel::$portHeight;?>px;
}
<?php echo $portCSSTags;?>
</style>
<?php
				//deviceLayout div
				echo "<div id='deviceLayout'>\n";
				echo $portDivs;
				echo "</div>\n";
			}//show device
			
			if(UserHasWritePermission())
			{	
				$action = "./?deviceid=$input";
				EditDeviceForm($action);
			}
			
			//end top panel and panel body
			echo "</div>\n";
			echo "</div>\n";
			
			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>Device Details</div>\n";
			echo "<div class='panel-body'>\n\n";
			
			
			//all Ports
			ListDevicePorts($deviceID,$deviceFullNameShort);
		}
		else 
		{
			echo "Device Not Found";
		}//device found
		
		//end panel and panel body
		echo "</div>\n";
		echo "</div>\n";
		
		if(UserHasWritePermission())
		{
			//initialize page JS
			echo "<script type='text/javascript'>InitializeEditButton();</script>\n";
			//$pageSubTitle = $customer;
		}
	}
	
	function ShowChassisPage($chassisName)
	{
		global $pageSubTitle;
		$pageSubTitle = MakeHTMLSafe($chassisName);
		
		if(UserHasWritePermission())
		{
			echo "<script src='lib/js/customerEditScripts.js'></script>\n";	
		}
		
		echo "<div class='panel'>\n";
		echo "<div class='panel-header'>Chassis Details for $chassisName</div>\n";
		echo "<div class='panel-body'>\n\n";
		
		//all Ports
		ListDevicePorts($chassisName,"",true);
		
		if(UserHasWritePermission())
		{
			//initialize page JS
			echo "<script type='text/javascript'>InitializeEditButton();</script>\n";
			//$pageSubTitle = $customer;
		}
		
		//end panel and panel body
		echo "</div>\n";
		echo "</div>\n";
	}
	
	function ListBadges($search, $input)
	{
		//NOTE badges are not filtered by site as they are assumed to be cross site compatable
		global $mysqli;
		
		if($search)
		{
			$input = "%".$input."%";
			
			$query = "SELECT c.name AS customer, b.badgeid, b.hno, b.name, b.badgeno, b.status, b.issue, b.hand, b.returned, b.edituser, b.editDate, b.qauser, b.qadate
			FROM dcim_badge AS b
				LEFT JOIN dcim_customer AS c ON c.hno=b.hno 
			WHERE b.name LIKE ? OR b.badgeno LIKE ?
			ORDER BY b.name";
			
			if (!($stmt = $mysqli->prepare($query)))
			{
				//TODO hadnle errors better
				echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			}
			$stmt->bind_Param('ss', $input, $input);
		}
		else
		{
			//select empty as customer to keep return results to match search query
			$query = "SELECT c.name AS customer, b.badgeid, b.hno, b.name, b.badgeno, b.status, b.issue, b.hand, b.returned, b.edituser, b.editdate, b.qauser, b.qadate
			FROM dcim_badge AS b 
				LEFT JOIN dcim_customer AS c ON c.hno=b.hno
			WHERE b.hno = ?
			ORDER BY b.status, b.name";

			if (!($stmt = $mysqli->prepare($query)))
			{
				//TODO handle errors better
				echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
				return -1;
			}
				
			$stmt->bind_Param('s', $input);
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($customer, $badgeID, $hNo, $name, $badgeNo, $status, $issue, $hand, $returned, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
		
		//data title
		echo "<span class='tableTitle'>Badges</span>\n";
		if(!$search && UserHasWritePermission())
		{
			// add button to add new badge
			echo "<button class='editButtons_hidden' onclick=\"EditBadge(true,-1,'$input','','','P','".date("Y-m-d")."')\">Add New</button>\n";
			
			echo "<div id='badgeHelpPopup' class='helpPopup'>".BadgeHelpPopup()."</div>";
			echo "<span class=''><a class='helpLink' href='javascript:void(0)' onclick = \"CreatePopup('badgeHelpPopup');\">Help</a></span>\n";
		}
		echo "<BR>\n";
		
		if($count>0)
		{
			if($search)
				echo CreateDataTableHeader(array("Customer","Name&#x25B2;","Badge#","Status","Issue","Enroll"));
			else
				echo CreateDataTableHeader(array(		   "Name&#x25B2;","Badge#","Status","Issue","Enroll"),true,UserHasWritePermission(),UserHasWritePermission());
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				echo "<tr class='$rowClass'>";
				if($search == true)
					echo "<td class='data-table-cell'>"."<A href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a>"."</td>\n";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($name)."</td>\n";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($badgeNo)."</td>\n";
				echo "<td class='data-table-cell'>".BadgeStatus($status)."</td>\n";
				echo "<td class='data-table-cell'>$issue</td>\n";
				echo "<td class='data-table-cell'>$hand</td>\n";
				if(!$search)
				{
					//last tech date 
					echo "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate,"", $qaUserID, $qaDate)."</td>";
					
					//edit button cell
					if(UserHasWritePermission())
					{
						//edit button
						echo "<td class='data-table-cell-button editButtons_hidden'>\n";
						
						$jsSafeName = MakeJSSafeParam($name);
						$jsSafeBadgeNo = MakeJSSafeParam($badgeNo);
						$jsSafeIssue = MakeJSSafeParam($issue);
						$params = "false,$badgeID,'$hNo','$jsSafeName','$jsSafeBadgeNo','$status','$jsSafeIssue'";
						?><button onclick="EditBadge(<?php echo $params;?>)">Edit</button>
						<?php 
						echo "</td>\n";
						
						$formAction = "./?host=$hNo";
						echo CreateQACell("dcim_badge", $badgeID, $formAction, $editUserID, $editDate, $qaUserID, $qaDate);
					}
				}
				echo "</tr>\n";
			}
			echo "</table>\n";
		}
		else 
		{
		  echo "No Badges Found.<BR>\n";
		}
		
		if(UserHasWritePermission())
		{	
			EditBadgeForm($input);
		}
		
		return $count;
	}
	
	function EditBadgeForm($inputHNo)
	{
		//edit/Add badge form
		//-default values - never seen
		$actionText = "Addy";
		$hnoInput = "000000";
		$nameInput = "Jimmy";
		$badgeNoInput = "0123456789";
		$statusInput = "A";
		
		?>
		<div id='EditBadgeMsg' class='hidden'></div>
		<div id='EditBadgeEntry' class='hidden'>
		<BR>
		<table><tr><td>
		<form action="./?host=<?php echo $inputHNo;?>" method='post' id='EditBadgeForm' onsubmit='return SaveBadge()' class=''>
			<fieldset>
				<legend id=EditBadgeEntryLegend><b><?php echo $actionText;?> Badge</b></legend>
				<table>
					<tr>
						<td align='right'>Hosting&nbsp;Account&nbsp;#:</td>
						<td width=1>
							<input id=EditBadge_hno type='text' name='hno' maxlength=6 value='<?php echo $hnoInput;?>' placeholder='000000' class='' readonly>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Badge&nbsp;Holder's&nbsp;Name:</td>
						<td align='left'>
							<input id=EditBadge_name type='text' tabindex=1 name='name' value='<?php echo $nameInput;?>' placeholder='John Doe' class='' >
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Badge&nbsp;#:</td>
						<td align='left'>
							<input id=EditBadge_badgeno type='text' tabindex=2 name='badgeno' value='<?php echo $badgeNoInput;?>' placeholder='0000000' class='' >
						</td>
					</tr>
					<tr>
						<td colspan=1 align=right>Status:</td>
						<td align='left'>
							<select id=EditBadge_status name="status" tabindex=3>
								<option value="A" <?php if($statusInput==="A") echo "Selected"; ?>>Active</option>
								<option value="P" <?php if($statusInput==="P") echo "Selected"; ?>>Pending</option>
								<option value="D" <?php if($statusInput==="D") echo "Selected"; ?>>Disabled</option>
								<option value="E" <?php if($statusInput==="E") echo "Selected"; ?>>Enroll (Active)</option>
								<option value="R" <?php if($statusInput==="R") echo "Selected"; ?>>Returned</option>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan=1 align=right>Issue date:</td>
						<td align='left'>
							<input id=EditBadge_issue type='date' tabindex=4 name='issue' value='' class='' >
						</td>
					</tr>
					<tr>
						<td colspan='1' align='Left'>
							<button id=EditBadge_deletebtn type="button" onclick="DeleteBadge()" tabindex=7>Delete</button>
						</td>
						<td colspan='1' align='right'>
							<button type="button" onclick="HideAllEditForms()" tabindex=6>Cancel</button>
							<input type="submit" value="Save" tabindex=5>
						</td>
					</tr>
				</table>
				<input id=EditBadge_badgeid type='hidden' name='badgeid' value=-1>
				<input id=EditBadge_paststatus type='hidden' name='paststatus' value='null'>
				<input id=EditBadge_action type='hidden' name='action' value='null'>
				<input type="hidden" name="page_instance_id" value="<?php echo end($_SESSION['page_instance_ids']); ?>" />
			</fieldset>
		</form>
		</td></tr></table></div>
		<?php  
	}
	
	function EditDevicePortForm($action)
	{
		//edit/Add Device Port form
		?>
		<div id='EditDevicePortMsg' class='hidden'></div>
		<div id='EditDevicePortEntry' class='hidden'>
		<BR>
		<table><tr><td>
		<form action="<?php echo $action;?>" method='post' id='EditDevicePortForm' onsubmit='return SaveDevicePort()' class=''>
			<fieldset>
				<legend id=EditDevicePortEntryLegend><b>Action Text</b></legend>
				<table>
					<tr>
						<td align='right'>Device:</td>
						<td width=1>
							<input id=EditDevicePort_devicename type='text' tabindex=1 value='' placeholder='DAS ##' class='' readonly>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Pic:</td>
						<td align='left'>
							<input id=EditDevicePort_pic type='number' tabindex=2 name='pic' value='0' min=0 max=99 placeholder='0' class='' style='width:50px' >
							Port:
							<input id=EditDevicePort_portno type='number' tabindex=3 name='portno' value='0' min=0 max=99 placeholder='0' class='' style='width:50px' >
						</td>
					</tr>
					<tr>
						<td colspan=1 align=right>Type:</td>
						<td align='left'>
							<select id=EditDevicePort_type name="type" tabindex=4>
								<option value="E">Ethernet</option>
								<option value="F">Fiber</option>
								<option value="O">Other</option>
							</select>
							Status:
							<select id=EditDevicePort_status name="status" tabindex=5>
								<option value="A">Active</option>
								<option value="D">Disabled</option>
								<option value="R">Reserved</option>
								<option value="B">Bad</option>
							</select>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Speed:</td>
						<td align='left'>
							<input id=EditDevicePort_speed type='text' tabindex=6 name='speed' value='' placeholder='AUTO, 100Mb, 1Gb' class='' >
						</td>
					</tr>
					<tr>
						<td align='right' width=1>MAC:</td>
						<td align='left'>
							<input id=EditDevicePort_mac type='text' tabindex=7 name='mac' value='' placeholder='ff:ff:ff:ff:ff:ff' class=''>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Note:</td>
						<td align='left'>
							<input id=EditDevicePort_note type='text' tabindex=8 name='note' value='' placeholder='' class=''>
						</td>
					</tr>
					<tr>
						<td colspan=2><table style='width:100%;'><tr>
							<td align='left'>
								<button id=EditDevicePort_deletebtn type="button" onclick="DeleteDevicePort()" tabindex=11>Delete</button>
							</td>
							<td align='right'>
								<button type="button" onclick="HideAllEditForms()" tabindex=10>Cancel</button>
								<input type="submit" value="Save" tabindex=9>
							</td>
						</tr></table></td>
					</tr>
				</table>
				<input id=EditDevicePort_deviceid type='hidden' name='deviceid' value=-1>
				<input id=EditDevicePort_deviceportid type='hidden' name='deviceportid' value=-1>
				<input id=EditDevicePort_action type='hidden' name='action' value='null'>
				<input type="hidden" name="page_instance_id" value="<?php echo end($_SESSION['page_instance_ids']); ?>" />
			</fieldset>
		</form>
		</td></tr></table></div>
		<?php  
	}
	
	function ListDevices($search, $input)
	{
		global $mysqli;
		global $deviceModels;
		
		$formAction = "./?host=$input";
			
		if($search)
		{
			$input = "%".$input."%";
			
			$query = "SELECT d.deviceid, s.name AS site, r.name AS room, c.hno, c.name AS cust, l.locationid, l.name as loc, l.note, d.unit, d.name, d.altname, d.member, d.size, d.type, d.status, d.note, d.asset, d.serial, d.model, d.edituser, d.editdate, d.qauser, d.qadate
					FROM dcim_device AS d
						LEFT JOIN dcim_customer AS c ON c.hno=d.hno
						LEFT JOIN dcim_location AS l ON d.locationid=l.locationid
						LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
						LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
					WHERE CONCAT(d.name,'~',d.altname,'~',d.asset,'~',d.note,'~',d.serial,'~',d.model) LIKE ?
				UNION
					SELECT '', s.name, r.name, '', '', l.locationid, l.name, l.note, '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''
						FROM dcim_location AS l
							LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
							LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
						WHERE CONCAT(l.name,'~',l.altname,'~',l.keyno,'~',l.note) LIKE ?
				ORDER BY site, room, loc, length(name) DESC, unit DESC,name, member";
			
			if (!($stmt = $mysqli->prepare($query))) 
			{
				//TODO hadnle errors better
				echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			}
			$stmt->bind_Param('ss', $input, $input);
			
			echo "<span class='tableTitle'>Locations and Devices</span>\n";
		}
		else
		{
			$query = "SELECT d.deviceid, s.name AS site, r.name AS room, d.hno, '', l.locationid, l.name AS loc, l.note, d.unit, d.name, d.altname, d.member, d.size, d.type, d.status, d.note, d.asset, d.serial, d.model, d.edituser, d.editdate, d.qauser, d.qadate
			FROM dcim_device AS d
				LEFT JOIN dcim_location AS l ON d.locationid=l.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE d.hno=?
			ORDER BY status, site, room, loc, unit, name, member";
			
			if (!($stmt = $mysqli->prepare($query))) 
			{
				//TODO hadnle errors better
				echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			}
			$stmt->bind_Param('s', $input);
			
			echo "<span class='tableTitle'>Devices</span>\n";
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($deviceID, $site, $room, $hNo, $customer, $locationID, $location, $locationNote, $unit, $name,$deviceAltName, $member, $size, $type, $status, $notes, $asset, $serial, $model, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
		
		
			
		
		if(!$search && UserHasWritePermission())
		{
			// add button to add new Device
			//EditDevice(add, deviceID, hNo, name, altname, fullname, type, size, locationID, unit, status, notes, model, member, asset, serial)
			echo "<button class='editButtons_hidden' onclick=\"EditDevice(true, -1, '$input', '$input-?', '', '$input-?', 'S', '1U', -1, '0', 'A', '', '', '-1', '', '')\">Add New</button>\n";
		}
		echo "<BR>\n";
		
		if($count>0)
		{
			if($search)
				echo CreateDataTableHeader(array("Location&#x25B2;", "Location Note","Customer","Device","AltName","Model","Serial","Asset","Note"));
			else
				echo CreateDataTableHeader(array("Location&#x25B2;", "Device","Unit","AltName","Model","Size","Type","Status","Notes"),true,UserHasWritePermission(),UserHasWritePermission());
			
			//list result data
			$oddRow = false;
			$lastLocationID = -1;
			while ($stmt->fetch()) 
			{
				// if same loction and no device here this record - skip
				if($locationID==$lastLocationID && strlen($name)<=0)
					continue;
				
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				//TODO test this with complex HTML
				$visibleNotes = TruncateWithSpanTitle(MakeHTMLSafe(htmlspecialchars($notes)));
				$deviceFullName = GetDeviceFullName($name, $model, $member,"", true);
				$fullLocationName = FormatLocation($site, $room, $location);
				
				echo "<tr class='$rowClass'>";
				echo "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>";
				if($search)
				{
					echo "<td class='data-table-cell'>".TruncateWithSpanTitle(MakeHTMLSafe($locationNote))."</td>";
					echo "<td class='data-table-cell'><a href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a></td>";
				}
				echo "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>";
				if($search)
				{
					echo "<td class='data-table-cell'>".MakeHTMLSafe($deviceAltName)."</td>";
					echo "<td class='data-table-cell'>".MakeHTMLSafe($model)."</td>";
					echo "<td class='data-table-cell'>".MakeHTMLSafe($serial)."</td>";
					echo "<td class='data-table-cell'>".MakeHTMLSafe($asset)."</td>";
					echo "<td class='data-table-cell'>$visibleNotes</td>";
				}
				else
				{//!search
					echo "<td class='data-table-cell'>$unit</td>";
					echo "<td class='data-table-cell'>".MakeHTMLSafe($deviceAltName)."</td>";
					echo "<td class='data-table-cell'>".MakeHTMLSafe($model)."</td>";
					echo "<td class='data-table-cell'>".MakeHTMLSafe($size)."</td>";
					echo "<td class='data-table-cell'>".DeviceType($type)."</td>\n";
					echo "<td class='data-table-cell'>".DeviceStatus($status)."</td>\n";
					echo "<td class='data-table-cell'>$visibleNotes</td>";
					echo "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
				}
				if(!$search && UserHasWritePermission())
				{
					//edit device - link to form
					echo "<td class='data-table-cell-button editButtons_hidden'>";
					$jsSafeFullName = MakeJSSafeParam($deviceFullName);
					$jsSafeName = MakeJSSafeParam($name);
					$jsSafeAltName = MakeJSSafeParam($deviceAltName);
					$jsSafeNotes = MakeJSSafeParam($notes);
					$jsSafeSize = MakeJSSafeParam($size);
					$jsSafeAsset = MakeJSSafeParam($asset);
					$jsSafeSerial = MakeJSSafeParam($serial);
					//EditDevice(add, deviceID, hNo, name, altname, fullname, type, size, locationID, unit, status, notes, model, member, asset, serial)
					echo "<button onclick=\"EditDevice(false, $deviceID, '$hNo', '$jsSafeName', '$jsSafeAltName', '$jsSafeFullName', '$type', '$jsSafeSize', '$locationID', '$unit', '$status', '$jsSafeNotes', '$model', '$member', '$jsSafeAsset', '$jsSafeSerial')\">Edit</button>\n";
					echo "</td>\n";
					
					echo CreateQACell("dcim_device", $deviceID, $formAction, $editUserID, $editDate, $qaUserID, $qaDate);
				}
				echo "</tr>";
				$lastLocationID = $locationID;
			}
			echo "</table>";
		}
		else 
		{
		  echo "No Devices Found.<BR>\n";
		}
	
		if(UserHasWritePermission())
		{	
			EditDeviceForm($formAction);
		}
		return $count;
	}
	
	function EditDeviceForm($action)
	{
		global $mysqli;
		global $deviceModels;
		global $errorMessage;
		
		//edit/Add Device form
		//-default values - never seen
		$actionText = "Addy";
		$hnoInput = "654321";
		$nameInput = "654321-1";
		$altNameInput = "DBWeb01";
		$statusInput = "A";
		$typeInput = "S";
		$locationInput = 1;
		$sizeInput = "1U";
		$unitInput = "0";
		$assetInput = "asset";
		$serialInput = "serial";
		$notesInput = "notes input";
		
		//build location combo options
		$locationOptions = "";
		$query = "SELECT s.name, l.locationid, s.siteid, r.name, l.name, l.type
			FROM dcim_location AS l
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			ORDER BY s.name, r.name, l.name";
			
		if (!($stmt = $mysqli->prepare($query))) 
		{
			$errorMessage[] = "EditDeviceForm() Prepare 1 failed: (" . $mysqli->errno . ") " . $mysqli->error;
		}
		else
		{
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($site, $locationID, $siteID, $room, $location, $type);
			while ($stmt->fetch()) 
			{
				$fullLocationName = FormatLocation($site, $room, $location);
				$selected = ($locationID==$locationInput ? "Selected" : "");
				$locationOptions .= "<option value='$locationID' $selected>$fullLocationName</option>\n";
			}
		}
		
		?>
		<div id='EditDeviceMsg' class='hidden'></div>
		<div id='EditDeviceEntry' class='hidden'>
		<BR>
		<table><tr><td>
		<form action="<?php echo $action;?>" method='post' id='EditDeviceForm' onsubmit='return SaveDevice()' class=''>
			<fieldset>
				<legend id=EditDeviceEntryLegend><b><?php echo $actionText;?> Device</b></legend>
				<table>
					<tr>
						<td align='right'>Hosting&nbsp;Account&nbsp;#:</td>
						<td width=1>
							<input id=EditDevice_hno type='text' name='hno' maxlength=6 size=6 value='<?php echo $hnoInput;?>' placeholder='000000' class='' readonly>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Device&nbsp;Name:</td>
						<td align='left'>
							<div class='inputToolTipContainer'>
								<input id=EditDevice_name type='text' tabindex=1 size=10 name='devicename' value='<?php echo $nameInput;?>' placeholder='000000-1' class='' >
							<span class=inputTooltip>Make sure to double check the dash number</span></div>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Device&nbsp;AltName:</td>
						<td align='left'>
							<input id=EditDevice_altname type='text' tabindex=2 size=15 name='devicealtname' value='<?php echo $altNameInput;?>' placeholder='WebDB01' class='' >
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Type:</td>
						<td align='left'>
							<select id=EditDevice_type onchange='EditDeviceTypeChanged(true)' name="type" tabindex=3>
								<option value="S" <?php if($typeInput==="S") echo "Selected"; ?>>Physical</option>
								<option value="F" <?php if($typeInput==="F") echo "Selected"; ?>>Full Cab</option>
								<option value="H" <?php if($typeInput==="H") echo "Selected"; ?>>Half Cab</option>
								<option value="C" <?php if($typeInput==="C") echo "Selected"; ?>>Cage</option>
							</select>
							Size:
							<input id=EditDevice_size type='text' tabindex=4 size=6 name='size' value='<?php echo $sizeInput;?>' placeholder='2U, Full, 5x7, Half' class=''>
						</td>
					</tr>
					<tr>
						<td colspan=1 align=right>Location:</td>
						<td align='left'>
							<select id=EditDevice_location name="locationid" tabindex=5>
								<?php echo $locationOptions; ?>
							</select>
							Unit:
							<input id=EditDevice_unit type='text' tabindex=6 size=3 name='unit' value='<?php echo $unitInput;?>' placeholder='0' class=''>
						</td>
					</tr>
					<tr>
						<td colspan=1 align=right>Status:</td>
						<td align='left'>
							<select id=EditDevice_status name="status" tabindex=7>
								<option value="A" <?php if($statusInput==="A") echo "Selected"; ?>>Active</option>
								<option value="I" <?php if($statusInput==="I") echo "Selected"; ?>>Inactive</option>
							</select>
						</td>
					</tr>
					<tr id='EditDevice_interalFields_row1'>
						<td align='right' width=1>Model:</td>
						<td align='left'>
							<select id=EditDevice_model name="model" tabindex=8>
							<?php 
							//This should be a list of all switch (or other non colo device) models
							foreach($deviceModels as $model)
							{
								if($model->coloDevice==false)
								{
									echo "<option value='$model->name'>$model->name</option>\n";
								}
							}
							//also include unknown to prevent accedental defualts
							?>
							</select>
							Member:
							<select id=EditDevice_member name="member" tabindex=9>
							<?php 
							for ($member = 0; $member <= 9; $member++) {
								echo "<option value='$member'>$member</option>\n";
							}?>
							</select>
						</td>
					</tr>
					<tr id='EditDevice_interalFields_row2'>
						<td align='right' width=1>Asset:</td>
						<td align='left'>
							<input id=EditDevice_asset type='text' tabindex=10 size=50 name='asset' value='<?php echo $assetInput;?>' placeholder='000000' class=''>
						</td>
					</tr>
					<tr id='EditDevice_interalFields_row3'>
						<td align='right' width=1>Serial:</td>
						<td align='left'>
							<input id=EditDevice_serial type='text' tabindex=11 size=50 name='serial' value='<?php echo $serialInput;?>' placeholder='FFFFFFFFFFFFFFFF' class=''>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Notes:</td>
						<td align='left'>
							<input id=EditDevice_notes type='text' tabindex=12 size=50 name='notes' value='<?php echo $notesInput;?>' placeholder='Notes' class=''>
						</td>
					</tr>
					<tr>
						<td colspan='2' align='right'>
							<button type="button" onclick="HideAllEditForms()" tabindex=14>Cancel</button>
							<input type="submit" value="Save" tabindex=13>
						</td>
					</tr>
				</table>
				<input id=EditDevice_deviceid type='hidden' name='deviceid' value=-1>
				<input id=EditDevice_action type='hidden' name='action' value='null'>
				<input type="hidden" name="page_instance_id" value="<?php echo end($_SESSION['page_instance_ids']); ?>"/>
			</fieldset>
		</form>
		</td></tr></table></div>
		<?php
	}
	
	function ListSearchCustomers($input)
	{
		global $mysqli;
		
		$query = "SELECT hno, cno, name, note, status, edituser
		FROM dcim_customer
		WHERE CONCAT('H',hno) LIKE ? OR CONCAT('C',cno) LIKE ? OR name LIKE ? OR note LIKE ?
		ORDER BY name";
		
		$input = "%".$input."%";
		if (!($stmt = $mysqli->prepare($query))) 
		{
			//TODO handle errors better
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		
		$stmt->bind_Param('ssss', $input, $input, $input, $input);
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($hNo, $cNo, $name, $note, $status, $editUser);
		$count = $stmt->num_rows;
		
		echo "<span class='tableTitle'>Customers</span>\n";
		echo "<BR>\n";
		
		if($count>0)
		{
			echo CreateDataTableHeader(array("H#","C#","Name&#x25B2;","Status","Note"));
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$note = Truncate($note);
				echo "<tr class='$rowClass'>";
				echo "<td class='data-table-cell'>"."<A href='./?host=$hNo'>$hNo</a>"."</td>";
				echo "<td class='data-table-cell'>$cNo</td>";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($name)."</td>";
				echo "<td class='data-table-cell'>".CustomerStatus($status)."</td>";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($note)."</td>";
				echo "</tr>";
			}
			echo "</table>\n";
		}
		else 
		{
			echo "No Customers Found.<BR>\n";
		}
		return $count;
	}
	
	function ShowSiteListPage()
	{
		global $pageSubTitle;
		
		$pageSubTitle = "Site List";
		$result = "";
		
		$result .= "<div class=\"panel\">\n";
		$result .= "<div class=\"panel-header\">Site List</div>\n";
		$result .= "<div class=\"panel-body\">\n";
		
		$result .= ListSites();
		
		$result .= "</div>\n</div>\n\n";//end panel and panel body
		
		echo $result;
	}
	
	function ListSites($page="L", $input="1")
	{
		global $mysqli;
		global $errorMessage;
		
		if($page=="L")
		{
			$title = "All Sites";
			$listPage = true;
			$filter = "?=1";
		}
		else if($page=="?")
		{
			$title = "Sites";
			$searchPage = true;
			$input = "%".trim($input)."%";
			$filter = "CONCAT(s.name,'~',s.fullname) LIKE ?";
		}
		
		$query = "SELECT s.siteid, s.name, s.fullname, COUNT(r.roomid) AS rooms, 
				(SELECT COUNT(pu.powerupsid)   FROM dcim_powerups AS pu   WHERE pu.siteid=s.siteid) AS upss, 
				(SELECT COUNT(l.locationid)    FROM dcim_location AS l    INNER JOIN dcim_room AS r ON l.roomid=r.roomid  WHERE r.siteid=s.siteid) AS locations,
				(SELECT COUNT(pp.powerpanelid) FROM dcim_powerpanel AS pp INNER JOIN dcim_room AS r ON pp.roomid=r.roomid WHERE r.siteid=s.siteid) AS powerpanels,
				(SELECT COUNT(d.deviceid)      FROM dcim_device AS d      INNER JOIN dcim_location AS l ON d.locationid=l.locationid INNER JOIN dcim_room AS r ON l.roomid=r.roomid WHERE r.siteid=s.siteid AND d.status='A') AS devices
			FROM dcim_site AS s
				LEFT JOIN dcim_room AS r ON r.siteid=s.siteid
			WHERE $filter
			GROUP BY s.siteid
			ORDER BY s.name";
		
		$result = "";
		if(!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $input)|| !$stmt->execute())
		{
			$errorMessage[]="ListSites($page) Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			$result .= "Error Looking up sites";
		}
		else
		{
			$stmt->store_result();
			$stmt->bind_result($siteID, $siteName, $siteFullName, $roomCount, $upsCount, $locationCount, $powerPanelCount, $deviceCount);
			$count = $stmt->num_rows;
			
			//data title
			$result .= "<span class='tableTitle'>$title</span>\n";
			$result .= "<BR>\n";
			
			if($count>0)
			{
				$result .= CreateDataTableHeader(array("Name","Full Name","UPSs","Rooms","Panels","Locations","Devices"));
				
				//list result data
				$oddRow = false;
				while ($stmt->fetch())
				{
					$oddRow = !$oddRow;
					if($oddRow) $rowClass = "dataRowOne";
					else $rowClass = "dataRowTwo";
					
					$result .= "<tr class='$rowClass'>";
					$result .= "<td class='data-table-cell'>"."<a href='./?siteid=$siteID'>".MakeHTMLSafe($siteName)."</a>"."</td>\n";
					$result .= "<td class='data-table-cell'>".MakeHTMLSafe($siteFullName)."</td>\n";
					$result .= "<td class='data-table-cell'>".MakeHTMLSafe($upsCount)."</td>\n";
					$result .= "<td class='data-table-cell'>".MakeHTMLSafe($roomCount)."</td>\n";
					$result .= "<td class='data-table-cell'>".MakeHTMLSafe($powerPanelCount)."</td>\n";
					$result .= "<td class='data-table-cell'>".MakeHTMLSafe($locationCount)."</td>\n";
					$result .= "<td class='data-table-cell'>".MakeHTMLSafe($deviceCount)."</td>\n";
					$result .= "</tr>\n";
				}
				$result .= "</table>\n";
			}
			else
				$result .= "No Sites Found.<BR>\n";
			
			if(CustomFunctions::UserHasSitePermission())
			{
				//EditSiteForm($input);
			}
		}
		return $result;
	}
	
	function ShowSitePage($siteID)
	{
		global $mysqli;
		global $pageSubTitle;
		global $focusSearch;
		global $errorMessage;
		
		$query = "SELECT s.siteid, s.name AS site, s.fullname, s.width, s.depth, s.edituser, s.editdate, s.qauser, s.qadate
			FROM dcim_site AS s
			WHERE s.siteid=?";
		
		$result = "";
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $siteID) || !$stmt->execute()) 
			$errorMessage[]= "ShowSitePage Prepare 1 failed: (" . $mysqli->errno . ") " . $mysqli->error;
		else
		{
			$stmt->store_result();
			$stmt->bind_result($siteID, $siteName, $siteFullName, $width, $depth, $editUserID, $editDate, $qaUserID, $qaDate);
			$siteFound = $stmt->num_rows==1;
			
			if($siteFound)
			{
				$stmt->fetch();
				$safeSiteName = MakeHTMLSafe($siteName);
				$safeSiteFullName = MakeHTMLSafe($siteFullName);
				$pageSubTitle = "$safeSiteFullName";
				
				if(CustomFunctions::UserHasSitePermission() || CustomFunctions::UserHasRoomPermission())
				{
					$result .= "<script src='lib/js/customerEditScripts.js'></script>\n";	
				}
				
				$size = FormatSizeInFeet($width,$depth);
				
				$result .= "<div class='panel'>\n";
				$result .= "<div class='panel-header'>$safeSiteFullName</div>\n";
				$result .= "<div class='panel-body'>\n\n";
				
				$result .= "<table width=100%><tr>\n";
				$result .= "<td align='left'>\n";
				$result .= "<span class='customerName'>$safeSiteFullName - ($safeSiteName)</span>\n";
				$result .= "</td>\n";
				
				$result .= "<td align='right'>\n";
				//edit site button - not visible till in edit mode
				/*if(CustomFunctions::UserHasLocationPermission())
				{
					$jsSafeName = MakeJSSafeParam($location);
					$jsSafeAltName = MakeJSSafeParam($altName);
					$jsSafeNote = MakeJSSafeParam($note);
					//add, locationID, roomID, name, altName, type, units, orientation, x, y, width, depth, note)
					$params = "false, $locationID, $roomID, '$jsSafeName', '$jsSafeAltName', '$type', $units, '$orientation', $xPos, $yPos, $width, $depth, '$jsSafeNote'";
					
					?><button type='button' class='editButtons_hidden' onclick="EditLocation(<?php echo $params;?>);">Edit Location</button>
					<?php
				}*/
				//editMode button
				if(CustomFunctions::UserHasSitePermission() || CustomFunctions::UserHasRoomPermission())
				{
					$result .= "<button type='button' onclick='ToggleEditMode()' style='display:inline;'>Edit Mode</button>\n";
				}
				$result .= "</td>\n";
				$result .= "</tr>\n";
				$result .= "</table>\n";
				
				//render room - ignore 0 width or height rooms
				if($width>0 && $depth>0)
				{
					$depthToWidthRatio = 100*$depth/$width;//key proportian of the site
					$result .= "<style>\n";
					$result .= "#siteContainer$siteID {\n";
					$result .= "	padding-bottom:$depthToWidthRatio%;\n";
					$result .= "}\n";
					$result .= "</style>\n";
					$result .= "<div id='siteContainer$siteID' class='siteContainer'>\n";
					$result .= CreateSiteLayout($siteID, $safeSiteName, $safeSiteFullName, $width, $depth);//this should be a lookup of all sites...
					$result .= "</div>\n";
				}
			}
			else
			{
				$result .= "<div class='panel'>\n";
				$result .= "<div class='panel-header'>Site</div>\n";
				$result .= "<div class='panel-body'>\n\n";
				$result .= "Site ID#$siteID not found.<BR>\n";
			}
		}
		
		if(UserHasWritePermission())
		{
			//$result .= EditSiteForm();
		}
		
		$result .= "</div>\n";
		$result .= "</div>\n\n";
		
		if($siteFound)
		{
			$result .= "<div class='panel'>\n";
			$result .= "<div class='panel-header'>$safeSiteFullName Details</div>\n";
			$result .= "<div class='panel-body'>\n\n";

			$result .= ListRooms("S",$siteID,$siteFullName);
			$result .= "<BR>";
			echo $result;
			
			ListPowerPanels("S", $siteID);
			
			$result = "</div>\n</div>\n";
			
			if(CustomFunctions::UserHasSitePermission() || CustomFunctions::UserHasRoomPermission())
			{//initialize page JS
				$result .= "<script type='text/javascript'>InitializeEditButton();</script>\n";
			}
			echo $result;
		}//site found
		//return $count;
	}
	
	function ListRooms($page, $input, $siteFullName="")
	{
		global $mysqli;
		global $errorMessage;
		//$formAction = "./?host=$hNo";
		
		if($page=="S")
		{
			$sitePage = true;
			$filter = "s.siteid=?";
		}
		else if($page=="?")
		{
			$searchPage = true;
			$input = "%".trim($input)."%";
			$filter = "CONCAT(r.name,'~',r.fullname) LIKE ?";
		}
		
		$query = "SELECT r.roomid, r.name, r.fullname, r.custaccess, s.name, r.edituser, r.editdate, r.qauser, r.qadate
		FROM dcim_room AS r
		LEFT JOIN dcim_site AS s ON s.siteid=r.siteid
		WHERE $filter
		ORDER BY r.fullname";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "ListSiteRooms() Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
		}
		
		$stmt->bind_Param('s', $input);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($roomID, $roomName, $roomFullName, $custAccess, $siteName, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
		
		$result = "<span class='tableTitle'>$siteFullName Rooms</span>\n";
		//Add button
		/*if($sitePage && UserHasWritePermission())
		 {
		 $result .=
		 //function EditSubnet(add, portID,vlan,subnet,mask,gateway,first,last,note)
		 ?><button class='editButtons_hidden' onclick="EditSubnet(true,-1,-1,'','','','','','','')">Add New</button>
		 <?php
		 }*/
		$result .= "<BR>";
		if($count>0)
		{
			//echo CreateDataTableHeader(array("Site","Room","Full Name","Cust Access"),true,UserHasWritePermission(),UserHasWritePermission());
			$result .= CreateDataTableHeader(array("Site","Room","Full Name","Cust Access"),true, false, false);
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch())
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				if($custAccess=="T")$custAccess = "Yes";
				else $custAccess = "No";
				
				$result .= "<tr class='$rowClass'>";
				$result .= "<td class='data-table-cell'>".MakeHTMLSafe($siteName)."</a></td>";
				$result .= "<td class='data-table-cell'><a href='?roomid=$roomID'>".MakeHTMLSafe($roomName)."</a></td>";
				$result .= "<td class='data-table-cell'>".MakeHTMLSafe($roomFullName)."</td>";
				$result .= "<td class='data-table-cell'>".$custAccess."</td>";
				$result .= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
				/*if(UserHasWritePermission())
				 {
				 //edit button
				 $result .= "<td class='data-table-cell-button editButtons_hidden'>\n";
				 
				 $jsSafeVLAN = MakeJSSafeParam($vlan);
				 $jsSafeSubnet = MakeJSSafeParam($subnet);
				 $jsSafeMask = MakeJSSafeParam($mask);
				 $jsSafeFirst = MakeJSSafeParam($first);
				 $jsSafeLast = MakeJSSafeParam($last);
				 $jsSafeGateway = MakeJSSafeParam($gateway);
				 $jsSafeNote = MakeJSSafeParam($note);
				 //function EditSubnet(add, portID,vlan,subnet,mask,gateway,first,last,note)
				 $params = "false,$vlanID, $devicePortID, '$jsSafeVLAN', '$jsSafeSubnet', '$jsSafeMask', '$jsSafeGateway', '$jsSafeFirst', '$jsSafeLast', '$jsSafeNote'";
				 ?><button onclick="EditSubnet(<?php echo $params;?>)">Edit</button>
				 <?php
				 $result .= "</td>\n";
				 
				 $result .= CreateQACell("dcim_vlan", $vlanID, $formAction, $editUserID, $editDate, $qaUserID, $qaDate);
				 }*/
				$result .= "</tr>";
			}
			$result .= "</table>";
		}
		else
		{
			$result .= "No room records found.<BR>\n";
		}
		
		/*if($sitePage && UserHasWritePermission())
		 {
		 EditSubnetForm($formAction,$hNo);
		 }*/
		
		return $result;
	}
	
	function ShowRoomPage($roomID)
	{
		global $mysqli;
		global $deviceModels;
		global $pageSubTitle;
		global $focusSearch;
		global $errorMessage;
		
		$query = "SELECT s.siteid, s.name AS site, s.fullname, r.roomid, r.name, r.fullname, r.custaccess, r.orientation, r.xpos, r.ypos, r.width, r.depth, s.width, s.depth, r.edituser, r.editdate, r.qauser, r.qadate
			FROM dcim_room AS r
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE r.roomid=?";
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $roomID) || !$stmt->execute()) 
			$errorMessage[]= "ShowRoomPage Prepare 1 failed: (" . $mysqli->errno . ") " . $mysqli->error;
		else
		{
			$stmt->store_result();
			$stmt->bind_result($siteID, $site, $siteFullName, $roomID, $name, $fullName, $custAccess, $orientation, $xPos, $yPos, $width, $depth, $siteWidth, $siteDepth, $editUserID, $editDate, $qaUserID, $qaDate);
			$roomFound = $stmt->num_rows==1;
			
			if($roomFound)
			{
				$stmt->fetch();
				$fullRoomName = FormatLocation($site, $fullName, "",true,false);
				$fullRoomName = MakeHTMLSafe($fullRoomName);
				$safeRoomName = MakeHTMLSafe($name);
				$safeSiteFullName = MakeHTMLSafe($siteFullName);
				$pageSubTitle = "$fullRoomName";
				
				if(CustomFunctions::UserHasLocationPermission() || CustomFunctions::UserHasRoomPermission())
				{
					echo "<script src='lib/js/customerEditScripts.js'></script>\n";	
				}
				
				$pos = FormatSizeInFeet($xPos,$yPos);
				$size = FormatSizeInFeet($width,$depth);
				
				echo "<div class='panel'>\n";
				echo "<div class='panel-header'>$fullRoomName</div>\n";
				echo "<div class='panel-body'>\n\n";
				
				
				echo "<table width=100%><tr>\n";
				echo "<td align='left'>\n";
				echo "<span class='customerName'>$fullRoomName - ($safeRoomName)</span>\n";
				echo "</td>\n";
				
				echo "<td align='right'>\n";
				//edit Locationbutton - not visible till in edit mode
				/*if(CustomFunctions::UserHasLocationPermission())
				{
					$jsSafeName = MakeJSSafeParam($location);
					$jsSafeAltName = MakeJSSafeParam($altName);
					$jsSafeNote = MakeJSSafeParam($note);
					//add, locationID, roomID, name, altName, type, units, orientation, x, y, width, depth, note)
					$params = "false, $locationID, $roomID, '$jsSafeName', '$jsSafeAltName', '$type', $units, '$orientation', $xPos, $yPos, $width, $depth, '$jsSafeNote'";
					
					?><button type='button' class='editButtons_hidden' onclick="EditLocation(<?php echo $params;?>);">Edit Location</button>
					<?php
				}*/
				//editMode button
				if(CustomFunctions::UserHasLocationPermission() || CustomFunctions::UserHasRoomPermission())
				{
					echo "<button type='button' onclick='ToggleEditMode()' style='display:inline;'>Edit Mode</button>\n";
				}
				echo "</td>\n";
				echo "</tr>\n";
				echo "</table>\n";

				$showRoomDetails = true;
				if($showRoomDetails)
				{
					//details//details
					echo "<table>\n";
					echo "<tr>\n";
					echo "<td align=right class='customerDetails'>\n";
					echo "<b>Site:</b>";
					echo "</td>\n";
					echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
					echo "<a href='./?siteid=$siteID'>".MakeHTMLSafe($safeSiteFullName)."</a>";
					echo "</td>\n";
					
					echo "<td align=right class='customerDetails'>\n";
					echo "<b>Position:</b>";
					echo "</td>\n";
					echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
					echo "$pos";
					echo "</td>\n";
					
					echo "</tr>\n";
					echo "<tr>\n";
					
					echo "<td align=right class='customerDetails'>\n";
					echo "<b>Cust Access:</b>";
					echo "</td>\n";
					echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
					echo RoomCustAccess($custAccess);
					echo "</td>\n";
					
					echo "<td align=right class='customerDetails'>\n";
					echo "<b>Size:</b>";
					echo "</td>\n";
					echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
					echo "$size";
					echo "</td>\n";
					
					echo "<td align=right class='customerDetails'>\n";
					echo "<b>Orientation:</b>";
					echo "</td>\n";
					echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
					echo FormatTechDetails($editUserID,$editDate,Orientation($orientation), $qaUserID, $qaDate);
					echo "</td>\n";
					
					echo "</tr></table>\n";
				}
				
				//render room - ignore 0 width or height rooms
				if($width>0 && $depth>0)
					echo CreateRoomLayout($roomID, $name, $fullName, $xPos, $yPos, $width, $depth, $orientation, 0, 0, $custAccess);
			}
			else
			{
				echo "<div class='panel'>\n";
				echo "<div class='panel-header'>Room</div>\n";
				echo "<div class='panel-body'>\n\n";
				echo "Room ID#$roomID not found.<BR>\n";
			}
		}
		
		if(UserHasWritePermission())
		{
			//EditRoomForm();
		}
		
		echo "</div>\n";
		echo "</div>\n\n";
		
		if($roomFound)
		{
			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>$fullRoomName Details</div>\n";
			echo "<div class='panel-body'>\n\n";

			ListRoomLocationsAndDevices($roomID);
			echo "<BR>";
			ListPowerPanels("R", $roomID);
			
			echo "</div>\n";
			echo "</div>\n";
			
			if(CustomFunctions::UserHasLocationPermission() || CustomFunctions::UserHasRoomPermission())
			{
				//initialize page JS
				echo "<script type='text/javascript'>InitializeEditButton();</script>\n";
			}
		}//room found
		//return $count;
	}
	
	function ListRoomLocationsAndDevices($roomID)
	{
		//show all customers/devices at given locations - IE all devices in room 5 sorted by location - from nav links 	
		global $mysqli;
		
		$result = "";
		
		$showEmpty = true;///this was a test feature to hide empty locations
		
		//lookup site/room name for headers
		$query = "SELECT s.siteid, s.name, r.roomid, r.name, r.fullname
			FROM dcim_room AS r
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE r.roomid=?";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			//TODO handle errors better
			$result .= "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		
		$stmt->bind_Param('i', $roomID);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($siteID, $site, $roomID, $room, $roomFullName);
		$count = $stmt->num_rows;
		
		if($count==1 && $stmt->fetch())
		{//sucsessfull lookup
			$panelDescription = "Locations & devices in $site $roomFullName";
			$searchTitle = "$site $roomFullName Locations";
			
			$deviceJoin = "LEFT";
			if(!$showEmpty)
				$deviceJoin = "INNER";
			
			$query = "SELECT s.name AS site, r.name, l.locationid, l.name, l.altname, l.type, l.units, l.orientation, l.keyno, l.allocation, l.order, l.xpos, l.ypos, l.width, l.depth, l.note, l.edituser, l.editdate, l.qauser, l.qadate, 
					c.hNo, c.name AS customer, d.deviceid, d.name AS devicename, d.altname AS devicealtname, d.model, d.member,
					COUNT(d.locationid) AS count
				FROM dcim_location AS l
					$deviceJoin JOIN dcim_device d ON l.locationID = d.locationid AND d.status='A'
					LEFT JOIN dcim_customer c ON c.hno = d.hno
					LEFT JOIN dcim_room r ON l.roomid=r.roomid
					LEFT JOIN dcim_site s ON r.siteid=s.siteid
				WHERE r.roomid=?
				GROUP BY l.locationid
				ORDER BY r.name, l.name";
			
			if (!($stmt = $mysqli->prepare($query)))
			{
				//TODO handle errors better
				$result .= "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			}
			
			$stmt->bind_Param('s', $roomID);
			
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($site, $room, $locationID, $location, $altName, $locType, $units, $orientation, $keyno, $allocation, $order, $xPos, $yPos, $width, $depth, $note, $editUserID, $editDate, $qaUserID, $qaDate, $hNo, $customer, $deviceID, $deviceName,$deviceAltName, $deviceModel, $deviceMember, $deviceCount);
			$count = $stmt->num_rows;
			
			$result .= "<span class='tableTitle'>$searchTitle ($count)</span>\n";
			//add location button
			if(CustomFunctions::UserHasLocationPermission())
			{
				//add, locationID, roomID, name, altName, type, units, orientation, keyno, allocation, order, x, y, width, depth, note)
				$params = "true, -1, $roomID, '', '', '', 42, 'N', '', 'E', 'R', 0, 0, 2, 3, ''";
				$result .= "<button type='button' class='editButtons_hidden' onclick=\"EditLocation($params);\">Add New</button>";
			}
			$result .= "<BR>";
			
			if($count>0)
			{//show results
				$result .= CreateDataTableHeader(array("Location","Size","Units","Customer","Device","Allocation","Key#","Notes"), true, CustomFunctions::UserHasLocationPermission(), CustomFunctions::UserHasLocationPermission());
				
				//list result data
				$lastLocID = -1;
				$oddRow = false;
				while ($stmt->fetch())
				{
					$oddRow = !$oddRow;
					if($oddRow) $rowClass = "dataRowOne";
					else $rowClass = "dataRowTwo";
					
					$additionalDevice = ($locationID==$lastLocID);
					$lastLocID = $locationID;
					
					$fullLocationName = FormatLocation($site, $room, $location);
					$deviceFullName = GetDeviceFullName($deviceName, $deviceModel, $deviceMember,$deviceAltName, true);
					$pos = FormatSizeInFeet($xPos,$yPos);//not used
					$size = FormatSizeInFeet($width,$depth);
					
					$result .= "<tr class='$rowClass'>";
					if(!$additionalDevice)
					{
						$result .= "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>";
						$result .= "<td class='data-table-cell'>$size</td>";
						$result .= "<td class='data-table-cell'>$units ($order)</td>";
					}
					
					if(strlen($customer) > 0)
						$result .= "<td class='data-table-cell'><a href='./?host=$hNo'>".MakeHTMLSafe($customer)."</a></td>";
					else 
						$result .= "<td class='data-table-cell'>Empty</td>";
					if($deviceCount==0)
						$result .= "<td class='data-table-cell'></td>";
					else if($deviceCount==1)
						$result .= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>";
					else
						$result .= "<td class='data-table-cell'>$deviceCount Devices</td>";
					
					if(!$additionalDevice)
					{//on spanned location record
						$result .= "<td class='data-table-cell'>".LocationAllocation($allocation)."</td>";
						$result .= "<td class='data-table-cell'>".MakeHTMLSafe($keyno)."</td>";
						$displayNote = Truncate($note);
						$result .= "<td class='data-table-cell'>".MakeHTMLSafe($displayNote)."</td>";
						$result .= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate,"", $qaUserID, $qaDate)."</td>";
						
						if(CustomFunctions::UserHasLocationPermission())//disabled cuz there could be multiples entries for this location for each device and that seems confusing and there is no real need to edit the location here anyways
						{
							$jsSafeName = MakeJSSafeParam($location);
							$jsSafeAltName = MakeJSSafeParam($altName);
							$jsSafeNote = MakeJSSafeParam($note);
							$jsSafeKeyno = MakeJSSafeParam($keyno);
							//add, locationID, roomID, name, altName, type, units, orientation, keyno, allocation, order, x, y, width, depth, note)
							
							$params = "false, $locationID, $roomID, '$jsSafeName', '$jsSafeAltName', '$locType', $units, '$orientation', '$jsSafeKeyno', '$allocation', '$order', $xPos, $yPos, $width, $depth, '$jsSafeNote'";
						
							$result .= "<td class='data-table-cell-button editButtons_hidden'><button type='button' class='editButtons_hidden' onclick=\"EditLocation($params);\">Edit</button></td>";
							
							$result .= CreateQACell("dcim_location", $locationID, "", $editUserID, $editDate, $qaUserID, $qaDate, true, 1);
						}
					}
					$result .= "</tr>";
				}
				$result .= "</table>";
			}
			else 
			{
				$result .= "No Locations or devices found in $roomFullName.<BR>\n";
			}
		}//sucsessfull lookup
		else
		{
			$result .= "Room($roomID) not found.<BR>\n";
		}
		
		echo $result;
		
		if(CustomFunctions::UserHasLocationPermission())
			EditLocationForm();
		
		return $count;
	}
	
	function ListPowerCircuits($page, $pageLookupID, $panelCircuits)
	{
		global $mysqli;
		global $errorMessage;
		
		
		$locationPage = $page=="L";
		$custPage = $page=="C";
		$panelPage = $page=="P";
		
		if($locationPage)
		{
			$formAction = "./?locationid=$pageLookupID";
			$query = "SELECT s.siteid, s.name AS site, r.roomid, r.name, l.locationid, l.name AS location, pc.powercircuitid, pp.powerpanelid, pp.name, pc.circuit, pc.volts, pc.amps, pc.status, pc.load, pc.edituser, pc.editdate, pc.qauser, pc.qadate, 
					1 AS cnt
			FROM dcim_location AS l
				INNER JOIN dcim_powercircuitloc AS pcl ON l.locationid=pcl.locationid
				LEFT JOIN dcim_powercircuit AS pc ON pcl.powercircuitid=pc.powercircuitid
				LEFT JOIN dcim_powerpanel AS pp ON pp.powerpanelid=pc.powerpanelid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE l.locationid=? AND pc.powercircuitid IS NOT NULL
			GROUP BY pp.powerpanelid, pc.circuit
			ORDER BY pc.status, r.name, l.name, ABS(pp.name),pp.name, ABS(pc.circuit)";
		}
		else if($custPage)
		{//customer page - based on hno in device table
			$formAction = "./?host=$pageLookupID";
			$query = "SELECT s.siteid, s.name AS site, r.roomid, r.name, l.locationid, l.name AS location, pc.powercircuitid, pp.powerpanelid, pp.name, pc.circuit, pc.volts, pc.amps, pc.status, pc.load, pc.edituser, pc.editdate, pc.qauser, pc.qadate, 
					1 AS cnt
			FROM dcim_device AS d
				LEFT JOIN dcim_location AS l ON d.locationid=l.locationid
				INNER JOIN dcim_powercircuitloc AS pcl ON l.locationid=pcl.locationid
				LEFT JOIN dcim_powercircuit AS pc ON pcl.powercircuitid=pc.powercircuitid
				LEFT JOIN dcim_powerpanel AS pp ON pp.powerpanelid=pc.powerpanelid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE d.hno=? AND d.status='A' AND pc.powercircuitid IS NOT NULL
			GROUP BY pp.powerpanelid, pc.circuit
			ORDER BY pc.status, r.name, l.name, ABS(pp.name),pp.name, ABS(pc.circuit)";
		}
		else if($panelPage)
		{//customer page - based on hno in device table
			$formAction = "./?powerpanelid=$pageLookupID";
			$query = "SELECT s.siteid, s.name AS site, r.roomid, r.name, l.locationid, l.name AS location, pc.powercircuitid, pp.powerpanelid, pp.name, pc.circuit, pc.volts, pc.amps, pc.status, pc.load, pc.edituser, pc.editdate, pc.qauser, pc.qadate,
				(SELECT COUNT(powercircuitid) FROM dcim_powercircuitloc AS cur WHERE cur.powercircuitid=pcl.powercircuitid) AS cnt
			FROM dcim_powerpanel AS pp 
				LEFT JOIN dcim_powercircuit AS pc ON pp.powerpanelid=pc.powerpanelid
				LEFT JOIN dcim_powercircuitloc AS pcl ON pcl.powercircuitid=pc.powercircuitid
				LEFT JOIN dcim_location AS l ON l.locationid=pcl.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE pp.powerpanelid=? AND pc.powercircuitid IS NOT NULL
			ORDER BY pp.name, (pc.circuit %2 =0), pc.circuit";
		}
		
		$pagePanelID = -1;
		
		//TODO this should also distinguish colo power vs other device power that they dont actualy pay for - only realy applies to customers with non colo devices
		//TODO This should also check the device status is active and or show/filter that here	
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $pageLookupID) || !$stmt->execute())
		{
			$errorMessage[]= "Prepare failed: ListPowerCircuits() (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			echo "SQL error locating power circuit records<BR>";
		}
		else
		{
			$stmt->store_result();
			$stmt->bind_result($siteID,$site,$roomID,$room,$locationID, $location, $powerCircuitID, $powerPanelID, $panel, $circuit, $volts, $amps, $status, $load, $editUserID, $editDate, $qaUserID, $qaDate,$circuitLocCount);
			$count = $stmt->num_rows;
			
			echo "<span class='tableTitle'>Power Circuits</span>\n";
			//Add button
			if($panelPage && CustomFunctions::UserHasPowerCircuitPermission())
			{
				//function EditPowerCircuit(add, powerCircuitID, locationID, panelID, panelName, circuit, volts, amps, status, load)
				echo "<button class='editButtons_hidden' onclick=\"EditPowerCircuit(true,-1, -1, $pageLookupID, 'pnlName', 1, 120, 20, 'D', 0)\">Add New</button>\n";
			}
			echo "<BR>";
			
			if($count>0)
			{
				echo CreateDataTableHeader(array("Panel","Circuit","Volts","Amps","Status","Load","Location"),true,CustomFunctions::UserHasPowerCircuitPermission(),CustomFunctions::UserHasPowerCircuitPermission());
				
				//list result data
				$oddRow = false;
				$lastCircuitID = -1;
				$numberIn3Phase = 0; 
				while ($stmt->fetch())
				{
					$pagePanelID = $powerPanelID;//for edit form - any one will be fine - just needs to be a panel in the correct site 
					$fullLocationName = FormatLocation($site, $room, $location);
					
					if($volts==308)$numberIn3Phase++;
					else $numberIn3Phase=0;
					
					$oddRow = !$oddRow;
					if($oddRow) $rowClass = "dataRowOne";
					else $rowClass = "dataRowTwo";
					
					$visibleVolts = FormatVolts($volts);
					$visibleCircuit = $circuit;
					if($volts==208)
						$visibleCircuit = Format208CircuitNumber($circuit);
					
					$noLocation = $circuitLocCount==0;
					if($noLocation)$circuitLocCount = 1;
					
					$trippleCircuitDisplay = "";
					if($numberIn3Phase==1)//just 2 divs - outer relative parent and inner absolute
						$trippleCircuitDisplay = "<div style='position: relative;'><div class='powerCircuitCellMarker'></div></div>";
					
					echo "<tr class='$rowClass'>";
					if($powerCircuitID!=$lastCircuitID)
					{
						echo "<td class='data-table-cell' rowspan='$circuitLocCount'><a href='./?powerpanelid=$powerPanelID'>".MakeHTMLSafe($panel)."</a></td>";
						if(CustomFunctions::UserHasDevPermission())
							echo "<td class='data-table-cell' rowspan='$circuitLocCount'>$trippleCircuitDisplay<span title='powercircuitid=$powerCircuitID' style='position:relative; z-index:2;'>".MakeHTMLSafe($visibleCircuit)."</span></td>";
						else
							echo "<td class='data-table-cell' rowspan='$circuitLocCount'>$trippleCircuitDisplay".MakeHTMLSafe($visibleCircuit)."</td>";
						echo "<td class='data-table-cell' rowspan='$circuitLocCount'>$visibleVolts</td>";
						echo "<td class='data-table-cell' rowspan='$circuitLocCount'>$amps</td>";
						echo "<td class='data-table-cell' rowspan='$circuitLocCount'>".PowerStatus($status)."</td>";
						echo "<td class='data-table-cell' rowspan='$circuitLocCount'>".$load."A ".FormatPowerUtilization($load, $amps)."</td>";
					}
					if($noLocation)
						echo "<td class='data-table-cell'></td>";//not linked to any location
					else
						echo "<td class='data-table-cell'><a href='./?locationid=$locationID'>".MakeHTMLSafe($fullLocationName)."</a></td>";
					if($powerCircuitID!=$lastCircuitID)
					{
						echo "<td class='data-table-cell' rowspan='$circuitLocCount'>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
						if(CustomFunctions::UserHasPowerCircuitPermission())
						{
							//edit button
							echo "<td class='data-table-cell-button editButtons_hidden' rowspan='$circuitLocCount'>\n";
							
							//function EditPowerCircuit(add, powerCircuitID, locationID, panelID, panelName, circuit, volts, amps, status, load)
							$jsSafePanel = MakeJSSafeParam($panel);
							$jsSafeCircuit = MakeJSSafeParam($circuit);
							$jsSafelocationID = ($noLocation ? -1 : $locationID);
							$params = "false,$powerCircuitID, $jsSafelocationID, $powerPanelID, '$jsSafePanel', '$jsSafeCircuit', $volts, $amps, '$status', $load";
							?><button onclick="EditPowerCircuit(<?php echo $params;?>)">Edit</button>
							<?php 
							echo "</td>\n";
							
							echo CreateQACell("dcim_powercircuit", $powerCircuitID, $formAction, $editUserID, $editDate, $qaUserID, $qaDate,true,$circuitLocCount);
						}
					}
					$lastCircuitID = $powerCircuitID;
					echo "</tr>";
					if($numberIn3Phase==3)$numberIn3Phase = 0;//reset for next
				}
				echo "</table>";
			}
			else
			{
				echo "No relevant power records found.<BR>\n";
			}
		}
		
		if(CustomFunctions::UserHasPowerCircuitPermission())
		{
			if($panelPage) $pagePanelID = $pageLookupID;
			EditPowerCircuitForm($formAction, $pagePanelID, $page, $panelCircuits);
		}
		return $count;
	}
	
	function EditPowerCircuitForm($action, $powerPanelIDInput, $page, $panelCircuits)
	{
		global $mysqli;
		global $errorMessage;
		
		$deleteEnabled = $page=="L" || $page=="P";
		
		//build Location combo options
		$query = "SELECT s.siteid, s.name, r2.roomid, r2.name, r2.fullname, l.locationid, l.name
			FROM dcim_powerpanel AS pp
				LEFT JOIN dcim_room AS r1 ON r1.roomid=pp.roomid
				LEFT JOIN dcim_site AS s ON s.siteid=r1.siteid
				LEFT JOIN dcim_room AS r2 ON r2.siteid=s.siteid
				LEFT JOIN dcim_location AS l ON l.roomid=r2.roomid
			WHERE pp.powerpanelid=? AND l.locationid IS NOT NULL
			ORDER BY s.name, r2.name, l.name";
		
		$locationOptions = "<option value='-5' Selected>SQL Error</option>\n";
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $powerPanelIDInput) || !$stmt->execute())
			$errorMessage[] = "EditPowerCircuitForm() Prepare 1 failed: (" . $mysqli->errno . ") " . $mysqli->error;
		else
		{
			$stmt->store_result();
			$stmt->bind_result($siteID, $siteName, $roomID, $roomName, $roomFullName, $locationID, $locationName);
			$locationOptions = "";
			$locationOptions.= "<option value='-1'>None</option>\n";
			while ($stmt->fetch())
			{
				$fullLocationName = MakeHTMLSafe("$roomName $locationName");
				$locationOptions.= "<option value='$locationID'>$fullLocationName</option>\n";
			}
		}
		
		//build Panel combo options
		$query = "SELECT s.siteid, s.name, pp2.powerpanelid, pp2.name
			FROM dcim_powerpanel AS pp1
				LEFT JOIN dcim_room AS r1 ON r1.roomid=pp1.roomid
				LEFT JOIN dcim_site AS s ON s.siteid=r1.siteid
				LEFT JOIN dcim_room AS r2 ON r2.siteid=s.siteid
				LEFT JOIN dcim_powerpanel AS pp2 ON pp2.roomid=r2.roomid
			WHERE pp1.powerpanelid=? AND pp2.powerpanelid IS NOT NULL
			ORDER BY s.name, pp2.name";
		
		$panelOptions = "<option value='-5' Selected>SQL Error</option>\n";
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $powerPanelIDInput) || !$stmt->execute())
			$errorMessage[] = "EditPowerCircuitForm() Prepare 2 failed: (" . $mysqli->errno . ") " . $mysqli->error;
		else
		{
			$stmt->store_result();
			$stmt->bind_result($siteID, $siteName, $panelID, $panelName);
			$panelOptions= "";
			while ($stmt->fetch())
			{
				$fullPanelName = MakeHTMLSafe($panelName);
				$panelOptions.= "<option value='$panelID'>$panelName</option>\n";
			}
		}
		
		//edit/Add Power Circuit form
		?>
		<div id='EditPowerCircuitMsg' class='hidden'></div>
		<div id='EditPowerCircuitEntry' class='hidden'>
		<BR>
		<table><tr><td>
		<form action="<?php echo $action;?>" method='post' id='EditPowerCircuitForm' onsubmit='return SavePowerCircuit()' class=''>
			<fieldset>
				<legend id=EditPowerCircuitEntryLegend><b>Init Power Circuit</b></legend>
				<table>
					<tr>
						<td align='right'>Panel:</td>
						<td>
							<select id=EditPowerCircuit_powerpanelid name="powerpanelid" tabindex=1>
								<?php echo $panelOptions; ?>
							</select>
							Circuit:
							<div class='inputToolTipContainer'><input id='EditPowerCircuit_circuit' type='number' tabindex=2 size=5 name='circuit' value='' placeholder='1' step=1 min=1 max='<?php echo $panelCircuits;?>'>
							<span class=inputTooltip>If 208v this must be the lesser circuit</span></div>
						</td>
					</tr>
					<tr>
						<td align='right'>Location:</td>
						<td>
							<select id=EditPowerCircuit_locationid name="locationid" tabindex=3>
								<?php echo $locationOptions; ?>
							</select>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Volts:</td>
						<td align='left'>
							<select id=EditPowerCircuit_volts name="volts" tabindex=4>
								<option value='120'>120v</option>
								<option value='208'>208v</option>
								<option value='308'>208v3p</option>
							</select>
							Amps:
							<select id=EditPowerCircuit_amps name="amps" tabindex=5>
								<option value='20'>20A</option>
								<option value='30'>30A</option>
								<option value='40'>40A</option>
								<option value='50'>50A</option>
								<option value='100'>100A</option>
							</select>
						</td>
					</tr>
					<tr>
						<td align='right'>Active</td>
						<td width=1 align='left'>
							<input id=EditPowerCircuit_status type='checkbox' tabindex=6 name='status' value='A' onclick='EditPowerCircuit_StatusClicked()' class=''>
							Load:
							<input id=EditPowerCircuit_load type='number' tabindex=7 name='load' size=5 placeholder='2.04' min=0 max=100 step=0.01 onchange='EditPowerCircuit_LoadChanged()' class=''>
						</td>
					</tr>
					<tr>
						<td colspan=2><table style='width:100%;'><tr>
							<td align=left>
								<?php if($deleteEnabled)echo "<button id='EditPowerCircuit_deletebutton' type='button' onclick='DeletePowerCircuit()' tabindex=10>Delete</button>";?>
							</td>
							<td align='right'>
								<button type="button" onclick="HideAllEditForms()" tabindex=9>Cancel</button>
								<input type="submit" value="Save" tabindex=8>
							</td>
						</tr></table></td>
					</tr>
				</table>
				<input id=EditPowerCircuit_powercircuitid type='hidden' name='powercircuitid' value='-2'>
				<input id=EditPowerCircuit_action type='hidden' name='action' value='null'>
				<input type="hidden" name="page_instance_id" value="<?php echo end($_SESSION['page_instance_ids']); ?>"/>
			</fieldset>
		</form>
		</td></tr></table></div>
		<?php
	}
	
	function ListCustomerSubnets($page, $key)
	{
		global $mysqli;
		global $errorMessage;
		
		$customerPage = false;
		$searchPage = false;
		if($page=="C")
		{
			$customerPage = true;
			$input= $key;
			$formAction = "./?host=$key";
			$innerFilter = "d.hno=?";
			$outerFilter = "1";
		}
		else if($page=="?")
		{
			$input = "%".trim($key)."%";
			$innerFilter = "1";
			$outerFilter= "CONCAT(v.vlan,'~',v.subnet,'~',v.note) LIKE ? AND ? != 'bshere'";//and portion need to keep the bing_param count
		}
		
		//TODO this is not properly showing active ports
		//GROUP to by VLAN/SUBNET to show unique networks link to customer
		$query = "SELECT d.deviceid, d.name, dp.deviceportid, v.vlanid, v.vlan, v.subnet, v.mask, v.first, v.last, v.gateway, v.note, v.edituser, v.editdate, v.qauser, v.qadate
			FROM
				(SELECT pv.vlan
						FROM dcim_device AS d
							LEFT JOIN dcim_deviceport AS dp ON d.deviceid=dp.deviceid AND NOT(dp.status='D')
							INNER JOIN dcim_portconnection AS pc ON dp.deviceportid=pc.childportid
							INNER JOIN dcim_deviceport AS sp ON pc.parentportid=sp.deviceportid
							INNER JOIN dcim_device AS s ON sp.deviceid=s.deviceid
							LEFT JOIN dcim_portvlan AS pv ON sp.deviceportid=pv.deviceportid
						WHERE $innerFilter
					UNION 
					SELECT pv.vlan
						FROM dcim_device AS d
							LEFT JOIN dcim_deviceport AS dp ON d.deviceid=dp.deviceid AND NOT(dp.status='D')
							INNER JOIN dcim_portconnection AS pc ON dp.deviceportid=pc.parentportid
							INNER JOIN dcim_deviceport AS sp ON pc.childportid=sp.deviceportid
							INNER JOIN dcim_device AS s ON sp.deviceid=s.deviceid
							LEFT JOIN dcim_portvlan AS pv ON dp.deviceportid=pv.deviceportid
						WHERE $innerFilter) AS csr
				LEFT JOIN dcim_vlan AS v ON v.vlan=csr.vlan
				INNER JOIN dcim_portvlan AS pv ON pv.vlan=v.vlan
				INNER JOIN dcim_deviceport AS dp ON dp.deviceportid=pv.deviceportid
				LEFT JOIN dcim_device AS d ON d.deviceid=dp.deviceid
			WHERE $outerFilter
			GROUP BY v.vlanid
			ORDER BY v.vlan";
		
		if (!($stmt = $mysqli->prepare($query))) 
		{
			//TODO handle errors better
			$errorMessage[]="Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		
		$stmt->bind_Param('ss', $input, $input);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($deviceID, $deviceName, $devicePortID, $vlanID, $vlan, $subnet, $mask, $first, $last, $gateway, $note, $editUserID, $editDate, $qaUserID, $qaDate);
		$count = $stmt->num_rows;
		
		echo "<span class='tableTitle'>Subnets</span>\n";
		//Add button
		if(!$searchPage && UserHasWritePermission())
		{
			//function EditSubnet(add, portID,vlan,subnet,mask,gateway,first,last,note)
			?><button class='editButtons_hidden' onclick="EditSubnet(true,-1,-1,'','','','','','','')">Add New</button>
			<?php 
		}
		echo "<BR>";
		if($count>0)
		{
			echo CreateDataTableHeader(array("Device","VLAN","Subnet","Mask","First","Last","Gateway","Note"),!$searchPage,!$searchPage && UserHasWritePermission(),!$searchPage && UserHasWritePermission());
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				echo "<tr class='$rowClass'>";
				echo "<td class='data-table-cell'><a href='?deviceid=$deviceID'>".MakeHTMLSafe($deviceName)."</a></td>";
				echo "<td class='data-table-cell'>".FormatVLAN($vlan)."</td>";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($subnet)."</td>";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($mask)."</td>";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($first)."</td>";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($last)."</td>";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($gateway)."</td>";
				echo "<td class='data-table-cell'>".TruncateWithSpanTitle(MakeHTMLSafe($note))."</td>";
				if(!$searchPage)
				{
					echo "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
					if(!$searchPage && UserHasWritePermission())
					{
						//edit button
						echo "<td class='data-table-cell-button editButtons_hidden'>\n";
						
						$jsSafeVLAN = MakeJSSafeParam($vlan);
						$jsSafeSubnet = MakeJSSafeParam($subnet);
						$jsSafeMask = MakeJSSafeParam($mask);
						$jsSafeFirst = MakeJSSafeParam($first);
						$jsSafeLast = MakeJSSafeParam($last);
						$jsSafeGateway = MakeJSSafeParam($gateway);
						$jsSafeNote = MakeJSSafeParam($note);
						//function EditSubnet(add, portID,vlan,subnet,mask,gateway,first,last,note)
						$params = "false,$vlanID, $devicePortID, '$jsSafeVLAN', '$jsSafeSubnet', '$jsSafeMask', '$jsSafeGateway', '$jsSafeFirst', '$jsSafeLast', '$jsSafeNote'";
						?><button onclick="EditSubnet(<?php echo $params;?>)">Edit</button>
						<?php 
						echo "</td>\n";
						
						echo CreateQACell("dcim_vlan", $vlanID, $formAction, $editUserID, $editDate, $qaUserID, $qaDate);
					}
				}
				echo "</tr>";
			}
			echo "</table>";
		}
		else 
		{
			echo "No VLAN subnet records found.<BR>\n";
		}
		
		if($customerPage && UserHasWritePermission())
		{
			EditSubnetForm($formAction,$key);
		}
		
		return $count;
	}
	
	function EditSubnetForm($action, $hNo)
	{
		global $mysqli;
		global $errorMessage;
		
		//select all Active Switch ports for/linked to this customer
		//UNION all connected chilren
		//UNION all connected parents
		//then looking up those port ids to get a list of all switch ports connected to or belonging to this customer 
		$query = "SELECT d.deviceid, d.model, d.name, d.altname, d.member, dp.deviceportid, dp.pic, dp.port, dp.type 
			FROM(
				SELECT dp.deviceportid AS portid
					FROM dcim_device d
						LEFT JOIN dcim_deviceport AS dp ON d.deviceid=dp.deviceid
					WHERE d.hno=? AND d.type='S' AND dp.status='A'
				UNION
				SELECT pc.childportid AS portid
					FROM dcim_device d
						LEFT JOIN dcim_deviceport AS dp ON d.deviceid=dp.deviceid
						INNER JOIN dcim_portconnection AS pc ON pc.parentportid=dp.deviceportid
					WHERE d.hno=? AND dp.status='A'
				UNION
				SELECT pc.parentportid AS portid
					FROM dcim_device d
						LEFT JOIN dcim_deviceport AS dp ON d.deviceid=dp.deviceid
						INNER JOIN dcim_portconnection AS pc ON pc.childportid=dp.deviceportid
					WHERE d.hno=? AND dp.status='A'
				) AS csr
				INNER JOIN dcim_deviceport AS dp ON csr.portid=dp.deviceportid
				INNER JOIN dcim_device AS d ON d.deviceid=dp.deviceid
			WHERE d.type='S' AND dp.status='A'
			ORDER BY d.name, d.member, dp.pic, dp.port";
		
		
		$portOptions = "";
			
		if (!($stmt = $mysqli->prepare($query))) 
		{
			$errorMessage[] = "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
		}
		else
		{
			//$stmt->bind_Param('s', $input);// no params
			
			$stmt->bind_Param('sss', $hNo, $hNo, $hNo);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($deviceID, $model, $deviceName,$deviceAltName, $member, $devicePortID, $pic, $port, $type);
			while ($stmt->fetch()) 
			{
				$deviceFullName = GetDeviceFullName($deviceName, $model, $member,$deviceAltName, true);
				$portFullName = FormatPort($member, $model, $pic, $port, $type);
				
				$portOptions .= "<option value=$devicePortID>".MakeHTMLSafe($deviceFullName." ".$portFullName)."</option>\n";
			}
		}
		
		//edit/Add Subnet form
		?>
		<div id='EditSubnetMsg' class='hidden'></div>
		<div id='EditSubnetEntry' class='hidden'>
		<BR>
		<table><tr><td>
		<form action="<?php echo $action;?>" method='post' id='EditSubnetForm' onsubmit='return SaveSubnet()' class=''>
			<fieldset>
				<legend id=EditSubnetEntryLegend><b>Init Subnet</b></legend>
				<table>
					<tr>
						<td align='right'>Port:</td>
						<td>
							<select id=EditSubnet_portid name="deviceportid" tabindex=1>
								<?php echo $portOptions; ?>
							</select>
						</td>
					</tr>
					<tr>
						<td align='right'>VLAN:</td>
						<td>
							<input id='EditSubnet_vlan' type='text' tabindex=2 name='vlan' value='' placeholder='###' size=3 class=''>
							 Leave blank to create a new temp VLAN
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Subnet:</td>
						<td align='left'>
							<div class='inputToolTipContainer'><input id='EditSubnet_subnet' type='text' tabindex=3 size=15 name='subnet' value='' placeholder='###.###.###.###/##' onkeyup='EditSubnet_SubnetChanged()' class=''>
							<span class=inputTooltip>Editing this will update the next IP fields</span></div>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Subnet Mask:</td>
						<td align='left'>
							<input id='EditSubnet_mask' type='text' tabindex=4 size=12 name='mask' value='' placeholder='###.###.###.###' class=''>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Gateway:</td>
						<td align='left'>
							<input id='EditSubnet_gateway' type='text' tabindex=5 size=12 name='gateway' value='' placeholder='###.###.###.###' class=''>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>First IP:</td>
						<td align='left'>
							<input id='EditSubnet_first' type='text' tabindex=6 size=12 name='first' value='' placeholder='###.###.###.###' class=''>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Last IP:</td>
						<td align='left'>
							<input id='EditSubnet_last' type='text' tabindex=7 size=12 name='last' value='' placeholder='###.###.###.###' class=''>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Note:</td>
						<td align='left'>
							<input id='EditSubnet_note' type='text' tabindex=8 size=15 name='note' value='' placeholder='' class=''>
						</td>
					</tr>
					<tr>
						<td colspan=2><table style='width:100%;'><tr>
							<td align=left>
								<button id='EditSubnet_deletebutton' type='button' onclick='DeleteSubnet()' tabindex=11>Delete</button>
							</td>
							<td align='right'>
								<button type="button" onclick="HideAllEditForms()" tabindex=10>Cancel</button>
								<input type="submit" value="Save" tabindex=9>
							</td>
						</tr></table></td>
					</tr>
				</table>
				<input id=EditSubnet_vlanid type='hidden' name='vlanid' value='-2'>
				<input id=EditSubnet_action type='hidden' name='action' value='null'>
				<input type="hidden" name="page_instance_id" value="<?php echo end($_SESSION['page_instance_ids']); ?>"/>
			</fieldset>
		</form>
		</td></tr></table></div>
		<?php
	}
	
	function EditConnectionForm($action, $hNo)
	{
		global $mysqli;
		global $deviceModels;
		global $config_subnetsEnabled;
		
		//edit/Add Connection form
		//-default values - never seen
		$actionText = "Addy";
		$patchesInput = "patches input";
		
		//build list of devices combo options
		$query = "SELECT d.deviceid, d.hno, d.name, d.altname, d.model, d.member
			FROM dcim_device AS d
			ORDER BY /*d.type='S' DESC,*/ d.name, d.member";
			
		if (!($stmt = $mysqli->prepare($query))) 
		{
			//TODO handle errors better
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($deviceID, $deviceHNo, $deviceName,$deviceAltName, $deviceModel, $deviceMember);
		$count = $stmt->num_rows;
		
		$childDeviceOptions = "";
		$parentDeviceOptions = "";
		
		$customerDeviceOptions = "";
		$customerAndInternalOptions = "";
		$allDeviceOptions = "";
		if($count>0)
		{
			$short = true;
			while ($stmt->fetch()) 
			{
				$fullName = GetDeviceFullName($deviceName, $deviceModel, $deviceMember,$deviceAltName, $short);
				if($hNo==$deviceHNo)
				{
					$customerDeviceOptions .= "<option value='$deviceID'>".MakeHTMLSafe($fullName)."</option>\n";
					$customerAndInternalOptions .= "<option value='$deviceID'>".MakeHTMLSafe($fullName)."</option>\n";
				}
				$allDeviceOptions .= "<option value='$deviceID'>".MakeHTMLSafe($fullName)."</option>\n";
				
				//TODO: Hadle this selection better - put 'local' devices at top and maybe a divider between them - maybe add a internal flag to customer record #77
				if($deviceHNo=='189165')//Internal
					$customerAndInternalOptions .= "<option value='$deviceID'>".MakeHTMLSafe($fullName)."</option>\n";
			}
			
			$childDeviceOptions = $customerDeviceOptions;
			$parentDeviceOptions = $customerAndInternalOptions;
			
			//if(CustomFunctions::UserHasDevPermission())
			{
				//dont limit to just this customers devices - show all devices as child and parent
				$childDeviceOptions = $allDeviceOptions;
				$parentDeviceOptions = $allDeviceOptions;
			}
		}
		
		?>
		<div id='EditConnectionMsg' class='hidden'></div>
		<div id='EditConnectionEntry' class='hidden'>
		<BR>
		<table><tr><td>
		<form action="<?php echo $action;?>" method='post' id='EditConnectionForm' onsubmit='return SaveConnection()' class=''>
			<fieldset>
				<legend id=EditConnectionEntryLegend><b><?php echo $actionText;?> Connection</b></legend>
				<table>
					<tr>
						<td align='right'>Child Device:</td>
						<td width=1>
							<select name='childdeviceid' id='EditConnection_childdeviceid' onchange='DeviceSelectChanged(true,-1)' tabindex=1>
								<?php echo $childDeviceOptions;?>
							</select>
						</td>
						<td>
							<select name='childportid' id='EditConnection_childportid' tabindex=2>
								<option value=1>Loading...</option>
							</select>
						</td>
					</tr>
					<tr>
						<td align='right'>Parent Device:</td>
						<td width=1>
							<select name='parentdeviceid' id='EditConnection_parentdeviceid' onchange='DeviceSelectChanged(false,-1)' tabindex=3>
								<?php echo $parentDeviceOptions;?>
							</select>
						</td>
						<td>
							<select name='parentportid' id='EditConnection_parentportid' tabindex=4>
								<option value=1>Loading...</option>
							</select>
						</td>
					</tr>
					<tr>
						<td align='right' width=1>Patches:</td>
						<td align='left' colspan='2'>
							<input id='EditConnection_patches' type='text' tabindex=5 size=50 name='patches' value='<?php echo $patchesInput;?>' placeholder='10.01/4 - G/13 - 10.24/1' class=''>
						</td>
					</tr>
					<?php if($config_subnetsEnabled){?>
					<tr id='EditConnection_updateportsrow'>
						<td align='right' width=1>Port Changes:</td>
						<td align='left' colspan='2'>
							<select name='updateports' id='EditConnection_updateports' tabindex=4>
								<option value=T SELECTED>Update/Delete VLANs</option>
								<option value=F>No change</option>
							</select>
						</td>
					</tr>
					<?php }?>
					<tr>
						<td colspan='1' align='left'>
							<button id='EditConnection_deletebutton' type="button" onclick="DeleteConnection()" tabindex=8>Delete</button>
						</td>
						<td colspan='2' align='right'>
							<button type="button" onclick="HideAllEditForms()" tabindex=7>Cancel</button>
							<input type="submit" value="Save" tabindex=6>
						</td>
					</tr>
				</table>
				<input id=EditConnection_portconnectionid type='hidden' name='portconnectionid' value='-2'>
				<input id=EditConnection_action type='hidden' name='action' value='null'>
				<input type="hidden" name="page_instance_id" value="<?php echo end($_SESSION['page_instance_ids']); ?>"/>
			</fieldset>
		</form>
		</td></tr></table></div>
		<?php
	}
	
	function ListDevicePorts($deviceKeyInput, $deviceFullName, $onChassisPage=false)
	{
		global $mysqli;
		global $config_subnetsEnabled;
		
		
		if($onChassisPage)
			$filter = "d.name=?";
		else
			$filter = "d.deviceid=?";
		
		
		//group concat by port to combine vlans
		$query = "SELECT
				dp.deviceid, dp.deviceportid, d.name, d.altname, d.member, d.model, dp.pic, dp.port, dp.mac,
				sp.deviceid AS sid, sp.deviceportid AS spid, s.name AS sname, s.altname AS saltname, s.member AS smember, s.model AS smodel, sp.pic AS spic, sp.port AS sport,l.locationid, site.name,r.name,l.name,
				d.hno, dp.type, dp.speed, dp.note, dp.status, sc.hno AS switchhno, sc.name AS switchcust, dp.edituser, dp.editdate, dp.qauser, dp.qadate,
				CAST(GROUP_CONCAT(IF(pv.vlan<0,CONCAT('Temp-',ABS(pv.vlan)),pv.vlan) ORDER BY pv.vlaN<0, ABS(pv.vlaN) SEPARATOR ', ') AS CHAR) AS vlans
			FROM dcim_device AS d
				LEFT JOIN dcim_deviceport AS dp ON d.deviceid=dp.deviceid
				LEFT JOIN (
							SELECT pcA.portconnectionid,pcA.childportid AS srcportid,pcA.parentportid AS destportid,pcA.patches,pcA.edituser,pcA.editdate FROM dcim_portconnection AS pcA
							UNION ALL
							SELECT pcB.portconnectionid,pcB.parentportid AS srcportid,pcB.childportid AS destportid,pcB.patches,pcB.edituser,pcB.editdate FROM dcim_portconnection AS pcB) 
					AS pc ON dp.deviceportid=pc.srcportid
				LEFT JOIN dcim_deviceport AS sp ON pc.destportid=sp.deviceportid
				LEFT JOIN dcim_device AS s ON sp.deviceid=s.deviceid
				LEFT JOIN dcim_customer AS sc ON sc.hno=s.hno
				LEFT JOIN dcim_portvlan AS pv ON dp.deviceportid=pv.deviceportid
				LEFT JOIN dcim_location AS l ON s.locationid=l.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS site ON r.siteid=site.siteid
			WHERE $filter
			GROUP BY dp.deviceportid
			ORDER BY 3,4,6,7";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			//TODO handle errors better
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		
		//might filter differenctely baced on chassis page and filter
		$stmt->bind_Param('s', $deviceKeyInput);
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($deviceID, $devicePortID, $deviceName, $deviceAltName, $member, $model, $pic, $port, $mac, 
						   $switchID, $switchPortID, $switchName, $switchAltName, $switchMember, $switchModel, $switchPic, $switchPort, $switchLocationID,$switchSite,$switchRoom,$switchLocationName, 
						   $hNo, $type, $speed, $note, $status, $switchHNo, $switchCust, $editUserID, $editDate, $qaUserID, $qaDate, $vlan);
		$portCount = $stmt->num_rows;
		
		
		echo "<table width=100%><tr><td align=left>";
		
		if($onChassisPage)
			echo "<span class='tableTitle'>$deviceKeyInput Ports</span>\n";
		else
			echo "<span class='tableTitle'>Device Ports</span>\n";

		//show active ports button
		echo "<span class='showAllToggleButton'><button id='showAllPortsButton' onclick='ToggleShowAllPorts()'>Show all</button></span>\n";
			
		//add port button
		if(!$onChassisPage && UserHasAdminPermission())
		{
			// add button to add new Port
			$jsSafeDeviceFullName = MakeJSSafeParam($deviceFullName);
			//EditDevicePort(event,add, devicePortID, deviceID, deviceName, portName, pic, port, type, status, speed, mac, note)
			//redundant check for admin priv, but left for code completion sake
			echo "<button class='editButtons_hidden' onclick=\"EditDevicePort(event,true,".(CustomFunctions::UserHasPortAddEditPermission()?"true":"false").",-1,$deviceKeyInput,'$jsSafeDeviceFullName','',0,0,'E','D','','','')\">Add New</button>\n";
		}
		
		//editMode button
		echo "</td><td align=right>";
		if(UserHasWritePermission())
		{
			if($onChassisPage)
			{
				$formAction = "./?chassisname=$deviceKeyInput";
				echo "<button type='button' onclick='ToggleEditMode()' style='display:inline;'>Edit Mode</button>\n";
			}
			else 
			{
				$formAction = "./?deviceid=$deviceKeyInput";
			}
		}
		echo "</td></tr></table>";
		
		if($portCount>0)
		{
			$tableWithAllData = "";
			$tableWithActiveData = "";

			if($config_subnetsEnabled)
				$tableHeader = CreateDataTableHeader(array("Device","Port&#x25B2;","MAC","Connected Device","Port","Speed","Status","VLANs","Note"),true,UserHasWritePermission(),UserHasWritePermission());
			else 
				$tableHeader = CreateDataTableHeader(array("Device","Port&#x25B2;","MAC","Connected Device","Port","Speed","Status","Note"),true,UserHasWritePermission(),UserHasWritePermission());
			
			$tableWithAllData = $tableHeader;
			$tableWithActiveData = $tableHeader;
			
			//list result data
			$lastDevicePortID = -1;
			$lastSwitchPortID = -1;
			$oddRow = false;
			$oddActiveRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				$record = "";
				$recordHead = "<tr class='$rowClass'>";
				
				$activeRow = $status=="A";
				if($activeRow)
				{
					$oddActiveRow = !$oddActiveRow;
					if($oddActiveRow) $activeRowClass = "dataRowOne";
					else $activeRowClass = "dataRowTwo";
					$activeRecordHead = "<tr class='$activeRowClass'>";
				}
				
				$deviceFullName = GetDeviceFullName($deviceName, $model, $member,$deviceAltName, true);
				$switchFullName = GetDeviceFullName($switchName, $switchModel, $switchMember,$switchAltName, true);
				$switchLocationFullName = FormatLocation($switchSite,$switchRoom,$switchLocationName);
				
				$portFullName = FormatPort($member, $model, $pic, $port, $type);
				$switchPortFullName = FormatPort($switchMember, $switchModel, $switchPic, $switchPort, $type);
				
				if(strlen($switchFullName) > 0)
					$switchDisplayName = "<a href='./?deviceid=$switchID'>".MakeHTMLSafe($switchFullName)."</a> (<a href='./?host=$switchHNo'>$switchCust</a> - <a href='./?locationid=$switchLocationID'>$switchLocationFullName</a>)";
				else
				{
					$switchDisplayName = "";
					$switchPortFullName = "";
				}
				
				//table values
				if($onChassisPage)
					$record .= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>";
				else 
					$record .= "<td class='data-table-cell'>".MakeHTMLSafe($deviceFullName)."</td>";
					
				if(CustomFunctions::UserHasDevPermission())
					$record .= "<td class='data-table-cell'><span title='deviceportid=$devicePortID'>$portFullName</span></td>";
				else
					$record .= "<td class='data-table-cell'>$portFullName</td>";
				$record .= "<td class='data-table-cell'>".MakeHTMLSafe($mac)."</td>";
				$record .= "<td class='data-table-cell'>$switchDisplayName</td>";
				if(CustomFunctions::UserHasDevPermission())
					$record .= "<td class='data-table-cell'><span title='switchportid=$switchPortID'>$switchPortFullName</span></td>";
				else
					$record .= "<td class='data-table-cell'>$switchPortFullName</td>";
				$record .= "<td class='data-table-cell'>".MakeHTMLSafe($speed)."</td>";
				$record .= "<td class='data-table-cell'>".DevicePortStatus($status)."</td>";
				if($config_subnetsEnabled)
					$record .= "<td class='data-table-cell'>$vlan</td>";
				$record .= "<td class='data-table-cell'>".MakeHTMLSafe($note)."</td>";
				$record .= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
				
				//edit button cell
				if(UserHasWritePermission())
				{
					//edit button
					$record .= "<td class='data-table-cell-button editButtons_hidden'>\n";
					$jsSafeDeviceFullName = MakeJSSafeParam($deviceFullName);
					$jsSafePortFullName = MakeJSSafeParam($portFullName);
					$jsSafeMac = MakeJSSafeParam($mac);
					$jsSafeSpeed = MakeJSSafeParam($speed);
					$jsSafeNote = MakeJSSafeParam($note);
					//EditDevicePort(event,add, devicePortID, deviceID, deviceName, portName, pic, port, type, status, speed, mac, note)
					$portEditJS = "EditDevicePort(event,false,".(CustomFunctions::UserHasPortAddEditPermission()?"true":"false").",$devicePortID,$deviceID,'$jsSafeDeviceFullName','$jsSafePortFullName',$pic,$port,'$type','$status','$jsSafeSpeed','$jsSafeMac','$jsSafeNote')";
					
					$record .= "<button onclick=\"$portEditJS\">Edit</button>\n";
					$record .= "</td>\n";
					
					$record .= CreateQACell("dcim_deviceport", $devicePortID, $formAction, $editUserID, $editDate, $qaUserID, $qaDate);
				}
				
				$record .= "</tr>";
				
				$tableWithAllData .= $recordHead.$record;
				if($activeRow)
				{
					$tableWithActiveData .= $activeRecordHead.$record;
				}
				
				$lastDevicePortID = $devicePortID;
				$lastSwitchPortID = $switchPortID;
			}
			
			$tableWithAllData .= "</table>";
			$tableWithActiveData .= "</table>";
			
			echo "<div id='allPortsTable'>$tableWithAllData</div>\n";
			echo "<div id='activePortsTable'>$tableWithActiveData</div>\n";
			
			//Initialize show all ports
			echo "<script type='text/javascript'>InitializeShowAllPortsButton();</script>\n";
			
			if(UserHasWritePermission())
			{	
				//TODO this could fail on chasis page if no ports are found... shouldn't be possible - deviceID will be null
				EditDevicePortForm($formAction);
			}
		}
		else
			echo "No device port info found<BR>";
		return $portCount;
	}
	
	function ListActiveCustomerDeviceConnections($hNo)
	{
		global $mysqli;
		global $config_subnetsEnabled;
		$formAction = "./?host=$hNo";
		
		$query = "SELECT 
				dp.deviceid, dp.deviceportid, d.name, d.altname, d.member, d.model, dp.pic, dp.port, dp.mac,
				sp.deviceid AS sid, sp.deviceportid AS spid, s.name AS sname, s.altname AS saltname, s.member AS smember, s.model AS smodel, sp.pic AS spic, sp.port AS sport,
				dp.type, dp.speed, dp.note, dp.status, pc.portconnectionid, pc.patches, pc.relationship, pc.edituser, pc.editdate, pc.qauser, pc.qadate,
				CAST(GROUP_CONCAT(IF(pv.vlan<0,CONCAT('Temp-',ABS(pv.vlan)),pv.vlan) ORDER BY pv.vlaN<0, ABS(pv.vlaN) SEPARATOR ', ') AS CHAR) AS vlans,
				l.locationid, l.name, sl.locationid, sl.name
			FROM dcim_device AS d
				INNER JOIN dcim_deviceport AS dp ON d.deviceid=dp.deviceid
				INNER JOIN (
							SELECT pcA.portconnectionid,pcA.childportid AS childportid,pcA.parentportid AS parentportid,
									pcA.patches,pcA.edituser,pcA.editdate,pcA.qauser,pcA.qadate, 'Child' AS relationship 
								FROM dcim_portconnection AS pcA
							UNION ALL
							SELECT pcB.portconnectionid,pcB.parentportid AS childportid,pcB.childportid AS parentportid,
									pcB.patches,pcB.edituser,pcB.editdate,pcB.qauser,pcB.qadate, 'Parent' AS relationship 
								FROM dcim_portconnection AS pcB)
					AS pc ON dp.deviceportid=pc.childportid
				INNER JOIN dcim_deviceport AS sp ON pc.parentportid=sp.deviceportid
				INNER JOIN dcim_device AS s ON sp.deviceid=s.deviceid
				LEFT JOIN dcim_portvlan AS pv ON sp.deviceportid=pv.deviceportid
				INNER JOIN dcim_location AS l ON d.locationid=l.locationid
				INNER JOIN dcim_location AS sl ON s.locationid=sl.locationid
			WHERE d.hno=?
			GROUP BY pc.portconnectionid
			ORDER BY 3,4,6,7";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			//TODO handle errors better
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		
		$stmt->bind_Param('s', $hNo);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($deviceID, $devicePortID, $deviceName,$deviceAltName, $member, $model, $pic, $port, $mac, 
						   $switchID, $switchPortID, $switchName,$switchAltName, $switchMember, $switchModel, $switchPic, $switchPort, 
						   $type, $speed, $note, $status, $portConnectionID, $patches, $relationship, $editUserID, $editDate, $qaUserID, $qaDate, 
							$vlan, $dLocID, $dLocName, $sLocID, $sLocName);
		$count = $stmt->num_rows;
		
		
		echo "<span class='tableTitle'>Device Connections</span>\n";
		if(UserHasWritePermission())
		{
			// add button to add new Connection
			echo "<button class='editButtons_hidden' onclick=\"EditConnection(true,-1,$deviceID,-1,-1,-1,'','')\">Add New</button>\n";
		}
		echo "<BR>\n";
		
		if($count>0)
		{
			if($config_subnetsEnabled)
				echo CreateDataTableHeader(array("Loc","Child Device","Port&#x25B2;","Loc","Parent Device","Port","VLAN","Patches"),true,UserHasWritePermission(),UserHasWritePermission());
			else
				echo CreateDataTableHeader(array("Loc","Child Device","Port&#x25B2;","Loc","Parent Device","Port","Patches"),true,UserHasWritePermission(),UserHasWritePermission());
			
			
			//list result data
			$lastDevicePortID = -1;
			$lastSwitchPortID = -1;
			$oddRow = false;
			while ($stmt->fetch()) 
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				echo "<tr class='$rowClass'>";
				
				$portFullName = FormatPort($member, $model, $pic, $port, $type);
				$switchPortFullName = FormatPort($switchMember, $switchModel, $switchPic, $switchPort, $type);
				
				$deviceFullName = GetDeviceFullName($deviceName, $model, $member,$deviceAltName, true);
				$switchFullName = GetDeviceFullName($switchName, $switchModel, $switchMember,$switchAltName, true);

				$devicePortTitle = "";
				$switchPortTitle = "";
				if(CustomFunctions::UserHasDevPermission())
				{
					$devicePortTitle = "switchportid=$devicePortID";
					$switchPortTitle = "switchportid=$switchPortID";
				}

				$childCells = "<td class='data-table-cell'><a href='./?locationid=$dLocID'>".MakeHTMLSafe($dLocName)."</a></td>\n";
				$childCells .= "<td class='data-table-cell'><a href='./?deviceid=$deviceID'>".MakeHTMLSafe($deviceFullName)."</a></td>\n";
				$childCells .=  "<td class='data-table-cell'><span title='$devicePortTitle'>$portFullName</span></td>\n";

				$parentCells = "<td class='data-table-cell'><a href='./?locationid=$sLocID'>".MakeHTMLSafe($sLocName)."</a></td>\n";
				$parentCells .=  "<td class='data-table-cell'><a href='./?deviceid=$switchID'>".MakeHTMLSafe($switchFullName)."</a></td>\n";
				$parentCells .=  "<td class='data-table-cell'><span title='$switchPortTitle'>$switchPortFullName</span></td>\n";
				
				if($relationship!="Child")
				{//swap parent and child
					$t = $childCells;
					$childCells = $parentCells;
					$parentCells = $t;
				}
				echo $childCells;
				echo $parentCells;
				
				if($config_subnetsEnabled)
					echo "<td class='data-table-cell'>$vlan</td>";
				echo "<td class='data-table-cell'>".MakeHTMLSafe($patches)."</td>";
				echo "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate,"", $qaUserID, $qaDate)."</td>";
				
				//edit button cell
				if(UserHasWritePermission())
				{
					//edit button
					echo "<td class='data-table-cell-button editButtons_hidden'>\n";
					
					$jsSafePatchs = MakeJSSafeParam($patches);
					$editDescription = "$deviceName $portFullName <-> $switchName $switchPortFullName";
					$params = "false,$portConnectionID,$deviceID,$devicePortID,$switchID,$switchPortID,'$jsSafePatchs','$editDescription'";
					?><button onclick="EditConnection(<?php echo $params;?>)">Edit</button>
					<?php 
					echo "</td>\n";
					
					echo CreateQACell("dcim_portconnection", $portConnectionID, $formAction, $editUserID, $editDate, $qaUserID, $qaDate);
				}
				echo "</tr>";
				$lastDevicePortID = $devicePortID;
				$lastSwitchPortID = $switchPortID;
			}
			echo "</table>";
		}
		else
			echo "No device connections found<BR>";
	
		if(UserHasWritePermission())
		{	
			EditConnectionForm($formAction, $hNo);
		}
		return $count;
	}
	
	function ShowPowerPanelPage($powerPanelID)
	{
		global $mysqli;
		global $pageSubTitle;
		global $errorMessage;
		
		$query = "SELECT pp.powerpanelid, pp.roomid, pp.name, pp.amps, pp.circuits, pp.orientation, pp.xpos, pp.ypos, pp.width, pp.depth, pp.note, pp.edituser, pp.editdate, pp.qauser, pp.qadate, 
					SUM(IF(pc.volts=208,2,1)) AS circuitslinked, SUM(pc.load) AS `load`, r.name AS room, r.fullname AS roomfullname, s.name AS sitename, s.fullname AS sitefullname, s.siteid, pu.name AS ups, pp.powerupsid
				FROM dcim_powerpanel AS pp
					LEFT JOIN dcim_powercircuit AS pc ON pp.powerpanelid=pc.powerpanelid
					LEFT JOIN dcim_room AS r ON r.roomid=pp.roomid
					LEFT JOIN dcim_site AS s ON s.siteid=r.siteid
					LEFT JOIN dcim_powerups AS pu ON pu.powerupsid=pp.powerupsid
				WHERE pp.powerpanelid=?
				GROUP BY pp.powerpanelid";
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $powerPanelID) || !$stmt->execute())
			$errorMessage[]= "ShowPowerPanelPage Prepare 1 failed: (" . $mysqli->errno . ") " . $mysqli->error;
		else
		{
			$stmt->store_result();
			$stmt->bind_result($powerPanelID, $roomID, $panelName, $amps, $circuits, $orientation, $xPos, $yPos, $width, $depth, $note, $editUserID, $editDate, $qaUserID, $qaDate, $linkedCircuits, $load, $roomName, $roomFullName, $siteName,$siteFullName, $siteID, $upsName, $upsID);
			$panelFound = $stmt->num_rows==1;
			
			if($panelFound)
			{
				$stmt->fetch();
				$fullPanelName = trim("$siteName $panelName");
				$fullPanelName = MakeHTMLSafe($fullPanelName);
				$panelName = MakeHTMLSafe($panelName);
				$roomFullName = MakeHTMLSafe($roomFullName);
				$upsName = MakeHTMLSafe($upsName);
				
				$pageSubTitle = "$fullPanelName";
				
				if(CustomFunctions::UserHasPanelPermission() || CustomFunctions::UserHasPowerCircuitPermission())
				{
					echo "<script src='lib/js/customerEditScripts.js'></script>\n";
				}
				
				$pos = FormatSizeInFeet($xPos,$yPos);
				$size = FormatSizeInFeet($width,$depth);
				
				echo "<div class='panel'>\n";
				echo "<div class='panel-header'>Power Panel: $fullPanelName</div>\n";
				echo "<div class='panel-body'>\n\n";
				
				echo "<table width=100%><tr>\n";
				echo "<td align='left'>\n";
				echo "<span class='customerName'>$panelName</span>\n";
				echo "</td>\n";
				
				echo "<td align='right'>\n";
				//edit panel button - not visible till in edit mode
				echo "<button type='button' class='editButtons_hidden' onClick='parent.location=\"./?powerpanelid=$powerPanelID&page=PowerAudit\"'>Audit Panel</button>\n";
				if(CustomFunctions::UserHasPanelPermission())
				{
					$jsSafeSiteName = MakeJSSafeParam($siteName);
					$jsSafeName = MakeJSSafeParam($panelName);
					$jsSafeNote = MakeJSSafeParam($note);
					//function EditPowerPanel(add, powerPanelID, roomID, upsID, siteName, name, amps, circuis, orientation, x, y, width, depth, note)
					$params = "false, $powerPanelID, $roomID, $upsID, '$jsSafeSiteName', '$jsSafeName', '$amps', '$circuits', '$orientation', $xPos, $yPos, $width, $depth, '$jsSafeNote'";
						
					?><button type='button' class='editButtons_hidden' onclick="EditPowerPanel(<?php echo $params;?>);">Edit Panel</button>
					<?php
				}
				//editMode button
				if(CustomFunctions::UserHasPanelPermission() || CustomFunctions::UserHasPowerCircuitPermission())
				{
					echo "<button type='button' onclick='ToggleEditMode()' style='display:inline;'>Edit Mode</button>\n";
				}
				echo "</td>\n";
				echo "</tr>\n";
				echo "</table>\n";
				
				//details//details
				echo "<table>\n";
				echo "<tr>\n";
				echo "<td align=right class='customerDetails'>\n";
				echo "<b>Site:</b>";
				echo "</td>\n";
				echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
				echo $siteFullName;
				echo "</td>\n";
				
				echo "<td align=right class='customerDetails'>\n";
				echo "<b>UPS:</b>";
				echo "</td>\n";
				echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
				if($upsID!=-1)
					echo "<a href='./?powerupsid=$upsID'>$upsName</a>";
				else
					echo "None";
				echo "</td>\n";
				
				echo "<td align=right class='customerDetails'>\n";
				echo "<b>Circuits:</b>";
				echo "</td>\n";
				echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
				echo "$linkedCircuits / $circuits";
				echo "</td>\n";
				
				echo "</tr>\n";
				echo "<tr>\n";
				
				echo "<td align=right class='customerDetails'>\n";
				echo "<b>Room:</b>";
				echo "</td>\n";
				echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
				echo "<a href='./?roomid=$roomID'>$roomFullName</a>";
				echo "</td>\n";
				
				echo "<td align=right class='customerDetails'>\n";
				echo "<b>Position:</b>";
				echo "</td>\n";
				echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
				echo "$pos";
				echo "</td>\n";
				
				echo "<td align=right class='customerDetails'>\n";
				echo "<b>Load:</b>";
				echo "</td>\n";
				echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
				echo "$load / $amps ".FormatPowerUtilization($load, $amps);
				echo "</td>\n";
				
				echo "</tr>\n";
				echo "<tr>\n";
				
				echo "<td align=right class='customerDetails'>\n";
				echo "<b>Orientation:</b>";
				echo "</td>\n";
				echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
				echo FormatTechDetails($editUserID,$editDate,Orientation($orientation), $qaUserID, $qaDate);
				echo "</td>\n";
				
				echo "<td align=right class='customerDetails'>\n";
				echo "<b>Size:</b>";
				echo "</td>\n";
				echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
				echo "$size";
				echo "</td>\n";
				echo "</tr>\n";
				
				echo "<tr>\n";
				echo "<td align=right class='customerDetails' valign='top'>\n";
				echo "<b>Notes:</b>";
				echo "</td>\n";
				echo "<td valign=top align=left colspan='5'>\n";
				echo "<textarea rows=3 cols=95 readonly placeholder=''>".MakeHTMLSafe($note)."</textarea>";
				echo "</td>\n";
				
				echo "</tr></table>\n";
				
				if(CustomFunctions::UserHasPanelPermission())
				{
					EditPowerPanelForm($roomID);
				}
			}
			else
			{
				echo "<div class='panel'>\n";
				echo "<div class='panel-header'>Panel</div>\n";
				echo "<div class='panel-body'>\n\n";
				echo "Power Panel ID#$powerPanelID not found.<BR>\n";
			}
			echo "</div>\n";
			echo "</div>\n\n";
		}
		
		if($panelFound)
		{
			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>Power Panel Details: $fullPanelName</div>\n";
			echo "<div class='panel-body'>\n\n";
			
			ListPowerCircuits("P",$powerPanelID, $circuits);//temp test code
				
			echo "</div>\n";
			echo "</div>\n";
			
			if(CustomFunctions::UserHasPanelPermission() || CustomFunctions::UserHasPowerCircuitPermission())
			{
				//initialize page JS
				echo "<script type='text/javascript'>InitializeEditButton();</script>\n";
			}
		}//panel found*/
		//return $count;
	}
	
	function EditPowerPanelForm($roomID)
	{
		global $errorMessage;
		global $mysqli;
		
		//-default values - never seen
		$action = "";
		$actionText = "Addy";
		$roomIDInput = 2;
		$upsIDInput = 2;
		$nameInput = "panel";
		$ampsInput = 6;
		$circuitsInput = 43;
		$xPosInput = 211.11;
		$yPosInput = 311.11;
		$orientationInput = "E";
		$widthInput = 411.11;
		$depthInput = 511.11;
		$noteInput = "notes input";

		//build room combo options
		$roomOptions = "";
		$query = "SELECT s.siteid, s.name, r2.roomid, r2.name, r2.fullname
			FROM dcim_room AS r1
				LEFT JOIN dcim_site AS s ON s.siteid=r1.siteid
				LEFT JOIN dcim_room AS r2 ON r2.siteid=s.siteid
			WHERE r1.roomid = $roomID
			ORDER BY s.name, r2.name";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "EditPowerPanelForm() Prepare 1 failed: (" . $mysqli->errno . ") " . $mysqli->error;
		}
		else
		{
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($siteID, $site, $roomID, $room, $roomFullName);
			$roomOptions = "";
			while ($stmt->fetch())
			{
				$fullRoomName = "$site $roomFullName";
				$selected = ($roomID==$roomIDInput ? "Selected" : "");
				$roomOptions .= "<option value='$roomID' $selected>$fullRoomName</option>\n";
			}
		}
		
		//build ups combo options
		$upsOptions = "";
		$query = "SELECT pu.siteid, s.name AS site, pu.powerupsid, pu.name
		FROM dcim_room AS r
			LEFT JOIN dcim_site AS s ON s.siteid=r.siteid
			LEFT JOIN dcim_powerups AS pu ON pu.siteid=s.siteid
		WHERE r.roomid=$roomID
		ORDER BY pu.name";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "EditPowerPanelForm() Prepare 1 failed: (" . $mysqli->errno . ") " . $mysqli->error;
		}
		else
		{
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($siteID, $siteName, $upsID, $upsName);
			$upsOptions = "";
			//None Option
			$selected = (-1==$upsIDInput ? "Selected" : "");
			$upsOptions .= "<option value='-1' $selected>None</option>\n";
			while ($stmt->fetch())
			{
				$selected = ($upsID==$upsIDInput ? "Selected" : "");
				$upsOptions .= "<option value='$upsID' $selected>$siteName $upsName</option>\n";
			}
		}
		
		?>
			<div id='EditPowerPanelMsg' class='hidden'></div>
			<div id='EditPowerPanelEntry' class='hidden'>
			<BR>
			<table><tr><td>
			<form action="<?php echo $action;?>" method='post' id='EditPowerPanelForm' onsubmit='return SavePowerPanel()' class=''>
				<fieldset>
					<legend id=EditPowerPanelEntryLegend><b><?php echo $actionText;?> Power Panel</b></legend>
					<table>
						<tr>
							<td colspan=1 align=right>Room:</td>
							<td align='left'>
								<select id=EditPowerPanel_roomid name="roomid" tabindex=1>
									<?php echo $roomOptions; ?>
								</select>
							</td>
						</tr>
						<tr>
							<td align='right' width=1>Name:</td>
							<td align='left'>
								<input id=EditPowerPanel_name type='text' tabindex=2 size=18 name='name' value='<?php echo $nameInput;?>' placeholder="UPS-12" class='' >
							</td>
						</tr>
						<tr>
							<td align='right' width=1>Amps:</td>
							<td align='left'>
								<input id=EditPowerPanel_amps type='number' tabindex=3 size=6 name='amps' min='0' max='500' step='1' value='<?php echo $ampsInput;?>' placeholder='225' class=''>
								Circuits:
								<input id=EditPowerPanel_circuits type='number' tabindex=4 size=6 name='circuits' min='1' max='150' step='1' value='<?php echo $circuitsInput;?>' placeholder='42' class=''>
							</td>
						</tr>
						<tr>
							<td align='right' width=1>Orientation:</td>
							<td align='left'>
								<div class='inputToolTipContainer'>
									<select id=EditPowerPanel_orientation onchange='' name="orientation" tabindex=5>
										<option value="N" <?php if($orientationInput==="N") echo "Selected"; ?>>Normal</option>
										<option value="E" <?php if($orientationInput==="E") echo "Selected"; ?>>Right</option>
										<option value="S" <?php if($orientationInput==="S") echo "Selected"; ?>>Backwards</option>
										<option value="W" <?php if($orientationInput==="W") echo "Selected"; ?>>Left</option>
									</select>
								<span class=inputTooltip>When looking at panel in room, relative orientation to room.</span></div>
							</td>
						</tr>
						<tr>
							<td colspan=1 align=right>UPS:</td>
							<td align='left'>
								<select id=EditPowerPanel_upsid name="upsid" tabindex=6>
									<?php echo $upsOptions; ?>
								</select>
							</td>
						</tr>
						<tr>
							<td align='right' width=1>Left:</td>
							<td align='left'>
								<div class='inputToolTipContainer'>
									<input id=EditPowerPanel_xpos type='number' tabindex=7 size=3 min='-9999.99' max='9999.99' step='0.01' name='xpos' value='<?php echo $xPosInput;?>' placeholder="12.34" class='' >
								<span class=inputTooltip>Distance from left room edge to back left corner of panel in feet (negative for distance from right wall)</span></div>
								Foreward:
								<div class='inputToolTipContainer'>
									<input id=EditPowerPanel_ypos type='number' tabindex=8 size=3 min='-9999.99' max='9999.99' step='0.01' name='ypos' value='<?php echo $yPosInput;?>' placeholder="12.34" class='' >
								<span class=inputTooltip>Distance from far room edge to back left corner of panel in feet (negative for distance from close wall)</span></div>
							</td>
						</tr>
						<tr>
							<td align='right' width=1>Width:</td>
							<td align='left'>
								<div class='inputToolTipContainer'>
									<input id=EditPowerPanel_width type='number' tabindex=9 size=3 min='0' max='9999.99' step='0.01' name='width' value='<?php echo $widthInput;?>' placeholder="12.34" class='' >
								<span class=inputTooltip>In feet</span></div>
								Depth:
								<div class='inputToolTipContainer'>
									<input id=EditPowerPanel_depth type='number' tabindex=10 size=3 min='0' max='9999.99' step='0.01' name='depth' value='<?php echo $depthInput;?>' placeholder="12.34" class='' >
								<span class=inputTooltip>In feet</span></div>
							</td>
						</tr>
						<tr>
							<td align='right' width=1>Notes:</td>
							<td align='left'>
								<input id=EditPowerPanel_note type='text' tabindex=11 size=50 name='notes' value='<?php echo $noteInput;?>' placeholder='Notes' class=''>
							</td>
						</tr>
						<tr>
							<td colspan='2'>
								<table style='width:100%;'>
									<tr>
										<td align=left>
											<?php 
											if(CustomFunctions::UserHasPanelPermission())
													echo "<button id='EditPowerPanel_deletebtn' type='button' onclick='DeletePowerPanel()' tabindex=12>Delete</button>";
											?>
										</td>
										<td align='right'>
											<button type='button' onclick='HideAllEditForms()' tabindex=14>Cancel</button>
											<input type='submit' value='Save' tabindex=13>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					<input id=EditPowerPanel_powerpanelid type='hidden' name='powerpanelid' value=-1>
					<input id=EditPowerPanel_action type='hidden' name='action' value='null'>
					<input type="hidden" name="page_instance_id" value="<?php echo end($_SESSION['page_instance_ids']); ?>"/>
				</fieldset>
			</form>
		</td></tr></table></div>
		<?php
	}
	
	function ShowPowerPanelAuditPage($powerPanelID)
	{
		global $mysqli;
		global $pageSubTitle;
		
		//lookup site room and circuit info for headers
		$query = "SELECT s.siteid, s.name, r.roomid, r.name, r.fullname, pp.name, pp.circuits
			FROM dcim_powercircuit AS pc
				LEFT JOIN dcim_powercircuitloc AS pcl ON pc.powercircuitid=pcl.powercircuitid
				LEFT JOIN dcim_powerpanel AS pp ON pp.powerpanelid=pc.powerpanelid
				LEFT JOIN dcim_location AS l ON pcl.locationid=l.locationid
				LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
			WHERE pp.powerpanelid=?
			GROUP BY r.roomid, pp.powerpanelid, pc.circuit
			ORDER BY pc.circuit
			LIMIT 1";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			//TODO handle errors better
			echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
		}
		
		$stmt->bind_Param('s' ,$powerPanelID);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($siteID, $site, $roomID, $room, $roomFullName, $panel, $numberOfCircuitsPerPanel);
		$count = $stmt->num_rows;
		
		if($count==1 && $stmt->fetch())
		{//sucsessfull lookup
			//TODO: This room name is not guranteed to be the room the panel is in so it could be a little confusing. it is just a room linked to at least one of the circuits.
			$fullPanelDescription = MakeHTMLSafe("$site $roomFullName Panel:".$panel);
			$pageSubTitle = "Power Audit - ".MakeHTMLSafe("$site $room Panel:".$panel);//short room name 
			echo "<script src='lib/js/customerEditScripts.js'></script>\n";
			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>\n";
			echo "Circuits for $fullPanelDescription\n";
			echo "</div>\n";
			
			echo "<div class='panel-body'>\n\n";
			
			//select power data
			$query = "SELECT s.siteid, s.name, r.roomid, r.name, l.locationid, l.name AS loc, LEFT(c.name,25) AS cust, pc.powercircuitid, pp.name, pc.circuit, pc.volts, pc.amps, pc.status, pc.load, pc.edituser, pc.editdate
				FROM dcim_powercircuit AS pc
					LEFT JOIN dcim_powercircuitloc AS pcl ON pc.powercircuitid=pcl.powercircuitid
					LEFT JOIN dcim_powerpanel AS pp ON pp.powerpanelid=pc.powerpanelid
					LEFT JOIN dcim_location AS l ON pcl.locationid=l.locationid
					LEFT JOIN dcim_room AS r ON l.roomid=r.roomid
					LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
					LEFT JOIN dcim_device AS d ON l.locationid=d.locationid AND d.status='A'
					LEFT JOIN dcim_customer AS c ON d.hno=c.hno
				WHERE pp.powerpanelid=?
				GROUP BY r.roomid, pp.name, pc.circuit
				ORDER BY pc.circuit";
			
			if (!($stmt = $mysqli->prepare($query)))
			{
				//TODO handle errors better
				echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			}
			
			$stmt->bind_Param('s', $powerPanelID);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($siteID, $site, $roomID, $room, $locationID, $location, $cust, $powerCircuitID, $panel, $circuit, $volts, $amps, $status, $load, $editUserID, $editDate);
			$count = $stmt->num_rows;
				
			if($count>0)
			{
				//show results
				echo "<a href='javascript:;' onclick='PowerAuditPanel_ConfirmPageChange(\"./?siteid=$siteID\");'>Back to $site site page</a><BR><BR>\n";
				echo "<span class='tableTitle'>Circuits for $fullPanelDescription</span><BR>\n";
				echo "<form action='./?page=PowerAudit' method='post' id='PowerAuditPanelForm' onsubmit='return SavePowerAuditPanel()' class=''>\n";
				echo "<table style='border-collapse: collapse'>\n";
				
				$stmt->fetch();
				
				//count from 1 to $numberOfCircuitsPerPanel pulling records out of cursor as necisary
				$tableCircuitNo = 0;
				$leftSpan = 1;
				$rightSpan = 1;
				$numberIn3PhaseLeft = 0;
				$numberIn3PhaseRight = 0;
				while($tableCircuitNo<$numberOfCircuitsPerPanel)//42 circuits per panel
				{
					//odd circutis are on the left 
					$tableCircuitNo++;
					$left = ($tableCircuitNo%2)!=0;
					
					//this will make 0&1 odd color and 2&3 not for every set of 4
					if($tableCircuitNo%4<=1) $cellClass = "powerAuditCellOne";
					else $cellClass = "powerAuditCellTwo";
					
					if($circuit<$tableCircuitNo)
						$stmt->fetch();
					
					$tabIndex = $left ? $circuit : $circuit+$numberOfCircuitsPerPanel;
					$fullLocationName = FormatLocation($site, $room, $location, false);
					
					if($left)
					{//start a new row
						echo "<tr>\n";
					}
					
					$hasData = ($circuit==$tableCircuitNo);
					
					if($hasData)
					{
						$rowSpan="";
						$displayVolts = FormatVolts($volts);
						$displayCircuit = $circuit;
						$trippleBreaker = false;
						
						if($left)
						{
							if($volts==308)$numberIn3PhaseLeft++;
							else $numberIn3PhaseLeft=0;
						}
						else
						{
							if($volts==308)$numberIn3PhaseRight++;
							else $numberIn3PhaseRight=0;
						}
						
						if($volts==208)//208 volt circuits take up double
						{
							if($left)
								$leftSpan = 2;
							else
								$rightSpan = 2;
							$cellClass .= " powerAuditCellDouble";
							$rowSpan = " rowspan=2";
							$displayCircuit = Format208CircuitNumber($circuit);
						}
						else if($left && $numberIn3PhaseLeft==1 || !$left && $numberIn3PhaseRight==1)
						{
							$trippleBreaker = true;
							$trippleBreakerMarker = "<div style='position: relative;'><div class='powerAuditMarker'></div></div>\n";
						}
						
						echo "<td $rowSpan class='$cellClass'>\n";
						if($trippleBreaker)echo $trippleBreakerMarker;
						echo "	<table width=100%><tr>\n";
						echo "	<td><b>".MakeHTMLSafe($panel)." CKT ".MakeHTMLSafe($displayCircuit)."</b></td>\n";
						echo "	<td align=right>".MakeHTMLSafe($cust)."</td>\n";
						echo "	</tr></table><table width=100%><tr>\n";
						//echo "	$fullLocationName ($percentLoad%) ";
						if($locationID==null)
							echo "	<td>No Location</td>\n";
						else
							echo "	<td><a href='javascript:;' onclick='PowerAuditPanel_ConfirmPageChange(\"./?locationid=$locationID\");'>".MakeHTMLSafe($fullLocationName)."</a>&nbsp;&nbsp;</td>\n";
						echo "	<td align=right>".$displayVolts."-".$amps."A-<b>".PowerOnOff($status)."</b>\n";
						$statusFieldID = "PowerAuditPanel_Circuit".$circuit."_status";
						$loadFieldID = "PowerAuditPanel_Circuit".$circuit."_load";
						$checked = ($status==="A") ? " checked" : "";
						echo "	<input id='$statusFieldID' type='checkbox' name='c".$circuit."status' value='A' onclick='PowerAuditCircuit_StatusClicked(\"$statusFieldID\",\"$loadFieldID\");' $checked>\n";
						echo "	<input id='$loadFieldID' type='number' name='c".$circuit."load' tabindex=$tabIndex size=5 placeholder='$load' min=0 max=$amps step=0.01 onchange='PowerAuditCircuit_LoadChanged(\"$loadFieldID\",\"$statusFieldID\");' style='position:relative; z-index:2;'>\n";
						echo "	<input id=PowerAuditPanel_Circuit".$circuit."_powercircuitid type='hidden' name='c".$circuit."powercircuitid' value='$powerCircuitID'>\n";
						echo "	</td></tr>\n";
						echo "	</table>\n";
						
						echo "</td>\n";
					}
					else 
					{
						if($left && $leftSpan>1)
							$leftSpan--;
						else if(!$left && $rightSpan>1)
							$rightSpan--;
						else
							echo "<td class='$cellClass powerAuditCellEmpty'><b>".MakeHTMLSafe($panel)." CKT ".MakeHTMLSafe($tableCircuitNo)."</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;EMPTY</td>\n";
					}
					
					if(!$left)
					{//end row
						echo "</tr>\n";
					}
					if($numberIn3PhaseLeft==3)$numberIn3PhaseLeft = 0;
					if($numberIn3PhaseRight==3)$numberIn3PhaseRight= 0;
				}
				echo "<tr><td colspan='2' align='center' style='padding-top: 8px;'><input type='submit' value='Save' tabindex='".($numberOfCircuitsPerPanel*2+1)."'></td></tr>\n";
				echo "<input id=PowerAuditPanel_powerpanelid type='hidden' name='powerpanelid' value='$powerPanelID'>\n";
				echo "<input id=PowerAuditPanel_action type='hidden' name='action' value='PowerAudit_PanelUpdate'>\n";
				echo "<input type='hidden' name='page_instance_id' value='".end($_SESSION['page_instance_ids'])."'>\n";
				echo "</table></form>\n";
			}
			else
				echo "No power circuits found at $fullPanelDescription<BR>\n";
		}//sucsessfull lookup
		else//panel/room combo not found
		{
			$pageSubTitle = "Power Audit - Panel ($powerPanelID) not found";
			echo "<script src='lib/js/customerEditScripts.js'></script>\n";
			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>\n";
			echo "Circuits for Panel: ($powerPanelID)\n";
			echo "</div>\n";
		
			echo "<div class='panel-body'>\n\n";
			echo "Panel ($powerPanelID) not found<BR>\n";
		}	
		echo "</div>\n";
		echo "</div>\n";
		return $count;
	}
	
	function ListUPSs($page, $input, $siteFullName="")
	{
		global $mysqli;
		global $errorMessage;
		//$formAction = "./?host=$hNo";
		
		if($page=="S")
		{
			$sitePage = true;
			$filter = "s.siteid=?";
		}
		else if($page=="?")
		{
			$searchPage = true;
			$input = "%".trim($input)."%";
			$filter = "CONCAT(pu.name,'~',pu.note) LIKE ?";
		}
		
		$query = "SELECT s.name, pu.powerupsid, pu.name, pu.note
			FROM dcim_powerups AS pu
				LEFT JOIN dcim_site AS s ON s.siteid=pu.siteid
			WHERE $filter
			ORDER BY s.name, pu.name";
		
		if (!($stmt = $mysqli->prepare($query)))
		{
			$errorMessage[] = "ListUPSs() Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
		}
		
		$stmt->bind_Param('s', $input);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($siteName, $powerUpsID, $upsName, $upsNote);
		$count = $stmt->num_rows;
		
		$result = "<span class='tableTitle'>$siteFullName UPSs</span>\n";
		//Add button
		/*if($sitePage && UserHasWritePermission())
		 {
		 $result .=
		 //function EditSubnet(add, portID,vlan,subnet,mask,gateway,first,last,note)
		 ?><button class='editButtons_hidden' onclick="EditSubnet(true,-1,-1,'','','','','','','')">Add New</button>
		 <?php
		 }*/
		$result .= "<BR>";
		if($count>0)
		{
			$result .= CreateDataTableHeader(array("Site","UPS","Note"));
			
			//list result data
			$oddRow = false;
			while ($stmt->fetch())
			{
				$oddRow = !$oddRow;
				if($oddRow) $rowClass = "dataRowOne";
				else $rowClass = "dataRowTwo";
				
				if($custAccess=="T")$custAccess = "Yes";
				else $custAccess = "No";
				
				$result .= "<tr class='$rowClass'>";
				$result .= "<td class='data-table-cell'>".MakeHTMLSafe($siteName)."</a></td>";
				$result .= "<td class='data-table-cell'><a href='?powerupsid=$powerUpsID'>".MakeHTMLSafe($upsName)."</a></td>";
				$result .= "<td class='data-table-cell'>".TruncateWithSpanTitle(MakeHTMLSafe($upsNote))."</td>";
				$result .= "</tr>";
			}
			$result .= "</table>";
		}
		else
		{
			$result .= "No ups records found.<BR>\n";
		}
		
		/*if($sitePage && UserHasWritePermission())
		 {
		 EditSubnetForm($formAction,$hNo);
		 }*/
		
		return $result;
	}
	
	function ShowPowerUPSPage($powerUpsID)
	{
		global $mysqli;
		global $pageSubTitle;
		global $errorMessage;
		
		$query = "SELECT s.siteid, s.name, s.fullname, pu.powerupsid, pu.name, pu.volts, pu.amps, SUM(pc.load) AS `load`, pu.note, pu.edituser, pu.editdate, pu.qauser, pu.qadate
			FROM dcim_powerups pu
				LEFT JOIN dcim_powerpanel AS pp ON pp.powerupsid=pu.powerupsid
				LEFT JOIN dcim_powercircuit AS pc ON pc.powerpanelid=pp.powerpanelid
				LEFT JOIN dcim_site AS s on s.siteid=pu.siteid
			WHERE pu.powerupsid=?
			GROUP BY pu.powerupsid";
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $powerUpsID) || !$stmt->execute())
			$errorMessage[]= "ShowPowerUPSPage Prepare 1 failed: (" . $mysqli->errno . ") " . $mysqli->error;
		else
		{
			$stmt->store_result();
			$stmt->bind_result($siteID, $siteName, $siteFullName, $powerUpsID, $upsName, $volts, $amps, $load, $note, $editUserID, $editDate, $qaUserID, $qaDate);
			$upsFound = $stmt->num_rows==1;
			
			if($upsFound)
			{
				$stmt->fetch();
				$fullUpsName = trim("$siteName $upsName");
				$fullUpsName = MakeHTMLSafe($fullUpsName);
				$displayUpsName = MakeHTMLSafe($upsName);
				
				$pageSubTitle = "$fullUpsName";
				
				if(CustomFunctions::UserHasUPSPermission() || CustomFunctions::UserHasPanelPermission())
				{
					echo "<script src='lib/js/customerEditScripts.js'></script>\n";
				}
				
				echo "<div class='panel'>\n";
				echo "<div class='panel-header'>UPS: $fullUpsName</div>\n";
				echo "<div class='panel-body'>\n\n";
				
				echo "<table width=100%><tr>\n";
				echo "<td align='left'>\n";
				echo "<span class='customerName'>$displayUpsName</span>\n";
				echo "</td>\n";
				
				echo "<td align='right'>\n";
				//edit UPS button - not visible till in edit mode
				/*
				echo "<button type='button' class='editButtons_hidden' onClick='parent.location=\"./?powerpanelid=$powerPanelID&page=PowerAudit\"'>Audit Panel</button>\n";
				if(CustomFunctions::UserHasPanelPermission())
				{
					$jsSafeSiteName = MakeJSSafeParam($siteName);
					$jsSafeName = MakeJSSafeParam($panelName);
					$jsSafeNote = MakeJSSafeParam($note);
					//function EditPowerUPS(add, powerPanelID, roomID, upsID, siteName, name, amps, circuis, orientation, x, y, width, depth, note)
					$params = "false, $powerPanelID, $roomID, $upsID, '$jsSafeSiteName', '$jsSafeName', '$amps', '$circuits', '$orientation', $xPos, $yPos, $width, $depth, '$jsSafeNote'";
						
					?><button type='button' class='editButtons_hidden' onclick="EditPowerUPS(<?php echo $params;?>);">Edit Panel</button>
					<?php
				}*/
				//editMode button
				if(CustomFunctions::UserHasUPSPermission() || CustomFunctions::UserHasPanelPermission())
				{
					echo "<button type='button' onclick='ToggleEditMode()' style='display:inline;'>Edit Mode</button>\n";
				}
				echo "</td>\n";
				echo "</tr>\n";
				echo "</table>\n";
				
				//details//details
				echo "<table>\n";
				echo "<tr>\n";
				echo "<td align=right class='customerDetails'>\n";
				echo "<b>Site:</b>";
				echo "</td>\n";
				echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
				echo "<a href='./?siteid=$siteID'>$siteFullName</a>";
				echo "</td>\n";
				
				echo "</tr>\n";
				echo "<tr>\n";
				
				echo "<td align=right class='customerDetails'>\n";
				echo "<b>Volts:</b>";
				echo "</td>\n";
				echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
				echo $volts;
				echo "</td>\n";
				
				echo "<td align=right class='customerDetails'>\n";
				echo "<b>Load:</b>";
				echo "</td>\n";
				echo "<td align=left class='customerDetails' style='padding-right: 25;'>\n";
				echo "$load / $amps ".FormatPowerUtilization($load, $amps);
				echo "</td>\n";
				
				echo "</tr>\n";
				echo "<tr>\n";
				
				echo "<td align=right class='customerDetails' valign='top'>\n";
				echo "<b>Notes:</b>";
				echo "</td>\n";
				echo "<td valign=top align=left colspan='5'>\n";
				echo "<textarea rows=3 cols=95 readonly placeholder=''>".MakeHTMLSafe($note)."</textarea>";
				echo "</td>\n";
				
				echo "</tr></table>\n";
				
				if(CustomFunctions::UserHasUPSPermission())
				{
					//EditPowerUPSForm($roomID);//doesnt exist
				}
			}
			else
			{
				echo "<div class='panel'>\n";
				echo "<div class='panel-header'>UPS</div>\n";
				echo "<div class='panel-body'>\n\n";
				echo "Power UPS ID#$powerUpsID not found.<BR>\n";
			}
			echo "</div>\n";
			echo "</div>\n\n";
		}
		
		if($upsFound)
		{
			echo "<div class='panel'>\n";
			echo "<div class='panel-header'>UPS Details: $fullUpsName</div>\n";
			echo "<div class='panel-body'>\n\n";
			
			ListPowerPanels("U",$powerUpsID);
				
			echo "</div>\n";
			echo "</div>\n";
			
			if(CustomFunctions::UserHasUPSPermission() || CustomFunctions::UserHasPanelPermission())
			{
				//initialize page JS
				echo "<script type='text/javascript'>InitializeEditButton();</script>\n";
			}
		}//ups found*/
		//return $count;
	}
	
	function PowerAuditPanelList()
	{
		global $pageSubTitle;
		
		$pageSubTitle = "Power Audit - Panel List";
		
		$result = "<div class='panel'>\n";
		$result.= "<div class='panel-header'>\n";
		$result.= "Power Panel list\n";
		$result.= "</div>\n";
		
		$result.= "<div class='panel-body'>\n\n";
		
		echo $result;
		
		$siteID = 0;//broken maybe with panel edit form
		ListPowerPanels("S",$siteID); 
		
		echo "</div>\n</div>\n";
	}
	
	function ListPowerPanels($page, $input=-1)
	{
		global $mysqli;
		global $errorMessage;
		
		$filter = "";
		
		$addEnabled = false;
		$editEnabled = false;
		
		if($page=="?")
		{//search
			$input = "%".trim($input)."%";
			$filter = "CONCAT(pp.name,'~',pp.note) LIKE ?";
		}
		else if($page=="R")
		{//room page
			$filter = "pp.roomid=?";
			$addEnabled = true;
			$editEnabled = true;
			$formAction = "./?roomid=$input";
		}
		else if($page=="U")
		{//UPS page
			$filter = "pu.powerupsid=?";
			$editEnabled = true;
			$formAction = "./?powerupsid=$input";
		}
		else if($page=="S")
		{//Site page - actualy power audit panel list
			$filter = "s.siteid=?";
			$editEnabled = true;
			$formAction = "./?siteid=$input";
		}
		
		$query = "SELECT s.siteid, s.name, r.roomid, r.name, pp.powerupsid, pu.name, pp.powerpanelid, pp.name, pp.amps, SUM(pc.load), pp.circuits, pp.orientation, pp.xpos, pp.ypos, pp.width, pp.depth, pp.note, pp.edituser, pp.editdate, pp.qauser, pp.qadate
			FROM  dcim_room AS r
				LEFT JOIN dcim_site AS s ON r.siteid=s.siteid
				LEFT JOIN dcim_powerpanel AS pp ON pp.roomid=r.roomid
				LEFT JOIN dcim_powerups AS pu ON pu.powerupsid=pp.powerupsid
				LEFT JOIN dcim_powercircuit AS pc ON pp.powerpanelid=pc.powerpanelid
			WHERE $filter AND pp.powerpanelid IS NOT NULL
			GROUP BY pp.powerpanelid
			ORDER BY s.name, pp.name";
		
		$result = "<span class='tableTitle'>Power Panels</span>\n";
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('s', $input) || !$stmt->execute())
		{
			$errorMessage[]= "Prepare failed: ListPowerPanels() (" . $mysqli->errno . ") " . $mysqli->error . "<BR>";
			$result .= "<BR>SQL error locating power panels";
		}
		else
		{
			$stmt->store_result();
			$stmt->bind_result($siteID,$siteName,$roomID,$room, $upsID, $upsName, $powerPanelID, $panelName, $amps, $load, $circuits, $orientation, $xPos, $yPos, $width, $depth, $note, $editUserID, $editDate, $qaUserID, $qaDate);
			$count = $stmt->num_rows;
			
			//on room page - add location button
			if($addEnabled && CustomFunctions::UserHasPanelPermission())
			{
				//function EditPowerPanel(add, powerPanelID, roomID, upsID, siteName, name, amps, circuis, orientation, x, y, width, depth, note)
				$params = "true, -1, '".MakeJSSafeParam($input)."', -1, '', '', 225, 42, 'N', 0, 0, 1.21, 0.35, ''";
				$result .= "<button type='button' class='editButtons_hidden' onclick=\"EditPowerPanel($params);\">Add New</button>";
			}
			$result .= "<BR>";
			
			if($count>0)
			{
				//show results
				$result .= CreateDataTableHeader(array("Site","Panel","Room","UPS","Amps","Utilization","Note"),true,true,true);
				
				//list result data
				$oddRow = false;
				while ($stmt->fetch())
				{
					$oddRow = !$oddRow;
					if($oddRow) $rowClass = "dataRowOne";
					else $rowClass = "dataRowTwo";
					
					$result .= "<tr class='$rowClass'>";
					$result .= "<td class='data-table-cell'>".MakeHTMLSafe($siteName)."</td>";
					$result .= "<td class='data-table-cell'><a href='./?powerpanelid=$powerPanelID'>".MakeHTMLSafe($panelName)."</a></td>";
					$result .= "<td class='data-table-cell'><a href='./?roomid=$roomID'>".MakeHTMLSafe($room)."</a></td>";
					if($upsID!=-1)
						$result .= "<td class='data-table-cell'><a href='./?powerupsid=$upsID'>".MakeHTMLSafe($upsName)."</a></td>";
					else
						$result .= "<td class='data-table-cell'></td>";
					$result .= "<td class='data-table-cell'>".$amps."A</td>";
					$result .= "<td class='data-table-cell'>".FormatPowerUtilization($load, $amps)."</td>";
					$result .= "<td class='data-table-cell'>".MakeHTMLSafe(Truncate($note))."</td>";

					$result .= "<td class='data-table-cell'>".FormatTechDetails($editUserID, $editDate, "", $qaUserID, $qaDate)."</td>";
					if(CustomFunctions::UserHasPanelPermission())
					{
						//edit button
						$result .= "<td class='data-table-cell-button editButtons_hidden'>\n";
						
						$jsSafeSiteName = MakeJSSafeParam($siteName);
						$jsSafeName = MakeJSSafeParam($panelName);
						$jsSafeNote = MakeJSSafeParam($note);
						//function EditPowerPanel(add, powerPanelID, roomID, upsID, siteName, name, amps, circuis, orientation, x, y, width, depth, note)
						$params = "false, $powerPanelID, $roomID, $upsID, '$jsSafeSiteName', '$jsSafeName', '$amps', '$circuits', '$orientation', $xPos, $yPos, $width, $depth, '$jsSafeNote'";
						$result .= "<button onclick=\"EditPowerPanel($params)\">Edit</button>";
						$result .= "</td>\n";
						$result .= CreateQACell("dcim_powerpanel", $powerPanelID, $formAction, $editUserID, $editDate, $qaUserID, $qaDate);
					}
					$result .= "</tr>";
				}
				$result .= "</table>";
			}
			else
				$result .= "No Power panel data found<BR>";//TODO should say where they aren't found 
		}
		
		echo $result;
		
		if($editEnabled && CustomFunctions::UserHasPanelPermission())
			EditPowerPanelForm($input);
		
		return $count;
	}
	
	function CreateSiteLayout($siteID, $name, $fullName, $siteWidth, $siteDepth)
	{
		global $mysqli;
		global $errorMessage;
		
		$result = CustomFunctions::CreateSiteCustomLayout($siteID, $name, $fullName, $siteWidth, $siteDepth);
		
		//select rooms from table for rendering each one - NOTE these are sorted by layer so rooms that may over lap others can have a proper layer
		$query = "SELECT roomid, name, fullname, custaccess, xpos, ypos, width, depth, orientation
				FROM dcim_room
				WHERE siteid=? AND width > 0 AND depth > 0
				ORDER BY layer";
		
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
				$result .= CreateRoomLayout($roomID, $name, $fullName, $xPos, $yPos, $width, $depth, $orientation, $siteWidth, $siteDepth, $custAccess);
			}
		}
		return $result;
	}
	
	function CreateRoomLayout($roomID, $name, $fullName, $xPos, $yPos, $width, $depth, $orientation, $parentWidth, $parentDepth, $custAccess)
	{
		global $mysqli;
		global $errorMessage;
		
		//calculated
		$relativeX = 0;
		$relativeY = 0;
		$relativeWidth = 0;
		$relativeDepth = 0;
		
		$renderingWithinParent = ($parentWidth > 0 && $parentDepth>0);
		if($renderingWithinParent)
		{
			$relativeX = 100*$xPos/$parentWidth;
			$relativeY= 100*$yPos/$parentDepth;
		
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
		}
		else
			$orientation = "N";
		
		$rotation = OritentationToDegrees($orientation);
		$rotationTransform = "";
		if($rotation!=0)
			$rotationTransform = "	transform: rotate(".$rotation."deg); -ms-transform: rotate(".$rotation."deg); -webkit-transform: rotate(".$rotation."deg);\n";
		
		//create custom style and html
		$roomCustomHTML = "";
		$roomCustomStyle = "";
		CustomFunctions::CreateRoomCustomLayout($roomID, $name, $custAccess, $roomCustomHTML, $roomCustomStyle);
		
		//begin creating style and html for this room
		$result = "<style>\n";
		if($renderingWithinParent)
		{
			$result .= "#room$roomID {\n";
			$result .= "	left: $relativeX%;\n";
			$result .= "	top: $relativeY%;\n";
			$result .= "	width: $relativeWidth%;\n";
			$result .= "	height: $relativeDepth%;\n";
			$result .= $rotationTransform;
			$result .= "}\n";
		}
		else
		{
			$heightMax = 650;
			$widthMax = 948;
			
			$renderHeight = $heightMax;
			$renderWidth = $renderHeight*($width/$depth);
			if($renderWidth>$widthMax)
			{
				$renderWidth = $widthMax;
				$renderHeight = $renderWidth*($depth/$width);
			}
			
			//rendering this room standalone
			$result .= "#room$roomID {\n";
			$result .= "	position: relative;\n";
			$result .= "	width: ".$renderWidth."px;\n";
			$result .= "	height: ".$renderHeight."px;\n";
			$result .= $rotationTransform;
			$result .= "}\n";
		}
		$result .= $roomCustomStyle;
		$result .= "</style>\n";
		
		$result .= "<div id='room$roomID' class='roomContainer'>\n";
		if($renderingWithinParent)$result .= "<a href='./?roomid=$roomID' title='$fullName'>\n";
		if($roomCustomHTML)
			$result .= $roomCustomHTML;
		else
		{
			$roomTypeClass = RoomAccesClass($custAccess);
			$result .= "<div id='' class='roomBorders $roomTypeClass'></div>\n";
			$result .= "<span class='roomLayoutTitle'>$name</span>\n";
		}
		
		//render locations
		$parentWidth = $width;
		$parentDepth = $depth;
		
		//select locations from table for rendering each one
		$query = "SELECT l.locationid, l.name, l.xpos, l.ypos, l.width, l.depth, l.orientation, s.name, r.fullname, d.hno, c.name AS cust, d.deviceid, d.name as device, COUNT(Distinct l.locationid) AS countl, COUNT(Distinct d.deviceid) AS countd
				FROM dcim_location AS l
					LEFT JOIN dcim_device AS d ON d.locationid=l.locationid AND d.status = 'A'
					LEFT JOIN dcim_customer AS c ON d.hno = c.hno
					LEFT JOIN dcim_room AS r ON r.roomid = l.roomid
					LEFT JOIN dcim_site AS s ON s.siteid = r.siteid
				WHERE l.roomid=? AND l.width > 0 AND l.depth > 0
				GROUP BY l.xpos, l.ypos, l.width, l.depth, l.orientation
				ORDER BY l.name";
		
		if (!($stmt = $mysqli->prepare($query)) || !$stmt->bind_Param('i', $roomID) || !$stmt->execute())
			$errorMessage[]= "CreateRoomLayout() SQL setup 1 failed: (" . $mysqli->errno . ") " . $mysqli->error;
		else
		{
			$stmt->store_result();
			$stmt->bind_result($locationID, $name, $xPos, $yPos, $width, $depth, $orientation, $site, $room, $hNo, $customer, $deviceid, $deviceName, $countL, $countD);

			$lastLocationID = PHP_INT_MAX;
			while($stmt->fetch())
			{
				$firstDevice = true;
				$popupText = "";
				
				if(!$renderingWithinParent)
				{
					//countL should always be >=1
					if(true || $countL>1 || $countD>1)//XXX this extra SELECT per locaition* could be avoided if the counts were 1 but would require duplication the popup text generation code 
					{
						//full locattion name, type, size
						//full device name type,size, sorted by cust, devicename
						
						//look up each location  & customer here
						$query2 = "SELECT l.locationid, l.name, l.xpos, l.ypos, l.width, l.depth, l.orientation, s.name, r.fullname, d.hno, c.name AS cust, d.deviceid, d.name as device, d.altname AS devicealtname, d.model, d.member
							FROM dcim_location AS l
								LEFT JOIN dcim_device AS d ON d.locationid=l.locationid AND d.status = 'A'
								LEFT JOIN dcim_customer AS c ON d.hno = c.hno
								LEFT JOIN dcim_room AS r ON r.roomid = l.roomid
								LEFT JOIN dcim_site AS s ON s.siteid = r.siteid
							WHERE r.roomid=? AND l.xpos=? AND l.ypos=? AND l.width=? AND l.depth=? AND l.orientation=?
							ORDER BY l.name, c.name, c.hno, d.name, d.unit";
						
						if (!($stmt2 = $mysqli->prepare($query2)) || !$stmt2->bind_Param('idddds', $roomID, $xPos,$yPos,$width,$depth,$orientation) || !$stmt2->execute())
							$errorMessage[]= "CreateRoomLayout() SQL setup 2 failed: (" . $mysqli->errno . ") " . $mysqli->error;
						else
						{
							$stmt2->store_result();
							$stmt2->bind_result($locationID, $name, $xPos, $yPos, $width, $depth, $orientation, $site, $room, $hNo, $customer, $deviceid, $deviceName,$deviceAltName, $model, $member);
							
							$lastCustomerID = PHP_INT_MAX;
							while($stmt2->fetch())
							{
								$newLocation = ($lastLocationID!=$locationID);
								$newCustomer = ($lastCustomerID!=$hNo || $newLocation);
								$fullLocationName = FormatLocation($site, $room, $name);
								$deviceFullName = GetDeviceFullName($deviceName, $model, $member,$deviceAltName, true);

								if(!$firstDevice)
									$popupText .= "<BR>";
								
								if($newLocation && !$firstDevice)
									$popupText .= "<BR>";//blank line between locations
								
								if($newLocation)
									$popupText .= "<b><a href='?locationid=$locationID'>$fullLocationName</a></b><BR>\n";
								
								if($hNo==NULL)
									$popupText .= "Empty";
								else
								{
									if($newCustomer)
										$popupText .= "<a href='?host=$hNo'>$customer</a><BR>\n";
									$popupText .= "&nbsp;&nbsp;&nbsp;&nbsp;<a href='?deviceid=$deviceid'>$deviceFullName</a>";
								}
								$firstDevice = false;
								$lastLocationID=$locationID;
								$lastCustomerID = $hNo;
							}
						}
					}
					else
					{ /* disabled since this code is not active and not up to date - see above comment
						$fullLocationName = FormatLocation($site, $room, $name);
						if($countD==0)
							$popupText = "<b><a href='?locationid=$locationID'>$fullLocationName</a></b><BR>Empty";
						else if($countD==1)
							$popupText = "<b><a href='?locationid=$locationID'>$fullLocationName</a></b><BR>
						<a href='?host=$hNo'>$customer</a><BR>
						<a href='?deviceid=$deviceid'>$deviceName</a>";*/
					}
				}
				else
				{//rendering within parent - simplify popup for title
					//if($countD>1)$customer = "Multiple";//could do this better with SQL group
					//if($countL>1)$name = "Multiple";
				}
				$result .= CreateLocationLayout($locationID, $name, $xPos, $yPos, $width, $depth, $orientation, $countD, $hNo, $customer, $parentWidth, $parentDepth, $renderingWithinParent, $popupText);
			}
		}
		
		if($renderingWithinParent)$result .= "</a>\n";
		$result .= "</div>\n";
		
		return $result;
	}
	
	function CreateRoomLayout_CornerInset($cornerWidthInset,$cornerDepthInset, $roomID, $roomTypeClass, &$roomCustomStyle, &$roomCustomHTML)
	{//percent inset corner
		//breaks rectangle into 4  corner rectangles #1 - #4 with #1 in top left
		//then set borders properly and background properly and disables the inset
		//determines which corner based on inset values such that negative x inset is inset from the right
		$borderThickness = CustomFunctions::$roomBorderThickness;
		
		$cornerNo = 1;//TL
		if($cornerWidthInset<0)
			$cornerNo=2;//TR
		if($cornerWidthInset<0 && $cornerDepthInset<0)
			$cornerNo=3;//BR
		else if($cornerDepthInset<0)
			$cornerNo=4;//BL
		
		$leftWidth = $cornerWidthInset;
		$topHeight = $cornerDepthInset;
		
		if($cornerWidthInset<0)$leftWidth+=100;
		if($cornerDepthInset<0)$topHeight+=100;
		
		$rightWidth = 100-$leftWidth;
		$bottomHeight = 100-$topHeight;
		
		$roomCustomStyle .= "#room".$roomID."_TopLeft {\n";
		$roomCustomStyle .= "	width: calc($leftWidth% + ".$borderThickness."px);\n";
		$roomCustomStyle .= "	height: calc($topHeight% + ".$borderThickness."px);\n";
		$roomCustomStyle .= "	border-style: solid hidden hidden solid;\n";
		if($cornerNo==2)$roomCustomStyle .= "	border-right-style: solid;\n";
		if($cornerNo==4)$roomCustomStyle .= "	border-bottom-style: solid;\n";
		$roomCustomStyle .= "}\n";
		$roomCustomStyle .= "#room".$roomID."_TopRight {\n";
		$roomCustomStyle .= "	left: $leftWidth%;\n";
		$roomCustomStyle .= "	width: $rightWidth%;\n";
		$roomCustomStyle .= "	height: calc($topHeight% + ".$borderThickness."px);\n";
		$roomCustomStyle .= "	border-style: solid solid hidden hidden;\n";
		if($cornerNo==1)$roomCustomStyle .= "	border-left-style: solid;\n";
		if($cornerNo==3)$roomCustomStyle .= "	border-bottom-style: solid;\n";
		$roomCustomStyle .= "}\n";
		$roomCustomStyle .= "#room".$roomID."_BottomRight {\n";
		$roomCustomStyle .= "	top: $topHeight%;\n";
		$roomCustomStyle .= "	left: $leftWidth%;\n";
		$roomCustomStyle .= "	width: $rightWidth%;\n";
		$roomCustomStyle .= "	height: $bottomHeight%;\n";
		$roomCustomStyle .= "	border-style: hidden solid solid hidden;\n";
		if($cornerNo==2)$roomCustomStyle .= "	border-top-style: solid;\n";
		if($cornerNo==4)$roomCustomStyle .= "	border-left-style: solid;\n";
		$roomCustomStyle .= "}\n";
		$roomCustomStyle .= "#room".$roomID."_BottomLeft {\n";
		$roomCustomStyle .= "	top: $topHeight%;\n";
		$roomCustomStyle .= "	width: calc($leftWidth% + ".$borderThickness."px);\n";
		$roomCustomStyle .= "	height: $bottomHeight%;\n";
		$roomCustomStyle .= "	border-style: hidden hidden solid solid;\n";
		if($cornerNo==1)$roomCustomStyle .= "	border-top-style: solid;\n";
		if($cornerNo==3)$roomCustomStyle .= "	border-right-style: solid;\n";
		$roomCustomStyle .= "}\n";
		
		if($cornerNo==2)
		{//special case for this corner because corner 4(BL) is added last it would overlap the inner corner making it look bad 
			$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_BottomLeft'></div>\n";
			$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_TopLeft'></div>\n";
			$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_BottomRight'></div>\n";
		}
		else
		{
			if($cornerNo!=1)$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_TopLeft'></div>\n";
			if($cornerNo!=2)$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_TopRight'></div>\n";
			if($cornerNo!=3)$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_BottomRight'></div>\n";
			if($cornerNo!=4)$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_BottomLeft'></div>\n";
		}
	}
	
	
	function CreateRoomLayout_FarLeftCornerAngle($cornerWidth, $roomID, $roomTypeClass, $depthToWidthRatio, &$roomCustomStyle, &$roomCustomHTML)
	{//not finished and not 100% correct
		//$cornerWidth = 34.577;//width of the corner to be rotated into place -  will need to be converted to height percentage
		$cornerHeight = $cornerWidth*$depthToWidthRatio;
		$cornerLength = sqrt($cornerWidth*$cornerWidth + $cornerHeight*$cornerHeight);
		
		$roomCustomStyle .= "#room".$roomID."_right {\n";
		$roomCustomStyle .= "	left: $cornerWidth%;\n";
		$roomCustomStyle .= "	width: ".(100-$cornerWidth)."%;\n";
		$roomCustomStyle .= "	border-style: solid solid solid hidden;\n";
		$roomCustomStyle .= "}\n";
		$roomCustomStyle .= "#room".$roomID."_left {\n";
		$roomCustomStyle .= "	top: $cornerHeight%;\n";
		$roomCustomStyle .= "	width: $cornerWidth%;\n";
		$roomCustomStyle .= "	height: ".(100-$cornerHeight)."%;\n";
		$roomCustomStyle .= "	border-style: hidden hidden solid solid;\n";
		$roomCustomStyle .= "}\n";
		$roomCustomStyle .= "#room".$roomID."_corner {\n";
		$roomCustomStyle .= "	top: $cornerHeight%;\n";
		$roomCustomStyle .= "	height: $cornerWidth%;\n";//length into shape
		$roomCustomStyle .= "	width: $cornerLength%;\n";
		$roomCustomStyle .= "	border-style: solid hidden hidden hidden;\n";
		$roomCustomStyle .= "	transform: rotate(-45deg);\n";
		$roomCustomStyle .= "}\n";
		
		$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_right'></div>\n";
		$roomCustomHTML .= "<div class='$roomTypeClass roomBorders' id='room".$roomID."_left'></div>\n";
		$roomCustomHTML .= "<div class='$roomTypeClass roomBorders roomCorner' id='room".$roomID."_corner'></div>\n";
	}
	
	function CreateLocationLayout($locationID, $name, $xPos, $yPos, $width, $depth, $orientation, $deviceCount, $hNo, $customer, $parentWidth, $parentDepth, $renderingWithinSite, $popupText)
	{
		$toolTipOffset = 15;
		
		//these are the back left of the location so it can be rotated into place
		$relativeX = 100*$xPos/$parentWidth;
		$relativeY= 100*$yPos/$parentDepth;
		
		//these are true top left on the screen for tooltips
		$relativeTopLeftX = $xPos;
		$relativeTopLeftY = $yPos;
		if($orientation=="E")
			$relativeTopLeftX -= $depth;
		else if($orientation=="W")
			$relativeTopLeftY -= $width;
		else if($orientation=="S")
		{
			$relativeTopLeftX -= $width;
			$relativeTopLeftY -= $depth;
		}
		
		$relativeTopLeftX = 100*($relativeTopLeftX/$parentWidth);
		$relativeTopLeftY= 100*($relativeTopLeftY/$parentDepth);
		
		//adjust dimentions if rotated
		if($orientation=="E" || $orientation=="W")
		{
			$relativeWidth= 100*(($width/$parentDepth)*($parentDepth/$parentWidth));
			$relativeDepth = 100*(($depth/$parentWidth)*($parentWidth/$parentDepth));
		}
		else
		{
			$relativeWidth = 100*($width/$parentWidth);
			$relativeDepth= 100*($depth/$parentDepth);
		}
		
		if($deviceCount>0)
		{
			if(CustomFunctions::IsThisHNoInternal($hNo) || $deviceCount>1)
				$name = $name . " ($customer [$deviceCount device".($deviceCount>1?"s":"")."])";
			else
				$name = $name . " ($customer)";//maybe show device names if this is a non cust access room like the MDF
			$locationClass = "locationFullBackground";
		}
		else
			$locationClass = "locationEmptyBackground";
		
		$rotation = OritentationToDegrees($orientation);
		$rotationTransform = "";
		$reverseRotationTransform = "";
		if($rotation!=0)
		{
			$rotationTransform = "	transform: rotate(".$rotation."deg); -ms-transform: rotate(".$rotation."deg); -webkit-transform: rotate(".$rotation."deg);\n";
			$reverseRotationTransform = "	transform: rotate(".-$rotation."deg); -ms-transform: rotate(".-$rotation."deg); -webkit-transform: rotate(".-$rotation."deg);\n";
		}
		
		$titleWidth = 100;
		$titleHeight = 100;
		if($orientation=="E" || $orientation=="W")
		{
			$titleWidth= 100*($depth/$width);
			$titleHeight = 100*($width/$depth);
		}
		
		$result = "<style>\n";
		$result .= "#location$locationID {\n";
		$result .= "	left: $relativeX%;\n";
		$result .= "	top: $relativeY%;\n";
		$result .= "	width: $relativeWidth%;\n";
		$result .= "	height: $relativeDepth%;\n";
		$result .= $rotationTransform;
		$result .= "}\n";
		$result .= "#location".$locationID."_title {\n";
		$result .= "	width: $titleWidth%;\n"; 
		$result .= "	height: $titleHeight%;\n";
		$result .= $reverseRotationTransform;
		
		if($orientation=="E")
			$result .= "	top: 100%;\n";
		else if($orientation=="S"){
			$result .= "	top: 100%;\n";
			$result .= "	left: 100%;\n";}
		else if($orientation=="W")
			$result .= "	left: 100%;\n";
		
		$result .= "}\n";
		if(!$renderingWithinSite)
		{
			$result .= "#location".$locationID."_tooltip {\n";
			$result .= "	left: $relativeTopLeftX%;\n";
			$result .= "	top: $relativeTopLeftY%;\n";
			$result .= "}\n";
		}
		$result .= "</style>\n";
		
		$result .= "<div id='location$locationID' class='locationContainer tooltip'>\n";
		if($renderingWithinSite)
			$result .= "	<a href='./?locationid=$locationID' title='$name'>\n";
		$result .= "	<div id='' class='$locationClass'>\n";
		$result .= "		<div id='location".$locationID."_title' class='locationTitle'>$name</div>\n";
		$result .= "	</div>\n";
		if($renderingWithinSite)
			$result .= "	</a>\n";
		$result .= "</div>\n";
		if(!$renderingWithinSite)
		{
			$result .= "<span id='location".$locationID."_tooltip' class='toolTip_LocationDetails'>$popupText</span>\n";
		}
		return $result;
	}
?>