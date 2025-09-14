<?php
require_once dirname(__DIR__) . '/env.php';

session_start();
session_regenerate_id();

if (isset($_SESSION['member'])):
  ?>

  <!DOCTYPE html>
  <html lang="ja">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Cell Focus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/destyle.css@1.0.15/destyle.css" />
    <link rel="stylesheet" type="text/css" href="css/style.css" />
  </head>

  <?php
  $member = $_SESSION['member'];

  $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

  try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
    $db->set_charset('utf8mb4');
  } catch (mysqli_sql_exception $e) {
    error_log('DB connect failed: ' . $e->getMessage());
    http_response_code(500);
    exit('サーバ内部エラー');
  }

  $stmt = $db->prepare('SELECT members.name, members.picture, posts.member_id, posts.message, posts.modified FROM members INNER JOIN posts ON members.id = posts.member_id WHERE posts.id = ?');
  if (!$stmt) {
    die($db->error);
  }
  $stmt->bind_param('i', $id);
  $success = $stmt->execute();
  if (!$success) {
    die($db->error);
  }
  $stmt->bind_result($name, $picture, $member_id, $message, $modified);
  $stmt->fetch();
  $stmt->close();

  $stmt = $db->prepare('SELECT COUNT(*) FROM posts WHERE id = ?');
  if (!$stmt) {
    die($db->error);
  }
  $stmt->bind_param('i', $id);
  $success = $stmt->execute();
  if (!$success) {
    die($db->error);
  }
  $stmt->bind_result($cnt);
  $stmt->fetch();
  $stmt->close();

  ?>

  <body>
    <div class="container">
      <header class="header">
        <a class="logo top_return_link" href="./index.php">Cell Focus</a>
        <nav class="header-right">
          <button class="current_user_icon" type="button"><img src="./member_picture/<?php echo $member['picture'] ?>"
              alt="プロフィール画像"></button>
          <div class="toggleArea">
            <a class="btn" href="profile/profile.php?id=<?php echo $member['id']; ?>">プロフィール</a>
            <a class="btn" href="logout.php">ログアウト</a>
          </div>
        </nav>
      </header>
      <div class="inner">
        <div class="content">
          <?php if ($cnt === 1): ?>
            <article class="message">
              <a href="./profile/profile.php?id=<?php echo $member_id ?>"><img class="message__avatar"
                  src="./member_picture/<?php echo htmlspecialchars($picture, ENT_QUOTES); ?>" alt="ユーザーアイコン" /></a>
              <div class="message__main">
                <h2 class="message_item">
                  <span class="message__text"><?php echo htmlspecialchars($message, ENT_QUOTES); ?></span>
                  <span class="message__author">（<?php echo htmlspecialchars($name, ENT_QUOTES); ?>）</span>
                </h2>
                <p class="message__meta">
                  <time class="message__time"><?php echo htmlspecialchars($modified, ENT_QUOTES); ?></time>
                  <?php if ($member_id === $member['id']): ?>
                    <span class="message__actions">
                      <a class="message__action message__action--edit" href="edit.php?id=<?php echo $id; ?>">編集</a>
                      <a class="message__action message__action--delete" href="delete.php?id=<?php echo $id; ?>">削除</a>
                    </span>
                  <?php endif; ?>
                </p>
              </div>
            </article>

          <?php endif; ?>

          <?php if ($cnt === 0): ?>
            <p class="notfound-text notice notice--error">その投稿は削除されたか、URLが間違えています</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <script src="js/script.js"></script>
  </body>

  </html>
<?php else: ?>
  <?php header('Location: login.php'); ?>
<?php endif; ?>