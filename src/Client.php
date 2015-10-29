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
