<?php
require_once dirname(__DIR__) . '/../env.php';

session_start();
session_regenerate_id();

if (!isset($_SESSION['member'])) {
    header('Location: ../login.php');
    exit();
}

function h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

$loginMember = $_SESSION['member'];

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('DB connect failed: ' . $e->getMessage());
    http_response_code(500);
    exit('サーバ内部エラー');
}



$stmt = $db->prepare('SELECT id, name, picture FROM members WHERE id = ?');
if (!$stmt) {
    die($db->error);
}
$stmt->bind_param('i', $loginMember['id']);
$stmt->execute();
$res = $stmt->get_result();
$current = $res->fetch_assoc();
$stmt->close();

if (!$current) {
    http_response_code(404);
    exit('会員が見つかりません。');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = filter_input(INPUT_POST, 'name');
    if ($name === null)
        $name = '';
    $name = trim($name);

    if ($name === '') {
        $error = 'ニックネームを入力してください。';
    }


    $uploadDir = dirname(__DIR__) . '/member_picture';
    $oldFileName = (string) ($current['picture'] ?? '');
    $newFileName = $oldFileName;
    $newFileUploaded = false;

    if (!$error && isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['image']['tmp_name']);
            $ext = '';

            if ($mime === 'image/jpeg')
                $ext = 'jpg';
            elseif ($mime === 'image/png')
                $ext = 'png';

            if ($ext === '') {
                $error = 'jpg/pngの画像を選択してください。';
            } else {

                $newFileName = date('YmdHis') . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
                $destPath = $uploadDir . '/' . $newFileName;

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
                    $error = '画像を保存できませんでした。';
                } else {
                    $newFileUploaded = true;
                }
            }
        } else {
            $error = '画像アップロードに失敗しました。';
        }
    }

    if (!$error) {

        $stmt = $db->prepare('UPDATE members SET name = ?, picture = ? WHERE id = ?');
        if (!$stmt) {
            die($db->error);
        }
        $stmt->bind_param('ssi', $name, $newFileName, $loginMember['id']);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {

            if ($newFileUploaded) {
                $tmp = $uploadDir . '/' . basename($newFileName);
                if (is_file($tmp)) {
                    @unlink($tmp);
                }
            }
            $error = '更新に失敗しました。';
        } else {

            $old = basename($oldFileName);
            if ($newFileUploaded && $old !== '' && $old !== 'default-icon.png' && $old !== $newFileName) {
                $oldPath = $uploadDir . '/' . $old;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }



            $_SESSION['member']['name'] = $name;
            $_SESSION['member']['picture'] = $newFileName;

            header('Location: ./profile.php?id=' . (int) $loginMember['id']);
            exit();
        }
    }
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
        <a href="../index.php" class="logo top_return_link">Cell Focus</a>
        <nav class="header-right">
            <button class="current_user_icon" type="button">
                <img src="../member_picture/<?php echo h($current['picture']); ?>" alt="プロフィール画像">
            </button>
            <div class="toggleArea">
                <a class="btn" href="./profile.php?id=<?php echo (int) $loginMember['id']; ?>">プロフィール</a>
                <a class="btn" href="../logout.php">ログアウト</a>
            </div>
        </nav>
    </header>

    <div class="inner">
        <?php if ($error): ?>
            <p class="error error-profile__edit"><?php echo h($error); ?></p>
        <?php endif; ?>
        <h2 class="profile_title">プロフィール編集ページ</h2>

        <div class="profile_image">
            <p>現在の画像：</p>
            <p>
                <?php if (!empty($current['picture'])): ?>
                    <img src="../member_picture/<?php echo h($current['picture']); ?>" alt="現在の画像">
                <?php endif; ?>
            </p>
        </div>

        <form action="" method="post" enctype="multipart/form-data" class="form_edit">
            <div class="form_edit_row">
                <label for="name">ニックネーム</label><br>
                <input id="name" class="input" type="text" name="name" value="<?php echo h($current['name']); ?>">
            </div>

            <div class="form_edit_row">
                <label for="image">アイコン画像 <span class="optional">任意</span></label><br>
                <input id="image" type="file" name="image" accept="image/*">
            </div>

            <div class="form_edit_row">
                <input type="submit" value="更新する" class="btn" />
            </div>
        </form>

        <div class="profile_bottom">
            <a href="./profile.php?id=<?php echo (int) $loginMember['id']; ?>" class="profile_action_link">プロフィールに戻る</a>
        </div>
    </div>
    <script src="../js/script.js"></script>
</body>

</html>