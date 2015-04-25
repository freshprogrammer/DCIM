## DCIM - Data Center Infrastructure Management

### Database Details
[DB Documentation](documentation/database_structure.md)
![DCIM DC Visual Diagram](/documentation/dcim_db_visual.jpg)

### Cookies
- dcim_user - username of current logged in user, blank (un set) if not logged in.
- dcim_password - md5 of current user's password, blank (un set) if not logged in.
- dcim_editMode - if 'true' edit mode is enabled - no expiration.
- dcim_showAllPorts - if 'true' all device ports will be shown ELSE only active ports will be shown.