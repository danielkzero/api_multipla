<?php
//app/settings.php
declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {

    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {
            return new Settings([
                'displayErrorDetails' => true, 
                'logError'            => true,
                'logErrorDetails'     => true,
                'logger' => [
                    'name' => 'slim-app',
                    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                    'level' => Logger::DEBUG,
                ],
                "db" => [
                    'driver' => 'mysql',
                    'host' => '127.0.0.1',
                    'database' => 'dmultipla',
                    'username' => 'mobile',
                    'password' => '100senha',
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'flags' => [
                        PDO::ATTR_PERSISTENT => false,
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_EMULATE_PREPARES => true,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ],
                ],
                // Configuração PostgreSQL
                "db_pgsql" => [
                    'driver' => 'pgsql',
                    'host' => 'localhost',
                    'port' => 5432, // Porta padrão para PostgreSQL
                    'database' => 'dmultipla',
                    'username' => 'julio',
                    'password' => 'multipla',
                    'charset' => 'utf8',
                    'flags' => [
                        PDO::ATTR_PERSISTENT => false,
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_EMULATE_PREPARES => true,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ],
                ],
                "secret_key" => "Chave%100%=>0.1MM@ahneS#001",
            ]);
        }
    ]);
};
