<?php
/**
 * Created by PhpStorm.
 * User: yjx
 * Date: 2018/4/25
 * Time: 14:58
 */

namespace EasyDI\Exception;
use Psr\Container\ContainerExceptionInterface;

class InvalidArgumentException extends \InvalidArgumentException implements ContainerExceptionInterface
{

}