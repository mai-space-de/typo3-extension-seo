<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\StructuredData\RecordStorageResolverInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class PlaceCollector implements CollectorInterface
{
    private const DAY_OF_WEEK_MAP = [
        '0' => 'https://schema.org/Monday',
        '1' => 'https://schema.org/Tuesday',
        '2' => 'https://schema.org/Wednesday',
        '3' => 'https://schema.org/Thursday',
        '4' => 'https://schema.org/Friday',
        '5' => 'https://schema.org/Saturday',
        '6' => 'https://schema.org/Sunday',
    ];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly RecordStorageResolverInterface $recordStorageResolver,
    ) {}

    public function collect(StructuredDataCollectionEvent $event): void
    {
        if (!in_array($event->getGraph()['@type'] ?? '', $this->supportedTypes(), true)) {
            return;
        }

        $storagePids = $this->recordStorageResolver->resolveStoragePids($event->pageUid);
        if ($storagePids === []) {
            return;
        }

        $qb = $this->connectionPool->getQueryBuilderForTable('tx_mailocations_location');
        $rows = $qb
            ->select('uid', 'name', 'street', 'zip', 'city', 'country', 'phone', 'email', 'latitude', 'longitude', 'description')
            ->from('tx_mailocations_location')
            ->where(
                $qb->expr()->in(
                    'pid',
                    $qb->createNamedParameter($storagePids, Connection::PARAM_INT_ARRAY),
                ),
            )
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

        $openingHours = $this->fetchOpeningHoursSpecification((int) $row['uid']);
        if ($openingHours !== []) {
            $event->addToGraph('openingHoursSpecification', $openingHours);
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

    private function fetchOpeningHoursSpecification(int $locationUid): array
    {
        $qb = $this->connectionPool->getQueryBuilderForTable('tx_mailocations_opening_hours');
        $rows = $qb
            ->select('day_of_week', 'time_open', 'time_close', 'is_closed', 'special_date')
            ->from('tx_mailocations_opening_hours')
            ->where(
                $qb->expr()->eq('parentid', $qb->createNamedParameter($locationUid, Connection::PARAM_INT)),
                $qb->expr()->eq('parenttable', $qb->createNamedParameter('tx_mailocations_location')),
                $qb->expr()->eq('is_closed', $qb->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->orderBy('sorting', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $specs = [];
        foreach ($rows as $hour) {
            $spec = ['@type' => 'OpeningHoursSpecification'];

            $specialDate = (string) ($hour['special_date'] ?? '');
            if ($specialDate !== '' && $specialDate !== '0000-00-00') {
                $spec['validFrom'] = $specialDate;
                $spec['validThrough'] = $specialDate;
            } else {
                $dayOfWeek = (string) ($hour['day_of_week'] ?? '');
                $dayUri = self::DAY_OF_WEEK_MAP[$dayOfWeek] ?? null;
                if ($dayUri === null) {
                    continue;
                }
                $spec['dayOfWeek'] = $dayUri;
            }

            if (!empty($hour['time_open'])) {
                $spec['opens'] = $hour['time_open'];
            }

            if (!empty($hour['time_close'])) {
                $spec['closes'] = $hour['time_close'];
            }

            $specs[] = $spec;
        }

        return $specs;
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
