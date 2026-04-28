<?php

namespace App\Controllers;

use App\Models\Articles;
use App\Utility\ApplicationEnum;
use App\Utility\ErrorMessageEnum;
use App\Utility\Hash;
use App\Validators\UserControllerValidator;
use Core\Controller;
use \Core\View;
use Exception;
use InvalidArgumentException;
use JetBrains\PhpStorm\NoReturn;
use LogicException;
use App\Models\User as UserModel;
use App\Models\UserToken as UserTokenModel;
use Random\RandomException;

/**
 * User controller
 */
class User extends Controller
{
    /**
     * Affiche la page de login
     */
    public function loginAction(): void //Ca a l'air OK.
    {
        if (!isset($_SESSION['user']['id']) && isset($_COOKIE['remember_me'])) {
            $this->refreshSessionIfRememberMeValid();
        }

        if (isset($_SESSION['user']['id'])) {
            header(ApplicationEnum::HEADER_LOCATION . $this->consumeRedirectAfterLogin());
            exit;
        }

        $formError = null;

        if(isset($_POST['submit'])){
            $f = $_POST;

            try {
                $this->validateFormLogin($f);

                $this->login($f);

                if (!isset($_SESSION['user']['id'])) {
                    $formError = ErrorMessageEnum::INVALID_CREDENTIALS;
                    $this->addWarningFlash(ErrorMessageEnum::INVALID_CREDENTIALS);
                } else {
                    $this->addSuccessFlash("You've succesfully been logged in");
                    header(ApplicationEnum::HEADER_LOCATION . $this->consumeRedirectAfterLogin());
                    exit;
                }
            } catch (InvalidArgumentException $e) {
                $formError = $e->getMessage();
                $this->addWarningFlash(ErrorMessageEnum::INVALID_CREDENTIALS);
            } catch (Exception) {
                $formError = ErrorMessageEnum::INVALID_CREDENTIALS;
                $this->addWarningFlash(ErrorMessageEnum::INVALID_CREDENTIALS);
            }
        }

        View::renderTemplate('User/login.html', $this->withSonner([
            'formError' => $formError
        ]));
    }

    /**
     * Page de création de compte
     */
    public function registerAction(): void //Fixed: Login + redirect.
    {
        if (isset($_SESSION['user']['id'])) {
            header(ApplicationEnum::HEADER_LOCATION . $this->consumeRedirectAfterLogin());
            exit;
        }

        $formError = null;

        if(isset($_POST['submit'])){
            $f = $_POST;

            try {
                $this->validateRegisterForm($f);

                $this->register($f);
                $this->login($f);

                if (!isset($_SESSION['user']['id'])) {
                    $formError = 'Impossible de connecter le nouvel utilisateur';
                } else {
                    header(ApplicationEnum::HEADER_LOCATION . $this->consumeRedirectAfterLogin());
                    exit;
                }
            } catch (InvalidArgumentException $e) {
                $this->addWarningFlash("Une erreur est survenue lors de l'enregistrement");
                $formError = $e->getMessage();
            } catch (Exception) {
                $this->addDangerFlash("Une erreur technique est survenue lors de l'enregistrement");
                $formError = "Impossible d'enregistrer l'utilisateur";
            }
        }

        View::renderTemplate('User/register.html', $this->withSonner([
            'formError' => $formError
        ]));
    }

    /**
     * Affiche la page du compte
     */
    public function accountAction(): void
    {
        try {
            $articles = Articles::getByUser($_SESSION['user']['id']);
        } catch (Exception) {
            $this->addDangerFlash("Aucun articles sur votre compte");
            $articles = [];
        }

        View::renderTemplate('User/account.html', $this->withSonner([
            'articles' => $articles
        ]));
    }

    /*
     * Fonction privée pour enregister un utilisateur
     */
    private function register($data): void
    {
        try {
            $salt = Hash::generateSalt(32);

            UserModel::createUser([
                "email" => $data['email'],
                "username" => $data['username'],
                "password" => Hash::generate($data['password'], $salt),
                "salt" => $salt
            ]);

            return;

        } catch (Exception) {
            $this->addDangerFlash("Impossible d'enregistrer l'utilisateur");
        }
    }

    private function login($data): void //Créer le cookie de rememberme
    {
        try {
            if(!isset($data['email'], $data['password'])){
                throw new InvalidArgumentException('Email et mot de passe requis');
            }

            $user = UserModel::getByLogin($data['email']);

            if (!is_array($user)) {
                return;
            }

            if (Hash::generate($data['password'], $user['salt']) !== $user['password']) {
                return;
            }

            $this->completeLogin($user);

            if (isset($data['remember_me'])) {
                $this->issueRememberMeCookie((int) $user['id']);
            }
        } catch (Exception) {
            $this->addDangerFlash("Invalid credentials");
        }
    }

    private function refreshSessionIfRememberMeValid(): void
    {
        $token = $_COOKIE['remember_me'] ?? null;
        if (!is_string($token) || $token === '') {
            return;
        }

        try {
            $userToken = UserTokenModel::getByToken($token);
            if ($userToken === null) {
                $this->clearRememberMeCookie();
                $this->addWarningFlash('No session found for this cookie.');
                return;
            }

            $user = UserModel::getOneById((int) $userToken['user_id']);
            if (!is_array($user) || !isset($user['id'])) {
                UserTokenModel::invalidateByToken($token);
                $this->clearRememberMeCookie();
                $this->addWarningFlash('No user found for this cookie.');
                return;
            }

            $this->completeLogin($user);
            UserTokenModel::invalidateByToken($token);
            $this->issueRememberMeCookie((int) $user['id']);
            $this->addSuccessFlash("Session refreshed");
        } catch (Exception) {
            $this->clearRememberMeCookie();
            $this->addDangerFlash('Unable to restore your previous session. Please log in again.');
        }
    }

    private function consumeRedirectAfterLogin(): string
    {
        $targetUrl = $_SESSION['redirect_after_login'] ?? '/account';
        unset($_SESSION['redirect_after_login']);

        if (!is_string($targetUrl) || $targetUrl === '' || $targetUrl[0] !== '/' || str_starts_with($targetUrl, '//')) {
            return '/account';
        }

        return $targetUrl;
    }

    private function completeLogin(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => (string) $user['username'],
        ];
    }

    /**
     * @throws RandomException
     */
    private function issueRememberMeCookie(int $userId): void
    {
        $userToken = UserTokenModel::createUserToken($userId);
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        $ok = setcookie('remember_me', $userToken, [
            'expires'  => time() + 60 * 60 * 24,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        if (!$ok) {
            throw new LogicException('Failed to send rememberme cookie');
        }
    }

    private function clearRememberMeCookie(): void
    {
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie('remember_me', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function validateFormLogin(array $data): void
    {
        UserControllerValidator::validateEmail($data['email'] ?? null);
        UserControllerValidator::validatePassword($data['password'] ?? null);
    }

    private function validateRegisterForm(array $data): void
    {
        UserControllerValidator::validateUsername($data['username'] ?? null);
        UserControllerValidator::validateEmail($data['email'] ?? null);
        UserControllerValidator::validatePassword($data['password'] ?? null);
        UserControllerValidator::validatePasswordMatch($data['password'] ?? null, $data['password-check'] ?? null);
    }

    /**
     * Logout: Delete cookie and session. Returns true if everything is okay,
     * otherwise turns false.
     * @access public
     * @return void
     * @since 1.0.2
     */
    #[NoReturn]
    public function logoutAction(): void
    {

        $_SESSION = array();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
        $this->clearRememberMeCookie();

        header ("Location: /");
        exit;
    }

}
