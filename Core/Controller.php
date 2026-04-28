<?php

namespace Core;

use RuntimeException;

/**
 * Base controller
 *
 * PHP version 7.0
 */
abstract class Controller
{
    private const FLASH_SESSION_KEY = 'flashes';

    /**
     * Parameters from the matched route
     * @var array
     */
    protected $route_params = [];

    /**
     * Class constructor
     *
     * @param array $route_params  Parameters from the route
     *
     * @return void
     */
    public function __construct($route_params)
    {
        $this->route_params = $route_params;
    }

    /**
     * @param string $name  Method name
     * @param array $args Arguments passed to the method
     *
     * @return void
     */
    public function __call($name, $args)
    {
        $method = $name . 'Action';

        if (method_exists($this, $method)) {
            call_user_func_array([$this, $method], $args);
        } else {
            throw new RuntimeException("Method $method not found in controller " . get_class($this));
        }
    }

    /**
     * Before filter - called before an action method.
     *
     * @return void
     */
    protected function before()
    {
    }

    /**
     * After filter - called after an action method.
     *
     * @return void
     */
    protected function after()
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
