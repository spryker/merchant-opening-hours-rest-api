<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\MerchantOpeningHoursRestApi\Processor\Expander;

use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use Spryker\Glue\MerchantOpeningHoursRestApi\Dependency\Client\MerchantOpeningHoursRestApiToMerchantOpeningHoursStorageClientInterface;
use Spryker\Glue\MerchantOpeningHoursRestApi\Dependency\Client\MerchantOpeningHoursRestApiToMerchantStorageClientInterface;
use Spryker\Glue\MerchantOpeningHoursRestApi\Processor\RestResponseBuilder\MerchantOpeningHoursRestResponseBuilderInterface;
use Spryker\Glue\MerchantOpeningHoursRestApi\Processor\Translator\MerchantOpeningHoursTranslatorInterface;

class MerchantOpeningHoursByMerchantReferenceResourceRelationshipExpander implements MerchantOpeningHoursByMerchantReferenceResourceRelationshipExpanderInterface
{
    /**
     * @var \Spryker\Glue\MerchantOpeningHoursRestApi\Processor\RestResponseBuilder\MerchantOpeningHoursRestResponseBuilderInterface
     */
    protected $merchantsRestResponseBuilder;

    /**
     * @var \Spryker\Glue\MerchantOpeningHoursRestApi\Dependency\Client\MerchantOpeningHoursRestApiToMerchantStorageClientInterface
     */
    protected $merchantStorageClient;

    /**
     * @var \Spryker\Glue\MerchantOpeningHoursRestApi\Dependency\Client\MerchantOpeningHoursRestApiToMerchantOpeningHoursStorageClientInterface
     */
    protected $merchantOpeningHoursStorageClient;

    /**
     * @var \Spryker\Glue\MerchantOpeningHoursRestApi\Processor\Translator\MerchantOpeningHoursTranslatorInterface
     */
    protected $merchantOpeningHoursTranslator;

    /**
     * @param \Spryker\Glue\MerchantOpeningHoursRestApi\Dependency\Client\MerchantOpeningHoursRestApiToMerchantOpeningHoursStorageClientInterface $merchantOpeningHoursStorageClient
     * @param \Spryker\Glue\MerchantOpeningHoursRestApi\Dependency\Client\MerchantOpeningHoursRestApiToMerchantStorageClientInterface $merchantStorageClient
     * @param \Spryker\Glue\MerchantOpeningHoursRestApi\Processor\RestResponseBuilder\MerchantOpeningHoursRestResponseBuilderInterface $merchantsRestResponseBuilder
     * @param \Spryker\Glue\MerchantOpeningHoursRestApi\Processor\Translator\MerchantOpeningHoursTranslatorInterface $merchantOpeningHoursTranslator
     */
    public function __construct(
        MerchantOpeningHoursRestApiToMerchantOpeningHoursStorageClientInterface $merchantOpeningHoursStorageClient,
        MerchantOpeningHoursRestApiToMerchantStorageClientInterface $merchantStorageClient,
        MerchantOpeningHoursRestResponseBuilderInterface $merchantsRestResponseBuilder,
        MerchantOpeningHoursTranslatorInterface $merchantOpeningHoursTranslator
    ) {
        $this->merchantOpeningHoursStorageClient = $merchantOpeningHoursStorageClient;
        $this->merchantStorageClient = $merchantStorageClient;
        $this->merchantsRestResponseBuilder = $merchantsRestResponseBuilder;
        $this->merchantOpeningHoursTranslator = $merchantOpeningHoursTranslator;
    }

    /**
     * @param \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface[] $resources
     * @param \Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface $restRequest
     *
     * @return void
     */
    public function addResourceRelationships(array $resources, RestRequestInterface $restRequest): void
    {
        $merchantReferences = $this->getMerchantReferences($resources);
        $merchantStorageTransfers = $this->merchantStorageClient->findByMerchantReference($merchantReferences);

        $merchantIds = $this->getMerchantIds($merchantStorageTransfers);
        $merchantOpeningHoursStorageTransfers = $this->merchantOpeningHoursStorageClient
            ->getMerchantOpeningHoursByMerchantIds($merchantIds);

        $merchantOpeningHoursStorageTransfers = $this->flipIdsToMerchantReferences($merchantOpeningHoursStorageTransfers, $merchantIds);

        $merchantOpeningHoursStorageTransfersWithTranslatedNotes = $this->merchantOpeningHoursTranslator
            ->getMerchantOpeningHoursTransfersWithTranslatedNotes(
                $merchantOpeningHoursStorageTransfers,
                $restRequest->getMetadata()->getLocale()
            );

        foreach ($resources as $resource) {
            $merchantOpeningHoursStorageTransfer = $merchantOpeningHoursStorageTransfersWithTranslatedNotes[$resource->getId()];
            $restMerchantOpeningHoursResource = $this->merchantsRestResponseBuilder->createMerchantOpeningHoursRestResource(
                $merchantOpeningHoursStorageTransfer,
                $resource->getId()
            );

            $resource->addRelationship($restMerchantOpeningHoursResource);
        }
    }

    /**
     * @param \Spryker\Glue\GlueApplication\Rest\JsonApi\RestResourceInterface[] $resources
     *
     * @return string[]
     */
    protected function getMerchantReferences(array $resources): array
    {
        $references = [];
        foreach ($resources as $resource) {
            $references[] = $resource->getId();
        }

        return $references;
    }

    /**
     * @param \Generated\Shared\Transfer\MerchantStorageTransfer[] $merchantStorageTransfers
     *
     * @return int[]
     */
    protected function getMerchantIds(array $merchantStorageTransfers): array
    {
        $merchantIds = [];
        foreach ($merchantStorageTransfers as $merchantStorageTransfer) {
            $merchantIds[$merchantStorageTransfer->getMerchantReference()] = $merchantStorageTransfer->getIdMerchant();
        }

        return $merchantIds;
    }

    /**
     * @param \Generated\Shared\Transfer\MerchantOpeningHoursStorageTransfer[] $merchantOpeningHoursStorageTransfers
     * @param int[] $merchantIds
     *
     * @return \Generated\Shared\Transfer\MerchantOpeningHoursStorageTransfer[]
     */
    protected function flipIdsToMerchantReferences(array $merchantOpeningHoursStorageTransfers, array $merchantIds): array
    {
        $flippedMerchantOpeningHoursStorageTransfers = [];
        $merchantReferences = array_flip($merchantIds);
        foreach ($merchantOpeningHoursStorageTransfers as $merchantId => $merchantOpeningHourStorageTransfers) {
            $flippedMerchantOpeningHoursStorageTransfers[$merchantReferences[$merchantId]] = $merchantOpeningHourStorageTransfers;
        }

        return $flippedMerchantOpeningHoursStorageTransfers;
    }
}