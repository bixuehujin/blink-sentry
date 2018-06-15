<?php

namespace blink\sentry;

use blink\core\BaseObject;

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
     * @var Client
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
     * @param $exception
     * @param array $options
     * @param string $logger
     * @param null $context
     * @return string
     */
    public function captureException($exception, $options = [], $logger = '', $context = null)
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            return  $this->_client->getIdent(
                $this->_client->captureException($exception, $options, $logger, $context)
            );
        } catch (\Exception $e) {
            logger()->error($e);
        }
    }

    /**
     * Log a message to sentry.
     *
     * @param $message
     * @param array $params
     * @param array $options
     * @param bool|false $stack
     * @param null $context
     * @return string
     */
    public function captureMessage($message, $params = [], $options = [], $stack = false, $context = null)
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            return $this->_client->getIdent(
                $this->_client->captureMessage($message, $params, $options, $stack, $context)
            );
        } catch (\Exception $e) {
            logger()->error($e);
        }
    }

    /**
     * Log a query to sentry.
     *
     * @param $query
     * @param string $level
     * @param string $engine
     * @return string
     */
    public function captureQuery($query, $level = Client::INFO, $engine = '')
    {
        if (!$this->isEnabled()) {
            return null;
        }
        try {
            return $this->_client->getIdent(
                $this->_client->captureQuery($query, $level, $engine)
            );
        } catch (\Exception $e) {
            logger()->error($e);
        }
    }

    public function flush()
    {
        $this->_client->sendUnsentErrors();
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

        return new Client($this->dsn, $options);
    }
}
