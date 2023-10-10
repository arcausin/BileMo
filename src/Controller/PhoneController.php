<?php

namespace App\Controller;

use App\Repository\PhoneRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;

class PhoneController extends AbstractController
{
    #[Route('/api/phones', name: 'app_phones_index', methods: ['GET'])]
    public function index(PhoneRepository $phoneRepository, SerializerInterface $serializer): JsonResponse
    {
        $phones = $phoneRepository->findAll();
        $jsonPhones = $serializer->serialize($phones, 'json');

        return new JsonResponse($jsonPhones, Response::HTTP_OK, [], true);
    }

    #[Route('/api/phones/{id}', name: 'app_phones_show', methods: ['GET'])]
    public function show(int $id, PhoneRepository $phoneRepository, SerializerInterface $serializer): JsonResponse
    {
        $phone = $phoneRepository->find($id);

        if ($phone) {
            $jsonPhone = $serializer->serialize($phone, 'json');
            return new JsonResponse($jsonPhone, Response::HTTP_OK, [], true);
        }

        return new JsonResponse('Phone not found', Response::HTTP_NOT_FOUND);
    }
}
