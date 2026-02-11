<?php
require_once 'BaseController.php';

class OrderController extends BaseController
{
    private $mysqli;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function payment(array $input)
    {
        $id_order = (int)($input['order_id']);
        $status = $input['status'];
        $id_payment = ($status === 'success') ? 2 : 3;

        $stmt = $this->mysqli->prepare("UPDATE orders SET id_status_payment = ? WHERE id_order = ?");
        $stmt->bind_param('ii', $id_payment, $id_order);
        $stmt->execute();

        $this->sendNoContent();
    }

    public function deleteOrder()
    {
        $id_order = (int)$_GET['order_id'];

        $stmt = $this->mysqli->prepare("SELECT id_status_payment FROM orders WHERE id_order = ?");
        $stmt->bind_param('i', $id_order);
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$order = $result->fetch_assoc()) {
            $this->sendNotFound('Order not found');
            return;
        }

        if ($order['id_status_payment'] == 2) {
            http_response_code(418);
            echo json_encode(['status' => 'was payed']);
            return;
        }

        $deleteStmt = $this->mysqli->prepare("DELETE FROM orders WHERE id_order = ?");
        $deleteStmt->bind_param('i', $id_order);

        if ($deleteStmt->execute()) {
            $this->sendSuccess(['status' => 'success']);
        } else {
            $this->sendBadRequest('Ошибка отмены записи');
        }
    }
}
