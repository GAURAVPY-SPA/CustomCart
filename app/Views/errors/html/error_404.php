<?php
/* Plain 404 page (requests the designed 404 page does not handle). */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>404 Page Not Found</title>
<style>
body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f4f6f8;color:#1f2933}
.box{max-width:640px;margin:12vh auto;padding:32px;background:#fff;border:1px solid #e4e7eb;border-radius:8px}
h1{margin:0 0 12px;font-size:22px;color:#0f5132}
a{color:#0f5132}
</style>
</head>
<body>
<div class="box">
<h1>404 Page Not Found</h1>
<p>The page you requested was not found.</p>
<p><a href="<?php echo htmlspecialchars(website_url(), ENT_QUOTES, 'UTF-8'); ?>">Back to the home page</a></p>
</div>
</body>
</html>
