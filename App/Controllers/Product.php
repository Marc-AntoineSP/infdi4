<?php

namespace App\Controllers;

use App\Models\Articles;
use App\Utility\ApplicationEnum;
use App\Utility\ErrorMessageEnum;
use App\Utility\RegexEnum;
use App\Utility\Upload;
use App\Validators\ProductControllerValidator;
use Core\Controller;
use Core\View;
use InvalidArgumentException;
use LogicException;
use Random\RandomException;
use RuntimeException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Throwable;

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
        $formData = [
            'name' => '',
            'description' => '',
            'id_city' => '',
        ];
        $formError = null;
        $csrfToken = '';

        try {
            $csrfToken = $this->getCsrfToken();
        } catch (Throwable $e) {
            $formError = ErrorMessageEnum::CSRF_GENERATION_ERROR;
            $this->addDangerFlash($formError);
        }

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

                $this->addSuccessFlash('Produit enregistre avec succes');
                header(ApplicationEnum::HEADER_LOCATION.'/product/' . $id);
                return;
            } catch (InvalidArgumentException $e) {
                $formError = $e->getMessage();
                $this->addWarningFlash($formError);
            } catch (Throwable $e) {
                $formError = $e->getMessage() !== ''
                    ? $e->getMessage()
                    : ErrorMessageEnum::PRODUCT_UPLOAD_ERROR;
                $this->addDangerFlash($formError);
            }
        }

        View::renderTemplate('Product/Add.html', $this->withSonner([
            'formData' => $formData,
            'formError' => $formError,
            'csrfToken' => $csrfToken,
        ]));
    }

    /**
     * Affiche la page d'un produit
     * @return void
     */
    public function showAction(): void
    {
        $id = (int) ($this->route_params['id'] ?? 0);
        $csrfToken = '';
        $contactError = $_SESSION['contact_error'] ?? null;
        $contactSuccess = $_SESSION['contact_success'] ?? null;
        $contactMessage = $_SESSION['contact_message'] ?? '';

        unset($_SESSION['contact_error'], $_SESSION['contact_success'], $_SESSION['contact_message']);

        if ($id <= 0) {
            $this->addWarningFlash(ErrorMessageEnum::ARTICLE_NOT_FOUND);
            View::renderTemplate('404.html', $this->withSonner());
            return;
        }

        try {
            $csrfToken = $this->getCsrfToken();
        } catch (Throwable $e) {
            $this->addDangerFlash(ErrorMessageEnum::CSRF_GENERATION_ERROR);
        }

        $suggestions = [];
        $article = null;

        try {
            Articles::addOneView($id);
            $suggestions = Articles::getSuggest();
            $articleRows = Articles::getOne($id);
            if (!isset($articleRows[0]) || !is_array($articleRows[0])) {
                throw new InvalidArgumentException(ErrorMessageEnum::ARTICLE_NOT_FOUND);
            }
            $article = $articleRows[0];
        } catch (InvalidArgumentException $e) {
            $this->addWarningFlash($e->getMessage());
            View::renderTemplate('404.html', $this->withSonner());
            return;
        } catch (Throwable $e) {
            $this->addDangerFlash(ErrorMessageEnum::PRODUCT_LOAD_ERROR);
            View::renderTemplate('500.html', $this->withSonner());
            return;
        }

        View::renderTemplate('Product/Show.html', $this->withSonner([
            'article' => $article,
            'suggestions' => $suggestions,
            'articleId' => $id,
            'csrfToken' => $csrfToken,
            'contactError' => $contactError,
            'contactSuccess' => $contactSuccess,
            'contactMessage' => $contactMessage,
        ]));
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
                    new Assert\NotBlank(message: ErrorMessageEnum::PICTURE_NOT_BLANK),
                    new Assert\Type(type: 'string'),
                ],
                'tmp_name' => [
                    new Assert\NotBlank(message: ErrorMessageEnum::PICTURE_UPLOAD_ERROR),
                    new Assert\File(
                        maxSize: '4M',
                        mimeTypes: ['image/jpeg', 'image/png'],
                        maxSizeMessage: ErrorMessageEnum::PICTURE_TOO_LARGE,
                        mimeTypesMessage: ErrorMessageEnum::PICTURE_MIME_TYPE_ERROR,
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
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => ErrorMessageEnum::PICTURE_TOO_LARGE,
            UPLOAD_ERR_PARTIAL => ErrorMessageEnum::PICTURE_UPLOAD_ERROR,
            UPLOAD_ERR_NO_FILE => ErrorMessageEnum::PICTURE_NOT_BLANK,
            default => ErrorMessageEnum::DEFAULT_PICTURE_UPLOAD_ERROR,
        };
    }

    private function getCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            try {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } catch (RandomException $e) {
                throw new LogicException(ErrorMessageEnum::CSRF_GENERATION_ERROR, 0, $e);
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
            throw new InvalidArgumentException(ErrorMessageEnum::INVALID_CSRF_TOKEN);
        }
    }

    public function sendContactMessageAction(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->addWarningFlash(ErrorMessageEnum::REQUEST_METHOD_INVALID);
            header(ApplicationEnum::HEADER_LOCATION.'/');
            return;
        }

        $articleId = (int)($_POST['article_id'] ?? 0);

        if ($articleId <= 0) {
            $this->addWarningFlash(ErrorMessageEnum::ARTICLE_NOT_FOUND);
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
                throw new InvalidArgumentException(ErrorMessageEnum::ARTICLE_NOT_FOUND);
            }

            unset($_SESSION['contact_message']);
            $_SESSION['contact_success'] = 'Votre message a bien ete transmis.';
            $this->addSuccessFlash('Votre message a bien ete transmis.');
        } catch (InvalidArgumentException $e) {
            $_SESSION['contact_error'] = $e->getMessage();
            $this->addWarningFlash($_SESSION['contact_error']);
        } catch (Throwable $e) {
            $_SESSION['contact_error'] = ErrorMessageEnum::MESSAGE_SEND_ERROR;
            $this->addDangerFlash($_SESSION['contact_error']);
        }

        header(ApplicationEnum::HEADER_LOCATION.'/product/' . $articleId);
    }
}
