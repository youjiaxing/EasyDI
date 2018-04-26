<?php
namespace EasyDI\Test\UnitTest;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\Exception;
use PHPUnit\Framework\TestCase;
use EasyDI\Container;
use Psr\Container\ContainerInterface;

/**
 * .\vendor\bin\phpunit --bootstrap .\vendor\autoload.php .\tests\UnitTest\ContainerTest.php
 *
 * Class ContainerTest
 * @package EasyDI\Test\UnitTest
 */
class ContainerTest extends TestCase
{
    function testString()
    {
        $c = new Container();
        $c->raw('param', 'value');
//        echo $c->get('param');
        Assert::assertEquals('value', $c->get('param'));
    }

    function testClosure()
    {
        $c = new Container();
        $c->set('service', function () {
            return new \Exception("");
        });
        Assert::assertInstanceOf("\\Exception", $c->get('service'));
    }

    function testClosureWithParams()
    {
        $c = new Container();
        $c->set('service', function ($msg) {
            return "Hello $msg";
        });
        $c->set('service2', function ($msg="", $msg2=null) {
            return "Hello{$msg}{$msg2}";
        });
        $msg = 'testMsg';
        Assert::assertEquals("Hello $msg", $c->get('service', [$msg]));
        Assert::assertEquals("Hello", $c->get('service2'));
        Assert::assertEquals("Hello123", $c->get('service2', [123]));
        Assert::assertEquals("Hello123456", $c->get('service2', [123, 456]));
        Assert::assertEquals("Hello456", $c->get('service2', [123, 'msg'=>456]));
    }

    function testServiceShouldBeDifferent()
    {
        $c = new Container();
        $c->set('service', function () {
            return new \Exception(rand(0,100));
        });
        Assert::assertNotSame($c->get('service'), $c->get('service'));
    }

    function testServiceShouldBeSame()
    {
        $c = new Container();
        $c->singleton('service', function () {
            return new \Exception(rand(0,100));
        });
        Assert::assertSame($c->get('service'), $c->get('service'));
    }

    function testShouldPassContainerAsParameter()
    {
        $c = new Container();
        $c->raw('param', 123);
        $c->set('service1', function () {
            return new Exception("");
        });
        $c->set('service2', function (ContainerInterface $c) {
            return $c;
        });
        $c2 = new Container();

        Assert::assertNotSame($c, $c->get('service1'));
        Assert::assertSame($c, $c->get('service2'));
        Assert::assertNotSame($c, $c->get('service2', [$c2]));
        Assert::assertNotSame($c, $c->get('service2', ['c'=>$c2]));
        Assert::assertSame($c2, $c->get('service2', ['c'=>$c2]));
    }

    function testHas()
    {
        $c = new Container();
        $c->raw('param', 123);
        $c->set('service1', function () {
            return new Exception("");
        });
        $c->set('service2', function (ContainerInterface $c) {
            return $c;
        }, true);

        Assert::assertTrue($c->has('param'));
        Assert::assertTrue($c->has('service1'));
        Assert::assertTrue($c->has('service2'));
        Assert::assertNotTrue($c->has('not_exists'));
    }

    /**
     * @expectedException \Psr\Container\NotFoundExceptionInterface
     */
    public function testGetInvalidId()
    {
        $c = new Container();
        $c->get('foo');
    }

    /**
     * @expectedException \Psr\Container\ContainerExceptionInterface
     */
    public function testGetUnInstantiate()
    {
        $c = new Container();
        $c->set('foo', function (Exception $e){});
        $c->get('foo');
    }

    public function testGetNullValue()
    {
        $c = new Container();
        $c->raw('t1', null);
        $c->set('t2', "\\Exception");
        Assert::assertNull($c->get('t1'));
        Assert::assertNotNull($c->get('t2', [123]));
    }

    public function testUnset()
    {
        $c = new Container();
        $c->raw('t1', null);
        $c->set('t2', "\\Exception");
        $c->unset('t1');
        $c->unset('t2');
        Assert::assertFalse($c->has('t1'));
        Assert::assertFalse($c->has('t2'));
    }

    public function testShare()
    {
        $c = new Container();
        $c->singleton('shared_service', function () {
            return new \LogicException();
        });
        $serviceOne = $c->get('shared_service');
        Assert::assertInstanceOf('\\Exception', $serviceOne);
        $serviceTwo = $c->get('shared_service');
        Assert::assertInstanceOf('\\LogicException', $serviceTwo);
        Assert::assertSame($serviceOne, $serviceTwo);
    }

    public function testRaw()
    {
        $c = new Container();
        $raw1 = new \LogicException();
        $c->set('raw1', $raw1);
        $raw2 = "test";
        $c->raw('raw2', $raw2);
        $raw3 = new \ErrorException();
        $c->raw('raw3', $raw3);
        $raw4 = function () {return 'foo';};
        $c->raw('raw4', $raw4);
        $c->set('noraw', $raw4);
        $raw5 = function () {return new \LogicException();};
        $c->set('raw5', $raw5);
        $c->singleton('raw6', $raw5);

        Assert::assertSame($c->get('raw1'), $raw1);
        Assert::assertSame($c->get('raw2'), $raw2);
        Assert::assertSame($c->get('raw3'), $raw3);
        Assert::assertSame($c->get('raw4'), $raw4);
        Assert::assertNotSame($c->get('noraw'), $raw4);
        $exception1 = $c->get('raw5');
        $exception2 = $c->get('raw5');
        Assert::assertNotSame($exception1, $exception2);
        $exception3 = $c->get('raw6');
        $exception4 = $c->get('raw6');
        Assert::assertSame($exception3, $exception4);
    }

    public function testKeys()
    {
        $c = new Container();
        $c->raw('t1', 123);
        $c->set('t2', 234);
//        var_dump($c->keys());
        Assert::assertArrayHasKey('t1', array_flip($c->keys()));
        Assert::assertArrayHasKey('t2', array_flip($c->keys()));
        Assert::assertArrayNotHasKey('t3', array_flip($c->keys()));
    }

    public function testAutoDependenciesInject()
    {
        $c = new Container();
        $c->set(\Exception::class, \LogicException::class);
        $c->set('service', TestService::class);
        $service = $c->get('service');
        Assert::assertInstanceOf(TestService::class, $service);
    }

    public function testComplextAutoDI()
    {
        $c = new Container();
//        echo "\n".str_pad('-',60,'-')."\n";
        $c->set(TestC::class, TestC::class);
        $c->set(TestA::class, TestA::class);
        $c->set(TestB::class, TestB::class);
        $testB = $c->get(TestB::class);
        Assert::assertInstanceOf(TestB::class, $testB);
        Assert::assertEquals("TestB -> TestA -> TestC", $testB->show());
        Assert::assertEquals("TestB -> TestA -> TestC", $c->call([$testB, 'show']));
//        var_dump($testB->anotherShow());
//        var_dump($testB->show2($c->get(TestC::class)));
//        var_dump($testB->show2($c->get(TestC::class), "123"));

        Assert::assertEquals("TestB() -> TestC", $c->call([$testB, 'show2']));
//        var_dump($c->call([$testB, 'show2'], [1=>123]));
//        var_dump($c->call([$testB, 'show2'], ['context'=>123]));
        Assert::assertEquals("TestB(123) -> TestC", $c->call([$testB, 'show2'], [1=>123]));
        Assert::assertEquals("TestB(123) -> TestC", $c->call([$testB, 'show2'], ['context'=>123]));

//        var_dump($c->call([$testB, 'staticShow']));
        Assert::assertEquals("TestB::staticShow,TestC,TestA -> TestC", $c->call([$testB, 'staticShow']));
        Assert::assertEquals("TestB::staticShow,TestC,TestA -> TestC", $c->call([TestB::class, 'staticShow']));
        Assert::assertEquals("TestB::staticShow2,TestC,123", $c->call([TestB::class, 'staticShow2'], ['context'=>123]));
    }

    /**
     * @expectedException \Psr\Container\ContainerExceptionInterface
     */
    public function testUnInstantiable()
    {
        $c = new Container();
        $c->set('service', TestServicePrivateConstruct::class);
        $c->get('service');
    }

    public function testCall()
    {
        $c = new Container();

        Assert::assertSame('test', $c->call('EasyDI\Test\UnitTest\testFunc'));

        Assert::assertSame('test', $c->call(function (UserManager $tmp) {
            return 'test';
        }));

        Assert::assertSame('send to 1@1.1 with 123', $c->call([UserManager::class, 'register'], ['password'=>123, 'email'=>'1@1.1']));

        Assert::assertSame('send to 1@1.1 with 123', $c->call([UserManager::class, 'staticRegister'], ['password'=>123, 'email'=>'1@1.1']));

        Assert::assertSame('send to 1@1.1 with 123', $c->call([new UserManager(new Mailer()), 'register'], ['password'=>123, 'email'=>'1@1.1']));

        Assert::assertSame('send to 1@1.1 with 123', $c->call([new UserManager(new Mailer()), 'staticRegister'], ['password'=>123, 'email'=>'1@1.1']));
    }
}

class TestService
{
    function __construct(ContainerInterface $c, \Exception $e)
    {
    }
}

class TestServicePrivateConstruct
{
    private function __construct()
    {
    }
}

class TestA
{
    protected $c;
    public function __construct(TestC $c)
    {
        $this->c = $c;
    }

    public function show()
    {
        return "TestA"." -> ".$this->c->show();
    }
}

class TestB
{
    protected $a;
    public function __construct(TestA $a)
    {
        $this->a = $a;
    }

    public function show2(TestC $c, $context="")
    {
        return "TestB({$context})"." -> ".$c->show();
    }

    public function show3(TestC $c, $context)
    {
        return "TestB({$context})"." -> ".$c->show();
    }

    public function show()
    {
        return "TestB"." -> ".$this->a->show();
    }

    public static function staticShow(TestC $c, TestA $a=null)
    {
        return "TestB::staticShow,{$c->show()},{$a->show()}";
    }

    public static function staticShow2(TestC $c, $context)
    {
        return "TestB::staticShow2,{$c->show()},{$context}";
    }
}

class TestC
{
    public function show()
    {
        return "TestC";
    }
}

class Mailer
{
    public function mail($recipient, $content)
    {
        // send an email to the recipient
    }
}

class UserManager
{
    private $mailer;

    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function register($email, $password)
    {
        // The user just registered, we create his account
        // ...

        // We send him an email to say hello!
        return "send to $email with $password";
    }

    public static function staticRegister(Mailer $mailer, $email, $password)
    {
        return "send to $email with $password";
    }
}

function testFunc(UserManager $manager)
{
    return "test";
}