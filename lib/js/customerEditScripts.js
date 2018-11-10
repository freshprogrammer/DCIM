/*scripts included on pages where user is able to edit data  and has permission to do so

This is mainly form management and input validation 
Edit*** called when that edit button is clicked to prepopulate with current values or clear form for entry
Save*** and Delete** called to validate form data and return true if all data checks out

note that when any edit form is brought up all other forms are hidden with HideAllEditForms()
*/
var editMode = false;
function ContactAdminToDoThisMsg(fieldName)
{
	return "If you need to edit "+fieldName+" please contact your admin";
}
function InitializeEditButton()
{
	var editModeCookie = GetCookie('dcim_editMode');
	//set it to the opposite then toggle to what I want
	editMode = !(editModeCookie=="true");
	ToggleEditMode();
}
function ToggleEditMode()
{
	editMode = !editMode;
	HideAllEditForms();
	if(editMode)
	{
		document.cookie ="dcim_editMode=true;";
		SwapStyleClass("editButtons_hidden", "editButtons_visible");
	}
	else
	{
		document.cookie = "dcim_editMode=; expires=Thu, 01 Jan 1970 00:00:00 GMT";
		SwapStyleClass("editButtons_visible", "editButtons_hidden");
	}
}
function SavePassword()
{
	var valid = false;
	var errorMsg = "";
	
	var minLen = 8;
	
	//other tests will be in php for final test -  all code in one place
	//if all checks were here error messages could be very decriptve and page responsive, but 
	//TODO test password here in PHP via AJAX - complex AJAX threading...
	
	document.getElementById("EditUserPasswordMsg").className = "hidden";

	//cant test current password here... just test len>=1
	if(document.getElementById("EditUserPassword_oldpas").value.length < 1){ 
		errorMsg = "Old password cannot be blank";
		FocusAndSelectAll("EditUserPassword_oldpas");
	}
	else if(document.getElementById("EditUserPassword_newpas1").value.length < minLen){ 
		errorMsg = "New password is must be at least "+minLen+" characters";
		FocusAndSelectAll("EditUserPassword_newpas1");
	}
	else if(document.getElementById("EditUserPassword_newpas1").value != document.getElementById("EditUserPassword_newpas2").value){ 
		errorMsg = "New passwords dont match";
		FocusAndSelectAll("EditUserPassword_newpas1");
	}
	else
		valid = true;

	if(valid)
	{
		var cancel;
		if(document.getElementById("EditUserPassword_username")!=null)
		{
			//seperate user - not editing self
			var userName = document.getElementById("EditUserPassword_username").value;
			cancel = window.confirm("Are you sure you want to change " + userName + "'s password?") == false;
		}
		else
			cancel = window.confirm("Are you sure you want to change your password?") == false;
		valid = !cancel;
	}
	
	//submit
	if(valid)
	{
		document.getElementById("EditUserPasswordForm").submit();
	}
	else
	{
		if(!cancel)
		{
			//show error msg
			document.getElementById("EditUserPasswordMsg").innerHTML = errorMsg;
			document.getElementById("EditUserPasswordMsg").className = "errorMessage";
		}
	}
	return valid; 
}
function HideAllEditForms()
{
	//these wont all exist on all pages
	try {
		document.getElementById("EditCustomerMsg").className = "hidden";
		document.getElementById("EditCustomerEntry").className = "hidden";
	}catch(err){}
	try {
		document.getElementById("EditBadgeMsg").className = "hidden";
		document.getElementById("EditBadgeEntry").className = "editForm hidden";
	}catch(err){}
	try {
		document.getElementById("EditDeviceMsg").className = "hidden";
		document.getElementById("EditDeviceEntry").className = "editForm hidden";
	}catch(err){}
	try {
		document.getElementById("EditConnectionMsg").className = "hidden";
		document.getElementById("EditConnectionEntry").className = "editForm hidden";
	}catch(err){}
	try {
		document.getElementById("EditDevicePortMsg").className = "hidden";
		document.getElementById("EditDevicePortEntry").className = "editForm hidden";
	}catch(err){}
	try {
		document.getElementById("EditPowerCircuitMsg").className = "hidden";
		document.getElementById("EditPowerCircuitEntry").className = "editForm hidden";
	}catch(err){}
	try {
		document.getElementById("EditSubnetMsg").className = "hidden";
		document.getElementById("EditSubnetEntry").className = "editForm hidden";
	}catch(err){}
	try {
		document.getElementById("EditLocationMsg").className = "hidden";
		document.getElementById("EditLocationEntry").className = "editForm hidden";
	}catch(err){}
	try {
		document.getElementById("EditPowerPanelMsg").className = "hidden";
		document.getElementById("EditPowerPanelEntry").className = "editForm hidden";
	}catch(err){}
}
function UnHideEditForm(form)
{
	form.className = "editForm";
}
function EditCustomer(add, hNo, cNo, name, notes, status)
{
	HideAllEditForms();
	//populate fields
	if(add)
	{
		document.getElementById("EditCustomerEntryLegend").innerHTML = "Add Customer";
		document.getElementById("EditCustomer_action").value = "Customer_Add";

		//on special add page - make some changes
		document.getElementById("EditCustomer_hno").readOnly = false;
		document.getElementById("EditCustomer_cno").readOnly = false; 
		//send back to home from cancel button
		document.getElementById("EditCustomer_cancelbutton").onclick = function () { window.location='./'; };
	}
	else
	{
		document.getElementById("EditCustomerEntryLegend").innerHTML = "Edit Customer ("+name+")";
		document.getElementById("EditCustomer_action").value = "Customer_Edit";
	}

	document.getElementById("EditCustomer_hno").value = hNo;
	document.getElementById("EditCustomer_cno").value = cNo;
	document.getElementById("EditCustomer_name").value = name;
	document.getElementById("EditCustomer_notes").value = notes;
	SelectItemByValue(document.getElementById("EditCustomer_status"),status);
	//unhide
	UnHideEditForm(document.getElementById("EditCustomerEntry"));
	//focus
	if(add)
		document.getElementById("EditCustomer_hno").focus();
	else
		document.getElementById("EditCustomer_name").focus();
}
function SaveCustomer()
{
	var valid = false;
	var errorMsg = "";
	var add = document.getElementById("EditCustomer_action").value == "Customer_Add";
	document.getElementById("EditCustomerMsg").className = "hidden";

	if(document.getElementById("EditCustomer_hno").value.length < 5 || isNaN(document.getElementById("EditCustomer_hno").value)){ 
		errorMsg = "Invalid H#";
		FocusAndSelectAll("EditCustomer_hno");
	}
	else if(document.getElementById("EditCustomer_cno").value.length < 5 || isNaN(document.getElementById("EditCustomer_cno").value)){ 
		errorMsg = "Invalid C#";
		FocusAndSelectAll("EditCustomer_cno");
	}
	else if(document.getElementById("EditCustomer_name").value.length < 3) {
		errorMsg = "Invalid Name";
		FocusAndSelectAll("EditCustomer_name");
	}
	else
		valid = true;

	if(add && valid)
	{
		var cancel = window.confirm("Are you sure you want to create this Customer?") == false;
		valid = !cancel;
	}
	
	//submit
	if(valid)
	{
		document.getElementById("EditCustomerForm").submit();
	}
	else
	{
		if(!cancel)
		{
			//show error msg
			document.getElementById("EditCustomerMsg").innerHTML = errorMsg;
			document.getElementById("EditCustomerMsg").className = "errorMessage";
		}
	}
	return valid; 
}
function EditBadge(add, badgeID, hNo, name, badgeNo, status, issue)
{
	HideAllEditForms();
	//populate fields
	document.getElementById("EditBadge_deletebtn").className = "hidden";
	if(add)
	{
		document.getElementById("EditBadgeEntryLegend").innerHTML = "Add Badge";
		document.getElementById("EditBadge_action").value = "Badge_Add";
	}
	else
	{
		document.getElementById("EditBadgeEntryLegend").innerHTML = "Edit Badge ("+name+")";
		document.getElementById("EditBadge_action").value = "Badge_Edit";
	}
	
	if(status=="R" || status=="D")
		document.getElementById("EditBadge_deletebtn").className = "";
	else
		document.getElementById("EditBadge_deletebtn").className = "hidden";

	document.getElementById("EditBadge_badgeid").value = badgeID;
	document.getElementById("EditBadge_hno").value = hNo;
	document.getElementById("EditBadge_name").value = name;
	document.getElementById("EditBadge_badgeno").value = badgeNo;
	document.getElementById("EditBadge_issue").value = issue;
	document.getElementById("EditBadge_paststatus").value = status;
	SelectItemByValue(document.getElementById("EditBadge_status"),status);
	//unhide
	UnHideEditForm(document.getElementById("EditBadgeEntry"));
	//focus
	document.getElementById("EditBadge_name").focus();
}
function DeleteBadge()
{
	var confirmed;
	confirmed = window.confirm("Are you sure you want to delete this badge? Have you already updated the badge server?");
	
	if(confirmed)
	{
		document.getElementById("EditBadge_action").value = "Badge_Delete";
		document.getElementById("EditBadgeForm").submit();
	}
}
function SaveBadge()
{
	var valid = false;
	var errorMsg = "";
	var add = document.getElementById("EditBadge_action").value == "Badge_Add";
	document.getElementById("EditBadgeMsg").className = "hidden";

	if(document.getElementById("EditBadge_hno").value.length < 6 || isNaN(document.getElementById("EditBadge_hno").value)){ 
		errorMsg = "Invalid H#";
		FocusAndSelectAll("EditBadge_hno");
	}
	else if(document.getElementById("EditBadge_name").value.length < 3) {
		errorMsg = "Invalid Name";
		FocusAndSelectAll("EditBadge_name");
	}
	else if(document.getElementById("EditBadge_badgeno").value.length <= 0 || document.getElementById("EditBadge_badgeno").value.length > 8 || isNaN(document.getElementById("EditBadge_badgeno").value)){ 
		errorMsg = "Invalid Badge No";
		FocusAndSelectAll("EditBadge_badgeno");
	}
	else
		valid = true;

	if(add && valid)
	{
		var cancel = window.confirm("Are you sure you want to create this Badge?") == false;
		valid = !cancel;
	}
	
	//submit
	if(valid)
	{
		document.getElementById("EditBadgeForm").submit();
	}
	else
	{
		if(!cancel)
		{
			//show error msg
			document.getElementById("EditBadgeMsg").innerHTML = errorMsg;
			document.getElementById("EditBadgeMsg").className = "errorMessage";
		}
	}
	return valid; 
}
function EditDevicePort(event, add, permission, devicePortID, deviceID, deviceName, portName, pic, port, type, status, speed, mac, note)
{
	if(!editMode)//clicked port while not in edit mode - do nothing
		return false;

	event = event || window.event;
	var elem = event.target || event.srcElement;
	if(elem.tagName!="DIV" && elem.tagName!="BUTTON")//ignore clicks on popup span, Font, Bold, ect - port only
		return true
	
	HideAllEditForms();
	
	//populate fields
	var canEdit = add || permission;
	if(add)
	{
		document.getElementById("EditDevicePortEntryLegend").innerHTML = "Add Port";
		document.getElementById("EditDevicePort_action").value = "DevicePort_Add";
	}
	else
	{
		document.getElementById("EditDevicePortEntryLegend").innerHTML = "Edit Port ("+deviceName+" "+portName+")";
		document.getElementById("EditDevicePort_action").value = "DevicePort_Edit";
	}
	if(!add && permission)
		document.getElementById("EditDevicePort_deletebtn").className = "";
	else
		document.getElementById("EditDevicePort_deletebtn").className = "hidden";
	
	document.getElementById("EditDevicePort_pic").readOnly = !canEdit;
	document.getElementById("EditDevicePort_portno").readOnly = !canEdit;
	document.getElementById("EditDevicePort_type").disabled = !canEdit;
	document.getElementById("EditDevicePort_status").disabled = !canEdit;

	document.getElementById("EditDevicePort_devicename").value = deviceName;
	document.getElementById("EditDevicePort_deviceid").value = deviceID;
	document.getElementById("EditDevicePort_deviceportid").value = devicePortID;
	document.getElementById("EditDevicePort_pic").value = pic;
	document.getElementById("EditDevicePort_portno").value = port;
	SelectItemByValue(document.getElementById("EditDevicePort_type"),type);
	SelectItemByValue(document.getElementById("EditDevicePort_status"),status);
	document.getElementById("EditDevicePort_speed").value = speed;
	document.getElementById("EditDevicePort_mac").value = mac;
	document.getElementById("EditDevicePort_note").value = note;
	
	//unhide
	UnHideEditForm(document.getElementById("EditDevicePortEntry"));
	//focus
	if(canEdit)
		document.getElementById("EditDevicePort_pic").focus();
	else
		document.getElementById("EditDevicePort_speed").focus();
}
function DeleteDevicePort()
{
	var confirmed = false;
	var confirm = prompt("Are you sure you want to delete this Port. Have you already dealt with all outstanding port connections and VLAN links? Enter 'YES' to confirm.", "");
	
	if (confirm != null) 
	{
		if(confirm=="YES")
			confirmed = true;
		else
			alert("Failed Authentication. You must be not be sure.");
	}
	
	if(confirmed)
	{
		document.getElementById("EditDevicePort_action").value = "DevicePort_Delete";
		document.getElementById("EditDevicePortForm").submit();
	}
}
function SaveDevicePort()
{
	var valid = false;
	var errorMsg = "";
	var add = document.getElementById("EditDevicePort_action").value == "DevicePort_Add";
	document.getElementById("EditDevicePortMsg").className = "hidden";

	var macField = document.getElementById("EditDevicePort_mac");
	macField.value = macField.value.toLowerCase();
	macField.value = replaceAll(macField.value,"-",":");
	macField.value = replaceAll(macField.value,".",":");
	macField.value = replaceAll(macField.value," ",":");
	
	if(document.getElementById("EditDevicePort_deviceid").value.length < 1 || isNaN(document.getElementById("EditDevicePort_deviceid").value)){ 
		errorMsg = "Invalid Device ID";
	}
	else if(document.getElementById("EditDevicePort_deviceportid").value.length < 1 || isNaN(document.getElementById("EditDevicePort_deviceportid").value)){ 
		errorMsg = "Invalid Device Port ID";
	}
	else if(document.getElementById("EditDevicePort_pic").value.length < 1 || document.getElementById("EditDevicePort_pic").value.length > 2 || isNaN(document.getElementById("EditDevicePort_pic").value)) {
		errorMsg = "Invalid Pic";
		FocusAndSelectAll("EditDevicePort_pic");
	}
	else if(document.getElementById("EditDevicePort_portno").value.length < 1 || document.getElementById("EditDevicePort_portno").value.length > 2 || isNaN(document.getElementById("EditDevicePort_portno").value)) {
		errorMsg = "Invalid Port";
		FocusAndSelectAll("EditDevicePort_port");
	}
	else if(macField.value.length>0 && !ValidMACAddress(macField.value)){ 
		errorMsg = "Invalid Mac Address. Format as 'ff:ff:ff:ff:ff:ff'";
		FocusAndSelectAll("EditDevicePort_mac");
	}
	else
		valid = true;
	
	if(add && valid)
	{
		var cancel = window.confirm("Are you sure you want to create this DevicePort?") == false;
		valid = !cancel;
	}
	
	//submit
	if(valid)
	{
		document.getElementById("EditDevicePort_type").disabled = false;
		document.getElementById("EditDevicePort_status").disabled = false;
		
		document.getElementById("EditDevicePortForm").submit();
	}
	else
	{
		if(!cancel)
		{
			//show error msg
			document.getElementById("EditDevicePortMsg").innerHTML = errorMsg;
			document.getElementById("EditDevicePortMsg").className = "errorMessage";
		}
	}
	return valid; 
}
function EditDevice(add, deviceID, roomID, hNo, name,altname, fullName, type, size, locationID, unit, status, notes, model, member, asset, serial)
{
	HideAllEditForms();
	//populate fields
	SelectItemByValue(document.getElementById("EditDevice_type"),type);
	window.selectedLocationID = locationID;
	if(add)
	{
		document.getElementById("EditDeviceEntryLegend").innerHTML = "Add Device";
		document.getElementById("EditDevice_action").value = "Device_Add";
		document.getElementById("EditDevice_name").placeholder = hNo+'-?';
		
		document.getElementById("EditDevice_name").title   = '';
		document.getElementById("EditDevice_type").title   = '';
		document.getElementById("EditDevice_model").title  = '';
		document.getElementById("EditDevice_member").title = '';
		onchange='EditDevice_RoomSelectChanged(-1)'
		
		EditDeviceTypeChanged(true);
	}
	else
	{
		document.getElementById("EditDeviceEntryLegend").innerHTML = "Edit Device ("+fullName+")";
		document.getElementById("EditDevice_action").value = "Device_Edit";
		
		document.getElementById("EditDevice_name").title   = ContactAdminToDoThisMsg("device name");
		document.getElementById("EditDevice_type").title   = ContactAdminToDoThisMsg("device type");
		document.getElementById("EditDevice_model").title  = ContactAdminToDoThisMsg("device model");
		document.getElementById("EditDevice_member").title = ContactAdminToDoThisMsg("device member");
		
		EditDeviceTypeChanged(false);
	}
	//these are not post editabled fields - need to be changed by admin - should never really be changed
	document.getElementById("EditDevice_name").disabled = !add;
	document.getElementById("EditDevice_type").disabled = !add;
	document.getElementById("EditDevice_size").disabled = !add;
	document.getElementById("EditDevice_model").disabled = !add;
	document.getElementById("EditDevice_member").disabled = !add;
	
	document.getElementById("EditDevice_deviceid").value = deviceID;
	document.getElementById("EditDevice_hno").value = hNo;
	document.getElementById("EditDevice_size").value = size;
	SelectItemByValue(document.getElementById("EditDevice_room"),roomID);
	document.getElementById("EditDevice_name").value = name;
	document.getElementById("EditDevice_altname").value = altname;
	document.getElementById("EditDevice_unit").value = unit;
	SelectItemByValue(document.getElementById("EditDevice_status"),status);
	document.getElementById("EditDevice_notes").value = notes;
	SelectItemByValue(document.getElementById("EditDevice_model"),model);
	SelectItemByValue(document.getElementById("EditDevice_member"),member);
	document.getElementById("EditDevice_asset").value = asset;
	document.getElementById("EditDevice_serial").value = serial;

	EditDevice_RoomSelectChanged(roomID)
	Sleep(5, PostAJAXRoomSelectChanged_FixLocationID);
	
	//unhide
	UnHideEditForm(document.getElementById("EditDeviceEntry"));
	//focus
	if(add)
		document.getElementById("EditDevice_name").focus();
	else
		document.getElementById("EditDevice_location").focus();
}
function EditDevice_RoomSelectChanged()
{
	selectedLocationID = window.window.selectedLocationID;
	var locationSelect = "EditDevice_location";
	document.getElementById(locationSelect).innerHTML = "<option value=-1>Loading...</option>";
	
	var data = 'function=RoomSelect'+
	'&roomid='+document.getElementById("EditDevice_room").value +
	'&selectedlocationid='+selectedLocationID;
	AJAXCall(data,locationSelect);
}
function PostAJAXRoomSelectChanged_FixLocationID()
{//seperate thread now?
	if(activeAJAXCalls>0)//wait again for AJAX calls to finish
		Sleep(5, PostAJAXRoomSelectChanged_FixLocationID);
	else
		SelectItemByValue(document.getElementById("EditDevice_location"),window.selectedLocationID);
}
function EditDeviceTypeChanged(updateValues)
{
	var colo = true;
	
	document.getElementById("EditDevice_size").value = '';
	
	var type = document.getElementById("EditDevice_type").value;
	if(type=="C")
	{
		//cage
		colo = true;
		document.getElementById("EditDevice_size").placeholder = '5x7';
	}
	else if(type=="F")
	{
		//full cab
		colo = true;
		document.getElementById("EditDevice_size").placeholder = 'Full, Half';
		if(updateValues)
		{
			document.getElementById("EditDevice_size").value = 'Full';
		}
	}
	else if(type=="H")
	{
		//half cab
		colo = true;
		document.getElementById("EditDevice_size").placeholder = 'Half';
		if(updateValues)
		{
			document.getElementById("EditDevice_size").value = 'Half';
		}
	}
	else if(type=="S")
	{
		//Physical/Switchs
		colo =false;
		document.getElementById("EditDevice_size").placeholder = '1U, 2U';
		if(updateValues)
		{
			SelectItemByValue(document.getElementById("EditDevice_model"),'Unknown');
		}
	}
	else
	{
		colo =false;
		//un defined shouldn't be possible
	}
	
	if(colo)
	{
		//hide internal fields
		document.getElementById("EditDevice_interalFields_row1").className = 'hidden';
		document.getElementById("EditDevice_interalFields_row2").className = 'hidden';
		document.getElementById("EditDevice_interalFields_row3").className = 'hidden';
		//set to propper default values - though these probably wont get checked at all
		if(updateValues)
		{
			document.getElementById("EditDevice_unit").value = '0';
			document.getElementById("EditDevice_member").value = '0';
			document.getElementById("EditDevice_asset").value = '';
			document.getElementById("EditDevice_serial").value = '';
		}
	}
	else
	{
		//un hide internal fields
		document.getElementById("EditDevice_interalFields_row1").className = '';
		document.getElementById("EditDevice_interalFields_row2").className = '';
		document.getElementById("EditDevice_interalFields_row3").className = '';
	}
}
function SaveDevice()
{
	var valid = false;
	var errorMsg = "";
	var add = document.getElementById("EditDevice_action").value == "Device_Add";
	var type = document.getElementById("EditDevice_type").value;
	var colo = (type=="F" || type=="H" || type=="C"); 
	//clear prev error 
	document.getElementById("EditDeviceMsg").className = "hidden";

	/*
	if(document.getElementById("EditDevice_asset").value.length < 7 || isNaN(document.getElementById("EditDevice_asset").value)){ 
		errorMsg = "Invalid asset";
		FocusAndSelectAll("EditDevice_asset");
	}*/
	//TODO check for existing... proper member (or other values) here in AJAX - prevent re-entering complex device info
	
	//hNo, name, notes, unit, size - 
	if(document.getElementById("EditDevice_hno").value.length < 6 || isNaN(document.getElementById("EditDevice_hno").value)){ 
		errorMsg = "Invalid H#";
		FocusAndSelectAll("EditDevice_hno");
	}
	else if(document.getElementById("EditDevice_name").value.length < 3) {
		errorMsg = "Invalid Device Name";
		FocusAndSelectAll("EditDevice_name");
	}
	else if(document.getElementById("EditDevice_size").value.length < 1) {
		errorMsg = "Invalid Size";
		FocusAndSelectAll("EditDevice_size");
	}
	else if(!document.getElementById("EditDevice_unit").disabled && (document.getElementById("EditDevice_unit").value.length < 1 || isNaN(document.getElementById("EditDevice_unit").value))) {
		errorMsg = "Invalid Unit. Must be a number"; // null or NAN
		FocusAndSelectAll("EditDevice_unit");
	}
	else if(!document.getElementById("EditDevice_unit").disabled && (document.getElementById("EditDevice_unit").value > 99 || document.getElementById("EditDevice_unit").value < 0)) {
		errorMsg = "Invalid Unit. Must be between 0 and 99";// not between 0 and 99
		FocusAndSelectAll("EditDevice_unit");
	}
	else if(!colo && document.getElementById("EditDevice_model").value=="Unknown"){ 
		errorMsg = "Cannot create device with unknown model. Contact Admin for assistance.";
		//document.getElementById("EditDevice_model").select();
	}
	else
		valid = true;

	var cancel = false;
	
	//ask about unit 0 for non colo
	if(valid && !colo && document.getElementById("EditDevice_unit").value=='0')
	{
		cancel = window.confirm("Are you sure this device is at unit 0 in the rack?") == false;
		valid = !cancel;
	}
	
	//submit
	if(valid)
	{
		//ask if sure
		if(add && valid)
		{
			valid = false;
			var confirm = prompt("Are you sure you want to create this device and all of its ports. Enter 'YES' to confirm.", "");
			if(confirm=="YES")
				valid = true;
			else
				alert("Failed Authentication. You must be not be sure.");
		}
		
		if(valid)
		{
			//re-enable fields for send - these should be the ones that are disabled dynamicly
			document.getElementById("EditDevice_name").disabled = false;
			document.getElementById("EditDevice_type").disabled = false;
			document.getElementById("EditDevice_size").disabled = false;
			document.getElementById("EditDevice_model").disabled = false;
			document.getElementById("EditDevice_member").disabled = false;
			
			//send this with submit
			document.getElementById("EditDeviceForm").submit();
		}
	}
	else
	{
		if(!cancel)
		{
			//show error msg
			document.getElementById("EditDeviceMsg").innerHTML = errorMsg;
			document.getElementById("EditDeviceMsg").className = "errorMessage";
		}
	}
	return valid; 
}
function EditConnection(add, portConnectionID, childDeviceID, childPortID, parentDeviceID, parentPortID, patches, description)
{
	HideAllEditForms();
	//populate fields
	if(add)
	{
		document.getElementById("EditConnectionEntryLegend").innerHTML = "Add Connection";
		document.getElementById("EditConnection_action").value = "Connection_Add";
		document.getElementById("EditConnection_deletebutton").className = "";
		try
		{
			document.getElementById("EditConnection_updateportsrow").className = "hidden";
		}catch(err){}
		
		//select first devices
		document.getElementById("EditConnection_childdeviceid").selectedIndex  = 0;
		document.getElementById("EditConnection_parentdeviceid").selectedIndex  = 0;
		
		//populate port selects
		DeviceSelectChanged(true, -1);
		DeviceSelectChanged(false, -1);
	}
	else
	{
		document.getElementById("EditConnectionEntryLegend").innerHTML = "Edit Connection ("+description+")";
		document.getElementById("EditConnection_action").value = "Connection_Edit";
		document.getElementById("EditConnection_deletebutton").className = "";
		try
		{
			document.getElementById("EditConnection_updateportsrow").className = "";
			document.getElementById("EditConnection_updateports").value = "T";
		}catch(err){}
	
		//select propper devices
		document.getElementById("EditConnection_childdeviceid").value = childDeviceID;
		document.getElementById("EditConnection_parentdeviceid").value = parentDeviceID;
		
		//populate port selects
		DeviceSelectChanged(true, childPortID);
		DeviceSelectChanged(false, parentPortID);
		
		//select propper ports after AJAX completes - store globaly in window till then
		window.childPortID = childPortID;
		window.parentPortID = parentPortID;
		Sleep(5, PostAJAXDeviceChange_FixPortIDs);
	}

	document.getElementById("EditConnection_patches").value = patches;
	document.getElementById("EditConnection_portconnectionid").value = portConnectionID;
	
	//unhide
	UnHideEditForm(document.getElementById("EditConnectionEntry"));
	//focus
	document.getElementById("EditConnection_childportid").focus();
}
function PostAJAXDeviceChange_FixPortIDs()
{//seperate thread now?
	if(activeAJAXCalls>0)//wait again for AJAX calls to finish
	{
		Sleep(5, PostAJAXDeviceChange_FixPortIDs);
	}
	else
	{
		document.getElementById("EditConnection_childportid").value = window.childPortID;
		document.getElementById("EditConnection_parentportid").value = window.parentPortID;
	}
}
function DeviceSelectChanged(isChild, selectedPortID)
{
	var deviceSelect = "";
	var portSelect = "";
	if(isChild)
	{
		var deviceSelect = "EditConnection_childdeviceid";
		var portSelect = "EditConnection_childportid";
		document.getElementById("EditConnection_childportid").innerHTML = "<option value=-1>Loading...</option>";
	}
	else
	{
		var deviceSelect = "EditConnection_parentdeviceid";
		var portSelect = "EditConnection_parentportid";
		document.getElementById("EditConnection_parentportid").innerHTML = "<option value=-1>Loading...</option>";
	}
	
	var data = 'function=DevicePortSelect'+
	'&deviceid='+document.getElementById(deviceSelect).value +
	'&selectedportid='+selectedPortID;
	var dest = portSelect;
	AJAXCall(data,dest);
}
function SaveConnection()
{
	var valid = false;
	var errorMsg = "";
	var add = document.getElementById("EditConnection_action").value == "Connection_Add";
	document.getElementById("EditConnectionMsg").className = "hidden";
	
	//all data is from selects (or blah notes) data cant be wrong unless hacking
	if(document.getElementById("EditConnection_childdeviceid").value.length < 1 || isNaN(document.getElementById("EditConnection_childdeviceid").value)){ 
		errorMsg = "Invalid Child Device";
		document.getElementById("EditConnection_childportid").focus();
	}
	else if(document.getElementById("EditConnection_childportid").value.length < 1 || isNaN(document.getElementById("EditConnection_childportid").value)){ 
		errorMsg = "Invalid Child Port";
		document.getElementById("EditConnection_childportid").focus();
	}
	else if(document.getElementById("EditConnection_parentdeviceid").value.length < 1 || isNaN(document.getElementById("EditConnection_parentdeviceid").value)){ 
		errorMsg = "Invalid Parent Device";
		document.getElementById("EditConnection_parentdeviceid").focus();
	}
	else if(document.getElementById("EditConnection_parentportid").value.length < 1 || isNaN(document.getElementById("EditConnection_parentportid").value)){ 
		errorMsg = "Invalid Parent Port";
		document.getElementById("EditConnection_parentdeviceid").focus();
	}
	else if(document.getElementById("EditConnection_parentportid").value == document.getElementById("EditConnection_childportid").value){ 
		errorMsg = "The child port cannot be the same as the parent port";
		document.getElementById("EditConnection_childportid").focus();
	}
	else
		valid = true;
	
	if(add && valid)
	{
		var cancel = window.confirm("Are you sure you want to create this Connection?") == false;
		valid = !cancel;
	}
	
	//submit
	if(valid)
	{
		document.getElementById("EditConnectionForm").submit();
	}
	else
	{
		if(!cancel)
		{
			//show error msg
			document.getElementById("EditConnectionMsg").innerHTML = errorMsg;
			document.getElementById("EditConnectionMsg").className = "errorMessage";
		}
	}
	return valid; 
}
function DeleteConnection()
{
	var confirmed;
	try
	{
		if(document.getElementById("EditConnection_updateports").value == "T")
			confirmed = window.confirm("Are you sure you want to delete this connection and clear the ports' notes, speeds, and VLANs?");
		else
			confirmed = window.confirm("Are you sure you want to delete this connection?");
	}catch(err)
	{
		confirmed = window.confirm("Are you sure you want to delete this connection?");
	}
	
	if(confirmed)
	{
		document.getElementById("EditConnection_action").value = "Connection_Delete";
		
		document.getElementById("EditConnectionForm").submit();
	}
}
function EditPowerCircuit(add, powerCircuitID, locationID, panelID, panelName, circuit, volts, amps, status, load)
{
	HideAllEditForms();
	//populate fields
	if(add)
	{
		document.getElementById("EditPowerCircuitEntryLegend").innerHTML = "Add Power Circuit";
		document.getElementById("EditPowerCircuit_action").value = "PowerCircuit_Add";
		try{
			document.getElementById("EditPowerCircuit_deletebutton").className = "hidden";
		} catch (err){}

		document.getElementById("EditPowerCircuit_powerpanelid").disabled = true;
		document.getElementById("EditPowerCircuit_locationid").disabled = false;
		document.getElementById("EditPowerCircuit_volts").disabled = false;
	}
	else
	{
		document.getElementById("EditPowerCircuitEntryLegend").innerHTML = "Edit Power Circuit ("+panelName+" CRK#"+circuit+")";
		document.getElementById("EditPowerCircuit_action").value = "PowerCircuit_Edit";
		try{
			document.getElementById("EditPowerCircuit_deletebutton").className = "";
		} catch (err){}

		document.getElementById("EditPowerCircuit_locationid").disabled = true;
		document.getElementById("EditPowerCircuit_powerpanelid").disabled = true;
		document.getElementById("EditPowerCircuit_volts").disabled = true;
	}
	document.getElementById("EditPowerCircuit_circuit").readOnly = !add;
	
	document.getElementById("EditPowerCircuit_powercircuitid").value = powerCircuitID;
	document.getElementById("EditPowerCircuit_locationid").value = locationID;
	document.getElementById("EditPowerCircuit_locationid").originalValue = locationID;//save to detect changes
	document.getElementById("EditPowerCircuit_powerpanelid").value = panelID;
	document.getElementById("EditPowerCircuit_circuit").value = circuit;
	document.getElementById("EditPowerCircuit_volts").value = volts;
	document.getElementById("EditPowerCircuit_amps").value = amps;
	document.getElementById("EditPowerCircuit_status").checked = status=="A";
	document.getElementById("EditPowerCircuit_load").value = load;
	
	//unhide
	UnHideEditForm(document.getElementById("EditPowerCircuitEntry"));
	//focus
	if(add)
		document.getElementById("EditPowerCircuit_circuit").focus();
	else
		document.getElementById("EditPowerCircuit_load").focus();
}
function EditPowerCircuit_StatusClicked()
{
	if(!document.getElementById("EditPowerCircuit_status").checked)
	{
		document.getElementById("EditPowerCircuit_load").value = 0;
	}
}
function EditPowerCircuit_LoadChanged()
{
	if(document.getElementById("EditPowerCircuit_load").value > 0)
	{
		document.getElementById("EditPowerCircuit_status").checked = true;
	}
}
function ValidPowerPanel(input)
{
	//could load in array of existing panels and check against that... new panels would have to be added backdoor
	//test against regex   //# or ## or #-##
	//var panelFormat = /^(\d|\d\d|\d-\d)$/;
	//return panelFormat.test(input);
	return input.length >= 1 && input.length <= 8;
}
function SavePowerCircuit()
{
	var valid = false;
	var errorMsg = "";
	var add = document.getElementById("EditPowerCircuit_action").value == "PowerCircuit_Add";
	document.getElementById("EditPowerCircuitMsg").className = "hidden";
	
	if(add && (document.getElementById("EditPowerCircuit_circuit").value.length < 1 || isNaN(document.getElementById("EditPowerCircuit_circuit").value))){ 
		errorMsg = "Invalid Power Circuit";
		document.getElementById("EditPowerCircuit_circuit").focus();
	}
	else if(parseInt(document.getElementById("EditPowerCircuit_load").value) > parseInt(document.getElementById("EditPowerCircuit_amps").value)){ 
		errorMsg = "Invalid load. Load cannot be greater than the circuit's ampere rating";
		document.getElementById("EditPowerCircuit_load").focus();
	}
	else if(!add && document.getElementById("EditPowerCircuit_locationid").originalValue != document.getElementById("EditPowerCircuit_locationid").value && !window.confirm("Are you sure you want to change this location?")){ 
		errorMsg = "Unsure about location change. Location has been reset";
		document.getElementById("EditPowerCircuit_locationid").value = document.getElementById("EditPowerCircuit_locationid").originalValue;
		document.getElementById("EditPowerCircuit_locationid").focus();
	}
	else
		valid = true;

	if(add && valid)
	{
		if(document.getElementById("EditPowerCircuit_volts").value==308)
			var cancel = window.confirm("Are you sure you want to create these 3 Power Circuits?") == false;
		else
			var cancel = window.confirm("Are you sure you want to create this Power Circuit?") == false;
		valid = !cancel;
	}
	
	//submit
	if(valid)
	{//dont need to enabled the other fields since they are never editable
		document.getElementById('EditPowerCircuit_powerpanelid').disabled = false;
		document.getElementById("EditPowerCircuit_volts").disabled = false;
		
		document.getElementById("EditPowerCircuitForm").submit();
	}
	else
	{
		if(!cancel)
		{
			//show error msg
			document.getElementById("EditPowerCircuitMsg").innerHTML = errorMsg;
			document.getElementById("EditPowerCircuitMsg").className = "errorMessage";
		}
	}
	return valid; 
}
function DeletePowerCircuit()
{
	var confirmed;
	if(document.getElementById("EditPowerCircuit_volts").value==308)
		confirmed = window.confirm("Are you sure you want to delete these 3 power circuits and unlink them from this location?");
	else
		confirmed = window.confirm("Are you sure you want to delete this power circuit and unlink it from this location?");
	
	if(confirmed)
	{
		document.getElementById("EditPowerCircuit_volts").disabled = false;
		document.getElementById("EditPowerCircuit_powerpanelid").disabled = false;
		
		document.getElementById("EditPowerCircuit_action").value = "PowerCircuit_Delete";
		document.getElementById("EditPowerCircuitForm").submit();
	}
}
function EditPowerPanel(add, powerPanelID, roomID, upsID, siteName, name, amps, circuits, orientation, x, y, width, depth, note)
{
	HideAllEditForms();
	//populate fields
	if(add)
	{
		document.getElementById("EditPowerPanelEntryLegend").innerHTML = "Add Power Panel";
		document.getElementById("EditPowerPanel_action").value = "PowerPanel_Add";
		document.getElementById("EditPowerPanel_deletebtn").className = "hidden";
	}
	else
	{
		document.getElementById("EditPowerPanelEntryLegend").innerHTML = "Edit Power Panel ("+siteName +" "+name+")";
		document.getElementById("EditPowerPanel_action").value = "PowerPanel_Edit";
		document.getElementById("EditPowerPanel_deletebtn").className = "";
	}
	
	document.getElementById("EditPowerPanel_powerpanelid").value = powerPanelID;
	SelectItemByValue(document.getElementById("EditPowerPanel_roomid"),roomID);
	document.getElementById("EditPowerPanel_name").value = name;
	document.getElementById("EditPowerPanel_amps").value = amps;
	document.getElementById("EditPowerPanel_circuits").value = circuits;
	SelectItemByValue(document.getElementById("EditPowerPanel_upsid"),upsID);
	SelectItemByValue(document.getElementById("EditPowerPanel_orientation"),orientation);
	document.getElementById("EditPowerPanel_xpos").value = x;
	document.getElementById("EditPowerPanel_ypos").value = y;
	document.getElementById("EditPowerPanel_width").value = width;
	document.getElementById("EditPowerPanel_depth").value = depth;
	document.getElementById("EditPowerPanel_note").value = note;
	
	//unhide
	UnHideEditForm(document.getElementById("EditPowerPanelEntry"));
	//focus
	document.getElementById("EditPowerPanel_name").focus();
}
function SavePowerPanel()
{
	var valid = false;
	var errorMsg = "";
	var posLimit = 9999.99;
	var ampsLimit = 1000;
	var circuitsLimit = 150;
	
	var add = document.getElementById("EditPowerPanel_action").value == "PowerPanel_Add";
	document.getElementById("EditPowerPanelMsg").className = "hidden";
	
	if(document.getElementById("EditPowerPanel_name").value.length < 1 || document.getElementById("EditPowerPanel_name").value.length > 50){ 
		errorMsg = "Invalid PowerPanel Name";
		document.getElementById("EditPowerPanel_name").focus();
	}
	else if(isNaN(document.getElementById("EditPowerPanel_amps").value) || document.getElementById("EditPowerPanel_amps").value <= 0 || document.getElementById("EditPowerPanel_amps").value > ampsLimit){ 
		errorMsg = "Invalid Amps";
		document.getElementById("EditPowerPanel_amps").focus();
	}
	else if(isNaN(document.getElementById("EditPowerPanel_circuits").value) || document.getElementById("EditPowerPanel_circuits").value <= 0 || document.getElementById("EditPowerPanel_circuits").value > circuitsLimit){ 
		errorMsg = "Invalid Circuits";
		document.getElementById("EditPowerPanel_circuits").focus();
	}
	else if(isNaN(document.getElementById("EditPowerPanel_xpos").value) || document.getElementById("EditPowerPanel_xpos").value < -posLimit || document.getElementById("EditPowerPanel_xpos").value > posLimit){ 
		errorMsg = "Invalid Left value";
		document.getElementById("EditPowerPanel_xpos").focus();
	}
	else if(isNaN(document.getElementById("EditPowerPanel_ypos").value) || document.getElementById("EditPowerPanel_ypos").value < -posLimit || document.getElementById("EditPowerPanel_ypos").value > posLimit){ 
		errorMsg = "Invalid Foreward value";
		document.getElementById("EditPowerPanel_ypos").focus();
	}
	else if(isNaN(document.getElementById("EditPowerPanel_width").value) || document.getElementById("EditPowerPanel_width").value <= 0 || document.getElementById("EditPowerPanel_width").value > posLimit){ 
		errorMsg = "Invalid Width value";
		document.getElementById("EditPowerPanel_width").focus();
	}
	else if(isNaN(document.getElementById("EditPowerPanel_depth").value) || document.getElementById("EditPowerPanel_depth").value <= 0 || document.getElementById("EditPowerPanel_depth").value > posLimit){ 
		errorMsg = "Invalid Depth value";
		document.getElementById("EditPowerPanel_depth").focus();
	}
	else
		valid = true;
	
	if(add && valid)
	{
		var cancel = confirm("Are you sure you want to create this PowerPanel?") == false;
		valid = !cancel;
	}
	
	//submit
	if(valid)
	{
		document.getElementById("EditPowerPanelForm").submit();
	}
	else
	{
		if(!cancel)
		{
			//show error msg
			document.getElementById("EditPowerPanelMsg").innerHTML = errorMsg;
			document.getElementById("EditPowerPanelMsg").className = "errorMessage";
		}
	}
	return valid; 
}
function DeletePowerPanel()
{
	var confirmed;
	confirmed = confirm("Are you sure you want to delete this power panel?");
	
	if(confirmed)
	{
		document.getElementById("EditPowerPanel_action").value = "PowerPanel_Delete";
		document.getElementById("EditPowerPanelForm").submit();
	}
}
function EditLocation(add, locationID, roomID, name, altName, type, units, orientation, keyno, allocation, order, x, y, width, depth, note)
{
	HideAllEditForms();
	//populate fields
	if(add)
	{
		document.getElementById("EditLocationEntryLegend").innerHTML = "Add Location";
		document.getElementById("EditLocation_action").value = "Location_Add";
		document.getElementById("EditLocation_deletebtn").className = "hidden";
	}
	else
	{
		document.getElementById("EditLocationEntryLegend").innerHTML = "Edit Location ("+name+")";
		document.getElementById("EditLocation_action").value = "Location_Edit";
		document.getElementById("EditLocation_deletebtn").className = "";
	}

	document.getElementById("EditLocation_locationid").value = locationID;
	SelectItemByValue(document.getElementById("EditLocation_roomid"),roomID);
	document.getElementById("EditLocation_name").value = name;
	document.getElementById("EditLocation_altname").value = altName;
	SelectItemByValue(document.getElementById("EditLocation_type"),type);
	SelectItemByValue(document.getElementById("EditLocation_allocation"),allocation);
	document.getElementById("EditLocation_units").value = units;
	SelectItemByValue(document.getElementById("EditLocation_order"),order);
	SelectItemByValue(document.getElementById("EditLocation_orientation"),orientation);
	document.getElementById("EditLocation_xpos").value = x;
	document.getElementById("EditLocation_ypos").value = y;
	document.getElementById("EditLocation_width").value = width;
	document.getElementById("EditLocation_depth").value = depth;
	document.getElementById("EditLocation_keyno").value = keyno;
	document.getElementById("EditLocation_note").value = note;
	
	//unhide
	UnHideEditForm(document.getElementById("EditLocationEntry"));
	//focus
	document.getElementById("EditLocation_name").focus();
}
function EditLocationTypeChanged()
{
	var units = 1;
	var width = 0;
	var depth = 0;
	var type = document.getElementById("EditLocation_type").value;
	if(type=="F" || type=="H")
	{
		if(type=="H")
			units = 20;
		else
			units = 42;
		width= 2;
		depth= 3.5;
		document.getElementById("EditLocation_order").value = 'R';
	}
	else if(type=="R")
	{
		units = 45;
		width= 1.71;//20.5in
		depth= 2;
		document.getElementById("EditLocation_order").value = 'N';
	}
	else if(type=="C")
	{
		units = 1;
		width= 5.25;
		depth= 7.25;
		document.getElementById("EditLocation_order").value = 'N';
	}
	document.getElementById("EditLocation_units").value = units;
	document.getElementById("EditLocation_width").value = width;
	document.getElementById("EditLocation_depth").value = depth;
}
function SaveLocation()
{
	var valid = false;
	var errorMsg = "";
	var posLimit = 9999.99;
	var unitsLimit = 50;
	
	var add = document.getElementById("EditLocation_action").value == "Location_Add";
	document.getElementById("EditLocationMsg").className = "hidden";
	
	if(document.getElementById("EditLocation_name").value.length < 2 || document.getElementById("EditLocation_name").value.length > 50){ 
		errorMsg = "Invalid Location Name";
		document.getElementById("EditLocation_name").focus();
	}
	else if(isNaN(document.getElementById("EditLocation_units").value) || document.getElementById("EditLocation_units").value < 0 || document.getElementById("EditLocation_units").value > unitsLimit){ 
		errorMsg = "Invalid Units";
		document.getElementById("EditLocation_units").focus();
	}
	else if(isNaN(document.getElementById("EditLocation_xpos").value) || document.getElementById("EditLocation_xpos").value < -posLimit || document.getElementById("EditLocation_xpos").value > posLimit){ 
		errorMsg = "Invalid Left value";
		document.getElementById("EditLocation_xpos").focus();
	}
	else if(isNaN(document.getElementById("EditLocation_ypos").value) || document.getElementById("EditLocation_ypos").value < -posLimit || document.getElementById("EditLocation_ypos").value > posLimit){ 
		errorMsg = "Invalid Foreward value";
		document.getElementById("EditLocation_ypos").focus();
	}
	else if(isNaN(document.getElementById("EditLocation_width").value) || document.getElementById("EditLocation_width").value <= 0 || document.getElementById("EditLocation_width").value > posLimit){ 
		errorMsg = "Invalid Width value";
		document.getElementById("EditLocation_width").focus();
	}
	else if(isNaN(document.getElementById("EditLocation_depth").value) || document.getElementById("EditLocation_depth").value <= 0 || document.getElementById("EditLocation_depth").value > posLimit){ 
		errorMsg = "Invalid Depth value";
		document.getElementById("EditLocation_depth").focus();
	}
	else
		valid = true;
	
	if(add && valid)
	{
		var cancel = window.confirm("Are you sure you want to create this location?") == false;
		valid = !cancel;
	}
	
	//submit
	if(valid)
	{
		document.getElementById("EditLocationForm").submit();
	}
	else
	{
		if(!cancel)
		{
			//show error msg
			document.getElementById("EditLocationMsg").innerHTML = errorMsg;
			document.getElementById("EditLocationMsg").className = "errorMessage";
		}
	}
	return valid; 
}
function DeleteLocation()
{
	var confirmed;
	confirmed = window.confirm("Are you sure you want to delete this location?");
	
	if(confirmed)
	{
		document.getElementById("EditLocation_action").value = "Location_Delete";
		document.getElementById("EditLocationForm").submit();
	}
}
function EditSubnet(add, vlanID, portID,vlan,subnet,mask,gateway,first,last,note)
{
	HideAllEditForms();
	//populate fields
	if(add)
	{
		document.getElementById("EditSubnetEntryLegend").innerHTML = "Add Subnet";
		document.getElementById("EditSubnet_action").value = "Subnet_Add";
		document.getElementById("EditSubnet_deletebutton").className = "hidden";

		document.getElementById("EditSubnet_portid").selectedIndex  = 0;
	}
	else
	{
		document.getElementById("EditSubnetEntryLegend").innerHTML = "Edit Subnet ("+vlan+" "+subnet+")";
		document.getElementById("EditSubnet_action").value = "Subnet_Edit";
		document.getElementById("EditSubnet_deletebutton").className = "";
		
		if(vlan<0)
			vlan = vlan.replace("-","T");

		SelectItemByValue(document.getElementById("EditSubnet_portid"),portID);
	}

	document.getElementById("EditSubnet_portid").disabled = !add;

	document.getElementById("EditSubnet_vlanid").value = vlanID;
	document.getElementById("EditSubnet_vlan").value = vlan;
	document.getElementById("EditSubnet_subnet").value = subnet;
	document.getElementById("EditSubnet_mask").value = mask;
	document.getElementById("EditSubnet_gateway").value = gateway;
	document.getElementById("EditSubnet_first").value = first;
	document.getElementById("EditSubnet_last").value = last;
	document.getElementById("EditSubnet_note").value = note;
	
	
	//unhide
	UnHideEditForm(document.getElementById("EditSubnetEntry"));
	//focus
	if(add)
		document.getElementById("EditSubnet_portid").focus();
	else
		document.getElementById("EditSubnet_vlan").focus();
}
function EditSubnet_SubnetChanged()
{
	var subnetInput = document.getElementById("EditSubnet_subnet").value;
	var mask = "";
	var gateway = "";
	var first = "";
	var last = "";
	var subnet = GetValidSubnet(subnetInput);
	if(subnet!=null)
	{
		//update other fields with defauls for this subnet
		mask	= GetMaskFromSubnet(subnet);
		gateway	= GetGatewayFromSubnet(subnet);
		first	= GetFirstFromSubnet(subnet);
		last	= GetLastFromSubnet(subnet);

		document.getElementById("EditSubnet_subnet").value = subnet;
	}
	document.getElementById("EditSubnet_mask").value = mask;
	document.getElementById("EditSubnet_gateway").value = gateway;
	document.getElementById("EditSubnet_first").value = first;
	document.getElementById("EditSubnet_last").value = last;
}
function SaveSubnet()
{
	var valid = false;
	var errorMsg = "";
	var add = document.getElementById("EditSubnet_action").value == "Subnet_Add";
	document.getElementById("EditSubnetMsg").className = "hidden";
	var subnet = document.getElementById("EditSubnet_vlan").value;
	subnet = subnet.replace("T","");
	
	var newTempVLAN = (""+document.getElementById("EditSubnet_vlan").value).length ==0;
	
	if(!newTempVLAN && (isNaN(subnet) || subnet <0 || subnet >9999)){ 
		errorMsg = "Invalid VLAN";
		document.getElementById("EditSubnet_vlan").focus();
	}
	else if(GetValidSubnet(document.getElementById("EditSubnet_subnet").value)==null){ 
		errorMsg = "Invalid Subnet";
		document.getElementById("EditSubnet_subnet").focus();
	}
	else if(!ValidIPAddress(document.getElementById("EditSubnet_mask").value)){ 
		errorMsg = "Invalid Subnet Mask";
		document.getElementById("EditSubnet_mask").focus();
	}
	else if(!ValidIPAddress(document.getElementById("EditSubnet_gateway").value)){ 
		errorMsg = "Invalid Gateway";
		document.getElementById("EditSubnet_gateway").focus();
	}
	else if(!ValidIPAddress(document.getElementById("EditSubnet_first").value)){ 
		errorMsg = "Invalid First IP";
		document.getElementById("EditSubnet_first").focus();
	}
	else if(!ValidIPAddress(document.getElementById("EditSubnet_last").value)){ 
		errorMsg = "Invalid Last IP";
		document.getElementById("EditSubnet_last").focus();
	}
	else
		valid = true;

	if(add && valid)
	{
		var cancel;
		if(newTempVLAN)
			cancel = window.confirm("Are you sure you want to create this Subnet with an unknown VLAN?") == false;
		else
			cancel = window.confirm("Are you sure you want to create this Subnet on VLAN "+VLAN+"?") == false;
		valid = !cancel;
	}
	else if(valid)
	{
		var cancel = false;
		if(newTempVLAN)
			cancel = window.confirm("Continue with subnet edit on unknown VLAN?") == false;
		valid = !cancel;
	}
	
	//submit
	if(valid)
	{
		document.getElementById("EditSubnet_vlan").value = document.getElementById("EditSubnet_vlan").value.replace("T","-");
		document.getElementById("EditSubnet_portid").disabled = false;
		
		document.getElementById("EditSubnetForm").submit();
	}
	else
	{
		if(!cancel)
		{
			//show error msg
			document.getElementById("EditSubnetMsg").innerHTML = errorMsg;
			document.getElementById("EditSubnetMsg").className = "errorMessage";
		}
	}
	return valid; 
}
function DeleteSubnet()
{
	var confirmed;
	confirmed = window.confirm("Are you sure you want to delete this subnet (and if its the last subnet on the VLAN unlink this VLAN from all its ports)?");
	
	if(confirmed)
	{
		document.getElementById("EditSubnet_portid").disabled = false;
		
		document.getElementById("EditSubnet_action").value = "Subnet_Delete";
		document.getElementById("EditSubnetForm").submit();
	}
}
function QARecord(table, key, cellID)
{//todo - remove form action and instance id
	var cont = window.confirm("Are you sure this data is correct");
	if (!cont)
		return;
	
	var data = 'function=QA'+
	'&table='+table +
	'&id='+key;
	AJAXCall(data,cellID);
}
function SavePowerAuditPanel()
{
	var valid = true;//This is totaly unnecisarry on real browsers but iOS does repect number restrictions
	var form = document.getElementById('PowerAuditPanelForm');
	var invalidInput;
	for ( var i = 0; i < form.elements.length; i++ )
	{//check number input fields to see if anything has been entered
		if(form.elements[i].type=="number" && (parseFloat("0"+form.elements[i].value)<form.elements[i].min || parseFloat("0"+form.elements[i].value)>form.elements[i].max))
		{//there is a value here
			valid = false;
			alert("Invalid value entered. Must be from "+form.elements[i].min+" to "+form.elements[i].max+".");
			form.elements[i].focus();
			break;
		}
	}
	if(valid)
		valid = window.confirm("Are you sure you want to update all these records?");
	return valid; 
}
function PowerAuditPanel_ConfirmPageChange(url)
{
	var confirmed = true;
	var form = document.getElementById('PowerAuditPanelForm');
	for ( var i = 0; i < form.elements.length; i++ )
	{//check number input fields to see if anything has been entered
		if(form.elements[i].type=="number" && form.elements[i].value!="")
		{//there is a value here
			confirmed = window.confirm("Are you sure you want to navigate away from this page and lose you unsaved progress?");
			break;
		}
		//didn't find any entered values - open url
	}
	if(confirmed)
		window.open(url,"_self");
}
function PowerAuditCircuit_StatusClicked(srcCheckbox, srcLoadField)
{
	if(!document.getElementById(srcCheckbox).checked || document.getElementById(srcLoadField).value.length==0)
	{//put a 0 here so this change is saved
		document.getElementById(srcLoadField).value = 0;
	}
}
function PowerAuditCircuit_LoadChanged(srcLoadField, srcCheckbox)
{
	if(document.getElementById(srcLoadField).value > 0)
	{
		document.getElementById(srcCheckbox).checked = true;
	}
}