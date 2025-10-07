<?php
namespace OpenCCK\Infrastructure\Model;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Drift\DBAL\Driver\Driver;
use Drift\DBAL\Connection;
use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\Mysql\MysqlDriver;
use Drift\DBAL\SingleConnection;

use React\EventLoop\Loop;

class DB {
    private static DB $_instance;
    /**
     * DataBase Abstract Layer Connection
     * @var Connection
     */
    private Connection $conn;

    /**
     * @var Credentials
     */
    private Credentials $credentials;

    /**
     * Credentials options
     * @var string[]
     */
    private static array $options = [
        'host' => 'mysql',
        'port' => '3306',
        'user' => 'mysql',
        'password' => 'mysql',
        'db' => 'db',
    ];

    /**
     * @param string[] $options
     */
    public static function setDefaultOptions(array $options): void {
        self::$options = array_merge(self::$options, $options);
    }

    /**
     * @param array $options
     * @param Driver $driver
     * @param AbstractPlatform $platform
     */
    private function __construct(
        array $options,
        private readonly Driver $driver,
        private readonly AbstractPlatform $platform
    ) {
        self::setDefaultOptions($options);

        $this->credentials = new Credentials(
            self::$options['host'],
            self::$options['port'],
            self::$options['user'],
            self::$options['password'],
            self::$options['db']
        );
        $this->conn = SingleConnection::createConnected($this->driver, $this->credentials, $this->platform);
    }

    /**
     * @param array $options
     * @param ?Driver $driver
     * @param ?AbstractPlatform $platform
     * @return DB
     */
    public static function getInstance(
        array $options = [],
        ?Driver $driver = null,
        ?AbstractPlatform $platform = null
    ): DB {
        return self::$_instance ??= new self(
            $options,
            $driver ?? new MysqlDriver(Loop::get()),
            $platform ?? new MySqlPlatform()
        );
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection {
        return $this->conn;
    }

    /**
     * @return AbstractPlatform
     */
    public function getPlatform(): AbstractPlatform {
        return $this->platform;
    }
}
