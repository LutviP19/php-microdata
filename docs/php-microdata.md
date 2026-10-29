
# Add Library:
```bash
composer require phpstreamserver/scheduler
```

### Sample Data (DB: employees):
#### [Installation](https://dev.mysql.com/doc/employee/en/employees-installation.html)
------------
### Github repo:
#### [datacharmer/test_db](https://github.com/datacharmer/test_db)


# Build Directory structure :
### BASIC
---------------------------
```bash
mkdir ./cron && 
mkdir -p ./ffi/source && 
mkdir ./ffi/lib && 
mkdir ./public
```

## Build Core Directory structure :
### FRAMEWORK
--------------------------------
```bash
mkdir ./config && 
mkdir -p ./app/Core/Database && 
mkdir -p ./app/Core/Support && 
mkdir -p ./app/Core/Auth && 
mkdir -p ./app/Core/Events && 
mkdir -p ./app/Core/Http && 
mkdir -p ./app/Core/FFI && 
mkdir ./app/Helpers && 
mkdir ./app/Models && 
mkdir ./app/Structs && 
mkdir ./app/Data && 
mkdir ./app/Listeners && 
mkdir ./static && 
mkdir -p ./storage/database && 
mkdir -p ./storage/framework && 
mkdir -p ./storage/logs && 
mkdir -p ./views/partial && 
mkdir -p ./views/elements && 
mkdir -p ./views/error
```


## BUILD APP
```text
Migrations Files:
----------------

storage/database/migrations/mysql/*.sql

Seeders:
--------
storage/database/seeders/mysql/*.sql
```

## Build shared Library:
### GO
```bash
go build -buildmode=c-shared -o ./ffi/lib/concurrency.so ./ffi/source/concurrency.go
go build -buildmode=c-shared -o ./ffi/lib/fibonacci.so ./ffi/source/fibonacci.go
```

## Build shared Library:
### RUST
```bash
cd ffi/source/rust_curl
cargo build --release
cp target/release/librust_curl_ffi.so ../../lib/librust_curl_ffi.so
```

# LOGGING

### Test - Log:
```bash
php ./cron/test.php >> ./cron_log.txt
```

### Main Error Log:
#### (Auto Create)
```text
app 	: storage/logs/app-error.log
cron 	: storage/logs/worker-error.log
DB		: storage/logs/error_DB.log
Encrypt	: storage/logs/error_ENCRYPTION.log
Queue	: storage/logs/error_RabbitFFI.log
Events	: storage/logs/error_EventLib.log
```


# RUN APP
## Generate new KEY:
```text
----------------
App Key | Api Key | JWT Secret
$secret = generateRandomString(64, false);
dd($secret);

SodiumAuth:
dd(bin2hex(random_bytes(32)));
or
php -r "echo bin2hex(random_bytes(32)).PHP_EOL;"
```

## FrankenPHP:
### Install FrankenPHP on Linux or macOS
```bash
curl https://frankenphp.dev/install.sh | sh

#Check Version: 
frankenphp version

#Validate Config(Caddyfile):
frankenphp validate

```


### Add Extensions(Linux-Ubuntu)
```bash
sudo apt update
sudo apt install php-zts-pdo php-zts-pdo-mysql php-zts-pdo-sqlite
sudo apt install php-zts-ffi
sudo apt install php-zts-redis
```

### RUN - With Frankenphp (terminal 1)
#### http://localhost:8000
```bash
# Force stop if port conflict
frankenphp stop
# Run frankenphp server
frankenphp run
```

### RUN - Worker (terminal 2):
```bash
php worker.php start

frankenphp php-cli worker.php start
frankenphp php-cli worker.php stop
```

### RUN - with composer:
```bash
composer start:all 
composer stop:all 
```


### Optional with Docker desktop
----------------------------
#### images: 
docker pull dunglas/frankenphp:1.12-builder-php8.5

docker pull dunglas/frankenphp:1.12.4-php8.5
```bash
copy .env.docker -> .env
run docker compose up -d
migrate mysql database with phpMyAdmin
```

#### Microdata App: 
http://localhost:8000

#### phpMyAdmin (Auto-login): 
http://localhost:8080

#### Redis Commander GUI: 
http://localhost:8081

#### RabbitMQ Dashboard: 
http://localhost:15673 (User/Pass: guest/guest)


### Update composer :
```bash
composer dump-autoload -o
# Or
composer du -o
```

### Tmp Cleaner
```bash
php cron/cleanTmp.php
```

### TESTING - CURL

```bash
# Test-Curl JSON 
curl --request GET \
  --url http://localhost:8000/dashboard \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'X-API-KEY: zjwTis+UxjPt2VhvlqSQoTw1CZuaR0LC8a654X1ofVc=' \
  --data '{
	"title" : "JSON data <script>document.print</script>",
	"tag_html" : {
		"div" : "<div onclick='\''submitError()'\''>Submit</div>"
	},
	"page": "1",
  	"offset": "0",
	"limit": 10000
}'

# POST - Validation errors
curl --request POST \
  --url http://localhost:8000/dashboard \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'X-API-KEY: zjwTis+UxjPt2VhvlqSQoTw1CZuaR0LC8a654X1ofVc=' \
  --data '{
	"title" : "JSON data <script>document.print</script>",
	"tag_html" : {
		"div" : "<div onclick='\''submitError()'\''>Submit</div>"
	},
	"age": "18",
	"username": "lutvi",
	"email": "lutvi-demo.local",
	"website": "http://demo.local:8000/dashboard/"
}'

# POST - Valid Stuct
curl --request POST \
  --url http://localhost:8000/dashboard \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header 'X-API-KEY: zjwTis+UxjPt2VhvlqSQoTw1CZuaR0LC8a654X1ofVc=' \
  --data '{
	"title" : "JSON data <script>document.print</script>",
	"tag_html" : {
		"div" : "<div onclick='\''submitError()'\''>Submit</div>"
	},
	"age": "18",
	"username": "lutvi",
	"email": "lutvi@demo.local",
	"website": "http://demo.local:8000/dashboard/"
}'
```


## BUILD FFI - LIB

### TESTING:
```bash
go build -buildmode=c-shared -o ./ffi/lib/fibonacci.so ./ffi/source/fibonacci.go
```

### crawler.go
```bash
cd ffi/source/crawler
go mod init mycrawler
go get github.com/PuerkitoBio/goquery
go mod tidy
go run crawler.go
go build -o ../../lib/crawler.so -buildmode=c-shared ./crawler.go

$ chmod +x ./ffi/lib/crawler.so
$ file ./ffi/lib/crawler.so

php cron/test.php >> ./storage/logs/test.txt

# Testing
php cron/crawler.php >> ./storage/logs/crawler-out.txt
php cron/crawler.php > ./storage/logs/crawler-out.txt
```

### mq.go (Queue FFI)
```bash
cd ffi/source/mq
go mod init mq
go mod tidy
go build -o ../../lib/mq.so -buildmode=c-shared ./mq.go

# PHP-Testing
# -----------
# Consumer : 
php cron/queue-consumer.php
# Sender Testing : 
php cron/queue.php
```

### Sanitize:
```bash
cd ffi/source/sanitize
go mod init phpffi.lutvi/sanitize
go mod tidy

go build -o ../../lib/sanitize.so -buildmode=c-shared ./sanitize.go

go build -buildmode=c-shared -o ./ffi/lib/sanitize.so ./ffi/source/sanitize/sanitize.go
```

### Validator:
```bash
cd ffi/source/dynamic_validate
go mod init phpffi.lutvi/dynamic_validate
go mod tidy

go build -o ../../lib/dynamic_validate.so -buildmode=c-shared ./dynamic_validate.go

go build -buildmode=c-shared -o ./ffi/lib/dynamic_validate.so ./ffi/source/dynamic_validate/dynamic_validate.go
```

### Bulk_Data:
```bash
cd ffi/source/bulk_data
go mod init bulk_data
go mod tidy

go build -buildmode=c-shared -o ../../lib/bulk_data.so ./bulk_data.go
OR ---
go build -buildmode=c-shared -o ./ffi/lib/bulk_data.so ./ffi/source/bulk_data/bulk_data.go

php cron/bulkData.php
php cron/searchBulkData.php
```


### curlgo.go (CURL with Go - FFI)
```bash
cd ffi/source/curlgo
go mod init curlgo
go mod tidy
go build -o ../../lib/curlgo.so -buildmode=c-shared ./curlgo.go

# PHP-Testing Curl-Go : 
php cron/curlGo.php

# Curl - Rust (FFI)
cargo clean (menghapus semua cache build)

cd ffi/source/rust_curl
cargo build --release
cp target/release/librust_curl_ffi.so ../../lib/librust_curl_ffi.so

# PHP-Testing Curl-Rust : 
php cron/curlRust.php
```

### DataEngine(Filter) - Rust (FFI)
#### cargo clean (menghapus semua cache build)
```bash
cd ffi/source/data_engine
cargo test
cargo build --release
cp target/release/libdata_engine.so ../../lib/libdata_engine.so

# PHP-Testing Data Filter-Rust : 
php cron/DataEngine.php
```

### Protobuf(Serial) - Rust (FFI)
#### [installation](https://protobuf.dev/installation/)
#### cargo clean (menghapus semua cache build)
```bash
protoc --version
cd ffi/source/rust_protobuf/rust-lib
cargo test
cargo build --release
cp ../target/release/librust_protobuf.so ../../../lib/librust_protobuf.so
```

### Web-Testing
#### Protobuf Serialize-Rust : http://localhost:8000/testing
-----------

## Event - Listener:
### Migarte table
#### storage/database/migrations/mysql/event_queue.sql

```bash
# Consume Queue
php cron/queue-consumer.php

# Run Listener and Dispact Event on separated terminals
php cron/event-listener.php
php cron/event-dispact.php
```

## App - Auto Scan Listeners
-------------------------
```bash
# Auto Scan Listeners and waiting for events (daemon)
php worker-event.php

# Dispatch Events (run once)
php cron/event-dispatch_test.php

# Listen and Consume RabbitMQ
php cron/rabbitmq-receiver.php
```

## CRON Testing

#### Consumers (daemon)
```bash
php cron/event-listener.php
php cron/queue-consumer.php
php cron/rabbitmq-receiver.php
```

#### Senders (single run)
```bash
php cron/event-dispatch_test.php
php cron/queue.php
php cron/rabbitmq-receiver.php
```

#### Indexing
```bash
php cron/meiliserach.php
php cron/indexing.php
```


## Framework - Performance Testing


### Using k6 - grafana
```bash
mkdir -p $HOME/tmp
cp $PWD/test/k6-test.js $HOME/tmp/k6-test.js
k6 run $HOME/tmp/k6-test.js

# Debuging Run once
k6 run --vus 1 --iterations 1 $HOME/tmp/k6-test.js
```

#### RESULTS: ./test/TEST.txt
```bash
k6 run $HOME/tmp/k6-test.js > $PWD/test/TEST.txt 2>&1
```

#### APPEND RESULTS:
```bash
k6 run $HOMEtmp/k6-test.js >> $PWD/test/TEST.txt 2>&1
```

#### SHOW OUTPUT (verbose):
```bash
k6 run $HOME/tmp/k6-test.js | tee $PWD/test/TEST.txt
```

#### Format to JSON:
```bash
# Debuging Run once
k6 run --vus 1 --iterations 1 --summary-export=$HOME/tmp/TEST_SUMMARY.json $HOME/tmp/k6-test.js

# Debuging Run full testing
k6 run --summary-export=$HOME/tmp/TEST_SUMMARY.json $HOME/tmp/k6-test.js

# Copy TEST_SUMMARY.json to project
cp $HOME/tmp/TEST_SUMMARY.json $PWD/test/TEST_SUMMARY.json


# Log BOTH
--------
k6 run --summary-export=$PWD/test/TEST_SUMMARY.json $HOME/tmp/k6-test.js > $PWD/test/TEST.txt 2>&1
```


### Using ab tools
#### Apache Benchmark
```bash
# Benchmark
ab -c 128 -n 10000 -k http://localhost:8000/health 2>&1

# POST
ab -n 1000 -c 50 \
-T application/json \
-H "X-API-KEY: zjwTis+UxjPt2VhvlqSQoTw1CZuaR0LC8a654X1ofVc=" \
-H "Accept: application/json" \
-H "Content-Type: application/json" \
-p $PWD/test/hey-data.json \
"http://localhost:8000/api/v1/dashboard"

```

### Using hey tools
#### [Get Binary](https://github.com/rakyll/hey)
#### paste bin to: test/bin/
```bash
# Docker or terminal:
cp /usr/local/bin/hey /app/test/bin/hey

# Run Testing
test/bin/hey -n 1000 -c 50 \
-m GET \
-D $PWD/test/hey-data.json \
-H "X-API-KEY: zjwTis+UxjPt2VhvlqSQoTw1CZuaR0LC8a654X1ofVc=" \
-H "Accept: application/json" \
-H "Content-Type: application/json" \
"http://localhost:8000/api/v1/dashboard"

# Output to text
test/bin/hey -n 1000 -c 50 -m GET -D $PWD/test/hey-data.json -H "X-API-KEY: zjwTis+UxjPt2VhvlqSQoTw1CZuaR0LC8a654X1ofVc=" -H "Accept: application/json" -H "Content-Type: application/json" "http://localhost:8000/api/v1/dashboard" > test/HEY_TEST.txt

# Hit static file
test/bin/hey -n 1000 -c 50 \
-m GET \
"http://localhost:8000/testing"
```


## Framework - Formater

### Rector:

#### To see preview of suggested changed, run process command with --dry-run option:
```bash
vendor/bin/rector process --dry-run
vendor/bin/rector process --dry-run > rector.log
vendor/bin/rector process --dry-run > rector-master.log
vendor/bin/rector process --dry-run > storage/framework/cache/rector.log
vendor/bin/rector process --dry-run --debug > storage/framework/cache/rector.log
```

#### To make changes happen, run bare command:
```bash
vendor/bin/rector process
vendor/bin/rector process > rector-master.log
```

## Structure
```text
php-microdata/                 # [Root Project Directory in Host & /app in Docker]
├── app/                       # Where the main logic of the application (Core & Domain)
│   ├── Core/                  # Engine framework (Router, DI Container, Request/Response)
│   ├── Data/                  # Database abstraction (PDO, SQLite wrapper)
│   ├── Helpers/               # Global utility function
│   ├── Listeners/             # Event listeners /RabbitMQ Event Handlers
│   ├── Models/                # Model Class (pairs with modelPath resolver)
│   └── Structs/               # PHP 8.5 Typed Properties /Data Transfer Object (DTO)
├── ffi/               	       # Binding/Bridge to shared libraries (.so /.dll)
│   └── lib                    # Place shared libraries (.so /.dll)
├── config/                    # Configuration files that return arrays (database, redis, amqp)
├── public/                    # Public Root (Only this folder is exposed to the internet)
│   ├── api.php                # MAIN ENTRY POINT: For API Workers (/api/*)
│   └── index.php              # SECOND ENTRY POINT: For Web/HTMX (UI/Non-Worker)
├── storage/                   # Place temporary files and logs
│   └── logs/  
│       └── docker/            # Mounted to docker host to view api/web logs
├── cron/                      # CLI Worker /Consumer Background
│   └── rabbitmq-receiver.php  # Daemon script to process RabbitMQ queues via CLI
├── Caddyfile-docker           # FrankenPHP Server configuration for Docker
├── Dockerfile                 # Docker Image Blueprint (Alpine/Debian + FFI)
├── docker-compose.yml         # Container orchestration (PHP, MySQL, Redis, RabbitMQ)
└── php.ini                    # Customize php.ini (ffi.enable=preload, opcache, etc)
```

## Upgrade History

### Upgrade to min version PHP-8.5:
```bash
Updating dependencies
Lock file operations: 1 install, 16 updates, 1 removal
  - Removing paragonie/random_compat (v9.99.100)
  - Upgrading amphp/amp (v3.1.1 => v3.1.3)
  - Upgrading amphp/dns (v2.4.0 => v2.4.1)
  - Upgrading amphp/pipeline (v1.2.3 => v1.2.7)
  - Upgrading amphp/process (v2.0.3 => v2.1.0)
  - Upgrading amphp/socket (v2.4.0 => v2.4.1)
  - Upgrading dragonmantank/cron-expression (v3.6.0 => v3.6.1)
  - Upgrading league/uri (7.8.1 => 7.9.0)
  - Upgrading league/uri-interfaces (7.8.1 => 7.9.0)
  - Upgrading meilisearch/meilisearch-php (v1.16.1 => v1.17.0)
  - Upgrading php-amqplib/php-amqplib (v3.7.4 => v3.7.5)
  - Upgrading phpseclib/phpseclib (3.0.52 => 4.0.2)
  - Upgrading phpstan/phpstan (2.1.54 => 2.3.1)
  - Upgrading predis/predis (v3.4.2 => v3.7.0)
  - Upgrading rector/rector (2.4.2 => 2.7.0)
  - Upgrading revolt/event-loop (v1.0.8 => v1.0.9)
  - Upgrading symfony/polyfill-php81 (v1.37.0 => v1.38.1)
  - Locking symfony/polyfill-php82 (v1.43.0)
```
