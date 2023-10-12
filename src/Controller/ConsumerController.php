<?php

namespace App\Controller;

use App\Repository\ConsumerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;

class ConsumerController extends AbstractController
{
    #[Route('/api/consumers', name: 'app_consumers_index', methods: ['GET'])]
    public function index(ConsumerRepository $consumerRepository, SerializerInterface $serializer): JsonResponse
    {
        $consumers = $consumerRepository->findAll();
        $jsonConsumers = $serializer->serialize($consumers, 'json', ['groups' => 'getConsumers']);

        return new JsonResponse($jsonConsumers, Response::HTTP_OK, [], true);
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
}
