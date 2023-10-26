<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Article;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Form
use App\Form\ArticleType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

#[Route("/article")]
class ArticleController extends AbstractController
{
    #[Route("/", name: "app_article")]
    public function index(): Response
    {
        return $this->render('article/index.html.twig', [
            'controller_name' => 'ArticleController',
        ]);
    }

    //#[IsGranted('ROLE_ADMIN')]
    #[Route("/create", name: "app_article_create")]
    public function createArticle(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $article = new Article();

        $form = $this->createFormBuilder($article)
            ->add("title", TextType::class)
            ->add("description", TextType::class)
            ->add("save", SubmitType::class, ["label" => 'Create Article'])
            ->getForm();
        //dd($form);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($article);
            $em->flush();
        }
        return $this->render("article/create.html.twig", [
            "form" => $form->createView(),
        ]);
    }

    #[Route("/edit/{id}", name: "app_article_edit")]
    public function editArticle(
        $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $article = $em->getRepository(Article::class)->find($id);

        if (!$article) {
            throw $this->createNotFoundException(
                'No article found for id ' . $id
            );
        }

        if ($request->isMethod("POST")) {
            $article->setTitle($request->request->get("title"));
            $article->setDescription($request->request->get("description"));
            $em->flush();

            return $this->redirectToRoute("app_article_list");
        }

        return $this->render("article/edit.html.twig", [
            "article" => $article,
        ]);
    }

    #[Route("/show/{id}", name: "app_article_show")]
    public function showArticle($id, EntityManagerInterface $em): Response
    {
        $article = $em->getRepository(Article::class)->find($id);

        if (!$article) {
            throw $this->createNotFoundException(
                "No article found for id " . $id
            );
        }

        return $this->render("article/show.html.twig", [
            "article" => $article,
        ]);
    }

    #[Route("/list", name: "app_article_list")]
    public function listArticles(EntityManagerInterface $em): Response
    {
        $articles = $em->getRepository(Article::class)->findAll();

        return $this->render("article/index.html.twig", [
            "articles" => $articles,
        ]);
    }

    #[Route("/delete/{id}", name: "app_article_delete")]
    public function deleteArticle($id, EntityManagerInterface $em): Response
    {
        $article = $em->getRepository(Article::class)->find($id);

        if (!$article) {
            throw $this->createNotFoundException(
                'Aucun article trouvé avec l\'id ' . $id
            );
        }

        $em->remove($article);
        $em->flush();

        return $this->redirectToRoute("app_article");
    }
}
