install:
	composer install

validate:
	composer validate

lint:
	composer exec --verbose phpcs -- --standard=PSR12 src public tests

dump:
	composer dump-autoload

start:
	PHP_CLI_SERVER_WORKERS=1 php -S 0.0.0.0:8080 -t public &
	sleep 2

test:
	PHP_CLI_SERVER_WORKERS=1 php -S 0.0.0.0:8080 -t public &
	while ! curl -s http://localhost:8080 > /dev/null; do
		sleep 1
	done
	PLAYWRIGHT_TEST_TIMEOUT=60000 playwright test --browser='chromium'

test-coverage:
	XDEBUG_MODE=coverage composer exec phpunit tests -- --coverage-clover ./build/logs/clover.xml

test-coverage-text:
	XDEBUG_MODE=coverage composer exec --verbose phpunit tests -- --coverage-text