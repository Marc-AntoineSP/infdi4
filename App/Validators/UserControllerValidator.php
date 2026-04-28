<?php

declare(strict_types=1);

namespace App\Validators;

use App\Utility\RegexEnum;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use InvalidArgumentException;

class UserControllerValidator
{
    public static function validateEmail(?string $email): void
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
                    pattern: RegexEnum::PASSWORD_UNAUTHORIZED,
                    message: 'L email contient des caracteres non autorises'
                )
            ]);

        if (count($violations) > 0) {
            throw new InvalidArgumentException($violations[0]->getMessage());
        }
    }

    public static function validateUsername(?string $username): void
    {
        $violations = Validation::createValidatorBuilder()
            ->getValidator()
            ->validate($username, [
                new Assert\NotBlank(
                    message: 'Le nom d utilisateur est obligatoire'
                ),
                new Assert\Regex(
                    pattern: RegexEnum::USERNAME_UNAUTHORIZED,
                    message: 'Le nom d utilisateur contient des caracteres non autorises'
                ),
                new Assert\Regex(
                    pattern: RegexEnum::PASSWORD_UNAUTHORIZED,
                    message: 'Le nom d utilisateur contient des caracteres non autorises'
                )
            ]);

        if (count($violations) > 0) {
            throw new InvalidArgumentException($violations[0]->getMessage());
        }
    }

    public static function validatePasswordMatch(?string $password, ?string $passwordCheck): void
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

    public static function validatePassword(?string $password): void
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
                    pattern: RegexEnum::PASSWORD_UNAUTHORIZED,
                    message: 'Le mot de passe contient des caracteres non autorises'
                )
            ]);

        if (count($violations) > 0) {
            throw new InvalidArgumentException($violations[0]->getMessage());
        }
    }
}
