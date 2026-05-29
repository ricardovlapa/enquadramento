.PHONY: test phpunit phpstan

test: phpunit phpstan

phpunit:
	composer test

phpstan:
	composer stan
