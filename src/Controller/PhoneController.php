<?php

namespace App\Controller;

use App\Entity\Phone;
use App\Repository\PhoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PhoneController extends AbstractController
{
    #[Route('/api/phones', name: 'app_phones_index', methods: ['GET'])]
    public function index(PhoneRepository $phoneRepository, SerializerInterface $serializer): JsonResponse
    {
        $phones = $phoneRepository->findAll();
        $jsonPhones = $serializer->serialize($phones, 'json');

        return new JsonResponse($jsonPhones, Response::HTTP_OK, [], true);
    }

    #[Route('/api/phones', name: 'app_phones_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Only admins can access this resource')]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator): JsonResponse
    {
        $phone = $serializer->deserialize($request->getContent(), Phone::class, 'json');

        $errors = $validator->validate($phone);

        if ($errors->count() > 0) {
            $jsonErrors = $serializer->serialize($errors, 'json');
            return new JsonResponse($jsonErrors, Response::HTTP_BAD_REQUEST, [], true);
        }

        $content = $request->toArray();
        $phone->setReleaseAt(new \DateTimeImmutable($content['releaseAt']));

        $em->persist($phone);
        $em->flush();

        $jsonPhone = $serializer->serialize($phone, 'json');

        $location = $urlGenerator->generate('app_phones_show', ['id' => $phone->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonPhone, Response::HTTP_CREATED, ['Location' => $location], true);
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

    #[Route('/api/phones/{id}', name: 'app_phones_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Only admins can access this resource')]
    public function update(int $id, Request $request, PhoneRepository $phoneRepository, SerializerInterface $serializer, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $phone = $phoneRepository->find($id);

        if ($phone) {
            $updatePhone = $serializer->deserialize($request->getContent(), Phone::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $phone]);

            $errors = $validator->validate($updatePhone);

            if ($errors->count() > 0) {
                $jsonErrors = $serializer->serialize($errors, 'json');
                return new JsonResponse($jsonErrors, Response::HTTP_BAD_REQUEST, [], true);
            }

            $content = $request->toArray();
            $updatePhone->setReleaseAt(new \DateTimeImmutable($content['releaseAt']));

            $em->persist($updatePhone);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse('Phone not found', Response::HTTP_NOT_FOUND);
    }

    #[Route('/api/phones/{id}', name: 'app_phones_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Only admins can access this resource')]
    public function delete(int $id, PhoneRepository $phoneRepository, EntityManagerInterface $em): JsonResponse
    {
        $phone = $phoneRepository->find($id);

        if ($phone) {
            $em->remove($phone);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse('Phone not found', Response::HTTP_NOT_FOUND);
    }
}
