<?php
/**
 * Created by PhpStorm.
 * User: yjx
 * Date: 2018/4/24
 * Time: 14:58
 */

namespace EasyDI\Exception;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class UnknownIdentifierException extends \InvalidArgumentException implements NotFoundExceptionInterface
{
    public function __construct($id, $code = 0, Exception $previous = null)
    {
        parent::__construct(sprintf('Identifier %s is not defined!', $id), $code, $previous);
    }

}