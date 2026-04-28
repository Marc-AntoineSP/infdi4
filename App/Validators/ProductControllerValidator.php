<?php

declare(strict_types=1);

namespace App\Validators;

use App\Utility\RegexEnum;
use InvalidArgumentException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class ProductControllerValidator {
    public static function validateMessage(?string $message): void
    {
        $violations = Validation::createValidatorBuilder()
            ->getValidator()
            ->validate($message, [
                new Assert\NotBlank(
                    message: 'Veuillez entrer un message'
                ),
                new Assert\Regex(
                    pattern: RegexEnum::FRENCH_STRING,
                    message: 'Le message contient des caracteres non autorises'
                ),
                new Assert\Length(
                    max: 2000,
                    maxMessage: 'Le message est trop long'
                )
            ]);

        if ($violations->count() > 0) {
            throw new InvalidArgumentException($violations[0]->getMessage());
        }
    }
}
