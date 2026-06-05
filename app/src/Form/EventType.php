<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Player;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('type', ChoiceType::class, [
                'choices' => array_flip(Event::TypeLabels),
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('time', TextType::class)
            ->add('description', TextareaType::class)
            ->add('tournamentFormat', ChoiceType::class, [
                'choices' => array_combine(Event::TournamentFormats, Event::TournamentFormats),
                'required' => false,
                'placeholder' => 'Format du tournoi',
            ])
            ->add('captain', EntityType::class, [
                'class' => Player::class,
                'choice_label' => 'pseudo',
                'required' => false,
                'placeholder' => 'Choisir un capitaine',
            ])
            ->add('players', EntityType::class, [
                'class' => Player::class,
                'choice_label' => 'pseudo',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
            ])
            ->add('substitutes', EntityType::class, [
                'class' => Player::class,
                'choice_label' => 'pseudo',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'csrf_protection' => false,
        ]);
    }
}
