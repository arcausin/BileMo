<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class CustomerController extends AbstractController
{
    private $customerPasswordHasher;

    public function __construct(UserPasswordHasherInterface $customerPasswordHasher)
    {
        $this->customerPasswordHasher = $customerPasswordHasher;
    }

    /**
     * This method allows you to recover all the customers.
     *
     * @OA\Response(
     *     response=200,
     *     description="Return the list of customers",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Customer::class, groups={"getCustomers"}))
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
     * @OA\Tag(name="Customers")
     *
     * @param CustomerRepository $customerRepository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/api/customers', name: 'app_customers_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Only admins can access this resource')]
    public function index(CustomerRepository $customerRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 5);

        $cacheKey = 'customers_' . $page . '_' . $limit;

        $jsonCustomerList = $cache->get($cacheKey, function (ItemInterface $item) use ($customerRepository, $page, $limit, $serializer) {
            $item->tag('customersCache');
            $item->expiresAfter(300);

            $customerList = $customerRepository->findBy([], [], $limit, ($page - 1) * $limit);

            $context = SerializationContext::create()->setGroups(['getCustomers']);
            return $serializer->serialize($customerList, 'json', $context);
        });

        if (empty($jsonCustomerList) || $jsonCustomerList == '[]') {
            return new JsonResponse('Customers not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($jsonCustomerList, Response::HTTP_OK, [], true);
    }

    /**
     * This method allows you to create a customer.
     * 
     * @OA\Response(
     *     response=201,
     *     description="Create a customer",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Customer::class, groups={"getCustomers"}))
     *     )
     * )
     * 
     * @OA\RequestBody(
     *     description="Create a customer",
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="name", type="string", default="Customer 1"),
     *         @OA\Property(property="email", type="string", default="customer1@test.com"),
     *         @OA\Property(property="password", type="string", default="passwordPost")
     *     )
     * )
     * 
     * @OA\Tag(name="Customers")
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @param UrlGeneratorInterface $urlGenerator
     * @param ValidatorInterface $validator
     * @return JsonResponse
    */
    #[Route('/api/customers', name: 'app_customers_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Only admins can access this resource')]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $customer = $serializer->deserialize($request->getContent(), Customer::class, 'json');

        $errors = $validator->validate($customer);

        if ($errors->count() > 0) {
            $jsonErrors = $serializer->serialize($errors, 'json');
            return new JsonResponse($jsonErrors, Response::HTTP_BAD_REQUEST, [], true);
        }

        $customer->setCreatedAt(new \DateTimeImmutable());
        $customer->setPassword($this->customerPasswordHasher->hashPassword($customer, $customer->getPassword()));

        $cache->invalidateTags(['customersCache']);
        $em->persist($customer);
        $em->flush();

        $context = SerializationContext::create()->setGroups(['getCustomers']);
        $jsonCustomer = $serializer->serialize($customer, 'json', $context);

        $location = $urlGenerator->generate('app_customers_show', ['id' => $customer->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonCustomer, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    /**
     * This method allows you to recover a customer.
     * 
     * @OA\Response(
     *     response=200,
     *     description="Return a customer",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Customer::class, groups={"getCustomers"}))
     *    )
     * )
     * 
     * @OA\Tag(name="Customers")
     * 
     * @param int $id
     * @param PhoneRepository $phoneRepository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/customers/{id}', name: 'app_customers_show', methods: ['GET'])]
    public function show(int $id, CustomerRepository $customerRepository, SerializerInterface $serializer): JsonResponse
    {
        $customer = $customerRepository->find($id);

        if ($customer) {
            if ($customer === $this->getUser() || $this->isGranted('ROLE_ADMIN')) {
                $context = SerializationContext::create()->setGroups(['getCustomers']);
                $jsonCustomer = $serializer->serialize($customer, 'json', $context);
                return new JsonResponse($jsonCustomer, Response::HTTP_OK, [], true);
            }
        }

        return new JsonResponse('Customer not found', Response::HTTP_NOT_FOUND);
    }

    /**
     * This method allows you to update a customer.
     * 
     * @OA\Response(
     *     response=204,
     *     description="Update a customer"
     * )
     * 
     * @OA\RequestBody(
     *     description="Update a customer",
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="name", type="string", default="Customer 1"),
     *         @OA\Property(property="email", type="string", default="customer1@test.com"),
     *         @OA\Property(property="password", type="string", default="passwordPost")
     *     )
     * )
     * 
     * @OA\Tag(name="Customers")
     * 
     * @param int $id
     * @param Request $request
     * @param CustomerRepository $customerRepository
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     * @return JsonResponse
    */
    #[Route('/api/customers/{id}', name: 'app_customers_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Only admins can access this resource')]
    public function update(int $id, Request $request, CustomerRepository $customerRepository, SerializerInterface $serializer, EntityManagerInterface $em, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $customer = $customerRepository->find($id);

        if ($customer) {
            $updateCustomer = $serializer->deserialize($request->getContent(), Customer::class, 'json');

            $customer->setName($updateCustomer->getName());
            $customer->setEmail($updateCustomer->getEmail());
            $customer->setPassword($this->customerPasswordHasher->hashPassword($customer, $updateCustomer->getPassword()));

            $errors = $validator->validate($customer);

            if ($errors->count() > 0) {
                $jsonErrors = $serializer->serialize($errors, 'json');
                return new JsonResponse($jsonErrors, Response::HTTP_BAD_REQUEST, [], true);
            }
            
            $cache->invalidateTags(['customersCache']);
            $em->persist($customer);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse('Customer not found', Response::HTTP_NOT_FOUND);
    }

    /**
     * This method allows you to delete a customer.
     * 
     * @OA\Response(
     *     response=204,
     *     description="Delete a customer"
     * )
     * 
     * @OA\Tag(name="Customers")
     * 
     * @param int $id
     * @param CustomerRepository $customerRepository
     * @param EntityManagerInterface $em
     * @return JsonResponse
    */
    #[Route('/api/customers/{id}', name: 'app_customers_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Only admins can access this resource')]
    public function delete(int $id, CustomerRepository $customerRepository, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        $customer = $customerRepository->find($id);

        if ($customer) {
            $cache->invalidateTags(['customersCache']);
            $em->remove($customer);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse('Customer not found', Response::HTTP_NOT_FOUND);
    }
}
