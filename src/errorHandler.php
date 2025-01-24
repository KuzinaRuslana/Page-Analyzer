<?php

return function ($app) {
    $app->map(['GET', 'POST', 'PUT', 'DELETE'], '/{routes:.+}', function ($request, $response) {
        return $this->get('renderer')->render($response->withStatus(404), 'error404.phtml');
    });
};