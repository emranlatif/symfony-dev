<?php


namespace App\Form;

use App\Entity\Company;
use App\Entity\OpeningHour;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use libphonenumber\PhoneNumberFormat;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;


class CompanyFormType extends AbstractType
{


    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('companyname');
        $builder->add('address');
        $builder->add('housenumber');
        $builder->add('box');
        $builder->add('geoPlacesId', HiddenType::class, [
            'error_bubbling' => false
        ]);
        $builder->add('phonenumber', PhoneNumberType::class, array(
            'default_region' => 'BE',
            'format' => PhoneNumberFormat::INTERNATIONAL
        ));
        $builder->add('emailaddress', EmailType::class);
        $builder->add('website', UrlType::class);
        $builder->add('vatnumber');

        $builder->add('description', CKEditorType::class, [
            'empty_data' => '',
            'config' => [
                'uiColor' => '#ffffff',
            ],
        ]);

        //$builder->add('companyTags', ChoiceType::class.);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Company::class
        ]);
    }
}