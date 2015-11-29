# ShinyDeploy

_ShinyDeploy_ is a deployment tool written in PHP and JavaScript. It's main goal is to provide an easy way to deploy
files from your repositories to your servers.

**Attention:** Even though this application is usable it is still in an early beta version and has not be tested by
a wide range of users. You should **always have a backup** of your data.

![ShinyDeploy Screenshot](https://nekudo.com/images/github/shiny_deploy_screen01.jpg)

## Features

* Graphical user interface

  All action from adding servers and repositories to deployments can be managed from simple and easy to use GUI.

* Secure data storage

  Sensitive data like usernames and passwords are encrypted before storing them in database. Decryption is only
  possible with a password you need to enter during login.

* List changed files before deploy

  Before deploying to a target server you can list all files that have been changed since last deploy. Using a
  diff-view you can even review changes before uploading.

* Execute tasks before/after deploy

  It is possible to define simple ssh commands to be executed before/after each deployment. This feature may be
  useful to re-start applications, build css/js or do whatever is necessary to deploy your application.

* Open source

  The whole project is open-source and MIT license. This way you can host your own instance in your local network
  and don't have to worry about giving sensitive information away. And of course you can modify the application
  in any way you like.

## Installation

### Requirements

The following packages and php-extensions are requried to run this application.

[ZeroMQ](http://zeromq.org/bindings:php)

```sudo apt-get install libzmq3 libzmq3-dev```

```sudo pecl install zmq-beta```

[Gearman](http://gearman.org/download/#php)

```sudo apt-get install gearman-job-server php5-gearman```


PHP Extensions

```sudo apt-get install php5-intl```

```sudo apt-get install libssh2-php```


### Installation procedure

* Clone repository.

  ```git clone https://github.com/nekudo/shiny_deploy.git```

* Install dependencies

  ```composer install```

* Create MySQL tables using db_structure.sql in project root.

* Rename and adjust config files

  ```mv src/ShinyDeploy/config.sample.php src/ShinyDeploy/config.php```

  ```mv www/js/app/app.config.js.sample www/js/app.config.js```

### Start application

To use this application you need to start up the websocket server and worker processes. This can be done by executing
the following command:

```php cli/app.php start```

### Optional Steps

In case you want to adjust CSS or JS you can use gulp to create the minified files. Install gulp using the following
command: (node.js is required!)

```npm install```

To build CSS/JS files run:

```gulp build```

To start a watcher automatically rebuilding files on modifications run:

```gulp watch```

 Happy hacking...

## ToDos and Known Bugs

* Add SSH key support.
* Trigger deployments using webhooks and API keys.
* Check switch from Ratchet to another websocket server. (Ratchet development seems to be stuck...)
* Check switch from Gearman to RabbitMQ. (Gearman development seems to be stuck...)

## License

MIT