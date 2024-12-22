<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @Route("/users")
 */
class UserController extends AbstractController
{
    /**
     * GET /users
     * Nécessite un rôle Admin (cf. security.yaml)
     */
    #[Route('/users', name: 'users_list', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès interdit'], 403);
        }

        $users = $em->getRepository(User::class)->findAll();

        // Conversion simple en tableau
        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
            ];
        }

        return new JsonResponse($data, 200);
    }

    /**
     * GET /users/{id} – Accès si admin ou si c’est le propre utilisateur
     */
    #[Route('/users/{id<\d+>}', name: 'user_get', methods: ['GET'])]
    public function getUserById(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User non trouvé'], 404);
        }
        
        // Vérifier les droits : admin ou le user lui-même
        if (!$this->isGranted('ROLE_ADMIN') && $this->getUser()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Accès interdit'], 403);
        }
        

        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
        ];

        return new JsonResponse($data, 200);
    }

    /**
     * POST /users/{id}
     * Modifie les informations d’un utilisateur (admin ou user lui-même)
     */
    #[Route('/users/{id<\d+>}', name: 'user_update', methods: ['POST'])]
    public function updateUser(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User non trouvé'], 404);
        }

        // Vérifier les droits
        if (!$this->isGranted('ROLE_ADMIN') && $this->getUser()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Accès interdit'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        if ($email) {
            $user->setEmail($email);
        }
        
        $plainPassword = $data['password'] ?? null;
        if ($plainPassword) {
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
        }

        $firstName = $data['firstName'] ?? null;
        $lastName = $data['lastName'] ?? null;
        if ($firstName !== null) {
            $user->setFirstName($firstName);
        }
        if ($lastName !== null) {
            $user->setLastName($lastName);
        }

        $em->flush();

        return new JsonResponse(['message' => 'Utilisateur mis à jour.'], 200);
    }

    /**
     * DELETE /users/{id}
     * Supprime un utilisateur
     */
    #[Route('/users/{id<\d+>}', name: 'user_delete', methods: ['DELETE'])]
    public function deleteUser(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User non trouvé'], 404);
        }

        // Vérifier les droits
        if (!$this->isGranted('ROLE_ADMIN') && $this->getUser()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Accès interdit'], 403);
        }

        $em->remove($user);
        $em->flush();

        return new JsonResponse(['message' => 'Utilisateur supprimé.'], 200);
    }
}
