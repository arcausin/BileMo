<?php

namespace App\Controller;

use App\Repository\CustomerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;

class CustomerController extends AbstractController
{
    #[Route('/api/customers', name: 'app_customers_index', methods: ['GET'])]
    public function index(CustomerRepository $customerRepository, SerializerInterface $serializer): JsonResponse
    {
        $customers = $customerRepository->findAll();
        $jsonCustomers = $serializer->serialize($customers, 'json', ['groups' => 'getCustomers']);

        return new JsonResponse($jsonCustomers, Response::HTTP_OK, [], true);
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
}
