<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\DiscordGuildRoleResolver;
use App\Service\PlayerSocialLinks;
use App\Service\ProfileDataEraser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class SecurityController extends AbstractController
{
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Symfony gère tout seul
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();

        return $this->render('page/accueil.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/connect/failure', name: 'connect_failure')]
    public function failure(): RedirectResponse
    {
        $this->addFlash('error', 'Connexion Discord échouée !');

        return $this->redirectToRoute('app_index');
    }

    #[Route('/ajax/profile', name: 'ajax_profile')]
    public function profileAjax(DiscordGuildRoleResolver $discordGuildRoleResolver): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        $discordRoles = $user instanceof User
            ? $discordGuildRoleResolver->resolveMemberRoleNames((string) $user->getDiscordId())
            : [];

        return $this->render('pages/profile/profile.html.twig', [
            'user' => $user,
            'discordRoles' => $discordRoles,
            'socialNetworks' => PlayerSocialLinks::AllowedNetworks,
            'socialLabels' => PlayerSocialLinks::Labels,
        ]);
    }

    #[Route('/ajax/profile/socials', name: 'ajax_profile_socials', methods: ['POST'])]
    public function updateProfileSocials(
        Request $request,
        EntityManagerInterface $entityManager,
        PlayerSocialLinks $playerSocialLinks,
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['message' => 'Non authentifie'], 401);
        }

        $player = $user->getPlayer();

        if (null === $player) {
            return $this->json(['message' => 'Aucun joueur lie a ce profil'], 400);
        }

        $data = $this->decodePayload($request);

        if (null === $data) {
            return $this->json(['message' => 'Donnees invalides'], 400);
        }

        try {
            $socials = $playerSocialLinks->normalize($data['socials'] ?? []);
        } catch (\RuntimeException $exception) {
            return $this->json(['message' => $exception->getMessage()], 400);
        }

        $player->setSocials($socials);
        $entityManager->flush();

        return $this->json([
            'message' => 'Liens enregistres',
            'socials' => $socials,
        ]);
    }

    #[Route('/ajax/profile/delete', name: 'ajax_profile_delete', methods: ['POST'])]
    public function deleteProfile(
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager,
        ProfileDataEraser $profileDataEraser,
        TokenStorageInterface $tokenStorage,
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['message' => 'Non authentifie'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $this->decodePayload($request) ?? [];
        $token = (string) ($data['_token'] ?? $request->headers->get('X-CSRF-Token', ''));

        if (!$csrfTokenManager->isTokenValid(new CsrfToken('delete_profile', $token))) {
            return $this->json(['message' => 'Jeton de securite invalide'], Response::HTTP_BAD_REQUEST);
        }

        $profileDataEraser->erase($user);
        $tokenStorage->setToken(null);

        if ($request->hasSession()) {
            $request->getSession()->invalidate();
        }

        $response = $this->json([
            'message' => 'Profil supprime',
            'redirect' => $this->generateUrl('app_index'),
        ]);
        $response->headers->clearCookie('REMEMBERME');

        return $response;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodePayload(Request $request): ?array
    {
        if ([] !== $request->request->all()) {
            return $request->request->all();
        }

        $data = json_decode($request->getContent(), true);

        return is_array($data) ? $data : null;
    }
}
