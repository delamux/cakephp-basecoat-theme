<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\Bake;

use BasecoatTheme\Bake\PhpLiteral;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use stdClass;

/**
 * PHP short-array literals written into baked code.
 */
class PhpLiteralTest extends TestCase
{
    public function testScalars(): void
    {
        $this->assertSame('null', PhpLiteral::export(null));
        $this->assertSame('true', PhpLiteral::export(true));
        $this->assertSame('false', PhpLiteral::export(false));
        $this->assertSame('42', PhpLiteral::export(42));
        $this->assertSame('-7', PhpLiteral::export(-7));
        $this->assertSame('1.5', PhpLiteral::export(1.5));
        $this->assertSame('2.0', PhpLiteral::export(2.0));
        $this->assertSame("'Harbor & Pine'", PhpLiteral::export('Harbor & Pine'));
        $this->assertSame('[]', PhpLiteral::export([]));
    }

    public function testQuotesAndBackslashes(): void
    {
        $this->assertSame("'it\\'s'", PhpLiteral::export("it's"));
        $this->assertSame("'C:\\\\fernway\\\\'", PhpLiteral::export('C:\\fernway\\'));
        $this->assertSame("'\"double\" \$var {\$x}'", PhpLiteral::export('"double" $var {$x}'));
    }

    public function testNestedListAndAssoc(): void
    {
        $value = [
            'people' => [
                ['name' => 'Elena Sarto', 'admin' => true],
                ['name' => 'Kofi Mensah', 'admin' => false],
            ],
            'tags' => ['ops', 'billing'],
            'empty' => [],
            7 => 'seven',
        ];
        $expected = <<<'PHP'
[
    'people' => [
        [
            'name' => 'Elena Sarto',
            'admin' => true,
        ],
        [
            'name' => 'Kofi Mensah',
            'admin' => false,
        ],
    ],
    'tags' => [
        'ops',
        'billing',
    ],
    'empty' => [],
    7 => 'seven',
]
PHP;
        $this->assertSame($expected, PhpLiteral::export($value));
        $this->assertSame($value, eval('return ' . PhpLiteral::export($value) . ';'));
    }

    public function testIndentShiftsInnerLinesOnly(): void
    {
        $this->assertSame("[\n            'a',\n        ]", PhpLiteral::export(['a'], 2));
    }

    public function testUtf8RoundTrip(): void
    {
        $value = [
            'items' => [
                ['name' => 'Café <b>"x"</b> \'q\' \\', 'total' => '€12', 'ok' => true, 'n' => null],
                ['name' => "Ines Duarte — Ostrava Freight\nline two\t☕", 'total' => 12.75, 'count' => 3],
            ],
        ];
        $literal = PhpLiteral::export($value);
        $this->assertStringContainsString("'€12'", $literal);
        $this->assertSame($value, eval('return ' . $literal . ';'));
    }

    public function testObjectsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PhpLiteral::export(['x' => new stdClass()]);
    }
}
