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
		
		public static function CreateNavigationQuickLinks()
		{
			$result  = "<a class='navLinks' href='?roomid=2'>CA1</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?roomid=3'>CA2</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?roomid=4'>CA3</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?roomid=5'>CA4</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?roomid=6'>CA5</a>&nbsp;\n";
			$result .= "&nbsp;&nbsp;\n";
			$result .= "<div class='navLinks'>ROW:</div>\n";
			$result .= "<a class='navLinks' href='?row=01'>1</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?row=02'>2</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?row=03'>3</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?row=04'>4</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?row=05'>5</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?row=06'>6</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?row=07'>7</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?row=08'>8</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?row=09'>9</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?row=10'>10</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?row=11'>11</a>&nbsp;\n";
			$result .= "<a class='navLinks' href='?row=12'>12</a>&nbsp;\n";
			return $result;
		}
	}
?>
