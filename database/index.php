<?php
http_response_code(403);
header('HTTP/1.1 403 Forbidden');
die("403 Forbidden: Akses langsung ke direktori database tidak diizinkan.");
