<?php 

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ );
}

// we using PHPStreamServer library as worker to running periodic scheduler
require __DIR__ . '/vendor/autoload.php';

use PHPStreamServer\Core\Server;
use PHPStreamServer\Core\Worker\WorkerProcess;
use PHPStreamServer\Plugin\Scheduler\SchedulerPlugin;
use PHPStreamServer\Plugin\Scheduler\Worker\PeriodicProcess;

$server = new Server();

$server->addPlugin(
    new SchedulerPlugin(),
);

$server->addWorker(
    new WorkerProcess(
        name: 'Worker',
        count: 1,
        onStart: static function (WorkerProcess $worker): void {
            
            // Logger
            $worker->logger->notice("Hello from worker!");
            $worker->logger->info('Hello from worker', ['pid' => \posix_getpid()]);
        }
    ),
    new PeriodicProcess(
        name: 'Periodic process 1',
        schedule: '*/1 * * * *',
        onStart: function (PeriodicProcess $worker): void {
            // process include file php

            // Logger
            $worker->logger->info('Scheduler 1 is running', ['at' => date('d-m-Y H:i:s')]);
            $worker->logger->info('Scheduler 1', ['pid' => \posix_getpid()]);

            // Run included php file
            include BASEPATH . '/cron/sample.php';
        },
    ),
    new PeriodicProcess(
        name: 'Periodic process 2',
        schedule: '*/2 * * * *',
        onStart: function (PeriodicProcess $worker): void {
            // process execute file php-ffi

            // Logger
            $worker->logger->info('Scheduler 2 is running', ['at' => date('d-m-Y H:i:s')]);
            $worker->logger->info('Scheduler 2', ['pid' => \posix_getpid()]);

            // Execute the command (Linux/macOS) or (Windows)
            $source_path = BASEPATH . '/cron/test.php';
            
            // append the output directly to the log file (opsi 1)
            $logFile = BASEPATH . '/cron_log.txt';
            system("php $source_path >> $logFile", $return_code);

            // // or display the output directly to the console||browser (opsi 2)
            // $last_line = system("php $source_path", $return_code);
            // echo "Last line of output: $last_line" . PHP_EOL;

            echo "Return code: $return_code" . PHP_EOL;
        },
    ),
);

exit($server->run());

