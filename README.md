# ShinyDeploy

_ShinyDeploy_ is a deployment tool written in PHP and JavaScript. It's main goal is to provide an easy way to deploy
files from your GIT repositories to your servers.

![ShinyDeploy Screenshot](https://nekudo.com/images/github/shiny_deploy_screen01.jpg)

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
GitHub or Bitbucket as soon as you push changes to your repositories.

##### Open source

The whole project is open-source and MIT license. This way you can host your own instance in your local network
and don't have to worry about giving sensitive information away. And of course you can modify the application
in any way you like.

## Installation

### Requirements

The following packages and php-extensions are required to run this application.

* [ZeroMQ](http://zeromq.org/bindings:php)
* [Gearman](http://gearman.org/download/#php)
* [SSH2](http://php.net/manual/en/book.ssh2.php)

### Installation procedure

* Clone repository.

  ```git clone https://github.com/nekudo/shiny_deploy.git```

* Install dependencies

  ```composer install```

* Create MySQL tables using db_structure.sql in project root.

* Rename and adjust config files

  ```mv src/ShinyDeploy/config.sample.php src/ShinyDeploy/config.php```

  ```mv www/js/config.js.sample www/js/config.js```

### Start application

To use this application you need to start up the websocket server and worker processes. This can be done by executing
the following command:

```php cli/app.php start```

### Optional Steps

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