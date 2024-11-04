# Straight joins (MySQL/MariaDb only)

Sometimes the query optimizer of mysql seems to prioritize join orders at random, making some joins expensive due to missing indexes.
To remedy that you can use ```STRAIGHT_JOIN``` instead of ```INNER JOIN``` (see https://dev.mysql.com/doc/refman/8.4/en/join.html for documentation)

Since it should only be needed in specific cases and since it's a MySQL only feature, you need to manually add straight join options to your Model:

You can do this by using the ```\Zalt\Model\Sql\Laminas\Mysql\StraightJoinModelTrait``` in your model.
It also requires you to use a MySQL specific SqlRunner, e.g. ```\Zalt\Model\Sql\Laminas\Mysql\LaminasMysqlRunner```.

This enables you to use either the ```addStraightTable``` function, or use the ```addTable``` with parameter ```straightJoin``` set to ```true```, to enable a straight join on that particular join.

This functionality does not change the Laminas-db code, but takes the query it creates and translates the correct ```INNER JOIN``` of the resulting query into a ```STRAIGHT_JOIN```

If the Database Adapter platform turns out not to by MySQL, no changes to the query are made.


Example model:

```php
<?php

declare(strict_types=1);

namespace Zalt\Model;

use Zalt\Model\Sql\JoinModel;
use Zalt\Model\Sql\Laminas\Mysql\LaminasMysqlRunner;
use Zalt\Model\Sql\Laminas\Mysql\StraightJoinModelTrait;

class SomeJoinModel extends JoinModel
{
    use StraightJoinModelTrait;
    
    public function __construct(
        MetaModelLoader $metaModelLoader,
        LaminasMysqlRunner $sqlRunner,
    )
    {
        $metaModel = new MetaModel('my_table', $metaModelLoader);
        parent::__construct($metaModel, $sqlRunner);
        $this->startJoin($metaModel->getName());

        // directly add Straight table
        $this->addStraightTable('my_second_table', ['my_table.reference_id' => 'my_second_table.id']);

        // Use the normal addTable but with the extra straightJoin. Could also be named for easier access
        $this->addTable('my_third_table', ['my_table.third_reference_id' => 'my_third_table.id'], true, 'someOtherName', true, true);
    }
}
```