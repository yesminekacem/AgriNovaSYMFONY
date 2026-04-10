<?php

namespace App\Form;

use App\Entity\Inventory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InventoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('itemName', TextType::class, [
                'label' => 'Item name',
                'attr' => ['placeholder' => 'Ex: Seed Drill 3m'],
            ])
            ->add('itemType', ChoiceType::class, [
                'label' => 'Item type',
                'choices' => array_combine(Inventory::ITEM_TYPES, Inventory::ITEM_TYPES),
                'placeholder' => 'Choose a type',
            ])
            ->add('conditionStatus', ChoiceType::class, [
                'label' => 'Condition',
                'choices' => array_combine(Inventory::CONDITION_STATUSES, Inventory::CONDITION_STATUSES),
                'placeholder' => 'Choose a condition',
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',
                'attr' => ['min' => 1],
            ])
            ->add('unitPrice', NumberType::class, [
                'label' => 'Unit price',
                'html5' => true,
                'attr' => ['step' => '0.01', 'min' => '0'],
            ])
            ->add('purchaseDate', DateType::class, [
                'label' => 'Purchase date',
                'widget' => 'single_text',
                'input' => 'datetime',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 4, 'placeholder' => 'Short description of the item'],
            ])
            ->add('ownerName', TextType::class, [
                'label' => 'Owner name',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: Ahmed Ben Salem'],
            ])
            ->add('ownerContact', TextType::class, [
                'label' => 'Owner contact',
                'required' => false,
                'attr' => ['placeholder' => 'Phone or email'],
            ])
            ->add('isRentable', CheckboxType::class, [
                'label' => 'Available for rental',
                'required' => false,
            ])
            ->add('rentalPricePerDay', NumberType::class, [
                'label' => 'Rental price / day',
                'required' => false,
                'html5' => true,
                'attr' => ['step' => '0.01', 'min' => '0'],
            ])
            ->add('rentalStatus', ChoiceType::class, [
                'label' => 'Rental status',
                'choices' => array_combine(Inventory::RENTAL_STATUSES, Inventory::RENTAL_STATUSES),
                'placeholder' => 'Choose a rental status',
            ])
            ->add('lastMaintenanceDate', DateType::class, [
                'label' => 'Last maintenance',
                'widget' => 'single_text',
                'input' => 'datetime',
                'required' => false,
            ])
            ->add('nextMaintenanceDate', DateType::class, [
                'label' => 'Next maintenance',
                'widget' => 'single_text',
                'input' => 'datetime',
                'required' => false,
            ])
            ->add('totalUsageHours', IntegerType::class, [
                'label' => 'Total usage hours',
                'required' => false,
                'attr' => ['min' => 0],
            ])
            ->add('imagePath', TextType::class, [
                'label' => 'Image path',
                'required' => false,
                'attr' => ['placeholder' => 'Optional local or web image path'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Inventory::class,
        ]);
    }
}
