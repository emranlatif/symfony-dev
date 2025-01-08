<?php

namespace App\Form;

use App\Repository\CategoryRepository;
use App\Repository\ChannelRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;


class CategoryType extends AbstractType
{
    public function __construct(CategoryRepository $categoryRepository, ChannelRepository $channelRepository)
    {
        $this->categoryRepository = $categoryRepository;
        $this->channelRepository = $channelRepository;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('channel', ChoiceType::class, [
            'choices' => $this->getChannelCategories(),
            'required' => true,
        ]);

        $builder->add('parent', ChoiceType::class, [
            'placeholder' => 'Select parent',
            'choices' => $this->getParentCategories(),
            'attr' => ['class' => 'd-none'],
            'label_attr' => ['class' => 'd-none'],
            'required' => false,
        ]);

        $builder->add('title_en', null, ['label' => 'Title EN']);
        $builder->add('title_nl', null, ['label' => 'Title NL']);
        $builder->add('title_fr', null, ['label' => 'Title FR']);
        $builder->add('description_en', TextareaType::class, ['label' => 'Description EN']);
        $builder->add('description_nl', TextareaType::class, ['label' => 'Description NL']);
        $builder->add('description_fr', TextareaType::class, ['label' => 'Description FR']);
        $builder->add('featured', CheckboxType::class, [
            'label' => 'Featured',
            'required' => false
        ]);

        $builder->add('save', SubmitType::class, ['attr' => ['class' => 'btn btn-primary']]);
    }

    private function getParentCategories()
    {
        $res = ['' => 0];
        foreach ($this->categoryRepository->getParents() as $c) {
            $res[$c->getTitle()] = $c->getChannel() . '-' . $c->getId();
        }

        return $res;
    }

    private function getChannelCategories()
    {
        $res = ['' => 0];
        foreach ($this->channelRepository->findAll() as $c) {
            $res[$c->getName()] = $c->getId();
        }

        return $res;
    }
}
