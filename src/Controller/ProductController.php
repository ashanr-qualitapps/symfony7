<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\CategoryRepository;
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
    #[Route('/', name: 'product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'product_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response
    {
        $product = new Product();

        // Check if there are any categories
        $defaultCategory = $categoryRepository->findOneBy([], ['id' => 'ASC']);
        if (!$defaultCategory) {
            $this->addFlash('error', 'No categories available. Please create a category first.');
            return $this->redirectToRoute('product_index');
        }
        $product->setCategory($defaultCategory);

        // Use createForm method from AbstractController
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Double check that category is set
            if (!$product->getCategory()) {
                $this->addFlash('error', 'Please select a category for this product.');
                return $this->render('product/new.html.twig', [
                    'product' => $product,
                    'form' => $form->createView(),
                ]);
            }
            
            try {
                $entityManager->persist($product);
                $entityManager->flush();
                $this->addFlash('success', 'Product created successfully.');
                return $this->redirectToRoute('product_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating product: ' . $e->getMessage());
            }
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'product_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($product->getName() === null) {
            $this->addFlash('error', 'This product has no name and cannot be edited.');
            return $this->redirectToRoute('product_index');
        }
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/products',
                        $newFilename
                    );
                    $product->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Image upload failed: ' . $e->getMessage());
                }
            }
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Product updated successfully.');
                return $this->redirectToRoute('product_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating product: ' . $e->getMessage());
            }
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'product_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('success', 'Product deleted successfully.');
        }

        return $this->redirectToRoute('product_index');
    }
}
