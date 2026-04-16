<?php 
declare(strict_types=1);

/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

// file: static/service-status.php

if (!isHtmx()) {
    return response('Unauthorized', 403);
}

header('Content-Type: text/html');
\App\Core\Support\ServiceMonitor::checkAll();
exit;