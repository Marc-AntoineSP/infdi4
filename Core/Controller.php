<?php

namespace Core;

use AllowDynamicProperties;
use InvalidArgumentException;
use RuntimeException;

/**
 * Base controller
 *
 * PHP version 7.0
 */
#[AllowDynamicProperties]
abstract class Controller
{
    private const string FLASH_SESSION_KEY = 'flashes';

    /**
     * Parameters from the matched route
     * @var array
     */
    protected array $routeParams = [];

    /**
     * Class constructor
     *
     * @param array<int, string> $routeParams  Parameters from the route
     *
     * @return void
     */
    public function __construct(array $routeParams)
    {
        $this->route_params = $routeParams;
    }

    /**
     * @param string $name  Method name
     * @param array<int, string> $args Arguments passed to the method
     *
     * @return void
     */
    public function __call(string $name, array $args)
    {
        $method = $name . 'Action';

        if (method_exists($this, $method)) {
            call_user_func_array([$this, $method], $args);
        } else {
            throw new InvalidArgumentException("Method $method not found in controller " . get_class($this));
        }
    }

    /**
     * Before filter - called before an action method.
     *
     * @return void
     */
    protected function before(): void
    {
    }

    /**
     * After filter - called after an action method.
     *
     * @return void
     */
    protected function after(): void
    {
    }

    protected function addFlash(string $type, string $message): void
    {
        $allowedTypes = ['success', 'warning', 'danger'];
        $normalizedType = in_array($type, $allowedTypes, true) ? $type : 'success';
        $normalizedMessage = trim($message);

        if ($normalizedMessage === '') {
            return;
        }

        if (!isset($_SESSION[self::FLASH_SESSION_KEY]) || !is_array($_SESSION[self::FLASH_SESSION_KEY])) {
            $_SESSION[self::FLASH_SESSION_KEY] = [];
        }

        $_SESSION[self::FLASH_SESSION_KEY][] = [
            'type' => $normalizedType,
            'message' => $normalizedMessage,
        ];
    }

    protected function addSuccessFlash(string $message): void
    {
        $this->addFlash('success', $message);
    }

    protected function addWarningFlash(string $message): void
    {
        $this->addFlash('warning', $message);
    }

    protected function addDangerFlash(string $message): void
    {
        $this->addFlash('danger', $message);
    }

    protected function consumeFlashes(): array
    {
        $rawMessages = $_SESSION[self::FLASH_SESSION_KEY] ?? [];
        unset($_SESSION[self::FLASH_SESSION_KEY]);

        if (!is_array($rawMessages)) {
            return [];
        }

        $flashes = [];
        foreach ($rawMessages as $flash) {
            if (!is_array($flash)) {
                continue;
            }

            $type = isset($flash['type']) && is_string($flash['type']) ? $flash['type'] : 'success';
            if (!in_array($type, ['success', 'warning', 'danger'], true)) {
                $type = 'success';
            }

            $message = isset($flash['message']) && is_string($flash['message']) ? trim($flash['message']) : '';
            if ($message === '') {
                continue;
            }

            $flashes[] = [
                'type' => $type,
                'message' => $message,
            ];
        }

        return $flashes;
    }

    protected function withSonner(array $args = []): array
    {
        $args['sonnerFlashes'] = $this->consumeFlashes();

        return $args;
    }
}
