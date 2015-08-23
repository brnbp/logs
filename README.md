# logs_notifications

## How to use:

* this api needs the authentication key so you can use, just set the header 'auth' with the appropriate value

NOTE: for this examples, i will assume that the main folder is on:
/var/www/api.com/

### to send data:
> via POST: 
```
api.com/Logs/notification
```
with json containing the following structure exemplified:
```javascript
{
  "identifier": "34234",
  "log_name": "amazon_stock_update",
  "level": "critical",
  "messages": "Procuct sku: 34234 can not be updated",
  "site": "amazon-uk"
}
```
you must send the exact data, or this will not work.
level must be one of this: 'critical', 'warning', 'info'. anything else has to be changed on code and db scheme

### to get data:
> via GET 
you have many options and sub-options, let's see..
##### get by site content
api.com/Logs/notification/site/site_here
```
ex:
api.com/Logs/notification/site/amazon-uk
```
##### get by log_name content
api.com/Logs/notification/logName/log_name_here
```
ex:
api.com/Logs/notification/logName/cnova_stock_update
```
##### get by level content
api.com/Logs/notification/level/level_here
```
ex:
api.com/Logs/notification/level/critical
```
>>
#### get with more options
* you can use more options, besides the main get, it's possible to add others filters so you can have more specific data
>>
###### getting all amazon-uk logs with critical level
```
api.com/Logs/notification/site/amazon-uk?level=critical
```
or
###### getting only 3 amazon-uk logs with critical level 
```
api.com/Logs/notification/site/amazon-uk?level=critical&limit=3
```
and other way to get exactly the same infos: 
###### getting all amazon-uk logs with critical level
```
api.com/Logs/notification/level/critical?site=amazon-uk
```
or
###### getting only 3 amazon-uk logs with critical level 
```
api.com/Logs/notification/level/critical?site=amazon-uk&limit=3
```
