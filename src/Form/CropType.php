<?php

namespace App\Form;

use App\Entity\Crop;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CropType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['placeholder' => 'e.g. Tomato, Wheat, Maize…'],
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Vegetable' => 'vegetable',
                    'Fruit'     => 'fruit',
                    'Cereal'    => 'cereal',
                    'Legume'    => 'legume',
                    'Other'     => 'other',
                ],
                'placeholder' => 'Select type',
            ])
            ->add('variety', TextType::class, [
                'attr' => ['placeholder' => 'e.g. Cherry, Heirloom…'],
            ])
            ->add('planting_date', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('expected_harvest_date', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('growth_stage', ChoiceType::class, [
                'choices' => [
                    'Germination' => 'germination',
                    'Seedling'    => 'seedling',
                    'Vegetative'  => 'vegetative',
                    'Flowering'   => 'flowering',
                    'Ripening'    => 'ripening',
                ],
                'placeholder' => 'Select stage',
            ])
            ->add('area_size', NumberType::class, [
                'scale' => 2,
                'attr'  => ['placeholder' => '0.00'],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active'    => 'active',
                    'Growing'   => 'growing',
                    'Harvested' => 'harvested',
                    'Failed'    => 'failed',
                    'Dormant'   => 'dormant',
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label'    => false,
                'mapped'   => false,  
                'required' => false,
                'attr'     => ['accept' => 'image/*'],
                'constraints' => [
                    new File([
                        'maxSize'   => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPG, PNG, WEBP)',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Crop::class,
        ]);
    }
}