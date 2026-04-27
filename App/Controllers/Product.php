<?php

namespace App\Controllers;

use App\Models\Articles;
use App\Utility\RegexEnum;
use App\Utility\Upload;
use Core\Controller;
use Core\View;
use Exception;
use InvalidArgumentException;
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
                $this->addProductValidation($formData, $_FILES['picture'] ?? null);

                $payload = $formData;
                $payload['user_id'] = $_SESSION['user']['id'];
                $id = Articles::save($payload);

                $pictureName = Upload::uploadFile($_FILES['picture'], $id);
                Articles::attachPicture($id, $pictureName);

                header('Location: /product/' . $id);
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
        ]);
    }

    /**
     * Affiche la page d'un produit
     * @return void
     */
    public function showAction(): void
    {
        $id = $this->route_params['id'];

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

        if (count($violations) > 0) {
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

        if (count($violations) > 0) {
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
}
