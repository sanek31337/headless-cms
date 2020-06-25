<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class ArticleController
 * @package App\Controller
 *
 * @Route("/article")
 */
class ArticleController extends AbstractController
{
    private const SORT_FIELD = 'sortField';
    private const SORT_ORDER = 'sortOrder';
    private const LIMIT = 'limit';
    private const OFFSET = 'offset';

    private const ARTICLE_TITLE = 'title';
    private const ARTICLE_BODY = 'body';
    private const ARTICLE_CREATED_AT = 'created_at';
    private const ARTICLE_UPDATED_AT = 'updated_at';

    private function getRequestData()
    {
        $request = Request::createFromGlobals();

        $data = $request->getContent();

        return $data;
    }

    /**
     * @Route("/", methods={"GET"})
     */
    public function readAll(ArticleRepository $articleRepository)
    {
        try
        {
            $request = Request::createFromGlobals();

            $sortFieldValue = $request->get(self::SORT_FIELD, 'created_at');
            $sortOrderValue = $request->get(self::SORT_ORDER, 'DESC');
            $limitValue = $request->get(self::LIMIT, 50);
            $offsetValue = $request->get(self::OFFSET, 0);

            $this->checkRequestStructure(
                [
                    self::SORT_FIELD => $sortFieldValue,
                    self::SORT_ORDER => $sortOrderValue,
                    self::LIMIT => $limitValue,
                    self::OFFSET => $offsetValue
                ],
                [
                self::SORT_FIELD => [
                    'allowedValues' => [
                        self::ARTICLE_TITLE,
                        self::ARTICLE_BODY,
                        self::ARTICLE_CREATED_AT,
                        self::ARTICLE_UPDATED_AT,
                        null
                    ]
                ],
                self::SORT_ORDER => [
                    'allowedValues' => [
                        'ASC',
                        'DESC'
                    ]
                ],
                self::LIMIT => [
                    'regexRule' => '/\d+/isx'
                ],
                self::OFFSET => [
                    'regexRule' => '/\d+/isx'
                ]
            ]);

            $articles = $articleRepository->getAllArticles($sortFieldValue, $sortOrderValue, $limitValue, $offsetValue);

            $content = $this->prepareContent($articles);
        }
        catch (Exception $exception)
        {
            $content = 'Can not get list of articles. Reason: ' . $exception->getMessage();
        }

        $response = new Response($content, Response::HTTP_OK, ['Content-Type' => 'application/json']);

        return $response;
    }

    /**
     * @Route("/{id}", methods={"GET"})
     */
    public function readOne($id, ArticleRepository $articleRepository)
    {
        $article = $articleRepository->find($id);

        if (!$article)
        {
            throw $this->createNotFoundException('No article found for id: ' . $id);
        }

        $response = $this->entityResponseBuilder($article);
        return $response;
    }

    /**
     * @Route("/", methods={"PUT", "POST"})
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')", statusCode=504, message="Access forbidden")
     */
    public function createArticle()
    {
        try
        {
            $request = Request::createFromGlobals();

            $data = $request->getContent();

            $preparedData = $this->transformRequestBody($data, [self::ARTICLE_TITLE, self::ARTICLE_BODY]);

            $em = $this->getDoctrine()->getManager();

            $article = new Article();
            $article->setTitle($preparedData['title']);
            $article->setBody($preparedData['body']);

            $em->persist($article);

            $em->flush();

            $content = $this->prepareContent($article);
            $response = new Response($content, Response::HTTP_OK, ['Content-Type' => 'text/json']);

            return $response;
        }
        catch (Exception $exception)
        {
            throw new Exception('Can not create article');
        }
    }

    /**
     * @Route("/{id}", methods={"PATCH"})
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')", statusCode=504, message="Access forbidden")
     */
    public function updateArticle($id, ArticleRepository $articleRepository)
    {
        $article = $articleRepository->find($id);

        if (!$article)
        {
            throw $this->createNotFoundException('No article found for id: ' . $id);
        }

        try
        {
            $request = Request::createFromGlobals();

            $data = $request->getContent();

            $preparedData = $this->transformRequestBody($data, [self::ARTICLE_TITLE, self::ARTICLE_BODY]);

            $article->setTitle($preparedData['title']);
            $article->setBody($preparedData['body']);

            $em = $this->getDoctrine()->getManager();

            $em->persist($article);
            $em->flush();

            $response = $this->entityResponseBuilder($article);

            return $response;
        }
        catch (Exception $exception)
        {
            throw new Exception('Can not update article. ID: ' . $id);
        }
    }

    /**
     * @Route("/{id}", methods={"DELETE"})
     * @Security("is_granted('USER')", statusCode=504, message="Access forbidden")
     */
    public function deleteArticle($id, ArticleRepository $articleRepository)
    {
        $article = $articleRepository->find($id);

        if (!$article)
        {
            throw $this->createNotFoundException('No article found for id: ' . $id);
        }

        try
        {
            $em = $this->getDoctrine()->getManager();
            $em->remove($article);
            $em->flush();

            $content = 'The article was successfully removed';

            $response = $this->entityResponseBuilder($content);

            return $response;
        }
        catch (Exception $exception)
        {
            throw $exception;
        }
    }

    /**
     * @param $content
     * @return string
     */
    private function prepareContent($content)
    {
        $serializer = new Serializer(
            [new ObjectNormalizer()],
            [new JsonEncoder()]
        );

        return $serializer->serialize($content, 'json');
    }

    /**
     * @param $data
     * @param array $mandatoryFields
     * @return mixed
     * @throws Exception
     */
    private function transformRequestBody($data, $mandatoryFields, $requestFormat = 'json')
    {
        try
        {
            $serializer = new Serializer(
                [new JsonSerializableNormalizer()],
                [new JsonEncoder(), new XmlEncoder()]
            );

            $serializedData = $serializer->decode($data, $requestFormat);

            $this->checkRequestStructure($serializedData, $mandatoryFields);

            return $serializedData;
        }
        catch (Exception $exception)
        {
            throw $exception;
        }
    }

    /**
     * @param $data
     * @param array $mandatoryFields
     * @throws Exception
     */
    private function checkRequestStructure($data, $mandatoryFields = [])
    {
        if (count($mandatoryFields) > 0)
        {
            foreach ($data as $itemName => $itemValue)
            {
                if (!in_array($itemName, array_keys($mandatoryFields)))
                {
                    throw new Exception('The field ' . $itemName . ' is mandatory. Please provide it.');
                }
                else
                {
                    if (isset($mandatoryFields[$itemName]))
                    {
                        if (array_key_exists('allowedValues', $mandatoryFields[$itemName]))
                        {
                            if (!in_array($itemValue, array_values($mandatoryFields[$itemName]['allowedValues'])))
                            {
                                throw new Exception('The field ' . $itemName . ' value is incorrect. Please use only one from the following list: ' . implode(', ', $fieldRequirements['allowedValues']));
                            }
                        }
                        elseif (array_key_exists('regexRule', $mandatoryFields[$itemName]))
                        {
                            if (preg_match($mandatoryFields[$itemName]['regexRule'], $itemValue) == 0)
                            {
                                throw new Exception('The field ' . $itemName . ' value is incorrect. Please use correct value');
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $data
     * @return Response
     */
    private function entityResponseBuilder($data)
    {
        $content = $this->prepareContent($data);

        $response = new Response($content, Response::HTTP_OK, ['Content-Type' => 'text/json']);

        return $response;
    }
}