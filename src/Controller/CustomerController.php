<?php

namespace App\Controller;

use App\Entity\Customer;
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

class CustomerController extends AbstractController
{
    #[Route('/api/customers', name: 'app_customers_index', methods: ['GET'])]
    public function index(CustomerRepository $customerRepository, SerializerInterface $serializer): JsonResponse
    {
        $customers = $customerRepository->findAll();
        $jsonCustomers = $serializer->serialize($customers, 'json', ['groups' => 'getCustomers']);

        return new JsonResponse($jsonCustomers, Response::HTTP_OK, [], true);
    }

    #[Route('/api/customers', name: 'app_customers_create', methods: ['POST'])]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator): JsonResponse
    {
        $customer = $serializer->deserialize($request->getContent(), Customer::class, 'json');

        $errors = $validator->validate($customer);

        if ($errors->count() > 0) {
            $jsonErrors = $serializer->serialize($errors, 'json');
            return new JsonResponse($jsonErrors, Response::HTTP_BAD_REQUEST, [], true);
        }

        $customer->setCreatedAt(new \DateTimeImmutable());

        $em->persist($customer);
        $em->flush();

        $jsonCustomer = $serializer->serialize($customer, 'json', ['groups' => 'getCustomers']);

        $location = $urlGenerator->generate('app_customers_show', ['id' => $customer->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonCustomer, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('/api/customers/{id}', name: 'app_customers_show', methods: ['GET'])]
    public function show(int $id, CustomerRepository $customerRepository, SerializerInterface $serializer): JsonResponse
    {
        $customer = $customerRepository->find($id);

        if ($customer) {
            $jsonCustomer = $serializer->serialize($customer, 'json', ['groups' => 'getCustomers']);
            return new JsonResponse($jsonCustomer, Response::HTTP_OK, [], true);
        }

        return new JsonResponse('Customer not found', Response::HTTP_NOT_FOUND);
    }

    #[Route('/api/customers/{id}', name: 'app_customers_update', methods: ['PUT'])]
    public function update(int $id, Request $request, CustomerRepository $customerRepository, SerializerInterface $serializer, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $customer = $customerRepository->find($id);

        if ($customer) {
            $updateCustomer = $serializer->deserialize($request->getContent(), Customer::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $customer]);

            $errors = $validator->validate($updateCustomer);

            if ($errors->count() > 0) {
                $jsonErrors = $serializer->serialize($errors, 'json');
                return new JsonResponse($jsonErrors, Response::HTTP_BAD_REQUEST, [], true);
            }
            
            $em->persist($updateCustomer);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse('Customer not found', Response::HTTP_NOT_FOUND);
    }

    #[Route('/api/customers/{id}', name: 'app_customers_delete', methods: ['DELETE'])]
    public function delete(int $id, CustomerRepository $customerRepository, EntityManagerInterface $em): JsonResponse
    {
        $customer = $customerRepository->find($id);

        if ($customer) {
            $em->remove($customer);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse('Customer not found', Response::HTTP_NOT_FOUND);
    }
}
