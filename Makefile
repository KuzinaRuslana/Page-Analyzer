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
	composer exec --verbose phpunit tests

test-coverage:
	XDEBUG_MODE=coverage composer exec phpunit tests -- --coverage-clover ./build/logs/clover.xml

test-coverage-text:
	XDEBUG_MODE=coverage composer exec --verbose phpunit tests -- --coverage-text