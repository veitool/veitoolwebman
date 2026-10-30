<?php

declare(strict_types=1);

namespace Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Webman\Limiter\Annotation\Limit as BaseLimit;
use Webman\Limiter\Middleware;
use Webman\Route\Route;

final class FunctionAndClosureAttributeTest extends TestCase
{
    public function testCanApplyLimitAttributeToNamedFunction(): void
    {
        $functionName = 'test_limit_attr_fn_' . bin2hex(random_bytes(6));
        $fqfn = __NAMESPACE__ . '\\' . $functionName;

        $code = sprintf(
            <<<'PHP'
namespace %s;

#[\support\limiter\annotation\Limit(2, 10, 'k')]
function %s(): void {}
PHP,
            __NAMESPACE__,
            $functionName
        );

        eval($code);

        $ref = new \ReflectionFunction($fqfn);
        $attrs = $ref->getAttributes(BaseLimit::class, \ReflectionAttribute::IS_INSTANCEOF);

        $this->assertCount(1, $attrs);
        $instance = $attrs[0]->newInstance();
        $this->assertSame(2, $instance->limit);
        $this->assertSame(10, $instance->ttl);
        $this->assertSame('k', $instance->key);
    }

    public function testMiddlewareCanReadLimitAttributeFromRouteClosureCallback(): void
    {
        $closure = eval('return #[\\support\\limiter\\annotation\\Limit(1, 5, "k")] function (): void {};');

        $route = new Route(['GET'], '/t', $closure);

        $request = (new \ReflectionClass(\Webman\Http\Request::class))->newInstanceWithoutConstructor();
        $request->controller = null;
        $request->action = null;
        $request->route = $route;

        $middleware = new Middleware();
        $getAttributes = new \ReflectionMethod($middleware, 'getAttributes');
        $getAttributes->setAccessible(true);

        [$keyPrefix, $annotations] = $getAttributes->invoke($middleware, $request);

        $this->assertNotSame('', $keyPrefix);
        $this->assertCount(1, $annotations);
        $this->assertSame(1, $annotations[0]->limit);
        $this->assertSame(5, $annotations[0]->ttl);
        $this->assertSame('k', $annotations[0]->key);
    }

    public function testMiddlewareCanReadLimitAttributeFromRouteFunctionCallback(): void
    {
        $functionName = 'test_limit_attr_route_fn_' . bin2hex(random_bytes(6));
        $fqfn = __NAMESPACE__ . '\\' . $functionName;

        $code = sprintf(
            <<<'PHP'
namespace %s;

#[\support\limiter\annotation\Limit(3, 7, 'k')]
function %s(): void {}
PHP,
            __NAMESPACE__,
            $functionName
        );

        eval($code);

        $route = new Route(['GET'], '/t', $fqfn);

        $request = (new \ReflectionClass(\Webman\Http\Request::class))->newInstanceWithoutConstructor();
        $request->controller = null;
        $request->action = null;
        $request->route = $route;

        $middleware = new Middleware();
        $getAttributes = new \ReflectionMethod($middleware, 'getAttributes');
        $getAttributes->setAccessible(true);

        [$keyPrefix, $annotations] = $getAttributes->invoke($middleware, $request);

        $this->assertSame($fqfn, $keyPrefix);
        $this->assertCount(1, $annotations);
        $this->assertSame(3, $annotations[0]->limit);
        $this->assertSame(7, $annotations[0]->ttl);
        $this->assertSame('k', $annotations[0]->key);
    }
}

