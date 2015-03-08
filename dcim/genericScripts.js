//generic functions
function GetCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
        var c = ca[i].trim();
        if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
    }
    return "";
}
var activeAJAXCalls = 0;
function AJAXCall(page, data, destination)
{
    var xmlhttp;
    if (window.XMLHttpRequest)
    {// code for IE7+, Firefox, Chrome, Opera, Safari
    	xmlhttp=new XMLHttpRequest();
	}
    else
    {// code for IE6, IE5
    	xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange=function()
    {
        if (xmlhttp.readyState==4 && xmlhttp.status==200)
        {
        	document.getElementById(destination).innerHTML=xmlhttp.responseText;
            activeAJAXCalls--;
        }
    }
    xmlhttp.open("GET",page+"?"+data,true);
    activeAJAXCalls++;
    xmlhttp.send();
}
function Sleep(millis, callback) {
    setTimeout(function()
            { callback(); }
    , millis);
}
function replaceAll(string, find, replace) 
{
  return string.split(find).join(replace);
}
function ValidMACAddress(input)
{
	var macFormat = /^([0-9a-f]{2}[:]){5}([0-9a-f]{2})$/;
	return macFormat.test(input);
}
function ValidIPAddress(input)
{
	var ipFormat = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;  
	return ipFormat.test(input);
}
//var subnetFormat = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\/(30|2[0-9]|1[6-9])$/;  
//only up to 24
var subnetFormat = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\/(30|2[4-9])$/;  
function GetValidSubnet(input)
{
	//192.168.0.12/27s
	if(subnetFormat.test(input))
	{
		var parts = subnetFormat.exec(input);
		if(parseInt(parts[5])>=24)
		{
			
			var ipCount = GetIPCountInSubnet(input);
			
			var inputFirstIP = parseInt(parts[4]);
			//count down in chunks
			var firstIP = 0;
			var lastIP = ipCount-1;
			while(firstIP<255)
			{
				if(inputFirstIP >= firstIP && inputFirstIP <= lastIP)
				{
					break;//in this block 
				}
				else
				{
					firstIP +=ipCount;
					lastIP +=ipCount;
				}
			}
			if(firstIP>255)//fell past 255
				return null
			else
			{
				var result = parts[1];
				result += "."+parts[2];
				result += "."+parts[3];
				result += "."+firstIP;
				result += "/"+parseInt(parts[5]);
				return result;
			}
		}
		else
			return null;
	}
	else
		return null;
}
function GetMaskFromSubnet(subnet)
{
	var parts = subnetFormat.exec(subnet);
	
	switch(parseInt(parts[5]))
	{
	case 30: return "255.255.255.252";
	case 29: return "255.255.255.248";	
	case 28: return "255.255.255.240";	
	case 27: return "255.255.255.224";	
	case 26: return "255.255.255.192";	
	case 25: return "255.255.255.128";	
	case 24: return "255.255.255.0";	
	//case 23: return "255.255.254.0";	
	//case 22: return "255.255.252.0";	
	//case 21: return "255.255.248.0";	
	//case 20: return "255.255.240.0";	
	//case 19: return "255.255.224.0";	
	//case 18: return "255.255.192.0";	
	//case 17: return "255.255.128.0";	
	//case 16: return "255.255.0.0";
	default: return "";
	}
}
function GetGatewayFromSubnet(subnet)
{
	var result = "";
	var parts = subnetFormat.exec(subnet);
	if(parseInt(parts[5])>=24)
	{
		result = parts[1];
		result += "."+parts[2];
		result += "."+parts[3];
		result += "."+(parseInt(parts[4])+1);
	}
	return result;
}
function GetFirstFromSubnet(subnet)
{
	var result = "";
	var parts = subnetFormat.exec(subnet);
	if(parseInt(parts[5])>=24)
	{
		result = parts[1];
		result += "."+parts[2];
		result += "."+parts[3];
		result += "."+(parseInt(parts[4])+4);
	}
	return result;
}
function GetLastFromSubnet(subnet)
{
	var result = "";
	var parts = subnetFormat.exec(subnet);
	if(parseInt(parts[5])>=24)
	{	
		var numberIPsInSubnet = GetIPCountInSubnet(subnet);
		numberIPsInSubnet-=1;//reserved
		numberIPsInSubnet-=1;//last fence post - so with 1 IP first +count = last (same)
		
		result = parts[1];
		result += "."+parts[2];
		result += "."+parts[3];
		result += "."+(parseInt(parts[4])+numberIPsInSubnet);
	}
	return result;
}
function GetIPCountInSubnet(subnet)
{
	var parts;
	if((parts = subnetFormat.exec(subnet))==null)
		return null;
	return numberIPsInSubnet = Math.pow(2,32-parseInt(parts[5]));
}
function PromptToCopyToClipboard(text) 
{
	window.prompt("Copy to clipboard: Ctrl+C, Enter", text);
}
function PadLeft(num)
{
	if (num<10)
	{
		num="0" + num;
	}
	return num;
}
function SelectIDRange(containerid) 
{
    if (document.selection) 
    {
        var range = document.body.createTextRange();
        range.moveToElementText(document.getElementById(containerid));
        range.select();
    } 
    else if (window.getSelection) 
    {
        var range = document.createRange();
        range.selectNode(document.getElementById(containerid));
        window.getSelection().addRange(range);
    }
}
function SelectItemByValue(elmnt, value)
{
  for(var i=0; i < elmnt.options.length; i++)
  {
    if(elmnt.options[i].value == value) {
      elmnt.selectedIndex = i;
      break;
    }
  }
}
function SwapStyleClass(oldSwapClass, newSwapClass)
{
	var elements = document.getElementsByClassName(oldSwapClass);
	//var lastSize = elements.length;
	while(elements.length > 0)
	{
    	var curClass = elements[0].className;
    	var newClass = curClass.replace(oldSwapClass, newSwapClass);
    	elements[0].className = newClass;
    	//if(lastSize = elements.length)
    		//break;//stuck in loop
	}
}
function FocusAndSelectAll(id)
{
    var e = document.getElementById(id);
	e.focus();
	try{
		e.select();
		e.setSelectionRange(0, e.value.length)
	}catch (err){}
}