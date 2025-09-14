<?php
require_once dirname(__DIR__) . '/../env.php';
session_start();
session_regenerate_id();


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('DB connect failed: ' . $e->getMessage());
    http_response_code(500);
    exit('サーバ内部エラー');
}

function h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {

    $postedToken = (string) ($_POST['token'] ?? '');
    if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $postedToken)) {
        http_response_code(400);
        exit('不正なリクエストです。（token）');
    }


    if (!isset($_SESSION['member'])) {
        header('Location: ../login.php');
        exit();
    }
    $userId = (int) $_SESSION['member']['id'];



    $stmt = $db->prepare('SELECT picture FROM members WHERE id = ?');
    if (!$stmt) {
        die($db->error);
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    $picture = $row['picture'] ?? '';


    $stmt = $db->prepare('DELETE FROM posts WHERE member_id = ?');
    if (!$stmt) {
        die($db->error);
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();


    $stmt = $db->prepare('DELETE FROM members WHERE id = ?');
    if (!$stmt) {
        die($db->error);
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();


    if (!empty($picture) && $picture != "default-icon.png") {
        $path = dirname(__DIR__) . '/member_picture/' . $picture;
        if (is_file($path)) {
            @unlink($path);
        }
    }


    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();


    header('Location: ../login.php');
    exit();
}


if (!isset($_SESSION['member'])) {
    header('Location: ../login.php');
    exit();
}
$loginMember = $_SESSION['member'];


if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}




$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $id = (int) $loginMember['id'];
}


$stmt = $db->prepare('SELECT id, name, picture FROM members WHERE id = ?');
if (!$stmt) {
    die($db->error);
}
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$member = $res->fetch_assoc();
$stmt->close();

if (!$member) {
    http_response_code(404);
    exit('会員が見つかりません。');
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>Cell Focus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/destyle.css@1.0.15/destyle.css" />
    <link rel="stylesheet" type="text/css" href="../css/style.css" />
</head>

<body>
    <header class="header site-header--index">
        <a class="logo top_return_link" href="../index.php">Cell Focus</a>
        <nav class="header-right">
            <button class="current_user_icon" type="button">
                <img src="../member_picture/<?php echo h($loginMember['picture']); ?>" alt="プロフィール画像">
            </button>
            <div class="toggleArea">
                <a class="btn" href="./profile.php?id=<?php echo (int) $loginMember['id']; ?>">プロフィール</a>
                <a class="btn" href="../logout.php">ログアウト</a>
            </div>
        </nav>
    </header>

    <div class="inner">
        <h2 class="profile_title">プロフィール</h2>
        <div class="profile_content">

            <p class="profile_name">ニックネーム：<?php echo h($member['name']); ?></p>
            <p class="profile_image">
                <?php if (!empty($member['picture'])): ?>
                    <img src="../member_picture/<?php echo h($member['picture']); ?>" alt="プロフィール画像">
                <?php endif; ?>
            </p>
        </div>

        <p>
            <?php if ((int) $member['id'] === (int) $loginMember['id']): ?>
                <a class="profile_action_link" href="../profile/profile_edit.php">プロフィールを編集</a> /
            <?php endif; ?>
            <a class="profile_action_link" href="../index.php">一覧に戻る</a>
        </p>

        <?php if ((int) $member['id'] === (int) $loginMember['id']): ?>
            <form action="" method="post" class="form_signout" onsubmit="return confirm('本当に退会しますか？この操作は取り消せません。');">
                <input type="hidden" name="action" value="logout">
                <input type="hidden" name="token" value="<?php echo h($_SESSION['token']); ?>">
                <input type="submit" class="btn" value="退会する" />
            </form>
        <?php endif; ?>
    </div>
    <script src="../js/script.js"></script>
</body>

</html>