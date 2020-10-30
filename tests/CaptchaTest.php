<?php

namespace Padosoft\InvisibleReCaptcha\Tests;

use Padosoft\InvisibleReCaptcha\InvisibleReCaptchaServiceProvider;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use PHPUnit\Framework\TestCase;
use Padosoft\InvisibleReCaptcha\InvisibleReCaptcha;
use ReflectionClass;

class CaptchaTest extends TestCase
{
    const SITE_KEY   = 'site_key';
    const SECRET_KEY = 'secret_key';
    const OPTIONS    = [
        'hideBadge' => false,
        'dataBadge' => 'bottomright',
        'timeout' => 5,
        'debug' => false
    ];

    protected $captcha;

    protected function setUp(): void
    {
        $this->captcha = new InvisibleReCaptcha(
            static::SITE_KEY,
            static::SECRET_KEY,
            static::OPTIONS
        );
    }

    public function testConstructor()
    {
        self::assertEquals(static::SITE_KEY, $this->captcha->getSiteKey());
        self::assertEquals(static::SECRET_KEY, $this->captcha->getSecretKey());
    }

    public function testGetOptions()
    {
        self::assertEquals(static::OPTIONS, $this->captcha->getOptions());
    }

    public function testSetOption()
    {
        $this->captcha->setOption('debug', true);
        $this->captcha->setOption('timeout', 10);
        self::assertEquals(10, $this->captcha->getOption('timeout'));
        self::assertTrue($this->captcha->getOption('debug'));
    }

    public function testGetCaptchaJs()
    {
        $js = 'https://www.google.com/recaptcha/api.js';

        self::assertEquals($js, $this->captcha->getCaptchaJs());
        self::assertEquals($js . '?hl=us', $this->captcha->getCaptchaJs('us'));
    }

    public function testGetPolyfillJs()
    {
        $js = 'https://cdn.polyfill.io/v2/polyfill.min.js';

        self::assertEquals($js, $this->captcha->getPolyfillJs());
    }

    public function testSendVerifyRequest(){
        $class = new ReflectionClass('Padosoft\InvisibleReCaptcha\InvisibleReCaptcha');
        $method = $class->getMethod('sendVerifyRequest');
        $method->setAccessible(true);
        $ret=$method->invoke($this->captcha,[
                                            'secret' => static::SECRET_KEY,
                                            'remoteip' => '127.0.0.1',
                                            'response' => '12321231321'
                                        ]);
        self::assertIsArray($ret);
        self::assertTrue(array_key_exists('success',$ret));
    }
    public function testVerifyRequest()
    {
        $request = $this->getMockBuilder('Illuminate\Http\Request')
                        ->disableOriginalConstructor()
                        ->onlyMethods(['get', 'getClientIp'])->getMock();
        $request->expects(self::any())
                ->method('get')->willReturn('123131212');

        $request->expects(self::any())
                ->method('getClientIp')->willReturn('192.168.1.1');
        self::assertFalse($this->captcha->verifyRequest($request));

    }

    public function testBladeDirective()
    {
        $app = Container::getInstance();
        $app->instance('captcha', $this->captcha);

        $blade = new BladeCompiler(
            $this->getMockBuilder(Filesystem::class)->disableOriginalConstructor()->getMock(),
            __DIR__
        );

        $serviceProvider = new InvisibleReCaptchaServiceProvider($app);
        $serviceProvider->addBladeDirective($blade);

        $result = $blade->compileString('@captcha()');
        self::assertEquals(
            "<?php echo app('captcha')->render(); ?>",
            $result
        );
    }
}
