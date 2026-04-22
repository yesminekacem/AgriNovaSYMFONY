<?php

namespace App\Form;

use App\Entity\ProductListing;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('productName', TextType::class, [
                'label' => 'Product Name',
                'attr' => ['placeholder' => 'Enter product name']
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'attr' => ['placeholder' => 'Enter product description']
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',
                'attr' => ['placeholder' => 'Enter quantity']
            ])
            ->add('pricePerUnit', NumberType::class, [
                'label' => 'Price Per Kg (TND)',
                'attr' => ['placeholder' => 'Enter price'],
                'scale' => 2
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'choices' => [
                    'Fruits' => 'Fruits',
                    'Grains' => 'Grains',
                    'Vegetables' => 'Vegetables'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Available' => 'available',
                    'Sold Out' => 'sold-out'
                ],
                'required' => false
            ])
            ->add('picture', FileType::class, [
                'label' => 'Product Image',
                'mapped' => false,
                'required' => false,
                'attr' => ['accept' => 'image/*'],
                'help' => 'Choose an image file (JPG, PNG, GIF, etc.)'
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save Product'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductListing::class,
        ]);
    }
}
