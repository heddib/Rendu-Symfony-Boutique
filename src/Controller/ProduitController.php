<?php

namespace App\Controller;

use App\Entity\Panier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Produit;
use App\Form\PanierType;
use App\Form\ProduitType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *  @Route("/{_locale}")
 */
class ProduitController extends AbstractController
{
    /**
     * @Route("/produits", name="produits")
     */
    public function index(Request $request, TranslatorInterface $translator)
    {
        // Récupère Doctrine (service de gestion de BDD)
        $pdo = $this->getDoctrine()->getManager();

        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);

        // Analyse la requête HTTP
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Le formulaire a été envoyé, on le sauvegarde
            // On récupère le fichier du formulaire
            $fichier = $form->get('photo')->getData();
            // Si un fichier a été uploadé
            if ($fichier) {
                $nomFichier = uniqid() . '.' . $fichier->guessExtension();

                try {
                    $fichier->move(
                        $this->getParameter('upload_dir'),
                        $nomFichier
                    );
                } catch (FileException $e) {
                    $this->addFlash("danger", $translator->trans("flash.error.upload_photo"));
                    return $this->redirectToRoute('home');
                }

                $produit->setPhoto($nomFichier);
            }

            $pdo->persist($produit); // prepare
            $pdo->flush();           // execute

            $this->addFlash("success", $translator->trans("flash.success.added_product"));
        }

        // Récupère tous les produits
        $produits = $pdo->getRepository(Produit::class)->findAll();

        return $this->render('produit/index.html.twig', [
            'produits' => $produits,
            'new_product_form' => $form->createView()
        ]);
    }

    /**
     * @Route("/produit/{id}", name="view_product")
     */
    public function produit(Produit $produit = null, Request $request, TranslatorInterface $translator)
    {
        if ($produit != null) {
            $panier = new Panier($produit);
            $form = $this->createForm(PanierType::class, $panier);

            // Analyse la requête HTTP
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                // Le formulaire a été envoyé, on le sauvegarde
                // Check des conditions pour ajouter au panier
                if ($panier->getQte() > 0 && $panier->getQte() <= $produit->getQte()) {
                    $pdo = $this->getDoctrine()->getManager();
                    $pdo->persist($panier); // prepare
                    $pdo->flush();          // execute

                    $this->addFlash("success", $translator->trans("flash.success.added_product_to_cart"));
                    return $this->redirectToRoute('panier');
                } else {
                    $this->addFlash("danger", $translator->trans("flash.error.quantity"));
                    return $this->redirectToRoute('produits');
                }
            }

            return $this->render('produit/produit.html.twig', [
                'produit' => $produit,
                'form_add_panier' => $form->createView()
            ]);
        } else {
            // Produit n'existe pas, on redirige l'internaute
            $this->addFlash("danger", $translator->trans("flash.error.product_not_found"));
            return $this->redirectToRoute('produits');
        }
    }

    /**
     * @Route("/produit/delete/{id}", name="delete_product")
     */
    public function delete(Produit $produit = null, TranslatorInterface $translator)
    {
        if ($produit != null) {
            $pdo = $this->getDoctrine()->getManager();
            $pdo->remove($produit);
            $pdo->flush();

            if($produit->getPhoto() != null) {
                // Supprimer si y a une photo
                unlink($this->getParameter('upload_dir') . $produit->getPhoto());
            }

            $this->addFlash("success", $translator->trans("flash.success.deleted_product"));
        } else {
            $this->addFlash("danger", $translator->trans("flash.error.product_not_found"));
        }
        return $this->redirectToRoute('produits');
    }
}
