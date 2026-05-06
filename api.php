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
    case 'file':
        checkAuth($pdo);
        handleFileUpload($pdo, $method);
        break;
    case 'public_images':
        handleImagelist();
        break;
    case 'messages':
        handleUserMessage($pdo, $method, $input);
        break;

    default:
        echo json_encode(["error" => "Ismeretlen útvonal"]);
        break;
}

function handleUserMessage($pdo, $method, $input)
{
    if ($method === 'GET') {
        try {
            $stmt = $pdo->prepare("SELECT sender, content, created_at FROM uzenetek ORDER BY created_at DESC");
            $stmt->execute();
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($messages);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Hiba az üzenetek lekérésekor: " . $e->getMessage()]);
        }
    }
    elseif ($method === 'POST') {
        $senderEmail = getOptionalUser($pdo);
        $displayName = ($senderEmail !== null) ? $senderEmail : "Anonymous";

        $content = $input['message'] ?? '';

        if (empty($content)) {
            echo json_encode(["error" => "Az üzenet nem lehet üres!"]);
            return;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO uzenetek (sender, content) VALUES (?, ?)");
            $stmt->execute([$displayName, $content]);

            echo json_encode([
                "status" => "Üzenet elküldve",
                "sent_as" => $displayName
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Adatbázis hiba: " . $e->getMessage()]);
        }
    }
}

function handleImageList()
{
    $uploadDir = 'uploads/';
    $images = [];

    if (is_dir($uploadDir)) {
        $files = scandir($uploadDir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'];
                $projectPath = dirname($_SERVER['PHP_SELF']);

                $images[] = [
                    "name" => $file,
                    "url" => $protocol . $host . $projectPath . '/' . $uploadDir . $file
                ];
            }
        }
    }

    echo json_encode($images);
}

function getOptionalUser($pdo)
{
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? '';

    if (empty($token)) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT email FROM users WHERE token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    return $user ? $user['email'] : null;
}

function handleFileUpload($pdo, $method)
{
    if ($method == "POST") {
        if (!isset($_FILES['image'])) {
            echo json_encode(["error" => "Nincs fájl kiválasztva (kulcs: image)"]);
            return;
        }

        $file = $_FILES['image'];
        $maxSize = 1 * 1024 * 1024; // 1 MB bájtban
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if ($file['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode(["error" => "A fájl túl nagy! Maximum 1MB engedélyezett."]);
            return;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedTypes)) {
            http_response_code(400);
            echo json_encode(["error" => "Csak képfájlok (JPG, PNG, GIF, WEBP) engedélyezettek."]);
            return;
        }

        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . "_" . basename($file['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            echo json_encode([
                "status" => "Sikeres feltöltés",
                "url" => $targetPath
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Hiba történt a fájl mentésekor."]);
        }
    } else {
        echo json_encode(["error" => "Csak POST metodus engedelyezett"]);
    }
}

function handleUsers($pdo, $method, $input)
{
    $action = $_GET['action'] ?? '';

    if ($method === 'POST' && $action === 'register') {
        $email = $input['email'];
        $password = password_hash($input['password'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->execute([$email, $password]);
        echo json_encode(["status" => "Sikeres regisztráció"]);
    } elseif ($method === 'POST' && $action === 'login') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$input['email']]);
        $user = $stmt->fetch();

        if ($user && password_verify($input['password'], $user['password'])) {

            $token = bin2hex(random_bytes(16));

            $update = $pdo->prepare("UPDATE users SET token = ? WHERE id = ?");
            $update->execute([$token, $user['id']]);

            echo json_encode([
                "status" => "Sikeres bejelentkezés",
                "token" => $token
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["error" => "Hibás adatok"]);
        }
    }
}

function checkAuth($pdo)
{
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