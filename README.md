# import-csv

Install

<b> 
docker-compose up -d <br>
docker-compose exec php bash <br>
composer install <br>
php bin/console doctrine:migrations:migrate <br>
copy .env.local to .env
</b>
<br><br>
<h2>Run script</h2>
<br>
<b> 
docker-compose exec php bash <br>
php bin/console app:csv-import-product <path_csv> --test --view-error
</b>
<br>
--test - test mode everything the normal import does, but not insert the data into the database <br>
--view-error - view first 100 rows with error


<br><br>
<h2>Tests</h2>
<br>
<b> 
docker-compose exec php bash <br>
./bin/phpunit
</b>

