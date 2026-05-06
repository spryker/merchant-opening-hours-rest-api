<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\MerchantOpeningHoursRestApi\Api\Storefront\Provider;

use Generated\Api\Storefront\MerchantOpeningHoursStorefrontResource;
use Generated\Shared\Transfer\MerchantOpeningHoursStorageTransfer;
use Generated\Shared\Transfer\MerchantStorageCriteriaTransfer;
use Spryker\ApiPlatform\Exception\GlueApiException;
use Spryker\ApiPlatform\State\Provider\AbstractStorefrontProvider;
use Spryker\Client\GlossaryStorage\GlossaryStorageClientInterface;
use Spryker\Client\MerchantOpeningHoursStorage\MerchantOpeningHoursStorageClientInterface;
use Spryker\Client\MerchantStorage\MerchantStorageClientInterface;
use Spryker\Glue\MerchantOpeningHoursRestApi\MerchantOpeningHoursRestApiConfig;
use Symfony\Component\HttpFoundation\Response;

class MerchantOpeningHoursStorefrontProvider extends AbstractStorefrontProvider
{
    protected const string URI_VAR_MERCHANT_REFERENCE = 'merchantReference';

    public function __construct(
        protected MerchantOpeningHoursStorageClientInterface $merchantOpeningHoursStorageClient,
        protected MerchantStorageClientInterface $merchantStorageClient,
        protected GlossaryStorageClientInterface $glossaryStorageClient,
    ) {
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     *
     * @return array<\Generated\Api\Storefront\MerchantOpeningHoursStorefrontResource>
     */
    protected function provideCollection(): array
    {
        $merchantReference = $this->resolveMerchantReference();

        $merchantStorageTransfer = $this->merchantStorageClient->findOne(
            (new MerchantStorageCriteriaTransfer())->addMerchantReference($merchantReference),
        );

        if ($merchantStorageTransfer === null) {
            throw new GlueApiException(
                Response::HTTP_NOT_FOUND,
                MerchantOpeningHoursRestApiConfig::RESPONSE_CODE_MERCHANT_NOT_FOUND,
                MerchantOpeningHoursRestApiConfig::RESPONSE_DETAIL_MERCHANT_NOT_FOUND,
            );
        }

        $idMerchant = $merchantStorageTransfer->getIdMerchant();

        if ($idMerchant === null) {
            return [];
        }

        $merchantOpeningHoursStorageTransfers = $this->merchantOpeningHoursStorageClient
            ->getMerchantOpeningHoursByMerchantIds([$idMerchant]);

        if ($merchantOpeningHoursStorageTransfers === []) {
            return [];
        }

        $merchantOpeningHoursStorageTransfer = reset($merchantOpeningHoursStorageTransfers);
        $this->translateDateScheduleNotes($merchantOpeningHoursStorageTransfer);

        return [$this->mapToResource($merchantOpeningHoursStorageTransfer, $merchantReference)];
    }

    protected function resolveMerchantReference(): string
    {
        if (!$this->hasUriVariable(static::URI_VAR_MERCHANT_REFERENCE)) {
            $this->throwMissingMerchantReference();
        }

        $merchantReference = (string)$this->getUriVariable(static::URI_VAR_MERCHANT_REFERENCE);

        if ($merchantReference === '') {
            $this->throwMissingMerchantReference();
        }

        return $merchantReference;
    }

    protected function throwMissingMerchantReference(): never
    {
        throw new GlueApiException(
            Response::HTTP_BAD_REQUEST,
            MerchantOpeningHoursRestApiConfig::RESPONSE_CODE_MERCHANT_IDENTIFIER_MISSING,
            MerchantOpeningHoursRestApiConfig::RESPONSE_DETAIL_MERCHANT_IDENTIFIER_MISSING,
        );
    }

    protected function translateDateScheduleNotes(MerchantOpeningHoursStorageTransfer $transfer): void
    {
        $glossaryKeys = [];

        foreach ($transfer->getDateSchedule() as $dateScheduleTransfer) {
            $key = $dateScheduleTransfer->getNoteGlossaryKey();
            if ($key !== null && $key !== '') {
                $glossaryKeys[] = $key;
            }
        }

        if ($glossaryKeys === []) {
            return;
        }

        $translations = $this->glossaryStorageClient->translateBulk(
            array_values(array_unique($glossaryKeys)),
            $this->getLocale()->getLocaleNameOrFail(),
        );

        foreach ($transfer->getDateSchedule() as $dateScheduleTransfer) {
            $key = $dateScheduleTransfer->getNoteGlossaryKey();
            if ($key !== null && isset($translations[$key])) {
                $dateScheduleTransfer->setNoteGlossaryKey($translations[$key]);
            }
        }
    }

    protected function mapToResource(
        MerchantOpeningHoursStorageTransfer $merchantOpeningHoursStorageTransfer,
        string $merchantReference,
    ): MerchantOpeningHoursStorefrontResource {
        $resource = new MerchantOpeningHoursStorefrontResource();
        $resource->merchantReference = $merchantReference;
        $resource->weekdaySchedule = $this->extractWeekdaySchedule($merchantOpeningHoursStorageTransfer);
        $resource->dateSchedule = $this->extractDateSchedule($merchantOpeningHoursStorageTransfer);

        return $resource;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function extractWeekdaySchedule(MerchantOpeningHoursStorageTransfer $transfer): array
    {
        $schedule = [];

        foreach ($transfer->getWeekdaySchedule() as $weekdayScheduleTransfer) {
            $schedule[] = [
                'day' => $weekdayScheduleTransfer->getDay(),
                'timeFrom' => $weekdayScheduleTransfer->getTimeFrom(),
                'timeTo' => $weekdayScheduleTransfer->getTimeTo(),
            ];
        }

        return $schedule;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function extractDateSchedule(MerchantOpeningHoursStorageTransfer $transfer): array
    {
        $schedule = [];

        foreach ($transfer->getDateSchedule() as $dateScheduleTransfer) {
            $schedule[] = [
                'date' => $dateScheduleTransfer->getDate(),
                'timeFrom' => $dateScheduleTransfer->getTimeFrom(),
                'timeTo' => $dateScheduleTransfer->getTimeTo(),
                'noteGlossaryKey' => $dateScheduleTransfer->getNoteGlossaryKey(),
            ];
        }

        return $schedule;
    }
}
