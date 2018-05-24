PHONY: infection phpstan
./infection.phar:
	wget https://github.com/infection/infection/releases/download/0.8.1/infection.phar
	chmod a+x ./infection.phar

./infection.phar.pubkey:
	wget https://github.com/infection/infection/releases/download/0.8.1/infection.phar.pubkey

infection: infection.phar infection.phar.pubkey
	./infection.phar

./phpstan.phar:
	wget https://github.com/phpstan/phpstan/releases/download/0.9.2/phpstan.phar
	chmod a+x ./phpstan.phar

phpstan: phpstan.phar
	./phpstan.phar analyse --level=7 src
