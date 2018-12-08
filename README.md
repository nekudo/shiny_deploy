# ShinyDeploy

_ShinyDeploy_ is a deployment tool written in PHP and JavaScript. It's main goal is to provide an easy way to deploy
files from your GIT repositories to your servers.

![ShinyDeploy Screenshot](https://static.samtleben.me/github/shinydeploy01.png)

## Features

##### Graphical user interface

All action from adding servers and repositories to deployments can be managed from simple and easy to use GUI.

##### Secure data storage

Sensitive data like usernames and passwords are encrypted before storing them in database. Decryption is only
possible with a password you need to enter during login.

##### List changed files before deploy

Before deploying to a target server you can list all files that have been changed since last deploy. Using a
diff-view you can even review changes before uploading.

##### Execute tasks before/after deploy

It is possible to define simple ssh commands to be executed before/after each deployment. This feature may be
useful to re-start applications, build css/js or do whatever is necessary to deploy your application.

##### Webhook support

Once a Deployment is created you can generate an API-URL. Using this URL you can trigger deployments directly from
GitHub, Bitbucket or Gitea as soon as you push changes to your repositories.

##### Open source

The whole project is open-source and MIT license. This way you can host your own instance in your local network
and don't have to worry about giving sensitive information away. And of course you can modify the application
in any way you like.

## Installation

### Requirements

The following software is required to run this application.

* Webserver (Nginx, Apache, ...)
* MySQL Server
* PHP >= 7.1
  * [ZeroMQ Extension](http://zeromq.org/bindings:php)
  * [Gearman Extension](http://gearman.org/download/#php)
  * Curl extension
  * Mysqli extension

### Installation procedure

* Install project using composer.

  ```composer create-project nekudo/shiny_deploy myshinydeploy```

* Create MySQL tables using db_structure.sql in project root.

* Adjust config files in the following folders:

  ```config/config.php```

  ```www/js/config.js```

### Start application

To use this application you need to start up the websocket server and worker processes. This can be done by executing
the following command:

```php cli/app.php start```

## Updates

#### Updating to 1.3 or higher

If you want to update to version 1.3.0 or higher you have to convert the encrypted database when you come from a
version that is 1.1 or lower. (See the update guide below.)

Please note that is process is only possible running PHP 7.1 with the mcrypt extension installed. Once your data
is converted you can update to the latest PHP version and the mcrypt extension is not required any longer. 

#### Updating to 1.2.*

Due to the fact that the mcrypt extension was removed in PHP 7.2 the cryptography routines within this application
needed to be updated. So after updating to version 1.2 (or later) you will need to execute the update script by running
the following command:

`php cli/scripts/update.php`

The updater will ask for your system password and than re-encrypt all data to the new standard. This step has to be done
running PHP 7.1.* with the mcrypt extension installed.

Unfortunately you will have to **generate new api/webhook URLs after this step**. (The old URLs won't work any longer
after the update.) 

## Developer Hints

In case you want to adjust CSS or JS you can use the robo.phar to rebuild the assets.

To build CSS/JS files run:

```php robo.phar assets```

To start a watcher automatically rebuilding files on modifications run:

```php robo.phar watch```

To list all available tasks run:

```php robo.phar list```

 Happy hacking...

## License

MIT