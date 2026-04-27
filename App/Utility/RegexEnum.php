<?php

namespace App\Utility;

final class RegexEnum
{
    public const string EMAIL = "/^[\w\-\.\+]+\@[a-zA-Z0-9\.\-]+\.[a-zA-z0-9]{2,4}$/";
    public const string FRENCH_STRING = "/^[\p{L}\p{M}\d ',;:_’-]+$/u";
    public const string PASSWORD = "/^(?=.*[A-Z])(?=(?:.*\d){2,})(?=.*[,;:\.\?&\*])[A-Za-z\d,;:\.\?&\*]{9,}$/";
}
