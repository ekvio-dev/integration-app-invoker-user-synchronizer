<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\Tests\Unit\UserFactory;

use Ekvio\Integration\Invoker\UserFactory\PhoneBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Class PhoneBuilderTest
 * @package Ekvio\Integration\Invoker\Tests\Unit\UserFactory
 */
class PhoneBuilderTest extends TestCase
{
    public function testBuildUserPhoneFromNoValidSymbols()
    {
        $phone = PhoneBuilder::build('abcdef');
        $this->assertNull($phone);
    }

    public function testBuildUserPhoneFromTenSymbols()
    {
        $phone = PhoneBuilder::build('9275000000');
        $this->assertEquals('79275000000', $phone);
    }

    public function testBuildUserPhoneFromElevenSymbols()
    {
        $phone = PhoneBuilder::build('89275000000');
        $this->assertEquals('79275000000', $phone);

        $phone = PhoneBuilder::build('+79275000000');
        $this->assertEquals('79275000000', $phone);
    }
}