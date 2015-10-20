<?php
namespace ShinyDeploy\Responder;

use ShinyDeploy\Core\Responder;

/**
 * A dummy responder in cases no logging e.g. is needed.
 */
class NullResponder extends Responder
{
     public function log($msg, $type = 'default', $source = '')
     {
         return true;
     }
}