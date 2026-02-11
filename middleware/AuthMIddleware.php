<?php
class AuthMiddleware
{
    private $mysqli;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * Выполняет проверку авторизации и вызывает callback если успешно
     */
    public function handle(callable $next)
    {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            exit();
        }
        $auth = $headers['Authorization'];
        if (strpos($auth, 'Bearer ') !== 0) {
            http_response_code(401);
            echo json_encode(['message' => 'Неверный формат токена']);
            exit();
        }

        $token = substr($auth, 7);
        $stmt = $this->mysqli->prepare("
            SELECT ut.id_user, u.id_role
            FROM user_tokens ut
            JOIN users u USING(id_user)
            WHERE ut.token = ? AND ut.expires_at > NOW()
        ");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $_SERVER['AUTH_USER_ID'] = $row['id_user'];
            $_SERVER['AUTH_ROLE_ID'] = $row['id_role'];
            $next();
            exit();
        } else {
            http_response_code(401);
            echo json_encode(['message' => 'Неверный или истекший токен']);
        }
    }

    public static function isAdmin()
    {
        return isset($_SERVER['AUTH_ROLE_ID']) && $_SERVER['AUTH_ROLE_ID'] == 2;
    }
}
