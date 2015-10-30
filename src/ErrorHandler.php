<?php

namespace blink\sentry;

use blink\core\ErrorHandler as BaseErrorHandler;

/**
 * Class ErrorHandler
 *
 * @package blink\sentry
 */
class ErrorHandler extends BaseErrorHandler
{
    /**
     * The service name used to report error.
     *
     * @var string
     */
    public $sentry = 'sentry';

    protected function report($exception)
    {
        if (in_array(get_class($exception), $this->notReport)) {
            return;
        }

        app()->get($this->sentry)->captureException($exception);

        parent::report($exception);
    }
}
