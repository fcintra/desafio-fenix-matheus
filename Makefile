APP = docker exec fenix_app

setup:
	$(APP) composer install --no-interaction --prefer-dist --optimize-autoloader
	$(APP) npm install
	$(APP) php artisan key:generate
	$(APP) php artisan migrate --force
