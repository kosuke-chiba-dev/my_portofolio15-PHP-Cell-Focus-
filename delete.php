<?php
require_once dirname(__DIR__) . '/env.php'; 

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('DB connect failed: ' . $e->getMessage());
    http_response_code(500);
    exit('サーバ内部エラー');
}


$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);


$stmt = $db->prepare('DELETE FROM posts WHERE id = ?');
if (!$stmt) {
    die($db->error);
}
$stmt->bind_param('i', $id);
$success = $stmt->execute();
if (!$success) {
    die($db->error);
}
$stmt->close();

header('Location: index.php');
exit();
?>