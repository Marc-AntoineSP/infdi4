<?php

namespace App\Controllers;

use App\Models\Articles;
use \Core\View;
use Exception;

/**
 * Home controller
 */
class Home extends \Core\Controller
{

    /**
     * Affiche la page d'accueil
     *
     * @return void
     * @throws \Exception
     */
    public function indexAction()
    {
        $sort = $_GET['sort'] ?? '';

        if (!in_array($sort, ['', 'views', 'date'], true)) {
            $sort = '';
        }

        $products = Articles::getAll($sort);
        View::renderTemplate('Home/index.html', [
            "products" => $products,
            "sort" => $sort,
        ]);
    }
}
