<?php
$allowed_referer = "http://localhost:8888/Portofolio_PHP1_Wrapper/Portofolio_PHP1/join/check.php";
$referer = $_SERVER['HTTP_REFERER'];
if ($referer !== $allowed_referer) {
	header('Location: index.php');
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
<?php

?>

<body>
	<div>
		<header class="header">
			<h1 class="logo">会員登録</h1>
		</header>

		<div id="content" class="inner">
			<p class="lead-title">ユーザー登録が完了しました</p>
			<p class="lead-link"><a href="../">ログインする</a></p>
		</div>

	</div>
</body>

</html>