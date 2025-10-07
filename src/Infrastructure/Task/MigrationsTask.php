<?php

namespace OpenCCK\Infrastructure\Task;

use Amp\Cancellation;
use Amp\Sync\Channel;

use Cycle\Schema;
use Cycle\Annotated;
use Cycle\Database;
use Cycle\Database\Config;
use OpenCCK\Infrastructure\API\App;
use OpenCCK\Infrastructure\Task\AbstractTask;
use function OpenCCK\getEnv;

final class MigrationsTask extends AbstractTask {
    public function __construct(private readonly array|string $dirs) {
    }

    public function run(Channel $channel, Cancellation $cancellation): mixed {
        $this->init();

        $finder = (new \Symfony\Component\Finder\Finder())->files()->in($this->dirs); // __DIR__ here is folder with entities
        $classLocator = new \Spiral\Tokenizer\ClassLocator($finder);
        $embeddingLocator = new Annotated\Locator\TokenizerEmbeddingLocator($classLocator);
        $entityLocator = new Annotated\Locator\TokenizerEntityLocator($classLocator);
        $dbal = new Database\DatabaseManager(
            new Config\DatabaseConfig([
                'default' => 'default',
                'databases' => [
                    'default' => ['connection' => 'mysql'],
                ],
                'connections' => [
                    'mysql' => new Config\MySQLDriverConfig(
                        connection: new Config\MySQL\TcpConnectionConfig(
                            database: getEnv('MYSQL_DB') ?? 'db',
                            host: getEnv('MYSQL_HOST') ?? 'mysql',
                            port: getEnv('MYSQL_PORT') ?? '3306',
                            user: getEnv('MYSQL_USER') ?? 'mysql',
                            password: getEnv('MYSQL_PASSWORD') ?? 'mysql'
                        ),
                        queryCache: false
                    ),
                ],
            ])
        );

        return (new Schema\Compiler())->compile(new Schema\Registry($dbal), [
            new Schema\Generator\ResetTables(), // re-declared table schemas (remove columns)
            new Annotated\Embeddings($embeddingLocator), // register embeddable entities
            new Annotated\Entities($entityLocator), // register annotated entities
            new Annotated\TableInheritance(), // register STI/JTI
            new Annotated\MergeColumns(), // add @Table column declarations
            new Schema\Generator\GenerateRelations(), // generate entity relations
            new Schema\Generator\GenerateModifiers(), // generate changes from schema modifiers
            new Schema\Generator\ValidateEntities(), // make sure all entity schemas are correct
            new Schema\Generator\RenderTables(), // declare table schemas
            new Schema\Generator\RenderRelations(), // declare relation keys and indexes
            new Schema\Generator\RenderModifiers(), // render all schema modifiers
            new Annotated\MergeIndexes(), // add @Table column declarations
            new Schema\Generator\SyncTables(), // sync table changes to database
            new Schema\Generator\GenerateTypecast(), // typecast non string columns
        ]);
        //$schema = new \Cycle\ORM\Schema($schemaDescription);
        //$orm = new ORM\ORM(new ORM\Factory($dbal), $schema);
        //return $schema->toArray();
    }
}
