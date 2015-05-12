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
			return $result;
		}
	}
?>
