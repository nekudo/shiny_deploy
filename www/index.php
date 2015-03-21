<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
    <meta http-equiv="Content-Language" content="en">
    <meta name="description" content="Shiny Deploy">
    <title>Shiny Deploy</title>
    <link href="css/bootstrap.min.css" media="all" rel="stylesheet" type="text/css">
    <link href="css/src/console.css" media="all" rel="stylesheet" type="text/css">
</head>
<body>
<div class="container">
    <header>
        <div class="page-header">
            <h1>Shiny Deploy</h1>
        </div>
    </header>

    <div class="row">
        <div class="col-lg-6">
            <div class="well bs-component">
                <form class="form-horizontal" method="post" action="">
                    <fieldset>
                        <legend>Dummy Inputs</legend>
                        <div class="form-group">
                            <label for="deploy-source" class="col-lg-2 control-label">Source</label>
                            <div class="col-lg-10">
                                <select class="form-control" id="deploy-source">
                                    <option value="source1">Source #1</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="deploy-target" class="col-lg-2 control-label">Target</label>
                            <div class="col-lg-10">
                                <select class="form-control" id="deploy-target">
                                    <option value="target1">Target #1</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-lg-10 col-lg-offset-2">
                                <button type="submit" id="deploy-button" class="btn btn-success">Deploy</button>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>

    <h3>Logs</h3>
    <div id="console" class="nano">
        <div id="log" class="nano-content"></div>
    </div>



    <footer>
        <p class="text-center"><small>another shiny project by <a href="https://nekudo.com">nekudo.com</a>.</small></p>
    </footer>
</div><!-- /container -->
<script src="js/vendor/autobahn.min.js"></script>
<script src="js/vendor/jquery.min.js"></script>
<script src="js/vendor/jquery.nanoscroller.min.js"></script>
<script src="js/src/HelperMisc.js"></script>
<script src="js/src/WsEventHandler.js"></script>
<script src="js/src/WsEvents.js"></script>
<script src="js/src/project.js"></script>
</body>
</html>