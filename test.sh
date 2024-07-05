php artisan migrate:fresh --env=testing
php artisan db:seed --env=testing

# ./vendor/bin/pest --coverage
./vendor/bin/pest --filter=PlnTest


