<?php
require_once dirname(__DIR__) . '/env.php';
session_start();
session_regenerate_id();

if (isset($_SESSION['member'])):
    ?>
    <!DOCTYPE html>
    <html lang="ja">

    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width,initial-scale=1.0" />
        <title>Cell Focus</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/destyle.css@1.0.15/destyle.css" />
        <link rel="stylesheet" type="text/css" href="css/style.css" />
    </head>
    <?php
    $member = $_SESSION['member'];
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
        $db->set_charset('utf8mb4');
    } catch (mysqli_sql_exception $e) {
        error_log('DB connect failed: ' . $e->getMessage());
        http_response_code(500);
        exit('サーバ内部エラー');
    }


    $errors = [];
    $post = ['id' => null, 'message' => '', 'created' => ''];

    $id_post = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $id_get = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    $id = $id_post ?: $id_get;

    if (!$id) {
        http_response_code(400);
        exit('不正なアクセスです。(id)');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postedToken = (string) filter_input(INPUT_POST, 'token', FILTER_UNSAFE_RAW);
        if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $postedToken)) {
            http_response_code(400);
            exit('不正なリクエストです。(CSRF)');
        }

        $message = trim((string) filter_input(INPUT_POST, 'message', FILTER_UNSAFE_RAW));

        if ($message === '') {
            $errors[] = 'メッセージを入力してください。';
        }
        if (mb_strlen($message) > 1000) {
            $errors[] = 'メッセージは1000文字以内で入力してください。';
        }

        if (empty($errors)) {
            $stmt = $db->prepare('UPDATE posts SET message = ?, modified = NOW() WHERE id = ? AND member_id = ?');
            if (!$stmt) {
                die($db->error);
            }
            $stmt->bind_param('sii', $message, $id, $member['id']);
            $ok = $stmt->execute();
            $stmt->close();
            if (!$ok) {
                die($db->error);
            }

            header('Location: index.php');
            exit();
        }

        $stmt = $db->prepare('SELECT modified FROM posts AS p WHERE p.id = ? AND p.member_id = ?');
        if (!$stmt) {
            die($db->error);
        }
        $stmt->bind_param('ii', $id, $member['id']);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        $post['id'] = (int) $id;
        $post['message'] = $message;
        $post['modified'] = $row ? $row['modified'] : '';

    } else {
        $stmt = $db->prepare('SELECT id, message, modified FROM posts WHERE id = ? AND member_id = ?');
        if (!$stmt) {
            die($db->error);
        }
        $stmt->bind_param('ii', $id, $member['id']);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if (!$row) {
            http_response_code(403);
            $forbidden = true;
        } else {
            $post['id'] = (int) $row['id'];
            $post['message'] = (string) $row['message'];
            $post['modified'] = (string) $row['modified'];
        }
    }

    $_SESSION['token'] = bin2hex(random_bytes(32));
    $token = $_SESSION['token'];
    ?>

    <body class="page-edit">
        <div class="container">
            <header class="header">
                <a class="logo top_return_link" href="./index.php">Cell Focus</a>
                <nav class="header-right">
                    <button class="current_user_icon" type="button"><img
                            src="./member_picture/<?php echo $member['picture'] ?>" alt="プロフィール画像"></button>
                    <div class="toggleArea">
                        <a class="btn" href="profile/profile.php?id=<?php echo $member['id']; ?>">プロフィール</a>
                        <a class="btn" href="logout.php">ログアウト</a>
                    </div>
                </nav>
            </header>

            <div class="inner">
                <main class="content content--edit">
                    <?php if (!empty($errors)): ?>
                        <ul class="error-list">
                            <?php foreach ($errors as $e): ?>
                                <li class="error">
                                    <?php echo htmlspecialchars("・" . $e, ENT_QUOTES, 'UTF-8'); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (!empty($forbidden)): ?>
                        <p class="error card">
                            この投稿を編集する権限がありません。
                        </p>
                    <?php else: ?>

                        <form class="post-form" action="" method="post">
                            <dl class="post-fields">
                                <dt class="post-fields__label">
                                    <?php echo htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8'); ?> さんの投稿
                                    （更新日時：<time
                                        class="message__time"><?php echo htmlspecialchars($post['modified'], ENT_QUOTES, 'UTF-8'); ?></time>）
                                </dt>
                                <dd class="post-fields__control">
                                    <textarea class="post-textarea" name="message" cols="50" rows="5"
                                        placeholder="メッセージを編集してください"><?php
                                        echo htmlspecialchars($post['message'], ENT_QUOTES, 'UTF-8');
                                        ?></textarea>
                                </dd>
                            </dl>

                            <div class="form-actions">
                                <input type="hidden" name="id" value="<?php echo (int) $post['id']; ?>">
                                <input type="hidden" name="token"
                                    value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                                <input class="btn" type="submit" value="更新する" />
                            </div>
                        </form>

                    <?php endif; ?>
                </main>
                <a href="./" class="top_return_link_bottom">一覧に戻る</a>
            </div>
        </div>
        <script src="js/script.js"></script>
    </body>

    </html>
<?php else: ?>
    <?php header('Location: login.php'); ?>
<?php endif; ?>