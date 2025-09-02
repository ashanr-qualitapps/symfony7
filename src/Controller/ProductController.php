<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/products')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'product_index')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findBy([], ['createdAt' => 'DESC']);
        return $this->render('product/index.html.twig', [
            'products' => $products
        ]);
    }

    #[Route('/new', name: 'product_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $product = new Product();
            $product->setName($request->request->get('name'));
            $product->setDescription($request->request->get('description'));
            $product->setPrice($request->request->get('price'));
            $product->setStock((int)$request->request->get('stock'));
            $em->persist($product);
            $em->flush();
            $this->addFlash('success', 'Product created successfully!');
            return $this->redirectToRoute('product_index');
        }
        return $this->render('product/new.html.twig');
    }

    #[Route('/{id}', name: 'product_show')]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product
        ]);
    }

    #[Route('/{id}/edit', name: 'product_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $product->setName($request->request->get('name'));
            $product->setDescription($request->request->get('description'));
            $product->setPrice($request->request->get('price'));
            $product->setStock((int)$request->request->get('stock'));
            $product->setUpdatedAt(new \DateTime());
            $em->flush();
            $this->addFlash('success', 'Product updated successfully!');
            return $this->redirectToRoute('product_index');
        }
        return $this->render('product/edit.html.twig', [
            'product' => $product
        ]);
    }

    #[Route('/{id}/delete', name: 'product_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Product $product, EntityManagerInterface $em): Response
    {
        $em->remove($product);
        $em->flush();
        $this->addFlash('success', 'Product deleted successfully!');
        return $this->redirectToRoute('product_index');
    }
}
