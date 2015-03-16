<?php
header("HTTP/1.1 301");
header("Location: http://".$_SERVER['HTTP_HOST']);
exit;
?> 
