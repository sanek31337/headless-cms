<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\HttpClient;

class CRUDControllerTest extends WebTestCase
{
    public function testReadArticle()
    {
        $client = static::createClient();

        $client->request('GET', '/article/?limit=1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testCreateArticleWithoutAuthToken()
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/article/',
            [],
            [],
            [
                'HTTP_Content-Type' => 'application/json',
            ],
            '{"title":"title1","body":"body1"}'
        );


        $this->assertEquals($client->getResponse()->getStatusCode(), 401, false);
    }

    public function testCreateArticleWithAuthToken()
    {
        $client = static::createClient();

        $data = [
            "title" => "title3",
            "body" => "body3"
        ];

        $client->request(
            'PUT',
            '/article/',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X-AUTH-TOKEN' => 'secretToken'
            ],
            json_encode($data)
        );

        $this->assertEquals($client->getResponse()->getStatusCode(), 200, false);
    }
}