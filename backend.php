<?php
header('Content-Type: application/xml');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit;
}

$servername = "localhost";
$username = "user";
$password = "password";
$dbname = "todos";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getTasks();
        break;
    case 'POST':
        addTask();
        break;
    case 'PUT':
        updateTask();
        break;
    case 'DELETE':
        deleteTask();
        break;
    default:
        header("HTTP/1.1 405 Method Not Allowed");
        header("Allow: GET, POST, PUT, DELETE");
        break;
}

function getTasks() {
    global $conn;

    $sql = "SELECT * FROM tasks";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $tasks = array();

        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }

        generateXMLResponse($tasks);
    } else {
        generateXMLResponse(array());
    }
}

function addTask() {
    global $conn;

    $title = $_POST['title'];
    $description = $_POST['description'];

    $sql = "INSERT INTO tasks (title, description) VALUES ('$title', '$description')";
    $result = $conn->query($sql);

    if ($result) {
        $id = $conn->insert_id;
        $task = array('id' => $id, 'title' => $title, 'description' => $description);

        generateXMLResponse($task);
    } else {
        header("HTTP/1.1 500 Internal Server Error");
    }
}

function updateTask() {
    global $conn;

    parse_str(file_get_contents("php://input"), $putData);
    $id = $putData['id'];
    $title = $putData['title'];
    $description = $putData['description'];

    $sql = "UPDATE tasks SET title='$title', description='$description' WHERE id=$id";
    $result = $conn->query($sql);

    if ($result) {
        $task = array('id' => $id, 'title' => $title, 'description' => $description);

        generateXMLResponse($task);
    } else {
        header("HTTP/1.1 500 Internal Server Error");
    }
}

function deleteTask() {
    global $conn;

    parse_str(file_get_contents("php://input"), $deleteData);
    $id = $deleteData['id'];

    $sql = "DELETE FROM tasks WHERE id=$id";
    $result = $conn->query($sql);

    if ($result) {
        generateXMLResponse(array());
    } else {
        header("HTTP/1.1 500 Internal Server Error");
    }
}

function generateXMLResponse($data) {
    $xmlDoc = new DOMDocument('1.0', 'UTF-8');
    $rootElement = $xmlDoc->createElement('tasks');
    $xmlDoc->appendChild($rootElement);

    foreach ($data as $item) {
        $taskElement = $xmlDoc->createElement('task');

        foreach ($item as $key => $value) {
            $element = $xmlDoc->createElement($key, $value);
            $taskElement->appendChild($element);
        }

        $rootElement->appendChild($taskElement);
    }

    $xmlDoc->formatOutput = true;
    $xmlString = $xmlDoc->saveXML();

    echo $xmlString;
}

$conn->close();
?>
