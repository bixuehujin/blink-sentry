<?php

namespace blink\sentry;

use Closure;
use blink\core\BaseObject;
use blink\di\Container;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sentry\Integration\FrameContextifierIntegration;
use Sentry\Integration\RequestFetcherInterface;
use Sentry\Integration\RequestIntegration;
use Sentry\Severity;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Sentry\Tracing\Transaction;
use Sentry\Tracing\TransactionContext;

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
    /** @var Transaction|null */
    private $currentTransaction;

    public function init()
    {
        $this->_client = $this->createClient();
    }

    protected function isEnabled()
    {
        $env = Container::$global->get('app.env');

        return in_array($env, $this->environments);
    }

    protected function fetchRequest(): ?ServerRequestInterface
    {
        return null;
    }

    protected function configureScope(Scope $scope): void 
    {
        $env = Container::$global->get('app.env');

        $scope->setTags([
            'environment' => $env,
            'php_version' => phpversion(),
        ]);
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

    public function startTransaction(TransactionContext $context, array $customSamplingContext = []): Transaction
    {
        return $this->_client->startTransaction($context, $customSamplingContext);
    }
    
    public function currentTransaction(): ?Transaction 
    {
        return $this->currentTransaction; 
    }
    
    public function setCurrentTransaction(?Transaction $transaction): void 
    {
        $this->currentTransaction = $transaction;
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
                'integrations' => [
                    new FrameContextifierIntegration(),
                    new RequestIntegration(new class($fetcher) implements RequestFetcherInterface {
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
