<?php
session_start();
session_regenerate_id();
if (isset($_SESSION['member'])):
    ?>
    <!DOCTYPE html>
    <html lang="ja">

    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="X-UA-Compatible" content="ie=edge" />
        <title>Cell Focus</title>

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/destyle.css@1.0.15/destyle.css" />
        <link rel="stylesheet" type="text/css" href="css/style.css" />
    </head>
    <?php
    require_once dirname(__DIR__) . '/env.php';

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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
        if (!empty(trim($message))) {
            $stmt = $db->prepare('INSERT INTO posts (member_id, message, reply_post_id) VALUES(?, ?, ?)');
            if (!$stmt) {
                die($db->error);
            }
            $stmt->bind_param('isi', $member['id'], $message, $member['id']);
            $success = $stmt->execute();
            if (!$success) {
                die($db->error);
            }
            $stmt->close();
            header('Location: index.php');
            exit();
        }
    }
    $posts = $db->query('SELECT posts.id, posts.message, posts.modified, posts.member_id, members.picture, members.name FROM posts INNER JOIN members ON posts.member_id = members.id ORDER BY posts.modified DESC');
    ?>

    <body class="page-index">
        <div class="container">
            <header class="header">
                <h1 class="logo">Cell Focus</h1>
                <nav class="header-right">
                    <button class="current_user_icon" type="button"><img
                            src="./member_picture/<?php echo $member['picture'] ?>" alt="プロフィール画像"></button>
                    <div class="toggleArea">
                        <a class="btn" href="profile/profile.php?id=<?php echo $member['id']; ?>">プロフィール</a>
                        <a class="btn" href="logout.php">ログアウト</a>
                    </div>
                </nav>
            </header>

            <main class="content content--index">
                <div class="inner">
                    <form class="post-form card" action="" method="post">
                        <dl class="post-fields">
                            <dt class="post-fields__label"><?php echo htmlspecialchars($member['name']); ?>さん、メッセージをどうぞ</dt>
                            <dd class="post-fields__control">
                                <textarea class="post-textarea" name="message" cols="50" rows="5"
                                    placeholder="いま何してる？"></textarea>
                            </dd>
                        </dl>
                        <div class="form-actions">
                            <input class="btn" type="submit" value="投稿する" />
                        </div>
                    </form>

                    <section class="message-listArea">
                        <h2 class="message-list__title">ひとこと一覧 (最新順)</h2>
                        <div class="message-list">
                            <?php while ($post = $posts->fetch_assoc()): ?>
                                <article class="message card">
                                    <a href="profile/profile.php?id=<?php echo $post['member_id']; ?>"
                                        class="message_avatar_link"><img class="message__avatar"
                                            src="./member_picture/<?php echo htmlspecialchars($post['picture']); ?>"
                                            alt="ユーザーアイコン" /></a>
                                    <div class="message__main">
                                        <a class="message__link" href="view.php?id=<?php echo $post['id']; ?>">
                                            <span class="message__text"><?php echo htmlspecialchars($post['message']); ?></span>
                                            <span
                                                class="message__author">（<?php echo htmlspecialchars($post['name']); ?>）</span>
                                        </a>
                                        <p class="message__meta">
                                            <time
                                                class="message__time"><?php echo htmlspecialchars($post['modified']); ?></time>
                                            <?php if ($post['member_id'] == $member['id']): ?>
                                                <span class="message__actions">
                                                    <a class="message__action message__action--edit"
                                                        href="edit.php?id=<?php echo $post['id']; ?>">編集</a>
                                                    <a class="message__action message__action--delete"
                                                        href="delete.php?id=<?php echo $post['id']; ?>">削除</a>
                                                </span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </article>
                            <?php endwhile; ?>
                        </div>
                    </section>
                </div>
            </main>
        </div>
        </div>
        <script src="js/script.js"></script>
    </body>

    </html>
<?php else: ?>
    <?php header('Location: login.php'); ?>
<?php endif; ?>