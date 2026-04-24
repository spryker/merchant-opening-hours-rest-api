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
use Spryker\Client\MerchantOpeningHoursStorage\MerchantOpeningHoursStorageClientInterface;
use Spryker\Client\MerchantStorage\MerchantStorageClientInterface;
use Symfony\Component\HttpFoundation\Response;

class MerchantOpeningHoursStorefrontProvider extends AbstractStorefrontProvider
{
    protected const string URI_VAR_MERCHANT_REFERENCE = 'merchantReference';

    protected const string ERROR_CODE_MERCHANT_NOT_FOUND = '3501';

    protected const string ERROR_MESSAGE_MERCHANT_NOT_FOUND = 'Merchant not found.';

    protected const string ERROR_CODE_MERCHANT_REFERENCE_NOT_SPECIFIED = '3502';

    protected const string ERROR_MESSAGE_MERCHANT_REFERENCE_NOT_SPECIFIED = 'Merchant identifier is not specified.';

    public function __construct(
        protected MerchantOpeningHoursStorageClientInterface $merchantOpeningHoursStorageClient,
        protected MerchantStorageClientInterface $merchantStorageClient,
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
                static::ERROR_CODE_MERCHANT_NOT_FOUND,
                static::ERROR_MESSAGE_MERCHANT_NOT_FOUND,
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
            static::ERROR_CODE_MERCHANT_REFERENCE_NOT_SPECIFIED,
            static::ERROR_MESSAGE_MERCHANT_REFERENCE_NOT_SPECIFIED,
        );
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
            $schedule[] = $weekdayScheduleTransfer->toArray();
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
            $schedule[] = $dateScheduleTransfer->toArray();
        }

        return $schedule;
    }
}
