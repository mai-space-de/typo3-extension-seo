<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\StructuredData\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StructuredDataValidationTest extends TestCase
{
    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    private function encodeJsonLd(array $graph): string
    {
        return json_encode($graph, self::JSON_FLAGS);
    }

    private function assertValidJsonLd(string $json, string $expectedType): array
    {
        $decoded = json_decode($json, true);
        self::assertNotNull($decoded);
        self::assertSame(JSON_ERROR_NONE, json_last_error());
        self::assertArrayHasKey('@context', $decoded);
        self::assertSame('https://schema.org', $decoded['@context']);
        self::assertArrayHasKey('@type', $decoded);
        self::assertSame($expectedType, $decoded['@type']);

        return $decoded;
    }

    #[Test]
    public function organizationJsonLdHasRequiredGoogleProperties(): void
    {
        $decoded = $this->assertValidJsonLd($this->encodeJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Stadt Pulheim',
            'url' => 'https://www.bgm-pulheim.org',
            'logo' => ['@type' => 'ImageObject', 'url' => 'https://www.bgm-pulheim.org/logo.svg'],
        ]), 'Organization');

        self::assertArrayHasKey('name', $decoded);
        self::assertSame('Stadt Pulheim', $decoded['name']);
        self::assertArrayHasKey('url', $decoded);
        self::assertSame('https://www.bgm-pulheim.org', $decoded['url']);
        self::assertArrayHasKey('logo', $decoded);
        self::assertIsArray($decoded['logo']);
        self::assertSame('ImageObject', $decoded['logo']['@type']);
        self::assertStringStartsWith('https://', $decoded['logo']['url']);
    }

    #[Test]
    public function organizationJsonLdHandlesMissingLogo(): void
    {
        $decoded = $this->assertValidJsonLd($this->encodeJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Test Org',
            'url' => 'https://example.com',
        ]), 'Organization');

        self::assertArrayNotHasKey('logo', $decoded);
    }

    #[Test]
    public function webPageJsonLdHasRequiredGoogleProperties(): void
    {
        $decoded = $this->assertValidJsonLd($this->encodeJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => 'About Us',
            'url' => 'https://www.bgm-pulheim.org/about',
            'datePublished' => '2024-01-15T10:00:00+01:00',
            'dateModified' => '2024-06-20T14:30:00+02:00',
        ]), 'WebPage');

        self::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $decoded['datePublished'],
        );
        self::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $decoded['dateModified'],
        );
    }

    #[Test]
    public function webPageJsonLdOmitsEmptyOptionalFields(): void
    {
        $decoded = $this->assertValidJsonLd($this->encodeJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => 'Minimal Page',
        ]), 'WebPage');

        self::assertArrayNotHasKey('description', $decoded);
        self::assertArrayNotHasKey('datePublished', $decoded);
        self::assertArrayNotHasKey('dateModified', $decoded);
    }

    #[Test]
    public function breadcrumbListJsonLdHasValidListItemStructure(): void
    {
        $decoded = $this->assertValidJsonLd($this->encodeJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://www.bgm-pulheim.org/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'About', 'item' => 'https://www.bgm-pulheim.org/about'],
            ],
        ]), 'BreadcrumbList');

        self::assertCount(2, $decoded['itemListElement']);
        foreach ($decoded['itemListElement'] as $i => $item) {
            self::assertSame('ListItem', $item['@type'], "Item {$i} type");
            self::assertSame($i + 1, $item['position'], "Item {$i} position");
            self::assertArrayHasKey('name', $item);
            self::assertStringStartsWith('https://', $item['item']);
        }
    }

    #[Test]
    public function faqPageJsonLdHasValidQuestionAnswerStructure(): void
    {
        $decoded = $this->assertValidJsonLd($this->encodeJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => 'What are your opening hours?',
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => '<p>Mon-Fri 9am-5pm.</p>'],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'How do I register?',
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => '<p>Register online.</p>'],
                ],
            ],
        ]), 'FAQPage');

        self::assertCount(2, $decoded['mainEntity']);
        foreach ($decoded['mainEntity'] as $i => $entry) {
            self::assertSame('Question', $entry['@type'], "Entry {$i} type");
            self::assertArrayHasKey('name', $entry);
            self::assertArrayHasKey('acceptedAnswer', $entry);
            self::assertSame('Answer', $entry['acceptedAnswer']['@type']);
            self::assertArrayHasKey('text', $entry['acceptedAnswer']);
        }
    }

    #[Test]
    public function faqPageJsonLdHandlesQuestionWithoutAnswer(): void
    {
        $decoded = $this->assertValidJsonLd($this->encodeJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [['@type' => 'Question', 'name' => 'No answer yet?']],
        ]), 'FAQPage');

        self::assertArrayNotHasKey('acceptedAnswer', $decoded['mainEntity'][0]);
    }

    #[Test]
    public function eventJsonLdHasValidGoogleStructure(): void
    {
        $decoded = $this->assertValidJsonLd($this->encodeJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => 'Summer Concert',
            'startDate' => '2024-11-14T18:00:00+01:00',
            'endDate' => '2024-11-14T20:00:00+01:00',
            'eventStatus' => 'https://schema.org/EventScheduled',
            'location' => ['@type' => 'Place', 'name' => 'City Hall'],
        ]), 'Event');

        self::assertArrayHasKey('name', $decoded);
        self::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $decoded['startDate'],
        );
        self::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $decoded['endDate'],
        );
        self::assertStringStartsWith('https://schema.org/', $decoded['eventStatus']);
        self::assertIsArray($decoded['location']);
        self::assertSame('Place', $decoded['location']['@type']);
        self::assertArrayHasKey('name', $decoded['location']);
    }

    #[Test]
    public function jobPostingJsonLdHasRequiredGoogleProperties(): void
    {
        $decoded = $this->assertValidJsonLd($this->encodeJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => 'Software Engineer',
            'description' => 'Build great software.',
            'directApply' => true,
            'validThrough' => '2024-12-31T23:59:59+01:00',
        ]), 'JobPosting');

        self::assertNotEmpty($decoded['title']);
        self::assertArrayHasKey('description', $decoded);
        self::assertIsBool($decoded['directApply']);
        self::assertTrue($decoded['directApply']);
        self::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $decoded['validThrough'],
        );
    }

    #[Test]
    public function newsArticleJsonLdHasRequiredGoogleProperties(): void
    {
        $decoded = $this->assertValidJsonLd($this->encodeJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => 'Local Event Draws Crowd',
            'datePublished' => '2024-10-01T12:00:00+02:00',
            'author' => ['@type' => 'Person', 'name' => 'Jane Doe'],
        ]), 'NewsArticle');

        self::assertNotEmpty($decoded['headline']);
        self::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $decoded['datePublished'],
        );
        self::assertIsArray($decoded['author']);
        self::assertSame('Person', $decoded['author']['@type']);
        self::assertArrayHasKey('name', $decoded['author']);
    }

    #[Test]
    public function newsArticleJsonLdHandlesMissingAuthor(): void
    {
        $decoded = $this->assertValidJsonLd($this->encodeJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => 'Headline Only',
        ]), 'NewsArticle');

        self::assertArrayNotHasKey('author', $decoded);
        self::assertArrayNotHasKey('datePublished', $decoded);
    }

    #[Test]
    public function personJsonLdHasRequiredGoogleProperties(): void
    {
        $decoded = $this->assertValidJsonLd($this->encodeJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => 'John Doe',
            'jobTitle' => 'Developer',
            'email' => 'john@example.com',
        ]), 'Person');

        self::assertSame('John Doe', $decoded['name']);
        self::assertSame('Developer', $decoded['jobTitle']);
        self::assertSame('john@example.com', $decoded['email']);
    }

    #[Test]
    public function placeJsonLdHasRequiredProperties(): void
    {
        $decoded = $this->assertValidJsonLd($this->encodeJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'Place',
            'name' => 'City Hall',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => '123 Main St',
                'addressLocality' => 'Cologne',
                'postalCode' => '50667',
                'addressCountry' => 'DE',
            ],
        ]), 'Place');

        self::assertIsArray($decoded['address']);
        self::assertSame('PostalAddress', $decoded['address']['@type']);
        self::assertArrayHasKey('addressCountry', $decoded['address']);
    }

    #[Test]
    public function localBusinessJsonLdHasRequiredGoogleProperties(): void
    {
        $decoded = $this->assertValidJsonLd($this->encodeJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => 'BGM Pulheim',
            'url' => 'https://www.bgm-pulheim.org',
            'address' => ['@type' => 'PostalAddress', 'addressCountry' => 'DE'],
            'geo' => ['@type' => 'GeoCoordinates', 'latitude' => 50.9997, 'longitude' => 6.8022],
        ]), 'LocalBusiness');

        self::assertSame('PostalAddress', $decoded['address']['@type']);
        self::assertSame('GeoCoordinates', $decoded['geo']['@type']);
        self::assertIsFloat($decoded['geo']['latitude']);
        self::assertIsFloat($decoded['geo']['longitude']);
    }

    #[Test]
    public function jsonLdEncodesSpecialCharactersCorrectly(): void
    {
        $json = $this->encodeJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Bürgergemeinschaft Pulheim e.V. (mit Umlauten & Sonderzeichen)',
            'description' => 'Straße 1, "Anführungszeichen", & €',
        ]);

        self::assertStringContainsString('Bürgergemeinschaft', $json);
        self::assertStringContainsString('Umlauten', $json);
        self::assertStringContainsString('€', $json);
        self::assertStringNotContainsString('\/', $json);

        $decoded = $this->assertValidJsonLd($json, 'Organization');
        self::assertStringContainsString('Straße', $decoded['description']);
    }

    #[Test]
    public function jsonLdHandlesDeeplyNestedStructures(): void
    {
        $decoded = $this->assertValidJsonLd($this->encodeJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Nested Org',
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'City', 'addressCountry' => 'DE'],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => '+49 1234',
                'contactType' => 'customer service',
                'areaServed' => ['DE', 'AT', 'CH'],
            ],
        ]), 'Organization');

        self::assertArrayHasKey('address', $decoded);
        self::assertArrayHasKey('contactPoint', $decoded);
        self::assertArrayHasKey('areaServed', $decoded['contactPoint']);
        self::assertIsArray($decoded['contactPoint']['areaServed']);
        self::assertCount(3, $decoded['contactPoint']['areaServed']);
    }

    #[Test]
    public function jsonLdEncodesUrlsWithoutSlashesEscaping(): void
    {
        $json = $this->encodeJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'URL Test',
            'url' => 'https://example.com/path?query=value&lang=de',
        ]);

        self::assertStringNotContainsString('\/', $json);

        $decoded = $this->assertValidJsonLd($json, 'Organization');
        self::assertSame('https://example.com/path?query=value&lang=de', $decoded['url']);
    }

    #[Test]
    public function jsonLdPreservesHtmlInTextFields(): void
    {
        $decoded = $this->assertValidJsonLd($this->encodeJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => 'Page with HTML',
            'description' => '<p>Paragraph with <strong>bold</strong> and <a href="https://example.com">link</a>.</p>',
        ]), 'WebPage');

        self::assertStringContainsString('<p>', $decoded['description']);
        self::assertStringContainsString('</p>', $decoded['description']);
        self::assertStringContainsString('<strong>', $decoded['description']);
    }

    /** @return array<string, array{0: array}> */
    public static function allSchemaTypesDataProvider(): array
    {
        return [
            'Organization' => [[
                '@context' => 'https://schema.org', '@type' => 'Organization', 'name' => 'Test',
            ]],
            'WebPage' => [[
                '@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => 'Test',
            ]],
            'BreadcrumbList' => [[
                '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [],
            ]],
            'FAQPage' => [[
                '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => [],
            ]],
            'Event' => [[
                '@context' => 'https://schema.org', '@type' => 'Event', 'name' => 'Test Event',
            ]],
            'JobPosting' => [[
                '@context' => 'https://schema.org', '@type' => 'JobPosting', 'title' => 'Test Job',
            ]],
            'NewsArticle' => [[
                '@context' => 'https://schema.org', '@type' => 'NewsArticle', 'headline' => 'Test News',
            ]],
            'Person' => [[
                '@context' => 'https://schema.org', '@type' => 'Person', 'name' => 'Test Person',
            ]],
            'Place' => [[
                '@context' => 'https://schema.org', '@type' => 'Place', 'name' => 'Test Place',
            ]],
            'LocalBusiness' => [[
                '@context' => 'https://schema.org', '@type' => 'LocalBusiness', 'name' => 'Test Business',
            ]],
        ];
    }

    #[Test]
    #[DataProvider('allSchemaTypesDataProvider')]
    public function allSchemaTypesProduceValidJsonLd(array $graph): void
    {
        $decoded = $this->assertValidJsonLd($this->encodeJsonLd($graph), $graph['@type']);

        self::assertIsArray($decoded);
    }

    #[Test]
    public function jsonLdFlagsMatchProductionSerialization(): void
    {
        $json = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Test: "Quotes", Schloßstraße & Café',
            'url' => 'https://example.com/path?query=1',
        ], self::JSON_FLAGS);

        self::assertStringContainsString('Schloßstraße', $json);
        self::assertStringContainsString('Café', $json);
        self::assertStringContainsString('https://example.com/path?query=1', $json);
        self::assertStringContainsString('\"', $json);

        $decoded = json_decode($json, true);
        self::assertNotNull($decoded);
        self::assertSame('Test: "Quotes", Schloßstraße & Café', $decoded['name']);
    }
}
