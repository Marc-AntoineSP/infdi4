<?php

namespace App\Controllers;

use App\Models\Articles;
use App\Utility\ApplicationEnum;
use App\Utility\RegexEnum;
use App\Utility\Upload;
use App\Validators\ProductControllerValidator;
use Core\Controller;
use Core\View;
use Exception;
use InvalidArgumentException;
use Random\RandomException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

/**
 * Product controller
 */
class Product extends Controller
{
    /**
     * Affiche la page d'ajout
     * @return void
     */
    public function indexAction(): void
    {
        $csrfToken = $this->getCsrfToken();
        $formData = [
            'name' => '',
            'description' => '',
            'id_city' => '',
        ];
        $formError = null;

        if (isset($_POST['submit'])) {
            $formData = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'id_city' => $_POST['id_city'] ?? '',
            ];

            try {
                $this->validateCsrfToken($_POST['csrf_token'] ?? null);
                $this->addProductValidation($formData, $_FILES['picture'] ?? null);

                $payload = $formData;
                $payload['user_id'] = $_SESSION['user']['id'];
                $id = Articles::save($payload);

                $pictureName = Upload::uploadFile($_FILES['picture'], $id);
                Articles::attachPicture($id, $pictureName);

                header(ApplicationEnum::HEADER_LOCATION.'/product/' . $id);
                return;
            } catch (InvalidArgumentException $e) {
                $formError = $e->getMessage();
            } catch (Exception $e) {
                $formError = $e->getMessage() !== ''
                    ? $e->getMessage()
                    : 'Une erreur est survenue lors de l enregistrement du produit';
            }
        }

        View::renderTemplate('Product/Add.html', [
            'formData' => $formData,
            'formError' => $formError,
            'csrfToken' => $csrfToken,
        ]);
    }

    /**
     * Affiche la page d'un produit
     * @return void
     */
    public function showAction(): void
    {
        $id = $this->route_params['id'];
        $csrfToken = $this->getCsrfToken();
        $contactError = $_SESSION['contact_error'] ?? null;
        $contactSuccess = $_SESSION['contact_success'] ?? null;
        $contactMessage = $_SESSION['contact_message'] ?? '';

        unset($_SESSION['contact_error'], $_SESSION['contact_success'], $_SESSION['contact_message']);

        try {
            Articles::addOneView($id);
            $suggestions = Articles::getSuggest();
            $article = Articles::getOne($id);
        } catch (Exception $e) {
            var_dump($e);
        }

        View::renderTemplate('Product/Show.html', [
            'article' => $article[0],
            'suggestions' => $suggestions,
            'articleId' => $id,
            'csrfToken' => $csrfToken,
            'contactError' => $contactError,
            'contactSuccess' => $contactSuccess,
            'contactMessage' => $contactMessage,
        ]);
    }

    private function addProductValidation(array $data, ?array $picture): void
    {
        $this->stringValidation($data['name'] ?? null, 'Le titre');
        $this->stringValidation($data['description'] ?? null, 'La description');
        $this->stringValidation($data['id_city'] ?? null, 'La ville');
        $this->pictureValidation($picture);
    }

    private function stringValidation(?string $data, string $fieldLabel): void
    {
        $validator = Validation::createValidatorBuilder()
            ->getValidator();

        $violations = $validator->validate($data, [
            new Assert\NotBlank(
                message: sprintf('%s est obligatoire', $fieldLabel)
            ),
            new Assert\Length(
                max: 255,
                maxMessage: sprintf('%s est trop long', $fieldLabel)
            ),
            new Assert\Regex(
                pattern: RegexEnum::FRENCH_STRING,
                message: sprintf('%s contient des caracteres non autorises', $fieldLabel)
            ),
        ]);

        if ($violations->count() > 0) {
            throw new InvalidArgumentException($violations[0]->getMessage());
        }
    }

    private function pictureValidation(?array $data): void
    {
        if ($data === null || !isset($data['error'])) {
            throw new InvalidArgumentException('La photo est obligatoire');
        }

        if ((int)$data['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException($this->getPictureUploadErrorMessage((int)$data['error']));
        }

        $validator = Validation::createValidatorBuilder()
            ->getValidator();

        $violations = $validator->validate($data, new Assert\Collection(
            fields: [
                'name' => [
                    new Assert\NotBlank(message: 'Le nom du fichier est obligatoire'),
                    new Assert\Type(type: 'string'),
                ],
                'tmp_name' => [
                    new Assert\NotBlank(message: 'Le fichier image est introuvable'),
                    new Assert\File(
                        maxSize: '4M',
                        mimeTypes: ['image/jpeg', 'image/png'],
                        maxSizeMessage: 'La photo ne doit pas depasser 4 Mo',
                        mimeTypesMessage: 'La photo doit etre au format JPG ou PNG'
                    ),
                ],
                'size' => [
                    new Assert\Type(type: 'integer'),
                    new Assert\PositiveOrZero(),
                ],
                'error' => [
                    new Assert\EqualTo(value: UPLOAD_ERR_OK),
                ],
            ],
            allowExtraFields: true,
            allowMissingFields: false,
        ));

        if ($violations->count() > 0) {
            throw new InvalidArgumentException($violations[0]->getMessage());
        }
    }

    private function getPictureUploadErrorMessage(int $uploadError): string
    {
        return match ($uploadError) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La photo ne doit pas depasser 4 Mo',
            UPLOAD_ERR_PARTIAL => 'La photo n a pas ete telechargee completement',
            UPLOAD_ERR_NO_FILE => 'La photo est obligatoire',
            default => 'Une erreur est survenue lors de l upload de la photo',
        };
    }

    private function getCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            try {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } catch (RandomException $e) {
                throw new Exception('Une erreur est survenue lors de la generation du token');
            }
        }

        return $_SESSION['csrf_token'];
    }

    private function validateCsrfToken(?string $submittedToken): void
    {
        $sessionToken = $_SESSION['csrf_token'] ?? null;

        if (
            $submittedToken === null
            || !is_string($sessionToken)
            || !hash_equals($sessionToken, $submittedToken)
        ) {
            throw new InvalidArgumentException('CSRF invalide.');
        }
    }

    public function sendContactMessageAction(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header(ApplicationEnum::HEADER_LOCATION.'/');
            return;
        }

        $articleId = (int)($_POST['article_id'] ?? 0);

        if ($articleId <= 0) {
            header(ApplicationEnum::HEADER_LOCATION.'/');
            return;
        }

        $message = trim((string)($_POST['message'] ?? ''));
        $_SESSION['contact_message'] = $message;

        try {
            $this->validateCsrfToken($_POST['csrf_token'] ?? null);

            ProductControllerValidator::validateMessage($message);

            $article = Articles::getOne($articleId);
            if (empty($article)) {
                throw new InvalidArgumentException('Article introuvable');
            }

            unset($_SESSION['contact_message']);
            $_SESSION['contact_success'] = 'Votre message a bien ete transmis.';
        } catch (InvalidArgumentException $e) {
            $_SESSION['contact_error'] = $e->getMessage();
        } catch (Exception $e) {
            $_SESSION['contact_error'] = 'Une erreur est survenue lors de l envoi du message';
        }

        header(ApplicationEnum::HEADER_LOCATION.'/product/' . $articleId);
    }
}
