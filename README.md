# JIRA Worklog
Many people are searching for an alternative for the TimeSheet Plugin. 
This little tool is using the JIRA API to get the worklog data for the current sprint.

# Usage
## Install dependencies with composer.
```shell
php composer.phar install
```  
## Add your data to the config files.
Copy `config/jira.local.php.dist` to `config/jira.local.php` and add your details
```php
<?php
return [
    'auth' => [
        'username' => 'someuser@example.com',
        'password' => 'supersecretpassword',
    ],
    'jiraUrl' => 'https://yourjiracloud.atlassian.net/',
    'jiraFilterId' => '1234',
];
```
## Run JIRA Worklog
```bash
php index.php
```
