# EasyDI
EasyDI是一个具有自动依赖注入的小型容器, 遵循PSR-11.

容器提供方法:
- `raw(string $id, mixed $value)`   
适用于保存参数, `$value`可以是任何类型, 容器不会对其进行解析.  

- `set(string $id, \Closure|array|string $value, array $params=[], bool $shared=false)`    
定义服务

- `singleton(string $id, \Closure|array|string $value, array $params=[])`    
等同调用`set($id, $value, $params, true)`

- `has(string $id)`    
判断容器是否包含$id对应条目

- `get(string $id, array $params = [])`  
从容器中获取$id对应条目, 可选参数$params可优先参与到条目实例化过程中的依赖注入

- `call(callable $function, array $params=[])`    
利用容器来调用callable, 由容器自动注入依赖.

- `unset(string $id)`  
从容器中移除$id对应条目

# 安装 Installation
Install EasyDi with [Composer](http://getcomposer.org/doc/00-intro.html)

`composer require yjx/easy-di`

# 基础使用 Basic Usage
## 容器的实例化
```php
use EasyDi\Container();

$container = new Container();
```
EasyDI容器管理两种类型的数据: 服务 和 参数(raw)

## 定义服务和参数
```php
use Psr\Container\ContainerInterface;
use EasyDI\Container();

$c = new EasyDI\Container();

// 定义参数
$c->raw('redis.host', "127.0.0.1");

// 使用闭包
$c->set('redis', function(ContainerInterface $c) {
  $redis = new Redis();
  $redis->pconnect($c->get('redis.host'));
  return $redis;
}, [], true);

$reids = $c->get('redis');  // 由于set时第4个参数$shared设置为true, 因此每次获取的都是同一个对象
```
> 闭包中的参数`$c`由于使用了类型指示, 因此EasyDI会自动完成依赖注入(将它自身注入).

## 自动依赖解决
### [示例1](http://php-di.org/doc/getting-started.html)
> 直接拿PHP-DI官方演示代码
```php
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
        $this->mailer->mail($email, 'Hello and welcome!');
    }
}

$c = new EasyDI\Container();
$userManager = $c->get('UserManager');

// 等价执行
//$mailer = new Mailer();
//$userManager = new UserManager($mailer);
```

### 示例2
```php
class ClassA
{
    protected $b;
    protected $say;

    public function __construct(ClassB $b, ClassC $c, $say="hello")
    {
        $this->b = $b;
        $this->say = $say;
    }

    public function saySth()
    {
      return "{$this->say} {$this->b->saySth()}";
    }
}

class ClassB
{
    protected $msg;

    public function __construct($msg)
    {
        $this->msg = $msg;
    }

    public function saySth()
    {
        return $this->msg;
    }
}

class ClassC
{
}

// 容器实例化
$c = new EasyDI\Container();

// 最基础的配置方式, 未用到容器的依赖注入特性
$c->set('basic', function () {
    return new ClassA(new ClassB("easy-di"), new ClassC(), "I like");
});
$basicService = $c->get('basic');
echo $basicService->saySth().PHP_EOL;   // 输出: I like easy-di

// 利用容器自动解决依赖
$c->set(ClassB::class, ClassB::class, ['easy-di']);         // 配置ClassB的标量依赖, params 等同配置 ['msg'=>"easy-di"]
$c->set('advance', ClassA::class, [2=>"I really like"]);    // 配置advance服务, params 等同配置 ['say'=>"I really like"]
$advanceService = $c->get('advance');                       // ClassA实例化所需的第2个参数$c由容器自动生成实例
echo $advanceService->saySth().PHP_EOL; // 输出: I really like easy-di
```
