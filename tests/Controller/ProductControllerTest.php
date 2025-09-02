<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProductControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;
    private ProductRepository $productRepository;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        
        // Get services from the container
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->productRepository = $container->get(ProductRepository::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // Create database schema for testing
        $this->setupDatabase();
        
        // Clean up any existing test data
        $this->cleanUpTestData();
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
        // Clean up products
        $products = $this->productRepository->findAll();
        foreach ($products as $product) {
            $this->entityManager->remove($product);
        }

        // Clean up users
        $users = $this->userRepository->findAll();
        foreach ($users as $user) {
            $this->entityManager->remove($user);
        }

        $this->entityManager->flush();
    }

    private function createTestProduct(string $name = 'Test Product', string $price = '99.99', int $stock = 10): Product
    {
        $product = new Product();
        $product->setName($name)
                ->setDescription('Test product description')
                ->setPrice($price)
                ->setStock($stock);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    private function createTestUser(string $email = 'test@example.com', array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail($email)
             ->setUsername('testuser')
             ->setFirstName('Test')
             ->setLastName('User')
             ->setRoles($roles)
             ->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createAdminUser(): User
    {
        return $this->createTestUser('admin@example.com', ['ROLE_ADMIN']);
    }

    private function loginUser(User $user): void
    {
        $this->client->loginUser($user);
    }

    public function testProductIndexPageAccessible(): void
    {
        $this->client->request('GET', '/products/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1'); // Assuming there's an h1 tag
    }

    public function testProductIndexDisplaysProducts(): void
    {
        // Create test products
        $product1 = $this->createTestProduct('Product 1', '19.99', 5);
        $product2 = $this->createTestProduct('Product 2', '29.99', 10);

        $this->client->request('GET', '/products/');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Product 1');
        $this->assertSelectorTextContains('body', 'Product 2');
    }

    public function testProductShowPageAccessible(): void
    {
        $product = $this->createTestProduct();
        
        $this->client->request('GET', '/products/' . $product->getId());
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Test Product');
    }

    public function testProductNewPageRequiresAdminRole(): void
    {
        // Test without authentication
        $this->client->request('GET', '/products/new');
        $this->assertResponseRedirects('/login'); // Assuming login redirect

        // Test with regular user
        $user = $this->createTestUser();
        $this->loginUser($user);
        
        $this->client->request('GET', '/products/new');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testProductNewPageAccessibleForAdmin(): void
    {
        $admin = $this->createAdminUser();
        $this->loginUser($admin);
        
        $this->client->request('GET', '/products/new');
        $this->assertResponseIsSuccessful();
    }

    public function testProductCreation(): void
    {
        $admin = $this->createAdminUser();
        $this->loginUser($admin);
        
        $this->client->request('POST', '/products/new', [
            'name' => 'New Test Product',
            'description' => 'A new test product',
            'price' => '49.99',
            'stock' => '15'
        ]);
        
        $this->assertResponseRedirects('/products/');
        
        // Verify product was created
        $product = $this->productRepository->findOneBy(['name' => 'New Test Product']);
        $this->assertNotNull($product);
        $this->assertEquals('New Test Product', $product->getName());
        $this->assertEquals('A new test product', $product->getDescription());
        $this->assertEquals('49.99', $product->getPrice());
        $this->assertEquals(15, $product->getStock());
    }

    public function testProductEditPageRequiresAdminRole(): void
    {
        $product = $this->createTestProduct();
        
        // Test without authentication
        $this->client->request('GET', '/products/' . $product->getId() . '/edit');
        $this->assertResponseRedirects('/login');

        // Test with regular user
        $user = $this->createTestUser();
        $this->loginUser($user);
        
        $this->client->request('GET', '/products/' . $product->getId() . '/edit');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testProductEditPageAccessibleForAdmin(): void
    {
        $product = $this->createTestProduct();
        $admin = $this->createAdminUser();
        $this->loginUser($admin);
        
        $this->client->request('GET', '/products/' . $product->getId() . '/edit');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Test Product');
    }

    public function testProductUpdate(): void
    {
        $product = $this->createTestProduct();
        $admin = $this->createAdminUser();
        $this->loginUser($admin);
        
        $this->client->request('POST', '/products/' . $product->getId() . '/edit', [
            'name' => 'Updated Product Name',
            'description' => 'Updated description',
            'price' => '79.99',
            'stock' => '20'
        ]);
        
        $this->assertResponseRedirects('/products/');
        
        // Refresh entity from database
        $this->entityManager->refresh($product);
        
        $this->assertEquals('Updated Product Name', $product->getName());
        $this->assertEquals('Updated description', $product->getDescription());
        $this->assertEquals('79.99', $product->getPrice());
        $this->assertEquals(20, $product->getStock());
    }

    public function testProductDeleteRequiresAdminRole(): void
    {
        $product = $this->createTestProduct();
        
        // Test without authentication
        $this->client->request('POST', '/products/' . $product->getId() . '/delete');
        $this->assertResponseRedirects('/login');

        // Test with regular user
        $user = $this->createTestUser();
        $this->loginUser($user);
        
        $this->client->request('POST', '/products/' . $product->getId() . '/delete');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testProductDeletion(): void
    {
        $product = $this->createTestProduct();
        $productId = $product->getId();
        $admin = $this->createAdminUser();
        $this->loginUser($admin);
        
        $this->client->request('POST', '/products/' . $productId . '/delete');
        
        $this->assertResponseRedirects('/products/');
        
        // Verify product was deleted
        $deletedProduct = $this->productRepository->find($productId);
        $this->assertNull($deletedProduct);
    }

    public function testProductNotFound(): void
    {
        $this->client->request('GET', '/products/99999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testProductIndexWithNoProducts(): void
    {
        $this->client->request('GET', '/products/');
        $this->assertResponseIsSuccessful();
        // Test that page loads even with no products
    }

    public function testFlashMessagesOnProductOperations(): void
    {
        $admin = $this->createAdminUser();
        $this->loginUser($admin);
        
        // Test creation flash message
        $this->client->request('POST', '/products/new', [
            'name' => 'Flash Test Product',
            'description' => 'Testing flash messages',
            'price' => '25.99',
            'stock' => '5'
        ]);
        
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert'); // Assuming flash messages use .alert class
    }

    public function testProductFormValidation(): void
    {
        $admin = $this->createAdminUser();
        $this->loginUser($admin);
        
        // Test with empty required fields
        $this->client->request('POST', '/products/new', [
            'name' => '',
            'description' => '',
            'price' => '',
            'stock' => ''
        ]);
        
        // Should not redirect if validation fails
        $this->assertResponseIsSuccessful();
        
        // Verify no product was created
        $products = $this->productRepository->findAll();
        $this->assertCount(0, $products);
    }
}
