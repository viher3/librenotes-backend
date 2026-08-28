<?php

declare(strict_types=1);

/**
 * Bootstrap consumed by phpstan-doctrine (`doctrine.objectManagerLoader` in phpstan.dist.neon).
 *
 * Boots the real application kernel so PHPStan resolves entity metadata from the actual
 * XML mappings (src/{Module}/Infrastructure/Persistence/Doctrine/Definition/*.orm.xml)
 * instead of a hand-maintained duplicate. Lives in etc/ rather than tests/ because tests/
 * is excluded from PHPStan's analysed paths.
 */

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__, 2) . '/.env');
}

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'localdocker', (bool) ($_SERVER['APP_DEBUG'] ?? false));
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
