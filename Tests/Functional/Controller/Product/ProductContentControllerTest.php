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

namespace Sulu\Bundle\SyliusConsumerBundle\Tests\Functional\Controller\Product;

use Sulu\Bundle\SyliusConsumerBundle\Model\Product\ProductInterface;
use Sulu\Bundle\SyliusConsumerBundle\Tests\Functional\Traits\ContentTrait;
use Sulu\Bundle\SyliusConsumerBundle\Tests\Functional\Traits\DimensionTrait;
use Sulu\Bundle\SyliusConsumerBundle\Tests\Functional\Traits\ProductInformationTrait;
use Sulu\Bundle\SyliusConsumerBundle\Tests\Functional\Traits\ProductTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ProductContentControllerTest extends SuluTestCase
{
    use ContentTrait;
    use DimensionTrait;
    use ProductInformationTrait;
    use ProductTrait;

    public function setUp()
    {
        $this->purgeDatabase();
    }

    public function testGetAction(): void
    {
        $content = $this->createContent(ProductInterface::RESOURCE_KEY, 'product-1');

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/product-contents/' . $content->getResourceId() . '?locale=en');

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals(
            array_merge(['template' => $content->getType()], $content->getData()),
            $response
        );
    }

    public function testGetActionOtherLocale(): void
    {
        $content = $this->createContent(ProductInterface::RESOURCE_KEY, 'product-1');

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/product-contents/' . $content->getResourceId() . '?locale=de');

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals(
            ['template' => $content->getType()],
            $response
        );
    }

    public function testPutActionCreate(): void
    {
        $product = $this->createProduct('product-1');

        $data = ['template' => 'default', 'title' => 'Sulu', 'article' => 'Sulu is awesome'];

        $client = $this->createAuthenticatedClient();
        $client->request('PUT', '/api/product-contents/' . $product->getId() . '?locale=en&action=draft', $data);

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals($data, $response);
    }

    public function testPutActionUpdate(): void
    {
        $content = $this->createContent(
            ProductInterface::RESOURCE_KEY,
            'product-1',
            'en',
            'homepage',
            ['title' => 'Zoolu', 'article' => 'Zoolu is great']
        );

        $data = ['template' => 'default', 'title' => 'Sulu', 'article' => 'Sulu is awesome'];

        $client = $this->createAuthenticatedClient();
        $client->request('PUT', '/api/product-contents/' . $content->getResourceId() . '?locale=en&action=draft', $data);

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals($data, $response);
    }

    public function testPutActionCreateAndPublish(): void
    {
        $product = $this->createProduct('product-1');
        $this->createProductInformation($product->getId(), 'en');

        $data = ['template' => 'default', 'title' => 'Sulu', 'article' => 'Sulu is awesome'];

        $client = $this->createAuthenticatedClient();
        $client->request('PUT', '/api/product-contents/' . $product->getId() . '?locale=en&action=publish', $data);

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals($data, $response);

        // TODO check status of response as soon as available
    }

    public function testPutActionUpdateAndPublish(): void
    {
        $product = $this->createProduct('product-1');
        $this->createProductInformation($product->getId(), 'en');
        $content = $this->createContent(
            ProductInterface::RESOURCE_KEY,
            $product->getId(),
            'en',
            'homepage',
            ['title' => 'Zoolu', 'article' => 'Zoolu is great']
        );

        $data = ['template' => 'default', 'title' => 'Sulu', 'article' => 'Sulu is awesome'];

        $client = $this->createAuthenticatedClient();
        $client->request('PUT', '/api/product-contents/' . $content->getResourceId() . '?locale=en&action=publish', $data);

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals($data, $response);

        // TODO check status of response as soon as available
    }
}
