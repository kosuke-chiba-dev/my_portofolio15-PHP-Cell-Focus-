<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>会員登録</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/destyle.css@1.0.15/destyle.css" />
    <link rel="stylesheet" type="text/css" href="../css/style.css" />
</head>

<body>
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

    $errors = [
        'name' => '',
        'email' => '',
        'password' => '',
        'image' => '',
    ];


    if (isset($_GET['action']) && $_GET['action'] === 'rewrite' && isset($_SESSION['member'])) {
        $member = $_SESSION['member'];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $member = $_POST;

        if (empty($member['name'])):
            $errors['name'] = 'blank';
        endif;

        if (empty($member['email'])):
            $errors['email'] = 'blank';
        else:
            $stmt = $db->prepare('SELECT COUNT(*) FROM members WHERE email = ?');
            if (!$stmt):
                die($db->error);
            endif;
            $stmt->bind_param('s', $member['email']);
            $success = $stmt->execute();
            if (!$success):
                die($db->error);
            endif;
            $stmt->bind_result($count);
            $stmt->fetch();
            if ($count > 0) {
                $errors['email'] = 'already';
            }
        endif;


        if (empty($member['password'])):
            $errors['password'] = 'blank';
        elseif (mb_strlen($member['password']) <= 3):
            $errors['password'] = 'count';
        endif;



        $image = $_FILES['image'];
        if ($image['name'] !== '' && $image['error'] === 0):
            $type = mime_content_type($image['tmp_name']);
            if ($type !== 'image/png' && $type !== 'image/jpeg'):
                $errors['image'] = 'type';
            endif;
        endif;

        if (empty($errors['name']) && empty($errors['email']) && empty($errors['password'] && empty($errors['image']))):
            $_SESSION['member'] = $_POST;

            if ($image['name'] !== ''):
                $filename = date('YmdHis') . '_' . $image['name'];
                if (!move_uploaded_file($image['tmp_name'], '../member_picture/' . $filename)):
                    die('ファイルのアップロードに失敗しました。');
                endif;
                $_SESSION['member']['image'] = $filename;
            endif;
            header('Location: check.php');
        endif;
    }

    ?>
    <div>
        <header class="header">
            <h1 class="logo">会員登録</h1>
        </header>
        <div class="inner">
            <p class="lead-title">次のフォームに必要事項をご記入ください。</p>
            <form action="" method="post" enctype="multipart/form-data" class="form-signup">
                <dl>
                    <dt>ニックネーム <span class="required">必須</span></dt>
                    <dd class="form-dd">
                        <input class="input" type="text" name="name" size="35" maxlength="255" value="<?php
                        if (($_SERVER['REQUEST_METHOD'] === 'POST' || (isset($_GET['action']) && $_GET['action'] === 'rewrite')) && $errors['name'] === ''):
                            echo htmlspecialchars($member['name'], ENT_QUOTES);
                        endif;
                        ?>" />
                        <?php if ($errors['name'] === 'blank'): ?>
                            <p class="error">* ニックネームを入力してください</p>
                        <?php endif; ?>
                    </dd>
                    <dt>メールアドレス <span class="required">必須</span></dt>
                    <dd class="form-dd">
                        <input class="input" type="text" name="email" size="35" maxlength="255" value="<?php
                        if (($_SERVER['REQUEST_METHOD'] === 'POST' || (isset($_GET['action']) && $_GET['action'] === 'rewrite')) && $errors['email'] === ''):
                            echo htmlspecialchars($member['email'], ENT_QUOTES);
                        endif;
                        ?>" />
                        <?php if ($errors['email'] === 'blank'): ?>
                            <p class="error">* メールアドレスを入力してください</p>
                        <?php endif; ?>
                        <?php if ($errors['email'] === 'already'): ?>
                            <p class="error">* 指定されたメールアドレスはすでに登録されています</p>
                        <?php endif; ?>
                    <dt>パスワード <span class="required">必須</span></dt>
                    <dd class="form-dd">
                        <input class="input" type="password" name="password" size="10" maxlength="20" value="" />
                        <?php if ($errors['password'] === 'blank'): ?>
                            <p class="error">* パスワードを入力してください</p>
                        <?php endif; ?>
                        <?php if ($errors['password'] === 'count'): ?>
                            <p class="error">* パスワードは4文字以上で入力してください</p>
                        <?php endif; ?>
                    </dd>
                    <dt>写真など <span class="optional">任意</span></dt>
                    <dd class="form-dd">
                        <input type="file" name="image" size="35" value="" />
                        <?php if ($errors['image'] === 'type'): ?>
                            <p class="error">* 写真などは「.png」または「.jpg」の画像を指定してください</p>
                            <p class="error">* 恐れ入りますが、画像を改めて指定してください</p>
                        <?php endif; ?>
                    </dd>
                </dl>
                <div class="form-actions">
                    <input class="btn" type="submit" value="入力内容を確認する" />
                </div>
            </form>
            <p class="lead-link">&raquo;<a href="../login.php">ログインする</a></p>
        </div>
</body>

</html>