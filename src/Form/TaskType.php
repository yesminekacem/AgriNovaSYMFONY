<?php

namespace App\Form;

use App\Entity\Task;
use App\Entity\Crop;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
       $builder
    ->add('crop', EntityType::class, [
        'class'        => Crop::class,
        'choice_label' => 'name',
        'label'        => 'Crop',
    ])

    ->add('task_name', TextType::class, [
        'label' => 'Task Name',
    ])

    ->add('description', TextareaType::class, [
        'label' => 'Description',
    ])

    ->add('task_type', ChoiceType::class, [
        'choices' => [
            'Planting'      => 'planting',
            'Irrigation'    => 'irrigation',
            'Fertilization' => 'fertilization',
            'Harvesting'    => 'harvesting',
        ],
        'label' => 'Task Type',
    ])

    ->add('scheduled_date', DateType::class, [
        'widget' => 'single_text',
        'label'  => 'Scheduled Date',
    ])

    ->add('completed_date', DateType::class, [
        'widget'   => 'single_text',
        'required' => false,
        'label'    => 'Completed Date',
    ])

    ->add('status', ChoiceType::class, [
        'choices' => [
            'Pending'     => 'pending',
            'In Progress' => 'in_progress',
            'Completed'   => 'completed',
        ],
        'label' => 'Status',
    ])

    ->add('assigned_to', TextType::class, [
        'label' => 'Assigned To',
    ])

    ->add('cost', NumberType::class, [
        'label' => 'Cost',
    ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}