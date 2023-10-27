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
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class PhoneController extends AbstractController
{
    /**
     * This method allows you to recover all the phones.
     *
     * @OA\Response(
     *     response=200,
     *     description="Return the list of phones",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Phone::class, groups={"getPhones"}))
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
     * @OA\Tag(name="Phones")
     *
     * @param PhoneRepository $phoneRepository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/api/phones', name: 'app_phones_index', methods: ['GET'])]
    public function index(PhoneRepository $phoneRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 5);

        $cacheKey = 'phones_' . $page . '_' . $limit;

        $jsonPhoneList = $cache->get($cacheKey, function (ItemInterface $item) use ($phoneRepository, $page, $limit, $serializer) {
            echo "Cache miss\n";
            $item->tag('phonesCache');
            $item->expiresAfter(300);

            $phoneList = $phoneRepository->findBy([], [], $limit, ($page - 1) * $limit);

            return $serializer->serialize($phoneList, 'json');
        });

        if (empty($jsonPhoneList) || $jsonPhoneList == '[]') {
            return new JsonResponse('Phones not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($jsonPhoneList, Response::HTTP_OK, [], true);
    }

    /**
     * This method allows you to create a phone.
     * 
     * @OA\Response(
     *     response=201,
     *     description="Create a phone",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Phone::class, groups={"getPhones"}))
     *     )
     * )
     * 
     * @OA\Parameter(
     *     name="brand",
     *     in="query",
     *     description="The brand of the phone",
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Parameter(
     *     name="model",
     *     in="query",
     *     description="The model of the phone",
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Parameter(
     *     name="image",
     *     in="query",
     *     description="The image of the phone",
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Parameter(
     *     name="price",
     *     in="query",
     *     description="The price of the phone",
     *     @OA\Schema(type="float")
     * )
     * 
     * @OA\Parameter(
     *     name="stock",
     *     in="query",
     *     description="The stock of the phone",
     *     @OA\Schema(type="int")
     * )
     * 
     * @OA\Parameter(
     *     name="releaseAt",
     *     in="query",
     *     description="The release date of the phone",
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Phones")
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @param UrlGeneratorInterface $urlGenerator
     * @param ValidatorInterface $validator
     * @return JsonResponse
    */
    #[Route('/api/phones', name: 'app_phones_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Only admins can access this resource')]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $phone = $serializer->deserialize($request->getContent(), Phone::class, 'json');

        $errors = $validator->validate($phone);

        if ($errors->count() > 0) {
            $jsonErrors = $serializer->serialize($errors, 'json');
            return new JsonResponse($jsonErrors, Response::HTTP_BAD_REQUEST, [], true);
        }

        $content = $request->toArray();
        $phone->setReleaseAt(new \DateTimeImmutable($content['releaseAt']));

        $cache->invalidateTags(['phonesCache']);
        $em->persist($phone);
        $em->flush();

        $jsonPhone = $serializer->serialize($phone, 'json');

        $location = $urlGenerator->generate('app_phones_show', ['id' => $phone->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonPhone, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    /**
     * This method allows you to recover a phone.
     * 
     * @OA\Response(
     *     response=200,
     *     description="Return a phone",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Phone::class, groups={"getPhones"}))
     *    )
     * )
     * 
     * @OA\Tag(name="Phones")
     * 
     * @param int $id
     * @param PhoneRepository $phoneRepository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
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

    /**
     * This method allows you to update a phone.
     * 
     * @OA\Response(
     *     response=204,
     *     description="Update a phone"
     * )
     * 
     * @OA\Parameter(
     *     name="brand",
     *     in="query",
     *     description="The brand of the phone",
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Parameter(
     *     name="model",
     *     in="query",
     *     description="The model of the phone",
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Parameter(
     *     name="image",
     *     in="query",
     *     description="The image of the phone",
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Parameter(
     *     name="price",
     *     in="query",
     *     description="The price of the phone",
     *     @OA\Schema(type="float")
     * )
     * 
     * @OA\Parameter(
     *     name="stock",
     *     in="query",
     *     description="The stock of the phone",
     *     @OA\Schema(type="int")
     * )
     * 
     * @OA\Parameter(
     *     name="releaseAt",
     *     in="query",
     *     description="The release date of the phone",
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Phones")
     * 
     * @param int $id
     * @param Request $request
     * @param PhoneRepository $phoneRepository
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     * @return JsonResponse
    */
    #[Route('/api/phones/{id}', name: 'app_phones_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Only admins can access this resource')]
    public function update(int $id, Request $request, PhoneRepository $phoneRepository, SerializerInterface $serializer, EntityManagerInterface $em, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $phone = $phoneRepository->find($id);

        if ($phone) {
            $updatePhone = $serializer->deserialize($request->getContent(), Phone::class, 'json');

            $phone->setBrand($updatePhone->getBrand());
            $phone->setModel($updatePhone->getModel());
            $phone->setImage($updatePhone->getImage());
            $phone->setPrice($updatePhone->getPrice());
            $phone->setStock($updatePhone->getStock());

            $errors = $validator->validate($phone);

            if ($errors->count() > 0) {
                $jsonErrors = $serializer->serialize($errors, 'json');
                return new JsonResponse($jsonErrors, Response::HTTP_BAD_REQUEST, [], true);
            }

            $content = $request->toArray();
            $phone->setReleaseAt(new \DateTimeImmutable($content['releaseAt']));

            $cache->invalidateTags(['phonesCache']);
            $em->persist($phone);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse('Phone not found', Response::HTTP_NOT_FOUND);
    }

    /**
     * This method allows you to delete a phone.
     * 
     * @OA\Response(
     *     response=204,
     *     description="Delete a phone"
     * )
     * 
     * @OA\Tag(name="Phones")
     * 
     * @param int $id
     * @param PhoneRepository $phoneRepository
     * @param EntityManagerInterface $em
     * @return JsonResponse
    */
    #[Route('/api/phones/{id}', name: 'app_phones_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Only admins can access this resource')]
    public function delete(int $id, PhoneRepository $phoneRepository, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        $phone = $phoneRepository->find($id);

        if ($phone) {
            $cache->invalidateTags(['phonesCache']);
            $em->remove($phone);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse('Phone not found', Response::HTTP_NOT_FOUND);
    }
}
