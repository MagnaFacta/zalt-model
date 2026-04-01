<?php

namespace Zalt\Model\Mapping\Database;

enum JoinType: string
{
    case INNER = 'INNER';
    case LEFT = 'LEFT';

}
