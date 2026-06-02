<?php

namespace App\Form;

use App\Entity\GameMatch;
use App\Entity\Roster;
use App\Entity\Team;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GameMatchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('playedAt', DateType::class, ['widget' => 'single_text'])
            ->add('teamA', EntityType::class, [
                'class' => Team::class,
                'choice_label' => 'name',
            ])
            ->add('teamB', EntityType::class, [
                'class' => Team::class,
                'choice_label' => 'name',
            ])
            ->add('roster', EntityType::class, [
                'class' => Roster::class,
                'choice_label' => 'name',
            ])
            ->add('game', ChoiceType::class, [
                'choices' => [
                    'Warzone' => 'Warzone',
                    'CDL' => 'CDL',
                    'Tournament' => 'Tournament',
                ],
            ])
            ->add('mode', TextType::class)
            ->add('result', ChoiceType::class, [
                'choices' => [
                    'Victory' => 'Victory',
                    'Defeat' => 'Defeat',
                ],
            ])
            ->add('score', TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => GameMatch::class]);
    }
}
