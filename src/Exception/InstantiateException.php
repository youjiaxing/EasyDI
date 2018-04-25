<?php
/**
 * Created by PhpStorm.
 * User: yjx
 * Date: 2018/4/24
 * Time: 16:46
 */

namespace EasyDI\Exception;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class InstantiateException extends \LogicException implements ContainerExceptionInterface
{
    protected $context = [];

    public function __construct($id, $context = [], $code=0, Exception $previous = null)
    {
        $this->context = $context;
        parent::__construct(sprintf("Identifier %s is unable to instantiate! context=%s", $id, json_encode($this->context, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)), $code, $previous);
    }
}