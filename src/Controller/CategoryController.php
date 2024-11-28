<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{
    #[Route('/category/{id}', name: 'category_posts')]
    public function postsByCategory(Category $category, PostRepository $postRepository): Response
    {
        $posts = $postRepository->findByCategory($category);

        return $this->render('category/postsByCategory.html.twig', [
            'category' => $category,
            'posts' => $posts,
        ]);
    }

    #[Route('/categories', name: 'category_list')]
    public function listCategories(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }
}