<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
    /**
     * @var string $projectRoot
     */
    protected $projectRoot;

    /**
     * @var string $cssRoot
     */
    protected $cssRoot;

    /**
     * @var string $jsRoot
     */
    protected $jsRoot;

    public function __construct()
    {
        $this->projectRoot = __DIR__ . '/';
        $this->cssRoot = $this->projectRoot . 'www/css/';
        $this->jsRoot = $this->projectRoot . 'www/js/';
    }

    /**
     * Generates projects minified css file.
     */
    public function cssProject()
    {
        $this->taskConcat([
            $this->cssRoot.'src/*.css'
        ])
            ->to($this->cssRoot.'project.css')
            ->run();

        $this->taskMinify($this->cssRoot . 'project.css')
            ->to($this->cssRoot . 'project.min.css')
            ->run();

        unlink($this->cssRoot . 'project.css');
    }

    /**
     * Generates css file containing all vendor css libs.
     */
    public function cssVendor()
    {
        $this->taskConcat([
            $this->cssRoot . 'vendor/bootstrap.min.css',
            $this->cssRoot . 'vendor/font-awesome.min.css',
            $this->cssRoot . 'vendor/AdminLTE.min.css',
            $this->cssRoot . 'vendor/skin-blue.min.css',
            $this->cssRoot . 'vendor/diff2html.min.css',
            $this->projectRoot . 'www/fonts/source-code-pro.css',
            $this->projectRoot . 'www/fonts/source-sans-pro.css'
        ])
            ->to($this->cssRoot.'vendor.css')
            ->run();

        $this->taskMinify($this->cssRoot . 'vendor.css')
            ->to($this->cssRoot . 'vendor.min.css')
            ->run();

        unlink($this->cssRoot . 'vendor.css');
    }

    /**
     * Generates minified project js file.
     */
    public function jsProject()
    {
        $this->taskConcat([
            $this->jsRoot . 'app/app.js',
            $this->jsRoot . 'app/**/*.js'
        ])
            ->to($this->jsRoot . 'project.js')
            ->run();

        $this->taskMinify($this->jsRoot . 'project.js')
            ->to($this->jsRoot . 'project.min.js')
            ->run();

        unlink($this->jsRoot . 'project.js');
    }

    /**
     * Generates file containing all vendor js libs.
     */
    public function jsVendor()
    {
        $this->taskConcat([
            $this->jsRoot . 'vendor/angular.min.js',
            $this->jsRoot . 'vendor/angular-route.min.js',
            $this->jsRoot . 'vendor/angular-jwt.min.js',
            $this->jsRoot . 'vendor/jquery.min.js',
            $this->jsRoot . 'vendor/jquery.nanoscroller.min.js',
            $this->jsRoot . 'vendor/bootstrap.min.js',
            $this->jsRoot . 'vendor/template.min.js',
            $this->jsRoot . 'vendor/autobahn.min.js',
            $this->jsRoot . 'vendor/diff2html.min.js'
        ])
            ->to($this->jsRoot . 'vendor.min.js')
            ->run();
    }

    /**
     * Rebuilds all assets.
     */
    public function assets()
    {
        $this->cssProject();
        $this->cssVendor();
        $this->jsProject();
        $this->jsVendor();
    }

    /**
     * Watches project JS and CSS folders for changes and regenerates assets if required.
     */
    public function watch()
    {
        $this->taskWatch()->monitor([$this->cssRoot . 'src/'], function () {
            $this->cssProject();
        })->monitor([$this->jsRoot . 'app/'], function () {
            $this->jsProject();
        })->run();
    }
}
