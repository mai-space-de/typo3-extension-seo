<?php

declare(strict_types=1);

namespace Maispace\MaispacesSeo\Tests\Unit\Service;

use Maispace\MaispacesSeo\Event\BeforeJsonLdRenderedEvent;
use Maispace\MaispacesSeo\Service\JsonLdService;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class JsonLdServiceTest extends TestCase
{
    private EventDispatcherInterface $eventDispatcher;
    private JsonLdService $subject;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->eventDispatcher
            ->method('dispatch')
            ->willReturnArgument(0);

        $this->subject = new JsonLdService($this->eventDispatcher);
    }

    public function testBuildSchemaReturnsCorrectContextAndType(): void
    {
        $schema = $this->subject->buildSchema(
            ['title' => 'Test Page', 'tx_maispace_seo_jsonld_type' => 'Article'],
            []
        );

        self::assertSame('https://schema.org', $schema['@context']);
        self::assertSame('Article', $schema['@type']);
    }

    public function testBuildSchemaUsesDefaultTypeWebPageWhenNoneSet(): void
    {
        $schema = $this->subject->buildSchema(
            ['title' => 'Test Page', 'tx_maispace_seo_jsonld_type' => ''],
            ['jsonLd.' => ['defaultType' => 'WebPage']]
        );

        self::assertSame('WebPage', $schema['@type']);
    }

    public function testBuildSchemaUsesFallbackWebPageWhenSettingsEmpty(): void
    {
        $schema = $this->subject->buildSchema(
            ['title' => 'Test Page', 'tx_maispace_seo_jsonld_type' => ''],
            []
        );

        self::assertSame('WebPage', $schema['@type']);
    }

    public function testBuildSchemaUsesPageTitleAsNameWhenOverrideIsEmpty(): void
    {
        $schema = $this->subject->buildSchema(
            ['title' => 'My Page Title', 'tx_maispace_seo_jsonld_type' => 'WebPage', 'tx_maispace_seo_jsonld_name' => ''],
            []
        );

        self::assertSame('My Page Title', $schema['name']);
    }

    public function testBuildSchemaUsesNameOverrideWhenSet(): void
    {
        $schema = $this->subject->buildSchema(
            ['title' => 'My Page Title', 'tx_maispace_seo_jsonld_type' => 'WebPage', 'tx_maispace_seo_jsonld_name' => 'Custom Name'],
            []
        );

        self::assertSame('Custom Name', $schema['name']);
    }

    public function testBuildSchemaMergesCustomJsonWhenValid(): void
    {
        $customJson = json_encode(['customField' => 'customValue', 'anotherField' => 42]);
        $schema = $this->subject->buildSchema(
            ['title' => 'Page', 'tx_maispace_seo_jsonld_type' => 'WebPage', 'tx_maispace_seo_jsonld_custom' => $customJson],
            []
        );

        self::assertSame('customValue', $schema['customField']);
        self::assertSame(42, $schema['anotherField']);
    }

    public function testBuildSchemaAddsPublisherWhenOrganizationNameSet(): void
    {
        $schema = $this->subject->buildSchema(
            ['title' => 'Page', 'tx_maispace_seo_jsonld_type' => 'WebPage'],
            ['jsonLd.' => ['organizationName' => 'Acme Corp', 'organizationUrl' => 'https://acme.example']]
        );

        self::assertIsArray($schema['publisher']);
        self::assertSame('Organization', $schema['publisher']['@type']);
        self::assertSame('Acme Corp', $schema['publisher']['name']);
        self::assertSame('https://acme.example', $schema['publisher']['url']);
    }

    public function testBuildSchemaDoesNotAddPublisherWhenOrganizationNameEmpty(): void
    {
        $schema = $this->subject->buildSchema(
            ['title' => 'Page', 'tx_maispace_seo_jsonld_type' => 'WebPage'],
            ['jsonLd.' => ['organizationName' => '']]
        );

        self::assertArrayNotHasKey('publisher', $schema);
    }

    public function testBuildSchemaAddsAuthorWhenSet(): void
    {
        $schema = $this->subject->buildSchema(
            ['title' => 'Page', 'tx_maispace_seo_jsonld_type' => 'Article', 'tx_maispace_seo_jsonld_author' => 'Jane Doe'],
            []
        );

        self::assertIsArray($schema['author']);
        self::assertSame('Person', $schema['author']['@type']);
        self::assertSame('Jane Doe', $schema['author']['name']);
    }

    public function testBuildSchemaFormatsDatePublishedAsIso8601(): void
    {
        $timestamp = mktime(12, 0, 0, 6, 15, 2024);
        $schema = $this->subject->buildSchema(
            ['title' => 'Page', 'tx_maispace_seo_jsonld_type' => 'Article', 'tx_maispace_seo_jsonld_date_published' => $timestamp],
            []
        );

        self::assertSame(date('c', $timestamp), $schema['datePublished']);
    }

    public function testBuildSchemaDoesNotAddDatePublishedWhenTimestampIsZero(): void
    {
        $schema = $this->subject->buildSchema(
            ['title' => 'Page', 'tx_maispace_seo_jsonld_type' => 'WebPage', 'tx_maispace_seo_jsonld_date_published' => 0],
            []
        );

        self::assertArrayNotHasKey('datePublished', $schema);
    }

    public function testBuildSchemaIgnoresInvalidCustomJson(): void
    {
        $schema = $this->subject->buildSchema(
            ['title' => 'Page', 'tx_maispace_seo_jsonld_type' => 'WebPage', 'tx_maispace_seo_jsonld_custom' => '{not valid json'],
            []
        );

        // Should return a valid schema without crashing
        self::assertSame('https://schema.org', $schema['@context']);
        self::assertSame('WebPage', $schema['@type']);
    }

    public function testBuildSchemaReturnsEmptyArrayWhenEventDisablesOutput(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(static function (BeforeJsonLdRenderedEvent $event): BeforeJsonLdRenderedEvent {
            $event->disable();

            return $event;
        });

        $subject = new JsonLdService($dispatcher);
        $schema = $subject->buildSchema(
            ['title' => 'Page', 'tx_maispace_seo_jsonld_type' => 'WebPage'],
            []
        );

        self::assertSame([], $schema);
    }

    public function testRenderScriptWrapsSchemaInScriptTag(): void
    {
        $schema = ['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => 'Test'];
        $result = $this->subject->renderScript($schema);

        self::assertStringStartsWith('<script type="application/ld+json">', $result);
        self::assertStringEndsWith('</script>', $result);
        self::assertStringContainsString('"@context": "https://schema.org"', $result);
    }

    public function testRenderScriptReturnsEmptyStringForEmptySchema(): void
    {
        self::assertSame('', $this->subject->renderScript([]));
    }
}
