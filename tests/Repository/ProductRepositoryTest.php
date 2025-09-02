<?php

namespace App\Tests\Repository;

use App\Entity\Product;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        
        $this->entityManager = $container->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(Product::class);
        
        // Create database schema for testing
        $this->setupDatabase();
        
        // Clean up any existing test data
        $this->cleanUpTestData();
        
        // Restore exception handler to avoid risky test warnings
        restore_exception_handler();
    }

    protected function tearDown(): void
    {
        $this->cleanUpTestData();
        parent::tearDown();
    }

    private function setupDatabase(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        
        // Drop and create schema
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function cleanUpTestData(): void
    {
        $products = $this->repository->findAll();
        foreach ($products as $product) {
            $this->entityManager->remove($product);
        }
        $this->entityManager->flush();
    }

    private function createTestProduct(string $name, string $price = '99.99', int $stock = 10): Product
    {
        $product = new Product();
        $product->setName($name)
                ->setDescription('Test description for ' . $name)
                ->setPrice($price)
                ->setStock($stock);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    public function testRepositoryIsConfiguredCorrectly(): void
    {
        $this->assertInstanceOf(EntityRepository::class, $this->repository);
    }

    public function testFindAllProducts(): void
    {
        // Create test products
        $this->createTestProduct('Product 1');
        $this->createTestProduct('Product 2');
        $this->createTestProduct('Product 3');

        $products = $this->repository->findAll();
        
        $this->assertCount(3, $products);
        $this->assertContainsOnlyInstancesOf(Product::class, $products);
    }

    public function testFindProductById(): void
    {
        $product = $this->createTestProduct('Find By ID Test');
        $productId = $product->getId();

        $foundProduct = $this->repository->find($productId);
        
        $this->assertNotNull($foundProduct);
        $this->assertEquals('Find By ID Test', $foundProduct->getName());
        $this->assertEquals($productId, $foundProduct->getId());
    }

    public function testFindProductByNonExistentId(): void
    {
        $foundProduct = $this->repository->find(99999);
        $this->assertNull($foundProduct);
    }

    public function testFindOneByName(): void
    {
        $this->createTestProduct('Unique Product Name');
        $this->createTestProduct('Another Product');

        $product = $this->repository->findOneBy(['name' => 'Unique Product Name']);
        
        $this->assertNotNull($product);
        $this->assertEquals('Unique Product Name', $product->getName());
    }

    public function testFindByStock(): void
    {
        $this->createTestProduct('In Stock Product', '29.99', 15);
        $this->createTestProduct('Low Stock Product', '19.99', 2);
        $this->createTestProduct('Out of Stock Product', '39.99', 0);

        $inStockProducts = $this->repository->findBy(['stock' => 15]);
        $outOfStockProducts = $this->repository->findBy(['stock' => 0]);
        
        $this->assertCount(1, $inStockProducts);
        $this->assertCount(1, $outOfStockProducts);
        $this->assertEquals('In Stock Product', $inStockProducts[0]->getName());
        $this->assertEquals('Out of Stock Product', $outOfStockProducts[0]->getName());
    }

    public function testFindByOrderedByCreatedAt(): void
    {
        // Create products with explicit timestamps to ensure proper ordering
        $product1 = $this->createTestProduct('First Product');
        $product1->setCreatedAt(new \DateTime('2025-01-01 10:00:00'));
        
        $product2 = $this->createTestProduct('Second Product');
        $product2->setCreatedAt(new \DateTime('2025-01-01 11:00:00'));
        
        $product3 = $this->createTestProduct('Third Product');
        $product3->setCreatedAt(new \DateTime('2025-01-01 12:00:00'));
        
        $this->entityManager->flush();

        // Find all products ordered by creation date (DESC)
        $products = $this->repository->findBy([], ['createdAt' => 'DESC']);
        
        $this->assertCount(3, $products);
        $this->assertEquals('Third Product', $products[0]->getName());
        $this->assertEquals('Second Product', $products[1]->getName());
        $this->assertEquals('First Product', $products[2]->getName());
    }

    public function testFindByPriceRange(): void
    {
        $this->createTestProduct('Cheap Product', '9.99');
        $this->createTestProduct('Medium Product', '29.99');
        $this->createTestProduct('Expensive Product', '99.99');

        // Custom method would need to be added to ProductRepository
        // For now, test with findBy using exact price
        $mediumPriceProducts = $this->repository->findBy(['price' => '29.99']);
        
        $this->assertCount(1, $mediumPriceProducts);
        $this->assertEquals('Medium Product', $mediumPriceProducts[0]->getName());
    }

    public function testCountProducts(): void
    {
        $this->createTestProduct('Product 1');
        $this->createTestProduct('Product 2');
        $this->createTestProduct('Product 3');

        $count = $this->repository->count([]);
        
        $this->assertEquals(3, $count);
    }

    public function testCountProductsByStock(): void
    {
        $this->createTestProduct('Product 1', '19.99', 0);
        $this->createTestProduct('Product 2', '29.99', 5);
        $this->createTestProduct('Product 3', '39.99', 0);

        $outOfStockCount = $this->repository->count(['stock' => 0]);
        $inStockCount = $this->repository->count(['stock' => 5]);
        
        $this->assertEquals(2, $outOfStockCount);
        $this->assertEquals(1, $inStockCount);
    }

    public function testProductPersistence(): void
    {
        $product = new Product();
        $product->setName('Persistence Test')
                ->setDescription('Testing product persistence')
                ->setPrice('19.99')
                ->setStock(5);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        // Clear entity manager to ensure fresh fetch from database
        $this->entityManager->clear();

        $foundProduct = $this->repository->find($product->getId());
        
        $this->assertNotNull($foundProduct);
        $this->assertEquals('Persistence Test', $foundProduct->getName());
        $this->assertEquals('Testing product persistence', $foundProduct->getDescription());
        $this->assertEquals('19.99', $foundProduct->getPrice());
        $this->assertEquals(5, $foundProduct->getStock());
    }

    public function testProductUpdate(): void
    {
        $product = $this->createTestProduct('Original Name');
        $originalId = $product->getId();
        
        $product->setName('Updated Name');
        $product->setPrice('49.99');
        $this->entityManager->flush();

        // Clear and refetch
        $this->entityManager->clear();
        $updatedProduct = $this->repository->find($originalId);
        
        $this->assertEquals('Updated Name', $updatedProduct->getName());
        $this->assertEquals('49.99', $updatedProduct->getPrice());
    }

    public function testProductDeletion(): void
    {
        $product = $this->createTestProduct('To Be Deleted');
        $productId = $product->getId();
        
        $this->entityManager->remove($product);
        $this->entityManager->flush();

        $deletedProduct = $this->repository->find($productId);
        
        $this->assertNull($deletedProduct);
    }

    public function testFindProductsWithEmptyRepository(): void
    {
        $products = $this->repository->findAll();
        $count = $this->repository->count([]);
        
        $this->assertCount(0, $products);
        $this->assertEquals(0, $count);
    }

    public function testProductEntityTimestamps(): void
    {
        $product = $this->createTestProduct('Timestamp Test');
        
        $this->assertInstanceOf(\DateTimeInterface::class, $product->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $product->getUpdatedAt());
        
        // Test that timestamps are close to current time (within 1 minute)
        $now = new \DateTime();
        $createdDiff = $now->getTimestamp() - $product->getCreatedAt()->getTimestamp();
        $this->assertLessThan(60, $createdDiff);
    }
}
