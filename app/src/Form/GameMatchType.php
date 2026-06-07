<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\GameMatch;
use App\Entity\Player;
use App\Entity\Roster;
use App\Entity\Team;
use App\Repository\EventRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GameMatchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('playedAt', DateType::class, [
                'widget' => 'single_text',
            ])
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
            ->add('tournament', EntityType::class, [
                'class' => Event::class,
                'choice_label' => 'title',
                'required' => false,
                'placeholder' => 'Aucun tournoi',
                'query_builder' => fn (EventRepository $repo) => $repo->createQueryBuilder('e')
                    ->where('e.type = :type')
                    ->setParameter('type', 'tournament')
                    ->orderBy('e.date', 'DESC'),
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
                'required' => false,
            ])
            ->add('substitutes', EntityType::class, [
                'class' => Player::class,
                'choice_label' => 'pseudo',
                'multiple' => true,
                'required' => false,
            ])
            ->add('opponents', TextareaType::class, ['required' => false])
            ->add('game', ChoiceType::class, [
                'choices' => array_combine(GameMatch::Games, GameMatch::Games),
            ])
            ->add('mode', ChoiceType::class, [
                'choices' => array_combine(GameMatch::Modes, GameMatch::Modes),
            ])
            ->add('mapName', TextType::class, ['required' => false])
            ->add('result', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Victory' => 'Victory',
                    'Defeat' => 'Defeat',
                ],
            ])
            ->add('score', TextType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GameMatch::class,
            'csrf_protection' => false,
        ]);
    }
}
