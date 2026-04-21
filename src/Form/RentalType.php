<?php

namespace App\Form;

use App\Entity\Inventory;
use App\Entity\Rental;
use App\Repository\InventoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RentalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isAdmin = $options['is_admin'];
        $isEdit = $options['is_edit'];

        $builder
            ->add('inventory', EntityType::class, [
                'class' => Inventory::class,
                'choice_label' => static fn (Inventory $inventory): string => sprintf(
                    '%s (%s | %s)',
                    $inventory->getItemName(),
                    $inventory->getItemType(),
                    $inventory->getRentalStatus()
                ),
                'query_builder' => static fn (InventoryRepository $repository) => $options['lock_inventory'] && $options['inventory_id'] !== null
                    ? $repository->createSelectedInventoryQueryBuilder($options['inventory_id'])
                    : $repository->createRentableForRentalFormQueryBuilder(
                        $options['inventory_id'],
                        $options['current_rental_id']
                    ),
                'placeholder' => $options['lock_inventory'] ? null : 'Choose inventory item',
                'label' => 'Inventory item',
                'disabled' => $options['lock_inventory'],
                'help' => $options['lock_inventory'] ? 'This rental stays linked to the selected inventory item.' : null,
            ])
            ->add('renterName', TextType::class, [
                'label' => 'Renter name',
                'attr' => ['placeholder' => 'Full renter name'],
                'disabled' => $options['lock_renter'],
            ])
            ->add('renterContact', TextType::class, [
                'label' => 'Renter contact',
                'attr' => ['placeholder' => 'Phone or email'],
                'disabled' => $options['lock_renter'],
            ])
            ->add('renterAddress', TextareaType::class, [
                'label' => 'Renter address',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Start date',
                'widget' => 'single_text',
                'input' => 'datetime',
            ])
            ->add('endDate', DateType::class, [
                'label' => 'End date',
                'widget' => 'single_text',
                'input' => 'datetime',
            ])
            ->add('dailyRate', NumberType::class, [
                'label' => 'Daily rate',
                'html5' => true,
                'attr' => ['step' => '0.01', 'min' => '0'],
                'disabled' => !$isAdmin,
                'help' => $isAdmin ? null : 'The rental price comes from the selected inventory item.',
            ])
            ->add('requiresDelivery', CheckboxType::class, [
                'label' => 'Requires delivery',
                'required' => false,
            ])
            ->add('deliveryAddress', TextareaType::class, [
                'label' => 'Delivery address',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('paymentMethod', TextType::class, [
                'label' => 'Payment method',
                'required' => false,
                'attr' => ['placeholder' => 'Cash, transfer, card...'],
            ]);

        if ($isAdmin) {
            $builder
                ->add('deliveryFee', NumberType::class, [
                    'label' => 'Delivery fee',
                    'required' => false,
                    'html5' => true,
                    'attr' => ['step' => '0.01', 'min' => '0'],
                ]);
        }

        if ($isAdmin && $isEdit) {
            $builder
                ->add('paymentStatus', ChoiceType::class, [
                    'label' => 'Payment status',
                    'choices' => array_combine(Rental::PAYMENT_STATUSES, Rental::PAYMENT_STATUSES),
                ])
                ->add('actualReturnDate', DateType::class, [
                    'label' => 'Actual return date',
                    'widget' => 'single_text',
                    'input' => 'datetime',
                    'required' => false,
                ])
                ->add('pickupCondition', TextType::class, [
                    'label' => 'Pickup condition',
                    'required' => false,
                ])
                ->add('returnCondition', TextType::class, [
                    'label' => 'Return condition',
                    'required' => false,
                ])
                ->add('pickupPhotos', TextareaType::class, [
                    'label' => 'Pickup photos notes',
                    'required' => false,
                    'attr' => ['rows' => 2],
                ])
                ->add('returnPhotos', TextareaType::class, [
                    'label' => 'Return photos notes',
                    'required' => false,
                    'attr' => ['rows' => 2],
                ])
                ->add('damageNotes', TextareaType::class, [
                    'label' => 'Damage notes',
                    'required' => false,
                    'attr' => ['rows' => 3],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rental::class,
            'inventory_id' => null,
            'current_rental_id' => null,
            'lock_inventory' => false,
            'lock_renter' => false,
            'is_admin' => false,
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('inventory_id', ['null', 'int']);
        $resolver->setAllowedTypes('current_rental_id', ['null', 'int']);
        $resolver->setAllowedTypes('lock_inventory', 'bool');
        $resolver->setAllowedTypes('lock_renter', 'bool');
        $resolver->setAllowedTypes('is_admin', 'bool');
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
