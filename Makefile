.PHONY: test install

test: install
	vendor/bin/phpunit -c phpunit.xml.dist \
		--log-junit output/junit-report.xml \
		--coverage-clover output/clover.xml \
		--coverage-html output/coverage

install: composer.phar
	php composer.phar install

composer.phar:
	curl -s https://getcomposer.org/installer | php
	chmod +x composer.phar


