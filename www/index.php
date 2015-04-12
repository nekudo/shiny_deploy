<!DOCTYPE html>
<html data-ng-app="shinyDeploy">
<head>
    <meta charset="UTF-8">
    <title>Shiny Deploy</title>
    <base href="/">
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <link href="/css/vendor/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
    <link href="http://code.ionicframework.com/ionicons/2.0.0/css/ionicons.min.css" rel="stylesheet" type="text/css" />
    <link href="/css/vendor/AdminLTE.min.css" rel="stylesheet" type="text/css" />
    <link href="/css/vendor/skin-blue.min.css" rel="stylesheet" type="text/css" />
    <link href="/css/src/console.css" rel="stylesheet" type="text/css" />
</head>

<body class="skin-blue">
<div class="wrapper">

    <header class="main-header">
        <a href="/" class="logo">Shiny<b>Deploy</b></a>
        <nav class="navbar navbar-static-top" role="navigation">
            <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </a>
        </nav>
    </header>

    <aside class="main-sidebar">
        <section class="sidebar">
            <ul class="sidebar-menu" data-ng-controller="MenuController">
                <li class="header">MAIN NAVIGATION</li>
                <li data-ng-class="{'active':getClass('/servers')}">
                    <a href="/servers">
                        <i class="fa fa-linux"></i> <span>Servers</span>
                    </a>
                </li>
                <li data-ng-class="{'active':getClass('/repositories')}">
                    <a href="/repositories">
                        <i class="fa fa-github"></i> <span>Repositories</span>
                    </a>
                </li>
            </ul>
        </section>
    </aside>

    <div class="content-wrapper" data-ng-view=""></div>

    <footer class="main-footer">
        <small>another shiny project by <a href="https://nekudo.com">nekudo.com</a></small>
    </footer>

</div><!-- /wrapper -->
<script src="/js/vendor/angular.min.js"></script>
<script src="/js/vendor/angular-route.min.js"></script>
<script src="/js/vendor/jquery.min.js"></script>
<script src="/js/vendor/jquery.nanoscroller.min.js"></script>
<script src="/js/vendor/bootstrap.min.js"></script>
<script src="/js/vendor/template.min.js"></script>
<script src="/js/vendor/autobahn.min.js"></script>

<script src="/js/app.js"></script>
<script src="/js/app/services/servers.js"></script>
<script src="/js/app/services/websocket.js"></script>
<script src="/js/app/controllers/log.js"></script>
<script src="/js/app/controllers/home.js"></script>
<script src="/js/app/controllers/menu.js"></script>
<script src="/js/app/controllers/servers.js"></script>
<script src="/js/app/controllers/repositories.js"></script>
</body>
</html>