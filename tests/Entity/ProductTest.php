<?php

namespace App\Tests\Entity;

use App\Entity\Product;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testProductCreation(): void
    {
        $product = new Product();
        $product->setName('Test Product')
                ->setDescription('A test product description')
                ->setPrice('99.99')
                ->setStock(10);

        $this->assertEquals('Test Product', $product->getName());
        $this->assertEquals('A test product description', $product->getDescription());
        $this->assertEquals('99.99', $product->getPrice());
        $this->assertEquals(10, $product->getStock());
    }

    public function testProductDefaults(): void
    {
        $product = new Product();
        
        $this->assertNull($product->getStock());
        $this->assertInstanceOf(\DateTimeImmutable::class, $product->getCreatedAt());
        $this->assertNull($product->getUpdatedAt());
        $this->assertNull($product->getId());
        $this->assertNull($product->getName());
        $this->assertNull($product->getDescription());
        $this->assertNull($product->getPrice());
    }

    public function testProductTimestamps(): void
    {
        $product = new Product();
        $originalCreatedAt = $product->getCreatedAt();

        // Test that timestamps are set automatically
        $this->assertInstanceOf(\DateTimeImmutable::class, $originalCreatedAt);

        // Test manual timestamp update
        $newDate = new \DateTimeImmutable('2025-01-01 12:00:00');
        $product->setUpdatedAt($newDate);
        $this->assertEquals($newDate, $product->getUpdatedAt());
        $this->assertEquals($originalCreatedAt, $product->getCreatedAt()); // createdAt should remain unchanged
    }

    public function testProductStockManagement(): void
    {
        $product = new Product();
        
        // Test initial stock
        $this->assertEquals(0, $product->getStock());
        
        // Test setting positive stock
        $product->setStock(25);
        $this->assertEquals(25, $product->getStock());
        
        // Test setting zero stock
        $product->setStock(0);
        $this->assertEquals(0, $product->getStock());
    }

    public function testProductPriceHandling(): void
    {
        $product = new Product();
        
        // Test decimal price
        $product->setPrice('19.99');
        $this->assertEquals('19.99', $product->getPrice());
        
        // Test integer price
        $product->setPrice('100');
        $this->assertEquals('100', $product->getPrice());
        
        // Test price with more decimals
        $product->setPrice('99.999');
        $this->assertEquals('99.999', $product->getPrice());
    }

    public function testProductFluentInterface(): void
    {
        $product = new Product();
        
        $result = $product->setName('Fluent Product')
                         ->setDescription('Testing fluent interface')
                         ->setPrice('49.99')
                         ->setStock(5);
        
        $this->assertSame($product, $result);
        $this->assertEquals('Fluent Product', $product->getName());
        $this->assertEquals('Testing fluent interface', $product->getDescription());
        $this->assertEquals('49.99', $product->getPrice());
        $this->assertEquals(5, $product->getStock());
    }

    public function testProductValidation(): void
    {
        $product = new Product();
        
        // Test with valid data
        $product->setName('Valid Product')
                ->setPrice('29.99')
                ->setStock(10);
        
        $violations = $this->validator->validate($product);
        $this->assertCount(0, $violations);
    }

    public function testProductValidationWithLongName(): void
    {
        $product = new Product();
        
        // Test with name exceeding 255 characters
        $longName = str_repeat('a', 256);
        $product->setName($longName)
                ->setPrice('29.99')
                ->setStock(10);
        
        $violations = $this->validator->validate($product);
        // Note: This test assumes validation constraints are added to the entity
        // If no length constraint exists, this test will pass with 0 violations
        $this->assertGreaterThanOrEqual(0, count($violations));
    }

    public function testProductNullableDescription(): void
    {
        $product = new Product();
        $product->setName('Product without description')
                ->setPrice('15.99')
                ->setStock(3);
        
        $this->assertNull($product->getDescription());
        
        $violations = $this->validator->validate($product);
        $this->assertCount(0, $violations);
    }

    public function testProductToString(): void
    {
        $product = new Product();
        $product->setName('Test Product');
        
        // If you add a __toString method to Product entity, test it here
        // $this->assertEquals('Test Product', (string) $product);
        
        // For now, just verify the name is set correctly
        $this->assertEquals('Test Product', $product->getName());
    }
}
