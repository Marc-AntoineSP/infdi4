<?php

namespace Core;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use RuntimeException;

/**
 * View
 *
 * PHP version 7.0
 */
class View
{

    /**
     * @param string $view  The template file
     * @param array $args  Associative array of data to display in the view (optional)
     *
     * @return void
     */
    public static function render(string $view, array $args = []): void
    {
        self::renderTemplate($view, $args);
    }

    /**
     * Render a view template using Twig
     *
     * @param string $template  The template file
     * @param array $args  Associative array of data to display in the view (optional)
     *
     * @return void
     */
    public static function renderTemplate($template, $args = [])
    {
        static $twig = null;

        if ($twig === null) {
            $loader = new FilesystemLoader(dirname(__DIR__) . '/App/Views');
            $twig = new Environment($loader, ['debug' => true,]);
            $twig->addExtension(new DebugExtension());
        }

        try {
            echo $twig->render($template, self::setDefaultVariables($args));
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            throw new RenderException($e->getMessage());
        }
    }

    /**
     * Ajoute les données à fournir à toutes les pages
     * @param array $args
     * @return array
     */
    public static function setDefaultVariables($args = []){

        $args["user"] = isset($_SESSION['user']) ? $_SESSION['user'] : null;

        return $args;
    }
}
