<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\StructuredData;

use Maispace\MaiSeo\StructuredData\PlainText;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PlainTextTest extends TestCase
{
    #[Test]
    public function fromRemovesTagsAndDecodesEntities(): void
    {
        self::assertSame(
            'Am 18. November & um 18 Uhr',
            PlainText::from('<p>Am <strong>18. November</strong> &amp; um&nbsp;18 Uhr</p>'),
        );
    }

    #[Test]
    public function fromSeparatesParagraphsWithASpace(): void
    {
        self::assertSame('Eins. Zwei.', PlainText::from('<p>Eins.</p><p>Zwei.</p>'));
    }

    #[Test]
    public function fromReturnsEmptyStringForNonScalarValues(): void
    {
        self::assertSame('', PlainText::from(null));
        self::assertSame('', PlainText::from(['a']));
    }

    #[Test]
    public function firstOfSkipsEmptyValues(): void
    {
        self::assertSame('Zweiter', PlainText::firstOf('', null, '<p> </p>', '<b>Zweiter</b>', 'Dritter'));
    }
}
