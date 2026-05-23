<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\Schema;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class VocabularyTest extends TestCase
{
    private array $vocabulary;

    protected function setUp(): void
    {
        $vocabFile = dirname(__DIR__, 3) . '/Resources/Public/JavaScript/schema/vocabulary.json';
        self::assertFileExists($vocabFile, 'vocabulary.json must exist');
        $decoded = json_decode((string) file_get_contents($vocabFile), true);
        self::assertIsArray($decoded, 'vocabulary.json must decode to an array');
        $this->vocabulary = $decoded;
    }

    #[Test]
    public function vocabularyHasTypesAndPropertiesSections(): void
    {
        self::assertArrayHasKey('types', $this->vocabulary);
        self::assertArrayHasKey('properties', $this->vocabulary);
    }

    #[Test]
    public function jobPostingTypeIsRegistered(): void
    {
        self::assertArrayHasKey('JobPosting', $this->vocabulary['types']);
    }

    #[Test]
    public function jobPostingHasCorrectLabel(): void
    {
        self::assertSame('JobPosting', $this->vocabulary['types']['JobPosting']['label']);
    }

    #[Test]
    public function jobPostingInheritsFromThing(): void
    {
        self::assertContains('Thing', $this->vocabulary['types']['JobPosting']['ancestors']);
    }

    #[Test]
    public function jobPostingHasRequiredProperties(): void
    {
        $properties = $this->vocabulary['types']['JobPosting']['properties'];
        foreach (['title', 'description', 'responsibilities', 'validThrough', 'employmentType', 'datePosted', 'url', 'hiringOrganization', 'jobLocation', 'directApply'] as $requiredProp) {
            self::assertContains($requiredProp, $properties, "JobPosting must declare property '{$requiredProp}'");
        }
    }

    #[Test]
    public function collectionPageTypeIsRegistered(): void
    {
        self::assertArrayHasKey('CollectionPage', $this->vocabulary['types']);
    }

    #[Test]
    public function collectionPageHasCorrectLabel(): void
    {
        self::assertSame('CollectionPage', $this->vocabulary['types']['CollectionPage']['label']);
    }

    #[Test]
    public function collectionPageInheritsFromWebPage(): void
    {
        $ancestors = $this->vocabulary['types']['CollectionPage']['ancestors'];
        self::assertContains('WebPage', $ancestors);
        self::assertContains('Thing', $ancestors);
    }

    #[Test]
    public function jobPostingSpecificPropertiesAreDefinedInPropertiesSection(): void
    {
        $properties = $this->vocabulary['properties'];
        foreach (['employmentType', 'hiringOrganization', 'validThrough', 'datePosted', 'responsibilities', 'directApply', 'jobLocation', 'title'] as $propName) {
            self::assertArrayHasKey($propName, $properties, "Property '{$propName}' must be defined in the properties section");
        }
    }

    #[Test]
    public function employmentTypePropertyHasCorrectStructure(): void
    {
        $prop = $this->vocabulary['properties']['employmentType'];
        self::assertArrayHasKey('label', $prop);
        self::assertArrayHasKey('expectedTypes', $prop);
        self::assertContains('Text', $prop['expectedTypes']);
    }

    #[Test]
    public function hiringOrganizationPropertyExpectsOrganizationType(): void
    {
        $prop = $this->vocabulary['properties']['hiringOrganization'];
        self::assertContains('Organization', $prop['expectedTypes']);
    }

    #[Test]
    public function jobLocationPropertyExpectsPostalAddressType(): void
    {
        $prop = $this->vocabulary['properties']['jobLocation'];
        self::assertContains('PostalAddress', $prop['expectedTypes']);
    }

    #[Test]
    public function vocabularyNowContainsSixteenOrMoreTypes(): void
    {
        // Previously 15 types; JobPosting and CollectionPage bring it to 17
        self::assertGreaterThanOrEqual(17, count($this->vocabulary['types']));
    }
}
