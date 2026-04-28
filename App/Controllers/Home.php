<?php

namespace App\Controllers;

use App\Models\Articles;
use Core\Controller;
use Core\View;
use Exception;

/**
 * Home controller
 */
class Home extends Controller
{

    /**
     * Affiche la page d'accueil
     *
     * @return void
     * @throws Exception
     */
    public function indexAction(): void
    {
        $sort = $_GET['sort'] ?? '';
        $searchQuery = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';

        if (!in_array($sort, ['', 'views', 'date'], true)) {
            $sort = '';
        }

        $products = Articles::getAll($sort, $searchQuery);
        View::renderTemplate('Home/index.html', [
            "products" => $products,
            "sort" => $sort,
            "searchQuery" => $searchQuery,
        ]);
    }
}
