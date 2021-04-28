<?php

namespace blink\sentry;

use Closure;
use blink\core\BaseObject;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sentry\Integration\FrameContextifierIntegration;
use Sentry\Integration\RequestFetcherInterface;
use Sentry\Integration\RequestIntegration;
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

    protected function fetchRequest(): ?ServerRequestInterface
    {
        return null;
    }

    protected function configureScope(Scope $scope): void 
    {
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
            $id = null;
            $this->_client->withScope(function (Scope $scope) use (&$id, $exception) {
                $this->configureScope($scope);
               $id = $this->_client->captureException($exception);
            });
        } catch (\Throwable $e) {
            logger()->error($e);
        } finally {
            $this->flush();;
        }
        
        return $id ?? null;
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
            $id = null;
            $this->_client->withScope(function (Scope $scope) use ($context, $message, &$id) {
                $scope->setExtras($context);
                $this->configureScope($scope);
                $id = $this->_client->captureMessage($message);
            }); 
            
        } catch (\Exception $e) {
            logger()->error($e);
        } finally {
            $this->flush();;
        }
        
        return $id; 
    }
    
    protected function flush(): void
    {
        $sentry = $this->_client->getClient();
        
        assert($sentry instanceof \Sentry\Client);
        
        $sentry->flush();
    }

    protected function createClient()
    {
        $fetcher = fn() => $this->fetchRequest();
        
        $options = array_merge_recursive(
            [
                'logger' => 'blink',
                'tags' => [
                    'environment' => app()->environment,
                    'php_version' => phpversion(),
                ],
                'integrations' => [
                    new FrameContextifierIntegration(),
                    new RequestIntegration(null, new class($fetcher) implements RequestFetcherInterface {
                        protected Closure $fetcher;
                        public function __construct(Closure $fetcher)
                        {
                            $this->fetcher = $fetcher;
                        }
                        public function fetchRequest(): ?ServerRequestInterface
                        {
                            return ($this->fetcher)();
                        }
                    }),
                ],
            ],
            $this->options
        );

        $options['dsn'] = $this->dsn;

        \Sentry\init($options);

        return \Sentry\SentrySdk::getCurrentHub();
    }
}
