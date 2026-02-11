<?php
require_once 'BaseController.php';

class CourseController extends BaseController
{
    protected $mysqli;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function coursesHandler()
    {
        if (isset($_GET['course_id'])) {
            $this->listLessons();
        } else {
            $this->listCourses();
        }
    }

    public function listCourses()
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(20, max(1, (int)$_GET['limit'])) : 5;
        $offset = ($page - 1) * $limit;

        $count = $this->mysqli->query("SELECT count(*) as total FROM courses");
        $total = $count->fetch_assoc()['total'];

        $stmt = $this->mysqli->prepare("
            SELECT id, name, description, hours, img, start_date, end_date, price FROM courses
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $per_page = count($courses);

        if ($courses) {
            $this->sendSuccess([
                'data' => $courses,
                'pagination' => [
                    'total' => $total,
                    'current' => $page,
                    'per_page' => $per_page,
                ]
            ]);
        } else {
            $this->sendBadRequest('Ошибка получения курсов');
        }
    }

    public function listLessons()
    {
        $id_course = $_GET['course_id'];

        $stmt = $this->mysqli->prepare("SELECT * FROM lessons WHERE id_course = ?");
        $stmt->bind_param('i', $id_course);
        $stmt->execute();
        $lessons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if ($lessons) {
            $this->sendSuccess(['data' => $lessons]);
        } else {
            $this->sendBadRequest('Ошибка получения уроков');
        }
    }

    public function orderCourse()
    {
        $id_course = $_GET['course_id'];
        $id_user = $_SERVER['AUTH_USER_ID'];

        if (!$id_course) {
            $this->sendBadRequest('Курс не выбран ');
            return;
        }

        $stmt = $this->mysqli->prepare("SELECT name, start_date, end_date FROM courses WHERE id = ?");
        $stmt->bind_param('i', $id_course);
        $stmt->execute();
        $course = $stmt->get_result()->fetch_assoc();

        if (!$course) {
            $this->sendNotFound('Курс не найден');
            return;
        }

        $now = date('Y-m-d');
        if ($now >= $course['start_date']) {
            $this->sendBadRequest('Курс уже начался');
            return;
        }
        if ($now > $course['end_date']) {
            $this->sendBadRequest('Курс завершился');
            return;
        }

        $stmt = $this->mysqli->prepare("INSERT INTO orders (id_user, id_course, date_order) VALUES (?,?,?)");
        $stmt->bind_param("iis", $id_user, $id_course, $now);
        if ($stmt->execute()) {
            $this->sendSuccess(['pay_url' => 'https://127.0.0.1:8000/school-api/payment-webhook']);
        } else {
            $this->sendBadRequest('Ошибка записи на курс');
        }
    }
}
