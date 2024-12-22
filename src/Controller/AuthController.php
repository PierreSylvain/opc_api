<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AuthController extends AbstractController
{
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $plainPassword = $data['password'] ?? null;
        $firstName = $data['firstName'] ?? null;
        $lastName = $data['lastName'] ?? null;

        if (!$email || !$plainPassword) {
            return new JsonResponse(['error' => 'Email et mot de passe requis.'], 400);
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'Cet utilisateur existe déjà.'], 400);
        }

        // Créer et sauvegarder
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);

        $em->persist($user);
        $em->flush();

        // Option 1 : on renvoie juste un message
        // return new JsonResponse(['message' => 'Utilisateur créé avec succès'], 201);

        // Option 2 : générer un token directement
        // Vous pouvez utiliser LexikJWTAuthenticationBundle pour générer le token
        // via un service (pas détaillé ici). Sinon, l'utilisateur fait un /login.

        return new JsonResponse(['message' => 'Utilisateur créé avec succès.'], 201);
    }

    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        // 1) Récupérer le JSON envoyé par le client
        // {
        //   "email": "demo@example.com",
        //   "password": "demo_password"
        // }
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse([
                'error' => 'Email and password are required.'
            ], 400);
        }

        // 2) Vérifier si l’utilisateur existe
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return new JsonResponse([
                'error' => 'User not found.'
            ], 404);
        }

        // 3) Vérifier le mot de passe
        $isValid = $passwordHasher->isPasswordValid($user, $password);
        if (!$isValid) {
            // Mauvais mot de passe
            return new JsonResponse([
                'error' => 'Invalid credentials.'
            ], 401);
        }

        // 4) Générer le token JWT
        try {
            $token = $jwtManager->create($user);
        } catch (\Exception $e) {
            throw new AuthenticationException('Token generation failed.');
        }

        // 5) Retourner le token
        return new JsonResponse([
            'token' => $token
        ]);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // En pratique, avec le JWT, il n'y a pas de "logout" direct :
        // - soit on supprime le token côté client,
        // - soit on met en place un système de "blacklist" des tokens côté serveur.
        // Ici, on peut juste renvoyer un message.

        return new JsonResponse(['message' => 'Token invalidé/supprimé côté client.'], 200);
    }
}
