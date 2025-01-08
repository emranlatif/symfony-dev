<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Form\CategoryType;

use App\Repository\ChannelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class CategoryController extends AbstractController
{

    #[Route(path: '/admin/category', name: 'admin_category')]
    public function category(Request $request, EntityManagerInterface $em, CategoryRepository $categoryRepository, ChannelRepository $channelRepository)
    {
        $data = null;

        if ($request->query->get('id') && $request->query->get('ac') == 'del') {
            $category = $em->getRepository(Category::class)->find($request->query->get('id'));
            $em->remove($category);
            $em->flush();
            return $this->redirectToRoute('admin_category');
        }

        if ($request->query->get('id') && $request->query->get('ac') == 'edit') {
            $category = $em->getRepository(Category::class)->find($request->query->get('id'));
            $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');
            $translations = $repository->findTranslations($category);

            $data = [
                'channel' => $category->getChannel(),
                'parent' => $category->getChannel() . '-' . $category->getId(),
                'title_en' => (isset($translations['en']['title'])) ? $translations['en']['title'] : null,
                'description_en' => (isset($translations['en']['description'])) ? $translations['en']['description'] : null,
                'title_nl' => (isset($translations['nl']['title'])) ? $translations['nl']['title'] : null,
                'description_nl' => (isset($translations['nl']['description'])) ? $translations['nl']['description'] : null,
                'title_fr' => (isset($translations['fr']['title'])) ? $translations['fr']['title'] : null,
                'description_fr' => (isset($translations['fr']['description'])) ? $translations['fr']['description'] : null,

                'featured' => $category->getFeatured() == 1,
            ];

        } else {
            $category = new Category();
        }

        $form = $this->createForm(CategoryType::class, $data);


        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $form_data = $form->getData();

            $p = explode('-', $form_data['parent']);
            $parent = end($p);
            $parent = ($parent > 0) ? (int)$parent : null;

            $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');
            $category->setChannel((int)$form_data['channel']);
            $category->setParent($parent);
            $category->setTitle($form_data['title_en']);
            $category->setDescription($form_data['description_en']);
            $category->setFeatured($form_data['featured']);
            $category->setTranslatableLocale('en');

            $repository
                ->translate($category, 'titleSlug', 'nl', $form_data['title_nl'])
                ->translate($category, 'title', 'nl', $form_data['title_nl'])
                ->translate($category, 'titleSlug', 'fr', $form_data['title_fr'])
                ->translate($category, 'title', 'fr', $form_data['title_fr'])
                ->translate($category, 'titleSlug', 'en', $form_data['title_en'])
                ->translate($category, 'title', 'en', $form_data['title_en'])
                ->translate($category, 'description', 'nl', $form_data['description_nl'])
                ->translate($category, 'description', 'fr', $form_data['description_fr'])
                ->translate($category, 'description', 'en', $form_data['description_en']);

            $em->persist($category);
            $em->flush();


            return $this->redirectToRoute('admin_category');
        }


        return $this->render('admin/category/index.html.twig', [
            'form' => $form->createView(),
            'channels' => $channelRepository->findAll(),
            'categories' => $categoryRepository->getAllOrderedByChannel()
        ]);
    }

}
