<?php

namespace App\Controller;

use App\Entity\Consumer;
use App\Repository\ConsumerRepository;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class ConsumerController extends AbstractController
{
    /**
     * This method allows you to recover all the consumers.
     *
     * @OA\Response(
     *     response=200,
     *     description="Return the list of consumers",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Consumer::class, groups={"getConsumers"}))
     *     )
     * )
     * 
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="The page you want to retrieve",
     *     @OA\Schema(type="int")
     * )
     *
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="The number of items you want to recover",
     *     @OA\Schema(type="int")
     * )
     * 
     * @OA\Tag(name="Consumers")
     *
     * @param ConsumerRepository $consumerRepository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/api/consumers', name: 'app_consumers_index', methods: ['GET'])]
    public function index(ConsumerRepository $consumerRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 5);

        $cacheKey = 'consumers_' . $page . '_' . $limit;

        $jsonConsumerList = $cache->get($cacheKey, function (ItemInterface $item) use ($consumerRepository, $page, $limit, $serializer) {
            $item->tag('consumersCache');
            $item->expiresAfter(300);

            if ($this->isGranted('ROLE_ADMIN')) {
                $consumerList = $consumerRepository->findBy([], [], $limit, ($page - 1) * $limit);
            } else {
                $consumerList = $consumerRepository->findBy(['customer' => $this->getUser()], [], $limit, ($page - 1) * $limit);
            }

            $context = SerializationContext::create()->setGroups(['getConsumers']);
            return $serializer->serialize($consumerList, 'json', $context);
        });

        if (empty($jsonConsumerList) || $jsonConsumerList == '[]') {
            return new JsonResponse('Consumers not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($jsonConsumerList, Response::HTTP_OK, [], true);
    }

    /**
     * This method allows you to create a consumer.
     * 
     * @OA\Response(
     *     response=201,
     *     description="Create a consumer",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Consumer::class, groups={"getConsumers"}))
     *     )
     * )
     * 
     * @OA\RequestBody(
     *     description="Create a consumer",
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="firstName", type="string", default="Name 1"),
     *         @OA\Property(property="lastName", type="string", default="Surname 1"),
     *         @OA\Property(property="email", type="string", default="consumer1@test.com")
     *     )
     * )
     * 
     * @OA\Tag(name="Consumers")
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param CustomerRepository $customerRepository
     * @param EntityManagerInterface $em
     * @param UrlGeneratorInterface $urlGenerator
     * @param ValidatorInterface $validator
     * @return JsonResponse
    */
    #[Route('/api/consumers', name: 'app_consumers_create', methods: ['POST'])]
    public function create(Request $request, SerializerInterface $serializer, CustomerRepository $customerRepository, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $consumer = $serializer->deserialize($request->getContent(), Consumer::class, 'json');

        $errors = $validator->validate($consumer);

        if ($errors->count() > 0) {
            $jsonErrors = $serializer->serialize($errors, 'json');
            return new JsonResponse($jsonErrors, Response::HTTP_BAD_REQUEST, [], true);
        }

        $consumer->setCustomer($customerRepository->find($this->getUser()));
        $consumer->setCreatedAt(new \DateTimeImmutable());

        $cache->invalidateTags(['consumersCache']);
        $em->persist($consumer);
        $em->flush();

        $context = SerializationContext::create()->setGroups(['getConsumers']);
        $jsonConsumer = $serializer->serialize($consumer, 'json', $context);

        $location = $urlGenerator->generate('app_consumers_show', ['id' => $consumer->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonConsumer, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    /**
     * This method allows you to recover a consumer.
     * 
     * @OA\Response(
     *     response=200,
     *     description="Return a consumer",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Consumer::class, groups={"getConsumers"}))
     *    )
     * )
     * 
     * @OA\Tag(name="Consumers")
     * 
     * @param int $id
     * @param ConsumerRepository $consumerRepository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/consumers/{id}', name: 'app_consumers_show', methods: ['GET'])]
    public function show(int $id, ConsumerRepository $consumerRepository, SerializerInterface $serializer): JsonResponse
    {
        $consumer = $consumerRepository->find($id);

        if ($consumer->getCustomer() != $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse('Consumer not found', Response::HTTP_NOT_FOUND);
        }

        if ($consumer) {
            $context = SerializationContext::create()->setGroups(['getConsumers']);
            $jsonConsumer = $serializer->serialize($consumer, 'json', $context);
            return new JsonResponse($jsonConsumer, Response::HTTP_OK, [], true);
        }

        return new JsonResponse('Consumer not found', Response::HTTP_NOT_FOUND);
    }

    /**
     * This method allows you to update a consumer.
     * 
     * @OA\Response(
     *     response=204,
     *     description="Update a consumer"
     * )
     * 
     * @OA\RequestBody(
     *     description="Update a consumer",
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="firstName", type="string", default="Name 1"),
     *         @OA\Property(property="lastName", type="string", default="Surname 1"),
     *         @OA\Property(property="email", type="string", default="consumer1@test.com")
     *     )
     * )
     * 
     * @OA\Tag(name="Consumers")
     * 
     * @param int $id
     * @param Request $request
     * @param ConsumerRepository $consumerRepository
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     * @return JsonResponse
    */
    #[Route('/api/consumers/{id}', name: 'app_consumers_update', methods: ['PUT'])]
    public function update(int $id, Request $request, ConsumerRepository $consumerRepository, SerializerInterface $serializer, EntityManagerInterface $em, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $consumer = $consumerRepository->find($id);

        if ($consumer->getCustomer() != $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse('Consumer not found', Response::HTTP_NOT_FOUND);
        }

        if ($consumer) {
            $updateConsumer = $serializer->deserialize($request->getContent(), Consumer::class, 'json');

            $consumer->setFirstName($updateConsumer->getFirstName());
            $consumer->setLastName($updateConsumer->getLastName());
            $consumer->setEmail($updateConsumer->getEmail());

            $errors = $validator->validate($consumer);

            if ($errors->count() > 0) {
                $jsonErrors = $serializer->serialize($errors, 'json');
                return new JsonResponse($jsonErrors, Response::HTTP_BAD_REQUEST, [], true);
            }

            $cache->invalidateTags(['consumersCache']);
            $em->persist($consumer);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse('Consumer not found', Response::HTTP_NOT_FOUND);
    }

    /**
     * This method allows you to delete a consumer.
     * 
     * @OA\Response(
     *     response=204,
     *     description="Delete a consumer"
     * )
     * 
     * @OA\Tag(name="Consumers")
     * 
     * @param int $id
     * @param ConsumerRepository $consumerRepository
     * @param EntityManagerInterface $em
     * @return JsonResponse
    */
    #[Route('/api/consumers/{id}', name: 'app_consumers_delete', methods: ['DELETE'])]
    public function delete(int $id, ConsumerRepository $consumerRepository, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        $consumer = $consumerRepository->find($id);

        if ($consumer->getCustomer() != $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse('Consumer not found', Response::HTTP_NOT_FOUND);
        }

        if ($consumer) {
            $cache->invalidateTags(['consumersCache']);
            $em->remove($consumer);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse('Consumer not found', Response::HTTP_NOT_FOUND);
    }
}
