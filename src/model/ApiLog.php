<?php

namespace Djdj\Base\model;

use think\Model;

class ApiLog extends Model
{
    protected $pk = '_id';

    protected $connection = 'mongodb';
}
