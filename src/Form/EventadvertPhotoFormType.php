<?php


namespace App\Form;

use App\Entity\EventadvertPhoto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;


class EventadvertPhotoFormType extends AbstractType
{


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $$builder
            ->add('photo', VichImageType::class);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EventadvertPhoto::class
        ]);
    }
}