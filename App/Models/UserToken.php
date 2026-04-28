<?php

namespace App\Models;

use App\Utility\ApplicationEnum;
use Core\Model;
use PDO;
use Random\RandomException;

class UserToken extends Model {
    /**
     * @throws RandomException
     */
    public static function createUserToken(int $userId): string
    {
        $db = static::getDB();

        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 day'));

        $hashedToken = hash('sha256', $token);

        $stmt = $db->prepare(
            '
                    INSERT INTO user_tokens(token, expires_at, user_id)
                    VALUES (:token, :expires_at, :user_id)
                   '
        );

        $stmt->bindParam(ApplicationEnum::TOKEN_PARAM, $hashedToken);
        $stmt->bindParam(ApplicationEnum::EXPIRES_AT_PARAM, $expires_at);
        $stmt->bindParam(ApplicationEnum::USER_ID_PARAM, $userId);

        $stmt->execute();

        return $token;
    }

    public static function getByToken(string $token): ?array
    {
        $db = static::getDB();

        $stmt = $db->prepare(
            '
                    SELECT * FROM user_tokens WHERE token = :token
                    AND expires_at > NOW()
                   '
        );

        $tokenToHash = hash('sha256', $token);

        $stmt->bindParam(ApplicationEnum::TOKEN_PARAM, $tokenToHash);

        $stmt->execute();

        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if($res === false){
            return null;
        }
        return $res;
    }

    public static function invalidateByToken(string $token): void
    {
        $db = static::getDB();
        $dbToken = hash('sha256', $token);
        $stmt = $db->prepare('DELETE FROM user_tokens WHERE token = :token');
        $stmt->bindParam(ApplicationEnum::TOKEN_PARAM, $dbToken);
        $stmt->execute();
    }

    public static function cleanup(): void
    {
        $db = static::getDB();
        $db->exec('DELETE FROM user_tokens WHERE expires_at < NOW()');
    }
}
