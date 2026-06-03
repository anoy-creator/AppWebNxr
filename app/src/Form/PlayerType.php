<?php

namespace App\Form;

use App\Entity\Player;
use App\Entity\Roster;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlayerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', TextType::class)
            ->add('discordId', TextType::class, [
                'required' => false,
            ])
            ->add('avatar', TextType::class)
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'Staff' => 'Staff',
                    'Joueur' => 'Joueur',
                    'Streamer' => 'Streamer',
                    'Createur' => 'Createur',
                ],
            ])
            ->add('grade', TextType::class)
            ->add('game', TextType::class)
            ->add('roster', EntityType::class, [
                'class' => Roster::class,
                'choice_label' => 'name',
                'required' => false,
            ])
            ->add('socials', TextareaType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Player::class]);
    }
}
