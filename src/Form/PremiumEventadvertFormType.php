<?php

namespace App\Form;

use App\Entity\EventadvertPremium;
use App\Repository\CategoryRepository;
use App\Repository\ChannelRepository;
use App\Repository\EventadvertRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class PremiumEventadvertFormType extends AbstractType
{
    private $categoryRepository;
    private $channelRepository;
    private $eventAdvertRepository;
    private $translator;

    public function __construct(TranslatorInterface $translator, CategoryRepository $categoryRepository, ChannelRepository $channelRepository, EventadvertRepository $eventAdvertRepository)
    {
        $this->categoryRepository = $categoryRepository;
        $this->channelRepository = $channelRepository;
        $this->eventAdvertRepository = $eventAdvertRepository;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title');
        $builder->add('redirection_type', ChoiceType::class, [
            'choices' => [
                'Website' => 0,
                'Standaard advertentie' => 1
            ],
            'required' => true,
        ]);

        $builder->add('redirection_link');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EventadvertPremium::class,
            // 'validation_groups' => false,
        ]);
    }
}