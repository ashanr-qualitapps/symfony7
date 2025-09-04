<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Product Name',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a product name']),
                ],
            ])
            ->add('description', null, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('price', null, [
                'label' => 'Price',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a price']),
                ],
            ])
            ->add('stock', null, [
                'label' => 'Stock',
                'required' => false,
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'required' => true,
                'placeholder' => 'Select a category',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a category',
                    ]),
                ],
            ])
            ->add('subCategory', EntityType::class, [
                'class' => \App\Entity\SubCategory::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Select a subcategory (optional)',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}