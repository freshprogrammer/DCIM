## DCIM Database v3
![DCIM DC Visual Diagram](/documentation/dcim_db_visual.jpg)

#### Describe dcim_badge
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|badgeid|int(8)|NO|PRI|*NULL*|auto_increment|
|hno|int(8)|NO|MUL|*NULL*||
|name|varchar(128)|NO||*NULL*||
|badgeno|varchar(8)|NO|MUL|*NULL*||
|status|varchar(1)|NO||*NULL*||
|issue|date|NO||*NULL*||
|hand|date|NO||*NULL*||
|returned|date|NO||*NULL*||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcim_config
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|configid|int(8)|NO|PRI|*NULL*|auto_increment|
|appname|varchar(200)|NO||DCIM Demo||
|pagetitle|varchar(200)|NO||DCIM||
|versionnote|varchar(200)|NO||note||
|cookiedurration|int(3)|NO||36||
|cookiedurrationipad|int(3)|NO||2||
|badgesEnabled|varchar(1)|NO||T||
|subnetsEnabled|varchar(1)|NO||T||
|qaenabled|varchar(1)|NO||T||
|demoenvironment|varchar(1)|NO||F||
|dbversion|varchar(10)|NO||1||

#### Describe dcim_customer
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|hno|int(8)|NO|PRI|*NULL*|auto_increment|
|cno|int(8)|NO|MUL|*NULL*||
|name|varchar(128)|NO||*NULL*||
|note|text|NO||*NULL*||
|status|varchar(1)|NO||*NULL*||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcim_device
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|deviceid|int(8)|NO|PRI|*NULL*|auto_increment|
|hno|int(8)|NO|MUL|*NULL*||
|locationid|int(8)|NO|MUL|*NULL*||
|name|varchar(64)|NO|MUL|*NULL*||
|altname|varchar(64)|NO|MUL|*NULL*||
|member|int(2)|NO||0||
|note|text|NO||*NULL*||
|unit|int(3)|NO||*NULL*||
|type|varchar(1)|NO||*NULL*||
|size|varchar(8)|NO||*NULL*||
|status|varchar(1)|NO||*NULL*||
|asset|varchar(8)|NO||*NULL*||
|serial|varchar(20)|NO||*NULL*||
|model|varchar(30)|NO||*NULL*||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcim_deviceport
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|deviceportid|int(8)|NO|PRI|*NULL*|auto_increment|
|deviceid|int(8)|NO|MUL|*NULL*||
|pic|int(2)|NO||0||
|port|int(2)|NO||*NULL*||
|type|varchar(1)|NO||E||
|mac|varchar(20)|NO||*NULL*||
|speed|varchar(8)|NO||*NULL*||
|note|text|NO||*NULL*||
|status|varchar(1)|NO||D||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcim_location
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|locationid|int(8)|NO|PRI|*NULL*|auto_increment|
|roomid|int(8)|NO|MUL|*NULL*||
|name|varchar(50)|NO|MUL|*NULL*||
|altname|varchar(50)|NO||||
|type|char(1)|NO||||
|units|int(3)|NO||1||
|xpos|decimal(6,2)|NO||0.00||
|ypos|decimal(6,2)|NO||0.00||
|width|decimal(6,2)|NO||0.00||
|depth|decimal(6,2)|NO||0.00||
|orientation|varchar(1)|NO||N||
|keyno|varchar(20)|NO||*NULL*||
|allocation|varchar(1)|NO||E||
|order|varchar(1)|NO||S||
|visible|char(1)|NO||||
|note|text|NO||*NULL*||
|edituser|int(8)|NO||0||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcim_portconnection
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|portconnectionid|int(8)|NO|PRI|*NULL*|auto_increment|
|childportid|int(8)|NO|MUL|0||
|parentportid|int(8)|NO|MUL|0||
|patches|varchar(50)|NO||*NULL*||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||0000-00-00 00:00:00||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcim_portvlan
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|portvlanid|int(8)|NO|PRI|*NULL*|auto_increment|
|deviceportid|int(8)|NO|MUL|0||
|vlan|int(8)|NO|MUL|0||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||0000-00-00 00:00:00||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcim_powercircuit
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|powercircuitid|smallint(8)|NO|PRI|*NULL*|auto_increment|
|powerpanelid|int(8)|NO|MUL|*NULL*||
|circuit|tinyint(3)|NO||*NULL*||
|volts|smallint(3)|NO||120||
|amps|tinyint(3)|NO||20||
|status|varchar(1)|NO||*NULL*||
|load|decimal(4,2)|NO||0.00||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcim_powercircuitloc
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|powercircuitlocid|int(8)|NO|PRI|*NULL*|auto_increment|
|powercircuitid|int(8)|NO|MUL|0||
|locationid|int(8)|NO|MUL|0||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcim_powerpanel
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|powerpanelid|int(8)|NO|PRI|*NULL*|auto_increment|
|powerupsid|int(8)|NO|MUL|*NULL*||
|roomid|int(8)|NO|MUL|*NULL*||
|name|varchar(50)|NO||*NULL*||
|amps|int(4)|NO||*NULL*||
|circuits|int(3)|NO||0||
|xpos|decimal(6,2)|NO||0.00||
|ypos|decimal(6,2)|NO||0.00||
|width|decimal(6,2)|NO||0.00||
|depth|decimal(6,2)|NO||0.00||
|orientation|varchar(1)|NO||N||
|note|text|NO||*NULL*||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcim_powerups
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|powerupsid|int(8)|NO|PRI|*NULL*|auto_increment|
|siteid|int(8)|NO|MUL|*NULL*||
|name|varchar(32)|NO||*NULL*||
|volts|int(5)|NO||*NULL*||
|amps|int(5)|NO||*NULL*||
|note|text|NO||*NULL*||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcim_room
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|roomid|int(8)|NO|PRI|*NULL*|auto_increment|
|siteid|int(8)|NO|MUL|*NULL*||
|name|varchar(50)|NO||*NULL*||
|fullname|varchar(128)|NO||*NULL*||
|custaccess|varchar(1)|NO||T||
|xpos|decimal(6,2)|NO||0.00||
|ypos|decimal(6,2)|NO||0.00||
|width|decimal(6,2)|NO||0.00||
|depth|decimal(6,2)|NO||0.00||
|orientation|varchar(1)|NO||N||
|layer|tinyint(1)|NO||0||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcim_site
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|siteid|int(8)|NO|PRI|*NULL*|auto_increment|
|name|varchar(64)|NO||*NULL*||
|fullname|varchar(128)|NO||*NULL*||
|width|decimal(6,2)|NO||0.00||
|depth|decimal(6,2)|NO||0.00||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcim_user
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|userid|int(8)|NO|PRI|*NULL*|auto_increment|
|siteid|int(8)|NO|MUL|*NULL*||
|username|varchar(64)|NO|MUL|*NULL*||
|name|varchar(64)|NO||*NULL*||
|pass|varchar(60)|NO||*NULL*||
|email|varchar(128)|NO||*NULL*||
|initials|varchar(4)|NO||*NULL*||
|note|text|NO||*NULL*||
|permission|varchar(1)|NO||*NULL*||
|passwordreset|int(8)|NO||*NULL*||
|lastactivity|datetime|NO||*NULL*||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||

#### Describe dcim_vlan
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|vlanid|int(8)|NO|PRI|*NULL*|auto_increment|
|vlan|int(8)|NO|MUL|*NULL*||
|subnet|varchar(18)|NO||*NULL*||
|mask|varchar(15)|NO||*NULL*||
|first|varchar(15)|NO||*NULL*||
|last|varchar(15)|NO||*NULL*||
|gateway|varchar(15)|NO||*NULL*||
|note|text|NO||*NULL*||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcimlog_badge
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|badgelogid|int(8)|NO|PRI|*NULL*|auto_increment|
|logtype|char(1)|NO||I||
|badgeid|int(8)|NO|MUL|*NULL*||
|hno|int(8)|NO|MUL|*NULL*||
|name|varchar(128)|NO||*NULL*||
|badgeno|varchar(8)|NO|MUL|*NULL*||
|status|varchar(1)|NO||*NULL*||
|issue|date|NO||*NULL*||
|hand|date|NO||*NULL*||
|returned|date|NO||*NULL*||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcimlog_customer
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|customerlogid|int(8)|NO|PRI|*NULL*|auto_increment|
|logtype|char(1)|NO||I||
|hno|int(8)|NO|MUL|*NULL*||
|cno|int(8)|NO|MUL|*NULL*||
|name|varchar(128)|NO||*NULL*||
|note|text|NO||*NULL*||
|status|varchar(1)|NO||*NULL*||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcimlog_device
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|devicelogid|int(8)|NO|PRI|*NULL*|auto_increment|
|logtype|char(1)|NO||I||
|deviceid|int(8)|NO|MUL|*NULL*||
|hno|int(8)|NO|MUL|*NULL*||
|locationid|int(8)|NO|MUL|*NULL*||
|name|varchar(64)|NO|MUL|*NULL*||
|altname|varchar(64)|NO|MUL|*NULL*||
|member|int(2)|NO||0||
|note|text|NO||*NULL*||
|unit|int(3)|NO||*NULL*||
|type|varchar(1)|NO||*NULL*||
|size|varchar(8)|NO||*NULL*||
|status|varchar(1)|NO||*NULL*||
|asset|varchar(8)|NO||*NULL*||
|serial|varchar(20)|NO||*NULL*||
|model|varchar(30)|NO||*NULL*||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcimlog_deviceport
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|deviceportlogid|int(8)|NO|PRI|*NULL*|auto_increment|
|logtype|char(1)|NO||I||
|deviceportid|int(8)|NO|MUL|*NULL*||
|deviceid|int(8)|NO|MUL|*NULL*||
|pic|int(2)|NO||0||
|port|int(2)|NO||*NULL*||
|type|varchar(1)|NO||E||
|mac|varchar(20)|NO||*NULL*||
|speed|varchar(8)|NO||*NULL*||
|note|text|NO||*NULL*||
|status|varchar(1)|NO||D||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcimlog_location
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|locationlogid|int(8)|NO|PRI|*NULL*|auto_increment|
|logtype|char(1)|NO||I||
|locationid|int(8)|NO|MUL|*NULL*||
|roomid|int(8)|NO|MUL|*NULL*||
|name|varchar(50)|NO|MUL|*NULL*||
|altname|varchar(50)|NO||||
|type|char(1)|NO||||
|units|int(3)|NO||1||
|xpos|decimal(6,2)|NO||0.00||
|ypos|decimal(6,2)|NO||0.00||
|width|decimal(6,2)|NO||0.00||
|depth|decimal(6,2)|NO||0.00||
|orientation|varchar(1)|NO||N||
|keyno|varchar(20)|NO||*NULL*||
|allocation|varchar(1)|NO||E||
|order|varchar(1)|NO||S||
|visible|char(1)|NO||||
|note|text|NO||*NULL*||
|edituser|int(8)|NO||0||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcimlog_portconnection
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|portconnectionlogid|int(8)|NO|PRI|*NULL*|auto_increment|
|logtype|char(1)|NO||I||
|portconnectionid|int(8)|NO|MUL|*NULL*||
|childportid|int(8)|NO|MUL|0||
|parentportid|int(8)|NO|MUL|0||
|patches|varchar(50)|NO||*NULL*||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||0000-00-00 00:00:00||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcimlog_portvlan
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|portvlanlogid|int(8)|NO|PRI|*NULL*|auto_increment|
|logtype|char(1)|NO||I||
|portvlanid|int(8)|NO|MUL|*NULL*||
|deviceportid|int(8)|NO|MUL|0||
|vlan|int(8)|NO|MUL|0||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||0000-00-00 00:00:00||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcimlog_powercircuit
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|powercircuitlogid|smallint(8)|NO|PRI|*NULL*|auto_increment|
|logtype|char(1)|NO||I||
|powercircuitid|smallint(8)|NO|MUL|*NULL*||
|powerpanelid|int(8)|NO|MUL|*NULL*||
|circuit|tinyint(3)|NO||*NULL*||
|volts|smallint(3)|NO||120||
|amps|tinyint(3)|NO||20||
|status|varchar(1)|NO||*NULL*||
|load|decimal(4,2)|NO||0.00||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcimlog_powercircuitloc
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|powercircuitloclogid|int(8)|NO|PRI|*NULL*|auto_increment|
|logtype|char(1)|NO||I||
|powercircuitlocid|int(8)|NO|MUL|*NULL*||
|powercircuitid|int(8)|NO|MUL|0||
|locationid|int(8)|NO|MUL|0||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcimlog_powerpanel
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|powerpanellogid|int(8)|NO|PRI|*NULL*|auto_increment|
|logtype|varchar(1)|NO||I||
|powerpanelid|int(8)|NO|MUL|*NULL*||
|powerupsid|int(8)|NO|MUL|*NULL*||
|roomid|int(8)|NO|MUL|*NULL*||
|name|varchar(50)|NO||*NULL*||
|amps|int(4)|NO||*NULL*||
|circuits|int(3)|NO||0||
|xpos|decimal(6,2)|NO||0.00||
|ypos|decimal(6,2)|NO||0.00||
|width|decimal(6,2)|NO||0.00||
|depth|decimal(6,2)|NO||0.00||
|orientation|varchar(1)|NO||N||
|note|text|NO||*NULL*||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcimlog_powerups
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|powerupslogid|int(8)|NO|PRI|*NULL*|auto_increment|
|logtype|varchar(1)|NO||I||
|powerupsid|int(8)|NO|MUL|*NULL*||
|siteid|int(8)|NO|MUL|*NULL*||
|name|varchar(32)|NO||*NULL*||
|volts|int(5)|NO||*NULL*||
|amps|int(5)|NO||*NULL*||
|note|text|NO||*NULL*||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcimlog_room
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|roomlogid|int(8)|NO|PRI|*NULL*|auto_increment|
|logtype|varchar(1)|NO||I||
|roomid|int(8)|NO|MUL|*NULL*||
|siteid|int(8)|NO|MUL|*NULL*||
|name|varchar(50)|NO||*NULL*||
|fullname|varchar(128)|NO||*NULL*||
|custaccess|varchar(1)|NO||T||
|xpos|decimal(6,2)|NO||0.00||
|ypos|decimal(6,2)|NO||0.00||
|width|decimal(6,2)|NO||0.00||
|depth|decimal(6,2)|NO||0.00||
|orientation|varchar(1)|NO||N||
|layer|tinyint(1)|NO||0||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcimlog_site
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|sitelogid|int(8)|NO|PRI|*NULL*|auto_increment|
|logtype|char(1)|NO||I||
|siteid|int(8)|NO|MUL|*NULL*||
|name|varchar(64)|NO||*NULL*||
|fullname|varchar(128)|NO||*NULL*||
|width|decimal(6,2)|NO||0.00||
|depth|decimal(6,2)|NO||0.00||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||

#### Describe dcimlog_vlan
|Field|Type|Null|Key|Default|Extra|
|---|---|---|---|---|---|
|vlanlogid|int(8)|NO|PRI|*NULL*|auto_increment|
|logtype|char(1)|NO||I||
|vlanid|int(8)|NO|MUL|*NULL*||
|vlan|int(8)|NO|MUL|*NULL*||
|subnet|varchar(18)|NO||*NULL*||
|mask|varchar(15)|NO||*NULL*||
|first|varchar(15)|NO||*NULL*||
|last|varchar(15)|NO||*NULL*||
|gateway|varchar(15)|NO||*NULL*||
|note|text|NO||*NULL*||
|edituser|int(8)|NO||*NULL*||
|editdate|timestamp|NO||CURRENT_TIMESTAMP||
|qauser|int(8)|NO|MUL|-1||
|qadate|datetime|NO||*NULL*||