<?php


namespace App\Form;

use App\Entity\Company;
use App\Entity\Message;
use App\Entity\OpeningHour;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use libphonenumber\PhoneNumberFormat;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;


class MessageFormType extends AbstractType
{


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('sender', EmailType::class, [
            'required' => 'true'
        ]);

        $builder->add('subject');
        $builder->add('message', TextareaType::class);
        $builder->add('Verstuur', SubmitType::class, [
            'label' => 'send',
            'validate' => false,
            'attr' => ['class' => 'btn btn-lg p-2 btn-pink btn-block']
        ]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Message::class
        ]);
    }
}