<?php 
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Helpers/TimeHelper.php';
require_once __DIR__ . '/../Exceptions/AuthException.php';
require_once __DIR__ . '/../Enums/HttpStatus.php';
require_once __DIR__ . '/../Responses/BaseResponse.php';
require_once __DIR__ . '/../Responses/SimpleResponse.php';

class AuthService {

    /**
     * Login user (create session)
     * @param string $email
     * @param string $password
     * @return SimpleResponse
     */
    public static function login(string $email, string $password): SimpleResponse {
        $Response = new SimpleResponse();
        $User = User::select(['email', '=', $email, 'password', '=', self::generatePassword($password)]);
        if ($User instanceof User) {
            $User->csrf = self::generateCsrf();
            $User->last_login = date('Y-m-d H:i:s');
            $User->save();
            self::setUserSession($User);
            $Response->setSuccess(HttpStatus::OK, 'התחברת בהצלחה');
        } else {
            $Response->setError(HttpStatus::UNAUTHORIZED, 'פרטי ההתחברות שגויים');
        }
        return $Response;
    }

    /**
     * Logout user
     * @return SimpleResponse
     */
    public static function logout(): SimpleResponse {
        $Response = new SimpleResponse();
        if(session_status() !== PHP_SESSION_ACTIVE) session_start();
        if(!isset($_SESSION['User'])) {
            $Response->setError(HttpStatus::UNAUTHORIZED, 'אינך מחובר');
            return $Response;
        }
        unset($_SESSION['User']);
        session_destroy();
        $Response->setSuccess(HttpStatus::OK, 'התנתקת בהצלחה');
        return $Response;
    }

    /**
     * Get logged in user instance
     * @return User|null
     */
    public static function user(): ?User {
        if(session_status() !== PHP_SESSION_ACTIVE) session_start();
        return isset($_SESSION['User']) ? unserialize($_SESSION['User']) : null;
    }

    /**
     * Check if user is logged in and not guest
     * @return boolean
     */
    public static function check(): bool {
        if(session_status() !== PHP_SESSION_ACTIVE) session_start();
        return !empty($_SESSION['User']);
    }

    /**
     * Check if user is guest and not logged in
     * @return boolean
     */
    public static function guest(): bool {
        return !self::check();
    }

    /**
     * Generate CSRF token
     * @return string
     */
    private static function generateCsrf(): string {
        return bin2hex(random_bytes(32));
    }

    /**
     * Generate password hash
     * @param string $password
     * @return string
     */
    private static function generatePassword(string $password): string {
        return hash('sha256', $_ENV['PASSWORD_SALT'] . "#$password#");
    }

    /**
     * Set user session
     * @param User $User
     * @return boolean
     */
    public static function setUserSession(User $User): bool {
        return (bool) $_SESSION['User'] = serialize($User);
    }
}