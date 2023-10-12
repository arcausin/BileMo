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

class ConsumerController extends AbstractController
{
    #[Route('/api/consumers', name: 'app_consumers_index', methods: ['GET'])]
    public function index(ConsumerRepository $consumerRepository, SerializerInterface $serializer): JsonResponse
    {
        $consumers = $consumerRepository->findAll();
        $jsonConsumers = $serializer->serialize($consumers, 'json', ['groups' => 'getConsumers']);

        return new JsonResponse($jsonConsumers, Response::HTTP_OK, [], true);
    }

    #[Route('/api/consumers', name: 'app_consumers_create', methods: ['POST'])]
    public function create(Request $request, SerializerInterface $serializer, CustomerRepository $customerRepository, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $consumer = $serializer->deserialize($request->getContent(), Consumer::class, 'json');
        $content = $request->toArray();
        $idCustomer = $content['idCustomer'] ?? -1;
        $consumer->setCustomer($customerRepository->find($idCustomer));
        $consumer->setCreatedAt(new \DateTimeImmutable());
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

        if ($consumer) {
            $jsonConsumer = $serializer->serialize($consumer, 'json', ['groups' => 'getConsumers']);
            return new JsonResponse($jsonConsumer, Response::HTTP_OK, [], true);
        }

        return new JsonResponse('Consumer not found', Response::HTTP_NOT_FOUND);
    }

    #[Route('/api/consumers/{id}', name: 'app_consumers_update', methods: ['PUT'])]
    public function update(int $id, Request $request, ConsumerRepository $consumerRepository, SerializerInterface $serializer, CustomerRepository $customerRepository, EntityManagerInterface $em): JsonResponse
    {
        $consumer = $consumerRepository->find($id);

        if ($consumer) {
            $updatedConsumer = $serializer->deserialize($request->getContent(), Consumer::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $consumer]);
            $content = $request->toArray();
            $idCustomer = $content['idCustomer'] ?? -1;
            $updatedConsumer->setCustomer($customerRepository->find($idCustomer));
            $em->persist($updatedConsumer);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse('Consumer not found', Response::HTTP_NOT_FOUND);
    }

    #[Route('/api/consumers/{id}', name: 'app_consumers_delete', methods: ['DELETE'])]
    public function delete(int $id, ConsumerRepository $consumerRepository, EntityManagerInterface $em): JsonResponse
    {
        $consumer = $consumerRepository->find($id);

        if ($consumer) {
            $em->remove($consumer);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse('Consumer not found', Response::HTTP_NOT_FOUND);
    }
}
