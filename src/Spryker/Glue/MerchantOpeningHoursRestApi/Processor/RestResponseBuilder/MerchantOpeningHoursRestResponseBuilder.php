<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\MerchantOpeningHoursRestApi\Processor\RestResponseBuilder;

use Generated\Shared\Transfer\MerchantOpeningHoursStorageTransfer;
use Generated\Shared\Transfer\RestErrorMessageTransfer;
use Generated\Shared\Transfer\RestMerchantOpeningHoursAttributesTransfer;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestLinkInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceBuilderInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;
use Spryker\Glue\MerchantOpeningHoursRestApi\MerchantOpeningHoursRestApiConfig;
use Spryker\Glue\MerchantOpeningHoursRestApi\Processor\Mapper\MerchantOpeningHoursMapperInterface;
use Symfony\Component\HttpFoundation\Response;

class MerchantOpeningHoursRestResponseBuilder implements MerchantOpeningHoursRestResponseBuilderInterface
{
    /**
     * @var \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceBuilderInterface
     */
    protected $restResourceBuilder;

    /**
     * @var \Spryker\Glue\MerchantOpeningHoursRestApi\Processor\Mapper\MerchantOpeningHoursMapperInterface
     */
    protected $merchantOpeningHoursMapper;

    public function __construct(
        RestResourceBuilderInterface $restResourceBuilder,
        MerchantOpeningHoursMapperInterface $merchantOpeningHoursMapper
    ) {
        $this->restResourceBuilder = $restResourceBuilder;
        $this->merchantOpeningHoursMapper = $merchantOpeningHoursMapper;
    }

    /**
     * @param array<\Generated\Shared\Transfer\MerchantOpeningHoursStorageTransfer> $merchantOpeningHoursStorageTransfers
     *
     * @return array<\Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface>
     */
    public function createMerchantOpeningHoursRestResources(array $merchantOpeningHoursStorageTransfers): array
    {
        $merchantOpeningHoursRestResources = [];
        foreach ($merchantOpeningHoursStorageTransfers as $merchantReference => $merchantOpeningHoursStorageTransfer) {
            $merchantOpeningHoursRestResources[$merchantReference] = $this->createMerchantOpeningHoursRestResource(
                $merchantOpeningHoursStorageTransfer,
                $merchantReference,
            );
        }

        return $merchantOpeningHoursRestResources;
    }

    public function createMerchantOpeningHoursRestResponse(
        MerchantOpeningHoursStorageTransfer $merchantOpeningHoursStorageTransfer,
        string $merchantReference
    ): RestResponseInterface {
        $merchantsRestResource = $this->createMerchantOpeningHoursRestResource($merchantOpeningHoursStorageTransfer, $merchantReference);

        return $this->restResourceBuilder
            ->createRestResponse()
            ->addResource($merchantsRestResource);
    }

    public function createEmptyMerchantOpeningHoursRestResponse(): RestResponseInterface
    {
        return $this->restResourceBuilder->createRestResponse();
    }

    public function createMerchantNotFoundErrorResponse(): RestResponseInterface
    {
        return $this->restResourceBuilder
            ->createRestResponse()
            ->addError(
                (new RestErrorMessageTransfer())
                    ->setStatus(Response::HTTP_NOT_FOUND)
                    ->setCode(MerchantOpeningHoursRestApiConfig::RESPONSE_CODE_MERCHANT_NOT_FOUND)
                    ->setDetail(MerchantOpeningHoursRestApiConfig::RESPONSE_DETAIL_MERCHANT_NOT_FOUND),
            );
    }

    public function createMerchantIdentifierMissingErrorResponse(): RestResponseInterface
    {
        return $this->restResourceBuilder->createRestResponse()
            ->addError(
                (new RestErrorMessageTransfer())
                    ->setStatus(Response::HTTP_BAD_REQUEST)
                    ->setCode(MerchantOpeningHoursRestApiConfig::RESPONSE_CODE_MERCHANT_IDENTIFIER_MISSING)
                    ->setDetail(MerchantOpeningHoursRestApiConfig::RESPONSE_DETAIL_MERCHANT_IDENTIFIER_MISSING),
            );
    }

    protected function createMerchantOpeningHoursRestResource(
        MerchantOpeningHoursStorageTransfer $merchantOpeningHoursStorageTransfer,
        string $merchantReference
    ): RestResourceInterface {
        $restResource = $this->restResourceBuilder->createRestResource(
            MerchantOpeningHoursRestApiConfig::RESOURCE_MERCHANT_OPENING_HOURS,
            $merchantReference,
            $this->merchantOpeningHoursMapper->mapMerchantOpeningHoursStorageTransferToRestMerchantOpeningHoursAttributesTransfer(
                $merchantOpeningHoursStorageTransfer,
                new RestMerchantOpeningHoursAttributesTransfer(),
            ),
        );

        $restResource->addLink(
            RestLinkInterface::LINK_SELF,
            $this->getMerchantsOpeningHoursResourceSelfLink($merchantReference),
        );

        return $restResource;
    }

    protected function getMerchantsOpeningHoursResourceSelfLink(string $merchantReference): string
    {
        return sprintf(
            '%s/%s/%s',
            MerchantOpeningHoursRestApiConfig::RESOURCE_MERCHANTS,
            $merchantReference,
            MerchantOpeningHoursRestApiConfig::RESOURCE_MERCHANT_OPENING_HOURS,
        );
    }
}
