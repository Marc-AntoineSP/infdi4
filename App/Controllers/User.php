<?php

namespace App\Controllers;

use App\Model\UserRegister;
use App\Models\Articles;
use App\Utility\Hash;
use App\Utility\RegexEnum;
use App\Utility\Session;
use \Core\View;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

/**
 * User controller
 */
class User extends \Core\Controller
{

    /**
     * Affiche la page de login
     */
    public function loginAction()
    {
        $formError = null;

        if(isset($_POST['submit'])){
            $f = $_POST;

            try {
                $this->validateFormLogin($f);

                $this->login($f);

                // Si login OK, redirige vers le compte
                header('Location: /account');
            } catch (InvalidArgumentException $e) {
                $formError = $e->getMessage();
            }
        }

        View::renderTemplate('User/login.html', [
            'formError' => $formError
        ]);
    }

    /**
     * Page de création de compte
     */
    public function registerAction()
    {
        $formError = null;

        if(isset($_POST['submit'])){
            $f = $_POST;

            try {
                $this->validateRegisterForm($f);

                $this->register($f);
                // TODO: Rappeler la fonction de login pour connecter l'utilisateur
            } catch (InvalidArgumentException $e) {
                $formError = $e->getMessage();
            }
        }

        View::renderTemplate('User/register.html', [
            'formError' => $formError
        ]);
    }

    /**
     * Affiche la page du compte
     */
    public function accountAction()
    {
        $articles = Articles::getByUser($_SESSION['user']['id']);

        View::renderTemplate('User/account.html', [
            'articles' => $articles
        ]);
    }

    /*
     * Fonction privée pour enregister un utilisateur
     */
    private function register($data)
    {
        try {
            // Generate a salt, which will be applied to the during the password
            // hashing process.
            $salt = Hash::generateSalt(32);

            $userID = \App\Models\User::createUser([
                "email" => $data['email'],
                "username" => $data['username'],
                "password" => Hash::generate($data['password'], $salt),
                "salt" => $salt
            ]);

            return $userID;

        } catch (Exception $ex) {
            // TODO : Set flash if error : utiliser la fonction en dessous
            /* Utility\Flash::danger($ex->getMessage());*/
        }
    }

    private function login($data){
        try {
            if(!isset($data['email'])){
                throw new Exception('TODO');
            }

            $user = \App\Models\User::getByLogin($data['email']);

            if (Hash::generate($data['password'], $user['salt']) !== $user['password']) {
                return false;
            }

            // TODO: Create a remember me cookie if the user has selected the option
            // to remained logged in on the login form.
            // https://github.com/andrewdyer/php-mvc-register-login/blob/development/www/app/Model/UserLogin.php#L86

            $_SESSION['user'] = array(
                'id' => $user['id'],
                'username' => $user['username'],
            );

            return true;

        } catch (Exception $ex) {
            // TODO : Set flash if error
            /* Utility\Flash::danger($ex->getMessage());*/
        }
    }

    private function validateFormLogin(array $data): void
    {
        $this->validateEmail($data['email'] ?? null);
        $this->validatePassword($data['password'] ?? null);
    }

    private function validateRegisterForm(array $data): void
    {
        $this->validateUsername($data['username'] ?? null);
        $this->validateEmail($data['email'] ?? null);
        $this->validatePassword($data['password'] ?? null);
        $this->validatePasswordMatch($data['password'] ?? null, $data['password-check'] ?? null);
    }

    private function validateEmail(?string $email): void
    {
        $violations = Validation::createValidatorBuilder()
            ->getValidator()
            ->validate($email, [
                new Assert\NotBlank(
                    message: 'L email est obligatoire'
                ),
                new Assert\Regex(
                    pattern: RegexEnum::EMAIL,
                    message: 'L email n est pas valide'
                ),
                new Assert\Regex(
                    pattern: '/^[^<>{}"\'`;]*$/',
                    message: 'L email contient des caracteres non autorises'
                )
            ]);

        if (count($violations) > 0) {
            throw new InvalidArgumentException($violations[0]->getMessage());
        }
    }

    private function validateUsername(?string $username): void
    {
        $violations = Validation::createValidatorBuilder()
            ->getValidator()
            ->validate($username, [
                new Assert\NotBlank(
                    message: 'Le nom d utilisateur est obligatoire'
                ),
                new Assert\Regex(
                    pattern: '/^[\p{L}\p{M}\d _.-]+$/u',
                    message: 'Le nom d utilisateur contient des caracteres non autorises'
                ),
                new Assert\Regex(
                    pattern: '/^[^<>{}"\'`;]*$/',
                    message: 'Le nom d utilisateur contient des caracteres non autorises'
                )
            ]);

        if (count($violations) > 0) {
            throw new InvalidArgumentException($violations[0]->getMessage());
        }
    }

    private function validatePassword(?string $password): void
    {
        $violations = Validation::createValidatorBuilder()
            ->getValidator()
            ->validate($password, [
                new Assert\NotBlank(
                    message: 'Le mot de passe est obligatoire'
                ),
                new Assert\Regex(
                    pattern: RegexEnum::PASSWORD,
                    message: 'Le mot de passe doit avoir 9 caracteres min, 1 majuscule, 2 chiffres et 1 special (,;:.?&*)'
                ),
                new Assert\Regex(
                    pattern: '/^[^<>{}"\'`;]*$/',
                    message: 'Le mot de passe contient des caracteres non autorises'
                )
            ]);

        if (count($violations) > 0) {
            throw new InvalidArgumentException($violations[0]->getMessage());
        }
    }

    private function validatePasswordMatch(?string $password, ?string $passwordCheck): void
    {
        $violations = Validation::createValidatorBuilder()
            ->getValidator()
            ->validate($passwordCheck, [
                new Assert\NotBlank(
                    message: 'La confirmation du mot de passe est obligatoire'
                )
            ]);

        if (count($violations) > 0) {
            throw new InvalidArgumentException($violations[0]->getMessage());
        }

        if ($password !== $passwordCheck) {
            throw new InvalidArgumentException('Les mots de passe ne correspondent pas');
        }
    }


    /**
     * Logout: Delete cookie and session. Returns true if everything is okay,
     * otherwise turns false.
     * @access public
     * @return boolean
     * @since 1.0.2
     */
    public function logoutAction() {

        /*
        if (isset($_COOKIE[$cookie])){
            // TODO: Delete the users remember me cookie if one has been stored.
            // https://github.com/andrewdyer/php-mvc-register-login/blob/development/www/app/Model/UserLogin.php#L148
        }*/
        // Destroy all data registered to the session.

        $_SESSION = array();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();

        header ("Location: /");

        return true;
    }

}
