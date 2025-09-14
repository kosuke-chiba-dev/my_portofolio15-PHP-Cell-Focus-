<?php
require_once dirname(__DIR__) . '/../env.php';
session_start();
session_regenerate_id();


if (empty($_SESSION['member'])) {
	header('Location: index.php');
	exit;
}

$member = $_SESSION['member'];

$imageFile = !empty($member['image']) ? $member['image'] : 'default-icon.png';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

	try {
		$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
		$db->set_charset('utf8mb4');
	} catch (mysqli_sql_exception $e) {
		error_log('DB connect failed: ' . $e->getMessage());
		http_response_code(500);
		exit('サーバ内部エラー');
	}
	if ($db->connect_error) {
		die($db->connect_error);
	}

	$stmt = $db->prepare('INSERT INTO members (name, email, password, picture) VALUES (?, ?, ?, ?)');
	if (!$stmt) {
		die($db->error);
	}

	$hashed = password_hash($member['password'], PASSWORD_DEFAULT);


	$stmt->bind_param('ssss', $member['name'], $member['email'], $hashed, $imageFile);

	if (!$stmt->execute()) {
		die($db->error);
	}

	unset($_SESSION['member']);
	header('Location: thanks.php');
	exit;
}
?>
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
	<div>
		<header class="header">
			<h1 class="logo">会員登録</h1>
		</header>

		<div id="content" class="inner">
			<p class="lead-title">記入した内容を確認して、「登録する」ボタンをクリックしてください</p>
			<form action="" method="post" class="check-form">
				<dl>
					<dt>ニックネーム</dt>
					<dd><?= htmlspecialchars($member['name'], ENT_QUOTES) ?></dd>

					<dt>メールアドレス</dt>
					<dd><?= htmlspecialchars($member['email'], ENT_QUOTES) ?></dd>

					<dt>パスワード</dt>
					<dd>【表示されません】</dd>

					<dt>写真など</dt>
					<dd>
						<img src="../member_picture/<?= htmlspecialchars($imageFile, ENT_QUOTES) ?>" width="100"
							alt="プロフィール画像" />
					</dd>
				</dl>
				<div>
					<a class="rewite-link" href="index.php?action=rewrite">&laquo;&nbsp;書き直す</a> |
					<input type="submit" value="登録する" class="btn" />
				</div>
			</form>
		</div>
	</div>
</body>

</html>