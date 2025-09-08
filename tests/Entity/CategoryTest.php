<?php

namespace App\Tests\Entity;

use App\Entity\Category;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    public function testCategoryProperties(): void
    {
        $category = new Category();
        $category->setName('Electronics');
        $category->setDescription('All electronic items');

        $this->assertEquals('Electronics', $category->getName());
        $this->assertEquals('All electronic items', $category->getDescription());
    }

    public function testAddAndRemoveSubCategory(): void
    {
        $category = new Category();
        $subCategory = $this->getMockBuilder('App\Entity\SubCategory')->disableOriginalConstructor()->getMock();
        $subCategory->expects($this->once())->method('setCategory')->with($category);
        $category->addSubCategory($subCategory);
        $this->assertTrue($category->getSubCategories()->contains($subCategory));

        $subCategory->expects($this->once())->method('getCategory')->willReturn($category);
        $subCategory->expects($this->once())->method('setCategory')->with(null);
        $category->removeSubCategory($subCategory);
        $this->assertFalse($category->getSubCategories()->contains($subCategory));
    }

    public function testAddAndRemoveProduct(): void
    {
        $category = new Category();
        $product = $this->getMockBuilder('App\Entity\Product')->disableOriginalConstructor()->getMock();
        $product->expects($this->once())->method('setCategory')->with($category);
        $category->addProduct($product);
        $this->assertTrue($category->getProducts()->contains($product));

        $product->expects($this->once())->method('getCategory')->willReturn($category);
        $product->expects($this->once())->method('setCategory')->with(null);
        $category->removeProduct($product);
        $this->assertFalse($category->getProducts()->contains($product));
    }
}
