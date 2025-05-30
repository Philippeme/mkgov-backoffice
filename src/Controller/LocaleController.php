<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de gestion des langues et localisation
 * 
 * Ce contrôleur gère le changement de langue dans l'interface
 * d'administration du système MK Gov, supportant l'anglais
 * comme langue principale et le français en alternative.
 */
class LocaleController extends AbstractController
{
    /**
     * Change la langue de l'interface utilisateur
     */
    #[Route('/admin/locale/{locale}', name: 'admin_locale', requirements: ['locale' => 'en|fr'])]
    public function changeLocale(string $locale, Request $request): Response
    {
        // Stocker la locale choisie en session
        $request->getSession()->set('_locale', $locale);
        
        // Rediriger vers la page de référence ou le dashboard
        $referer = $request->headers->get('referer');
        
        if ($referer && $this->isValidReferer($referer)) {
            return $this->redirect($referer);
        }
        
        return $this->redirectToRoute('admin_dashboard_index');
    }

    /**
     * Vérifie si l'URL de référence est valide et sécurisée
     */
    private function isValidReferer(string $referer): bool
    {
        $allowedHosts = [
            $this->getParameter('app.admin_host') ?? 'localhost',
            'admin.mkgov.cm',
            'localhost'
        ];
        
        $parsedUrl = parse_url($referer);
        
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return false;
        }
        
        return in_array($parsedUrl['host'], $allowedHosts);
    }
}