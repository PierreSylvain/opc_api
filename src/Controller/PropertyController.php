<?php

namespace App\Controller;

use App\Entity\Property;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/properties")
 */
class PropertyController extends AbstractController
{
    #[Route('/properties', name: 'property_index', methods: ['GET'])]
    public function index(PropertyRepository $propertyRepo): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès interdit'], 403);
        }

        $properties = $propertyRepo->findAll();

        $data = [];
        foreach ($properties as $property) {
            $data[] = [
                'id'       => $property->getId(),
                'name'     => $property->getName(),
                'url'      => $property->getUrl(),
                'address'  => $property->getAddress(),
                'city'     => $property->getCity(),
                'zipCode'  => $property->getZipCode(),
                'country'  => $property->getCountry(),
                'image'    => $property->getImage(),
                'area'     => $property->getArea(),
                'users'    => array_map(fn($u) => $u->getId(), $property->getUsers()->toArray()),
            ];
        }
        return new JsonResponse($data);
    }

    #[Route('/properties', name: 'property_new', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $property = new Property();
        $property->setName($data['name'] ?? 'Default')
            ->setUrl($data['url'] ?? 'default-url')
            ->setAddress($data['address'] ?? 'Unknown')
            ->setCity($data['city'] ?? 'Unknown')
            ->setZipCode($data['zipCode'] ?? '')
            ->setCountry($data['country'] ?? '')
            ->setImage($data['image'] ?? '')
            ->setArea($data['area'] ?? null);
            $property->addUser($this->getUser());
        // Si vous voulez lier des utilisateurs (IDs) :
        // if (isset($data['userIds'])) {
        //     foreach ($data['userIds'] as $userId) {
        //         $user = $em->getRepository(User::class)->find($userId);
        //         if ($user) {
        //             $property->addUser($user);
        //         }
        //     }
        // }

        $em->persist($property);
        $em->flush();

        return new JsonResponse(['message' => 'Property créée avec ID = '.$property->getId()], 201);
    }

    #[Route('/properties/{id}', name: 'property_show', methods: ['GET'])]
    public function show(int $id, PropertyRepository $propertyRepo): JsonResponse
    {
        $property = $propertyRepo->find($id);
        if (!$property) {
            return new JsonResponse(['error' => 'Property not found'], 404);
        }

        $found_users = array_map(fn($u) => $u->getId(), $property->getUsers()->toArray());
        $current_user_id = $this->getUser()->getId();

        if (!in_array($current_user_id, $found_users)) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = [
            'id'       => $property->getId(),
            'name'     => $property->getName(),
            'url'      => $property->getUrl(),
            'address'  => $property->getAddress(),
            'city'     => $property->getCity(),
            'zipCode'  => $property->getZipCode(),
            'country'  => $property->getCountry(),
            'image'    => $property->getImage(),
            'area'     => $property->getArea(),
            'users'    => array_map(fn($u) => $u->getId(), $property->getUsers()->toArray()),
        ];

        return new JsonResponse($data);
    }

    #[Route('/properties/{id}', name: 'property_edit', methods: ['PUT','PATCH'])]
    public function update(int $id, Request $request, EntityManagerInterface $em, PropertyRepository $propertyRepo): JsonResponse
    {
        $property = $propertyRepo->find($id);
        if (!$property) {
            return new JsonResponse(['error' => 'Property not found'], 404);
        }

        $found_users = array_map(fn($u) => $u->getId(), $property->getUsers()->toArray());
        $current_user_id = $this->getUser()->getId();

        if (!in_array($current_user_id, $found_users)) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);

        // M-à-J partielle ou totale selon PUT/PATCH
        if (isset($data['name'])) {
            $property->setName($data['name']);
        }
        if (isset($data['url'])) {
            $property->setUrl($data['url']);
        }
        // etc. pour chaque champ
        if (isset($data['address'])) {
            $property->setAddress($data['address']);
        }
        if (isset($data['city'])) {
            $property->setCity($data['city']);
        }
        if (isset($data['zipCode'])) {
            $property->setZipCode($data['zipCode']);
        }
        if (isset($data['country'])) {
            $property->setCountry($data['country']);
        }
        if (isset($data['image'])) {
            $property->setImage($data['image']);
        }
        if (isset($data['area'])) {
            $property->setArea($data['area']);
        }

        // Mise à jour des users si besoin (ajout / retrait)
        // ex: $property->clearUsers(), puis addUser() pour ceux spécifiés.

        $em->flush();
        return new JsonResponse(['message' => 'Property mise à jour.']);
    }

    #[Route('/properties/{id}', name: 'property_delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em, PropertyRepository $propertyRepo): JsonResponse
    {
        $property = $propertyRepo->find($id);
        if (!$property) {
            return new JsonResponse(['error' => 'Property not found'], 404);
        }

        $found_users = array_map(fn($u) => $u->getId(), $property->getUsers()->toArray());
        $current_user_id = $this->getUser()->getId();

        if (!in_array($current_user_id, $found_users)) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }
        
        $em->remove($property);
        $em->flush();
        return new JsonResponse(['message' => 'Property supprimée.']);
    }
}
