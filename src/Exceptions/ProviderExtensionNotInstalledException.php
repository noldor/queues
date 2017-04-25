<?php
declare(strict_types=1);

namespace Noldors\Queues\Exceptions;

/**
 * Exception to determine that pdo extension not loaded or installed.
 *
 * @package Noldors\Queues\Exceptions
 */
class ProviderExtensionNotInstalledException extends \Exception {}
