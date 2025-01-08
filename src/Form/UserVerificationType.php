<?php

namespace App\Form;

use App\Entity\UserVerification;
use phpDocumentor\Reflection\Types\Self_;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserVerificationType extends AbstractType
{
    const array COMPANY_ACTIONS = [
        'I want to claim the company', 'I want this company profile deleted'
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'constraints' => [
                    new NotBlank(message: 'This field is required'),
                ],
            ])
            ->add('surname', null, [
                'constraints' => [
                    new NotBlank(message: 'This field is required'),
                ],
            ])
            ->add('companyName', null, [
                'constraints' => [
                    new NotBlank(message: 'This field is required'),
                ],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(message: 'This field is required'),
                ],
            ])
            ->add('proofOfOwnership', FileType::class, [
                'constraints' => [
                    new NotBlank(message: 'This field is required'),
                ],
                'help' => 'Upload any document stating your ownership to this business',
                'mapped' => false
            ])
            ->add('companyAction', ChoiceType::class, [
                'choices' => array_combine(self::COMPANY_ACTIONS, self::COMPANY_ACTIONS),
                'expanded' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserVerification::class,
        ]);
    }
}
