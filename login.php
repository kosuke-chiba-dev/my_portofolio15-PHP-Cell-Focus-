<!DOCTYPE html>
<html lang="ja">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>ログインする</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/destyle.css@1.0.15/destyle.css" />
    <link rel="stylesheet" type="text/css" href="css/style.css" />
</head>
<?php
require_once dirname(__DIR__) . '/env.php';

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
if (!$db) {
    die($db->error);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST'):
    $errors = [
        'email' => '',
        'password' => '',
    ];
    $form = [
        'id' => '',
        'name' => '',
        'email' => '',
        'password' => '',
    ];
    $form['email'] = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $form['password'] = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

    if ($form['email'] === '') {
        $errors['email'] = 'blank';
    } else {
        $stmt = $db->prepare('SELECT COUNT(*) FROM members WHERE email = ?');
        if (!$stmt) {
            $db->error;
        }
        $stmt->bind_param('s', $form['email']);
        $success = $stmt->execute();
        if (!$success) {
            die($db->error);
        }
        $stmt->bind_result($cnt);
        $stmt->fetch();
        if ($cnt !== 1) {
            $errors['email'] = 'nothing';
        }
        $stmt->close();
    }

    if ($form['password'] === '') {
        $errors['password'] = 'blank';
    } else {
        $stmt = $db->prepare('SELECT password FROM members WHERE email = ?');
        if (!$stmt) {
            die($db->error);
        }
        $stmt->bind_param('s', $form['email']);
        $success = $stmt->execute();
        if (!$success) {
            die($db->error);
        }
        $stmt->bind_result($hashed_password);
        $stmt->fetch();
        if (!password_verify($form['password'], $hashed_password)) {
            $errors['password'] = 'nothing';
        }
        $stmt->close();
    }

    if ($errors['email'] === '' && $errors['password'] === '') {
        $stmt = $db->prepare('SELECT id, name, picture FROM members WHERE email = ?');
        if (!$stmt) {
            die($db->error);
        }
        $stmt->bind_param('s', $form['email']);
        $stmt->execute();
        $stmt->bind_result($id, $name, $picture);
        $stmt->fetch();
        $form['id'] = $id;
        $form['name'] = $name;
        $form['picture'] = $picture;
        $stmt->close();
        $_SESSION['member'] = $form;
        header('Location: index.php');
        exit();
    }
endif;
?>

<body class="auth">
    <div class="container auth-container">
        <header class="header auth-header">
            <h1 class="logo">ログイン</h1>
        </header>
        <div class="content auth-content inner">
            <div class="auth-lead card">
                <p class="lead-title">メールアドレスとパスワードを記入してログインしてください。</p>
                <p class="lead-link__title">入会手続きがまだの方はこちらからどうぞ。</p>
                <p class="lead-link">&raquo;<a href="join/">入会手続きをする</a></p>
            </div>
            <form action="" method="post">
                <dl class="auth-fields">
                    <dt>メールアドレス</dt>
                    <dd class="auth-fields__control">
                        <input class="input" type="text" name="email" size="35" maxlength="255" value="<?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST'):
                            echo htmlspecialchars($form['email'], ENT_QUOTES);
                        endif;
                        ?>" />
                        <?php if (isset($errors) && ($errors['email'] === 'blank' || $errors['password'] === 'blank')): ?>
                            <p class="error">* メールアドレスとパスワードをご記入ください</p>
                        <?php endif; ?>
                        <?php if (isset($errors) && ($errors['email'] === 'nothing' || $errors['password'] === 'nothing')): ?>
                            <p class="error">* ログインに失敗しました。正しくご記入ください。</p>
                        <?php endif; ?>
                    </dd>

                    <dt>パスワード</dt>
                    <dd class="auth-fields__control">
                        <input class="input" type="password" name="password" size="35" maxlength="255" value="<?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST'):
                            echo htmlspecialchars($form['password'], ENT_QUOTES);
                        endif;
                        ?>" />
                    </dd>
                </dl>
                <div class="form-actions">
                    <input class="btn btn--primary btn--block" type="submit" value="ログインする" />
                </div>
            </form>
        </div>
    </div>
</body>

</html>