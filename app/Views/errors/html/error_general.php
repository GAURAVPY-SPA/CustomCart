<?php
/* General error page (HaltException, show_error()). */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo isset($heading) ? strip_tags($heading) : 'Error'; ?></title>
<style>
body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f4f6f8;color:#1f2933}
.box{max-width:640px;margin:12vh auto;padding:32px;background:#fff;border:1px solid #e4e7eb;border-radius:8px}
h1{margin:0 0 12px;font-size:22px;color:#0f5132}
a{color:#0f5132}
</style>
</head>
<body>
<div class="box">
<h1><?php echo $heading; ?></h1>
<?php echo $message; ?>
</div>
</body>
</html>
