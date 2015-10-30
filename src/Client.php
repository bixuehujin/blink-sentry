<?php

namespace blink\sentry;

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
        return false;
    }

    protected function get_http_data()
    {
        return [];
    }

    protected function get_user_data()
    {
        return [];
    }
}
