<?php

declare(strict_types=1);

namespace App\Utility;

enum ErrorMessageEnum: string
{
    public const string CSRF_GENERATION_ERROR = 'Une erreur est survenue lors de la generation du token';
    public const string ARTICLE_NOT_FOUND = 'Article introuvable';
    public const string PRODUCT_LOAD_ERROR = 'Une erreur est survenue lors de la chargement du produit';
    public const string PRODUCT_UPLOAD_ERROR = 'Une erreur est survenue lors de l\'enregistrement du produit';
    public const string INVALID_CREDENTIALS = 'Identifiants invalides';
    public const string REQUEST_METHOD_INVALID = 'Methode non autorisée pour cette action';
    public const string INVALID_CSRF_TOKEN = 'Token CSRF invalide';
    public const string PICTURE_TOO_LARGE = 'La photo ne doit pas depasser 4 Mo';
    public const string PICTURE_UPLOAD_ERROR = 'Une erreur est survenue lors de l upload de la photo';
    public const string PICTURE_NOT_BLANK = 'La photo est obligatoire';
    public const string DEFAULT_PICTURE_UPLOAD_ERROR = 'Une erreur est survenue lors de l upload de la photo';
    public const string PICTURE_MIME_TYPE_ERROR = 'La photo doit etre au format JPG ou PNG';
    public const string MESSAGE_SEND_ERROR = 'Une erreur est survenue lors de l envoi du message';
}
