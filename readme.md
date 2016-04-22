# DCIM - Data Center Infrastructure Management
### v1.1.5
Web interface for tracking and maintaining data on customers, devices, networking and infrastructure. Supports multiple users with multiple access levels and user change tracking with data rollback capabilities. Demo environemt can be seen [here](http://freshprogramming.com/dcim/ "Demo Environment") and test credentials can be seen [here](documentation/creds.md "Credentials").


## [Screenshots](/documentation/screenshots/)
![Data center map](/documentation/screenshots/DCMap.jpg "Data center map")
![Customer page](/documentation/screenshots/CustomerPage.jpg "Customer page")
![Switch view](/documentation/screenshots/SwitchView.jpg "Switch view")


## Database Details
[DB Documentation](/documentation/database_structure.md)
![DCIM DC Visual Diagram](/documentation/dcim_db_visual.jpg)


## Cookies
- dcim_user - username of current logged in user, blank (un set) if not logged in.
- dcim_password - md5 of current user's password, blank (un set) if not logged in.
- dcim_editMode - if 'true' edit mode is enabled - no expiration.
- dcim_showAllPorts - if 'true' all device ports will be shown ELSE only active ports will be shown.
