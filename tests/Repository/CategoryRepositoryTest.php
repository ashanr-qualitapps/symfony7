<?php


namespace App\Tests\Repository;


use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CategoryRepositoryTest extends KernelTestCase
{
    private CategoryRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
    $kernel = self::bootKernel();
    $container = $kernel->getContainer();
    $this->entityManager = $container->get('doctrine')->getManager();
    $repo = $this->entityManager->getRepository(\App\Entity\Category::class);
    $this->repository = $repo instanceof CategoryRepository ? $repo : new CategoryRepository($this->entityManager->getConfiguration());
    }

    public function testFindAllReturnsCategories(): void
    {
        $categories = $this->repository->findAll();
        $this->assertIsArray($categories);
        foreach ($categories as $category) {
            $this->assertInstanceOf(Category::class, $category);
        }
    }

    public function testAddAndRemoveCategory(): void
    {
    $category = new Category();
    $category->setName('Test Category');
    $category->setDescription('Test Description');

    $this->entityManager->persist($category);
    $this->entityManager->flush();

    $found = $this->repository->find($category->getId());
    $this->assertNotNull($found);
    $this->assertEquals('Test Category', $found->getName());

    $this->entityManager->remove($category);
    $this->entityManager->flush();

    $deleted = $this->repository->find($category->getId());
    $this->assertNull($deleted);
    }
}
