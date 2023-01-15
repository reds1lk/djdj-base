<?php

namespace Djdj\Base\model;

use think\Model;

class RpcLog extends Model
{
    protected $pk = '_id';

    protected $connection = 'mongodb';
}
