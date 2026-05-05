<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: *");

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$type = $_GET['type'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($type) {
    case 'f1':
        checkAuth($pdo); 
        handleFormula1($pdo, $method, $input);
        break;

    case 'users':
        handleUsers($pdo, $method, $input);
        break;

    default:
        echo json_encode(["error" => "Ismeretlen útvonal"]);
        break;
}

function handleUsers($pdo, $method, $input) {
    $action = $_GET['action'] ?? '';

    if ($method === 'POST' && $action === 'register') {
        $email = $input['email'];
        $password = password_hash($input['password'], PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->execute([$email, $password]);
        echo json_encode(["status" => "Sikeres regisztráció"]);
    } 
    
    elseif ($method === 'POST' && $action === 'login') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$input['email']]);
        $user = $stmt->fetch();

        if ($user && password_verify($input['password'], $user['password'])) {

            $token = bin2hex(random_bytes(16));
            
            $update = $pdo->prepare("UPDATE users SET token = ? WHERE id = ?");
            $update->execute([$token, $user['id']]);
            
            echo json_encode([
                "status" => "Sikeres login",
                "token" => $token
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["error" => "Hibás adatok"]);
        }
    }
}

function checkAuth($pdo) {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? '';
    
    if (empty($token)) {
        echo json_encode(["error" => "Hiányzó token!"]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(["error" => "Érvénytelen token!"]);
        exit;
    }
    return $user['id'];
}

function handleFormula1($pdo, $method, $input)
{
    $table = $_GET['table'] ?? '';

    $allowedTables = ['eredmenyek', 'pilota', 'gp'];

    $db_fields = [
        'eredmenyek' => ['datum', 'pilotaaz', 'helyezes', 'hiba', 'csapat', 'tipus', 'motor'],
        'gp' => ['datum', 'nev', 'helyszin'],
        'pilota' => ['nev', 'nem', 'szuldat', 'nemzet']
    ];

    if (!in_array($table, $allowedTables)) {
        echo json_encode(["error" => "Ervenytelen tabla"]);
        exit;
    }

    switch ($method) {
        case 'GET':
            $stmt = $pdo->prepare("SELECT * FROM $table");
            $stmt->execute();
            echo json_encode($stmt->fetchAll());
            break;

        case 'POST':
            $fields = "";
            $placeholders = "";
            $values = [];

            foreach ($input as $key => $value) {
                $fields .= ($fields == "" ? "" : ", ") . $key;
                $placeholders .= ($placeholders == "" ? "" : ", ") . "?";
                $values[] = $value;
            }

            $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            echo json_encode(["status" => "Sikeres mentés"]);
            break;

        case 'PUT':
            $idColumn = isset($input['az']) ? "az" : "id";
            $idValue = $input[$idColumn] ?? null;

            if (!$idValue) {
                echo json_encode(["error" => "Hianyzó azonosító (id vagy az)"]);
                break;
            }

            $updates = [];
            $values = [];
            $allowedColumns = $db_fields[$table];

            foreach ($input as $key => $value) {
                if (in_array($key, $allowedColumns)) {
                    $updates[] = "$key = ?";
                    $values[] = $value;
                }
            }

            if (empty($updates)) {
                echo json_encode(["error" => "Nincs frissítendő adatmező"]);
                break;
            }

            $values[] = $idValue;
            $sql = "UPDATE $table SET " . implode(', ', $updates) . " WHERE $idColumn = ?";

            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($values);
                echo json_encode([
                    "status" => "Sikeres frissítés",
                    "table" => $table,
                    "updated_id" => $idValue
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["error" => "SQL hiba: " . $e->getMessage()]);
            }

            break;

        case 'DELETE':
            $sql;
            $val;
            if (isset($_GET['az'])) {
                $sql = "DELETE FROM $table WHERE az=?";
                $val = $_GET['az'];
            } else {
                $sql = "DELETE FROM $table WHERE id=?";
                $val = $_GET['id'];
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$val]);
            echo json_encode(["status" => "Törölve"]);
            break;
    }
}


?>