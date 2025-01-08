<?php

namespace App\Form;

use App\Entity\Eventadvert;
use App\Repository\CategoryRepository;
use App\Repository\ChannelRepository;
use DateTime;
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
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class EventadvertFormType extends AbstractType
{
    private $categoryRepository;
    private $channelRepository;
    private $translator;

    public function __construct(TranslatorInterface $translator, CategoryRepository $categoryRepository, ChannelRepository $channelRepository)
    {
        $this->categoryRepository = $categoryRepository;
        $this->channelRepository = $channelRepository;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $categories = $this->getParentCategories();

        $builder->add('channel', ChoiceType::class, [
            'choices' => $this->getChannelCategories(),
            'required' => true,
        ]);

        $builder->add('category', ChoiceType::class, [
            'choices' => $categories['choices'],
            'choice_attr' => $categories['attr'],
            //'attr' => ['class' => 'd-none'],
            'label_attr' => ['class' => 'd-none'],
            'required' => true
        ]);

        // dd($categories);

        $builder->add('subCategory', ChoiceType::class, [
            'choices' => $categories['sub_categories']['choices'],
            'choice_attr' => $categories['sub_categories']['attr'],
            'label_attr' => ['class' => 'd-none'],
            'required' => false
        ]);

        $builder->add('title');

        $builder->add('price', MoneyType::class);

        $builder->add('description', CKEditorType::class, [
            'config' => [
                'uiColor' => '#ffffff',
                //...
            ],
        ]);
        $builder->add('eventStartDate', DateType::class, [
            'widget' => 'choice',
            'years' => range(date('Y'), date('Y') + 5),
            'data' => ($options['startDate'] ? $options['startDate'] : new DateTime())
        ]);
        $builder->add('eventEndDate', DateType::class, [
            'widget' => 'choice',
            'years' => range(date('Y'), date('Y') + 5),
            'data' => ($options['endDate'] ? $options['endDate'] : new DateTime())
        ]);
        $builder->add('startHour', TimeType::class, [
            'input' => 'datetime',
            'widget' => 'choice',
            'minutes' => [
                0,
                15,
                30,
                45
            ]
        ]);

        $builder->add('endHour', TimeType::class, [
            'input' => 'datetime',
            'widget' => 'choice',
            'minutes' => [
                0,
                15,
                30,
                45
            ]
        ]);

        $builder->add('address', TextType::class, [
            'empty_data' => ''
        ]);
        $builder->add('housenumber', TextType::class, [
            'empty_data' => ''
        ]);
        $builder->add('box', TextType::class, [
            'empty_data' => ''
        ]);
        $builder->add('geoPlacesId', HiddenType::class, [
            'error_bubbling' => false,
            'empty_data' => ''
        ]);
        $builder->add('latitude');
        $builder->add('longitude');

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Eventadvert::class,
            'startDate' => new DateTime(),
            'endDate' => new DateTime(),
            // 'validation_groups' => false,
        ]);

    }

    private function getParentCategories()
    {
        $default = $this->translator->trans('Category');

        $res = [$default => 0];
        $res2 = [];
        $subCats = [];
        $subCatsAttr = [];
        foreach ($this->categoryRepository->getParents() as $c) {
            $subCategories = $this->categoryRepository->getParentsChid($c->getId());
            foreach ($subCategories as $subCat)
            {
                $subCats[$subCat->getTitle()] = $subCat->getId();
                $subCatsAttr[$subCat->getTitle()] = ['data-category' => $c->getTitle()];
            }
            $res[$c->getTitle()] = $c->getId();
            $res2[$c->getTitle()] = ['data-channel' => $c->getChannel()];
        }

        return [
            'choices' => $res,
            'attr' => $res2,
            'sub_categories' => [
                'choices' => $subCats,
                'attr' => $subCatsAttr
            ]
        ];
    }

    private function getChannelCategories()
    {
        $default = $this->translator->trans('Channel');

        $res = [$default => 0];
        foreach ($this->channelRepository->findAll() as $c) {
            $res[$c->getName()] = $c->getId();
        }

        return $res;
    }


}