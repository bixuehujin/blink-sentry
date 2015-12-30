<?php

namespace blink\sentry;

use blink\core\Application;
use Raven_Client;

/**
 * Class Client
 *
 * @package blink\sentry
 */
class Client extends Raven_Client
{
    const PROTOCOL = '5';

    protected function get_auth_header($timestamp, $client, $api_key, $secret_key)
    {
        $header = array(
            sprintf('sentry_timestamp=%F', $timestamp),
            "sentry_client={$client}",
            sprintf('sentry_version=%s', static::PROTOCOL),
        );

        if ($api_key) {
            $header[] = "sentry_key={$api_key}";
        }

        if ($secret_key) {
            $header[] = "sentry_secret={$secret_key}";
        }


        return sprintf('Sentry %s', implode(', ', $header));
    }

    protected function is_http_request()
    {
        return version_compare(explode(' ', Application::VERSION)[0], '0.1.1') >= 0 && app()->currentRequest;
    }

    protected function get_http_data()
    {
        $request = request();
        $headers = [];

        foreach ($request->headers as $name => $values) {
            $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));

            $headers[$name] = count($values) == 1 ? reset($values) : $values;
        }

        $result = [
            'method' => $request->method,
            'url' => $request->url(true),
            'query_string' => $request->queryString,
        ];

        if ($headers) {
            $result['headers'] = $headers;
        }

        $body = $request->getBody();
        if ($body->count()) {
            $result['data'] = $body->all();
        }

        if ($_ENV) {
            $result['env'] = $_ENV;
        }

        return [
            'request' => $result,
        ];
    }

    protected function get_user_data()
    {
        $user = $this->context->user;

        if ($user === null) {
            $session = request()->session;
            if (!$session || !$session->id) {
                return [];
            }

            $user = [
                'id' => $session->id,
                'data' => $session->all(),
            ];
        }

        return [
            'user' => $user,
        ];
    }
}
