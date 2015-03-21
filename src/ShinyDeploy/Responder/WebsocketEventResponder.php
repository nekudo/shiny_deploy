<?php
namespace ShinyDeploy\Responder;

class WebsocketEventResponder
{
    public function __invoke($event, array $params)
    {
        if (!isset($params['source'])) {
            $params['source'] = 'WsGateway';
        }
        return [
            'wsEventName' => $event,
            'wsEventParams' => $params,
        ];
    }
}
