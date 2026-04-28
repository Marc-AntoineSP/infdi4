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
     * Render a view file
     *
     * @param string $view  The view file
     * @param array $args  Associative array of data to display in the view (optional)
     *
     * @return void
     */
    public static function render($view, $args = [])
    {
        extract($args, EXTR_SKIP);

        $file = dirname(__DIR__) . "/App/Views/$view";  // relative to Core directory

        if (is_readable($file)) {
            require_once $file;
        } else {
            throw new RuntimeException("$file not found");
        }
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
        } catch (LoaderError $e) {
            throw new RuntimeException($e->getMessage());
        } catch (RuntimeError $e) {
            throw new RuntimeException($e->getMessage());
        } catch (SyntaxError $e) {
            throw new RuntimeException($e->getMessage());
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
