<?php
class BaseController
{
    protected function sendResponse($data, int $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    protected function sendSuccess($data)
    {
        $this->sendResponse($data, 200);
    }

    protected function sendCreate($data)
    {
        $this->sendResponse($data, 201);
    }

    protected function sendNoContent()
    {
        http_response_code(204);
        exit();
    }

    protected function sendBadRequest($msg = "Bad Request")
    {
        $this->sendResponse(['message' => $msg], 400);
    }

    protected function sendNotFound($msg = "Not Found")
    {
        $this->sendResponse(['message' => $msg], 404);
    }

    protected function sendUnauthorized($msg = "Unauthorized")
    {
        $this->sendResponse(['message' => $msg], 401);
    }

    protected function sendForbidden($msg = "Forbidden for you")
    {
        $this->sendResponse(['message' => $msg], 403);
    }

    protected function sendValidationErrors($errors, string $msg = "Invalid fields")
    {
        $this->sendResponse([
            'message' => $msg,
            'errors' => $errors
        ], 422);
    }

    public function sendServerError($msg = "Internal Server Error")
    {
        $this->sendResponse(['message' => $msg], 500);
    }
}
