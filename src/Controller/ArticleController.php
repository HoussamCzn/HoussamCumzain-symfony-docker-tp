<?php

namespace App\Controller;

use DateTimeImmutable;

use App\Entity\Article;
use App\Form\ArticleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route("/article")]
class ArticleController extends AbstractController
{
    #[Route("/", name: "app_article")]
    public function index(): Response
    {
        return $this->redirectToRoute("app_article_list");
    }

    #[IsGranted('ROLE_USER')]
    #[Route("/create", name: "app_article_create")]
    public function createArticle(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $article = new Article();

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $detailsFile = $form->get('details')->getData();

            if ($detailsFile) {
                $originalFilename = pathinfo($detailsFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$detailsFile->guessExtension();

                try {
                    $detailsFile->move(
                        $this->getParameter('details_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    return $this->redirectToRoute("app_article_list");
                }

                $article->setDetailsFilename($newFilename);
            }

            $em->persist($article);
            $em->flush();

            return $this->redirectToRoute("app_article_list");
        }
        return $this->render("article/create.html.twig", [
            "form" => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route("/edit/{id}", name: "app_article_edit")]
    public function editArticle(
        Article $article,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($article);
            $em->flush();

            return $this->redirectToRoute("app_article_list");
        }

        return $this->render("article/edit.html.twig", [
            'form' => $form->createView(),
            'article' => $article,
        ]);
    }

    #[IsGranted('ROLE_USER')]
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

    #[IsGranted('ROLE_USER')]
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

        return $this->redirectToRoute("app_article_list");
    }
}
