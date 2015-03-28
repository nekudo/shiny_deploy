<?php
namespace ShinyDeploy\Core;

use Apix\Log\Logger;
use Noodlehaus\Config;
use Slim\Slim;
use Slim\View;

class SlimResponder extends View
{
    /** @var Config $config */
    protected $config;

    /** @var Logger $logger */
    protected $logger;

    /** @var Slim $slim */
    protected $slim;

    public function __construct(Config $config, Logger $logger, Slim $slim)
    {
        parent::__construct();
        $this->config = $config;
        $this->logger = $logger;
        $this->slim = $slim;
        $this->setTemplatesDirectory($this->config->get('views.path'));
    }

    public function render($template, $data = null)
    {
        $templatePathname = $this->getTemplatePathname($template);
        if (!is_file($templatePathname)) {
            throw new \RuntimeException("View cannot render `$template` because the template does not exist");
        }
        $data = array_merge($this->data->all(), (array) $data);
        extract($data);
        ob_start();
        require $templatePathname;
        return ob_get_clean();
    }
}
