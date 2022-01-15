<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Product;
use App\Service\LineItems;
use Stripe\Checkout\Session;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CartController extends AbstractController
{
    // ---------------PANIER-----------------------
    
    // 1- creation page panier
    #[Route('/cart', name: 'cart')]
    public function index(SessionInterface $session, ProductRepository $productRepository): Response
    {
        
        // on récupere la session du panier
        $panier = $session->get("panier", []);

        // On initialise les données du panier (dataPanier) et le total
        $dataPanier = [];
        $total = 0;

        //  on determine chaque panier selon $id et la quantite selon $id
        foreach($panier as $id => $quantite)
        {
            // les produits
            $product = $productRepository->find($id);

            // les données du panier
            $dataPanier[] = 
            [
                "produit" => $product,
                "quantite" => $quantite
            ];

            // le total
            $total += $product->getPrice() * $quantite;
        }

        // la page du panier
        return $this->render('cart/index.html.twig', compact("dataPanier", "total"));
    }

    // 2 - Bouton plus
    #[Route('/add/{id}', name: 'add')]
    public function add(Product $product, SessionInterface $session)
    {
        // On récupère la session du panier actuel
        $panier = $session->get("panier", []);

        // On récupère l'id
        $id = $product->getId();

        // si le panier n'est pas vide selon id
        if(!empty($panier[$id]))
        {
            $panier[$id]++;
        }else
        {
            $panier[$id] = 1;
        }

        // On sauvegarde la session
        $session->set("panier", $panier);

        // redirection vers la page panier
        return $this->redirectToRoute("cart");
    }

    // 3 - Bouton moins
    #[Route('/remove/{id}', name: 'remove')]
    public function remove(Product $product, SessionInterface $session)
    {
        // On récupère la session du panier actuel
        $panier = $session->get("panier", []);

        // on recupere l'id
        $id = $product->getId();

        // si le panier n'est pas vide
        if(!empty($panier[$id]))
        {
            // si le panier > 1
            if($panier[$id] > 1)
            {
                $panier[$id]--;
            }
            // sinon on supprimer le panier
            else
            {
                unset($panier[$id]);
            }
        }

        // On sauvegarde la session
        $session->set("panier", $panier);

        // redirection vers la page panier
        return $this->redirectToRoute("cart");
    }

    // 4 - Supprimer un article dans le panier
    #[Route('/delete/{id}', name: 'delete')]
    public function delete(Product $product, SessionInterface $session)
    {
        // On récupère la session du panier actuel
        $panier = $session->get("panier", []);

        // on recupere l'id
        $id = $product->getId();

        // si le panier n'est pas vide
        if(!empty($panier[$id]))
        {
            unset($panier[$id]);
        }

        // On sauvegarde la session
        $session->set("panier", $panier);

        // redirection vers la page panier
        return $this->redirectToRoute("cart");
    }

    // 5 - Vider le panier
    #[Route('/vider-panier', name: 'vider')]
    public function deleteAll(SessionInterface $session)
    {
        // supprimer tous les articles dans le panier
        $session->remove("panier");

        // redirection vers la page panier
        return $this->redirectToRoute("cart");
    }

    // -------------------STRIPE--------------------

    #[Route('/checkout', name: 'checkout')]
    public function checkout( SessionInterface $session  ,ProductRepository $productRepository): Response
    {
		$panier = $session->get("panier", []);

        $lineItems = [];
        foreach($panier as $id => $quantite)
		{	
            $product = $productRepository->find($id);            
            $lineItems[] = 
			[
				'price_data' => 
				[
					'currency' => 'eur',
					'unit_amount' => $product->getPrice(),
					'product_data' => 
					[
						'name' => $product->getName(),
					],
				],
				'quantity' => $quantite,
            ];
        }
            
        Stripe::setApiKey('sk_test_51KG0R8B70WhTmRhmtnjAaylND1ngWwYozes0xzcDaTswo3LHbbcFzEqrzNlEiNA8uT15muemkhKGENo1SxUgIMsy00WTJxx7p8');
		
		$session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $this->generateUrl('success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
 
        return $this->redirect($session->url, 303);
    }

    #[Route('/success', name: 'success')]
    public function successUrl(): Response
    {
        return $this->render('cart/success.html.twig', []);
    }


    #[Route('/cancel', name: 'cancel')]
    public function cancelUrl(): Response
    {
        return $this->render('cart/cancel.html.twig', []);
    }
}
