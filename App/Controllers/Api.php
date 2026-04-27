<?php

namespace App\Controllers;

use App\Models\Articles;
use App\Models\Cities;
use Core\Controller;
use Exception;

/**
 * API controller
 */
class Api extends Controller
{

    /**
     * Affiche la liste des articles / produits pour la page d'accueil
     *
     * @throws Exception
     */
    public function ProductsAction(): void
    {
        $query = $_GET['sort'];

        $articles = Articles::getAll($query);

        header('Content-Type: application/json');
        echo json_encode($articles, JSON_THROW_ON_ERROR);
    }

    /**
     * Recherche dans la liste des villes
     *
     * @throws Exception
     */
    public function CitiesAction(): void
    {

        $cities = Cities::search($_GET['query']);

        header('Content-Type: application/json');
        echo json_encode($cities, JSON_THROW_ON_ERROR);
    }
}
