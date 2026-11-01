<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Invoice') ?></title>
<?php /* Invoice templates carry their own plain CSS so they print and convert to PDF reliably. */ ?>
</head>
<body style="margin:0;background:#e9ebe6;">
<?= $content ?>
</body>
</html>
