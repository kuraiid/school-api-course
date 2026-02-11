<?php
require_once 'BaseController.php';

class UserController extends BaseController
{
    protected $mysqli;
    protected $jwtService;

    public function __construct($mysqli, $jwtService)
    {
        $this->mysqli = $mysqli;
        $this->jwtService = $jwtService;
    }

    public function registration(array $input)
    {

        if (empty($input['email']) || empty($input['password']) || empty($input['name'])) {
            $this->sendValidationErrors(['input' => 'Все поля обязательны']);
            return;
        }

        $stmt = $this->mysqli->prepare("SELECT 1 FROM users WHERE email = ?");
        $stmt->bind_param("s", $input['email']);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $this->sendBadRequest('Пользователь с такой почтой уже зарегистрирован');
            return;
        }

        $hash = password_hash($input['password'], PASSWORD_BCRYPT);
        $id_role = 1;
        $stmt = $this->mysqli->prepare("
            INSERT INTO users
            (email, name, password, id_role) VALUES (?,?,?,?);
        ");
        $stmt->bind_param("sssi", $input['email'], $input['name'], $hash, $id_role);

        $stmt->execute() ? $this->sendCreate(['success' => 'true']) : $this->sendServerError('Ошибка создания учетной записи');
    }

    public function authorization(array $input)
    {
        if (empty($input['email']) || empty($input['password'])) {
            $this->sendValidationErrors([
                ['input' => 'Email и пароль обязательны']
            ]);
            return;
        }

        $email = $input['email'];
        $stmt = $this->mysqli->prepare("SELECT id_user, name, id_role, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();


        if (!$user = $result->fetch_assoc()) {
            $this->sendBadRequest('Неверный email или пароль');
            return;
        }

        if (!password_verify($input['password'], $user['password'])) {
            $this->sendBadRequest('Неверный email или пароль');
            return;
        }

        $token = $this->jwtService->generateToken([
            'id_user' => $user['id_user'],
            'name' => $user['name'],
            'email' => $input['email']
        ]);
        $created_at = date('Y-m-d H:i:s');
        $expires_at = date('Y-m-d H:i:s', time() + 3600);

        $stmt = $this->mysqli->prepare("
            INSERT INTO user_tokens (id_user, token, created_at, expires_at) VALUES (?,?,?,?);
        ");
        $stmt->bind_param('isss', $user['id_user'], $token, $created_at, $expires_at);
        if ($stmt->execute()) {
            $this->sendSuccess(['token' => $token]);
        } else {
            $this->sendServerError($stmt->error);
        }
    }
    /*
    public function logout(){
        $headers = getallheaders();
        $auth = $headers['Authorization'];
        $token = substr($auth, 7);
    }*/
}
