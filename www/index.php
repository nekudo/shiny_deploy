<!DOCTYPE html>
<html data-ng-app="shinyDeploy">
<head>
    <meta charset="UTF-8">
    <title>Shiny Deploy</title>
    <base href="/">
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <link rel="icon" href="data:;base64,iVBORw0KGgo=">
    <link href="/css/vendor.min.css" rel="stylesheet" type="text/css" />
    <link href="/css/project.min.css" rel="stylesheet" type="text/css" />
</head>

<body class="skin-blue layout-boxed">
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
            <ul class="sidebar-menu" data-ng-controller="MenuController as vm">
                <li class="header">MAIN NAVIGATION</li>
                <li data-ng-class="{'active':vm.getClass('/repositories')}">
                    <a href="/repositories">
                        <i class="fa fa-github"></i> <span>Repositories</span>
                    </a>
                </li>
                <li data-ng-class="{'active':vm.getClass('/servers')}">
                    <a href="/servers">
                        <i class="fa fa-linux"></i> <span>Servers</span>
                    </a>
                </li>
                <li data-ng-class="{'active':vm.getClass('/deployments')}">
                    <a href="/deployments">
                        <i class="fa fa-cloud-upload"></i> <span>Deployments</span>
                    </a>
                </li>
            </ul>
        </section>
    </aside>

    <div class="content-wrapper">
        <div data-ng-view=""></div>
    </div>

    <footer class="main-footer">
        <small>another shiny project by <a href="https://nekudo.com">nekudo.com</a></small>
    </footer>

</div><!-- /wrapper -->

<div data-ng-include="'/js/app/partials/notifications.html'"></div>

<script src="/js/vendor.min.js"></script>
<script src="/js/project.min.js"></script>
</body>
</html>