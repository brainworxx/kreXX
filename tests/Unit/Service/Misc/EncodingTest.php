<?php

/**
 * kreXX: Krumo eXXtended
 *
 * kreXX is a debugging tool, which displays structured information
 * about any PHP object. It is a nice replacement for print_r() or var_dump()
 * which are used by a lot of PHP developers.
 *
 * kreXX is a fork of Krumo, which was originally written by:
 * Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author
 *   brainworXX GmbH <info@brainworxx.de>
 *
 * @license
 *   http://opensource.org/licenses/LGPL-2.1
 *
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2024 Brainworxx GmbH
 *
 *   This library is free software; you can redistribute it and/or modify it
 *   under the terms of the GNU Lesser General Public License as published by
 *   the Free Software Foundation; either version 2.1 of the License, or (at
 *   your option) any later version.
 *   This library is distributed in the hope that it will be useful, but WITHOUT
 *   ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 *   FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 *   for more details.
 *   You should have received a copy of the GNU Lesser General Public License
 *   along with this library; if not, write to the Free Software Foundation,
 *   Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

namespace Brainworxx\Krexx\Tests\Unit\Service\Misc;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Misc\Encoding;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Encoding::class, 'mbStrLen')]
#[CoversMethod(Encoding::class, 'encodeStringForCodeGeneration')]
#[CoversMethod(Encoding::class, 'encodeString')]
#[CoversMethod(Encoding::class, 'arrayMapCallbackCode')]
#[CoversMethod(Encoding::class, 'arrayMapCallbackNormal')]
#[CoversMethod(Encoding::class, 'encodeCompletely')]
#[CoversMethod(Encoding::class, '__construct')]
class EncodingTest extends AbstractHelper
{
    /**
     * @var Encoding
     */
    protected $encoding;

    protected function setUp(): void
    {
        parent::setUp();
        $this->encoding = new Encoding(Krexx::$pool);
    }

    /**
     * Testing the setting of the pool annd the assigning of the string encoder.
     *
     * We will not test the cheap mb_string polyfills.
     */
    public function testConstruct()
    {
        $this->assertSame($this->encoding, Krexx::$pool->encodingService);
        $this->assertSame(Krexx::$pool, $this->retrieveValueByReflection('pool', $this->encoding));
    }

    /**
     * Testing the early return with an empty string.
     */
    public function testEncodeStringEmpty()
    {
        $fixture = '';
        $this->assertEquals($fixture, $this->encoding->encodeString($fixture));
    }

    /**
     * Testing the encoding of strings, also with some special stuff.
     */
    public function testEncodeStringNormal()
    {
        $fixture = 'just another string <div> { @  ';
        $expected = 'just another string &lt;div&gt; &#123; &#64;&nbsp;&nbsp;';
        $this->assertEquals($expected, $this->encoding->encodeString($fixture));

        $fixture = 'just another string <div> { @' . chr(9);
        $expected = 'just another string &lt;div&gt; &#123; &#64;&nbsp;&nbsp;';
        $this->assertEquals($expected, $this->encoding->encodeString($fixture, true));

        $fixture = random_bytes(102401);
        $expected = Krexx::$pool->messages->getHelp('stringTooLarge');
        $this->assertEquals($expected, $this->encoding->encodeString($fixture));
    }

    /**
     * We test it with a completely broken string.
     */
    public function testEncodeStringCompletelyBroken()
    {
        $mbConvertEncodingMock = $this->getFunctionMock(
            '\\Brainworxx\\Krexx\\Service\\Misc\\',
            'mb_convert_encoding'
        );
        $mbConvertEncodingMock->expects($this->once())->willReturn('');
        $fixture = random_bytes(50);
        $expected = '';
        $this->assertEquals($expected, $this->encoding->encodeString($fixture));
    }

    /**
     * Testing the encoding of strings, where html entities fail.
     */
    public function testEncodeStringBroken()
    {
        $fixture = substr('öÖäÄüÜ', 0, 3);
        $expected = '&#246;&#63;';
        $this->assertEquals($expected, $this->encoding->encodeString($fixture));

        $fixture = substr('öÖäÄüÜ', 0, 3) . chr(9);
        $expected = '&#246;&#63;&nbsp;&nbsp;';
        $this->assertEquals($expected, $this->encoding->encodeString($fixture, true));
    }

    /**
     * Testing the encoding of normal string when they are larger than 3 MB.
     */
    public function testEncodeStringNormalHuge()
    {
        $fixture = str_pad('', 3072001, 'just another string <div> { @ ');
        $expected = Krexx::$pool->messages->getHelp('stringTooLargeNormal');
        $this->assertEquals($expected, $this->encoding->encodeString($fixture));
    }

    /**
     * Testing the preparation of string as code connectors.
     */
    public function testEncodeStringForCodeGeneration()
    {
        $fixture = 'value';

        $specialChars = [
            '"' => '"',
            '\'' => '\'',
            "\0" => '\' . "\0" . \'',
            "\xEF" => '\' . "\xEF" . \'',
            "\xBB" => '\' . "\xBB" . \'',
            "\xBF" => '\' . "\xBF" . \''
        ];

        foreach ($specialChars as $original => $expected) {
            $this->assertEquals(
                $fixture . $expected,
                $this->encoding->encodeStringForCodeGeneration($fixture . $original)
            );
        }

        $this->assertSame(
            42,
            $this->encoding->encodeStringForCodeGeneration(42)
        );
    }

    /**
     * Testing the wrapper around the mb_strlen.
     */
    public function testMbStrLen()
    {
        $mbStrLen = $this->getFunctionMock('\\Brainworxx\\Krexx\\Service\\Misc\\', 'mb_strlen');
        $mbStrLen->expects($this->exactly(2))
            ->with(...$this->withConsecutive(
                ['string'],
                ['another string', 'some encoding']
            ))->willReturn(42);

        $this->assertEquals(42, $this->encoding->mbStrLen('string'));
        $this->assertEquals(42, $this->encoding->mbStrLen('another string', 'some encoding'));
    }
}
