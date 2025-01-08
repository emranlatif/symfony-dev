<?php

namespace App\Controller\Admin;

use App\Entity\Tag;
use App\Repository\TagRepository;
use App\Form\TagType;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class TagController extends AbstractController
{

    #[Route(path: '/admin/tag', name: 'admin_tag')]
    public function tag(Request $request, EntityManagerInterface $em, TagRepository $tagRepository)
    {
        $data = null;

        if ($request->query->get('id') && $request->query->get('ac') == 'del') {
            $tag = $em->getRepository(Tag::class)->find($request->query->get('id'));
            $em->remove($tag);
            $em->flush();
            return $this->redirectToRoute('admin_tag');
        }

        if ($request->query->get('id') && $request->query->get('ac') == 'edit') {
            $tag = $em->getRepository(Tag::class)->find($request->query->get('id'));
            $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');
            $translations = $repository->findTranslations($tag);

            $data = [
                'name_en' => (isset($translations['en']['name'])) ? $translations['en']['name'] : null,
                'name_nl' => (isset($translations['nl']['name'])) ? $translations['nl']['name'] : null,
                'name_fr' => (isset($translations['fr']['name'])) ? $translations['fr']['name'] : null,
            ];
        } else {
            $tag = new Tag();
        }

        $form = $this->createForm(TagType::class, $data);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $form_data = $form->getData();

            $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');
            $tag->setName($form_data['name_en']);
            $tag->setTranslatableLocale('en');

            $repository
                ->translate($tag, 'name', 'nl', $form_data['name_nl'])
                ->translate($tag, 'name', 'fr', $form_data['name_fr'])
                ->translate($tag, 'name', 'en', $form_data['name_en']);

            $em->persist($tag);
            $em->flush();


            return $this->redirectToRoute('admin_tag');
        }

        return $this->render('admin/tag/index.html.twig', [
            'form' => $form->createView(),
            'tags' => $tagRepository->findAll(),
        ]);
    }

}
