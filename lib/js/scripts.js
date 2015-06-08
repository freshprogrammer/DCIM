//main scripts included with every page

function FocusMainSearch()
{
	document.forms['MainSearch'].elements['search'].focus();
}
var delayedResultClear = -1;
function InitializePage()
{//called at end of page to initialize things
	
}
function UpdatePageLoadMessages(debugMessages, errorMessages, resultMessages)
{//NOTE: using style to hide them initialy so that the empty ones are never flipped on with the toggle
	var someMessage = false;
	if(debugMessages.length>0)
	{
		someMessage = true;
		document.getElementById("debugMessage").innerHTML = debugMessages;
		document.getElementById("debugMessage").style.display = "";
	}
	if(errorMessages.length>0)
	{
		someMessage = true;
		document.getElementById("errorMessage").innerHTML = errorMessages;
		document.getElementById("errorMessage").style.display = "";
	}
	if(resultMessages.length>0)
	{
		someMessage = true;
		document.getElementById("resultMessage").innerHTML = resultMessages;
		document.getElementById("resultMessage").style.display = "";

		window.clearInterval(delayedResultClear)
		delayedResultClear = setInterval(function(){ClearResultMessage()},10*1000);//wait 10 seconds then clear results messages
	}
	
	//show show/hide messages button if any messages are on screen
	if(someMessage)
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
		
		document.getElementById("debugMessage").className = "debugMessage";
		document.getElementById("errorMessage").className = "errorMessage";
		document.getElementById("resultMessage").className = "resultMessage";
	}
	else
	{
		document.getElementById("showMessagesButton").innerHTML = "Show Messages";
		
		document.getElementById("debugMessage").className = "hidden";
		document.getElementById("errorMessage").className = "hidden";
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
var activePopupID = "";
function CreatePopup(popupID)
{
	activePopupID = popupID;
	document.getElementById(activePopupID).style.display='block';
	document.getElementById('outOfFocusOverlay').style.display='block';
	
	document.getElementById('outOfFocusOverlay').style.webkitAnimationName = 'fadeIn';
	document.getElementById('outOfFocusOverlay').style.animationName = 'fadeIn';
	document.getElementById(activePopupID).style.webkitAnimationName = 'zoomIn';
	document.getElementById(activePopupID).style.animationName = 'zoomIn';
}
function ClearActivePopup()
{
	document.getElementById('outOfFocusOverlay').style.webkitAnimationName = 'fadeOut';
	document.getElementById('outOfFocusOverlay').style.animationName = 'fadeOut';
	document.getElementById(activePopupID).style.webkitAnimationName = 'zoomOut';
	document.getElementById(activePopupID).style.animationName = 'zoomOut';
	
	//wait till animation finishes then change display to 'none' restoring user control - this should match the animation durration in CSS
	window.setTimeout(function() {ClearActivePopup_RestoreFocus(activePopupID);},400);
}
function ClearActivePopup_RestoreFocus()
{
	document.getElementById(activePopupID).style.display='none';
	document.getElementById('outOfFocusOverlay').style.display='none';
	activePopupID = "";
}