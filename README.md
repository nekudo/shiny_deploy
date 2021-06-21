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
* PHP >= 8.0
  * [ZeroMQ Extension](http://zeromq.org/bindings:php)
  * [Gearman Extension](http://gearman.org/download/#php)
  * Curl extension
  * Mysqli extension

### Installation procedure

1. Install project using composer.

    ```composer create-project nekudo/shiny_deploy myshinydeploy```

2. Adjust config files in the following folders:

    ```config/config.php```

    ```www/js/config.js```

3. Run the installation script using the following command:

    ```php cli/app.php install```
    
4. Point your virtual host document root to the `www` directory and rewrite requests to the index.php file.

5. To use this application you need to start up the websocket server and worker processes. This can be done by executing
the following command:

    ```php cli/app.php start```

### Update procedure

To update an existing instance of ShinyDeploy the first step is to update the files. You can either use a simple
`git pull` to do this (in case you installed the application trough composer) or just override all the files with
a fresh copy downloaded from the project website.

Once the files are update you need to execution the update command which will execute all necessary migrations:

`php cli/app.php update`

Hint: Please have a look into the release notes before update you installation of ShinyDeploy to prevent any
problems.

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