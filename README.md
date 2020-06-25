# headless-cms
Headless CMS simplified

The headless CMS allowed basic CRUD operations via REST API requests. Based on the symfony 5.2 with Doctrine2 ORM. 
All communications with the headless-cms are in JSON format.

**Installation**:

To setup it necessary to set the proper mysql credentials in the .env file and then simply run composer json inside project directory.

**Test demo data**

To fill demo data need to run php bin/console doctrine:fixtures:load command inside project directory.

**Features**

The CMS allowed to get list of articles with specific ordering by such fields as 'title', 'body', 'created_at', 'updated_at'. The syntax is following:
'?sortField=fieldName&sortOrder=order'. 

Also is supported limiting number of returning results by adding '?limit=<value>' parameter in the URL GET request, where <value> is integer value.

Just to note these parameters are optional and can be omitted. If they aren't provided will be used the default values:

sortField = 'created_at',
sortOrder = 'DESC',
limit = 50
offset = 0

_Example:_
'?sortField=title&sortOrder=DESC&limit=25&offset=5'.

**Testing**
To run tests on CRUD operations simply run ./bin/phpunit in the project directory.
