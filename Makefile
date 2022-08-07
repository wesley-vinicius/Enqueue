update:
	 docker-compose run --rm app composer update

test:
	 docker-compose run --rm app composer test

coverage:
	 docker-compose run --rm app composer coverage
