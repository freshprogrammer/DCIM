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
		try
		{
			document.getElementById('allPortsTable').className = '';
			document.getElementById('activePortsTable').className = 'hidden';
		}catch(error){}
	}
	else
	{
		document.cookie = 'dcim_showAllPorts=; expires=Thu, 01 Jan 1970 00:00:00 GMT';
		
		document.getElementById('showAllPortsButton').innerHTML = 'Show All';
		try
		{
			document.getElementById('allPortsTable').className = 'hidden';
			document.getElementById('activePortsTable').className = '';
		}catch(error){}
	}
}
var activePopupIDs = [];
function CreatePopup(popupID)
{
	var popupAnimation = "zoomIn";//zoomin for first popop and fade between subsequent popups
	if(activePopupIDs.length>0)
	{
		popupAnimation = "fadeIn";
		//fade out existing popup
		var prevPopupID = activePopupIDs[activePopupIDs.length - 1];
		document.getElementById(prevPopupID).style.webkitAnimationName = 'fadeOut';
		document.getElementById(prevPopupID).style.animationName = 'fadeOut';
	}
	activePopupIDs.push(popupID);;
	document.getElementById(popupID).style.display='block';
	document.getElementById('outOfFocusOverlay').style.display='block';
	
	document.getElementById('outOfFocusOverlay').style.webkitAnimationName = 'fadeIn';
	document.getElementById('outOfFocusOverlay').style.animationName = 'fadeIn';
	document.getElementById(popupID).style.webkitAnimationName = popupAnimation;
	document.getElementById(popupID).style.animationName = popupAnimation;
}
function ClearActivePopup()
{
	var popupClearAnimation = "zoomOut"
	if(activePopupIDs.length>1)
	{
		popupClearAnimation = "fadeOut";
	}
	var popupID = activePopupIDs[activePopupIDs.length - 1];
	document.getElementById(popupID).style.webkitAnimationName = popupClearAnimation;
	document.getElementById(popupID).style.animationName = popupClearAnimation;
	if(activePopupIDs.length-1==0)//last popup
	{
		document.getElementById('outOfFocusOverlay').style.webkitAnimationName = 'fadeOut';
		document.getElementById('outOfFocusOverlay').style.animationName = 'fadeOut';
		//wait till animation finishes then change display to 'none' restoring user control - this should match the animation durration in CSS
	}
	else//return to prev
	{
		var prevPopupID = activePopupIDs[activePopupIDs.length - 2];
		document.getElementById(prevPopupID).style.webkitAnimationName = 'fadeIn';
		document.getElementById(prevPopupID).style.animationName = 'fadeIn';
	}
	window.setTimeout(function() {ClearActivePopup_RestoreFocus();},400);
}
function ClearActivePopup_RestoreFocus()
{
	var popupID = activePopupIDs.pop();
	document.getElementById(popupID).style.display='none';
	if(activePopupIDs.length==0)//last popup
	{
		document.getElementById('outOfFocusOverlay').style.display='none';
	}
}
function GotoAuditsFragment()
{
	let hash = window.location.hash.substr(1);
	if(hash.length>0)
	{
		let displayAuditFunction = hash+"_table_toggle()";
		eval(displayAuditFunction);
		Sleep(100,GotoActiveFragment);
	}
}
function GotoActiveFragment()
{//goto after page is loaded
	location.href = location.href;
}