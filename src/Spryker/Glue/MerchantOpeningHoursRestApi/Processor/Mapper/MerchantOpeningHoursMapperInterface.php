<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\MerchantOpeningHoursRestApi\Processor\Mapper;

use Generated\Shared\Transfer\MerchantOpeningHoursStorageTransfer;
use Generated\Shared\Transfer\RestMerchantOpeningHoursAttributesTransfer;

interface MerchantOpeningHoursMapperInterface
{
    public function mapMerchantOpeningHoursStorageTransferToRestMerchantOpeningHoursAttributesTransfer(
        MerchantOpeningHoursStorageTransfer $merchantOpeningHoursStorageTransfer,
        RestMerchantOpeningHoursAttributesTransfer $restMerchantOpeningHoursAttributesTransfer
    ): RestMerchantOpeningHoursAttributesTransfer;
}
