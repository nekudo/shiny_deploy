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


PHP Internationalization

```sudo apt-get install php5-intl```


### Installation

* Clone repository.

  ```git clone https://github.com/nekudo/shiny_deploy.git```
  
* Run composer update
* Create MySQL tables (db_structure.sql)
* Adjust config files
  * /src/ShinyDeploy/config.sample.php -> /src/ShinyDeploy/config.php
  * /www/js/app/app.config.sample.js -> /www/js/app.config.js
* Install node dependencies

  ```npm install```

* Run gulp tasks

  ```
  gulp vendor_css
  gulp project_css
  gulp vendor_js
  gulp project_js
  ```
  
* Start cli scripts

  ```
  php cli/ws_gateway.php
  php cli/worker.deployer.php --name deploy1
  php cli/worker.repo_actions.php --name repo1
  ```

* Happy hacking...