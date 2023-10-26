<?php

namespace App\Controller;

use App\Entity\Consumer;
use App\Repository\ConsumerRepository;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ConsumerController extends AbstractController
{
    #[Route('/api/consumers', name: 'app_consumers_index', methods: ['GET'])]
    public function index(ConsumerRepository $consumerRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 5);

        $cacheKey = 'consumers_' . $page . '_' . $limit;

        $jsonConsumerList = $cache->get($cacheKey, function (ItemInterface $item) use ($consumerRepository, $page, $limit, $serializer) {
            echo "Cache miss\n";
            $item->tag('consumersCache');
            $item->expiresAfter(300);

            if ($this->isGranted('ROLE_ADMIN')) {
                $consumerList = $consumerRepository->findBy([], [], $limit, ($page - 1) * $limit);
            }
            else {
                $consumerList = $consumerRepository->findBy(['customer' => $this->getUser()], [], $limit, ($page - 1) * $limit);
            }

            return $serializer->serialize($consumerList, 'json', ['groups' => 'getConsumers']);
        });

        if (empty($jsonConsumerList) || $jsonConsumerList == '[]') {
            return new JsonResponse('Consumers not found', Response::HTTP_NOT_FOUND);
        }
        
        return new JsonResponse($jsonConsumerList, Response::HTTP_OK, [], true);
    }

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

        $jsonConsumer = $serializer->serialize($consumer, 'json', ['groups' => 'getConsumers']);

        $location = $urlGenerator->generate('app_consumers_show', ['id' => $consumer->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonConsumer, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('/api/consumers/{id}', name: 'app_consumers_show', methods: ['GET'])]
    public function show(int $id, ConsumerRepository $consumerRepository, SerializerInterface $serializer): JsonResponse
    {
        $consumer = $consumerRepository->find($id);

        if ($consumer->getCustomer() != $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse('Consumer not found', Response::HTTP_NOT_FOUND);
        }

        if ($consumer) {
            $jsonConsumer = $serializer->serialize($consumer, 'json', ['groups' => 'getConsumers']);
            return new JsonResponse($jsonConsumer, Response::HTTP_OK, [], true);
        }

        return new JsonResponse('Consumer not found', Response::HTTP_NOT_FOUND);
    }

    #[Route('/api/consumers/{id}', name: 'app_consumers_update', methods: ['PUT'])]
    public function update(int $id, Request $request, ConsumerRepository $consumerRepository, SerializerInterface $serializer, CustomerRepository $customerRepository, EntityManagerInterface $em, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $consumer = $consumerRepository->find($id);

        if ($consumer->getCustomer() != $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse('Consumer not found', Response::HTTP_NOT_FOUND);
        }

        if ($consumer) {
            $updatedConsumer = $serializer->deserialize($request->getContent(), Consumer::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $consumer]);

            $errors = $validator->validate($updatedConsumer);

            if ($errors->count() > 0) {
                $jsonErrors = $serializer->serialize($errors, 'json');
                return new JsonResponse($jsonErrors, Response::HTTP_BAD_REQUEST, [], true);
            }
            
            $cache->invalidateTags(['consumersCache']);
            $em->persist($updatedConsumer);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse('Consumer not found', Response::HTTP_NOT_FOUND);
    }

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
