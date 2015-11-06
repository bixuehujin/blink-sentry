blink-sentry - Sentry integration for Blink Framework
=====================================================


## Installation

You can install the latest version of blink-sentry by using Composer:

```bash
composer require blink/sentry:dev-master@dev
```

## Configuration

You can easily setup Sentry in your Blink application with the following two steps:

### 1. Add a new service named `sentry` in the services definition file:

```php
'sentry' => [
    'class' => \blink\sentry\Sentry::class,
    'dsn' => 'The dsn',
    'environments' => ['prod'], // The environments that should report errors to Sentry
],
```

### 2. Override the default ErrorHandler:

```php
'errorHandler' => [
    'class' => blink\sentry\ErrorHandler::class
],
```
