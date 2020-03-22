<?php

namespace App\Controller;

use App\Entity\Panier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *  @Route("/{_locale}")
 */
class PanierController extends AbstractController
{
    /**
     * @Route("/", name="panier")
     */
    public function index()
    {
        $pdo = $this->getDoctrine()->getManager();
        $panier = $pdo->getRepository(Panier::class)->findAll();

        return $this->render('panier/index.html.twig', [
            'panier' => $panier
        ]);
    }

    /**
     * @Route("/panier/delete/{id}", name="delete_from_panier")
     */
    public function delete(Panier $panier = null, TranslatorInterface $translator)
    {
        if ($panier != null) {
            $pdo = $this->getDoctrine()->getManager();
            $pdo->remove($panier);
            $pdo->flush();
            $this->addFlash("success", $translator->trans("flash.success.product_deleted_from_cart"));
        } else {
            $this->addFlash("danger", $translator->trans("flash.error.product_not_found"));
        }
        return $this->redirectToRoute('panier');
    }

    /**
     * @Route("/panier/validate/", name="validate_panier")
     */
    public function validate(TranslatorInterface $translator)
    {
        $pdo = $this->getDoctrine()->getManager();
        $paniers = $pdo->getRepository(Panier::class)->findAll();

        if ($paniers != null) {
            foreach ($paniers as $panier) {
                $panier->setEtat(true);
                $pdo->persist($panier);
                $pdo->flush();
                $this->addFlash("success", $translator->trans("flash.success.validated_cart"));
            }
        } else {
            $this->addFlash("danger", $translator->trans("flash.error.validated_cart_no_products"));
        }
        return $this->redirectToRoute('panier');
    }
}
