//main scripts included with every page

function FocusMainSearch()
{
	document.forms['MainSearch'].elements['search'].focus();
}
var delayedResultClear;
function InitializePage()
{//called at end of page to initialize things
	delayedResultClear = setInterval(function(){ClearResultMessage()},10*1000);
	
	//show / hide messges button if any messages are on screen 
	if(document.getElementById("debugMessage")!=null || document.getElementById("errorMessage")!=null || document.getElementById("resultMessage")!=null)
		document.getElementById("showMessagesButton").className = "";
	else
		document.getElementById("showMessagesButton").className = "hidden";
		
}
function ClearResultMessage()
{
	SwapStyleClass("resultMessage","hidden");
	window.clearInterval(delayedResultClear)
}
function CopyBadgeToClipboard(text)
{
	var date = new Date();
	
	var am = "AM";
	var hour = date.getHours();
	if(hour==0) hour = 12;
	else if(hour>12) 
	{
		hour-=12;
		am = "PM";
	}
	
	var header = PadLeft(hour)+":"+PadLeft(date.getMinutes())+am;
	
	PromptToCopyToClipboard(header+text);
}
var messagesVisible=false;
function ToggleMessgeVisibility()
{
	ClearResultMessage();

	messagesVisible = !messagesVisible;

	if(messagesVisible)
	{
		document.getElementById("showMessagesButton").innerHTML = "Hide Messages";
		
		if(document.getElementById("debugMessage")!=null)
			document.getElementById("debugMessage").className = "debugMessage";
		if(document.getElementById("errorMessage")!=null)
			document.getElementById("errorMessage").className = "errorMessage";
		if(document.getElementById("resultMessage")!=null)
			document.getElementById("resultMessage").className = "resultMessage";
	}
	else
	{
		document.getElementById("showMessagesButton").innerHTML = "Show Messages";
		
		if(document.getElementById("debugMessage")!=null)
			document.getElementById("debugMessage").className = "hidden";
		if(document.getElementById("errorMessage")!=null)
			document.getElementById("errorMessage").className = "hidden";
		if(document.getElementById("resultMessage")!=null)
			document.getElementById("resultMessage").className = "hidden";
	}
}
var showAllPorts = false;//opposite of default condition
function InitializeShowAllPortsButton()
{
	var showAllPortsCookie = GetCookie('dcim_showAllPorts');
	//set it to the opposite then toggle to what I want
	showAllPorts = !(showAllPortsCookie=='true');
	ToggleShowAllPorts();
}
function ToggleShowAllPorts() {
	showAllPorts = !showAllPorts;
	if(showAllPorts)
	{
		document.cookie ='dcim_showAllPorts=true;';
		
		document.getElementById('showAllPortsButton').innerHTML = 'Show Active';
		document.getElementById('allPortsTable').className = '';
		document.getElementById('activePortsTable').className = 'hidden';
	}
	else
	{
		document.cookie = 'dcim_showAllPorts=; expires=Thu, 01 Jan 1970 00:00:00 GMT';
		
		document.getElementById('showAllPortsButton').innerHTML = 'Show All';
		document.getElementById('allPortsTable').className = 'hidden';
		document.getElementById('activePortsTable').className = '';
	}
}
function CreateHelpPopup(popupID)
{
	document.getElementById(popupID).style.display='block';
	document.getElementById('outOfFocusOverlay').style.display='block';
	
	document.getElementById('outOfFocusOverlay').style.webkitAnimationName = 'fadeIn';
	document.getElementById('outOfFocusOverlay').style.animationName = 'fadeIn';
	document.getElementById(popupID).style.webkitAnimationName = 'zoomIn';
	document.getElementById(popupID).style.animationName = 'zoomIn';
}
function ClearHelpPopup(popupID)
{
	document.getElementById('outOfFocusOverlay').style.webkitAnimationName = 'fadeOut';
	document.getElementById('outOfFocusOverlay').style.animationName = 'fadeOut';
	document.getElementById(popupID).style.webkitAnimationName = 'zoomOut';
	document.getElementById(popupID).style.animationName = 'zoomOut';
	
	//wait till animation finishes then change display to 'none' restoring user control - this should match the animation durration in CSS
	window.setTimeout(function() {ClearHelpPopup_RestoreFocus(popupID);},400);
}
function ClearHelpPopup_RestoreFocus(popupID)
{
	document.getElementById(popupID).style.display='none';
	document.getElementById('outOfFocusOverlay').style.display='none';
}