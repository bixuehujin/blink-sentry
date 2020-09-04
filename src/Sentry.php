<?php

namespace blink\sentry;

use blink\core\BaseObject;
use Sentry\Severity;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

/**
 * Class Sentry
 *
 * @package blink\sentry
 */
class Sentry extends BaseObject
{
    public $dsn;
    public $options = [];
    public $environments = ['prod', 'staging'];

    /**
     * @var HubInterface
     */
    private $_client;

    public function init()
    {
        $this->_client = $this->createClient();
    }

    protected function isEnabled()
    {
        return in_array(app()->environment, $this->environments);
    }

    /**
     * Log an exception to sentry.
     *
     * @param \Throwable $exception
     * @return string|null
     */
    public function captureException(\Throwable $exception)
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            return $this->_client->captureException($exception);
        } catch (\Throwable $e) {
            logger()->error($e);
            return null;
        }
    }

    /**
     * Log a message to sentry.
     *
     * @param string $message
     * @param array $context
     * @param Severity|null $level
     * @return string|null
     */
    public function captureMessage(string $message, array $context = [], ?Severity $level = null)
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            return $this->_client->captureMessage($message);
        } catch (\Exception $e) {
            logger()->error($e);
            return null;
        }
    }

    protected function createClient()
    {
        $options = array_merge_recursive(
            [
                'logger' => 'blink',
                'tags' => [
                    'environment' => app()->environment,
                    'php_version' => phpversion(),
                ],
            ],
            $this->options
        );

        $options['dsn'] = $this->dsn;

        \Sentry\init($options);

        return \Sentry\SentrySdk::getCurrentHub();
    }
}
