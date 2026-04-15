<?php 

if (!isHtmx()) {
    return response('Unauthorized', 403);
}

header('Content-Type: text/html');
\App\Core\Support\ServiceMonitor::checkAll();
exit;