<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\MerchantOpeningHoursRestApi\Processor\RestResponseBuilder;

use Generated\Shared\Transfer\MerchantOpeningHoursStorageTransfer;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;

interface MerchantOpeningHoursRestResponseBuilderInterface
{
    /**
     * @param array<\Generated\Shared\Transfer\MerchantOpeningHoursStorageTransfer> $merchantOpeningHoursStorageTransfers
     *
     * @return array<\Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface>
     */
    public function createMerchantOpeningHoursRestResources(array $merchantOpeningHoursStorageTransfers): array;

    public function createMerchantOpeningHoursRestResponse(
        MerchantOpeningHoursStorageTransfer $merchantOpeningHoursStorageTransfer,
        string $merchantReference
    ): RestResponseInterface;

    public function createEmptyMerchantOpeningHoursRestResponse(): RestResponseInterface;

    public function createMerchantNotFoundErrorResponse(): RestResponseInterface;

    public function createMerchantIdentifierMissingErrorResponse(): RestResponseInterface;
}
