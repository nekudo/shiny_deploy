# ShinyDeploy
## A shiny php deployment tool

This project is currently in development - it is not stable yet!

If you want to contribute here are some rough instructions to get you started:

### Requirements

[ZeroMQ](http://zeromq.org/bindings:php)

```sudo apt-get install libzmq3 libzmq3-dev```

```sudo pecl install zmq-beta```

[Gearman](http://gearman.org/download/#php)

```sudo apt-get install gearman-job-server php5-gearman```


PHP Extensions

```sudo apt-get install php5-intl```

```sudo apt-get install libssh2-php```


### Installation

* Clone repository.

  ```git clone https://github.com/nekudo/shiny_deploy.git```

* Install dependencies

  ```composer install```

  ```npm install```

* Create MySQL tables (db_structure.sql)
* Adjust config files
  * /src/ShinyDeploy/config.sample.php -> /src/ShinyDeploy/config.php
  * /www/js/app/app.config.js.sample -> /www/js/app.config.js
* Build css/js files

  ```gulp build```

* Start websocket server and gearman worker.

  ```php cli/app.php start```

* Happy hacking...