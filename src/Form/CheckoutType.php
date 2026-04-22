<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('deliveryAddress', TextareaType::class, [
                'label' => 'Delivery Address',
                'attr' => [
                    'class' => 'w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-200',
                    'rows' => '3',
                    'placeholder' => 'Enter your delivery address'
                ],
                'required' => true,
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Payment Method',
                'choices' => [
                    'Cash on Delivery' => 'cash_on_delivery',
                    'PayPal' => 'paypal',
                ],
                'attr' => [
                    'class' => 'w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-200',
                ],
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'checkout_token',
        ]);
    }
}
