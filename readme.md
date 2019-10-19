# Pasos a seguir

-   crear archivo .env desde .env.example
-   composer install
-   crear Homestead.yaml
-   php artisan jwt:secret
-   php artisan migrate --seed

## Probar tests de la carpeta Feature para comprobar que el flujo

auth esta correctamente funcionando.

-   phpunit test/Feature/
