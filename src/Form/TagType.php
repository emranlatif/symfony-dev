<?php

namespace App\Form;

use App\Repository\TagRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;


class TagType extends AbstractType
{
    public function __construct(TagRepository $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name_en', null, ['label' => 'Tag EN']);
        $builder->add('name_nl', null, ['label' => 'Tag NL']);
        $builder->add('name_fr', null, ['label' => 'Tag FR']);
        $builder->add('save', SubmitType::class, ['attr' => ['class' => 'btn btn-primary']]);
    }

}
