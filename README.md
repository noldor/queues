## Queues


### Usage Instructions

Use with Sqlite.

```PHP
use Noldors\Queues\Queue;
use \Noldors\Queues\Providers\SqliteProvider;

require 'vendor/autoload.php';

//Set queue instance.
$queue = new Queue(new SqliteProvider(__DIR__ . '/db.sqlite'));

//Add new queue
$queue->push('SomeClass::someMethod', ['firstParameter', 'secondParameter', 3]);
```

To execute queues

```PHP
use Noldors\Queues\Queue;
use \Noldors\Queues\Providers\SqliteProvider;

require 'vendor/autoload.php';

//Set queue instance.
$queue = new Queue(new SqliteProvider(__DIR__ . '/db.sqlite'));

//Add new queue
$queue->execute();
```

### Available providers

* SqliteProvider
* MysqlProvider
* PostgresProvider

## License

[MIT license](http://opensource.org/licenses/MIT).
