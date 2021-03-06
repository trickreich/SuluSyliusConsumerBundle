<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SyliusConsumerBundle\Model\Content\Handler\Message;

use Sulu\Bundle\SyliusConsumerBundle\Model\Content\ContentInterface;
use Sulu\Bundle\SyliusConsumerBundle\Model\Content\ContentRepositoryInterface;
use Sulu\Bundle\SyliusConsumerBundle\Model\Content\Exception\ContentNotFoundException;
use Sulu\Bundle\SyliusConsumerBundle\Model\Content\Message\PublishContentMessage;
use Sulu\Bundle\SyliusConsumerBundle\Model\Content\View\ContentViewFactoryInterface;
use Sulu\Bundle\SyliusConsumerBundle\Model\Dimension\DimensionInterface;
use Sulu\Bundle\SyliusConsumerBundle\Model\Dimension\DimensionRepositoryInterface;

class PublishContentMessageHandler
{
    /**
     * @var ContentRepositoryInterface
     */
    private $contentRepository;

    /**
     * @var DimensionRepositoryInterface
     */
    private $dimensionRepository;

    /**
     * @var ContentViewFactoryInterface
     */
    private $contentViewFactory;

    public function __construct(
        ContentRepositoryInterface $contentRepository,
        DimensionRepositoryInterface $dimensionRepository,
        ContentViewFactoryInterface $contentViewFactory
    ) {
        $this->contentRepository = $contentRepository;
        $this->dimensionRepository = $dimensionRepository;
        $this->contentViewFactory = $contentViewFactory;
    }

    public function __invoke(PublishContentMessage $message): ContentInterface
    {
        $contents = [
            $this->publishDimension($message->getResourceKey(), $message->getResourceId()),
            $this->publishDimension($message->getResourceKey(), $message->getResourceId(), $message->getLocale()),
        ];

        $content = $this->contentViewFactory->create(array_filter($contents));
        if (!$content) {
            throw new ContentNotFoundException($message->getResourceKey(), $message->getResourceId());
        }

        return $content;
    }

    protected function publishDimension(
        string $resourceKey,
        string $resourceId,
        ?string $locale = null
    ): ?ContentInterface {
        $draftAttributes = $this->getAttributes(DimensionInterface::ATTRIBUTE_VALUE_DRAFT, $locale);
        $draftDimension = $this->dimensionRepository->findOrCreateByAttributes($draftAttributes);

        $liveAttributes = $this->getAttributes(DimensionInterface::ATTRIBUTE_VALUE_LIVE, $locale);
        $liveDimension = $this->dimensionRepository->findOrCreateByAttributes($liveAttributes);

        $liveContent = $this->contentRepository->findOrCreate($resourceKey, $resourceId, $liveDimension);
        $draftContent = $this->contentRepository->findByResource($resourceKey, $resourceId, $draftDimension);

        if (!$draftContent) {
            throw new ContentNotFoundException($resourceKey, $resourceId);
        }

        $type = $draftContent->getType();
        if (!$type) {
            throw new \InvalidArgumentException('Content type cannot be null');
        }

        $liveContent->setType($type);
        $liveContent->setData($draftContent->getData());

        return $liveContent;
    }

    protected function getAttributes(string $stage, ?string $locale = null): array
    {
        $attributes = [DimensionInterface::ATTRIBUTE_KEY_STAGE => $stage];
        if (!$locale) {
            return $attributes;
        }

        $attributes[DimensionInterface::ATTRIBUTE_KEY_LOCALE] = $locale;

        return $attributes;
    }
}
