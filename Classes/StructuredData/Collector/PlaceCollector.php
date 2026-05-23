<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class PlaceCollector implements CollectorInterface
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function collect(StructuredDataCollectionEvent $event): void
    {
        if (!in_array($event->getGraph()['@type'] ?? '', $this->supportedTypes(), true)) {
            return;
        }

        $qb = $this->connectionPool->getQueryBuilderForTable('tx_mailocations_location');
        $rows = $qb
            ->select('uid', 'name', 'street', 'zip', 'city', 'country', 'phone', 'email', 'latitude', 'longitude', 'description')
            ->from('tx_mailocations_location')
            ->where($qb->expr()->eq('pid', $qb->createNamedParameter($event->pageUid, Connection::PARAM_INT)))
            ->orderBy('sorting', 'ASC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAllAssociative();

        if ($rows === []) {
            return;
        }

        $row = $rows[0];

        if (!empty($row['name'])) {
            $event->addToGraph('name', $row['name']);
        }

        if (!empty($row['description'])) {
            $event->addToGraph('description', $row['description']);
        }

        if (!empty($row['phone'])) {
            $event->addToGraph('telephone', $row['phone']);
        }

        if (!empty($row['email'])) {
            $event->addToGraph('email', $row['email']);
        }

        $address = $this->buildPostalAddress($row);
        if ($address !== []) {
            $event->addToGraph('address', $address);
        }

        $lat = (float) ($row['latitude'] ?? 0);
        $lng = (float) ($row['longitude'] ?? 0);
        if ($lat !== 0.0 || $lng !== 0.0) {
            $event->addToGraph('geo', [
                '@type' => 'GeoCoordinates',
                'latitude' => $lat,
                'longitude' => $lng,
            ]);
        }
    }

    private function buildPostalAddress(array $row): array
    {
        $address = ['@type' => 'PostalAddress'];

        if (!empty($row['street'])) {
            $address['streetAddress'] = $row['street'];
        }

        if (!empty($row['zip'])) {
            $address['postalCode'] = $row['zip'];
        }

        if (!empty($row['city'])) {
            $address['addressLocality'] = $row['city'];
        }

        if (!empty($row['country'])) {
            $address['addressCountry'] = $row['country'];
        }

        // Return empty array if nothing beyond @type was added
        if (count($address) === 1) {
            return [];
        }

        return $address;
    }

    public function supportedTypes(): array
    {
        return ['Place', 'LocalBusiness'];
    }

    public function priority(): int
    {
        return 70;
    }
}
